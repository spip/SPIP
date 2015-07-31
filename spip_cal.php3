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

$id_auteur = $_GET['id'];
$arg = $_GET['cle'];
$action = 'ical';
include ("spip_action.php");
?>
