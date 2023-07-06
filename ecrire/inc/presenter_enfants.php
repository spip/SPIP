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

include_spip('inc/autoriser');
include_spip('inc/presentation');

/**
 * Crée l'affichage des listes de rubriques dans le privé
 *
 * @param int $collection
 *  L'identifiant numérique de la rubrique à lister
 * @param int $debut
 *  Le numéro de la pagination si paginé (> 500)
 * @param int $limite
 *  Le pas de pagination si paginé (> 500)
 * @return array $res
 *  Un tableau des sous rubriques
 */
function enfant_rub($collection, $debut = 0, $limite = 500) {
	$voir_logo = (isset($GLOBALS['meta']['image_process']) && $GLOBALS['meta']['image_process'] != 'non');
	$logo = '';

	if ($voir_logo) {
		$chercher_logo = charger_fonction('chercher_logo', 'inc');
		include_spip('inc/filtres_images_mini');
	}

	$res = [];

	$result = sql_select(
		'id_rubrique, id_parent, titre, descriptif, lang',
		'spip_rubriques',
		'id_parent=' . (int) $collection,
		'',
		'0+titre,titre',
		$debut == -1 ? '' : "$debut,$limite"
	);
	while ($row = sql_fetch($result)) {
		$id_rubrique = $row['id_rubrique'];
		$id_parent = $row['id_parent'];
		// pour etre sur de passer par tous les traitements
		$titre = generer_objet_info($id_rubrique, 'rubrique', 'titre');
		if ('' !== ($rang = recuperer_numero($row['titre']))) {
			$rang = "<span class='rang'>$rang.</span> ";
		}

		if (autoriser('voir', 'rubrique', $id_rubrique)) {
			$les_sous_enfants = sous_enfant_rub($id_rubrique);

			changer_typo($row['lang']);
			$lang_dir = lang_dir($row['lang']);
			$descriptif = propre($row['descriptif']);

			if ($voir_logo && ($logo = $chercher_logo($id_rubrique, 'id_rubrique', 'on'))) {
				[$fid, $dir, $nom, $format] = $logo;
				$logo = image_recadre_avec_fallback("<img src='$fid' alt=''>", 70, 70);
				if ($logo) {
					$logo = wrap(inserer_attribut($logo, 'class', 'logo'), '<span class="logo-carre">');
				}
			}

			$lib_bouton = (acces_restreint_rubrique($id_rubrique)
				? http_img_pack('auteur-0minirezo-16.png', '', " width='16' height='16'", _T('image_administrer_rubrique'))
				: '') .
				" <a class='titremlien' dir='$lang_dir'" .
				($row['lang'] !== $GLOBALS['spip_lang'] ? " hreflang='" . $row['lang'] . "'" : '') .
				" href='" .
				generer_objet_url($id_rubrique, 'rubrique') .
				"'><span class='titre'>" .
				$rang . $titre
				. '</span>'
				. (is_string($logo) ? $logo : '')
				. '</a>';

			$titre = bouton_block_depliable($lib_bouton, $les_sous_enfants ? false : -1, "enfants$id_rubrique")
				. ($descriptif ? "\n<div class='descriptif'>$descriptif</div>" : '')
				;

			$res[] =
				debut_cadre_sous_rub(($id_parent ? 'rubrique-24.png' : 'secteur-24.png'), true, '', $titre) .
				$les_sous_enfants .
				fin_cadre_sous_rub();
		}
	}

	changer_typo($GLOBALS['spip_lang']); # remettre la typo de l'interface pour la suite
	return $res;
}

/**
 * Affiche les enfants d'une sous rubrique dans un bloc dépliable
 * (Utilisé dans les pages du privé)
 *
 * @param int $collection2
 *  L'identifiant numérique de la rubrique parente
 * @return string
 *  Le contenu du bloc dépliable
 */
function sous_enfant_rub($collection2) {
	$nb = sql_countsel('spip_rubriques', 'id_parent=' . (int) $collection2);

	$retour = '';
	$pagination = '';
	$debut = 0;
	$limite = 500;

	/**
	 * On ne va afficher que 500 résultats max
	 * Si > 500 on affiche une pagination
	 */
	if ($nb > $limite) {
		$debut = _request('debut_rubrique' . $collection2) ?: $debut;
		$pagination = chercher_filtre('pagination');
		$pagination = '<nav class="pagination">' . $pagination(
			$nb,
			'_rubrique' . $collection2,
			$debut,
			$limite,
			true,
			'prive'
		) . '</nav>';
		$limite = $debut + $limite;
	}

	$result = sql_select(
		'id_rubrique, id_parent, titre, lang',
		'spip_rubriques',
		'id_parent=' . (int) $collection2,
		'',
		'0+titre,titre',
		$debut == -1 ? '' : "$debut,$limite"
	);

	while ($row = sql_fetch($result)) {
		$id_rubrique2 = $row['id_rubrique'];
		$titre2 = generer_objet_info(
			$id_rubrique2,
			'rubrique',
			'titre'
		); // pour etre sur de passer par tous les traitements
		if ('' !== ($rang2 = recuperer_numero($row['titre']))) {
			$rang2 = "<span class='rang'>$rang2.</span> ";
		}

		changer_typo($row['lang']);
		$lang_dir = lang_dir($row['lang']);
		if (autoriser('voir', 'rubrique', $id_rubrique2)) {
			$retour .= "\n<li class='item' dir='$lang_dir'><a href='" . generer_objet_url(
				$id_rubrique2,
				'rubrique'
			) . "'>" . $rang2 . $titre2 . "</a></li>\n";
		}
	}

	$retour = $pagination . $retour . $pagination;

	if (!$retour) {
		return '';
	}

	return debut_block_depliable($debut > 0, "enfants$collection2")
	. "\n<ul class='liste-items sous-sous-rub'>\n"
	. $retour
	. "</ul>\n" . fin_block() . "\n\n";
}

/**
 * Affiche la liste des rubriques enfants d'une rubrique
 * (Utilisé dans les pages du privé notamment ?exec=rubriques)
 *
 * Si plus de 500 rubriques enfants, on pagine par 500 les résultats
 *
 * @param int $id_rubrique
 *  L'identifiant numérique de la rubrique parente (0 par défaut, la racine)
 * @return string $res
 *  Le contenu textuel affiché, la liste des sous rubriques
 */
function afficher_enfant_rub($id_rubrique = 0) {
	$pagination = '';
	$debut = 0;
	$limite = 500;

	$nb = sql_countsel('spip_rubriques', 'id_parent=' . (int) $id_rubrique);

	if ($nb > $limite) {
		$debut = _request('debut_rubrique' . $id_rubrique) ?: $debut;
		$pagination = chercher_filtre('pagination');
		$pagination = '<br class="nettoyeur"><nav class="pagination">' .
			$pagination($nb, '_rubrique' . $id_rubrique, $debut, $limite, true, 'prive') .
		'</nav>';
	}

	$les_enfants = enfant_rub($id_rubrique, $debut, $limite);

	if (!$n = count($les_enfants)) {
		return '';
	}

	if ($n == 1) {
		$les_enfants = reset($les_enfants);
		$les_enfants2 = '';
	} else {
		$n = ceil($n / 2);
		$les_enfants2 = implode('', array_slice($les_enfants, $n));
		$les_enfants = implode('', array_slice($les_enfants, 0, $n));
	}

	return $pagination
		. "<div class='gauche'>"
		. $les_enfants
		. '</div>'
		. "<div class='droite'>"
		. $les_enfants2
		. '</div>'
		. $pagination;
}
