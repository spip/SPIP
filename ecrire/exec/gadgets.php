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

// http://doc.spip.org/@exec_gadgets_dist
function exec_gadgets_dist()
{
	$id_rubrique = intval(_request('id_rubrique'));
	$gadget = _request('gadget');
	$gadgets = charger_fonction('gadgets', 'inc');

	header("Cache-Control: max-age=3600");

	if ($date = intval(_request('date')))
		header("Last-Modified: ".gmdate("D, d M Y H:i:s", $date)." GMT");

	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
	AND !strstr($_SERVER['SERVER_SOFTWARE'],'IIS/')) {
		include_spip('inc/headers');
		header('Content-Type: text/html; charset='. $GLOBALS['meta']['charset']);
		http_status(304);
		exit;
	} else {
		ajax_retour($gadgets($id_rubrique, $gadget));
	}
}
?>
