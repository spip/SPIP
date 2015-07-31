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
// http://doc.spip.org/@action_regler_moderation_dist
function action_regler_moderation_dist()
{
	include_spip('inc/autoriser');

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	$arg = _request('arg');

	if (!preg_match(",^\W*(\d+)$,", $arg, $r)) {
		spip_log("action_regler_moderation_dist $arg pas compris");
		return;
	}

	$id_article = $r[1];
	if (!autoriser('modifier', 'article', $id_article))
		return;

	$statut = _request('change_accepter_forum');
	spip_query("UPDATE spip_articles SET accepter_forum='$statut' WHERE id_article=". $id_article);
	if ($statut == 'abo') {
		ecrire_meta('accepter_visiteurs', 'oui');
		ecrire_metas();
	}
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_forum/a$id_article'");
}
?>
