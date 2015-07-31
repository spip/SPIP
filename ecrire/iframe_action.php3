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

$reinstall = 'non';
include ("inc.php3");

if ($id && ($connect_statut == "0minirezo")) {

	$var_f = find_in_path('inc_' . $action . '.php');
	if ($var_f) 
	  include($var_f);
	else
	  include_ecrire(_DIR_INCLUDE . 'inc_' . $action . '.php');

	$var_nom = 'changer_statut_' . $action;
	if (function_exists($var_nom))
		$var_nom($id, $statut);
	else spip_log("fonction $var_nom indisponible dans $var_f");
 }

if (!$redirect)
	header("Cache-Control: no-store, no-cache, must-revalidate");
else
	header("Location: " . urldecode($redirect));

?>
