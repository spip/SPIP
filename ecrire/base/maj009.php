<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@base_maj009_dist
function base_maj009_dist($version_installee, $version_cible)
{
	if (upgrade_vers(0.98, $version_installee, $version_cible)) {

		spip_query("ALTER TABLE spip_articles ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_articles ADD export VARCHAR(10) DEFAULT 'oui'");
		spip_query("ALTER TABLE spip_articles ADD images TEXT DEFAULT ''");
		spip_query("ALTER TABLE spip_articles ADD date_redac datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_articles DROP INDEX id_article");
		spip_query("ALTER TABLE spip_articles ADD INDEX id_rubrique (id_rubrique)");
		spip_query("ALTER TABLE spip_articles ADD visites INTEGER DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD referers BLOB NOT NULL");

		spip_query("ALTER TABLE spip_auteurs ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_auteurs ADD pgp BLOB NOT NULL");

		spip_query("ALTER TABLE spip_auteurs_articles ADD INDEX id_auteur (id_auteur), ADD INDEX id_article (id_article)");
	
		spip_query("ALTER TABLE spip_rubriques ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_rubriques ADD export VARCHAR(10) DEFAULT 'oui', ADD id_import BIGINT DEFAULT '0'");
	
		spip_query("ALTER TABLE spip_breves ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_breves DROP INDEX id_breve");
		spip_query("ALTER TABLE spip_breves DROP INDEX id_breve_2");
		spip_query("ALTER TABLE spip_breves ADD INDEX id_rubrique (id_rubrique)");
	
		spip_query("ALTER TABLE spip_forum ADD ip VARCHAR(16)");
		spip_query("ALTER TABLE spip_forum ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_forum DROP INDEX id_forum");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_parent (id_parent), ADD INDEX id_rubrique (id_rubrique), ADD INDEX id_article(id_article), ADD INDEX id_breve(id_breve)");
		maj_version (0.98);
	}

	if (upgrade_vers(0.99, $version_installee, $version_cible)) {
	
		$result = spip_query("SELECT DISTINCT id_article FROM spip_forum WHERE id_article!=0 AND id_parent=0");

		while ($row = sql_fetch($result)) {
			unset($forums_article);
			$id_article = $row['id_article'];
			$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_article=$id_article");
			for (;;) {
				unset($forums);
				while ($row2 = sql_fetch($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_article[] = $forums;
				$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)");
			}
			$forums_article = join(',', $forums_article);
			spip_query("UPDATE spip_forum SET id_article=$id_article WHERE id_forum IN ($forums_article)");
		}
	
		$result = spip_query("SELECT DISTINCT id_breve FROM spip_forum WHERE id_breve!=0 AND id_parent=0");

		while ($row = sql_fetch($result)) {
			unset($forums_breve);
			$id_breve = $row['id_breve'];
			$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_breve=$id_breve");
			for (;;) {
				unset($forums);
				while ($row2 = sql_fetch($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_breve[] = $forums;
				$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)");
			}
			$forums_breve = join(',', $forums_breve);
			spip_query("UPDATE spip_forum SET id_breve=$id_breve WHERE id_forum IN ($forums_breve)");
		}
	
		$result = spip_query("SELECT DISTINCT id_rubrique FROM spip_forum WHERE id_rubrique!=0 AND id_parent=0");

		while ($row = sql_fetch($result)) {
			unset($forums_rubrique);
			$id_rubrique = $row['id_rubrique'];
			$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_rubrique=$id_rubrique");
			for (;;) {

				unset($forums);
				while ($row2 = sql_fetch($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_rubrique[] = $forums;
				$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)");
			}
			$forums_rubrique = join(',', $forums_rubrique);
			spip_query("UPDATE spip_forum SET id_rubrique=$id_rubrique WHERE id_forum IN ($forums_rubrique)");

		}
		maj_version (0.99);
	}

	if (upgrade_vers(0.997, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_index");
		maj_version (0.997);
	}

	if (upgrade_vers(0.999, $version_installee, $version_cible)) {

		spip_query("ALTER TABLE spip_auteurs CHANGE pass pass tinyblob NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD htpass tinyblob NOT NULL");
		$result = spip_query("SELECT id_auteur, pass FROM spip_auteurs WHERE pass!=''");

		while ($r= sql_fetch($result)) {
			$htpass = generer_htpass($r['pass']);
			$pass = md5($pass);
			spip_query("UPDATE spip_auteurs SET pass='$pass', htpass='$htpass' WHERE id_auteur=" . $r['id_auteur']);
		}
		maj_version (0.999);
	}
}

?>
