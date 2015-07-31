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


function base_maj013_dist($version_installee, $version_cible)
{
	if (upgrade_vers(1.3, $version_installee, $version_cible)) {
		// Modifier la syndication (pour liste de sites)
		spip_query("ALTER TABLE spip_syndic ADD syndication VARCHAR(3) NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD statut VARCHAR(10) NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("UPDATE spip_syndic SET syndication='oui', statut='publie', date=NOW()");

		// Statut pour articles syndication, pour pouvoir desactiver un article
		spip_query("ALTER TABLE spip_syndic_articles ADD statut VARCHAR(10) NOT NULL");
		spip_query("UPDATE spip_syndic_articles SET statut='publie'");
		maj_version (1.3);
	}

	if (upgrade_vers(1.301, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD id_syndic bigint(21) DEFAULT '0' NOT NULL");
		maj_version (1.301);
	}

	if (upgrade_vers(1.302, $version_installee, $version_cible)) {
		# spip_query("ALTER TABLE spip_forum_cache DROP PRIMARY KEY");
		# spip_query("ALTER TABLE spip_forum_cache DROP INDEX fichier");
		# spip_query("ALTER TABLE spip_forum_cache ADD PRIMARY KEY (fichier, id_forum, id_article, id_rubrique, id_breve, id_syndic)");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_syndic (id_syndic)");
		maj_version (1.302);
	}

	if (upgrade_vers(1.303, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_rubriques ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD date_syndic datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("UPDATE spip_syndic SET date_syndic=date");
		maj_version (1.303);
	}

	if (upgrade_vers(1.306, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_index_syndic_articles");
		spip_query("ALTER TABLE spip_syndic ADD date_index datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD INDEX date_index (date_index)");
		maj_version (1.306);
	}

	if (upgrade_vers(1.307, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic_articles ADD descriptif blob NOT NULL");
		maj_version (1.307);
	}
}


?>
