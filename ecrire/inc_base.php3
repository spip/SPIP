<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_BASE")) return;
define("_ECRIRE_INC_BASE", "1");

include_local ("inc_acces.php3");


function creer_base() {

	//
	// Elements redactionnels
	//

	$query = "CREATE TABLE spip_articles (
		id_article bigint(21) DEFAULT '0' NOT NULL auto_increment,
		surtitre text NOT NULL,
		titre text NOT NULL, soustitre text NOT NULL,
		id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		descriptif text NOT NULL,
		chapo mediumtext NOT NULL,
		texte longblob NOT NULL,
		ps mediumtext NOT NULL,
		date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		statut varchar(10) DEFAULT '0' NOT NULL,
		id_secteur bigint(21) DEFAULT '0' NOT NULL,
		maj TIMESTAMP, 
		export VARCHAR(10) DEFAULT 'oui',
		date_redac datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		visites INTEGER DEFAULT '0' NOT NULL,
		referers BLOB NOT NULL,
		accepter_forum VARCHAR(3) NOT NULL,
		PRIMARY KEY (id_article),
		KEY id_rubrique (id_rubrique))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_auteurs (
		id_auteur bigint(21) DEFAULT '0' NOT NULL auto_increment,
		nom text NOT NULL,
		bio text NOT NULL,
		email tinytext NOT NULL,
		nom_site tinytext NOT NULL,
		url_site text NOT NULL,
		login tinytext NOT NULL,
		pass tinytext NOT NULL,
		statut tinyblob NOT NULL,
		maj TIMESTAMP,
		pgp BLOB NOT NULL,
		htpass tinyblob NOT NULL,
		en_ligne datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		imessage VARCHAR(3) NOT NULL,
		messagerie VARCHAR(3) NOT NULL,
		PRIMARY KEY (id_auteur))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_breves (
		id_breve bigint(21) DEFAULT '0' NOT NULL auto_increment,
		date_heure datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		titre text NOT NULL,
		texte longblob NOT NULL,
		lien_titre text NOT NULL,
		lien_url text NOT NULL,
		statut varchar(6) NOT NULL,
		id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (id_breve),
		KEY id_rubrique (id_rubrique))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_messages (
		id_message bigint(21) DEFAULT '0' NOT NULL auto_increment,
		titre text NOT NULL,
		texte longblob NOT NULL,
		type varchar(6) NOT NULL,
		date_heure datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		rv varchar(3) NOT NULL,
		statut varchar(6) NOT NULL,
		id_auteur bigint(21) NOT NULL,
		maj TIMESTAMP,
		KEY id_auteur (id_auteur),
		PRIMARY KEY (id_message))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_mots (
		id_mot bigint(21) DEFAULT '0' NOT NULL auto_increment,
		type VARCHAR(100) NOT NULL,
		titre text NOT NULL,
		descriptif text NOT NULL,
		texte longblob NOT NULL,
		id_groupe bigint(21) NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (id_mot),
		KEY type(type))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_groupes_mots (
		id_groupe bigint(21) DEFAULT '0' NOT NULL auto_increment,
		titre text NOT NULL,
		unseul varchar(3) NOT NULL,
		obligatoire varchar(3) NOT NULL,
		articles varchar(3) NOT NULL,
		breves varchar(3) NOT NULL,
		rubriques varchar(3) NOT NULL,
		syndic varchar(3) NOT NULL,
		0minirezo varchar(3) NOT NULL,
		1comite varchar(3) NOT NULL,
		6forum varchar(3) NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (id_groupe))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_rubriques (
		id_rubrique bigint(21) DEFAULT '0' NOT NULL auto_increment,
		id_parent bigint(21) DEFAULT '0' NOT NULL,
		titre text NOT NULL,
		descriptif text NOT NULL,
		texte longblob NOT NULL,
		id_secteur bigint(21) DEFAULT '0' NOT NULL,
		maj TIMESTAMP,
		export VARCHAR(10) DEFAULT 'oui',
		id_import BIGINT DEFAULT '0',
		statut VARCHAR(10) NOT NULL,
		date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY (id_rubrique),
		KEY id_parent (id_parent))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_documents (
		id_document bigint(21) DEFAULT '0' NOT NULL auto_increment,
		id_vignette bigint(21) DEFAULT '0' NOT NULL,
		id_type bigint(21) DEFAULT '0' NOT NULL,
		titre text NOT NULL,
		descriptif text NOT NULL,
		fichier varchar(255) NOT NULL,
		taille integer NOT NULL,
		largeur integer NOT NULL,
		hauteur integer NOT NULL,
		mode ENUM('vignette', 'document') NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (id_document),
		KEY id_vignette (id_vignette),
		KEY mode (mode),
		KEY id_type (id_type))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_types_documents (
		id_type bigint(21) DEFAULT '0' NOT NULL auto_increment,
		titre text NOT NULL,
		descriptif text NOT NULL,
		extension varchar(10) NOT NULL,
		mime_type varchar(100) NOT NULL,
		inclus ENUM('non', 'image', 'embed') NOT NULL DEFAULT 'non',
		upload ENUM('oui', 'non') NOT NULL DEFAULT 'oui',
		maj TIMESTAMP,
		PRIMARY KEY (id_type),
		UNIQUE extension (extension),
		KEY inclus (inclus))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_syndic (
		id_syndic bigint(21) DEFAULT '0' NOT NULL auto_increment,
		id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		id_secteur bigint(21) DEFAULT '0' NOT NULL,
		nom_site blob NOT NULL, 
		url_site blob NOT NULL,
		url_syndic blob NOT NULL,
		descriptif blob NOT NULL,
		maj TIMESTAMP,
		syndication VARCHAR(3) NOT NULL,
		statut VARCHAR(10) NOT NULL, 
		date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		date_syndic datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY (id_syndic),
		KEY id_rubrique (id_rubrique),
		KEY id_secteur (id_secteur))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_syndic_articles (
		id_syndic_article bigint(21) DEFAULT '0' NOT NULL auto_increment,
		id_syndic bigint(21) DEFAULT '0' NOT NULL,
		titre text NOT NULL,
		url text NOT NULL,
		date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		lesauteurs text NOT NULL,
		maj TIMESTAMP,
		statut VARCHAR(10) NOT NULL,
		date_index datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		descriptif blob NOT NULL,
		PRIMARY KEY (id_syndic_article),
		KEY id_syndic (id_syndic))";
	$result = mysql_query($query);


	//
	// Elements interactifs
	//

	$query = "CREATE TABLE spip_forum (
		id_forum bigint(21) DEFAULT '0' NOT NULL auto_increment, 
		id_parent bigint(21) DEFAULT '0' NOT NULL,
		id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		id_article bigint(21) DEFAULT '0' NOT NULL,
		id_breve bigint(21) DEFAULT '0' NOT NULL,
		date_heure datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		titre text NOT NULL,
		texte mediumtext NOT NULL,
		auteur text NOT NULL,
		email_auteur text NOT NULL,
		nom_site text NOT NULL,
		url_site text NOT NULL,
		statut varchar(8) NOT NULL,
		ip varchar(16),
		maj TIMESTAMP,
		id_auteur BIGINT DEFAULT '0' NOT NULL,
		id_message bigint(21) DEFAULT '0' NOT NULL,
		id_syndic bigint(21) DEFAULT '0' NOT NULL,
		PRIMARY KEY (id_forum),
		KEY id_parent(id_parent),
		KEY id_rubrique(id_rubrique),
		KEY id_article(id_article),
		KEY id_breve(id_breve),
		KEY id_message(id_message))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_petitions (
		id_article bigint(21) DEFAULT '0' NOT NULL,
		email_unique char(3) NOT NULL,
		site_obli char(3) NOT NULL,
		site_unique char(3) NOT NULL,
		message char(3) NOT NULL,
		texte longblob NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (id_article))";
	$result = mysql_query($query);
	
	$query = "CREATE TABLE spip_signatures (
		id_signature bigint(21) DEFAULT '0' NOT NULL auto_increment,
		id_article bigint(21) DEFAULT '0' NOT NULL,
		date_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		nom_email text NOT NULL,
		ad_email text NOT NULL,
		nom_site text NOT NULL,
		url_site text NOT NULL,
		message mediumtext NOT NULL,
		statut varchar(10) NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (id_signature),
		KEY id_article (id_article),
		KEY statut(statut))";
	$result = mysql_query($query);


	//
	// Relations
	//

	$query = "CREATE TABLE spip_auteurs_articles (
		id_auteur bigint(21) DEFAULT '0' NOT NULL, id_article bigint(21) DEFAULT '0' NOT NULL,
		KEY id_auteur (id_auteur), KEY id_article (id_article))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_auteurs_rubriques (
		id_auteur bigint(21) DEFAULT '0' NOT NULL, id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		KEY id_auteur (id_auteur), KEY id_rubrique (id_rubrique))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_auteurs_messages (
		id_auteur bigint(21) DEFAULT '0' NOT NULL, id_message bigint(21) DEFAULT '0' NOT NULL, vu CHAR(3) NOT NULL,
		KEY id_auteur (id_auteur), KEY id_message (id_message))";
	$result = mysql_query($query);


	$query = "CREATE TABLE spip_documents_articles (
		id_document bigint(21) DEFAULT '0' NOT NULL, id_article bigint(21) DEFAULT '0' NOT NULL,
		KEY id_document (id_document), KEY id_article (id_article))";
	$result = mysql_query($query);


	$query = "CREATE TABLE spip_mots_articles (
		id_mot bigint(21) DEFAULT '0' NOT NULL, id_article bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot), KEY id_article (id_article))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_mots_breves (
		id_mot bigint(21) DEFAULT '0' NOT NULL, id_breve bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot), KEY id_breve (id_breve))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_mots_rubriques (
		id_mot bigint(21) DEFAULT '0' NOT NULL, id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot), KEY id_rubrique (id_rubrique))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_mots_syndic (
		id_mot bigint(21) DEFAULT '0' NOT NULL, id_syndic bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot), KEY id_syndic (id_syndic))";
	$result = mysql_query($query);


	//
	// Gestion du site
	//

	$query = "CREATE TABLE spip_forum_cache (
		id_forum bigint(21) DEFAULT '0' NOT NULL,
		id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		id_article bigint(21) DEFAULT '0' NOT NULL,
		id_breve bigint(21) DEFAULT '0' NOT NULL,
		id_syndic bigint(21) DEFAULT '0' NOT NULL,
		fichier char(150) binary NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (fichier, id_forum, id_article, id_rubrique, id_breve, id_syndic),
		KEY id_forum(id_forum),
		KEY id_rubrique(id_rubrique),
		KEY id_article(id_article),
		KEY id_syndic(id_syndic),
		KEY id_breve(id_breve))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_meta (
		nom VARCHAR(255) NOT NULL, valeur VARCHAR(255) DEFAULT '', maj TIMESTAMP,
		PRIMARY KEY (nom))";
	$result = mysql_query($query);


	//
	// Indexation (moteur de recherche)
	//

	$query = "CREATE TABLE spip_index_articles (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_article int unsigned NOT NULL,
		KEY hash (hash), KEY id_article (id_article))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_index_auteurs (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_auteur int unsigned NOT NULL,
		KEY hash (hash), KEY id_auteur (id_auteur))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_index_breves (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_breve int unsigned NOT NULL,
		KEY hash (hash), KEY id_breve (id_breve))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_index_mots (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_mot int unsigned NOT NULL,
		KEY hash (hash), KEY id_mot (id_mot))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_index_rubriques (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_rubrique int unsigned NOT NULL,
		KEY hash (hash), KEY id_rubrique (id_rubrique))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_index_syndic (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_syndic int unsigned NOT NULL,
		KEY hash (hash), KEY id_syndic (id_syndic))";
	$result = mysql_query($query);

	$query = "CREATE TABLE spip_index_dico (
		hash bigint unsigned NOT NULL, dico VARCHAR(30) NOT NULL,
		PRIMARY KEY (dico))";
	$result = mysql_query($query);


	//
	// Pre-remplissage de la base
	//

	$query = "INSERT IGNORE spip_types_documents (id_type, extension, titre, inclus) VALUES ".
		"(1, 'jpg', 'Image JPEG', 'image'), ".
		"(2, 'png', 'Image PNG', 'image'), ".
		"(3, 'gif', 'Image GIF', 'image')";
	mysql_query($query);

	$query = "INSERT IGNORE spip_types_documents (extension, titre, inclus) VALUES ".
		"('bmp', 'Image BMP', 'image'), ".
		"('psd', 'Image Photoshop', 'image'), ".
		"('tif', 'Image TIFF', 'image')";
	mysql_query($query);

	$query = "INSERT IGNORE spip_types_documents (extension, titre) VALUES ".
		"('txt', 'Document texte'), ".
		"('htm', 'Fichier HTML'), ".
		"('html', 'Fichier HTML'), ".
		"('pdf', 'Document PDF'), ".
		"('mp3', 'Fichier sonore MP3'), ".
		"('wav', 'Fichier sonore WAV'), ".
		"('mid', 'Musique au format Midi'), ".
		"('mpg', 'Video MPEG'), ".
		"('ra', 'Fichier RealAudio'), ".
		"('ram', 'Fichier RealAudio'), ".
		"('rm', 'Fichier RealAudio'), ".
		"('mov', 'Video QuickTime'), ".
		"('qt', 'Video QuickTime'), ".
		"('asf', 'Video Windows'), ".
		"('avi', 'Video Windows'), ".
		"('doc', 'Document Word'), ".
		"('xls', 'Document Excel'), ".
		"('ppt', 'Document PowerPoint'), ".
		"('zip', 'Archive Zip'), ".
		"('gz', 'Archive GZ'), ".
		"('tgz', 'Archive TGZ'), ".
		"('swf', 'Animation Flash')";
	mysql_query($query);

}


