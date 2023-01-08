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
 * Déclaration des correspondances entre charsets iso-8859-9 (Turc) et unicode
 *
 * @author <alexis@nds.k12.tr>
 *
 * @package SPIP\Core\Charsets
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

load_charset('iso-8859-1');

$trans = $GLOBALS['CHARSET']['iso-8859-1'];
$trans[240] = 287; //gbreve
$trans[208] = 286; //Gbreve
$trans[221] = 304; //Idot
$trans[253] = 305; //inodot
$trans[254] = 351; //scedil
$trans[222] = 350; //Scedil

$GLOBALS['CHARSET']['iso-8859-9'] = $trans;
