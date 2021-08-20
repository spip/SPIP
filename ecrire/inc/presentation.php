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
 * Fonctions pour l'affichage privé des pages exec PHP
 *
 * @package SPIP\Core\Presentation
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/presentation_mini');
include_spip('inc/layer');
include_spip('inc/texte');
include_spip('inc/filtres');
include_spip('inc/boutons');
include_spip('inc/actions');
include_spip('inc/puce_statut');
include_spip('inc/filtres_ecrire');
include_spip('inc/filtres_boites');
include_spip('inc/filtres_alertes');

// https://code.spip.net/@debut_cadre
function debut_cadre($style, $icone = '', $fonction = '', $titre = '', $id = '', $class = '', $padding = true) {
	$style_mapping = [
		'r' => 'simple',
		'e' => 'raccourcis',
		'couleur' => 'basic highlight',
		'couleur-foncee' => 'basic highlight',
		'trait-couleur' => 'important',
		'alerte' => 'notice',
		'info' => 'info',
		'sous_rub' => 'simple sous-rub'
	];
	$style_titre_mapping = ['couleur' => 'topper', 'trait-couleur' => 'section'];
	$c = isset($style_mapping[$style]) ? $style_mapping[$style] : 'simple';
	$class = $c . ($class ? " $class" : '');
	if (!$padding) {
		$class .= ($class ? ' ' : '') . 'no-padding';
	}

	//($id?"id='$id' ":"")
	if (strlen($icone) > 1) {
		if ($icone_renommer = charger_fonction('icone_renommer', 'inc', true)) {
			list($fond, $fonction) = $icone_renommer($icone, $fonction);
		}
		$size = 24;
		if (preg_match('/-([0-9]{1,3})[.](gif|png)$/i', $fond, $match)) {
			$size = $match[1];
		}
		if ($fonction) {
			// 2 images pour composer l'icone : le fond (article) en background,
			// la fonction (new) en image
			$icone = http_img_pack($fonction, '', "class='cadre-icone' width='$size' height='$size'\n" .
				http_style_background($fond, 'no-repeat center center', $size));
		} else {
			$icone = http_img_pack($fond, '', "class='cadre-icone' width='$size' height='$size'");
		}
		$titre = $icone . $titre;
	}

	return boite_ouvrir($titre, $class, isset($style_titre_mapping[$style]) ? $style_titre_mapping[$style] : '', $id);
}

// https://code.spip.net/@fin_cadre
function fin_cadre() {
 return boite_fermer();
}


function debut_cadre_relief(
	$icone = '',
	$dummy = '',
	$fonction = '',
	$titre = '',
	$id = '',
	$class = ''
) {
	return debut_cadre('r', $icone, $fonction, $titre, $id, $class);
}

function fin_cadre_relief() {
 return fin_cadre('r');
}

function debut_cadre_enfonce(
	$icone = '',
	$dummy = '',
	$fonction = '',
	$titre = '',
	$id = '',
	$class = ''
) {
	return debut_cadre('e', $icone, $fonction, $titre, $id, $class);
}

function fin_cadre_enfonce() {
 return fin_cadre('e');
}

function debut_cadre_sous_rub(
	$icone = '',
	$dummy = '',
	$fonction = '',
	$titre = '',
	$id = '',
	$class = ''
) {
	return debut_cadre('sous_rub', $icone, $fonction, $titre, $id, $class);
}

function fin_cadre_sous_rub() {
 return fin_cadre('sous_rub');
}

function debut_cadre_couleur(
	$icone = '',
	$dummy = '',
	$fonction = '',
	$titre = '',
	$id = '',
	$class = ''
) {
	return debut_cadre('couleur', $icone, $fonction, $titre, $id, $class);
}

function fin_cadre_couleur() {
 return fin_cadre('couleur');
}

function debut_cadre_trait_couleur(
	$icone = '',
	$dummy = '',
	$fonction = '',
	$titre = '',
	$id = '',
	$class = ''
) {
	return debut_cadre('trait-couleur', $icone, $fonction, $titre, $id, $class);
}

function fin_cadre_trait_couleur() {
 return fin_cadre('trait-couleur');
}

function debut_boite_alerte() {
 return debut_cadre('alerte', '', '', '', '', '');
}

function fin_boite_alerte() {
 return fin_cadre('alerte');
}

