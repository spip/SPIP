<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;


$spip_articles = array(
		"id_article"	=> "bigint(21) NOT NULL",
		"surtitre"	=> "text NOT NULL",
		"titre"	=> "text NOT NULL",
		"soustitre"	=> "text NOT NULL",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"descriptif"	=> "text NOT NULL",
		"chapo"	=> "mediumtext NOT NULL",
		"texte"	=> "longblob NOT NULL",
		"ps"	=> "mediumtext NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
		"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"maj"	=> "TIMESTAMP",
		"export"	=> "VARCHAR(10) DEFAULT 'oui'",
		"date_redac"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"visites"	=> "INTEGER DEFAULT '0' NOT NULL",
		"referers"	=> "INTEGER DEFAULT '0' NOT NULL",
		"popularite"	=> "DOUBLE DEFAULT '0' NOT NULL",
		"accepter_forum"	=> "CHAR(3) NOT NULL",
		"date_modif"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"lang"		=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"langue_choisie"	=> "VARCHAR(3) DEFAULT 'non'",
		"id_trad"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"extra"		=> "longblob NULL",
		"idx"		=> "ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL",
		"id_version"	=> "int unsigned DEFAULT '0' NOT NULL",
		"nom_site"	=> "tinytext NOT NULL",
		"url_site"	=> "VARCHAR(255) NOT NULL",
		"url_propre" => "VARCHAR(255) NOT NULL");

$spip_articles_key = array(
		"PRIMARY KEY"		=> "id_article",
		"KEY id_rubrique"	=> "id_rubrique",
		"KEY id_secteur"	=> "id_secteur",
		"KEY id_trad"		=> "id_trad",
		"KEY lang"			=> "lang",
		"KEY statut"		=> "statut, date",
		"KEY url_site"		=> "url_site",
		"KEY date_modif"	=> "date_modif",
		"KEY idx"			=> "idx",
		"KEY url_propre"	=> "url_propre");

$spip_auteurs = array(
		"id_auteur"	=> "bigint(21) NOT NULL",
		"nom"	=> "text NOT NULL",
		"bio"	=> "text NOT NULL",
		"email"	=> "tinytext NOT NULL",
		"nom_site"	=> "tinytext NOT NULL",
		"url_site"	=> "text NOT NULL",
		"login"	=> "VARCHAR(255) BINARY NOT NULL",
		"pass"	=> "tinytext NOT NULL",
		"low_sec"	=> "tinytext NOT NULL",
		"statut"	=> "VARCHAR(255) NOT NULL",
		"maj"	=> "TIMESTAMP",
		"pgp"	=> "BLOB NOT NULL",
		"htpass"	=> "tinyblob NOT NULL",
		"en_ligne"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"imessage"	=> "VARCHAR(3) NOT NULL",
		"messagerie"	=> "VARCHAR(3) NOT NULL",
		"alea_actuel"	=> "tinytext NOT NULL",
		"alea_futur"	=> "tinytext NOT NULL",
		"prefs"	=> "tinytext NOT NULL",
		"cookie_oubli"	=> "tinytext NOT NULL",
		"source"	=> "VARCHAR(10) DEFAULT 'spip' NOT NULL",
		"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"idx"		=> "ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL",
		"url_propre" => "VARCHAR(255) NOT NULL",
		"extra"	=> "longblob NULL");

$spip_auteurs_key = array(
		"PRIMARY KEY"	=> "id_auteur",
		"KEY login"	=> "login",
		"KEY statut"	=> "statut",
		"KEY lang"	=> "lang",
		"KEY idx"	=> "idx",
		"KEY en_ligne"	=> "en_ligne",
		"KEY url_propre"	=> "url_propre");

