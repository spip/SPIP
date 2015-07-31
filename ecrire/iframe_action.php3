<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

include ("inc.php3");
@header("Cache-Control: no-store, no-cache, must-revalidate");
echo "";

if (($id > 0) && ($connect_statut == "0minirezo")) {


	#### OUH LA, il faudrait passer ca et les modifs des articles.php3 etc
	#### dans un seul fichier, sinon toute modif faite ici doit etre reportee
	#### la, et inversement : l'enfer

	if ($action == 'statut_article') {

		$id_article = $id;
		$query = "SELECT statut FROM spip_articles WHERE id_article=$id_article";
		$result = spip_query($query);
		if ($row = spip_fetch_array($result)) {
			$statut_ancien = $row['statut'];
		}

		
		if ($statut != $statut_ancien) {
			$query = "UPDATE spip_articles SET statut='$statut' WHERE id_article=$id_article";
			$result = spip_query($query);
			
			include_ecrire("inc_rubriques.php3");

			if ($statut == 'publie') {
				spip_query("UPDATE spip_articles SET date=NOW() WHERE id_article=$id_article");
				include_ecrire ("inc_index.php3");
				indexer_article($id_article);
				calculer_rubriques();
				include_ecrire("inc_mail.php3");
				envoyer_mail_publication($id_article);
			}
			if ($statut_ancien == 'publie' AND $invalider_caches) {
				include_ecrire ("inc_invalideur.php3");
				suivre_invalideur("id='id_article/$id_article'");
				calculer_rubriques();
			}
			if ($statut == "prop" AND $statut_ancien != 'publie') {
				include_ecrire("inc_mail.php3");
				envoyer_mail_proposition($id_article);
			}
		}
	}

	elseif ($action == 'statut_breve') {

		$id_breve = $id;
		$query = "SELECT statut FROM spip_breves WHERE id_breve=$id_breve";
		$result = spip_query($query);
		if ($row = spip_fetch_array($result)) {
			$statut_ancien = $row['statut'];
		}
				
		if ($statut != $statut_ancien) {
			$query = "UPDATE spip_breves SET date_heure=NOW(), statut='$statut' WHERE id_breve=$id_breve";
			$result = spip_query($query);
			
			include_ecrire("inc_rubriques.php3");
			calculer_rubriques();
		}
	}
 }
?>
