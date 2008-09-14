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

function image_bg ($img, $couleur, $pos="") {
	if (function_exists("imagecreatetruecolor")) return "background: url(".url_absolue(extraire_attribut(image_aplatir(image_sepia($img, $couleur),"gif","cccccc", 64, true), "src")).") $pos;";
	else return "background-color: #$couleur;";
}

?>