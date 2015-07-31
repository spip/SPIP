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


include ("inc.php3");

// prendre $var_* comme variables pour eviter les conflits avec les http_vars

$var_nom = "calendrier";
$var_f = find_in_path('inc_' . $var_nom . '.php');

if ($var_f) 
  include($var_f);
else
  include_ecrire('inc_' . $var_nom . '.php');

if (function_exists($var_nom))
  $var_nom($type, $css);
elseif (function_exists($var_f = $var_nom . "_dist"))
  $var_f($type, $css);
else
   spip_log("fonction $var_nom indisponible");
?>
