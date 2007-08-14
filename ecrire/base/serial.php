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


$spip_articles = array(
		"id_article"	=> "bigint(21) NOT NULL",
		"surtitre"	=> "text",
		"titre"	=> "text",
		"soustitre"	=> "text",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"descriptif"	=> "text",
		"chapo"	=> "mediumtext",
		"texte"	=> "longtext",
		"ps"	=> "mediumtext",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
		"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"maj"	=> "TIMESTAMP",
		"export"	=> "VARCHAR(10) DEFAULT 'oui'",
		"date_redac"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"visites"	=> "integer DEFAULT '0' NOT NULL",
		"referers"	=> "integer DEFAULT '0' NOT NULL",
		"popularite"	=> "DOUBLE DEFAULT '0' NOT NULL",
		"accepter_forum"	=> "CHAR(3)",
		"date_modif"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"lang"		=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"langue_choisie"	=> "VARCHAR(3) DEFAULT 'non'",
		"id_trad"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"extra"		=> "longtext NULL",
		"id_version"	=> "int unsigned DEFAULT '0' NOT NULL",
		"nom_site"	=> "tinytext",
		"url_site"	=> "VARCHAR(255)",
		"url_propre" => "VARCHAR(255)");

$spip_articles_key = array(
		"PRIMARY KEY"		=> "id_article",
		"KEY id_rubrique"	=> "id_rubrique",
		"KEY id_secteur"	=> "id_secteur",
		"KEY id_trad"		=> "id_trad",
		"KEY lang"			=> "lang",
		"KEY statut"		=> "statut, date",
		"KEY url_site"		=> "url_site",
		"KEY date_modif"	=> "date_modif",
		"KEY url_propre"	=> "url_propre");
$spip_articles_join = array(
		"id_article"=>"id_article",
		"id_rubrique"=>"id_rubrique");

$spip_auteurs = array(
		"id_auteur"	=> "bigint(21) NOT NULL",
		"nom"	=> "text",
		"bio"	=> "text",
		"email"	=> "tinytext",
		"nom_site"	=> "tinytext",
		"url_site"	=> "text",
		"login"	=> "VARCHAR(255) BINARY",
		"pass"	=> "tinytext",
		"low_sec"	=> "tinytext",
		"statut"	=> "varchar(255)  DEFAULT '0' NOT NULL",
		"maj"	=> "TIMESTAMP",
		"pgp"	=> "TEXT",
		"htpass"	=> "tinytext",
		"en_ligne"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"imessage"	=> "VARCHAR(3)",
		"messagerie"	=> "VARCHAR(3)",
		"alea_actuel"	=> "tinytext",
		"alea_futur"	=> "tinytext",
		"prefs"	=> "tinytext",
		"cookie_oubli"	=> "tinytext",
		"source"	=> "VARCHAR(10) DEFAULT 'spip' NOT NULL",
		"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"url_propre" => "VARCHAR(255)",
		"extra"	=> "longtext NULL");

$spip_auteurs_key = array(
		"PRIMARY KEY"	=> "id_auteur",
		"KEY login"	=> "login",
		"KEY statut"	=> "statut",
		"KEY lang"	=> "lang",
		"KEY en_ligne"	=> "en_ligne",
		"KEY url_propre"	=> "url_propre");
$spip_auteurs_join = array(
		"id_auteur"=>"id_auteur",
		"login"=>"login");


$spip_breves = array(
		"id_breve"	=> "bigint(21) NOT NULL",
		"date_heure"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"titre"	=> "text",
		"texte"	=> "longtext",
		"lien_titre"	=> "text",
		"lien_url"	=> "text",
		"statut"	=> "varchar(6)  DEFAULT '0' NOT NULL",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"langue_choisie"	=> "VARCHAR(3) DEFAULT 'non'",
		"maj"	=> "TIMESTAMP",
		"extra"	=> "longtext NULL",
		"url_propre" => "VARCHAR(255)");

$spip_breves_key = array(
		"PRIMARY KEY"	=> "id_breve",
		"KEY id_rubrique"	=> "id_rubrique",
		"KEY url_propre"	=> "url_propre");
$spip_breves_join = array(
		"id_breve"=>"id_breve",
		"id_rubrique"=>"id_rubrique");

$spip_messages = array(
		"id_message"	=> "bigint(21) NOT NULL",
		"titre"	=> "text",
		"texte"	=> "longtext",
		"type"	=> "varchar(6)",
		"date_heure"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_fin"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"rv"	=> "varchar(3)",
		"statut"	=> "varchar(6)  DEFAULT '0' NOT NULL",
		"id_auteur"	=> "bigint(21) NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_messages_key = array(
		"PRIMARY KEY"	=> "id_message",
		"KEY id_auteur"	=> "id_auteur");

