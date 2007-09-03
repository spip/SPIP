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

function maj_v019_dist($version_installee, $version_cible)
{
	// Syndication : ajout de l'option resume=oui/non et de la langue
	if (upgrade_vers(1.901, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD resume VARCHAR(3) DEFAULT 'oui'");
		spip_query("ALTER TABLE spip_syndic_articles ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		maj_version(1.901);
	}

	// Syndication : ajout de source, url_source, tags
	if (upgrade_vers(1.902, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic_articles ADD url_source TINYTEXT DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles ADD source TINYTEXT DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles ADD tags TEXT DEFAULT '' NOT NULL");
		maj_version(1.902);
	}

	// URLs propres des sites (sait-on jamais)
	// + oubli des KEY url_propre sur les auteurs si installation neuve
	if (upgrade_vers(1.903, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD url_propre VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD INDEX url_propre (url_propre)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX url_propre (url_propre)");
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
		$s=addslashes(serialize($liste_tables));
		spip_query("INSERT INTO `spip_meta` ( `nom` , `valeur` , `maj` ) VALUES ('index_table', '$s', NOW( ));");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_article as id_objet,'1' as id_table FROM spip_index_articles");
		spip_query("DROP TABLE IF EXISTS spip_index_articles");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_auteur as id_objet,'2' as id_table FROM spip_index_auteurs");
		spip_query("DROP TABLE IF EXISTS spip_index_auteurs");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_breve as id_objet,'3' as id_table FROM spip_index_breves");
		spip_query("DROP TABLE IF EXISTS spip_index_breves");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_document as id_objet,'4' as id_table FROM spip_index_documents");
		spip_query("DROP TABLE IF EXISTS spip_index_documents");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_forum as id_objet,'5' as id_table FROM spip_index_forum");
		spip_query("DROP TABLE IF EXISTS spip_index_forum");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_mot as id_objet,'6' as id_table FROM spip_index_mots");
		spip_query("DROP TABLE IF EXISTS spip_index_mots");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_rubrique as id_objet,'7' as id_table FROM spip_index_rubriques");
		spip_query("DROP TABLE IF EXISTS spip_index_rubriques");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_signature as id_objet,'8' as id_table FROM spip_index_signatures");
		spip_query("DROP TABLE IF EXISTS spip_index_signatures");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_syndic as id_objet,'9' as id_table FROM spip_index_syndic");
		spip_query("DROP TABLE IF EXISTS spip_index_syndic");
		lire_metas();
		ecrire_metas();
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
		spip_query("ALTER TABLE spip_forum ADD INDEX idx (idx)");
		maj_version(1.907);
	}
	// Oups ! on stockait les tags de syndication sous la forme rel="category"
	// au lieu de rel="directory" - http://microformats.org/wiki/rel-directory
	if (upgrade_vers(1.908, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_syndic_articles SET tags = REPLACE(tags, 'rel=\"category\">', 'rel=\"directory\">') WHERE tags like '%category%'");
		maj_version(1.908);
	}
	if (upgrade_vers(1.909, $version_installee, $version_cible)) {
		spip_query("ALTER IGNORE TABLE spip_mots_articles ADD PRIMARY KEY (id_article, id_mot)");
		spip_query("ALTER IGNORE TABLE spip_mots_breves ADD PRIMARY KEY (id_breve, id_mot)");
		spip_query("ALTER IGNORE TABLE spip_mots_rubriques ADD PRIMARY KEY (id_rubrique, id_mot)");
		spip_query("ALTER IGNORE TABLE spip_mots_syndic ADD PRIMARY KEY (id_syndic, id_mot)");
		spip_query("ALTER IGNORE TABLE spip_mots_documents ADD PRIMARY KEY (id_document, id_mot)");
		spip_query("ALTER IGNORE TABLE spip_mots_forum ADD PRIMARY KEY (id_forum, id_mot)");
		maj_version(1.909);
	}

	if (upgrade_vers(1.910, $version_installee, $version_cible)) {
		spip_query("ALTER IGNORE TABLE spip_auteurs_articles ADD PRIMARY KEY (id_auteur, id_article)");
		spip_query("ALTER IGNORE TABLE spip_auteurs_rubriques ADD PRIMARY KEY (id_auteur, id_rubrique)");
		spip_query("ALTER IGNORE TABLE spip_auteurs_messages ADD PRIMARY KEY (id_auteur, id_message)");
		maj_version(1.910);
	}

	if (upgrade_vers(1.911, $version_installee, $version_cible)) {

		spip_query("ALTER IGNORE TABLE spip_auteurs_articles DROP INDEX id_auteur");
		spip_query("ALTER IGNORE TABLE spip_auteurs_rubriques DROP INDEX id_auteur");
		spip_query("ALTER IGNORE TABLE spip_auteurs_messages DROP INDEX id_auteur");
		spip_query("ALTER IGNORE TABLE spip_mots_articles DROP INDEX id_article");
		spip_query("ALTER IGNORE TABLE spip_mots_breves DROP INDEX id_breve");
		spip_query("ALTER IGNORE TABLE spip_mots_rubriques DROP INDEX id_rubrique");
		spip_query("ALTER IGNORE TABLE spip_mots_syndic DROP INDEX id_syndic");
		spip_query("ALTER IGNORE TABLE spip_mots_forum DROP INDEX id_forum");
		spip_query("ALTER IGNORE TABLE spip_mots_documents DROP INDEX id_document");
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
		spip_query("ALTER TABLE spip_articles DROP auteur_modif");
		maj_version(1.913);
	}

	// Ajout de SVG
	if (upgrade_vers(1.914, $version_installee, $version_cible)) {
		spip_query("INSERT IGNORE INTO spip_types_documents (extension, titre, inclus) VALUES ('svg', 'Scalable Vector Graphics', 'embed')");
		spip_query("UPDATE spip_types_documents	SET mime_type='image/svg+xml' WHERE extension='svg'");
		maj_version(1.914);
	}

	// Ajout de plein de type mime
	if (upgrade_vers(1.915, $version_installee, $version_cible)) {
		maj_version(1.915);
	}
	// refaire l'upgrade 1.905 qui a pu foirer en partie a cause de la requete ALTER sur `spip_meta`
	if (upgrade_vers(1.916, $version_installee, $version_cible)) {
		// agrandir le champ "valeur" de spip_meta pour pouvoir y stocker
		// des choses plus sympa
		spip_query("ALTER TABLE spip_meta CHANGE `valeur` `valeur` TEXT");
#8/08/07  plus d'indexation dans le core
		//include_spip('inc/indexation'); 
		//update_index_tables();
		maj_version(1.916);
	}
	if (upgrade_vers(1.917, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_documents DROP inclus");
		maj_version(1.917);
	}

	// Permettre d'enregistrer un numero IP dans les revisions d'articles
	// a la place de l'id_auteur
	if (upgrade_vers(1.918, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_versions CHANGE `id_auteur` `id_auteur` VARCHAR(23)");
		maj_version(1.918);
	}

	if (upgrade_vers(1.919, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_ajax_fonc DROP id_auteur");
		maj_version('1.919');
	}

	if (upgrade_vers(1.920, $version_installee, $version_cible)) {
		spip_query("ALTER IGNORE TABLE spip_documents_articles ADD PRIMARY KEY (id_article, id_document)");
		spip_query("ALTER IGNORE TABLE spip_documents_breves ADD PRIMARY KEY (id_breve, id_document)");
		spip_query("ALTER IGNORE TABLE spip_documents_rubriques ADD PRIMARY KEY (id_rubrique, id_document)");
		spip_query("ALTER IGNORE TABLE spip_documents_articles DROP INDEX id_article");
		spip_query("ALTER IGNORE TABLE spip_documents_breves DROP INDEX id_breve");
		spip_query("ALTER IGNORE TABLE spip_documents_rubriques DROP INDEX id_rubrique");
		maj_version('1.920');
	}
	if (upgrade_vers(1.922, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_meta ADD impt ENUM('non', 'oui') DEFAULT 'oui' NOT NULL AFTER valeur");
		$meta_serveur = array('version_installee','adresse_site','alea_ephemere_ancien','alea_ephemere','alea_ephemere_date','langue_site','langues_proposees','date_calcul_rubriques','derniere_modif','optimiser_table','drapeau_edition','creer_preview','taille_preview','creer_htpasswd','creer_htaccess','gd_formats_read','gd_formats',
		'netpbm_formats','formats_graphiques','image_process','plugin_header','plugin');
		foreach($meta_serveur as $nom)
			spip_query("UPDATE spip_meta SET impt='non' WHERE nom="._q($nom));
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
		$req = spip_query("SELECT login FROM spip_auteurs WHERE statut = '0minirezo'");
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
		spip_query("INSERT IGNORE INTO spip_types_documents (extension, titre, inclus) VALUES ('mp4', 'MPEG4', 'embed')");
		spip_query("UPDATE spip_types_documents	SET mime_type='application/mp4' WHERE extension='mp4'");
		maj_version('1.926');
	}

	// FLV est embeddable, l'upgrade precedent l'avait oublie
	if (upgrade_vers(1.931, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_types_documents SET inclus='embed' WHERE extension='flv'");
		maj_version('1.931');
	}

	// Ajout de spip_forum.date_thread, et on essaie de le remplir
	// a coup de table temporaire (est-ce autorise partout... sinon
	// tant pis, ca ne marchera que pour les forums recemment modifies)
	if (upgrade_vers(1.932, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD date_thread datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD INDEX date_thread (date_thread)");

		spip_query("DROP TABLE IF EXISTS spip_tmp");
		spip_query("CREATE TEMPORARY TABLE spip_tmp SELECT id_thread,MAX(date_heure) AS dt FROM spip_forum GROUP BY id_thread");
		spip_query("ALTER TABLE spip_tmp ADD INDEX p (id_thread)");
		spip_query("UPDATE spip_forum AS F JOIN spip_tmp AS T ON F.id_thread=T.id_thread SET F.date_thread=T.dt");
		spip_query("DROP TABLE spip_tmp");

		maj_version('1.932');
	}
	if (upgrade_vers(1.933, $version_installee, $version_cible)) {
		// on ne fait que reecrire le numero de version installee en metant explicitement impt a 'non'
		maj_version('1.933');
	}

	// Retrait de _DIR_IMG dans le champ fichier de la table des doc
	if (upgrade_vers(1.934, $version_installee, $version_cible)) {
	  $dir_img = substr(_DIR_IMG,strlen(_DIR_RACINE));
	  $n = strlen($dir_img) + 1;
	  spip_query("UPDATE spip_documents SET fichier=substring(fichier,$n) WHERE fichier LIKE " . _q($dir_img . '%'));
	  maj_version('1.934');
	}
	if (upgrade_vers(1.935, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_documents_articles ADD vu ENUM('non', 'oui') DEFAULT 'non' NOT NULL");
		spip_query("ALTER TABLE spip_documents_rubriques ADD vu ENUM('non', 'oui') DEFAULT 'non' NOT NULL");
		spip_query("ALTER TABLE spip_documents_breves ADD vu ENUM('non', 'oui') DEFAULT 'non' NOT NULL");
		include_spip('inc/texte');
		foreach(array('article'=>'id_article','rubrique'=>'id_rubrique','breve'=>'id_breve') as $type => $id_table_objet){
			$table_objet = "$type"."s";
			$chapo = $type=='article' ? ",a.chapo":"";
			$res = spip_query("SELECT a.$id_table_objet,a.texte $chapo FROM spip_documents_$table_objet AS d JOIN spip_$table_objet AS a ON a.$id_table_objet=d.$id_table_objet GROUP BY $id_table_objet");
			while ($row = sql_fetch($res)){
				$GLOBALS['doublons_documents_inclus'] = array();
				traiter_modeles(($chapo?$row['chapo']:"").$row['texte'],true); // detecter les doublons
				if (count($GLOBALS['doublons_documents_inclus'])){
					$id = $row[$id_table_objet];
					$liste = "(".implode(",$id,'oui'),(",$GLOBALS['doublons_documents_inclus']).",$id,'oui')";
					spip_query("REPLACE INTO spip_documents_$table_objet (id_document,$id_table_objet,vu) VALUES $liste");
				}
			}
		}
	  maj_version('1.935');
	}

	if (upgrade_vers(1.937, $version_installee, $version_cible)) {
		// convertir les champs blob des tables spip en champs texte
		convertir_un_champ_blob_en_text("spip_articles","texte","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_articles","extra","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_auteurs","extra","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_breves","texte","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_breves","extra","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_messages","texte","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_mots","texte","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_mots","extra","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_groupes_mots","texte","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_rubriques","texte","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_rubriques","extra","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_syndic","nom_site","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_syndic","descriptif","TEXT");
		convertir_un_champ_blob_en_text("spip_syndic","extra","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_syndic_articles","descriptif","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_petitions","texte","LONGTEXT");
		convertir_un_champ_blob_en_text("spip_ortho_cache","suggest","TEXT");
		maj_version('1.938');
	}

	if (upgrade_vers(1.938, $version_installee, $version_cible)) {
		// Des champs NULL a l'installation
		// Ajouter un champ extension aux spip_documents, et le
		// remplir avec les valeurs ad hoc
		spip_query("ALTER TABLE spip_documents ADD extension VARCHAR(10) NOT NULL DEFAULT ''");
		spip_query("ALTER TABLE spip_documents ADD INDEX extension (extension)");
		$s = spip_query("SELECT id_type,extension FROM spip_types_documents");
		while ($t = sql_fetch($s)) {
			spip_query("UPDATE spip_documents
				SET extension="._q($t['extension'])
				." WHERE id_type="._q($t['id_type']));
		}
		spip_query("ALTER TABLE spip_documents DROP INDEX id_type, DROP id_type");
		spip_query("ALTER TABLE spip_types_documents DROP INDEX id_type, DROP id_type");

		## recreer la PRIMARY KEY sur spip_types_documents.extension
		spip_query("ALTER TABLE spip_types_documents ADD PRIMARY KEY (extension)");
		maj_version('1.938');
	}

	if (upgrade_vers(1.939, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_visites CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_visites_articles CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_referers CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_referers CHANGE `visites_jour` `visites_jour` INT UNSIGNED DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_referers CHANGE `visites_veille` `visites_veille` INT UNSIGNED DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_referers_articles CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL");
		maj_version('1.939');
	}

	if (upgrade_vers(1.940, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_caches");
		maj_version('1.940');
	}

	if (upgrade_vers(1.941, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_meta SET valeur = '' WHERE nom='preview' AND valeur='non' ");
		spip_query("UPDATE spip_meta SET valeur = ',0minirezo,1comite,' WHERE nom='preview' AND valeur='1comite' ");
		spip_query("UPDATE spip_meta SET valeur = ',0minirezo,' WHERE nom='preview' AND valeur='oui' ");
		maj_version('1.941');
	}

	if (upgrade_vers(1.942, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs CHANGE `statut` `statut` varchar(255)  DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_breves CHANGE `statut` `statut`varchar(6)  DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_messages CHANGE `statut` `statut`varchar(6)  DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_rubriques CHANGE `statut` `statut`varchar(10) DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_rubriques CHANGE `statut_tmp` `statut_tmp` varchar(10) DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_syndic CHANGE `statut` `statut`varchar(10) DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles CHANGE `statut` `statut`varchar(10) DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_forum CHANGE `statut` `statut`varchar(8) DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_signatures CHANGE `statut` `statut`varchar(10) DEFAULT '0' NOT NULL");
		maj_version('1.942');
	}

	// suppression de l'indexation dans la version standard
	if (upgrade_vers(1.943, $version_installee, $version_cible)) {
		foreach(array(
		'articles', 'auteurs', 'breves', 'mots', 'rubriques', 'documents', 'syndic', 'forum', 'signatures'
		) as $type) {
			spip_query("ALTER TABLE spip_$type DROP KEY `idx`");
			spip_query("ALTER TABLE spip_$type DROP `idx`");
		}
		spip_query("DROP TABLE spip_index");
		spip_query("DROP TABLE spip_index_dico");
		maj_version('1.943');
	}
	if (upgrade_vers(1.944, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_documents CHANGE taille taille integer");
		spip_query("ALTER TABLE spip_documents CHANGE largeur largeur  integer");
		spip_query("ALTER TABLE spip_documents CHANGE hauteur hauteur integer");
		maj_version('1.944');
	}
	if (upgrade_vers(1.945, $version_installee, $version_cible)) {
	  maj_v019_45();
	  maj_version('1.945');
	}
	if (upgrade_vers(1.946, $version_installee, $version_cible)) {
	  maj_v019_46();
	  maj_version('1.946');
	}
	if (upgrade_vers(1.947, $version_installee, $version_cible)) {
	  maj_v019_47();
	  maj_version('1.947');
	}
	// mauvaise manip
	if (upgrade_vers(1.949, $version_installee, $version_cible)) {
	  maj_v019_49();
	  maj_version('1.949');
	}
	if (upgrade_vers(1.950, $version_installee, $version_cible)) {
	  maj_v019_50();
	  maj_version('1.950');
	}
	if (upgrade_vers(1.951, $version_installee, $version_cible)) {
	  maj_v019_51();
	  maj_version('1.951');
	}

}

function maj_v019_45()
{
	spip_query("ALTER TABLE spip_petitions CHANGE email_unique email_unique CHAR (3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_petitions CHANGE site_obli site_obli CHAR (3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_petitions CHANGE site_unique site_unique CHAR (3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_petitions CHANGE message message CHAR (3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_petitions CHANGE texte texte LONGTEXT DEFAULT '' NOT NULL");
	
	spip_query("ALTER TABLE spip_articles CHANGE surtitre surtitre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_articles CHANGE titre titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_articles CHANGE soustitre soustitre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_articles CHANGE descriptif descriptif text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_articles CHANGE chapo chapo mediumtext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_articles CHANGE texte texte longtext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_articles CHANGE ps ps mediumtext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_articles CHANGE accepter_forum accepter_forum CHAR(3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_articles CHANGE nom_site nom_site tinytext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_articles CHANGE url_site url_site VARCHAR(255) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_articles CHANGE url_propre url_propre VARCHAR(255) DEFAULT '' NOT NULL");

	spip_query("ALTER TABLE spip_auteurs CHANGE nom nom text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_auteurs CHANGE bio bio text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_auteurs CHANGE email email tinytext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_auteurs CHANGE nom_site nom_site tinytext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_auteurs CHANGE url_site url_site text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_auteurs CHANGE pass pass tinytext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_auteurs CHANGE low_sec low_sec tinytext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_auteurs CHANGE pgp pgp TEXT DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_auteurs CHANGE htpass htpass tinytext DEFAULT '' NOT NULL");

	spip_query("ALTER TABLE spip_breves CHANGE titre titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_breves CHANGE texte texte longtext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_breves CHANGE lien_titre lien_titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_breves CHANGE lien_url lien_url text DEFAULT '' NOT NULL");

	spip_query("ALTER TABLE spip_messages CHANGE titre titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_messages CHANGE texte texte longtext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_messages CHANGE type type varchar(6) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_messages CHANGE rv rv varchar(3) DEFAULT '' NOT NULL");

	spip_query("ALTER TABLE spip_mots CHANGE titre titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_mots CHANGE descriptif descriptif text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_mots CHANGE texte texte longtext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_mots CHANGE type type text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_mots CHANGE url_propre url_propre VARCHAR(255) DEFAULT '' NOT NULL");
	
	spip_query("ALTER TABLE spip_groupes_mots CHANGE titre titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE descriptif descriptif text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE texte texte longtext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE unseul unseul varchar(3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE obligatoire obligatoire varchar(3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE articles articles varchar(3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE breves breves varchar(3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE rubriques rubriques varchar(3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE syndic syndic varchar(3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE minirezo minirezo varchar(3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE comite comite varchar(3) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_groupes_mots CHANGE forum forum varchar(3) DEFAULT '' NOT NULL");

	spip_query("ALTER TABLE spip_rubriques CHANGE titre titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_rubriques CHANGE descriptif descriptif text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_rubriques CHANGE texte texte longtext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_rubriques CHANGE url_propre url_propre VARCHAR(255) DEFAULT '' NOT NULL");

	spip_query("ALTER TABLE spip_documents CHANGE extension extension VARCHAR(10) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_documents CHANGE titre titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_documents CHANGE date date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
	spip_query("ALTER TABLE spip_documents CHANGE descriptif descriptif text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_documents CHANGE fichier fichier varchar(255) DEFAULT '' NOT NULL");

	spip_query("ALTER TABLE spip_types_documents CHANGE extension extension varchar(10) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_types_documents CHANGE titre titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_types_documents CHANGE descriptif descriptif text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_types_documents CHANGE mime_type mime_type varchar(100) DEFAULT '' NOT NULL");

	spip_query("ALTER TABLE spip_syndic CHANGE nom_site nom_site text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_syndic CHANGE url_site url_site text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_syndic CHANGE url_syndic url_syndic text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_syndic CHANGE descriptif descriptif text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_syndic CHANGE url_propre url_propre VARCHAR(255) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_syndic CHANGE syndication syndication VARCHAR(3) DEFAULT '' NOT NULL");

	spip_query("ALTER TABLE spip_syndic_articles CHANGE titre titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_syndic_articles CHANGE url url VARCHAR(255) DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_syndic_articles CHANGE lesauteurs lesauteurs text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_syndic_articles CHANGE descriptif descriptif text DEFAULT '' NOT NULL");

	spip_query("ALTER TABLE spip_forum CHANGE titre titre text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_forum CHANGE texte texte mediumtext DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_forum CHANGE auteur auteur text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_forum CHANGE email_auteur email_auteur text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_forum CHANGE nom_site nom_site text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_forum CHANGE url_site url_site text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_forum CHANGE ip ip varchar(16) DEFAULT '' NOT NULL");
	
	spip_query("ALTER TABLE spip_signatures CHANGE nom_email nom_email text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_signatures CHANGE ad_email ad_email text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_signatures CHANGE nom_site nom_site text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_signatures CHANGE url_site url_site text DEFAULT '' NOT NULL");
	spip_query("ALTER TABLE spip_signatures CHANGE message message mediumtext DEFAULT '' NOT NULL");
}

// http://trac.rezo.net/trac/spip/changeset/10150
function maj_v019_46()
{
	sql_alter("TABLE spip_forum DROP INDEX id_parent");
	sql_alter("TABLE spip_forum DROP INDEX id_article");
	sql_alter("TABLE spip_forum DROP INDEX id_breve");
	sql_alter("TABLE spip_forum DROP INDEX id_syndic");
	sql_alter("TABLE spip_forum DROP INDEX id_rubrique");
	sql_alter("TABLE spip_forum DROP INDEX date_thread");
	sql_alter("TABLE spip_forum DROP INDEX statut");
	sql_alter("TABLE spip_forum ADD INDEX optimal (statut,id_parent,id_article,date_heure,id_breve,id_syndic,id_rubrique)");
}

// http://trac.rezo.net/trac/spip/changeset/10151
function maj_v019_47()
{
	sql_alter("TABLE spip_articles DROP INDEX url_site");
	sql_alter("TABLE spip_articles DROP INDEX date_modif");
	sql_alter("TABLE spip_auteurs  DROP INDEX lang");
}

function maj_v019_49()
{
	sql_alter("TABLE spip_versions DROP INDEX date");
	sql_alter("TABLE spip_versions DROP INDEX id_auteur");
}

function maj_v019_50()
{
  // oubli de gerer le prefixe lors l'introduction de l'abstraction
  // => on relance les dernieres MAJ en mode silencieux pour mettre au carre.
	@maj_v019_46();
	@maj_v019_47();
	@maj_v019_49();
	global $tables_auxiliaires;
	include_spip('base/auxiliaires');
	$v = $tables_auxiliaires[$k='spip_urls'];
	sql_create($k, $v['field'], $v['key'], false, false);
	foreach(array('article'=>'id_article',
		      'rubrique'=>'id_rubrique',
		      'breve'=>'id_breve',
		      'auteur' => 'id_auteur', 
		      'mot' => 'id_mot', 
		      'syndic' => 'id_syndic') as $type => $id_objet){
		$table = ($type == 'syndic') ? $type : ($type ."s");
		$date = ($type == 'breve') ? 'date_heure' : 
		  (($type == 'auteur') ? 'maj' : 
		   (($type == 'mot') ? 'maj' : 'date'));
		$q = sql_select("url_propre AS url, $id_objet AS id_objet, '$type' AS type, $date as maj", "spip_$table", "url_propre<>''");
		while ($r = sql_fetch($q)) sql_replace('spip_urls', $r);
		spip_log("table $table : " . sql_count($q) . " urls propres copiees");
		sql_alter("TABLE spip_$table DROP INDEX url_propre");
		sql_alter("TABLE spip_$table DROP url_propre");		
	}
}

// http://trac.rezo.net/trac/spip/changeset/10210
// Erreur dans maj_v019_48():
// // http://trac.rezo.net/trac/spip/changeset/10194
// // Gestion du verrou SQL par PHP

function maj_v019_51()
{
	sql_alter("TABLE spip_versions CHANGE id_version id_version bigint(21) DEFAULT 0 NOT NULL");
}
?>