$spip_breves = array(
		"id_breve"	=> "bigint(21) NOT NULL",
		"date_heure"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"titre"	=> "text NOT NULL",
		"texte"	=> "longblob NOT NULL",
		"lien_titre"	=> "text NOT NULL",
		"lien_url"	=> "text NOT NULL",
		"statut"	=> "varchar(6) NOT NULL",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"langue_choisie"	=> "VARCHAR(3) DEFAULT 'non'",
		"maj"	=> "TIMESTAMP",
		"idx"		=> "ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL",
		"extra"	=> "longblob NULL",
		"url_propre" => "VARCHAR(255) NOT NULL");

$spip_breves_key = array(
		"PRIMARY KEY"	=> "id_breve",
		"KEY idx"	=> "idx",
		"KEY id_rubrique"	=> "id_rubrique",
		"KEY url_propre"	=> "url_propre");

$spip_messages = array(
		"id_message"	=> "bigint(21) NOT NULL",
		"titre"	=> "text NOT NULL",
		"texte"	=> "longblob NOT NULL",
		"type"	=> "varchar(6) NOT NULL",
		"date_heure"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_fin"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"rv"	=> "varchar(3) NOT NULL",
		"statut"	=> "varchar(6) NOT NULL",
		"id_auteur"	=> "bigint(21) NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_messages_key = array(
		"PRIMARY KEY"	=> "id_message",
		"KEY id_auteur"	=> "id_auteur");

$spip_mots = array(
		"id_mot"	=> "bigint(21) NOT NULL",
		"titre"	=> "text NOT NULL",
		"descriptif"	=> "text NOT NULL",
		"texte"	=> "longblob NOT NULL",
		"id_groupe"	=> "bigint(21) NOT NULL",
		"type"	=> "text NOT NULL",
		"extra"	=> "longblob NULL",
		"idx"		=> "ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL",
		"url_propre" => "VARCHAR(255) NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_mots_key = array(
		"PRIMARY KEY"	=> "id_mot",
		"KEY idx"	=> "idx",
		"KEY url_propre"	=> "url_propre");

$spip_groupes_mots = array(
		"id_groupe"	=> "bigint(21) NOT NULL",
		"titre"	=> "text NOT NULL",
		"descriptif"	=> "text NOT NULL",
		"texte"	=> "longblob NOT NULL",
		"unseul"	=> "varchar(3) NOT NULL",
		"obligatoire"	=> "varchar(3) NOT NULL",
		"articles"	=> "varchar(3) NOT NULL",
		"breves"	=> "varchar(3) NOT NULL",
		"rubriques"	=> "varchar(3) NOT NULL",
		"syndic"	=> "varchar(3) NOT NULL",
		"minirezo"	=> "varchar(3) NOT NULL",
		"comite"	=> "varchar(3) NOT NULL",
		"forum"	=> "varchar(3) NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_groupes_mots_key = array(
		"PRIMARY KEY"	=> "id_groupe");

$spip_rubriques = array(
		"id_rubrique"	=> "bigint(21) NOT NULL",
		"id_parent"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"titre"	=> "text NOT NULL",
		"descriptif"	=> "text NOT NULL",
		"texte"	=> "longblob NOT NULL",
		"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"maj"	=> "TIMESTAMP",
		"export"	=> "VARCHAR(10) DEFAULT 'oui'",
		"id_import"	=> "BIGINT DEFAULT '0'",
		"statut"	=> "VARCHAR(10) NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"langue_choisie"	=> "VARCHAR(3) DEFAULT 'non'",
		"idx"		=> "ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL",
		"extra"	=> "longblob NULL",
		"url_propre" => "VARCHAR(255) NOT NULL",
		"statut_tmp"	=> "VARCHAR(10) NOT NULL",
		"date_tmp"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL"
		);

$spip_rubriques_key = array(
		"PRIMARY KEY"	=> "id_rubrique",
		"KEY lang"	=> "lang",
		"KEY idx"	=> "idx",
		"KEY id_parent"	=> "id_parent",
		"KEY url_propre"	=> "url_propre");

