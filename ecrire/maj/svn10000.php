<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

/*--------------------------------------------------------------------- */
/*	Gestion des MAJ par tableau indexe par le numero SVN du chgt	*/
/*--------------------------------------------------------------------- */

// Type cls et sty pour LaTeX
$GLOBALS['maj'][10990] = array(array('upgrade_types_documents'));

// Type 3gp: http://www.faqs.org/rfcs/rfc3839.html
// Aller plus vite pour les vieilles versions en redeclarant une seule les doc
unset($GLOBALS['maj'][10990]);
$GLOBALS['maj'][11042] = array(array('upgrade_types_documents'));

function maj_11174() {
	global $tables_auxiliaires;
	include_spip('base/auxiliaires');
	$v = $tables_auxiliaires[$k='spip_recherches'];
	sql_drop_table('spip_recherches',true); // droppons la table cree en 11172
	sql_create($k, $v['field'], $v['key'], false, false);
}
$GLOBALS['maj'][11174] = array(array('maj_11174'));


// Un bug permettait au champ 'upload' d'etre vide, provoquant
// l'impossibilite de telecharger une image
// http://trac.rezo.net/trac/spip/ticket/1238
$GLOBALS['maj'][11171] = array(
	array('spip_query', "UPDATE spip_types_documents SET upload='oui' WHERE upload IS NULL OR upload!='non'")
);

?>
