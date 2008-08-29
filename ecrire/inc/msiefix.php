<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// Pour MSIE: reparer le cache des images de background
// Inserer le script jquery.ifixpng.js si necessaire
// Comme MSIE est goret, on n'a pas honte d'inserer comme un goret
// en fin de page
// http://doc.spip.org/@inc_msiefix_dist
function inc_msiefix_dist($texte) {
	$texte .= "<script type='text/javascript'><!--
	try { document.execCommand('BackgroundImageCache', false, true); } catch(err) {};
	// --></script>\n";

	// Si jQuery n'est pas la on ne fixe pas les PNG
	if (strpos($texte, 'jquery.js')
	AND strpos($texte, '.png')
	AND true /* ... autres tests si on veut affiner ... */
	AND lire_fichier(_DIR_RACINE.'prive/javascript/jquery.ifixpng.js', $ifixpng)
	) {
		$texte .=
"<script type='text/javascript'><!--
if (window.jQuery && jQuery.browser.msie) {
$ifixpng

jQuery.ifixpng('rien.gif');
var fixie = function(){jQuery('img').ifixpng();}
fixie();
onAjaxLoad(fixie);
}
// --></script>\n";
	}

	return $texte;
}


// http://doc.spip.org/@presentation_msiefix
function presentation_msiefix() {
	lire_fichier(_DIR_RACINE.'prive/javascript/jquery.ifixpng.js', $ifixpng);
	return "<script type='text/javascript'><!--
	try { document.execCommand('BackgroundImageCache', false, true); } catch(err) {};
	if (window.jQuery && jQuery.browser.msie) {
$ifixpng
		jQuery.ifixpng('rien.gif');
		jQuery('img,#bandeau-principal .icon_fond span').ifixpng();
	}
	// --></script>";
}


?>
