<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// iso latin 15 - Gaetan Ryckeboer <gryckeboer@virtual-net.fr>

load_charset('iso-8859-1');

$trans = $GLOBALS['CHARSET']['iso-8859-1'];
$trans[164]=8364;
$trans[166]=352;
$trans[168]=353;
$trans[180]=381;
$trans[184]=382;
$trans[188]=338;
$trans[189]=339;
$trans[190]=376;

$GLOBALS['CHARSET']['iso-8859-15'] = $trans;

?>
