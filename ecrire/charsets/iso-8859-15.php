<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Déclaration des correspondances entre charsets iso-8859-15 et unicode
 *
 * @author Gaetan Ryckeboer <gryckeboer@virtual-net.fr>
 *
 * @package SPIP\Core\Charsets
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

load_charset('iso-8859-1');

$trans = $GLOBALS['CHARSET']['iso-8859-1'];
$trans[164] = 8364;
$trans[166] = 352;
$trans[168] = 353;
$trans[180] = 381;
$trans[184] = 382;
$trans[188] = 338;
$trans[189] = 339;
$trans[190] = 376;

$GLOBALS['CHARSET']['iso-8859-15'] = $trans;
