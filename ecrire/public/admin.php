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
 * Affichage des boutons d'administration
 *
 * @package SPIP\Core\Administration
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Ajoute les boutons d'administration de la page s'ils n'y sont pas déjà
 *
 * Insère la feuille de style selon les normes, dans le `<head>`
 * puis les boutons.
 *
 * Feuilles de style admin : d'abord la CSS officielle, puis la perso
 *
 * @param string $contenu
 *     Contenu HTML de la page qui va être envoyée au navigateur
 * @return string
 *     Contenu HTML, avec boutons d'administrations et sa CSS
 **/
function affiche_boutons_admin($contenu) {
	include_spip('inc/filtres');

	// Inserer le css d'admin
	$css = "<link rel='stylesheet' href='" . url_absolue(direction_css(find_in_path('spip_admin.css')))
		. "' type='text/css' />\n";
	if ($f = find_in_path('spip_admin_perso.css')) {
		$css .= "<link rel='stylesheet' href='"
			. url_absolue(direction_css($f)) . "' type='text/css' />\n";
	}

	if (
		!($pos = stripos($contenu, '</head>'))
		&& !($pos = stripos($contenu, '<body>'))
	) {
    	$pos = 0;
 	}
	$contenu = substr_replace($contenu, $css, $pos, 0);


	// Inserer la balise #FORMULAIRE_ADMIN, en float
	$boutons_admin = inclure_balise_dynamique(
		balise_FORMULAIRE_ADMIN_dyn('spip-admin-float'),
		false
	);
 	if (
		!($pos = strripos($contenu, '</body>'))
		&& !($pos = strripos($contenu, '</html>'))
	) {
    	$pos = strlen($contenu);
 	}


	return substr_replace($contenu, (string) $boutons_admin, $pos, 0);
}
