<?

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

function changer_statut_articles($id_article, $statut)
{
	spip_log("arti $id_article, $statut");
	$result = spip_query("SELECT statut FROM spip_articles WHERE id_article=$id_article");

	if ($row = spip_fetch_array($result)) {
			$statut_ancien = $row['statut'];
		}

	if ($statut != $statut_ancien) {
		spip_query("UPDATE spip_articles SET statut='$statut', date=NOW() WHERE id_article=$id_article");			
		include_ecrire("inc_rubriques.php3");
		include_ecrire('inc_lang.php3');
		include_ecrire('inc_filtres.php3');
		include_ecrire('inc_texte.php3');
		calculer_rubriques();

		cron_articles($id_article, $statut, $statut_ancien);
	}
}

function cron_articles($id_article, $statut, $statut_ancien)
{
	global $invalider_caches;

	calculer_rubriques();

	if ($statut == 'publie') {
		if (lire_meta('activer_moteur') == 'oui') {
			include_ecrire ("inc_index.php3");
			marquer_indexer('article', $id_article);
		}
		include_ecrire("inc_mail.php3");
		envoyer_mail_publication($id_article);
	}

	if ($statut_ancien == 'publie' AND $invalider_caches) {
	  	include_ecrire ("inc_invalideur.php3");
		suivre_invalideur("id='id_article/$id_article'");
	}

	if ($statut == "prop" AND $statut_ancien != 'publie') {
		include_ecrire("inc_mail.php3");
		envoyer_mail_proposition($id_article);
	}
}

?>