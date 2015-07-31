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

if (!defined("_ECRIRE_INC_VERSION")) return;

function debut_block_visible($nom_block){
	global $numero_block, $compteur_block, $browser_layer;
	if (!$browser_layer) return '';
	if (!$numero_block["$nom_block"] > 0){
		$compteur_block++;
		$numero_block["$nom_block"] = $compteur_block;
	}
	return "<div id='Layer".$numero_block["$nom_block"]."' style='display: block;'>";

}

function debut_block_invisible($nom_block){
	global $numero_block, $compteur_block, $browser_layer;
	if (!$browser_layer) return '';
	if (!$numero_block["$nom_block"] > 0){
		$compteur_block++;
		$numero_block["$nom_block"] = $compteur_block;
	}
	
	return http_script("vis['".$numero_block["$nom_block"]."'] = 'hide';
document.write('<div id=\"Layer".$numero_block["$nom_block"]."\" style=\"display: none; margin-top: 1;\">');",
			      '',
			   "<div id='Layer".$numero_block["$nom_block"]."' style='display: block;'>");

}

function fin_block() {
	if ($GLOBALS['browser_layer'])
		return "<div style='clear: both;'></div></div>";
}

function bouton_block_invisible($nom_block, $icone='') {
	global $numero_block, $compteur_block, $browser_layer, $spip_lang_rtl;

	if (!$browser_layer) return '';
	$blocks = explode(",", $nom_block);
	$javasc = array();
	for ($index=0; $index < count($blocks); $index ++){
		$nom_block = $blocks[$index];

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}

		if (!$icone) {
			$icone = "deplierhaut$spip_lang_rtl.gif";
			$javasc[] = '[' . $numero_block[$nom_block] . ',0]';
		}
		else
			$javasc[] = '[' . $numero_block[$nom_block] . ',1]';
	}
	return produire_acceder_couche($javasc, $numero_block[$nom_block], $icone);
}


function bouton_block_visible($nom_block){
	global $numero_block, $compteur_block, $browser_layer, $spip_lang_rtl;

	if (!$browser_layer) return '';
	$blocks = explode(",", $nom_block);
	$javasc = array();
	for ($index=0; $index < count($blocks); $index ++){
		$nom_block = $blocks[$index];

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}

		$javasc[] = '[' . $numero_block[$nom_block] . ',0]';

	}

	return produire_acceder_couche($javasc, $numero_block[$nom_block], "deplierbas.gif");
}

function produire_acceder_couche($couches, $nom, $icone) {
	global $spip_lang_rtl;
	return http_script("acceder_couche([" . join(',',$couches) . '], ' .
			   $nom .
			   ", '" .
			   _DIR_IMG_PACK .
			   "', '" .
			   $icone .
			   "', '" .
			   addslashes(_T('info_deplier')) .
			   "','$spip_lang_rtl')");
}

//
// Tests sur le nom du butineur
//
function verif_butineur() {

	global $HTTP_USER_AGENT, $browser_name, $browser_version;
	global $browser_description, $browser_rev, $browser_layer, $browser_barre;
	ereg("^([A-Za-z]+)/([0-9]+\.[0-9]+) (.*)$", $HTTP_USER_AGENT, $match);
	$browser_name = $match[1];
	$browser_version = $match[2];
	$browser_description = $match[3];
	$browser_layer = '';
	$browser_barre = '';

	if (eregi("opera", $browser_description)) {
		eregi("Opera ([^\ ]*)", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 7) ? '' :  http_script('', _DIR_IMG_PACK . 'layer.js',''));
		$browser_barre = ($browser_version >= 8.5); 
	}
	else if (eregi("msie", $browser_description)) {
		eregi("MSIE ([^;]*)", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 5) ? '' :  http_script('', _DIR_IMG_PACK . 'layer.js',''));
		$browser_barre = ($browser_version >= 5.5);
	}
	else if (eregi("KHTML", $browser_description) &&
		eregi("Safari/([^;]*)", $browser_description, $match)) {
		$browser_name = "Safari";
		$browser_version = $match[1];
		$browser_layer = http_script('', _DIR_IMG_PACK . 'layer.js','');
	}
	else if (eregi("mozilla", $browser_name) AND $browser_version >= 5) {
		$browser_layer = http_script('', _DIR_IMG_PACK . 'layer.js','');
		// Numero de version pour Mozilla "authentique"
		if (ereg("rv:([0-9]+\.[0-9]+)", $browser_description, $match))
			$browser_rev = doubleval($match[1]);
		// Autres Gecko => equivalents 1.4 par defaut (Galeon, etc.)
		else if (strpos($browser_description, "Gecko") and !strpos($browser_description, "KHTML"))
			$browser_rev = 1.4;
		// Machins quelconques => equivalents 1.0 par defaut (Konqueror, etc.)
		else $browser_rev = 1.0;
		$browser_barre = $browser_rev >= 1.3;
	}

	if (!$browser_name) $browser_name = "Mozilla";
}


function flag_svg() {
	global $HTTP_USER_AGENT, $browser_name, $browser_version;
	global $browser_description, $browser_rev, $browser_layer, $browser_barre;
	if (!$browser_name) verif_butineur();

	$flag = false;
	if ($browser_name == "Mozilla" AND $browser_rev >= 1.8) $flag = true;
			
	return $flag;
}

// Obsolete. Present pour compatibilite 
function afficher_script_layer(){echo $GLOBALS['browser_layer'];}
function test_layer(){return $GLOBALS['browser_layer'];}

verif_butineur();

$GLOBALS['browser_caret'] =  (!$GLOBALS['browser_barre'] ? '' : "
onselect='storeCaret(this);'
onclick='storeCaret(this);'
onkeyup='storeCaret(this);'
ondbclick='storeCaret(this);'");

	// Hack pour forcer largeur des formo/forml sous Mozilla >= 1.7
	// meme principe que le behavior win_width.htc pour MSIE

$GLOBALS['browser_verifForm'] = 	(eregi("mozilla", $browser_name) AND $browser_rev >= 1.7) ?  "verifForm();" : "";

function http_script($script, $src='', $noscript='') {
	return '<script type="text/javascript"'
		. ($src ? " src=\"$src\"" : '')
		. ">"
		. ($script ? "<!--\n$script\n//-->" : '')
		. "</script>\n"
		. (!$noscript ? '' : "<noscript>\n\t$noscript\n</noscript>\n");
}

?>