function stripslashes_base($table, $champs) {
	$modifs = '';
	reset($champs);
	while (list(, $champ) = each($champs)) {
		$modifs[] = $champ . '=REPLACE(REPLACE(' .$champ. ',"\\\\\'", "\'"), \'\\\\"\', \'"\')';
	}
	$query = "UPDATE $table SET ".join(',', $modifs);
	mysql_query($query);
}


function maj_base() {

	global $spip_version;

	//
	// Lecture de la version installee
	//
	$version_installee = 0.0;
	$result = mysql_query("SELECT valeur FROM spip_meta WHERE nom='version_installee'");
	if ($result) if ($row = mysql_fetch_array($result)) $version_installee = (double) $row[0];
	
	//
	// Selection en fonction de la version
	//
	if ($version_installee < 0.98) {
	
		mysql_query("ALTER TABLE spip_articles ADD maj TIMESTAMP");
		mysql_query("ALTER TABLE spip_articles ADD export VARCHAR(10) DEFAULT 'oui'");
		mysql_query("ALTER TABLE spip_articles ADD images TEXT DEFAULT ''");
		mysql_query("ALTER TABLE spip_articles ADD date_redac datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		mysql_query("ALTER TABLE spip_articles DROP INDEX id_article");
		mysql_query("ALTER TABLE spip_articles ADD INDEX id_rubrique (id_rubrique)");
		mysql_query("ALTER TABLE spip_articles ADD visites INTEGER DEFAULT '0' NOT NULL");
		mysql_query("ALTER TABLE spip_articles ADD referers BLOB NOT NULL");
	
		mysql_query("ALTER TABLE spip_auteurs ADD maj TIMESTAMP");
		mysql_query("ALTER TABLE spip_auteurs ADD pgp BLOB NOT NULL");
	
		mysql_query("ALTER TABLE spip_auteurs_articles ADD INDEX id_auteur (id_auteur), ADD INDEX id_article (id_article)");
	
		mysql_query("ALTER TABLE spip_rubriques ADD maj TIMESTAMP");
		mysql_query("ALTER TABLE spip_rubriques ADD export VARCHAR(10) DEFAULT 'oui', ADD id_import BIGINT DEFAULT '0'");
	
		mysql_query("ALTER TABLE spip_breves ADD maj TIMESTAMP");
		mysql_query("ALTER TABLE spip_breves DROP INDEX id_breve");
		mysql_query("ALTER TABLE spip_breves DROP INDEX id_breve_2");
		mysql_query("ALTER TABLE spip_breves ADD INDEX id_rubrique (id_rubrique)");
	
		mysql_query("ALTER TABLE spip_forum ADD ip VARCHAR(16)");
		mysql_query("ALTER TABLE spip_forum ADD maj TIMESTAMP");
		mysql_query("ALTER TABLE spip_forum DROP INDEX id_forum");
		mysql_query("ALTER TABLE spip_forum ADD INDEX id_parent (id_parent), ADD INDEX id_rubrique (id_rubrique), ADD INDEX id_article(id_article), ADD INDEX id_breve(id_breve)");
	}
	
	if ($version_installee < 0.99) {
	
		$query = "SELECT DISTINCT id_article FROM spip_forum WHERE id_article!=0 AND id_parent=0";
		$result = mysql_query($query);
		while ($row = mysql_fetch_array($result)) {
			unset($forums_article);
			$id_article = $row[0];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_article=$id_article";
			for (;;) {
				$result2 = mysql_query($query2);
				unset($forums);
				while ($row2 = mysql_fetch_array($result2)) $forums[] = $row2[0];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_article[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_article = join(',', $forums_article);
			$query3 = "UPDATE spip_forum SET id_article=$id_article WHERE id_forum IN ($forums_article)";
			mysql_query($query3);
		}
	
		$query = "SELECT DISTINCT id_breve FROM spip_forum WHERE id_breve!=0 AND id_parent=0";
		$result = mysql_query($query);
		while ($row = mysql_fetch_array($result)) {
			unset($forums_breve);
			$id_breve = $row[0];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_breve=$id_breve";
			for (;;) {
				$result2 = mysql_query($query2);
				unset($forums);
				while ($row2 = mysql_fetch_array($result2)) $forums[] = $row2[0];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_breve[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_breve = join(',', $forums_breve);
			$query3 = "UPDATE spip_forum SET id_breve=$id_breve WHERE id_forum IN ($forums_breve)";
			mysql_query($query3);
		}
	
		$query = "SELECT DISTINCT id_rubrique FROM spip_forum WHERE id_rubrique!=0 AND id_parent=0";
		$result = mysql_query($query);
		while ($row = mysql_fetch_array($result)) {
			unset($forums_rubrique);
			$id_rubrique = $row[0];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_rubrique=$id_rubrique";
			for (;;) {
				$result2 = mysql_query($query2);
				unset($forums);
				while ($row2 = mysql_fetch_array($result2)) $forums[] = $row2[0];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_rubrique[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_rubrique = join(',', $forums_rubrique);
			$query3 = "UPDATE spip_forum SET id_rubrique=$id_rubrique WHERE id_forum IN ($forums_rubrique)";
			mysql_query($query3);
		}
	}

	if ($version_installee < 0.997) {
		mysql_query("DROP TABLE spip_index");
	}

	if ($version_installee < 0.999) {
		global $htsalt;
		mysql_query("ALTER TABLE spip_auteurs CHANGE pass pass tinyblob NOT NULL");
		mysql_query("ALTER TABLE spip_auteurs ADD htpass tinyblob NOT NULL");
		$query = "SELECT id_auteur, pass FROM spip_auteurs WHERE pass!=''";
		$result = mysql_query($query);
		while (list($id_auteur, $pass) = mysql_fetch_array($result)) {
			$htpass = generer_htpass($pass);
			$pass = md5($pass);
			mysql_query("UPDATE spip_auteurs SET pass='$pass', htpass='$htpass' WHERE id_auteur=$id_auteur");
		}
	}
	
	if ($version_installee < 1.01) {
		mysql_query("UPDATE spip_forum SET statut='publie' WHERE statut=''");
	}
	
	if ($version_installee < 1.02) {
		mysql_query("ALTER TABLE spip_forum ADD id_auteur BIGINT DEFAULT '0' NOT NULL");
	}

	if ($version_installee < 1.03) {
		mysql_query("DROP TABLE spip_maj");
	}

	if ($version_installee < 1.04) {
		mysql_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3)");
	}

	if ($version_installee < 1.05) {
		mysql_query("DROP TABLE spip_petition");
		mysql_query("DROP TABLE spip_signatures_petition");
	}

	if ($version_installee < 1.1) {
		mysql_query("DROP TABLE spip_petition");
		mysql_query("DROP TABLE spip_signatures_petition");
	}

	// Correction de l'oubli des modifs creations depuis 1.04
	if ($version_installee < 1.204) {
		mysql_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3) NOT NULL");
		mysql_query("ALTER TABLE spip_forum ADD id_message bigint(21) NOT NULL");
		mysql_query("ALTER TABLE spip_forum ADD INDEX id_message (id_message)");
		mysql_query("ALTER TABLE spip_auteurs ADD en_ligne datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		mysql_query("ALTER TABLE spip_auteurs ADD imessage VARCHAR(3) not null");
		mysql_query("ALTER TABLE spip_auteurs ADD messagerie VARCHAR(3) not null");
	}

	if ($version_installee < 1.207) {
		mysql_query("ALTER TABLE spip_rubriques DROP INDEX id_rubrique");
		mysql_query("ALTER TABLE spip_rubriques ADD INDEX id_parent (id_parent)");
		mysql_query("ALTER TABLE spip_rubriques ADD statut VARCHAR(10) NOT NULL");
		// Declencher le calcul des rubriques publiques
		mysql_query("REPLACE spip_meta (nom, valeur) VALUES ('calculer_rubriques', 'oui')");
	}

	if ($version_installee < 1.208) {
		mysql_query("ALTER TABLE spip_auteurs_messages CHANGE forum vu CHAR(3) NOT NULL");
		mysql_query("UPDATE spip_auteurs_messages SET vu='oui'");
		mysql_query("UPDATE spip_auteurs_messages SET vu='non' WHERE statut='a'");

		mysql_query("ALTER TABLE spip_messages ADD id_auteur bigint(21) NOT NULL");
		mysql_query("ALTER TABLE spip_messages ADD INDEX id_auteur (id_auteur)");
		$result = mysql_query("SELECT id_auteur, id_message FROM spip_auteurs_messages WHERE statut='de'");
		while ($row = mysql_fetch_array($result)) {
			$id_auteur = $row[0];
			$id_message = $row[1];
			mysql_query("UPDATE spip_messages SET id_auteur=$id_auteur WHERE id_message=$id_message");
		}

		mysql_query("ALTER TABLE spip_auteurs_messages DROP statut");
	}

	if ($version_installee < 1.209) {
		mysql_query("ALTER TABLE spip_syndic ADD maj TIMESTAMP");
		mysql_query("ALTER TABLE spip_syndic_articles ADD maj TIMESTAMP");
		mysql_query("ALTER TABLE spip_messages ADD maj TIMESTAMP");
	}

	if ($version_installee < 1.210) {
		mysql_query("ALTER TABLE spip_messages DROP page");

		stripslashes_base('spip_articles', array('surtitre', 'titre', 'soustitre', 'descriptif', 'chapo', 'texte', 'ps'));
		stripslashes_base('spip_auteurs', array('nom', 'bio', 'nom_site'));
		stripslashes_base('spip_breves', array('titre', 'texte', 'lien_titre'));
		stripslashes_base('spip_forum', array('titre', 'texte', 'auteur', 'nom_site'));
		stripslashes_base('spip_messages', array('titre', 'texte'));
		stripslashes_base('spip_mots', array('type', 'titre', 'descriptif', 'texte'));
		stripslashes_base('spip_petitions', array('texte'));
		stripslashes_base('spip_rubriques', array('titre', 'descriptif', 'texte'));
		stripslashes_base('spip_signatures', array('nom_email', 'nom_site', 'message'));
		stripslashes_base('spip_syndic', array('nom_site', 'descriptif'));
		stripslashes_base('spip_syndic_articles', array('titre', 'lesauteurs'));
	}

	if ($version_installee < 1.3) {
		// Modifier la syndication (pour liste de sites)
		mysql_query("ALTER TABLE spip_syndic ADD syndication VARCHAR(3) NOT NULL");
		mysql_query("ALTER TABLE spip_syndic ADD statut VARCHAR(10) NOT NULL");
		mysql_query("ALTER TABLE spip_syndic ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		mysql_query("UPDATE spip_syndic SET syndication='oui', statut='publie', date=NOW()");

		// Statut pour articles syndication, pour pouvoir desactiver un article
		mysql_query("ALTER TABLE spip_syndic_articles ADD statut VARCHAR(10) NOT NULL");
		mysql_query("UPDATE spip_syndic_articles SET statut='publie'");
	}

	if ($version_installee < 1.301) {
		mysql_query("ALTER TABLE spip_forum ADD id_syndic bigint(21) DEFAULT '0' NOT NULL");
	}

	if ($version_installee < 1.302) {
		mysql_query("ALTER TABLE spip_forum_cache DROP PRIMARY KEY");
		mysql_query("ALTER TABLE spip_forum_cache DROP INDEX fichier");
		mysql_query("ALTER TABLE spip_forum_cache ADD PRIMARY KEY (fichier, id_forum, id_article, id_rubrique, id_breve, id_syndic)");
		mysql_query("ALTER TABLE spip_forum ADD INDEX id_syndic (id_syndic)");
	}

	if ($version_installee < 1.303) {
		mysql_query("ALTER TABLE spip_rubriques ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		mysql_query("ALTER TABLE spip_syndic ADD date_syndic datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		mysql_query("UPDATE spip_syndic SET date_syndic=date");
	}

	if ($version_installee < 1.305) {
	}

	if ($version_installee < 1.306) {
		mysql_query("DROP TABLE spip_index_syndic_articles");
		mysql_query("ALTER TABLE spip_syndic ADD date_index datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		mysql_query("ALTER TABLE spip_syndic ADD INDEX date_index (date_index)");
	}

	if ($version_installee < 1.307) {
		mysql_query("ALTER TABLE spip_syndic_articles ADD descriptif blob NOT NULL");
	}

	if ($version_installee < 1.404) {
		mysql_query("UPDATE spip_mots SET type='Mots sans groupe...' WHERE type=''");

		$result = mysql_query("SELECT * FROM spip_mots GROUP BY type");
		while($row = mysql_fetch_array($result)) {
				$type = addslashes($row['type']);
				mysql_query("INSERT INTO spip_groupes_mots 
					(titre, unseul, obligatoire, articles, breves, rubriques, syndic, 0minirezo, 1comite, 6forum)
					VALUES (\"$type\", 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");
		}
		mysql_query("DELETE FROM spip_mots WHERE titre='kawax'");
	}

	if ($version_installee < 1.405) {
		mysql_query("ALTER TABLE spip_mots ADD id_groupe bigint(21) NOT NULL");
	
		$result = mysql_query("SELECT * FROM spip_groupes_mots");
		while($row = mysql_fetch_array($result)) {
				$id_groupe = addslashes($row['id_groupe']);
				$type = addslashes($row['titre']);
				mysql_query("UPDATE spip_mots SET id_groupe = '$id_groupe' WHERE type=\"$type\"");
		}
	}

	if ($version_installee < 1.408) {
		// Images articles passent dans spip_documents
		$query = "SELECT id_article, images FROM spip_articles WHERE LENGTH(images) > 0";
		$result = mysql_query($query);

		$types = array('jpg' => 1, 'png' => 2, 'gif' => 3);

		while ($row = @mysql_fetch_array($result)) {
			$id_article = $row['id_article'];
			$images = $row['images'];
			$images = explode(",", $images);
			reset($images);
			$replace = 'texte';
			while (list (, $val) = each($images)) {
				$image = explode("|", $val);
				$fichier = $image[0];
				$largeur = $image[1];
				$hauteur = $image[2];
				ereg("-([0-9]+)\.(gif|jpg|png)$", $fichier, $match);
				$id_type = intval($types[$match[2]]);
				$num_img = $match[1];
				$fichier = "IMG/$fichier";
				$taille = @filesize("../$fichier");
				mysql_query("INSERT spip_documents (titre, id_type, fichier, mode, largeur, hauteur, taille) VALUES ".
					"('image $largeur x $hauteur', $id_type, '$fichier', 'vignette', '$largeur', '$hauteur', '$taille')");
				$id_document = mysql_insert_id();
				mysql_query("INSERT spip_documents_articles (id_document, id_article) VALUES ($id_document, $id_article)");
				$replace = "REPLACE($replace, '<IMG$num_img|', '<IM_$id_document|')";
			}
			$query = "UPDATE spip_articles SET texte=REPLACE($replace, '<IM_', '<IMG') WHERE id_article=$id_article";
			mysql_query($query);
		}
		mysql_query("ALTER TABLE spip_articles DROP images");
	}


	//
	// Mettre a jour le numero de version installee
	//
	mysql_query("REPLACE spip_meta (nom, valeur) VALUES ('version_installee', '$spip_version')");
}

?>