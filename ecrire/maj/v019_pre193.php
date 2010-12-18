<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function v019_pre193($version_installee, $version_cible)
{
	// Syndication : ajout de l'option resume=oui/non et de la langue
	if (upgrade_vers(1.901, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD `resume` VARCHAR(3) DEFAULT 'oui'");
		spip_query("ALTER TABLE spip_syndic_articles ADD `lang` VARCHAR(10) DEFAULT '' NOT NULL");
		maj_version(1.901);
	}

	// Syndication : ajout de source, url_source, tags
	if (upgrade_vers(1.902, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic_articles ADD `url_source` TINYTEXT DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles ADD `source` TINYTEXT DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles ADD `tags` TEXT DEFAULT '' NOT NULL");
		maj_version(1.902);
	}

	// URLs propres des sites (sait-on jamais)
	// + oubli des KEY url_propre sur les auteurs si installation neuve
	if (upgrade_vers(1.903, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD `url_propre` VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD INDEX `url_propre` (`url_propre`)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX `url_propre` (`url_propre`)");
		maj_version(1.903);
	}

	// suppression des anciennes tables temporaires des visites
	// (maintenant stockees sous forme de fichiers)
	if (upgrade_vers(1.904, $version_installee, $version_cible)) {
		spip_query("DROP TABLE IF EXISTS spip_visites_temp");
		spip_query("DROP TABLE IF EXISTS spip_referers_temp");
		maj_version(1.904);
	}

	// fusion des 10 tables index en une seule
	// pour fonctions futures evoluees du moteur de recherche
	if (upgrade_vers(1.905, $version_installee, $version_cible)) {
		// agrandir le champ "valeur" de spip_meta pour pouvoir y stocker
		// des choses plus sympa
		spip_query("ALTER TABLE spip_meta DROP INDEX `valeur`");
		spip_query("ALTER TABLE spip_meta CHANGE `valeur` `valeur` TEXT");
		// table des correspondances table->id_table
		$liste_tables = array();
		$liste_tables[1]='spip_articles';
		$liste_tables[2]='spip_auteurs';
		$liste_tables[3]='spip_breves';
		$liste_tables[4]='spip_documents';
		$liste_tables[5]='spip_forum';
		$liste_tables[6]='spip_mots';
		$liste_tables[7]='spip_rubriques';
		$liste_tables[8]='spip_signatures';
		$liste_tables[9]='spip_syndic';

		ecrire_meta('index_table', serialize($liste_tables));

## devenu inutile car suppression totale de l'indexation
/*
		spip_query("INSERT INTO spip_index (`hash`,`points`,`id_objet`,`id_table`) SELECT `hash`,`points`,`id_article` as id_objet,'1' as id_table FROM spip_index_articles");
		spip_query("DROP TABLE IF EXISTS spip_index_articles");

		spip_query("INSERT INTO spip_index (`hash`,`points`,`id_objet`,`id_table`) SELECT `hash`,`points`,`id_auteur` as id_objet,'2' as id_table FROM spip_index_auteurs");
		spip_query("DROP TABLE IF EXISTS spip_index_auteurs");

		spip_query("INSERT INTO spip_index (`hash`,`points`,`id_objet`,`id_table`) SELECT `hash`,`points`,`id_breve` as id_objet,'3' as id_table FROM spip_index_breves");
		spip_query("DROP TABLE IF EXISTS spip_index_breves");

		spip_query("INSERT INTO spip_index (`hash`,`points`,`id_objet`,`id_table`) SELECT `hash`,`points`,`id_document` as id_objet,'4' as id_table FROM spip_index_documents");
		spip_query("DROP TABLE IF EXISTS spip_index_documents");

		spip_query("INSERT INTO spip_index (`hash`,`points`,`id_objet`,`id_table`) SELECT `hash`,`points`,`id_forum` as id_objet,'5' as id_table FROM spip_index_forum");
		spip_query("DROP TABLE IF EXISTS spip_index_forum");

		spip_query("INSERT INTO spip_index (`hash`,`points`,`id_objet`,`id_table`) SELECT `hash`,`points`,`id_mot` as id_objet,'6' as id_table FROM spip_index_mots");
		spip_query("DROP TABLE IF EXISTS spip_index_mots");

		spip_query("INSERT INTO spip_index (`hash`,`points`,`id_objet`,`id_table`) SELECT `hash`,`points`,`id_rubrique` as id_objet,'7' as id_table FROM spip_index_rubriques");
		spip_query("DROP TABLE IF EXISTS spip_index_rubriques");

		spip_query("INSERT INTO spip_index (`hash`,`points`,`id_objet`,`id_table`) SELECT `hash`,`points`,`id_signature` as id_objet,'8' as id_table FROM spip_index_signatures");
		spip_query("DROP TABLE IF EXISTS spip_index_signatures");

		spip_query("INSERT INTO spip_index (`hash`,`points`,`id_objet`,`id_table`) SELECT `hash`,`points`,`id_syndic` as id_objet,'9' as `id_table FROM spip_index_syndic");
		spip_query("DROP TABLE IF EXISTS spip_index_syndic");
*/
		maj_version(1.905);
	}


	// cette table est desormais geree par le plugin "podcast_client", on la
	// supprime si le plugin n'est pas active ; risque inherent a l'utilisation
	// de versions alpha :-)
	if (upgrade_vers(1.906, $version_installee, $version_cible)) {
		if (!@in_array('podcast_client', $GLOBALS['plugins'])) {
			spip_query("DROP TABLE spip_documents_syndic");
		}
		maj_version(1.906);
	}
	if (upgrade_vers(1.907, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD INDEX `idx` (`idx`)");
		maj_version(1.907);
	}
	// Oups ! on stockait les tags de syndication sous la forme rel="category"
	// au lieu de rel="directory" - http://microformats.org/wiki/rel-directory
	if (upgrade_vers(1.908, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_syndic_articles SET `tags` = REPLACE(`tags`, 'rel=\"category\">', 'rel=\"directory\">') WHERE `tags` like '%category%'");
		maj_version(1.908);
	}
	if (upgrade_vers(1.909, $version_installee, $version_cible)) {
		spip_query("ALTER IGNORE TABLE spip_mots_articles ADD PRIMARY KEY (`id_article`, `id_mot`)");
		spip_query("ALTER IGNORE TABLE spip_mots_breves ADD PRIMARY KEY (`id_breve`, `id_mot`)");
		spip_query("ALTER IGNORE TABLE spip_mots_rubriques ADD PRIMARY KEY (`id_rubrique`, `id_mot`)");
		spip_query("ALTER IGNORE TABLE spip_mots_syndic ADD PRIMARY KEY (`id_syndic`, `id_mot`)");
		spip_query("ALTER IGNORE TABLE spip_mots_documents ADD PRIMARY KEY (`id_document`, `id_mot`)");
		spip_query("ALTER IGNORE TABLE spip_mots_forum ADD PRIMARY KEY (`id_forum`, `id_mot`)");
		maj_version(1.909);
	}

	if (upgrade_vers(1.910, $version_installee, $version_cible)) {
		spip_query("ALTER IGNORE TABLE spip_auteurs_articles ADD PRIMARY KEY (`id_auteur`, `id_article`)");
		spip_query("ALTER IGNORE TABLE spip_auteurs_rubriques ADD PRIMARY KEY (`id_auteur`, `id_rubrique`)");
		spip_query("ALTER IGNORE TABLE spip_auteurs_messages ADD PRIMARY KEY (`id_auteur`, `id_message`)");
		maj_version(1.910);
	}

	if (upgrade_vers(1.911, $version_installee, $version_cible)) {

		spip_query("ALTER IGNORE TABLE spip_auteurs_articles DROP INDEX `id_auteur`");
		spip_query("ALTER IGNORE TABLE spip_auteurs_rubriques DROP INDEX `id_auteur`");
		spip_query("ALTER IGNORE TABLE spip_auteurs_messages DROP INDEX `id_auteur`");
		spip_query("ALTER IGNORE TABLE spip_mots_articles DROP INDEX `id_article`");
		spip_query("ALTER IGNORE TABLE spip_mots_breves DROP INDEX `id_breve`");
		spip_query("ALTER IGNORE TABLE spip_mots_rubriques DROP INDEX `id_rubrique`");
		spip_query("ALTER IGNORE TABLE spip_mots_syndic DROP INDEX `id_syndic`");
		spip_query("ALTER IGNORE TABLE spip_mots_forum DROP INDEX `id_forum`");
		spip_query("ALTER IGNORE TABLE spip_mots_documents DROP INDEX `id_document`");
# 18 juillet 2007: table depreciee
#		spip_query("ALTER IGNORE TABLE spip_caches DROP	INDEX fichier");
		maj_version(1.911);
	}

	// Le logo du site n'est plus le logo par defaut des rubriques
	// mais pour assurer la compatibilite ascendante, on le duplique
	if (upgrade_vers(1.912, $version_installee, $version_cible)) {
		@copy(_DIR_LOGOS.'rubon0.gif', _DIR_LOGOS.'siteon0.gif');
		@copy(_DIR_LOGOS.'ruboff0.gif', _DIR_LOGOS.'siteoff0.gif');
		@copy(_DIR_LOGOS.'rubon0.jpg', _DIR_LOGOS.'siteon0.jpg');
		@copy(_DIR_LOGOS.'ruboff0.jpg', _DIR_LOGOS.'siteoff0.jpg');
		@copy(_DIR_LOGOS.'rubon0.png', _DIR_LOGOS.'siteon0.png');
		@copy(_DIR_LOGOS.'ruboff0.png', _DIR_LOGOS.'siteoff0.png');
		maj_version(1.912);
	}

	// suppression de auteur_modif qui n'est plus utilise nulle part
	if (upgrade_vers(1.913, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles DROP `auteur_modif`");
		maj_version(1.913);
	}

	// Ajout de SVG
	if (upgrade_vers(1.914, $version_installee, $version_cible)) {
		spip_query("INSERT IGNORE INTO spip_types_documents (`extension`, `titre`, `inclus`) VALUES ('svg', 'Scalable Vector Graphics', 'embed')");
		spip_query("UPDATE spip_types_documents SET `mime_type`='image/svg+xml' WHERE `extension`='svg'");
		maj_version(1.914);
	}

	// Ajout de plein de type mime
	if (upgrade_vers(1.915, $version_installee, $version_cible)) {
		maj_version(1.915);
	}
	// refaire l'upgrade 1.905 qui a pu foirer en partie a cause de la requete ALTER sur spip_meta
	if (upgrade_vers(1.916, $version_installee, $version_cible)) {
		// agrandir le champ "valeur" de spip_meta pour pouvoir y stocker
		// des choses plus sympa
		spip_query("ALTER TABLE spip_meta DROP INDEX `valeur`");
		spip_query("ALTER TABLE spip_meta CHANGE `valeur` `valeur` TEXT");
#8/08/07  plus d'indexation dans le core
		//include_spip('inc/indexation'); 
		//update_index_tables();
		maj_version(1.916);
	}
	if (upgrade_vers(1.917, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_documents DROP `inclus`");
		maj_version(1.917);
	}

	// Permettre d'enregistrer un numero IP dans les revisions d'articles
	// a la place de l'id_auteur
	if (upgrade_vers(1.918, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_versions CHANGE `id_auteur` `id_auteur` VARCHAR(23)");
		maj_version(1.918);
	}

	if (upgrade_vers(1.919, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_ajax_fonc DROP `id_auteur`");
		maj_version('1.919');
	}

	if (upgrade_vers(1.920, $version_installee, $version_cible)) {
		spip_query("ALTER IGNORE TABLE spip_documents_articles ADD PRIMARY KEY (`id_article`, `id_document`)");
		spip_query("ALTER IGNORE TABLE spip_documents_breves ADD PRIMARY KEY (`id_breve`, `id_document`)");
		spip_query("ALTER IGNORE TABLE spip_documents_rubriques ADD PRIMARY KEY (`id_rubrique`, `id_document`)");
		spip_query("ALTER IGNORE TABLE spip_documents_articles DROP INDEX `id_article`");
		spip_query("ALTER IGNORE TABLE spip_documents_breves DROP INDEX `id_breve`");
		spip_query("ALTER IGNORE TABLE spip_documents_rubriques DROP INDEX `id_rubrique`");
		maj_version('1.920');
	}
	if (upgrade_vers(1.922, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_meta ADD `impt` ENUM('non', 'oui') DEFAULT 'oui' NOT NULL AFTER `valeur`");
		$meta_serveur = array('version_installee','adresse_site','alea_ephemere_ancien','alea_ephemere','alea_ephemere_date','langue_site','langues_proposees','date_calcul_rubriques','derniere_modif','optimiser_table','drapeau_edition','creer_preview','taille_preview','creer_htpasswd','creer_htaccess','gd_formats_read','gd_formats',
		'netpbm_formats','formats_graphiques','image_process','plugin_header','plugin');
		foreach($meta_serveur as $nom)
			spip_query("UPDATE spip_meta SET `impt`='non' WHERE `nom`="._q($nom));
		maj_version('1.922');
	}
	if (upgrade_vers(1.923, $version_installee, $version_cible)) {
		if (isset($GLOBALS['meta']['IMPORT_tables_noimport'])){
			$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
			foreach ($IMPORT_tables_noimport as $key=>$table)
				if ($table=='spip_meta') unset($IMPORT_tables_noimport[$key]);
			ecrire_meta('IMPORT_tables_noimport',serialize($IMPORT_tables_noimport),'non');
		}
		maj_version('1.923');
	}

	if (upgrade_vers(1.924, $version_installee, $version_cible)) {
		spip_query('DROP TABLE spip_ajax_fonc');
		maj_version('1.924');
	}

	if (upgrade_vers(1.925, $version_installee, $version_cible)) {
		include_spip('inc/flock');
		/* deplacement des sessions */
		$f_session = preg_files('data', 'session_');
		$repertoire = _DIR_SESSIONS;
		if(!@file_exists($repertoire)) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		foreach($f_session as $f) {
			$d = basename($f);
			@copy($f, $repertoire.$d);
		}
		/* deplacement des visites */
		$f_visites = preg_files('data/visites');
		$repertoire = sous_repertoire(_DIR_TMP, 'visites');
		foreach($f_visites as $f) {
			$d = basename($f);
			@copy($f, $repertoire.$d);
		}
		/* deplacement des upload */
		$auteurs = array();
		$req = spip_query("SELECT `login` FROM spip_auteurs WHERE `statut` = '0minirezo'");
		while($row = sql_fetch($req))
			$auteurs[] = $row['login']; 
		$f_upload = preg_files('upload', -1, 10000, $auteurs);
		$repertoire = _DIR_TRANSFERT;
		if(!@file_exists($repertoire)) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		foreach($auteurs as $login) {
			if(is_dir('upload/'.$login))
				$sous_repertoire = sous_repertoire(_DIR_TRANSFERT, $login);
		}
		foreach($f_upload as $f) {
			@copy($f, _DIR_TMP.$f);
		}
		/* deplacement des dumps */
		$f_session = preg_files('data', 'dump');
		$repertoire = _DIR_DUMP;
		if(!@file_exists($repertoire)) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		foreach($f_session as $f) {
			$d = basename($f);
			@copy($f, $repertoire.$d);
		}
		maj_version('1.925');
	}
	// Ajout de MP4
	if (upgrade_vers(1.926, $version_installee, $version_cible)) {
		spip_query("INSERT IGNORE INTO spip_types_documents (`extension`, `titre`, `inclus`) VALUES ('mp4', 'MPEG4', 'embed')");
		spip_query("UPDATE spip_types_documents SET `mime_type`='application/mp4' WHERE `extension`='mp4'");
		maj_version('1.926');
	}
}
?>
