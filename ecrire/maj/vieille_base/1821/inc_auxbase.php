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


// Ce fichier ne sera execute qu'une fois
if (defined('_ECRIRE_INC_AUXBASE')) return;
define('_ECRIRE_INC_AUXBASE', "1");

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
		"visites_veille"=> "INT UNSIGNED NOT NULL",
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

$spip_documents_syndic = array(
		"id_document"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_syndic"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_syndic_article"	=> "BIGINT (21) DEFAULT '0' NOT NULL"
		);

$spip_documents_syndic_key = array(
		"KEY id_document"	=> "id_document",
		"KEY id_syndic"	=> "id_syndic",
		"KEY id_syndic_article"	=> "id_syndic_article");

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

$spip_mots_documents = array(
		"id_mot"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
		"id_document"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

$spip_mots_documents_key = array(
		"KEY id_mot"	=> "id_mot",
		"KEY id_document"	=> "id_document");

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

$spip_index_documents = array(
		"`hash`"	=> "BIGINT UNSIGNED NOT NULL",
		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"id_document"	=> "INT UNSIGNED NOT NULL");

$spip_index_documents_key = array(
		"KEY `hash`"	=> "`hash`",
		"KEY id_document"	=> "id_document");

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
	'spip_petitions' => array('field' => &$spip_petitions,
			     'key' => &$spip_petitions_key),
	'spip_visites_temp' => array('field' => &$spip_visites_temp,
				'key' => &$spip_visites_temp_key),
	'spip_visites' =>	array('field' => &$spip_visites,
			      'key' => &$spip_visites_key),
	'spip_visites_articles' => array('field' => &$spip_visites_articles,
				    'key' => &$spip_visites_articles_key),
	'spip_referers_temp' => array('field' => &$spip_referers_temp,
				 'key' => &$spip_referers_temp_key),
	'spip_referers' => array('field' => &$spip_referers,
			    'key' => &$spip_referers_key),
	'spip_referers_articles' => array('field' => &$spip_referers_articles,
				     'key' => &$spip_referers_articles_key),
	'spip_auteurs_articles' => array('field' => &$spip_auteurs_articles,
				    'key' => &$spip_auteurs_articles_key),
	'spip_auteurs_rubriques' => array('field' => &$spip_auteurs_rubriques,
				     'key' => &$spip_auteurs_rubriques_key),
	'spip_auteurs_messages' => array('field' => &$spip_auteurs_messages,
				    'key' => &$spip_auteurs_messages_key),
	'spip_documents_articles' => array('field' => &$spip_documents_articles,
				      'key' => &$spip_documents_articles_key),
	'spip_documents_rubriques' => array('field' => &$spip_documents_rubriques,
				       'key' => &$spip_documents_rubriques_key),
	'spip_documents_breves' => array('field' => &$spip_documents_breves,
				    'key' => &$spip_documents_breves_key),
	'spip_documents_syndic' => array('field' => &$spip_documents_syndic,
				    'key' => &$spip_documents_syndic_key),
	'spip_mots_articles' => array('field' => &$spip_mots_articles,
				 'key' => &$spip_mots_articles_key),
	'spip_mots_breves' => array('field' => &$spip_mots_breves,
			       'key' => &$spip_mots_breves_key),
	'spip_mots_rubriques' => array('field' => &$spip_mots_rubriques,
				  'key' => &$spip_mots_rubriques_key),
	'spip_mots_syndic' => array('field' => &$spip_mots_syndic,
			       'key' => &$spip_mots_syndic_key),
	'spip_mots_forum' => array('field' => &$spip_mots_forum,
			      'key' => &$spip_mots_forum_key),
	'spip_mots_documents' => array('field' => &$spip_mots_documents,
			      'key' => &$spip_mots_documents_key),
	'spip_meta' => array('field' => &$spip_meta,
			'key' => &$spip_meta_key),
	'spip_index_articles' => array('field' => &$spip_index_articles,
				  'key' => &$spip_index_articles_key),
	'spip_index_auteurs' => array('field' => &$spip_index_auteurs,
				 'key' => &$spip_index_auteurs_key),
	'spip_index_breves' => array('field' => &$spip_index_breves,
				'key' => &$spip_index_breves_key),
	'spip_index_mots' => array('field' => &$spip_index_mots,
			      'key' => &$spip_index_mots_key),
	'spip_index_rubriques' => array('field' => &$spip_index_rubriques,
				   'key' => &$spip_index_rubriques_key),
	'spip_index_syndic' => array('field' => &$spip_index_syndic,
				'key' => &$spip_index_syndic_key),
	'spip_index_signatures' => array('field' => &$spip_index_signatures,
				    'key' => &$spip_index_signatures_key),
	'spip_index_forum' => array('field' => &$spip_index_forum,
			       'key' => &$spip_index_forum_key),
	'spip_index_documents' => array('field' => &$spip_index_documents,
			       'key' => &$spip_index_documents_key),
	'spip_index_dico' => array('field' => &$spip_index_dico,
			      'key' => &$spip_index_dico_key),
	'spip_versions'	=> array('field' => &$spip_versions,
					 'key' => &$spip_versions_key),
	'spip_versions_fragments'	=> array('field' => &$spip_versions_fragments,
					 'key' => &$spip_versions_fragments_key),
	'spip_caches'	=> array('field' => &$spip_caches,
					 'key' => &$spip_caches_key),
	'spip_ortho_cache'	=> array('field' => &$spip_ortho_cache,
					 'key' => &$spip_ortho_cache_key),
	'spip_ortho_dico'	=> array('field' => &$spip_ortho_dico,
					 'key' => &$spip_ortho_dico_key)
	);

	
