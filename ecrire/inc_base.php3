<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_BASE")) return;
define("_ECRIRE_INC_BASE", "1");

include_ecrire ("inc_acces.php3");


function creer_base() {

	global $spip_version;

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
		referers INTEGER DEFAULT '0' NOT NULL,
		popularite DOUBLE DEFAULT '0' NOT NULL,
		accepter_forum CHAR(3) NOT NULL,
		auteur_modif bigint(21) DEFAULT '0' NOT NULL,
		date_modif datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY (id_article),
		KEY id_rubrique (id_rubrique),
		KEY id_secteur (id_secteur),
		KEY statut (statut, date))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_auteurs (
		id_auteur bigint(21) DEFAULT '0' NOT NULL auto_increment,
		nom text NOT NULL,
		bio text NOT NULL,
		email tinytext NOT NULL,
		nom_site tinytext NOT NULL,
		url_site text NOT NULL,
		login VARCHAR(255) BINARY NOT NULL,
		pass tinytext NOT NULL,
		statut VARCHAR(255) NOT NULL,
		maj TIMESTAMP,
		pgp BLOB NOT NULL,
		htpass tinyblob NOT NULL,
		en_ligne datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		imessage VARCHAR(3) NOT NULL,
		messagerie VARCHAR(3) NOT NULL,
		alea_actuel tinytext NOT NULL,
		alea_futur tinytext NOT NULL,
		prefs tinytext NOT NULL,
		cookie_oubli tinytext NOT NULL,
		source VARCHAR(10) DEFAULT 'spip' NOT NULL,
		PRIMARY KEY (id_auteur),
		KEY login (login),
		KEY statut (statut))";
	$result = spip_query($query);

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
	$result = spip_query($query);

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
	$result = spip_query($query);

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
	$result = spip_query($query);

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
	$result = spip_query($query);

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
	$result = spip_query($query);

	$query = "CREATE TABLE spip_documents (
		id_document bigint(21) DEFAULT '0' NOT NULL auto_increment,
		id_vignette bigint(21) DEFAULT '0' NOT NULL,
		id_type bigint(21) DEFAULT '0' NOT NULL,
		titre text NOT NULL,
		date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		descriptif text NOT NULL,
		fichier varchar(255) NOT NULL,
		taille integer NOT NULL,
		largeur integer NOT NULL,
		hauteur integer NOT NULL,
		mode ENUM('vignette', 'document') NOT NULL,
		inclus VARCHAR(3) DEFAULT 'non',
		maj TIMESTAMP,
		PRIMARY KEY (id_document),
		KEY id_vignette (id_vignette),
		KEY mode (mode),
		KEY id_type (id_type))";
	$result = spip_query($query);

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
	$result = spip_query($query);

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
		date_index datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		moderation VARCHAR(3) NOT NULL,
		PRIMARY KEY (id_syndic),
		KEY id_rubrique (id_rubrique),
		KEY id_secteur (id_secteur),
		KEY statut (statut, date_syndic))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_syndic_articles (
		id_syndic_article bigint(21) DEFAULT '0' NOT NULL auto_increment,
		id_syndic bigint(21) DEFAULT '0' NOT NULL,
		titre text NOT NULL,
		url VARCHAR(255) NOT NULL,
		date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		lesauteurs text NOT NULL,
		maj TIMESTAMP,
		statut VARCHAR(10) NOT NULL,
		descriptif blob NOT NULL,
		PRIMARY KEY (id_syndic_article),
		KEY id_syndic (id_syndic),
		KEY statut (statut),
		KEY url (url))";
	$result = spip_query($query);


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
	$result = spip_query($query);

	$query = "CREATE TABLE spip_petitions (
		id_article bigint(21) DEFAULT '0' NOT NULL,
		email_unique char(3) NOT NULL,
		site_obli char(3) NOT NULL,
		site_unique char(3) NOT NULL,
		message char(3) NOT NULL,
		texte longblob NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (id_article))";
	$result = spip_query($query);
	
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
	$result = spip_query($query);

	$query = "CREATE TABLE spip_visites_temp (
		ip INTEGER UNSIGNED NOT NULL,
		type ENUM('article', 'rubrique', 'breve', 'autre') NOT NULL,
		id_objet INTEGER UNSIGNED NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (type, id_objet, ip))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_visites (
		date DATE NOT NULL,
		visites INTEGER UNSIGNED NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (date))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_visites_articles (
		date DATE NOT NULL,
		id_article INTEGER UNSIGNED NOT NULL,
		visites INTEGER UNSIGNED NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (date, id_article))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_referers_temp (
		ip INTEGER UNSIGNED NOT NULL,
		referer VARCHAR(255) NOT NULL,
		referer_md5 BIGINT UNSIGNED NOT NULL,
		type ENUM('article', 'rubrique', 'breve', 'autre') NOT NULL,
		id_objet INTEGER UNSIGNED NOT NULL,
		maj TIMESTAMP,
		PRIMARY KEY (type, id_objet, referer_md5, ip))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_referers (
		referer_md5 BIGINT UNSIGNED NOT NULL,
		date DATE NOT NULL,
		referer VARCHAR(255) NOT NULL,
		visites INTEGER UNSIGNED NOT NULL,
		maj TIMESTAMP, 
		PRIMARY KEY (referer_md5))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_referers_articles (
		id_article INTEGER UNSIGNED NOT NULL,
		referer_md5 BIGINT UNSIGNED NOT NULL,
		date DATE NOT NULL,
		referer VARCHAR(255) NOT NULL,
		visites INTEGER UNSIGNED NOT NULL,
		maj TIMESTAMP, 
		PRIMARY KEY (id_article, referer_md5),
		KEY referer_md5 (referer_md5))";
	$result = spip_query($query);


	//
	// Relations
	//

	$query = "CREATE TABLE spip_auteurs_articles (
		id_auteur bigint(21) DEFAULT '0' NOT NULL, id_article bigint(21) DEFAULT '0' NOT NULL,
		KEY id_auteur (id_auteur), KEY id_article (id_article))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_auteurs_rubriques (
		id_auteur bigint(21) DEFAULT '0' NOT NULL, id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		KEY id_auteur (id_auteur), KEY id_rubrique (id_rubrique))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_auteurs_messages (
		id_auteur bigint(21) DEFAULT '0' NOT NULL, id_message bigint(21) DEFAULT '0' NOT NULL, vu CHAR(3) NOT NULL,
		KEY id_auteur (id_auteur), KEY id_message (id_message))";
	$result = spip_query($query);


	$query = "CREATE TABLE spip_documents_articles (
		id_document bigint(21) DEFAULT '0' NOT NULL, id_article bigint(21) DEFAULT '0' NOT NULL,
		KEY id_document (id_document), KEY id_article (id_article))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_documents_rubriques (
		id_document bigint(21) DEFAULT '0' NOT NULL, id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		KEY id_document (id_document), KEY id_rubrique (id_rubrique))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_documents_breves (
		id_document bigint(21) DEFAULT '0' NOT NULL, id_breve bigint(21) DEFAULT '0' NOT NULL,
		KEY id_document (id_document), KEY id_breve (id_breve))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_mots_articles (
		id_mot bigint(21) DEFAULT '0' NOT NULL, id_article bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot), KEY id_article (id_article))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_mots_breves (
		id_mot bigint(21) DEFAULT '0' NOT NULL, id_breve bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot), KEY id_breve (id_breve))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_mots_rubriques (
		id_mot bigint(21) DEFAULT '0' NOT NULL, id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot), KEY id_rubrique (id_rubrique))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_mots_syndic (
		id_mot bigint(21) DEFAULT '0' NOT NULL, id_syndic bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot), KEY id_syndic (id_syndic))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_mots_forum (
		id_mot bigint(21) DEFAULT '0' NOT NULL, id_forum bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot), KEY id_forum (id_forum))";
	$result = spip_query($query);


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
	$result = spip_query($query);

	$query = "CREATE TABLE spip_meta (
		nom VARCHAR(255) NOT NULL, valeur VARCHAR(255) DEFAULT '', maj TIMESTAMP,
		PRIMARY KEY (nom))";
	$result = spip_query($query);


	//
	// Indexation (moteur de recherche)
	//

	$query = "CREATE TABLE spip_index_articles (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_article int unsigned NOT NULL,
		KEY hash (hash), KEY id_article (id_article))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_index_auteurs (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_auteur int unsigned NOT NULL,
		KEY hash (hash), KEY id_auteur (id_auteur))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_index_breves (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_breve int unsigned NOT NULL,
		KEY hash (hash), KEY id_breve (id_breve))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_index_mots (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_mot int unsigned NOT NULL,
		KEY hash (hash), KEY id_mot (id_mot))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_index_rubriques (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_rubrique int unsigned NOT NULL,
		KEY hash (hash), KEY id_rubrique (id_rubrique))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_index_syndic (
		hash bigint unsigned NOT NULL, points int unsigned DEFAULT '0' NOT NULL, id_syndic int unsigned NOT NULL,
		KEY hash (hash), KEY id_syndic (id_syndic))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_index_dico (
		hash bigint unsigned NOT NULL, dico VARCHAR(30) NOT NULL,
		PRIMARY KEY (dico))";
	$result = spip_query($query);


	//
	// Pre-remplissage de la base
	//
	remplir_type_documents();

	//
	// reglages par defaut
	//

	include_ecrire("inc_meta.php3");

	if (! lire_meta('post_dates'))
		ecrire_meta('post_dates', 'non');	// ne pas publier les post-dates

	ecrire_metas();
}


