<?php

// Inserer la feuille de style selon les normes, dans le <head>
// puis les boutons
// Feuilles de style admin : d'abord la CSS officielle, puis la perso,
function affiche_boutons_admin(&$contenu) {
	$css = "<link rel='stylesheet' href='spip_admin.css' type='text/css' />\n";
	if (@file_exists('spip_admin_perso.css'))
		$css2 = "<link rel='stylesheet' href='spip_admin_perso.css' type='text/css' />\n";

	if (preg_match('@<(/head|body)@i', $contenu, $regs)) {
		$contenu = explode($regs[0], $contenu, 2);
		$contenu = $contenu[0] . $css. $css2 . $regs[0] . $contenu[1];
	} else
		$contenu = $css . $css2 . $contenu;

	if (preg_match('@<(/body|/html)@i', $contenu, $regs)) {
		$split = explode($regs[0], $contenu, 2);
		$contenu = $split[0];
		$suite = $regs[0].$split[1];
	}

	echo $contenu;

	inclure_balise_dynamique(
		balise_formulaire_admin_dyn(
		$id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur, 'div'
	));

	echo $suite;

	$contenu = '';
}

?>
