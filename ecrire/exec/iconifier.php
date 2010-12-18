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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_iconifier_dist
function exec_iconifier_dist()
{
	$script = _request('script');
	$iframe = _request('iframe');
	$type =_request('type');
	$id = intval(_request($type));
	exec_iconifier_args($id, $type, $script, $iframe);
}

// http://doc.spip.org/@exec_iconifier_args
function exec_iconifier_args($id, $primary, $script, $iframe=false)
{
	$type = objet_type(table_objet(substr($primary, 3)));
	if (!preg_match('/^\w+$/', "$primary$script")
	  OR !autoriser('iconifier', $type, $id)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

		$iconifier = charger_fonction('iconifier', 'inc');
		$ret = $iconifier($primary, $id, $script, $visible=true);
	
		if ($iframe!=='iframe') 
			ajax_retour($ret);
		else {
			echo "<div class='upload_answer upload_document_added'>$ret</div>";
		}
	}
}?>
