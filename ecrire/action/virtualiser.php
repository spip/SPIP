<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres');
include_spip('inc/actions');

// http://doc.spip.org/@action_virtualiser_dist
function action_virtualiser_dist() {
	
	global $convert_command;

	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');
	$url = _request('virtuel');

	if (!preg_match(",^\W*(\d+)$,", $arg, $r)) {
		 spip_log("action_virtualiser_dist $arg $url pas compris");
	} else action_virtualiser_post($r);
}

// http://doc.spip.org/@action_virtualiser_post
function action_virtualiser_post($r)
{
	$url = eregi_replace("^ *https?://$", "", rtrim($url));
	if ($url) $url = corriger_caracteres("=$url");
	spip_query("UPDATE spip_articles SET chapo=" . spip_abstract_quote($url) . ", date_modif=NOW() WHERE id_article=" . $r[1]);
}
?>
