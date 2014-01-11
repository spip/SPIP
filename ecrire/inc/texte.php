<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/texte_mini');
include_spip('inc/lien');

/*************************************************************************************************************************
 * Fonctions inutilisees en dehors de inc/texte
 *
 */

// Raccourcis dependant du sens de la langue
function definir_raccourcis_alineas(){
	return array('','');
}


//
// Tableaux
//
// http://doc.spip.org/@traiter_tableau
function traiter_tableau($bloc) {
	return $bloc;
}


//
// Traitement des listes (merci a Michael Parienti)
//
// http://doc.spip.org/@traiter_listes
function traiter_listes ($texte) {
	return $texte;
}

// Nettoie un texte, traite les raccourcis autre qu'URL, la typo, etc.
// http://doc.spip.org/@traiter_raccourcis
function traiter_raccourcis($letexte) {

	// Appeler les fonctions de pre_traitement
	$letexte = pipeline('pre_propre', $letexte);

	// APPELER ICI UN PIPELINE traiter_raccourcis ?
	// $letexte = pipeline('traiter_raccourcis', $letexte);

	// Appeler les fonctions de post-traitement
	$letexte = pipeline('post_propre', $letexte);

	return $letexte;
}

/*************************************************************************************************************************
 * Fonctions utilisees en dehors de inc/texte
 */

// afficher joliment les <script>
// http://doc.spip.org/@echappe_js
function echappe_js($t,$class=' class="echappe-js"') {
	if (preg_match_all(',<script.*?($|</script.),isS', $t, $r, PREG_SET_ORDER))
	foreach ($r as $regs)
		$t = str_replace($regs[0],
			"<code$class>".nl2br(spip_htmlspecialchars($regs[0])).'</code>',
			$t);
	return $t;
}



/**
 * Empêcher l'exécution de code PHP et JS
 *
 * Sécurité : empêcher l'exécution de code PHP, en le transformant en joli code
 * dans l'espace privé. Cette fonction est aussi appelée par propre et typo.
 * 
 * De la même manière, la fonction empêche l'exécution de JS mais selon le mode
 * de protection déclaré par la globale filtrer_javascript :
 * - -1 : protection dans l'espace privé et public
 * - 0  : protection dans l'espace public
 * - 1  : aucune protection
 * 
 * Il ne faut pas désactiver globalement la fonction dans l'espace privé car elle protège
 * aussi les balises des squelettes qui ne passent pas forcement par propre ou typo après
 * si elles sont appelées en direct
 * 
 * @param string $arg
 *     Code à protéger
 * @return string
 *     Code protégé
**/
function interdire_scripts($arg) {
	// on memorise le resultat sur les arguments non triviaux
	static $dejavu = array();

	// Attention, si ce n'est pas une chaine, laisser intact
	if (!$arg OR !is_string($arg) OR !strstr($arg, '<')) return $arg; 

	if (isset($dejavu[$GLOBALS['filtrer_javascript']][$arg])) return $dejavu[$GLOBALS['filtrer_javascript']][$arg];

	// echapper les tags asp/php
	$t = str_replace('<'.'%', '&lt;%', $arg);

	// echapper le php
	$t = str_replace('<'.'?', '&lt;?', $t);

	// echapper le < script language=php >
	$t = preg_replace(',<(script\b[^>]+\blanguage\b[^\w>]+php\b),UimsS', '&lt;\1', $t);

	// Pour le js, trois modes : parano (-1), prive (0), ok (1)
	switch($GLOBALS['filtrer_javascript']) {
		case 0:
			if (!_DIR_RESTREINT)
				$t = echappe_js($t);
			break;
		case -1:
			$t = echappe_js($t);
			break;
	}

	// pas de <base href /> svp !
	$t = preg_replace(',<(base\b),iS', '&lt;\1', $t);

	// Reinserer les echappements des modeles
	if (defined('_PROTEGE_JS_MODELES'))
		$t = echappe_retour($t,"javascript"._PROTEGE_JS_MODELES);
	if (defined('_PROTEGE_PHP_MODELES'))
		$t = echappe_retour($t,"php"._PROTEGE_PHP_MODELES);

	return $dejavu[$GLOBALS['filtrer_javascript']][$arg] = $t;
}

// Typographie generale
// avec protection prealable des balises HTML et SPIP

