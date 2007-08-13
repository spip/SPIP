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

// http://doc.spip.org/@base_maj010_dist
function base_maj010_dist($version_installee, $version_cible)
{
	
	if (upgrade_vers(1.01, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_forum SET statut='publie' WHERE statut=''");
		maj_version (1.01);
	}
	
	if (upgrade_vers(1.02, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD id_auteur BIGINT DEFAULT '0' NOT NULL");
		maj_version (1.02);
	}

	if (upgrade_vers(1.03, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_maj");
		maj_version (1.03);
	}

	if (upgrade_vers(1.04, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3)");
		maj_version (1.04);
	}

	if (upgrade_vers(1.05, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_petition");
		spip_query("DROP TABLE spip_signatures_petition");
		maj_version (1.05);
	}
}


?>