$spip_mots = array(
		"id_mot"	=> "bigint(21) NOT NULL",
		"titre"	=> "text",
		"descriptif"	=> "text",
		"texte"	=> "longtext",
		"id_groupe"	=> "bigint(21) NOT NULL",
		"type"	=> "text",
		"extra"	=> "longtext NULL",
		"url_propre" => "VARCHAR(255)",
		"maj"	=> "TIMESTAMP");

$spip_mots_key = array(
		"PRIMARY KEY"	=> "id_mot",
		"KEY url_propre"	=> "url_propre");

$spip_groupes_mots = array(
		"id_groupe"	=> "bigint(21) NOT NULL",
		"titre"	=> "text",
		"descriptif"	=> "text",
		"texte"	=> "longtext",
		"unseul"	=> "varchar(3)",
		"obligatoire"	=> "varchar(3)",
		"articles"	=> "varchar(3)",
		"breves"	=> "varchar(3)",
		"rubriques"	=> "varchar(3)",
		"syndic"	=> "varchar(3)",
		"minirezo"	=> "varchar(3)",
		"comite"	=> "varchar(3)",
		"forum"	=> "varchar(3)",
		"maj"	=> "TIMESTAMP");

$spip_groupes_mots_key = array(
		"PRIMARY KEY"	=> "id_groupe");

$spip_rubriques = array(
		"id_rubrique"	=> "bigint(21) NOT NULL",
		"id_parent"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"titre"	=> "text",
		"descriptif"	=> "text",
		"texte"	=> "longtext",
		"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"maj"	=> "TIMESTAMP",
		"export"	=> "VARCHAR(10) DEFAULT 'oui'",
		"id_import"	=> "bigint DEFAULT '0'",
		"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"langue_choisie"	=> "VARCHAR(3) DEFAULT 'non'",
		"extra"	=> "longtext NULL",
		"url_propre" => "VARCHAR(255)",
		"statut_tmp"	=> "varchar(10) DEFAULT '0' NOT NULL",
		"date_tmp"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL"
		);

$spip_rubriques_key = array(
		"PRIMARY KEY"	=> "id_rubrique",
		"KEY lang"	=> "lang",
		"KEY id_parent"	=> "id_parent",
		"KEY url_propre"	=> "url_propre");

$spip_documents = array(
		"id_document"	=> "bigint(21) NOT NULL",
		"id_vignette"	=> "bigint(21) DEFAULT '0' NOT NULL",
/*		"id_type"	=> "bigint(21) DEFAULT '0' NOT NULL", */
		"extension"	=> "VARCHAR(10)",
		"titre"	=> "text",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"descriptif"	=> "text",
		"fichier"	=> "varchar(255)",
		"taille"	=> "integer",
		"largeur"	=> "integer",
		"hauteur"	=> "integer",
		"mode"	=> "ENUM('vignette', 'document') NOT NULL",//+distant
		"distant"	=> "VARCHAR(3) DEFAULT 'non'",
		"maj"	=> "TIMESTAMP");

$spip_documents_key = array(
		"PRIMARY KEY"	=> "id_document",
		"KEY id_vignette"	=> "id_vignette",
		"KEY mode"	=> "mode",
		"KEY extension"	=> "extension");
$spip_documents_join = array(
		"id_document"=>"id_document",
		"extension"=>"extension");

$spip_types_documents = array(
/*		"id_type"	=> "bigint(21) NOT NULL", */
		"extension"	=> "varchar(10)",
		"titre"	=> "text",
		"descriptif"	=> "text",
		"mime_type"	=> "varchar(100)",
		"inclus"	=> "ENUM('non', 'image', 'embed') NOT NULL DEFAULT 'non'",
		"upload"	=> "ENUM('oui', 'non') NOT NULL DEFAULT 'oui'",
		"maj"	=> "TIMESTAMP");

$spip_types_documents_key = array(
/*		"PRIMARY KEY"	=> "id_type",
		"UNIQUE extension"	=> "extension",
*/
		"PRIMARY KEY"	=> "extension",
		"KEY inclus"	=> "inclus");

$spip_syndic = array(
		"id_syndic"	=> "bigint(21) NOT NULL",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"nom_site"	=> "text",
		"url_site"	=> "text",
		"url_syndic"	=> "text",
		"descriptif"	=> "text",
		"url_propre"	=> "VARCHAR(255)",
		"maj"	=> "TIMESTAMP",
		"syndication"	=> "VARCHAR(3)",
		"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_syndic"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_index"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"extra"			=> "longtext NULL",
		"moderation"	=> "VARCHAR(3) DEFAULT 'non'",
		"miroir"	=> "VARCHAR(3) DEFAULT 'non'",
		"oubli"	=> "VARCHAR(3) DEFAULT 'non'",
		"resume"	=> "VARCHAR(3) DEFAULT 'oui'"
);

$spip_syndic_key = array(
		"PRIMARY KEY"	=> "id_syndic",
		"KEY id_rubrique"	=> "id_rubrique",
		"KEY id_secteur"	=> "id_secteur",
		"KEY statut"	=> "statut, date_syndic",
		"KEY url_propre"	=> "url_propre");
