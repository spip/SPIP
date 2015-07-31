<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_AUXBASE")) return;
define("_ECRIRE_INC_AUXBASE", "1");

$spip_petitions = array(
		"id_article"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"email_unique"	=> "CHAR (3) NOT NULL",
		"site_obli"	=> "CHAR (3) NOT NULL",
		"site_unique"	=> "CHAR (3) NOT NULL",
		"message"	=> "CHAR (3) NOT NULL",
		"texte"	=> "LONGBLOB NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_petitions_key = array(
		"PRIMARY KEY"	=> "id_article");

$spip_visites_temp = array(
		"ip"	=> "INT UNSIGNED NOT NULL",
		"type"	=> "ENUM ('article', 'rubrique', 'breve', 'autre') NOT NULL",
		"id_objet"	=> "INT UNSIGNED NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_visites_temp_key = array(
		"PRIMARY KEY"	=> "type, id_objet, ip");

$spip_visites = array(
		"date"	=> "DATE NOT NULL",
		"visites"	=> "INT UNSIGNED NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_visites_key = array(
		"PRIMARY KEY"	=> "date");

$spip_visites_articles = array(
		"date"	=> "DATE NOT NULL",
		"id_article"	=> "INT UNSIGNED NOT NULL",
		"visites"	=> "INT UNSIGNED NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_visites_articles_key = array(
		"PRIMARY KEY"	=> "date, id_article");

$spip_referers_temp = array(
		"ip"	=> "INT UNSIGNED NOT NULL",
		"referer"	=> "VARCHAR (255) NOT NULL",
		"referer_md5"	=> "BIGINT UNSIGNED NOT NULL",
		"type"	=> "ENUM ('article', 'rubrique', 'breve', 'autre') NOT NULL",
		"id_objet"	=> "INT UNSIGNED NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_referers_temp_key = array(
		"PRIMARY KEY"	=> "type, id_objet, referer_md5, ip");

$spip_referers = array(
		"referer_md5"	=> "BIGINT UNSIGNED NOT NULL",
		"date"		=> "DATE NOT NULL",
		"referer"	=> "VARCHAR (255) NOT NULL",
		"visites"	=> "INT UNSIGNED NOT NULL",
		"visites_jour"	=> "INT UNSIGNED NOT NULL",
		"maj"		=> "TIMESTAMP");

$spip_referers_key = array(
		"PRIMARY KEY"	=> "referer_md5");

$spip_referers_articles = array(
		"id_article"	=> "INT UNSIGNED NOT NULL",
		"referer_md5"	=> "BIGINT UNSIGNED NOT NULL",
		"date"		=> "DATE NOT NULL",
		"referer"	=> "VARCHAR (255) NOT NULL",
		"visites"	=> "INT UNSIGNED NOT NULL",
		"maj"		=> "TIMESTAMP");

$spip_referers_articles_key = array(
		"PRIMARY KEY"	=> "id_article, referer_md5",
		"KEY referer_md5"	=> "referer_md5");

$spip_auteurs_articles = array(
		"id_auteur"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_article"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_auteurs_articles_key = array(
		"KEY id_auteur"	=> "id_auteur",
		"KEY id_article"	=> "id_article");

$spip_auteurs_rubriques = array(
		"id_auteur"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_rubrique"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_auteurs_rubriques_key = array(
		"KEY id_auteur"	=> "id_auteur",
		"KEY id_rubrique"	=> "id_rubrique");

