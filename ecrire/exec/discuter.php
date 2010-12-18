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

// http://doc.spip.org/@exec_discuter_dist
function exec_discuter_dist()
{
	$script = preg_replace('/\W/','',_request('script'));
	$statut = preg_replace('/\W/','',_request('statut'));
	$objet = preg_replace('/\W/','',_request('objet'));
	$debut = intval(_request('debut'));
	$pas = intval(_request('pas'));
	$id = intval(_request($objet));
	$id_parent = intval(_request('id_parent'));
	$discuter = charger_fonction('discuter', 'inc');
	include_spip('inc/actions');
	ajax_retour($discuter($id, $script, $objet, $statut, $debut, $pas, $id_parent));
}
?>
