<?php

// Inserer la feuille de style selon les normes, dans le <head>
// Feuilles de style admin : d'abord la CSS officielle, puis la perso,
function perso_admin($texte) {
	$css = "<link rel='stylesheet' href='spip_admin.css' type='text/css' />\n";
	if (@file_exists('spip_admin_perso.css'))
		$css2 = "<link rel='stylesheet' href='spip_admin_perso.css' type='text/css' />\n";

	if (eregi('<(/head|body)', $texte, $regs)) {
		$texte = explode($regs[0], $texte, 2);
		return $texte[0] . $css. $css2 . $regs[0] . $texte[1];
	} else
		return $css . $css2 . $texte;
}

?>
