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

// http://doc.spip.org/@base_serial
function base_serial(&$tables_principales){
$spip_articles = array(
		"id_article"	=> "bigint(21) NOT NULL",
		"surtitre"	=> "text DEFAULT '' NOT NULL",
		"titre"	=> "text DEFAULT '' NOT NULL",
		"soustitre"	=> "text DEFAULT '' NOT NULL",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"descriptif"	=> "text DEFAULT '' NOT NULL",
		"chapo"	=> "mediumtext DEFAULT '' NOT NULL",
		"texte"	=> "longtext DEFAULT '' NOT NULL",
		"ps"	=> "mediumtext DEFAULT '' NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
		"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"maj"	=> "TIMESTAMP",
		"export"	=> "VARCHAR(10) DEFAULT 'oui'",
		"date_redac"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"visites"	=> "integer DEFAULT '0' NOT NULL",
		"referers"	=> "integer DEFAULT '0' NOT NULL",
		"popularite"	=> "DOUBLE DEFAULT '0' NOT NULL",
		"accepter_forum"	=> "CHAR(3) DEFAULT '' NOT NULL",
		"date_modif"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"lang"		=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"langue_choisie"	=> "VARCHAR(3) DEFAULT 'non'",
		"id_trad"	=> "bigint(21) DEFAULT '0' NOT NULL",
#		"id_version"	=> "int unsigned DEFAULT '0' NOT NULL",
		"nom_site"	=> "tinytext DEFAULT '' NOT NULL",
		"url_site"	=> "VARCHAR(255) DEFAULT '' NOT NULL",
#		"url_propre" => "VARCHAR(255) DEFAULT '' NOT NULL"
);

$spip_articles_key = array(
		"PRIMARY KEY"		=> "id_article",
		"KEY id_rubrique"	=> "id_rubrique",
		"KEY id_secteur"	=> "id_secteur",
		"KEY id_trad"		=> "id_trad",
		"KEY lang"		=> "lang",
		"KEY statut"		=> "statut, date",
#		"KEY url_propre"	=> "url_propre"
);
$spip_articles_join = array(
		"id_article"=>"id_article",
		"id_rubrique"=>"id_rubrique");

$spip_auteurs = array(
		"id_auteur"	=> "bigint(21) NOT NULL",
		"nom"	=> "text DEFAULT '' NOT NULL",
		"bio"	=> "text DEFAULT '' NOT NULL",
		"email"	=> "tinytext DEFAULT '' NOT NULL",
		"nom_site"	=> "tinytext DEFAULT '' NOT NULL",
		"url_site"	=> "text DEFAULT '' NOT NULL",
		"login"	=> "VARCHAR(255) BINARY",
		"pass"	=> "tinytext DEFAULT '' NOT NULL",
		"low_sec"	=> "tinytext DEFAULT '' NOT NULL",
		"statut"	=> "varchar(255)  DEFAULT '0' NOT NULL",
		"webmestre"	=> "varchar(3)  DEFAULT 'non' NOT NULL",
		"maj"	=> "TIMESTAMP",
		"pgp"	=> "TEXT DEFAULT '' NOT NULL",
		"htpass"	=> "tinytext DEFAULT '' NOT NULL",
		"en_ligne"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"alea_actuel"	=> "tinytext",
		"alea_futur"	=> "tinytext",
		"prefs"	=> "tinytext",
		"cookie_oubli"	=> "tinytext",
		"source"	=> "VARCHAR(10) DEFAULT 'spip' NOT NULL",
		"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL");

$spip_auteurs_key = array(
		"PRIMARY KEY"	=> "id_auteur",
		"KEY login"	=> "login",
		"KEY statut"	=> "statut",
		"KEY en_ligne"	=> "en_ligne",
#		"KEY url_propre"	=> "url_propre"
);
$spip_auteurs_join = array(
		"id_auteur"=>"id_auteur",
		"login"=>"login");


$spip_rubriques = array(
		"id_rubrique"	=> "bigint(21) NOT NULL",
		"id_parent"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"titre"	=> "text DEFAULT '' NOT NULL",
		"descriptif"	=> "text DEFAULT '' NOT NULL",
		"texte"	=> "longtext DEFAULT '' NOT NULL",
		"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"maj"	=> "TIMESTAMP",
		"export"	=> "VARCHAR(10) DEFAULT 'oui'",
		"id_import"	=> "bigint DEFAULT '0'",
		"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
		"langue_choisie"	=> "VARCHAR(3) DEFAULT 'non'",
		"statut_tmp"	=> "varchar(10) DEFAULT '0' NOT NULL",
		"date_tmp"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL"
		);

$spip_rubriques_key = array(
		"PRIMARY KEY"	=> "id_rubrique",
		"KEY lang"	=> "lang",
		"KEY id_parent"	=> "id_parent",
#		"KEY url_propre"	=> "url_propre"
);


/// Attention: mes_fonctions peut avoir deja defini cette variable
/// il faut donc rajouter, mais pas reinitialiser

$tables_principales['spip_articles'] =
	array('field' => &$spip_articles, 'key' => &$spip_articles_key, 'join' => &$spip_articles_join);
$tables_principales['spip_auteurs']  =
	array('field' => &$spip_auteurs, 'key' => &$spip_auteurs_key,'join' => &$spip_auteurs_join);
$tables_principales['spip_rubriques'] =
	array('field' => &$spip_rubriques, 'key' => &$spip_rubriques_key);

	$tables_principales = pipeline('declarer_tables_principales',$tables_principales);
}

include_spip('base/objets');
$GLOBALS['tables_principales'] = lister_tables_principales();

?>
