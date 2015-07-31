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

function action_virtualiser_dist() {
	
	global $convert_command;

	include_spip('inc/actions');

	$arg = _request('arg');
	$hash = _request('hash');
	$action = _request('action');
	$redirect = _request('redirect');
	$id_auteur = _request('id_auteur');
	$url = _request('virtuel');

	if (!verifier_action_auteur("$action-$arg", $hash, $id_auteur)) {
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	if (!preg_match(",^\W*(\d+)$,", $arg, $r)) {
		 spip_log("action_virtualiser_dist $arg $url pas compris");
	} else {
		$url = eregi_replace("^ *https?://$", "", rtrim($url));
		if ($url) $url = corriger_caracteres("=$url");
		spip_query("UPDATE spip_articles SET chapo=" . spip_abstract_quote($url) . ", date_modif=NOW() WHERE id_article=" . $r[1]);
	}
}
?>
