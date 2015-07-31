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

include_spip('inc/filtres');

// http://doc.spip.org/@action_virtualiser_dist
function action_virtualiser_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	$url = _request('virtuel');

	if (!preg_match(",^\W*(\d+)$,", $arg, $r)) {
		 spip_log("action_virtualiser_dist $arg $url pas compris");
	} else action_virtualiser_post($r, $url);
}

// http://doc.spip.org/@action_virtualiser_post
function action_virtualiser_post($r, $url)
{
	$url = preg_replace(",^ *https?://$,i", "", rtrim($url));
	if ($url) $url = corriger_caracteres("=$url");
	sql_updateq('spip_articles', array('chapo'=> $url, 'date_modif' => date('Y-m-d H:i:s')), "id_article=" . $r[1]);
}
?>