// http://doc.spip.org/@typo
function typo($letexte, $echapper=true, $connect=null, $env=array()) {
	// Plus vite !
	if (!$letexte) return $letexte;

	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrit sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	$interdire_script = false;
	if (is_null($connect)){
		$connect = '';
		$interdire_script = true;
	}

	// Echapper les codes <html> etc
	if ($echapper)
		$letexte = echappe_html($letexte, 'TYPO');

	//
	// Installer les modeles, notamment images et documents ;
	//
	// NOTE : propre() ne passe pas par ici mais directement par corriger_typo
	// cf. inc/lien

	$letexte = traiter_modeles($mem = $letexte, false, $echapper ? 'TYPO' : '', $connect, null, $env);
	if ($letexte != $mem) $echapper = true;
	unset($mem);

	$letexte = corriger_typo($letexte);
	$letexte = echapper_faux_tags($letexte);

	// reintegrer les echappements
	if ($echapper)
		$letexte = echappe_retour($letexte, 'TYPO');

	// Dans les appels directs hors squelette, securiser ici aussi
	if ($interdire_script)
		$letexte = interdire_scripts($letexte);

	return $letexte;
}

// Correcteur typographique
define('_TYPO_PROTEGER', "!':;?~%-");
define('_TYPO_PROTECTEUR', "\x1\x2\x3\x4\x5\x6\x7\x8");

define('_TYPO_BALISE', ",</?[a-z!][^<>]*[".preg_quote(_TYPO_PROTEGER)."][^<>]*>,imsS");

// http://doc.spip.org/@corriger_typo
function corriger_typo($letexte, $lang='') {

	// Plus vite !
	if (!$letexte) return $letexte;

	$letexte = pipeline('pre_typo', $letexte);

	// Caracteres de controle "illegaux"
	$letexte = corriger_caracteres($letexte);

	// Proteger les caracteres typographiques a l'interieur des tags html
	if (preg_match_all(_TYPO_BALISE, $letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$insert = $reg[0];
			// hack: on transforme les caracteres a proteger en les remplacant
			// par des caracteres "illegaux". (cf corriger_caracteres())
			$insert = strtr($insert, _TYPO_PROTEGER, _TYPO_PROTECTEUR);
			$letexte = str_replace($reg[0], $insert, $letexte);
		}
	}

	// trouver les blocs multi et les traiter a part
	$letexte = extraire_multi($e = $letexte, $lang, true);
	$e = ($e === $letexte);

	// Charger & appliquer les fonctions de typographie
	$typographie = charger_fonction(lang_typo($lang), 'typographie');
	$letexte = $typographie($letexte);

	// Les citations en une autre langue, s'il y a lieu
	if (!$e) $letexte = echappe_retour($letexte, 'multi');

	// Retablir les caracteres proteges
	$letexte = strtr($letexte, _TYPO_PROTECTEUR, _TYPO_PROTEGER);

	// pipeline
	$letexte = pipeline('post_typo', $letexte);

	# un message pour abs_url - on est passe en mode texte
	$GLOBALS['mode_abs_url'] = 'texte';

	return $letexte;
}




//
// Une fonction pour fermer les paragraphes ; on essaie de preserver
// des paragraphes indiques a la main dans le texte
// (par ex: on ne modifie pas un <p align='center'>)
//
// deuxieme argument : forcer les <p> meme pour un seul paragraphe
//
// http://doc.spip.org/@paragrapher
// /!\ appelee dans inc/filtres et public/composer
function paragrapher($letexte, $forcer=true) {
	return $letexte;
}

// Harmonise les retours chariots et mange les paragraphes html
// http://doc.spip.org/@traiter_retours_chariots
// ne sert plus
function traiter_retours_chariots($letexte) {
	$letexte = preg_replace(",\r\n?,S", "\n", $letexte);
	$letexte = preg_replace(",<p[>[:space:]],iS", "\n\n\\0", $letexte);
	$letexte = preg_replace(",</p[>[:space:]],iS", "\\0\n\n", $letexte);
	return $letexte;
}


// Filtre a appliquer aux champs du type #TEXTE*
// http://doc.spip.org/@propre
function propre($t, $connect=null, $env=array()) {
	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrits sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	$interdire_script = false;
	if (is_null($connect)){
		$connect = '';
		$interdire_script = true;
	}

	if (!$t) return strval($t);

	$t = echappe_html($t);
	$t = expanser_liens($t,$connect, $env);
	$t = traiter_raccourcis($t);
	$t = echappe_retour_modeles($t, $interdire_script);

	return $t;
}

?>
