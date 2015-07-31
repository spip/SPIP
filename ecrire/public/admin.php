<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


// Inserer la feuille de style selon les normes, dans le <head>
// puis les boutons
// Feuilles de style admin : d'abord la CSS officielle, puis la perso

// http://doc.spip.org/@affiche_boutons_admin
function affiche_boutons_admin($contenu) {
	include_spip('inc/filtres');

	// Compatibilite : on utilise stripos/strripos() qui n'existent pas en php4
	if (!function_exists('strripos')) {
		function strripos($botte, $aiguille) {
			return strrpos(strtolower($botte), $aiguille);
		}
	}
	if (!function_exists('stripos')) {
		function stripos($botte, $aiguille) {
			return strpos(strtolower($botte), $aiguille);
		}
	}

	// Inserer le css d'admin
	$css = "<link rel='stylesheet' href='".url_absolue(find_in_path('spip_admin.css'))
	. "' type='text/css' />\n";
	if ($f = find_in_path('spip_admin_perso.css'))
		$css .= "<link rel='stylesheet' href='"
		. url_absolue($f) . "' type='text/css' />\n";

	($pos = stripos($contenu, '</head>'))
	    || ($pos = stripos($contenu, '<body>'))
	    || ($pos = 0);
	$contenu = substr_replace($contenu, $css, $pos, 0);


	// Inserer la balise #FORMULAIRE_ADMIN, en float
	$boutons_admin = inclure_balise_dynamique(
		balise_FORMULAIRE_ADMIN_dyn('spip-admin-float'),
	false);

	($pos = strripos($contenu, '</body>'))
	    || ($pos = strripos($contenu, '</html>'))
	    || ($pos = strlen($contenu));
	$contenu = substr_replace($contenu, $boutons_admin, $pos, 0);


	return $contenu;
}

?>
