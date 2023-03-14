<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Présentation de l'interface privee (exec PHP), début du HTML
 *
 * @package SPIP\Core\Presentation
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Débute une page HTML pour l'espace privé
 *
 * Préferer l'usage des squelettes prive/squelettes/.
 *
 * @uses init_entete()
 * @uses init_body()
 * @example
 *     ```
 *     $commencer_page = charger_fonction('commencer_page','inc');
 *     echo $commencer_page($titre);
 *     ```
 *
 * @param string $titre Titre de la page
 * @param string $rubrique ?
 * @param string $sous_rubrique ?
 * @param string $id_rubrique ?
 * @param bool $menu ?
 * @param bool $minipres ?
 * @param bool $alertes ?
 * @return string Code HTML
 **/
function inc_commencer_page_dist(
	$titre = '',
	$rubrique = 'accueil',
	$sous_rubrique = 'accueil',
	$id_rubrique = '',
	$menu = true,
	$minipres = false,
	$alertes = true
) {

	include_spip('inc/headers');

	http_no_cache();

	return init_entete($titre, $id_rubrique, $minipres)
	. init_body($rubrique, $sous_rubrique, $id_rubrique, $menu)
	. "<div id='page'>"
	. auteurs_recemment_connectes($GLOBALS['connect_id_auteur'])
	. ($alertes ? alertes_auteur($GLOBALS['connect_id_auteur']) : '')
	. '<div class="largeur">';
}

/**
 * Envoi du DOCTYPE et du `<head><title>   </head>`
 *
 * @uses _DOCTYPE_ECRIRE
 * @uses textebrut()
 * @uses typo()
 * @uses html_lang_attributes()
 * @uses init_head()
 *
 * @param string $titre
 *     Titre de la page
 * @param integer $dummy
 *     Valeur non utilisée…
 * @param bool $minipres
 * @return string
 *     Entête du fichier HTML avec le DOCTYPE
 */
function init_entete($titre = '', $dummy = 0, $minipres = false) {
	include_spip('inc/texte');
	if (!$nom_site_spip = textebrut(typo($GLOBALS['meta']['nom_site']))) {
		$nom_site_spip = _T('info_mon_site_spip');
	}

	$titre = '['
		. $nom_site_spip
		. ']'
		. ($titre ? ' ' . textebrut(typo($titre)) : '');

	return _DOCTYPE_ECRIRE
	. html_lang_attributes()
	. "<head>\n"
	. init_head($titre, $dummy, $minipres)
	. "</head>\n";
}

/**
 * Retourne le code HTML du head (intégration des JS et CSS) de l'espace privé
 *
 * Code HTML récupéré du squelette `prive/squelettes/head/dist`
 *
 * @param string $titre
 * @param integer $dummy
 * @param bool $minipres
 * @return string
 */
function init_head($titre = '', $dummy = 0, $minipres = false) {
	return recuperer_fond('prive/squelettes/head/dist', ['titre' => $titre, 'minipres' => $minipres ? ' ' : '']);
}

/**
 * Fonction envoyant la double série d'icônes de rédac
 *
 * @uses init_body_class()
 * @uses inc_bandeau_dist()
 *
 * @pipeline_appel body_prive
 *
 * @global mixed $connect_id_auteur
 * @global mixed $auth_can_disconnect
 *
 * @param string $rubrique
 * @param string $sous_rubrique
 * @param integer $id_rubrique
 * @param bool $menu
 * @return string
 */
function init_body($rubrique = 'accueil', $sous_rubrique = 'accueil', $id_rubrique = '', $menu = true) {

	$res = pipeline('body_prive', "<body class='"
		. init_body_class() . ' ' . _request('exec') . "'"
		. ($GLOBALS['spip_lang_rtl'] ? " dir='rtl'" : '')
		. '>');

	if (!$menu) {
		return $res;
	}


	$bandeau = charger_fonction('bandeau', 'inc');

	return $res
	. $bandeau();
}

/**
 * Calcule les classes CSS à intégrer à la balise `<body>` de l'espace privé
 *
 * Les classes sont calculées en fonction des préférences de l'utilisateur,
 * par exemple s'il choisit d'avoir ou non les icônes.
 *
 * @return string Classes CSS (séparées par des espaces)
 */
function init_body_class() {
	$display_modes = [
		0 => 'icones_img_texte' // défaut.
		/*init*/,
		1 => 'icones_texte',
		2 => 'icones_img_texte',
		3 => 'icones_img'
	];

	$prefs = $GLOBALS['visiteur_session']['prefs'] ?? [];

	$display_mode = $display_modes[(int) ($prefs['display'] ?? 0)] ?? $display_modes[0];
	$spip_display_navigation = isset($prefs['display_navigation']) ? spip_sanitize_classname($prefs['display_navigation']) : 'navigation_avec_icones';

	$couleur = (int) ($prefs['couleur'] ?? 2);

	$classes = "spip-theme-colors-$couleur $spip_display_navigation $display_mode";
	return spip_sanitize_classname($classes);
}


/**
 * Afficher la liste des auteurs connectés à l'espace privé
 *
 * @param integer $id_auteur
 * @return string
 */
function auteurs_recemment_connectes($id_auteur) {
	return recuperer_fond('prive/objets/liste/auteurs_enligne');
}
