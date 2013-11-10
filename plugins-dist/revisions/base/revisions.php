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

if (!defined('_ECRIRE_INC_VERSION')) return;

function revisions_declarer_tables_interfaces($interface){

	$interface['table_des_tables']['versions']='versions';

	return $interface;
}

/**
 * Declaration des jointures generiques
 * @param  $tables
 * @return
 */
function revisions_declarer_tables_objets_sql($tables){

	// jointures sur les mots pour tous les objets
	$tables[]['tables_jointures'][]= 'versions';

	return $tables;
}

/**
 * Table principale spip_versions
 *
 * @param array $tables_principales
 * @return array
 */
function revisions_declarer_tables_auxiliaires($tables_auxiliaires){

	$spip_versions = array (
		"id_version"	=> "bigint(21) DEFAULT 0 NOT NULL",
		"id_objet"		=> "bigint(21) DEFAULT 0 NOT NULL",
		"objet"			=> "VARCHAR (25) DEFAULT '' NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"id_auteur"	=> "VARCHAR(23) DEFAULT '' NOT NULL", # stocke aussi IP(v6)
		"titre_version"	=> "text DEFAULT '' NOT NULL",
		"permanent"	=> "char(3) DEFAULT '' NOT NULL",
		"champs"	=> "text DEFAULT '' NOT NULL"
	);

	$spip_versions_key = array (
		"PRIMARY KEY"	=> "id_version, id_objet, objet",
		"KEY id_version" => "id_version",
		"KEY id_objet" => "id_objet",
		"KEY objet" => "objet");

	$spip_versions_fragments = array(
		"id_fragment"	=> "int unsigned DEFAULT '0' NOT NULL",
		"version_min"	=> "int unsigned DEFAULT '0' NOT NULL",
		"version_max"	=> "int unsigned DEFAULT '0' NOT NULL",
		"id_objet"	=> "bigint(21) NOT NULL",
		"objet"			=> "VARCHAR (25) DEFAULT '' NOT NULL",
		"compress"	=> "tinyint NOT NULL",
		"fragment"	=> "longblob"  # ici c'est VRAIMENT un blob (on y stocke du gzip)
	);

	$spip_versions_fragments_key = array(
		"PRIMARY KEY"	=> "id_objet, objet, id_fragment, version_min"
	);


	$tables_auxiliaires['spip_versions'] = array(
		'field' => &$spip_versions,
		'key' => &$spip_versions_key);

	$tables_auxiliaires['spip_versions_fragments'] = array(
		'field' => &$spip_versions_fragments,
		'key' => &$spip_versions_fragments_key);

	return $tables_auxiliaires;
}

?>