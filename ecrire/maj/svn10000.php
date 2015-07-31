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
?>
