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
function block_parfois_visible($nom, $invite, $masque, $style='', $visible=false)
{
	if (!$GLOBALS['browser_layer']) return '';

	$bouton = $visible
	? bouton_block_visible($nom)
	: bouton_block_invisible($nom);

	$nom = 'Layer' . renomme_block($nom);

	// initialement invisible, seulement si on sait rendre visible
	if (!$visible AND _SPIP_AJAX)
		$visible = 'display:none;';
	else 	$visible = 'display:block;';

	return "\n"
	. "<div style='$style'>"
	. $bouton
	. $invite
	. '</div>'
	. "<div id='$nom' style='$visible'>"
	. $masque
	. '<div style="clear: both;"></div></div>';
}


// http://doc.spip.org/@renomme_block
function renomme_block($nom_block)
{
	global $numero_block, $compteur_block;
	if (!isset($numero_block[$nom_block])){
		$compteur_block++;
		$numero_block[$nom_block] = $compteur_block;
	}
	return $numero_block["$nom_block"];
}

// http://doc.spip.org/@debut_block_visible
function debut_block_visible($nom_block){
	global $browser_layer;
	if (!$browser_layer) return '';
	return "<div id='Layer".renomme_block($nom_block)."' style='display: block;'>";

}

// http://doc.spip.org/@debut_block_invisible
function debut_block_invisible($nom_block){
	global $browser_layer;
	if (!$browser_layer) return '';

	// si on n'accepte pas js, ne pas fermer
	if (!_SPIP_AJAX)
		return debut_block_visible($nom_block);

	return "<div id='Layer".renomme_block($nom_block)."' style='display: none;'>";
}

// http://doc.spip.org/@fin_block
function fin_block() {
	if ($GLOBALS['browser_layer'])
		return "<div style='clear: both;'></div></div>";
}

// http://doc.spip.org/@bouton_block_invisible
function bouton_block_invisible($nom_block, $icone='') {
	global $numero_block, $compteur_block, $browser_layer, $spip_lang_rtl;
	if (!$browser_layer) return '';
	$blocks = explode(",", $nom_block);
	$couches = array();
	for ($index=0; $index < count($blocks); $index ++){
		$nom_block = $blocks[$index];

		if (!isset($numero_block[$nom_block])){
			$compteur_block++;
			$numero_block[$nom_block] = $compteur_block;
		}

		if (!$icone) {
			$icone = "deplierhaut$spip_lang_rtl.gif";
			$couches[] = array($numero_block[$nom_block],0);
		}
		else
			$couches[] = array($numero_block[$nom_block],1);
	}
	return produire_acceder_couche($couches, $numero_block[$nom_block], $icone);
}


// http://doc.spip.org/@bouton_block_visible
function bouton_block_visible($nom_block){
	global $numero_block, $compteur_block, $browser_layer, $spip_lang_rtl;
	if (!$browser_layer) return '';
	$blocks = explode(",", $nom_block);
	$couches = array();
	for ($index=0; $index < count($blocks); $index ++){
		$nom_block = $blocks[$index];

		if (!isset($numero_block[$nom_block])){
			$compteur_block++;
			$numero_block[$nom_block] = $compteur_block;
		}

		$couches[] = array($numero_block[$nom_block],0);

	}

	return produire_acceder_couche($couches, $numero_block[$nom_block], "deplierbas.gif");
}

// http://doc.spip.org/@produire_acceder_couche
function produire_acceder_couche($couches, $nom, $icone) {

	global $spip_lang_rtl;
	// ne rien afficher si js desactive
	if (!_SPIP_AJAX)
		return '';

	$onclick = array();
	foreach($couches as $i=>$couche)
		$onclick[] = 'swap_couche(' . $couche[0]
			. ",'$spip_lang_rtl','"
			. _DIR_IMG_PACK."',"
			. $couche[1].');';

	$t = _T('info_deplier');

	return 
	'<img id="triangle'.$nom.'" src="'
	  . _DIR_IMG_PACK . $icone
	  . '" alt="'
	  . $t
	  . '" title="'
	  . $t
	  . '" class="swap-couche"
	onclick="'
	  . join(' ',$onclick).'" />';

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

	if (!eregi("opera", $browser_description)&&eregi("opera", $browser_name)) {
		$browser_name = "Opera";
		$browser_version = $match[2];
		$browser_layer = (($browser_version < 7) ? '' :  http_script('', _DIR_JAVASCRIPT . 'layer.js',''));
		$browser_barre = ($browser_version >= 8.5); 
	}
	else if (eregi("opera", $browser_description)) {
		eregi("Opera ([^\ ]*)", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 7) ? '' :  http_script('', _DIR_JAVASCRIPT . 'layer.js',''));
		$browser_barre = ($browser_version >= 8.5); 
	}
	else if (eregi("msie", $browser_description)) {
		eregi("MSIE ([^;]*)", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 5) ? '' :  http_script('', _DIR_JAVASCRIPT . 'layer.js',''));
		$browser_barre = ($browser_version >= 5.5);
	}
	else if (eregi("KHTML", $browser_description) &&
		eregi("Safari/([^;]*)", $browser_description, $match)) {
		$browser_name = "Safari";
		$browser_version = $match[1];
		$browser_layer = http_script('', _DIR_JAVASCRIPT . 'layer.js','');
		$browser_barre = ($browser_version >= 5.0);
	}
	else if (eregi("mozilla", $browser_name) AND $browser_version >= 5) {
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
$GLOBALS['browser_verifForm'] = (eregi("mozilla", $GLOBALS["browser_name"]) AND $GLOBALS["browser_rev"] >= 1.7) ?  "verifForm();" : "";

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
