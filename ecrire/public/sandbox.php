<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Composer le code d'execution d'un texte
 * en principe juste un echappement de guillemets
 * sauf si on veut aussi echapper et interdire les scripts serveurs
 * dans les squelette
 *
 * @param string $texte
 * @param string $code
 * @param string $arglist
 * @param Object $p
 * @return string
 */
function sandbox_composer_texte($texte, &$p){
	$code = "'".str_replace(array("\\","'"),array("\\\\","\\'"), $texte)."'";
	return $code;
}


/**
 * Composer le code d'execution d'un filtre
 *
 * @param string $fonc
 * @param string $code
 * @param string $arglist
 * @param Object $p
 * @return string
 */
function sandbox_composer_filtre($fonc, $code, $arglist, &$p){
	if (isset($GLOBALS['spip_matrice'][$fonc])) {
		$code = "filtrer('$fonc',$code$arglist)";
	}

	// le filtre est defini sous forme de fonction ou de methode
	// par ex. dans inc_texte, inc_filtres ou mes_fonctions
	elseif ($f = chercher_filtre($fonc)) {
		$code = "$f($code$arglist)";
	}
	// le filtre n'existe pas,
	// on le notifie
	else erreur_squelette(array('zbug_erreur_filtre', array('filtre'=>  texte_script($fonc))), $p);

	return $code;
}

// Calculer un <INCLURE(xx.php)>
// La constante ci-dessous donne le code general quand il s'agit d'un script.
define('CODE_INCLURE_SCRIPT', 'if (($path = %s) AND is_readable($path)) {
include $path;
} else erreur_squelette(array("fichier_introuvable", array("fichier" => "%s")), array(%s));'
);

/**
 * Composer le code d'inclusion PHP
 *
 * @param string $fichier
 * @param Object $p
 * @return string
 */
function sandbox_composer_inclure_php($fichier, &$p){
	$compil = texte_script(memoriser_contexte_compil($p));
	// si inexistant, on essaiera a l'execution
	if ($path = find_in_path($fichier))
		$path = "\"$path\"";
	else $path = "find_in_path(\"$fichier\")";

	return sprintf(CODE_INCLURE_SCRIPT, $path, $fichier, $compil);
}

/**
 * Composer le code se securisation anti script
 *
 * @param string $code
 * @param Object $p
 * @return string
 */
function sandbox_composer_interdire_scripts($code, &$p){
	// Securite
	if ($p->interdire_scripts
	AND $p->etoile != '**') {
		if (!preg_match("/^sinon[(](.*),'([^']*)'[)]$/", $code, $r))
			$code = "interdire_scripts($code)";
		else {
			$code = interdire_scripts($r[2]);
			$code = "sinon(interdire_scripts($r[1]),'$code')";
		}
	}
	return $code;
}

