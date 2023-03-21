<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/texte');

function inc_plonger_dist($id_rubrique, $idom = '', $list = [], $col = 1, $exclu = 0, $do = 'aff') {

	if ($list) {
		$id_rubrique = $list[$col - 1];
	}

	$ret = '';

	# recherche les filles et petites-filles de la rubrique donnee
	# en excluant une eventuelle rubrique interdite (par exemple, lorsqu'on
	# deplace une rubrique, on peut la deplacer partout a partir de la
	# racine... sauf vers elle-meme ou sa propre branche)
	$ordre = [];
	$rub = [];

	$res = sql_select(
		'rub1.id_rubrique, rub1.titre, rub1.id_parent, rub1.lang, rub1.langue_choisie, rub2.id_rubrique AS id_enfant',
		'spip_rubriques AS rub1 LEFT JOIN spip_rubriques AS rub2 ON (rub1.id_rubrique = rub2.id_parent)',
		'rub1.id_parent = ' . sql_quote($id_rubrique) . '
			AND rub1.id_rubrique!=' . sql_quote($exclu) . '
			AND (rub2.id_rubrique IS NULL OR rub2.id_rubrique!=' . sql_quote($exclu) . ')',
		'',
		'0+rub1.titre,rub1.titre'
	);

	while ($row = sql_fetch($res)) {
		if (autoriser('voir', 'rubrique', $row['id_rubrique'])) {
			$rub[$row['id_rubrique']]['enfants'] = $row['id_enfant'];
			if ($row['id_parent'] == $id_rubrique) {
				$t = trim((string) typo(supprimer_numero($row['titre'])));
				if ($row['langue_choisie'] != 'oui') {
					$t .= ' <small title="'
						. traduire_nom_langue($row['lang'])
						. '">[' . $row['lang'] . ']</small>';
				}
				$ordre[$row['id_rubrique']] = $t;
			}
		}
	}
	$next = $list[$col] ?? 0;
	if ($ordre) {
		$rec = generer_url_ecrire('plonger', "rac=$idom&exclus=$exclu&do=$do&col=" . ($col + 1));
		$info = generer_url_ecrire('informer', "type=rubrique&rac=$idom&do=$do&id=");
		$args = "'$idom',this,$col,'" . $GLOBALS['spip_lang_left'] . "','$info',event";

		foreach ($ordre as $id => $titrebrut) {
			$titre = supprimer_numero($titrebrut);

			$classe1 = 'petit-item ' . ($id_rubrique ? 'petite-rubrique' : 'petit-secteur');
			if (isset($rub[$id]['enfants'])) {
				$classe2 = " class='rub-ouverte'";
				$url = "\nhref='$rec&amp;id=$id'";
			} else {
				$classe2 = $url = '';
				$url = "\nhref='javascript:void(0)'";
			}

			$js_func = $do . '_selection_titre';
			$click = "\nonclick=\"changerhighlight(this.parentNode.parentNode.parentNode);\nreturn "
				. (is_array($list) ? "aff_selection_provisoire($id,$args)" : ' false')
# ce lien provoque la selection (directe) de la rubrique cliquee
# et l'affichage de son titre dans le bandeau
				. "\"\nondblclick=\""
				. "$js_func(this."
				. 'firstChild.nodeValue,'
				. $id
				. ",'selection_rubrique','id_parent');"
				. "\nreturn aff_selection_provisoire($id,$args);"
				. '"';

			$ret .= "<div class='"
				. (($id == $next) ? 'item on' : 'item')
				. "'><div class='"
				. $classe1
				. "'><div$classe2><a"
				. $url
				. $click
				. '>'
				. $titre
				. '</a></div></div></div>';
		}
	}

	$idom2 = $idom . '_col_' . ($col + 1);
	$left = ($col * 250);

	return http_img_pack(
		'loader.svg',
		'',
		"class='loader' style='visibility: hidden; position: absolute; " . $GLOBALS['spip_lang_left'] . ': '
		. ($left - 30)
		. "px; top: 2px; z-index: 2;' id='img_$idom2'"
	)
	. "<div style='width: 250px; height: 100%; overflow: auto; position: absolute; top: 0px; " . $GLOBALS['spip_lang_left'] . ': '
	. ($left - 250)
	. "px;'>"
	. $ret
	. "\n</div>\n<div id='$idom2'>"
	. ($next
		? inc_plonger_dist($id_rubrique, $idom, $list, $col + 1, $exclu)
		: '')
	. "\n</div>";
}
