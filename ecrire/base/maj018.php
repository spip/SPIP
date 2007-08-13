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

// http://doc.spip.org/@base_maj018_dist
function base_maj018_dist($version_installee, $version_cible)
{
	if (upgrade_vers(1.801, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_rubriques	ADD statut_tmp VARCHAR(10) NOT NULL,	ADD date_tmp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		include_spip('inc/rubriques');
		calculer_rubriques();
		maj_version(1.801);
	}

	// Nouvelles tables d'invalidation
	if (upgrade_vers(1.802, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_id_article_caches");
		spip_query("DROP TABLE spip_id_auteur_caches");
		spip_query("DROP TABLE spip_id_breve_caches");
		spip_query("DROP TABLE spip_id_document_caches");
		spip_query("DROP TABLE spip_id_forum_caches");
		spip_query("DROP TABLE spip_id_groupe_caches");
		spip_query("DROP TABLE spip_id_message_caches");
		spip_query("DROP TABLE spip_id_mot_caches");
		spip_query("DROP TABLE spip_id_rubrique_caches");
		spip_query("DROP TABLE spip_id_signature_caches");
		spip_query("DROP TABLE spip_id_syndic_article_caches");
		spip_query("DROP TABLE spip_id_syndic_caches");
		spip_query("DROP TABLE spip_id_type_caches");
		spip_query("DROP TABLE spip_inclure_caches");
		maj_version(1.802);
	}
	if (upgrade_vers(1.803, $version_installee, $version_cible)) {

	#	27 AOUT 2004 : conservons cette table pour autoriser les retours
	#	de SPIP 1.8a6 CVS vers 1.7.2
	#	spip_query("DROP TABLE spip_forum_cache");

		spip_query("DROP TABLE spip_inclure_caches");
		maj_version(1.803);
	}
	if (upgrade_vers(1.804, $version_installee, $version_cible)) {
		// recreer la table spip_caches
		spip_query("DROP TABLE spip_caches");
		creer_base();
		maj_version(1.804);
	}

	if (upgrade_vers(1.805, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD id_thread bigint(21) DEFAULT '0' NOT NULL");
		include_spip('inc/forum');
		calculer_threads();
		maj_version(1.805);
	}

	// tables d'orthographe
	#if ($version_installee < 1.806)
	#	maj_version(1.806);

	// URLs propres (inc_version = 0.12)
	if (upgrade_vers(1.807, $version_installee, $version_cible)) {
		foreach (array('articles', 'breves', 'rubriques', 'mots') as $objets) {
			spip_query("ALTER TABLE spip_$objets ADD url_propre VARCHAR(255) NOT NULL");
			spip_query("ALTER TABLE spip_$objets ADD INDEX url_propre (url_propre)");
		}
		maj_version(1.807);
	}

	// referers de la veille
	if (upgrade_vers(1.808, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_referers ADD visites_veille INT UNSIGNED NOT NULL");
		maj_version(1.808);
	}


	// corrections diverses
	if (upgrade_vers(1.809, $version_installee, $version_cible)) {
		// plus de retour possible vers 1.7.2
		spip_query("DROP TABLE spip_forum_cache");

		// les requetes ci-dessous ne s'appliqueront que si on est passe
		// par une certaine version de developpement - oublie de le faire
		// plus tot, car le code d'alors recreait purement et simplement
		// cette table
		spip_query("ALTER TABLE spip_versions DROP chapo");
		spip_query("ALTER TABLE spip_versions DROP texte");
		spip_query("ALTER TABLE spip_versions DROP ps");
		spip_query("ALTER TABLE spip_versions DROP extra");
		spip_query("ALTER TABLE spip_versions ADD champs text NOT NULL");

		maj_version(1.809);
	}

	// Annuler les brouillons de forum jamais valides
	if (upgrade_vers(1.810, $version_installee, $version_cible)) {
		spip_query("DELETE FROM spip_forum WHERE statut='redac'");
		maj_version(1.810);
	}

	if (upgrade_vers(1.811, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD extra longblob NULL");
		maj_version(1.811);
	}
	
	if (upgrade_vers(1.812, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_documents ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		maj_version(1.812);
	}

	// Mise a jour des types MIME
	if (upgrade_vers(1.813, $version_installee, $version_cible)) {
		# rien a faire car c'est creer_base() qui s'en charge
		maj_version(1.813);
	}

	// URLs propres auteurs
	if (upgrade_vers(1.814, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD url_propre VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX url_propre (url_propre)");
		maj_version(1.814);
	}

	// Mots-cles sur les documents
	// + liens documents <-> sites et articles syndiques (podcasting)
	if (upgrade_vers(1.815, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_documents	ADD distant VARCHAR(3) DEFAULT 'non'");
		maj_version(1.815);
	}

	// Indexation des documents (rien a faire sauf reinstaller inc_auxbase)
	if (upgrade_vers(1.816, $version_installee, $version_cible)) {
		maj_version(1.816);
	}

	// Texte et descriptif des groupes de mots-cles
	if (upgrade_vers(1.817, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_groupes_mots ADD descriptif text NOT NULL AFTER titre");
		spip_query("ALTER TABLE spip_groupes_mots ADD COLUMN texte longblob NOT NULL AFTER descriptif");
		maj_version(1.817);
	}

	// Conformite des noms de certains champs (0minirezo => minirezo)
	if (upgrade_vers(1.818, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_groupes_mots CHANGE COLUMN 0minirezo minirezo char(3) NOT NULL");
		spip_query("ALTER TABLE spip_groupes_mots CHANGE COLUMN 1comite comite char(3) NOT NULL");
		spip_query("ALTER TABLE spip_groupes_mots CHANGE COLUMN 6forum forum char(3) NOT NULL");
		maj_version(1.818);
	}

	// Options de syndication : miroir + oubli
	if (upgrade_vers(1.819, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD miroir VARCHAR(3) DEFAULT 'non'");
		spip_query("ALTER TABLE spip_syndic ADD oubli VARCHAR(3) DEFAULT 'non'");
		maj_version(1.819);
	}

	// Un bug dans les 1.730 (il manquait le "ADD")
	if (upgrade_vers(1.820, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_breves ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_mots ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_syndic ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_forum ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_signatures ADD INDEX idx (idx)");
		maj_version(1.820);
	}

	// reindexer les articles (on avait oublie les auteurs)
	if (upgrade_vers(1.821, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_articles SET idx='1' WHERE idx='oui'");
		maj_version(1.821);
	}
	// le 'type' des mots doit etre du texte, sinon on depasse en champ multi
	if (upgrade_vers(1.822, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_mots DROP INDEX type");
		spip_query("ALTER TABLE spip_mots CHANGE type type TEXT NOT NULL");
		maj_version(1.822);
	}
	// ajouter une table de fonctions pour ajax
	if (upgrade_vers(1.825, $version_installee, $version_cible)) {
		maj_version(1.825);
	}
	if (upgrade_vers(1.826, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_ajax_fonc DROP fonction");
		maj_version(1.826);
	}
}


?>