function remplir_type_documents() {
	$query = "INSERT IGNORE spip_types_documents (id_type, extension, titre, inclus) VALUES ".
		"(1, 'jpg', 'JPEG', 'image'), ".
		"(2, 'png', 'PNG', 'image'), ".
		"(3, 'gif', 'GIF', 'image')";
	spip_query($query);

	$query = "INSERT IGNORE spip_types_documents (extension, titre, inclus) VALUES ".
		"('bmp', 'BMP', 'image'), ".
		"('psd', 'Photoshop', 'image'), ".
		"('tif', 'TIFF', 'image')";
	spip_query($query);
	
	$query = "INSERT IGNORE spip_types_documents (extension, titre, inclus) VALUES ".
		"('aiff', 'AIFF', 'embed'), ".
		"('asf', 'Windows Media', 'embed'), ".
		"('avi', 'Windows Media', 'embed'), ".
		"('bz2', 'BZip', 'non'), ".
		"('doc', 'Word', 'non'), ".
		"('eps', 'PostScript', 'non'), ".
		"('gz', 'GZ', 'non'), ".
		"('html', 'HTML', 'non'), ".
		"('mid', 'Midi', 'embed'), ".
		"('mov', 'QuickTime', 'embed'), ".
		"('mp3', 'MP3', 'embed'), ".
		"('mpg', 'MPEG', 'embed'), ".
		"('ogg', 'Ogg Vorbis', 'embed'), ".
		"('pdf', 'PDF', 'non'), ".
		"('ppt', 'PowerPoint', 'non'), ".
		"('ps', 'PostScript', 'non'), ".
		"('qt', 'QuickTime', 'embed'), ".
		"('ra', 'RealAudio', 'embed'), ".
		"('ram', 'RealAudio', 'embed'), ".
		"('rm', 'RealAudio', 'embed'), ".
		"('rtf', 'RTF', 'non'), ".
		"('sit', 'Stuffit', 'non'), ".
		"('swf', 'Flash', 'embed'), ".
		"('tgz', 'TGZ', 'non'), ".
		"('txt', 'texte', 'non'), ".
		"('wav', 'WAV', 'embed'), ".
		"('xls', 'Excel', 'non'), ".
		"('xml', 'XML', 'non'), ".
		"('wmv', 'Windows Media', 'embed'), ".
		"('zip', 'Zip', 'non')";
	spip_query($query);
}

