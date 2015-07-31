<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


// Du cote de la page HTML, utiliser un background-image en feuille de style
// plutot qu'un <img>, c'est plus discret notamment sous navigateur texte
$image = pack("H*", "47494638396118001800800000ffffff00000021f90401000000002c0000000018001800000216848fa9cbed0fa39cb4da8bb3debcfb0f86e248965301003b");

Header("Content-Type: image/gif");
Header("Content-Length: ".strlen($image));
Header("Cache-Control: no-cache,no-store");
Header("Pragma: no-cache");
Header("Connection: close");

echo $image;

flush();

include("ecrire/inc_version.php3");

$GLOBALS['flag_preserver'] = true; // pas d'espaces pour le flush()
cron(1); // acces gourmand (on veut bosser, nous, pas comme inc-public !)

?>
