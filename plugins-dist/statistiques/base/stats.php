<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function stats_declarer_tables_auxiliaires($tables_auxiliaires){

	$spip_visites = array(
			"date"	=> "DATE NOT NULL",
			"visites"	=> "int UNSIGNED DEFAULT '0' NOT NULL",
			"maj"	=> "TIMESTAMP");

	$spip_visites_key = array(
			"PRIMARY KEY"	=> "date");

	$spip_visites_articles = array(
			"date"	=> "DATE NOT NULL",
			"id_article"	=> "int UNSIGNED NOT NULL",
			"visites"	=> "int UNSIGNED DEFAULT '0' NOT NULL",
			"maj"	=> "TIMESTAMP");

	$spip_visites_articles_key = array(
			"PRIMARY KEY"	=> "date, id_article");	



	$spip_referers = array(
			"referer_md5"	=> "bigint UNSIGNED NOT NULL",
			"date"		=> "DATE NOT NULL",
			"referer"	=> "VARCHAR (255)",
			"visites"	=> "int UNSIGNED NOT NULL",
			"visites_jour"	=> "int UNSIGNED NOT NULL",
			"visites_veille"=> "int UNSIGNED NOT NULL",
			"maj"		=> "TIMESTAMP");

	$spip_referers_key = array(
			"PRIMARY KEY"	=> "referer_md5");

	$spip_referers_articles = array(
			"id_article"	=> "int UNSIGNED NOT NULL",
			"referer_md5"	=> "bigint UNSIGNED NOT NULL",
			"referer"	=> "VARCHAR (255) DEFAULT '' NOT NULL",
			"visites"	=> "int UNSIGNED NOT NULL",
			"maj"		=> "TIMESTAMP");

	$spip_referers_articles_key = array(
			"PRIMARY KEY"	=> "id_article, referer_md5",
			"KEY referer_md5"	=> "referer_md5");

	$tables_auxiliaires['spip_visites'] = array(
		'field' => &$spip_visites,
		'key' => &$spip_visites_key);
	$tables_auxiliaires['spip_visites_articles'] = array(
		'field' => &$spip_visites_articles,
		'key' => &$spip_visites_articles_key);
	$tables_auxiliaires['spip_referers'] = array(
		'field' => &$spip_referers,
		'key' => &$spip_referers_key);
	$tables_auxiliaires['spip_referers_articles'] = array(
		'field' => &$spip_referers_articles,
		'key' => &$spip_referers_articles_key);	

	return $tables_auxiliaires;
}

?>
