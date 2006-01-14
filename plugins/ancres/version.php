<?php

/*
 * ancres
 *
 * introduit le raccourci [#ancre<-] pour les ancres
 *
 * Auteur : collectif
 * © 2005 - Distribue sous licence BSD
 *
 */

$nom = 'ancres';
$version = 0.1;

// s'inserer dans le pipeline 'avant_propre' @ ecrire/inc_texte.php3
$GLOBALS['spip_pipeline']['post_propre'] .= '|ancres';

// la fonction est tres legere on la definit directement ici
function ancres($texte) {
	$regexp = "|\[#?([^][]*)<-\]|";
	if (preg_match_all($regexp, $texte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs)
		$texte = str_replace($regs[0],
		'<a name="'.entites_html($regs[1]).'"></a>', $texte);
	return $texte;
}

#$GLOBALS['spip_matrice']['ancres'] = dirname(__FILE__).'/ancres.php';

?>
