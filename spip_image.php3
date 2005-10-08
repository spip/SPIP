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

// Charger un document, une image, un logo, un repertoire
// supprimer cet element, creer les vignettes, etc.

include ("ecrire/inc_version.php3");

$nom = "spip_image";

$f = find_in_path('inc_' . $nom . '.php');

if ($f) 
	include($f);
elseif (file_exists($f = (_DIR_INCLUDE . 'inc_' . $nom . '.php')))
	include($f);

$nom .= '_' . $action;

if (function_exists($nom))
	$nom($doc);
elseif (function_exists($f = $nom . '_' .  "_dist"))
	$f($doc);
 else
	spip_log("fonction $nom indisponible");
?>