$spip_documents = array(
		"id_document"	=> "bigint(21) NOT NULL",
		"id_vignette"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_type"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"titre"	=> "text NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"descriptif"	=> "text NOT NULL",
		"fichier"	=> "varchar(255) NOT NULL",
		"taille"	=> "integer NOT NULL",
		"largeur"	=> "integer NOT NULL",
		"hauteur"	=> "integer NOT NULL",
		"mode"	=> "ENUM('vignette', 'document') NOT NULL",
		"inclus"	=> "VARCHAR(3) DEFAULT 'non'",
		"distant"	=> "VARCHAR(3) DEFAULT 'non'",
		"idx"		=> "ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_documents_key = array(
		"PRIMARY KEY"	=> "id_document",
		"KEY id_vignette"	=> "id_vignette",
		"KEY mode"	=> "mode",
		"KEY id_type"	=> "id_type");

$spip_types_documents = array(
		"id_type"	=> "bigint(21) NOT NULL",
		"titre"	=> "text NOT NULL",
		"descriptif"	=> "text NOT NULL",
		"extension"	=> "varchar(10) NOT NULL",
		"mime_type"	=> "varchar(100) NOT NULL",
		"inclus"	=> "ENUM('non', 'image', 'embed') NOT NULL DEFAULT 'non'",
		"upload"	=> "ENUM('oui', 'non') NOT NULL DEFAULT 'oui'",
		"maj"	=> "TIMESTAMP");

$spip_types_documents_key = array(
		"PRIMARY KEY"	=> "id_type",
		"UNIQUE extension"	=> "extension",
		"KEY inclus"	=> "inclus");

$spip_syndic = array(
		"id_syndic"	=> "bigint(21) NOT NULL",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"nom_site"	=> "blob NOT NULL",
		"url_site"	=> "blob NOT NULL",
		"url_syndic"	=> "blob NOT NULL",
		"descriptif"	=> "blob NOT NULL",
		"url_propre"	=> "VARCHAR(255) NOT NULL",
		"idx"		=> "ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL",
		"maj"	=> "TIMESTAMP",
		"syndication"	=> "VARCHAR(3) NOT NULL",
		"statut"	=> "VARCHAR(10) NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_syndic"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_index"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"extra"			=> "longblob NULL",
		"moderation"	=> "VARCHAR(3) DEFAULT 'non'",
		"miroir"	=> "VARCHAR(3) DEFAULT 'non'",
		"oubli"	=> "VARCHAR(3) DEFAULT 'non'",
		"resume"	=> "VARCHAR(3) DEFAULT 'oui'"
);

$spip_syndic_key = array(
		"PRIMARY KEY"	=> "id_syndic",
		"KEY id_rubrique"	=> "id_rubrique",
		"KEY id_secteur"	=> "id_secteur",
		"KEY idx"		=> "idx",
		"KEY statut"	=> "statut, date_syndic",
		"KEY url_propre"	=> "url_propre");

$spip_syndic_articles = array(
		"id_syndic_article"	=> "bigint(21) NOT NULL",
		"id_syndic"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"titre"	=> "text NOT NULL",
		"url"	=> "VARCHAR(255) NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"lesauteurs"	=> "text NOT NULL",
		"maj"	=> "TIMESTAMP",
		"statut"	=> "VARCHAR(10) NOT NULL",
		"descriptif"	=> "blob NOT NULL",
		"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"url_source" => "TINYTEXT DEFAULT '' NOT NULL",
		"source" => "TINYTEXT DEFAULT '' NOT NULL",
		"tags" => "TEXT DEFAULT '' NOT NULL");

$spip_syndic_articles_key = array(
		"PRIMARY KEY"	=> "id_syndic_article",
		"KEY id_syndic"	=> "id_syndic",
		"KEY statut"	=> "statut",
		"KEY url"	=> "url");

