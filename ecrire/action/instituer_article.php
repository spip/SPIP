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

function action_instituer_article_dist() {

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
	list($id_article, $statut) = preg_split('/\W/', $arg);
	if (!$statut) $statut = _request('statut_nouv'); // cas POST
	if (!$statut) return; // impossible mais sait-on jamais

	$id_article = intval($id_article);
	$result = spip_query("SELECT statut FROM spip_articles WHERE id_article=$id_article");

	if ($row = spip_fetch_array($result)) {
		$statut_ancien = $row['statut'];
		}

	if ($statut != $statut_ancien) {
		spip_query("UPDATE spip_articles SET statut='$statut',	date=NOW() WHERE id_article=$id_article");

		include_spip('inc/rubriques');
		calculer_rubriques();

		if ($statut == 'publie') {
			if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_spip("inc/indexation");
			marquer_indexer('article', $id_article);
			}
			include_spip('inc/lang');
			include_spip('inc/texte');
			include_spip('inc/mail');
			envoyer_mail_publication($id_article);
		}

		if ($statut_ancien == 'publie') {
			include_spip('inc/invalideur');
			suivre_invalideur("id='id_article/$id_article'");
		}

		if ($statut == "prop" AND $statut_ancien != 'publie') {
			include_spip('inc/lang');
			include_spip('inc/texte');
			include_spip('inc/mail');
			envoyer_mail_proposition($id_article);
		}
	}
}
?>
