<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
global $numero_block, $compteur_block;

$numero_block = array();

$compteur_block = 0;

if (_request('var_ajaxcharset') || _request("iframe")=="iframe")
$compteur_block = rand(1,2500)*500;	// astuce idiote pour que les blocs ahah n'aient pas les memes numeros de triangle que la page principale (sinon le triangle d'un bloc importe par ahah agit sur un autre triangle... vivement jquery...).

// http://doc.spip.org/@block_parfois_visible
function block_parfois_visible($nom, $invite, $masque, $style='', $visible=false){
	if (!$GLOBALS['browser_layer']) return '';

	return "\n"
	. bouton_block_depliable($invite,$visible,$nom)
	. debut_block_depliable($visible,$nom)
	. $masque
	. fin_block();
}

function debut_block_depliable($deplie,$id=""){
	$class=' deplie';
	// si on n'accepte pas js, ne pas fermer
	if (_SPIP_AJAX AND !$deplie)
		$class=" replie";
	return "<div ".($id?"id='$id' ":"")."class='bloc_depliable$class'>";	
}
// http://doc.spip.org/@fin_block
function fin_block() {
	return "<div class='nettoyeur' /></div></div>";
}
// $texte : texte du bouton
// $deplie : true (deplie) ou false (plie) ou -1 (inactif)
// $ids : id des div lies au bouton (facultatif, par defaut c'est le div.bloc_depliable qui suit)
function bouton_block_depliable($texte,$deplie,$ids=""){
	if (!_SPIP_AJAX) $deplie=true; // forcer un bouton deplie si pas de js
	$bouton_id = 'b'.substr(md5($texte.microtime()),8);
	$class= ($deplie===true)?" deplie":(($deplie==-1)?" impliable":" replie");
	if (strlen($ids)){
		$cible = explode(',',$ids);
		$cible = '#'.implode(",#",$cible);
		$bouton_id = "";
	}
	else{
		$cible = "#$bouton_id + div.bloc_depliable";
	}
	return "<div "
	  .($bouton_id?"id='$bouton_id' ":"")
	  ."class='titrem$class'"
	  . (($deplie===-1)?"":
	  " onclick=\"toggleBouton(jQuery(this),jQuery('$cible'));\""
	  ." onmouseover=\"jQuery(this).addClass('hover');\""
	  ." onmouseout=\"jQuery(this).removeClass('hover');\"")
	  .">$texte</div>";
}

//
// Tests sur le nom du butineur
//
// http://doc.spip.org/@verif_butineur
function verif_butineur() {

	global $browser_name, $browser_version;
	global $browser_description, $browser_rev, $browser_layer, $browser_barre;
	preg_match(",^([A-Za-z]+)/([0-9]+\.[0-9]+) (.*)$,", $_SERVER['HTTP_USER_AGENT'], $match);
	$browser_name = $match[1];
	$browser_version = $match[2];
	$browser_description = $match[3];
	$browser_layer = '';
	$browser_barre = '';

	if (!preg_match(",opera,i", $browser_description)&&preg_match(",opera,i", $browser_name)) {
		$browser_name = "Opera";
		$browser_version = $match[2];
		$browser_layer = (($browser_version < 7) ? '' :  http_script('', _DIR_JAVASCRIPT . 'layer.js',''));
		$browser_barre = ($browser_version >= 8.5); 
	}
	else if (preg_match(",opera,i", $browser_description)) {
		preg_match(",Opera ([^\ ]*),i", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 7) ? '' :  http_script('', _DIR_JAVASCRIPT . 'layer.js',''));
		$browser_barre = ($browser_version >= 8.5); 
	}
	else if (preg_match(",msie,i", $browser_description)) {
		preg_match(",MSIE ([^;]*),i", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 5) ? '' :  http_script('', _DIR_JAVASCRIPT . 'layer.js',''));
		$browser_barre = ($browser_version >= 5.5);
	}
	else if (preg_match(",KHTML,i", $browser_description) &&
		preg_match(",Safari/([^;]*),", $browser_description, $match)) {
		$browser_name = "Safari";
		$browser_version = $match[1];
		$browser_layer = http_script('', _DIR_JAVASCRIPT . 'layer.js','');
		$browser_barre = ($browser_version >= 5.0);
	}
	else if (preg_match(",mozilla,i", $browser_name) AND $browser_version >= 5) {
		$browser_layer = http_script('', _DIR_JAVASCRIPT . 'layer.js','');
		// Numero de version pour Mozilla "authentique"
		if (preg_match(",rv:([0-9]+\.[0-9]+),", $browser_description, $match))
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


// teste si accepte le SVG et pose un cookie en cas de demande explicite
// (fonction a appeler en debut de page, avant l'envoi de contenu)
// http://doc.spip.org/@flag_svg
function flag_svg() {
	global $browser_name, $browser_rev;

	// SVG est une preference definie par le visiteur ?
	if (_request('var_svg') == 'oui') {
		include_spip('inc/cookie');
		spip_setcookie('spip_svg', 'oui', time() + 365 * 24 * 3600);
		return true;
	}
	if (_request('var_svg') == 'non') {
		include_spip('inc/cookie');
		spip_setcookie('spip_svg', 'non', time() + 365 * 24 * 3600);
		return false;
	}
	if ($_COOKIE['spip_svg'] == 'oui')
		return true;
	if ($_COOKIE['spip_svg'] == 'non')
		return false;

	// Sinon, proceder a l'autodetection
	if (!$browser_name)
		verif_butineur();
	return ($browser_name == "Mozilla" AND $browser_rev >= 1.8);
}

verif_butineur();

$GLOBALS['browser_caret'] =  (!$GLOBALS['browser_barre'] ? '' : "
onselect='storeCaret(this);'
onclick='storeCaret(this);'
onkeyup='storeCaret(this);'
ondblclick='storeCaret(this);'");

	// Hack pour forcer largeur des formo/forml sous Mozilla >= 1.7
	// meme principe que le behavior win_width.htc pour MSIE
$GLOBALS['browser_verifForm'] = (preg_match(",mozilla,i", $GLOBALS["browser_name"]) AND $GLOBALS["browser_rev"] >= 1.7) ?  "verifForm();" : "";

// http://doc.spip.org/@http_script
function http_script($script, $src='', $noscript='') {
	return '<script type="text/javascript"'
		. ($src ? " src=\"$src\"" : '')
		. ">"
		. (!$script ? '' :
		   ("<!--\n" . 
		    preg_replace(',</([^>]*)>,','<\/\1>', $script) .
		    "\n//-->"))
		. "</script>\n"
		. (!$noscript ? '' : "<noscript>\n\t$noscript\n</noscript>\n");
}

?>