$spip_syndic_join = array(
		"id_syndic"=>"id_syndic",
		"id_rubrique"=>"id_rubrique");
		
$spip_syndic_articles = array(
		"id_syndic_article"	=> "bigint(21) NOT NULL",
		"id_syndic"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"titre"	=> "text",
		"url"	=> "VARCHAR(255)",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"lesauteurs"	=> "text",
		"maj"	=> "TIMESTAMP",
		"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
		"descriptif"	=> "text",
		"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"url_source" => "TINYTEXT DEFAULT '' NOT NULL",
		"source" => "TINYTEXT DEFAULT '' NOT NULL",
		"tags" => "TEXT DEFAULT '' NOT NULL");

$spip_syndic_articles_key = array(
		"PRIMARY KEY"	=> "id_syndic_article",
		"KEY id_syndic"	=> "id_syndic",
		"KEY statut"	=> "statut",
		"KEY url"	=> "url");
$spip_syndic_articles_join = array(
		"id_syndic_article"=>"id_syndic_article",
		"id_syndic"=>"id_syndic");

$spip_forum = array(
		"id_forum"	=> "bigint(21) NOT NULL",
		"id_parent"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_thread"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_article"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_breve"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"date_heure"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_thread"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"titre"	=> "text",
		"texte"	=> "mediumtext",
		"auteur"	=> "text",
		"email_auteur"	=> "text",
		"nom_site"	=> "text",
		"url_site"	=> "text",
		"statut"	=> "varchar(8) DEFAULT '0' NOT NULL",
		"ip"	=> "varchar(16)",
		"maj"	=> "TIMESTAMP",
		"id_auteur"	=> "bigint DEFAULT '0' NOT NULL",
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
		"KEY statut"	=> "statut, date_heure",
		"KEY date_thread" => "date_thread");
$spip_forum_join = array(
		"id_forum"=>"id_forum",
		"id_parent"=>"id_parent",
		"id_article"=>"id_article",
		"id_breve"=>"id_breve",
		"id_message"=>"id_message",
		"id_syndic"=>"id_syndic",
		"id_rubrique"=>"id_rubrique");

$spip_signatures = array(
		"id_signature"	=> "bigint(21) NOT NULL",
		"id_article"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"date_time"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"nom_email"	=> "text",
		"ad_email"	=> "text",
		"nom_site"	=> "text",
		"url_site"	=> "text",
		"message"	=> "mediumtext",
		"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_signatures_key = array(
		"PRIMARY KEY"	=> "id_signature",
		"KEY id_article"	=> "id_article",
		"KEY statut" => "statut");
$spip_signatures_join = array(
		"id_signature"=>"id_signature",
		"id_article"=>"id_article");


global $tables_principales;

/// Attention: mes_fonctions peut avoir deja defini cette variable
/// il faut donc rajouter, mais pas reinitialiser

$tables_principales['spip_articles'] =
	array('field' => &$spip_articles, 'key' => &$spip_articles_key, 'join' => &$spip_articles_join);
$tables_principales['spip_auteurs']  =
	array('field' => &$spip_auteurs, 'key' => &$spip_auteurs_key,'join' => &$spip_auteurs_join);
$tables_principales['spip_breves']   =
	array('field' => &$spip_breves, 'key' => &$spip_breves_key,'join' => &$spip_breves_join);
$tables_principales['spip_messages'] =
	array('field' => &$spip_messages, 'key' => &$spip_messages_key);
$tables_principales['spip_mots']     =
	array('field' => &$spip_mots, 'key' => &$spip_mots_key);
$tables_principales['spip_groupes_mots'] =
	array('field' => &$spip_groupes_mots, 'key' => &$spip_groupes_mots_key);
$tables_principales['spip_rubriques'] =
	array('field' => &$spip_rubriques, 'key' => &$spip_rubriques_key);
$tables_principales['spip_documents'] =
	array('field' => &$spip_documents,  'key' => &$spip_documents_key, 'join' => &$spip_documents_join);
$tables_principales['spip_types_documents']	=
	array('field' => &$spip_types_documents, 'key' => &$spip_types_documents_key);
$tables_principales['spip_syndic'] =
	array('field' => &$spip_syndic, 'key' => &$spip_syndic_key, 'join' => &$spip_syndic_join);
$tables_principales['spip_syndic_articles']	=
	array('field' => &$spip_syndic_articles, 'key' => &$spip_syndic_articles_key, 'join' => &$spip_syndic_articles_join);
$tables_principales['spip_forum'] =
	array('field' => &$spip_forum,	'key' => &$spip_forum_key, 'join' => &$spip_forum_join);
$tables_principales['spip_signatures'] =
	array('field' => &$spip_signatures, 'key' => &$spip_signatures_key, 'join' => &$spip_signatures_join);

?>
