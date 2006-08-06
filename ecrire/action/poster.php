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


// Modifier le reglage des forums publics de l'article x
function action_poster_dist()
{
	include_spip('inc/actions');

	$arg = _request('arg');
	$hash = _request('hash');
	$action = _request('action');
	$redirect = _request('redirect');
	$id_auteur = _request('id_auteur');

	if (!verifier_action_auteur("$action-$arg", $hash, $id_auteur)) {
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	if (!preg_match(",^\W*(\d+)$,", $arg, $r)) {
		 spip_log("action_poster_dist $arg pas compris");
	} else {
		$id_article = $r[1];
		$statut = _request('change_accepter_forum');
		spip_query("UPDATE spip_articles SET accepter_forum='$statut' WHERE id_article=". $id_article);
		if ($statut == 'abo') {
			ecrire_meta('accepter_visiteurs', 'oui');
			ecrire_metas();
		}
		include_spip('inc/invalideur');
		suivre_invalideur("id='id_forum/a$id_article'");
	}
}
?>
