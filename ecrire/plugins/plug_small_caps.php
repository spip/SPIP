<?php
// Ce plug-in ajoute le raccourci typographique <sc></sc>

function avant_typo_smallcaps($texte) {
	$texte = ereg_replace("<sc>", "<span style=\"font-variant: small-caps\">", $texte);
	$texte = ereg_replace("</sc>", "</span>", $texte);
	
	return $texte;
}

?>