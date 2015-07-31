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

// http://doc.spip.org/@exec_documenter_dist
function exec_documenter_dist()
{
	$script = _request('script'); // generalisation a tester
	$iframe = _request('iframe');
	$album = _request('s');
	$type = _request('type');
	$id = intval(_request(id_table_objet($type)));
	exec_documenter_args($id, $type, $script, $album, $iframe);
}

// http://doc.spip.org/@exec_documenter_args
function exec_documenter_args($id, $type, $script, $album='', $iframe=false)
{
	if (!$id OR !autoriser('modifier', $type, $id) OR !preg_match('/^\w*$/', $script)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		$album = !$album ? 'documents' :  'portfolio';
		include_spip('inc/actions');
		$documenter = charger_fonction('documenter', 'inc');
		if ($iframe==='iframe') { 
			$res = $documenter($id, $type, "portfolio", 'ajax', '', $script).
			  $documenter($id, $type, "documents", 'ajax', '', $script);
			ajax_retour("<div class='upload_answer upload_document_added'>".$res."</div>",false);
		} else ajax_retour($documenter($id, $type, $album, 'ajax', '', $script));
	}
}
?>
