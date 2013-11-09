<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@xml_indenter_dist
function xml_indenter_dist($page, $apply=false)
{
	$sax = charger_fonction('sax', 'xml');
	$f = new IndenteurXML();
	$sax($page, $apply, $f);
	if (!$f->err) return $f->entete . $f->res;
	spip_log("indentation impossible " . count($f->err) . " erreurs de validation");
	return $f->entete . $f->page;
}

?>