$spip_forum = array(
		"id_forum"	=> "bigint(21) NOT NULL",
		"id_parent"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_thread"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_article"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_breve"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"date_heure"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"titre"	=> "text NOT NULL",
		"texte"	=> "mediumtext NOT NULL",
		"auteur"	=> "text NOT NULL",
		"email_auteur"	=> "text NOT NULL",
		"nom_site"	=> "text NOT NULL",
		"url_site"	=> "text NOT NULL",
		"statut"	=> "varchar(8) NOT NULL",
		"idx"		=> "ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL",
		"ip"	=> "varchar(16)",
		"maj"	=> "TIMESTAMP",
		"id_auteur"	=> "BIGINT DEFAULT '0' NOT NULL",
		"id_message"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_syndic"	=> "bigint(21) DEFAULT '0' NOT NULL");

$spip_forum_key = array(
		"PRIMARY KEY"	=> "id_forum",
		"KEY id_parent"	=> "id_parent",
		"KEY id_article"	=> "id_article",
		"KEY id_breve"	=> "id_breve",
		"KEY id_message"	=> "id_message",
		"KEY id_syndic"	=> "id_syndic",
		"KEY id_rubrique"	=> "id_rubrique",
		"KEY idx"	=> "idx",
		"KEY statut"	=> "statut, date_heure");

$spip_signatures = array(
		"id_signature"	=> "bigint(21) NOT NULL",
		"id_article"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"date_time"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"nom_email"	=> "text NOT NULL",
		"ad_email"	=> "text NOT NULL",
		"nom_site"	=> "text NOT NULL",
		"url_site"	=> "text NOT NULL",
		"message"	=> "mediumtext NOT NULL",
		"statut"	=> "varchar(10) NOT NULL",
		"idx"		=> "ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_signatures_key = array(
		"PRIMARY KEY"	=> "id_signature",
		"KEY id_article"	=> "id_article",
		"KEY idx"		=> "idx",
		"KEY statut" => "statut");


$spip_ajax_fonc = array(
		"id_ajax_fonc" => "bigint(21) NOT NULL",
		"id_auteur" => "bigint(21) NOT NULL",
		"variables" => "text NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL" );

$spip_ajax_fonc_key = array(
		"PRIMARY KEY"		=> "id_ajax_fonc",
		"KEY `hash`"	=> "`hash`");


global $tables_principales;

/// Attention: mes_fonctions peut avoir deja defini cette variable
/// il faut donc rajouter, mais pas reinitialiser

$tables_principales['spip_articles'] =
	array('field' => &$spip_articles, 'key' => &$spip_articles_key);
$tables_principales['spip_auteurs']  =
	array('field' => &$spip_auteurs, 'key' => &$spip_auteurs_key);
$tables_principales['spip_breves']   =
	array('field' => &$spip_breves, 'key' => &$spip_breves_key);
$tables_principales['spip_messages'] =
	array('field' => &$spip_messages, 'key' => &$spip_messages_key);
$tables_principales['spip_mots']     =
	array('field' => &$spip_mots, 'key' => &$spip_mots_key);
$tables_principales['spip_groupes_mots'] =
	array('field' => &$spip_groupes_mots, 'key' => &$spip_groupes_mots_key);
$tables_principales['spip_rubriques'] =
	array('field' => &$spip_rubriques, 'key' => &$spip_rubriques_key);
$tables_principales['spip_documents'] =
	array('field' => &$spip_documents,  'key' => &$spip_documents_key);
$tables_principales['spip_types_documents']	=
	array('field' => &$spip_types_documents, 'key' => &$spip_types_documents_key);
$tables_principales['spip_syndic'] =
	array('field' => &$spip_syndic, 'key' => &$spip_syndic_key);
$tables_principales['spip_syndic_articles']	=
	array('field' => &$spip_syndic_articles, 'key' => &$spip_syndic_articles_key);
$tables_principales['spip_forum'] =
	array('field' => &$spip_forum,	'key' => &$spip_forum_key);
$tables_principales['spip_signatures'] =
	array('field' => &$spip_signatures, 'key' => &$spip_signatures_key);
$tables_principales['spip_ajax_fonc'] =
	array('field' => &$spip_ajax_fonc, 'key' => &$spip_ajax_fonc_key);

?>
