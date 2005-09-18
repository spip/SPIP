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
include_ecrire("inc_auth.php3");

@header("Cache-Control: no-store, no-cache, must-revalidate");
echo "";

if ($id && ($connect_statut == "0minirezo")) {

	$nom = "inc_" . $action . ".php";
	$f = find_in_path($nom);
	if ($f) 
	  include($f);
	elseif (file_exists($f = (_DIR_INCLUDE . $nom)))
	  include($f);
	$nom = 'changer_statut_' . $action;
	if (function_exists($nom))
		$nom($id, $statut);
 }
?>
