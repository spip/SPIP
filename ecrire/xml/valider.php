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

// Retourne une structure ValidateurXML, dont le champ "err" est un tableau
// ayant comme entrees des sous-tableaux [message, ligne, colonne]

// http://doc.spip.org/@xml_valider_dist
function xml_valider_dist($page, $apply=false, $process=false, $doctype='', $charset=null)
{
	$f = new ValidateurXML($process);
	$sax = charger_fonction('sax', 'xml');
	return $sax($page, $apply, $f, $doctype, $charset);
}
?>
