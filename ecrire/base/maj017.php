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

// http://doc.spip.org/@base_maj017_dist
function base_maj017_dist($version_installee, $version_cible)
{
	if (upgrade_vers(1.702, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_auteurs ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_breves ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_rubriques ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_mots ADD extra longblob NULL");

		// recuperer les eventuels 'supplement' installes en 1.701
		if ($version_installee == 1.701) {
			spip_query("UPDATE spip_articles SET extra = supplement");
			spip_query("ALTER TABLE spip_articles DROP supplement");
			spip_query("UPDATE spip_auteurs SET extra = supplement");
			spip_query("ALTER TABLE spip_auteurs DROP supplement");
			spip_query("UPDATE spip_breves SET extra = supplement");
			spip_query("ALTER TABLE spip_breves DROP supplement");
			spip_query("UPDATE spip_rubriques SET extra = supplement");
			spip_query("ALTER TABLE spip_rubriques DROP supplement");
			spip_query("UPDATE spip_mots SET extra = supplement");
			spip_query("ALTER TABLE spip_mots DROP supplement");
		}
		
		$u = spip_query("SELECT extra FROM spip_articles");
		$u&= spip_query("SELECT extra FROM spip_auteurs");
		$u&= spip_query("SELECT extra FROM spip_breves");
		$u&= spip_query("SELECT extra FROM spip_rubriques");
		$u&= spip_query("SELECT extra FROM spip_mots");
		maj_version (1.702,$u);
	}

	if (upgrade_vers(1.703, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_rubriques ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		maj_version (1.703);
	}

	if (upgrade_vers(1.704, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD INDEX lang (lang)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX lang (lang)");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX lang (lang)");
		maj_version (1.704);
	}

	if (upgrade_vers(1.705, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		spip_query("ALTER TABLE spip_rubriques ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		maj_version (1.705);
	}

	if (upgrade_vers(1.707, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_articles SET langue_choisie='oui' WHERE MID(lang,1,1) != '.' AND lang != ''");
		spip_query("UPDATE spip_articles SET lang=MID(lang,2,8) WHERE langue_choisie = 'non'");
		spip_query("UPDATE spip_rubriques SET langue_choisie='oui' WHERE MID(lang,1,1) != '.' AND lang != ''");
		spip_query("UPDATE spip_rubriques SET lang=MID(lang,2,8) WHERE langue_choisie = 'non'");
		maj_version (1.707);
	}

	if (upgrade_vers(1.708, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_breves ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_breves ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		maj_version (1.708);
	}

	if (upgrade_vers(1.709, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD id_trad bigint(21) DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD INDEX id_trad (id_trad)");
		maj_version (1.709);
	}

	if (upgrade_vers(1.717, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD INDEX date_modif (date_modif)");
		maj_version (1.717);
	}

	if (upgrade_vers(1.718, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_referers DROP domaine");
		spip_query("ALTER TABLE spip_referers_articles DROP domaine");
		spip_query("ALTER TABLE spip_referers_temp DROP domaine");
		maj_version (1.718);
	}

	if (upgrade_vers(1.722, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD nom_site tinytext NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD url_site VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD INDEX url_site (url_site)");
		if ($version_installee >= 1.720) {
			spip_query("UPDATE spip_articles SET url_site=url_ref");
			spip_query("ALTER TABLE spip_articles DROP INDEX url_ref");
			spip_query("ALTER TABLE spip_articles DROP url_ref");
		}
		maj_version (1.722);
	}

	if (upgrade_vers(1.723, $version_installee, $version_cible)) {
		if ($version_installee == 1.722) {
			spip_query("ALTER TABLE spip_articles MODIFY url_site VARCHAR(255) NOT NULL");
			spip_query("ALTER TABLE spip_articles DROP INDEX url_site;");
			spip_query("ALTER TABLE spip_articles ADD INDEX url_site (url_site);");
		}
		maj_version (1.723);
	}

	if (upgrade_vers(1.724, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_messages ADD date_fin datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		maj_version (1.724);
	}

	if (upgrade_vers(1.726, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD low_sec tinytext NOT NULL");
		maj_version (1.726);
	}

	if (upgrade_vers(1.727, $version_installee, $version_cible)) {
		// occitans : oci_xx -> oc_xx
		spip_query("UPDATE spip_auteurs SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_rubriques SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_articles SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_breves SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		maj_version (1.727);
	}

	// Ici version 1.7 officielle
	if (upgrade_vers(1.728, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD id_version int unsigned DEFAULT '0' NOT NULL");
		maj_version (1.728);
	}

	if (upgrade_vers(1.730, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_auteurs ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_breves ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_breves ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_mots ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_mots ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_rubriques ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_syndic ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_forum ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_signatures ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_signatures ADD INDEX idx (idx)");
		maj_version (1.730);
	}

	if (upgrade_vers(1.731, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_articles SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_rubriques SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_breves SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_auteurs SET idx='1' where lang IN ('de','vi')");
		maj_version (1.731);
	}

	if (upgrade_vers(1.732, $version_installee, $version_cible)) { // en correction d'un vieux truc qui avait fait sauter le champ inclus sur les bases version 1.415
		spip_query("ALTER TABLE spip_documents ADD inclus  VARCHAR(3) DEFAULT 'non'");
		maj_version (1.732);
	}

	if (upgrade_vers(1.733, $version_installee, $version_cible)) {
		// spip_query("ALTER TABLE spip_articles ADD id_version int unsigned DEFAULT '0' NOT NULL");
		spip_query("DROP TABLE spip_versions");
		spip_query("DROP TABLE spip_versions_fragments");
		creer_base();
		maj_version(1.733);
	}

	#if ($version_installee < 1.734) {
	#	// integrer nouvelles tables auxiliaires du compilateur ESJ
	#	creer_base();
	#	maj_version(1.734);
	#}
}


?>