$spip_auteurs_messages = array(
		"id_auteur"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_message"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"vu"		=> "CHAR (3) NOT NULL");

$spip_auteurs_messages_key = array(
		"KEY id_auteur"	=> "id_auteur",
		"KEY id_message"	=> "id_message");


$spip_documents_articles = array(
		"id_document"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_article"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_documents_articles_key = array(
		"KEY id_document"	=> "id_document",
		"KEY id_article"	=> "id_article");

$spip_documents_rubriques = array(
		"id_document"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_rubrique"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_documents_rubriques_key = array(
		"KEY id_document"	=> "id_document",
		"KEY id_rubrique"	=> "id_rubrique");

$spip_documents_breves = array(
		"id_document"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_breve"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_documents_breves_key = array(
		"KEY id_document"	=> "id_document",
		"KEY id_breve"	=> "id_breve");

$spip_mots_articles = array(
		"id_mot"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_article"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_mots_articles_key = array(
		"KEY id_mot"	=> "id_mot",
		"KEY id_article"	=> "id_article");

$spip_mots_breves = array(
		"id_mot"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_breve"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_mots_breves_key = array(
		"KEY id_mot"	=> "id_mot",
		"KEY id_breve"	=> "id_breve");

$spip_mots_rubriques = array(
		"id_mot"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_rubrique"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_mots_rubriques_key = array(
		"KEY id_mot"	=> "id_mot",
		"KEY id_rubrique"	=> "id_rubrique");

$spip_mots_syndic = array(
		"id_mot"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_syndic"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_mots_syndic_key = array(
		"KEY id_mot"	=> "id_mot",
		"KEY id_syndic"	=> "id_syndic");

$spip_mots_forum = array(
		"id_mot"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_forum"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_mots_forum_key = array(
		"KEY id_mot"	=> "id_mot",
		"KEY id_forum"	=> "id_forum");

$spip_meta = array(
		"nom"	=> "VARCHAR (255) NOT NULL",
		"valeur"	=> "VARCHAR (255) DEFAULT ''",
		"maj"	=> "TIMESTAMP");

$spip_meta_key = array(
		"PRIMARY KEY"	=> "nom");

$spip_index_articles = array(
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL",
		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"id_article"	=> "INT UNSIGNED NOT NULL");

$spip_index_articles_key = array(
		"KEY `hash`"	=> "`hash`",
		"KEY id_article"	=> "id_article");

$spip_index_auteurs = array(
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL",
		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"id_auteur"	=> "INT UNSIGNED NOT NULL");

$spip_index_auteurs_key = array(
		"KEY `hash`"	=> "`hash`",
		"KEY id_auteur"	=> "id_auteur");

$spip_index_breves = array(
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL",
		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"id_breve"	=> "INT UNSIGNED NOT NULL");

$spip_index_breves_key = array(
		"KEY `hash`"	=> "`hash`",
		"KEY id_breve"	=> "id_breve");

$spip_index_mots = array(
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL",
		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"id_mot"	=> "INT UNSIGNED NOT NULL");

$spip_index_mots_key = array(
		"KEY `hash`"	=> "`hash`",
		"KEY id_mot"	=> "id_mot");

$spip_index_rubriques = array(
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL",
		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"id_rubrique"	=> "INT UNSIGNED NOT NULL");

$spip_index_rubriques_key = array(
		"KEY `hash`"	=> "`hash`",
		"KEY id_rubrique"	=> "id_rubrique");

$spip_index_syndic = array(
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL",
		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"id_syndic"	=> "INT UNSIGNED NOT NULL");

$spip_index_syndic_key = array(
		"KEY `hash`"	=> "`hash`",
		"KEY id_syndic"	=> "id_syndic");

$spip_index_signatures = array(
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL",
		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"id_signature"	=> "INT UNSIGNED NOT NULL");

$spip_index_signatures_key = array(
		"KEY `hash`"		=> "`hash`",
		"KEY id_signature"	=> "id_signature");

$spip_index_forum = array(
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL",
		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"id_forum"	=> "INT UNSIGNED NOT NULL");

$spip_index_forum_key = array(
		"KEY `hash`"	=> "`hash`",
		"KEY id_forum"	=> "id_forum");

$spip_index_dico = array(
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL",
		"dico"		=> "VARCHAR (30) NOT NULL");

$spip_index_dico_key = array(
		"PRIMARY KEY"	=> "dico");

$spip_versions = array (
		"id_article"	=> "bigint(21) NOT NULL",
		"id_version"	=> "int unsigned DEFAULT '0' NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"id_auteur"	=> "bigint(21) NOT NULL",
		"titre_version"	=> "text DEFAULT '' NOT NULL",
		"permanent"	=> "char(3) NOT NULL",
		"champs"	=> "text NOT NULL");

$spip_versions_key = array (
		"PRIMARY KEY"	=> "id_article, id_version",
		"KEY date"	=> "id_article, date",
		"KEY id_auteur"	=> "id_auteur");

$spip_versions_fragments = array(
		"id_fragment"	=> "int unsigned DEFAULT '0' NOT NULL",
		"version_min"	=> "int unsigned DEFAULT '0' NOT NULL",
		"version_max"	=> "int unsigned DEFAULT '0' NOT NULL",
		"id_article"	=> "bigint(21) NOT NULL",
		"compress"	=> "tinyint NOT NULL",
		"fragment"	=> "longblob NOT NULL");

$spip_versions_fragments_key = array(
	     "PRIMARY KEY"	=> "id_article, id_fragment, version_min");

$spip_caches = array(
		"fichier" => "char (64) NOT NULL",
		"id" => "char (64) NOT NULL",
		// i=par id, t=timer, x=suppression
		"type" => "CHAR (1) DEFAULT 'i' NOT NULL",
		"taille" => "integer DEFAULT '0' NOT NULL");
$spip_caches_key = array(
		"PRIMARY KEY"	=> "fichier, id",
		"KEY fichier" => "fichier",
		"KEY id" => "id");

$spip_ortho_cache = array(
	"lang" => "VARCHAR(10) NOT NULL",
	"mot" => "VARCHAR(255) BINARY NOT NULL",
	"ok" => "TINYINT NOT NULL",
	"suggest" => "BLOB NOT NULL",
	"maj" => "TIMESTAMP");
$spip_ortho_cache_key = array(
	"PRIMARY KEY" => "lang, mot",
	"KEY maj" => "maj");

$spip_ortho_dico = array(
	"lang" => "VARCHAR(10) NOT NULL",
	"mot" => "VARCHAR(255) BINARY NOT NULL",
	"id_auteur" => "BIGINT UNSIGNED NOT NULL",
	"maj" => "TIMESTAMP");
$spip_ortho_dico_key = array(
	"PRIMARY KEY" => "lang, mot",);


global $tables_auxiliaires;

$tables_auxiliaires  = 
  array(
	'petitions' => array('field' => &$spip_petitions,
			     'key' => &$spip_petitions_key),
	'visites_temp' => array('field' => &$spip_visites_temp,
				'key' => &$spip_visites_temp_key),
	'visites' =>	array('field' => &$spip_visites,
			      'key' => &$spip_visites_key),
	'visites_articles' => array('field' => &$spip_visites_articles,
				    'key' => &$spip_visites_articles_key),
	'referers_temp' => array('field' => &$spip_referers_temp,
				 'key' => &$spip_referers_temp_key),
	'referers' => array('field' => &$spip_referers,
			    'key' => &$spip_referers_key),
	'referers_articles' => array('field' => &$spip_referers_articles,
				     'key' => &$spip_referers_articles_key),
	'auteurs_articles' => array('field' => &$spip_auteurs_articles,
				    'key' => &$spip_auteurs_articles_key),
	'auteurs_rubriques' => array('field' => &$spip_auteurs_rubriques,
				     'key' => &$spip_auteurs_rubriques_key),
	'auteurs_messages' => array('field' => &$spip_auteurs_messages,
				    'key' => &$spip_auteurs_messages_key),
	'documents_articles' => array('field' => &$spip_documents_articles,
				      'key' => &$spip_documents_articles_key),
	'documents_rubriques' => array('field' => &$spip_documents_rubriques,
				       'key' => &$spip_documents_rubriques_key),
	'documents_breves' => array('field' => &$spip_documents_breves,
				    'key' => &$spip_documents_breves_key),
	'mots_articles' => array('field' => &$spip_mots_articles,
				 'key' => &$spip_mots_articles_key),
	'mots_breves' => array('field' => &$spip_mots_breves,
			       'key' => &$spip_mots_breves_key),
	'mots_rubriques' => array('field' => &$spip_mots_rubriques,
				  'key' => &$spip_mots_rubriques_key),
	'mots_syndic' => array('field' => &$spip_mots_syndic,
			       'key' => &$spip_mots_syndic_key),
	'mots_forum' => array('field' => &$spip_mots_forum,
			      'key' => &$spip_mots_forum_key),
	'meta' => array('field' => &$spip_meta,
			'key' => &$spip_meta_key),
	'index_articles' => array('field' => &$spip_index_articles,
				  'key' => &$spip_index_articles_key),
	'index_auteurs' => array('field' => &$spip_index_auteurs,
				 'key' => &$spip_index_auteurs_key),
	'index_breves' => array('field' => &$spip_index_breves,
				'key' => &$spip_index_breves_key),
	'index_mots' => array('field' => &$spip_index_mots,
			      'key' => &$spip_index_mots_key),
	'index_rubriques' => array('field' => &$spip_index_rubriques,
				   'key' => &$spip_index_rubriques_key),
	'index_syndic' => array('field' => &$spip_index_syndic,
				'key' => &$spip_index_syndic_key),
	'index_signatures' => array('field' => &$spip_index_signatures,
				    'key' => &$spip_index_signatures_key),
	'index_forum' => array('field' => &$spip_index_forum,
			       'key' => &$spip_index_forum_key),
	'index_dico' => array('field' => &$spip_index_dico,
			      'key' => &$spip_index_dico_key),
	'versions'	=> array('field' => &$spip_versions,
					 'key' => &$spip_versions_key),
	'versions_fragments'	=> array('field' => &$spip_versions_fragments,
					 'key' => &$spip_versions_fragments_key),
	'caches'	=> array('field' => &$spip_caches,
					 'key' => &$spip_caches_key),
	'ortho_cache'	=> array('field' => &$spip_ortho_cache,
					 'key' => &$spip_ortho_cache_key),
	'ortho_dico'	=> array('field' => &$spip_ortho_dico,
					 'key' => &$spip_ortho_dico_key)
	);

?>