//
// tableau des tables de relations,
// Ex: gestion du critere {id_mot} dans la boucle(ARTICLES)
// transposee en tables_jointures pour le code moderne
//
global $tables_jointures;

$tables_jointures['spip_articles']['id_mot']='mots_articles';
$tables_jointures['spip_articles']['id_auteur']='auteurs_articles';
$tables_jointures['spip_articles']['id_document']='documents_articles';

$tables_jointures['spip_auteurs']['id_article']='auteurs_articles';

$tables_jointures['spip_breves']['id_mot']='mots_breves';
$tables_jointures['spip_breves']['id_document']='documents_breves';

$tables_jointures['spip_documents']['id_article']='documents_articles';
$tables_jointures['spip_documents']['id_rubrique']='documents_rubriques';
$tables_jointures['spip_documents']['id_breve']='documents_breves';
$tables_jointures['spip_documents']['id_syndic']='documents_syndic';
$tables_jointures['spip_documents']['id_syndic_article']='documents_syndic';
$tables_jointures['spip_documents']['id_mot']='mots_documents';

$tables_jointures['spip_forums']['id_mot']='mots_forum';

$tables_jointures['spip_mots']['id_article']='mots_articles';
$tables_jointures['spip_mots']['id_breve']='mots_breves';
$tables_jointures['spip_mots']['id_forum']='mots_forum';
$tables_jointures['spip_mots']['id_rubrique']='mots_rubriques';
$tables_jointures['spip_mots']['id_syndic']='mots_syndic';
$tables_jointures['spip_mots']['id_document']='mots_documents';

$tables_jointures['spip_groupes_mots']['id_groupe']='mots';

$tables_jointures['spip_rubriques']['id_mot']='mots_rubriques';
$tables_jointures['spip_rubriques']['id_document']='documents_rubriques';

$tables_jointures['spip_syndication']['id_mot']='mots_syndic';
$tables_jointures['spip_syndication']['id_document']='documents_syndic';
$tables_jointures['spip_syndic_articles']['id_document']='documents_syndic';

?>
