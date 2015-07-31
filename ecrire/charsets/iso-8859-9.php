<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// iso latin 9 - Turc <alexis@nds.k12.tr>

load_charset('iso-8859-1');

$trans = $GLOBALS['CHARSET']['iso-8859-1'];
$trans[240]=287; //gbreve
$trans[208]=286; //Gbreve
$trans[221]=304; //Idot
$trans[253]=305; //inodot
$trans[254]=351; //scedil
$trans[222]=350; //Scedil

$GLOBALS['CHARSET']['iso-8859-9'] = $trans;

?>