function debut_boite_info() {
 return debut_cadre('info', '', '', '', '', '');
}

function fin_boite_info() {
 return fin_cadre('info');
}

/**
 * Affiche le titre d’une page de l’interface privée. Utilisée par la plupart des fichiers `exec/xx.php`.
 *
 * @param string $titre Le titre en question
 * @param string $ze_logo Une image de logo
 * @return string Code PHP.
 **/
function gros_titre(
	$titre,
	$ze_logo = ''
) {
	return "<h1 class = 'grostitre'>" . $ze_logo . ' ' . typo($titre) . "</h1>\n";
}

// La boite des raccourcis
// Se place a droite si l'ecran est en mode panoramique.
// https://code.spip.net/@bloc_des_raccourcis
function bloc_des_raccourcis($bloc) {
	return creer_colonne_droite()
	. boite_ouvrir(_T('titre_cadre_raccourcis'), 'raccourcis') . $bloc . boite_fermer();
}

//
// Fonctions d'affichage
//

// Fonctions onglets
// https://code.spip.net/@debut_onglet
// @param string $sous_classe	prend la valeur second pour definir les onglet de deuxieme niveau
function debut_onglet($classe = 'barre_onglet') {
 return "<div class = '$classe clearfix'><ul>\n";
}

// https://code.spip.net/@fin_onglet
function fin_onglet() {
 return "</ul></div>\n";
}

// https://code.spip.net/@onglet
function onglet($texte, $lien, $onglet_ref, $onglet, $icone = '') {
	return '<li>'
	. ($icone ? http_img_pack($icone, '', " class='cadre-icone'") : '')
	. lien_ou_expose($lien, $texte, $onglet == $onglet_ref)
	. '</li>';
}

/**
 * Crée un lien précédé d'une icone au dessus du texte
 *
 * @uses icone_base()
 * @see  filtre_icone_verticale_dist() Pour l'usage en tant que filtre
 *
 * @example
 *     ```
 *     $icone = icone_verticale(_T('sites:info_sites_referencer'),
 *         generer_url_ecrire('site_edit', "id_rubrique=$id_rubrique"),
 *         "site-24.png", "new", 'right')
 *     ```
 *
 * @param string $texte
 *     Texte du lien
 * @param string $lien
 *     URL du lien
 * @param string $fond
 *     Objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *     Fonction du lien (`edit`, `new`, `del`)
 * @param string $align
 *     Classe CSS, tel que `left`, `right` pour définir un alignement
 * @param string $javascript
 *     Javascript ajouté sur le lien
 * @return string
 *     Code HTML du lien
 **/
function icone_verticale($texte, $lien, $fond, $fonction = '', $align = '', $javascript = '') {
	// cas d'ajax_action_auteur: faut defaire le boulot
	// (il faudrait fusionner avec le cas $javascript)
	if (preg_match(",^<a\shref='([^']*)'([^>]*)>(.*)</a>$,i", $lien, $r)) {
		list($x, $lien, $atts, $texte) = $r;
		$javascript .= $atts;
	}

	return icone_base($lien, $texte, $fond, $fonction, "verticale $align", $javascript);
}

/**
 * Crée un lien précédé d'une icone horizontale
 *
 * @uses icone_base()
 * @see  filtre_icone_horizontale_dist() Pour l'usage en tant que filtre
 *
 * @param string $texte
 *     Texte du lien
 * @param string $lien
 *     URL du lien
 * @param string $fond
 *     Objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *     Fonction du lien (`edit`, `new`, `del`)
 * @param string $dummy
 *     Inutilisé
 * @param string $javascript
 *     Javascript ajouté sur le lien
 * @return string
 *     Code HTML du lien
 **/
function icone_horizontale($texte, $lien, $fond, $fonction = '', $dummy = '', $javascript = '') {
	$retour = '';
	// cas d'ajax_action_auteur: faut defaire le boulot
	// (il faudrait fusionner avec le cas $javascript)
	if (preg_match(",^<a\shref='([^']*)'([^>]*)>(.*)</a>$,i", $lien, $r)) {
		list($x, $lien, $atts, $texte) = $r;
		$javascript .= $atts;
	}

	$retour = icone_base($lien, $texte, $fond, $fonction, 'horizontale', $javascript);

	return $retour;
}
