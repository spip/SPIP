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

include ("inc_version.php3");

// prendre $var_* comme variables pour eviter les conflits avec les http_vars

$var_nom = "export";
$var_f = find_in_path('inc_' . $var_nom . '.php');

if ($var_f) 
  include($var_f);
 // ATTENTION PHP3 ici
else
  include_ecrire(_DIR_INCLUDE . 'inc_' . $var_nom . '.php3');

if (function_exists($var_nom))
  $var_nom($id_rubrique, $maj);
elseif (function_exists($var_f = $var_nom . "_dist"))
  $var_f($id_rubrique, $maj);
else
   spip_log("fonction $var_nom indisponible");
?>
