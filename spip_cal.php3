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

// prendre $var_* comme variables pour eviter les conflits avec les http_vars

include ("ecrire/inc_version.php3");

$var_nom = "spip_cal";

$var_f = find_in_path('inc_' . $var_nom . '.php');

if ($var_f) 
	include($var_f);
else
	include_ecrire('inc_' . $var_nom);

if (function_exists($var_nom))
	$var_nom($id, $cle);
elseif (function_exists($var_f = $var_nom .  "_dist"))
	$var_f($id, $cle);
 else
	spip_log("fonction $nom indisponible");
?>
