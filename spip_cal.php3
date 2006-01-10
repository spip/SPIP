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

// script obsolete remplace par le generique spip_action 
// conserve pour compatibilite avec spip < 1.9

include ("ecrire/inc_version.php3");
include_ecrire("inc_spip_action_ical");

$id_auteur = $id;
$arg = $cle;
$action = 'ical';
$var_nom = "spip_action_ical";

if (function_exists($var_nom))
	$var_nom();
elseif (function_exists($var_f = $var_nom .  "_dist"))
	$var_f();
 else
	spip_log("fonction $var_nom indisponible");
?>
