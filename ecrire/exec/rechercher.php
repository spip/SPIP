<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion de la recherche ajax du mini navigateur de rubriques
 *
 * Cette possibilité de recherche apparaît s'il y a beaucoup de rubriques dans le site.
 *
 * @package SPIP\Core\Rechercher
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/texte');

/**
 * Prépare la fonction de recherche ajax du mini navigateur de rubriques
 *
 * @uses exec_rechercher_args() Formate le rendu de la recherche.
 * @uses ajax_retour()
 **/
function exec_rechercher_dist() {
	$id = intval(_request('id'));
	$exclus = intval(_request('exclus'));
	$rac = spip_htmlentities(_request('rac'));
	$type = _request('type');
	$do = _request('do');
	if (preg_match('/^\w*$/', $do)) {
		$r = exec_rechercher_args($id, $type, $exclus, $rac, $do);
	} else {
		$r = '';
	}
	ajax_retour($r);
}

/**
 * Formate le rendu de la recherche ajax du mini navigateur de rubriques
 *
 * @see calcul_branche_in()
 * @see proposer_item()
 *
 * @param int $id
 * @param string $type
 * @param string|int|array $exclus
 * @param string|bool $rac
 * @param string $do
 * @return string
 **/
function exec_rechercher_args($id, $type, $exclus, $rac, $do) {
	if (!$do) {
		$do = 'aff';
	}

	$points = $rub = [];

	$where = preg_split(',\s+,', $type);
	if ($where) {
		foreach ($where as $k => $v) {
			$where[$k] = "'%" . substr(str_replace('%', '\%', sql_quote($v, '', 'string')), 1, -1) . "%'";
		}
		$where_titre = ('(titre LIKE ' . join(' AND titre LIKE ', $where) . ')');
		$where_desc = ('(descriptif LIKE ' . join(' AND descriptif LIKE ', $where) . ')');
		$where_id = ('(id_rubrique = ' . intval($type) . ')');

		if ($exclus) {
			include_spip('inc/rubriques');
			$where_exclus = ' AND ' . sql_in('id_rubrique', calcul_branche_in($exclus), 'NOT');
		} else {
			$where_exclus = '';
		}

		foreach (
			[
			3 => $where_titre,
			2 => $where_desc,
			1 => $where_id,
			] as $point => $recherche
		) {
			$res = sql_select('id_rubrique, id_parent, titre', 'spip_rubriques', "$recherche$where_exclus");
			while ($row = sql_fetch($res)) {
				$id_rubrique = $row['id_rubrique'];
				if (!isset($rub[$id_rubrique])) {
					$rub[$id_rubrique] = [];
				}
				$rub[$id_rubrique]['titre'] = typo($row['titre']);
				$rub[$id_rubrique]['id_parent'] = $row['id_parent'];
				if (!isset($points[$id_rubrique])) {
					$points[$id_rubrique] = 0;
				}
				$points[$id_rubrique] = $points[$id_rubrique] + $point;
			}
		}
	}

	if ($points) {
		arsort($points);
		$style = " style='background-image: url(" . chemin_image('secteur-12.png') . ")'";
		foreach ($rub as $k => $v) {
			$rub[$k]['atts'] = ($v['id_parent'] ? $style : '')
				. " class='petite-rubrique'";
		}
	}

	return (proposer_item($points, $rub, $rac, $type, $do));
}


/**
 * Résultat de la recherche intéractive demandée par la fonction JS
 * `onkey_rechercher`
 *
 * @note
 *   `onkey_rechercher()` testera s'il comporte une seule balise au premier niveau
 *   car cela qui indique qu'un seul résultat a été trouvé.
 *
 *   Attention donc à composer le message d'erreur avec au moins 2 balises.
 *
 * @param array $ids
 * @param array|string $titles
 * @param string|bool $rac
 * @param string $type
 * @param string $do
 * @return string
 **/
function proposer_item($ids, $titles, $rac, $type, $do) {

	if (!$ids) {
		return "<br /><br /><div style='padding: 5px; color: red;'><b>"
		. spip_htmlentities($type)
		. '</b> :  ' . _T('avis_aucun_resultat') . '</div>';
	}

	$ret = '';
	$info = generer_url_ecrire('informer', "type=rubrique&rac=$rac&id=");

	$onClick = "aff_selection(this.firstChild.title,'$rac" . "_selection','$info', event)";

	$ondbClick = "$do(this.firstChild.firstChild.nodeValue,this.firstChild.title,'selection_rubrique', 'id_parent');";

	foreach ($ids as $id => $bof) {
		$titre = strtr(
			str_replace("'", '&#8217;', str_replace('"', '&#34;', textebrut($titles[$id]['titre']))),
			"\n\r",
			'  '
		);

		$ret .= "<div class='highlight off'\nonclick=\"changerhighlight(this); "
			. $onClick
			. "\"\nondblclick=\""
			. $ondbClick
			. $onClick
			. ' "><div'
			. $titles[$id]['atts']
			. " title='$id'>&nbsp; "
			. $titre
			. '</div></div>';
	}

	return $ret;
}
