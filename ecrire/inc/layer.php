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

// http://doc.spip.org/@cadre_depliable
function cadre_depliable($icone,$titre,$deplie,$contenu,$ids='',$style_cadre='r'){
	$bouton = bouton_block_depliable($titre,$deplie,$ids);
	return 
		debut_cadre($style_cadre,$icone,'',$bouton, '', '', false)
		. debut_block_depliable($deplie,$ids)
		. "<div class='cadre_padding'>\n"
		. $contenu
		. "</div>\n"
		. fin_block()
		. fin_cadre();
}

// http://doc.spip.org/@block_parfois_visible
function block_parfois_visible($nom, $invite, $masque, $style='', $visible=false){
	if (!$GLOBALS['browser_layer']) return '';

	return "\n"
	. bouton_block_depliable($invite,$visible,$nom)
	. debut_block_depliable($visible,$nom)
	. $masque
	. fin_block();
}

// http://doc.spip.org/@debut_block_depliable
function debut_block_depliable($deplie,$id=""){
	$class=' blocdeplie';
	// si on n'accepte pas js, ne pas fermer
	if (_SPIP_AJAX AND !$deplie)
		$class=" blocreplie";
	return "<div ".($id?"id='$id' ":"")."class='bloc_depliable$class'>";	
}
// http://doc.spip.org/@fin_block
function fin_block() {
	return "<div class='nettoyeur'></div>\n</div>";
}
// $texte : texte du bouton
// $deplie : true (deplie) ou false (plie) ou -1 (inactif) ou 'incertain' pour que le bouton s'auto init au chargement de la page 
// $ids : id des div lies au bouton (facultatif, par defaut c'est le div.bloc_depliable qui suit)
// http://doc.spip.org/@bouton_block_depliable
function bouton_block_depliable($texte,$deplie,$ids=""){
	if (!_SPIP_AJAX) $deplie=true; // forcer un bouton deplie si pas de js
	$bouton_id = 'b'.substr(md5($texte.microtime()),0,8);

	$class = ($deplie===true)?" deplie":(($deplie==-1)?" impliable":" replie");
	if (strlen($ids)){
		$cible = explode(',',$ids);
		$cible = '#'.implode(",#",$cible);
	}
	else{
		$cible = "#$bouton_id + div.bloc_depliable";
	}

	return "<div "
	  .($bouton_id?"id='$bouton_id' ":"")
	  ."class='titrem$class'"
	  . (($deplie===-1)
	  	?""
	  	:" onmouseover=\"jQuery(this).depliant('$cible');\""
	  )
	  .">"
	  // une ancre pour rendre accessible au clavier le depliage du sous bloc
	  // on ne la mets pas en interface "accessible", dans laquelle il n'y  pas de sous bloc ... un comble !
	  . ($GLOBALS['spip_display']==4?"":"<a href='#' onclick=\"return jQuery(this).depliant_clicancre('$cible');\" class='titremancre'></a>")
	  . "$texte</div>"
	  . http_script( ($deplie==='incertain')
			? "jQuery(document).ready(function(){if (jQuery('$cible').is(':visible')) $('#$bouton_id').addClass('deplie').removeClass('replie');});"
			: '');
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
		$browser_layer = (($browser_version < 7) ? '' :  http_script('',  'layer.js',''));
		$browser_barre = ($browser_version >= 8.5); 
	}
	else if (preg_match(",opera,i", $browser_description)) {
		preg_match(",Opera ([^\ ]*),i", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 7) ? '' :  http_script('',  'layer.js',''));
		$browser_barre = ($browser_version >= 8.5); 
	}
	else if (preg_match(",msie,i", $browser_description)) {
		preg_match(",MSIE ([^;]*),i", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 5) ? '' :  http_script('',  'layer.js',''));
		$browser_barre = ($browser_version >= 5.5);
	}
	else if (preg_match(",KHTML,i", $browser_description) &&
		preg_match(",Safari/([^;]*),", $browser_description, $match)) {
		$browser_name = "Safari";
		$browser_version = $match[1];
		$browser_layer = http_script('',  'layer.js','');
		$browser_barre = ($browser_version >= 5.0);
	}
	else if (preg_match(",mozilla,i", $browser_name) AND $browser_version >= 5) {
		$browser_layer = http_script('',  'layer.js','');
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

verif_butineur();

?>