function stripslashes_base($table, $champs) {
	$modifs = '';
	reset($champs);
	while (list(, $champ) = each($champs)) {
		$modifs[] = $champ . '=REPLACE(REPLACE(' .$champ. ',"\\\\\'", "\'"), \'\\\\"\', \'"\')';
	}
	$query = "UPDATE $table SET ".join(',', $modifs);
	spip_query($query);
}


function maj_base() {

	global $spip_version;

	//
	// Lecture de la version installee
	//
	$version_installee = 0.0;
	$result = spip_query("SELECT valeur FROM spip_meta WHERE nom='version_installee'");
	if ($result) if ($row = mysql_fetch_array($result)) $version_installee = (double) $row['valeur'];

	//
	// Si pas de version mentionnee dans spip_meta, c'est qu'il s'agit d'une nouvelle installation
	//   => ne pas passer par le processus de mise a jour
	//
	if (!$version_installee) $version_installee = $spip_version;
	
	//
	// Selection en fonction de la version
	//
	if ($version_installee < 0.98) {
	
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
	}
	
	if ($version_installee < 0.99) {
	
		$query = "SELECT DISTINCT id_article FROM spip_forum WHERE id_article!=0 AND id_parent=0";
		$result = spip_query($query);
		while ($row = mysql_fetch_array($result)) {
			unset($forums_article);
			$id_article = $row['id_article'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_article=$id_article";
			for (;;) {
				$result2 = spip_query($query2);
				unset($forums);
				while ($row2 = mysql_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_article[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_article = join(',', $forums_article);
			$query3 = "UPDATE spip_forum SET id_article=$id_article WHERE id_forum IN ($forums_article)";
			spip_query($query3);
		}
	
		$query = "SELECT DISTINCT id_breve FROM spip_forum WHERE id_breve!=0 AND id_parent=0";
		$result = spip_query($query);
		while ($row = mysql_fetch_array($result)) {
			unset($forums_breve);
			$id_breve = $row['id_breve'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_breve=$id_breve";
			for (;;) {
				$result2 = spip_query($query2);
				unset($forums);
				while ($row2 = mysql_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_breve[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_breve = join(',', $forums_breve);
			$query3 = "UPDATE spip_forum SET id_breve=$id_breve WHERE id_forum IN ($forums_breve)";
			spip_query($query3);
		}
	
		$query = "SELECT DISTINCT id_rubrique FROM spip_forum WHERE id_rubrique!=0 AND id_parent=0";
		$result = spip_query($query);
		while ($row = mysql_fetch_array($result)) {
			unset($forums_rubrique);
			$id_rubrique = $row['id_rubrique'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_rubrique=$id_rubrique";
			for (;;) {
				$result2 = spip_query($query2);
				unset($forums);
				while ($row2 = mysql_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_rubrique[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_rubrique = join(',', $forums_rubrique);
			$query3 = "UPDATE spip_forum SET id_rubrique=$id_rubrique WHERE id_forum IN ($forums_rubrique)";
			spip_query($query3);
		}
	}

	if ($version_installee < 0.997) {
		spip_query("DROP TABLE spip_index");
	}

	if ($version_installee < 0.999) {
		global $htsalt;
		spip_query("ALTER TABLE spip_auteurs CHANGE pass pass tinyblob NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD htpass tinyblob NOT NULL");
		$query = "SELECT id_auteur, pass FROM spip_auteurs WHERE pass!=''";
		$result = spip_query($query);
		while (list($id_auteur, $pass) = mysql_fetch_array($result)) {
			$htpass = generer_htpass($pass);
			$pass = md5($pass);
			spip_query("UPDATE spip_auteurs SET pass='$pass', htpass='$htpass' WHERE id_auteur=$id_auteur");
		}
	}
	
	if ($version_installee < 1.01) {
		spip_query("UPDATE spip_forum SET statut='publie' WHERE statut=''");
	}
	
	if ($version_installee < 1.02) {
		spip_query("ALTER TABLE spip_forum ADD id_auteur BIGINT DEFAULT '0' NOT NULL");
	}

	if ($version_installee < 1.03) {
		spip_query("DROP TABLE spip_maj");
	}

	if ($version_installee < 1.04) {
		spip_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3)");
	}

	if ($version_installee < 1.05) {
		spip_query("DROP TABLE spip_petition");
		spip_query("DROP TABLE spip_signatures_petition");
	}

	if ($version_installee < 1.1) {
		spip_query("DROP TABLE spip_petition");
		spip_query("DROP TABLE spip_signatures_petition");
	}

	// Correction de l'oubli des modifs creations depuis 1.04
	if ($version_installee < 1.204) {
		spip_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3) NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD id_message bigint(21) NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_message (id_message)");
		spip_query("ALTER TABLE spip_auteurs ADD en_ligne datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD imessage VARCHAR(3) not null");
		spip_query("ALTER TABLE spip_auteurs ADD messagerie VARCHAR(3) not null");
	}

	if ($version_installee < 1.207) {
		spip_query("ALTER TABLE spip_rubriques DROP INDEX id_rubrique");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX id_parent (id_parent)");
		spip_query("ALTER TABLE spip_rubriques ADD statut VARCHAR(10) NOT NULL");
		// Declencher le calcul des rubriques publiques
		spip_query("REPLACE spip_meta (nom, valeur) VALUES ('calculer_rubriques', 'oui')");
	}

	if ($version_installee < 1.208) {
		spip_query("ALTER TABLE spip_auteurs_messages CHANGE forum vu CHAR(3) NOT NULL");
		spip_query("UPDATE spip_auteurs_messages SET vu='oui'");
		spip_query("UPDATE spip_auteurs_messages SET vu='non' WHERE statut='a'");

		spip_query("ALTER TABLE spip_messages ADD id_auteur bigint(21) NOT NULL");
		spip_query("ALTER TABLE spip_messages ADD INDEX id_auteur (id_auteur)");
		$result = spip_query("SELECT id_auteur, id_message FROM spip_auteurs_messages WHERE statut='de'");
		while ($row = mysql_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$id_message = $row['id_message'];
			spip_query("UPDATE spip_messages SET id_auteur=$id_auteur WHERE id_message=$id_message");
		}

		spip_query("ALTER TABLE spip_auteurs_messages DROP statut");
	}

	if ($version_installee < 1.209) {
		spip_query("ALTER TABLE spip_syndic ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_syndic_articles ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_messages ADD maj TIMESTAMP");
	}

	if ($version_installee < 1.210) {
		spip_query("ALTER TABLE spip_messages DROP page");

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
		spip_query("ALTER TABLE spip_syndic ADD syndication VARCHAR(3) NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD statut VARCHAR(10) NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("UPDATE spip_syndic SET syndication='oui', statut='publie', date=NOW()");

		// Statut pour articles syndication, pour pouvoir desactiver un article
		spip_query("ALTER TABLE spip_syndic_articles ADD statut VARCHAR(10) NOT NULL");
		spip_query("UPDATE spip_syndic_articles SET statut='publie'");
	}

	if ($version_installee < 1.301) {
		spip_query("ALTER TABLE spip_forum ADD id_syndic bigint(21) DEFAULT '0' NOT NULL");
	}

	if ($version_installee < 1.302) {
		spip_query("ALTER TABLE spip_forum_cache DROP PRIMARY KEY");
		spip_query("ALTER TABLE spip_forum_cache DROP INDEX fichier");
		spip_query("ALTER TABLE spip_forum_cache ADD PRIMARY KEY (fichier, id_forum, id_article, id_rubrique, id_breve, id_syndic)");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_syndic (id_syndic)");
	}

	if ($version_installee < 1.303) {
		spip_query("ALTER TABLE spip_rubriques ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD date_syndic datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("UPDATE spip_syndic SET date_syndic=date");
	}

	if ($version_installee < 1.305) {
	}

	if ($version_installee < 1.306) {
		spip_query("DROP TABLE spip_index_syndic_articles");
		spip_query("ALTER TABLE spip_syndic ADD date_index datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD INDEX date_index (date_index)");
	}

	if ($version_installee < 1.307) {
		spip_query("ALTER TABLE spip_syndic_articles ADD descriptif blob NOT NULL");
	}

	if ($version_installee < 1.404) {
		spip_query("UPDATE spip_mots SET type='Mots sans groupe...' WHERE type=''");

		$result = spip_query("SELECT * FROM spip_mots GROUP BY type");
		while($row = mysql_fetch_array($result)) {
				$type = addslashes($row['type']);
				spip_query("INSERT INTO spip_groupes_mots 
					(titre, unseul, obligatoire, articles, breves, rubriques, syndic, 0minirezo, 1comite, 6forum)
					VALUES (\"$type\", 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");
		}
		spip_query("DELETE FROM spip_mots WHERE titre='kawax'");
	}

	if ($version_installee < 1.405) {
		spip_query("ALTER TABLE spip_mots ADD id_groupe bigint(21) NOT NULL");
	
		$result = spip_query("SELECT * FROM spip_groupes_mots");
		while($row = mysql_fetch_array($result)) {
				$id_groupe = addslashes($row['id_groupe']);
				$type = addslashes($row['titre']);
				spip_query("UPDATE spip_mots SET id_groupe = '$id_groupe' WHERE type=\"$type\"");
		}
	}

	if ($version_installee < 1.408) {
		// Images articles passent dans spip_documents
		$query = "SELECT id_article, images FROM spip_articles WHERE LENGTH(images) > 0";
		$result = spip_query($query);

		$types = array('jpg' => 1, 'png' => 2, 'gif' => 3);

		while ($row = @mysql_fetch_array($result)) {
			$id_article = $row['id_article'];
			$images = $row['images'];
			$images = explode(",", $images);
			reset($images);
			$replace = '_orig_';
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
				spip_query("INSERT INTO spip_documents (titre, id_type, fichier, mode, largeur, hauteur, taille) VALUES ".
					"('image $largeur x $hauteur', $id_type, '$fichier', 'vignette', '$largeur', '$hauteur', '$taille')");
				$id_document = mysql_insert_id();
				if ($id_document > 0) {
					spip_query("INSERT INTO spip_documents_articles (id_document, id_article) VALUES ($id_document, $id_article)");
					$replace = "REPLACE($replace, '<IMG$num_img|', '<IM_$id_document|')";
				} else {
					echo propre("Erreur de base de donn&eacute;es lors de la mise &agrave; niveau.
						L'image {{$fichier}} n'est pas pass&eacute;e (article $id_article).\n\n
						Notez bien cette r&eacute;f&eacute;rence, r&eacute;essayez la mise &agrave;
						niveau, et enfin v&eacute;rifiez que les images apparaissent
						toujours dans les articles.");
					exit;
				}
			}
			$replace = "REPLACE($replace, '<IM_', '<IMG')";
			$replace_chapo = ereg_replace('_orig_', 'chapo', $replace);
			$replace_descriptif = ereg_replace('_orig_', 'descriptif', $replace);
			$replace_texte = ereg_replace('_orig_', 'texte', $replace);
			$replace_ps = ereg_replace('_orig_', 'ps', $replace);
			$query = "UPDATE spip_articles ".
				"SET chapo=$replace_chapo, descriptif=$replace_descriptif, texte=$replace_texte, ps=$replace_ps ".
				"WHERE id_article=$id_article";
			spip_query($query);
		}
		spip_query("ALTER TABLE spip_articles DROP images");
	}

	if ($version_installee < 1.414) {
		// Forum par defaut "en dur" dans les spip_articles
		// -> non, prio (priori), pos (posteriori), abo (abonnement)
		include_ecrire ("inc_meta.php3");
		$accepter_forum = substr(lire_meta("forums_publics"),0,3) ;
		$query = "ALTER TABLE spip_articles CHANGE accepter_forum accepter_forum CHAR(3) NOT NULL";
		$result = spip_query($query);
		$query = "UPDATE spip_articles SET accepter_forum='$accepter_forum' WHERE accepter_forum != 'non'";
		$result = spip_query($query);
	}

	if ($version_installee == 1.415) {
		spip_query("ALTER TABLE spip_documents DROP inclus");
	}

	if ($version_installee < 1.417) {
		spip_query("ALTER TABLE spip_syndic_articles DROP date_index");
	}

	if ($version_installee < 1.418) {
		$query = "SELECT * FROM spip_auteurs WHERE statut = '0minirezo' AND email != '' ORDER BY id_auteur LIMIT 0,1";
		$result = spip_query($query);
		if ($webmaster = mysql_fetch_object($result)) {
			include_ecrire("inc_meta.php3");
			ecrire_meta('email_webmaster', $webmaster->email);
			ecrire_metas();
		}
	}

	if ($version_installee < 1.419) {
		$query = "ALTER TABLE spip_auteurs ADD alea_actuel TINYTEXT DEFAULT ''";
		spip_query($query);
		$query = "ALTER TABLE spip_auteurs ADD alea_futur TINYTEXT DEFAULT ''";
		spip_query($query);
		$query = "UPDATE spip_auteurs SET alea_futur = FLOOR(32000*RAND())";
		spip_query($query);
	}

	if ($version_installee < 1.420) {
		$query = "UPDATE spip_auteurs SET alea_actuel='' WHERE statut='nouveau'";
		spip_query($query);
	}
	
	if ($version_installee < 1.421) {
		$query = "ALTER TABLE spip_articles ADD auteur_modif bigint(21) DEFAULT '0' NOT NULL";
		spip_query($query);
		$query = "ALTER TABLE spip_articles ADD date_modif datetime DEFAULT '0000-00-00 00:00:00' NOT NULL";
		spip_query($query);
	}

	if ($version_installee < 1.432) {
		spip_query("ALTER TABLE spip_articles DROP referers");
		$query = "ALTER TABLE spip_articles ADD referers INTEGER DEFAULT '0' NOT NULL";
		spip_query($query);
		$query = "ALTER TABLE spip_articles ADD popularite INTEGER DEFAULT '0' NOT NULL";
		spip_query($query);
	}

	if ($version_installee < 1.436) {
		$query = "ALTER TABLE spip_documents ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL";
		spip_query($query);
	}

	if ($version_installee < 1.437) {
		spip_query("ALTER TABLE spip_visites ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_visites_referers ADD maj TIMESTAMP");
	}

	if ($version_installee < 1.438) {
		spip_query("ALTER TABLE spip_articles ADD INDEX id_secteur (id_secteur)");
		spip_query("ALTER TABLE spip_articles ADD INDEX statut (statut, date)");
	}

	if ($version_installee < 1.439) {
		spip_query("ALTER TABLE spip_syndic ADD INDEX statut (statut, date_syndic)");
		spip_query("ALTER TABLE spip_syndic_articles ADD INDEX statut (statut)");
		spip_query("ALTER TABLE spip_syndic_articles CHANGE url url VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles ADD INDEX url (url)");
	}

	if ($version_installee < 1.440) {
		spip_query("ALTER TABLE spip_visites_temp CHANGE ip ip INTEGER UNSIGNED NOT NULL");
	}

	if ($version_installee < 1.441) {
		spip_query("ALTER TABLE spip_visites_temp CHANGE date date DATE NOT NULL");
		spip_query("ALTER TABLE spip_visites CHANGE date date DATE NOT NULL");
		spip_query("ALTER TABLE spip_visites_referers CHANGE date date DATE NOT NULL");
	}

	if ($version_installee < 1.442) {
		$query = "ALTER TABLE spip_auteurs ADD prefs TINYTEXT NOT NULL";
		spip_query($query);
	}

	if ($version_installee < 1.443) {
		spip_query("ALTER TABLE spip_auteurs CHANGE login login VARCHAR(255) BINARY NOT NULL");
		spip_query("ALTER TABLE spip_auteurs CHANGE statut statut VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX login (login)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX statut (statut)");
	}

	if ($version_installee < 1.444) {
		spip_query("ALTER TABLE spip_syndic ADD moderation VARCHAR(3) NOT NULL");
	}

	if ($version_installee < 1.457) {
		spip_query("DROP TABLE spip_visites");
		spip_query("DROP TABLE spip_visites_temp");
		spip_query("DROP TABLE spip_visites_referers");
		creer_base(); // crade, a ameliorer :-((
	}

	if ($version_installee < 1.458) {
		spip_query("ALTER TABLE spip_auteurs ADD cookie_oubli TINYTEXT NOT NULL");
	}

	if ($version_installee < 1.459) {
		$result = spip_query("SELECT type FROM spip_mots GROUP BY type");
		while ($row = mysql_fetch_array($result)) {
			$type = addslashes($row['type']);
			$res = spip_query("SELECT * FROM spip_groupes_mots
				WHERE titre='$type'");
			if (mysql_num_rows($res) == 0) {
				spip_query("INSERT IGNORE INTO spip_groupes_mots 
					(titre, unseul, obligatoire, articles, breves, rubriques, syndic, 0minirezo, 1comite, 6forum)
					VALUES ('$type', 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");
				if ($id_groupe = mysql_insert_id()) 
					spip_query("UPDATE spip_mots SET id_groupe = '$id_groupe' WHERE type='$type'");
			}
		}
		spip_query("UPDATE spip_articles SET popularite=0");
	}

	if ($version_installee < 1.460) {
		// remettre les mots dans les groupes dupliques par erreur
		// dans la precedente version du paragraphe de maj 1.459
		// et supprimer ceux-ci
		$result = spip_query("SELECT * FROM spip_groupes_mots ORDER BY id_groupe");
		while ($row = mysql_fetch_array($result)) {
			$titre = addslashes($row['titre']);
			if (! $vu[$titre] ) {
				$vu[$titre] = true;
				$id_groupe = $row['id_groupe'];
				spip_query ("UPDATE spip_mots SET id_groupe=$id_groupe WHERE type='$titre'");
				spip_query ("DELETE FROM spip_groupes_mots WHERE titre='$titre' AND id_groupe<>$id_groupe");
			}
		}
	}

	if ($version_installee < 1.462) {
		spip_query("UPDATE spip_types_documents SET inclus='embed' WHERE inclus!='non' AND extension IN ".
			"('aiff', 'asf', 'avi', 'mid', 'mov', 'mp3', 'mpg', 'ogg', 'qt', 'ra', 'ram', 'rm', 'swf', 'wav', 'wmv')");
	}

	if ($version_installee < 1.463) {
		spip_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE");
		spip_query("ALTER TABLE spip_visites_temp ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_referers_temp ADD maj TIMESTAMP");
	}

	// l'upgrade < 1.462 ci-dessus etait fausse, d'ou correctif
	if (($version_installee < 1.464) AND ($version_installee >= 1.462)) {
		$res = spip_query("SELECT id_type, extension FROM spip_types_documents WHERE id_type NOT IN (1,2,3)");
		while ($row = mysql_fetch_array($res)) {
			$extension = $row['extension'];
			$id_type = $row['id_type'];
			spip_query("UPDATE spip_documents SET id_type=$id_type
				WHERE fichier like '%.$extension'");
		}
	}

	if ($version_installee < 1.465) {
		spip_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE NOT NULL");
	}

	if ($version_installee < 1.466) {
		spip_query("ALTER TABLE spip_auteurs ADD source VARCHAR(10) DEFAULT 'spip' NOT NULL");
	}

	//
	// Mettre a jour le numero de version installee
	//
	spip_query("REPLACE spip_meta (nom, valeur) VALUES ('version_installee', '$spip_version')");
}

?>