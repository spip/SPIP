<?php

/*
 * smallcaps
 *
 * introduit le raccourci <sc>...</sc> pour les petites majuscules
 *
 * Auteur : arno@scarabee.com
 * © 2005 - Distribue sous licence GNU/GPL
 *
 */

$nom = 'smallcaps';
$version = 0.1;

// s'inserer dans le pipeline 'apres_typo' @ ecrire/inc_texte.php3
$GLOBALS['spip_pipeline']['post_typo'] .= '|smallcaps';

// la fonction est tres legere on la definit directement ici
#$GLOBALS['spip_matrice']['smallcaps'] = dirname(__FILE__).'/smallcaps.php';

// Raccourci typographique <sc></sc>
function smallcaps($texte) {
	$texte = str_replace("<sc>",
		"<span style=\"font-variant: small-caps\">", $texte);
	$texte = str_replace("</sc>", "</span>", $texte);
	return $texte;
}


?>
