<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/sax');

define('_REGEXP_DOCTYPE',
	'/^\s*(<[?][^>]*>\s*)?<!DOCTYPE\s+(\w+)\s+(\w+)\s*([^>]*)>\s*/');

define('_SUB_REGEXP_SYMBOL', '[A-Za-z_][\w_:.-]*');

define('_REGEXP_ID', '/^'  . _SUB_REGEXP_SYMBOL . '$/');

define('_REGEXP_ENTITY_USE', '/%('  . _SUB_REGEXP_SYMBOL . ');/');
define('_REGEXP_ENTITY_DEF', '/^%('  . _SUB_REGEXP_SYMBOL . ');/');
define('_REGEXP_ENTITY_DECL', '/^<!ENTITY\s+(%?)\s*(' .
		_SUB_REGEXP_SYMBOL .
		';?)\s+(PUBLIC|SYSTEM|INCLUDE|IGNORE)?\s*"([^"]*)"\s*("([^"]*)")?\s*>\s*(.*)$/s');

// Document Type Compilation

class DTC {
	var	$macros = array();
	var 	$elements = array();
	var 	$peres = array();
	var 	$attributs = array();
	var	$entites = array();
	var	$regles = array();
}
?>
