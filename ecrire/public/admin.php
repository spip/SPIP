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
// Feuilles de style admin : d'abord la CSS officielle, puis la perso,

function affiche_boutons_admin($contenu) {
	include_spip('inc/filtres');
	$css = "<link rel='stylesheet' href='".url_absolue(find_in_path('spip_admin.css'))
	. "' type='text/css' />\n";
	if ($f = find_in_path('spip_admin_perso.css'))
		$css .= "<link rel='stylesheet' href='"
		. url_absolue($f) . "' type='text/css' />\n";

	if (preg_match('@<(/head|body)@i', $contenu, $regs)) {
		$contenu = explode($regs[0], $contenu, 2);
		$contenu = $contenu[0] . $css . $regs[0] . $contenu[1];
	} else
		$contenu = $css . $contenu;

	if (preg_match('@<(/body|/html)@i', $contenu, $regs)) {
		$split = explode($regs[0], $contenu, 2);
		$contenu = $split[0];
		$suite = $regs[0].$split[1];
	} else $suite ='';

	// Recuperer sans l'afficher la balise #FORMULAIRE_ADMIN, en float
	$boutons_admin = inclure_balise_dynamique(
		balise_FORMULAIRE_ADMIN_dyn('spip-admin-float'),
	false);

	return $contenu.$boutons_admin.$suite;
}

?>
