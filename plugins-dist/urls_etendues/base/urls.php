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

/**
 * Declarer les interfaces
 * 
 * @param array $interfaces
 * @return array
 */
function urls_declarer_tables_interfaces($interfaces) {
	$interfaces['table_des_tables']['urls']='urls';
	return $interfaces;
}
/**
 * Tables de jointures
 *
 * @param array $tables_auxiliaires
 * @return array
 */
function urls_declarer_tables_auxiliaires($tables_auxiliaires){

	$spip_urls = array(
		// un id parent eventuel, pour discriminer les doublons arborescents
		"id_parent"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"url"			=> "VARCHAR(255) NOT NULL",
		// la table cible
		"type"			=> "varchar(15) DEFAULT 'article' NOT NULL",
		// l'id dans la table
		"id_objet"		=> "bigint(21) NOT NULL",
		// pour connaitre la plus recente.
		// ATTENTION, pas on update CURRENT_TIMESTAMP implicite
		// et pas le nom maj, surinterprete par inc/import_1_3
		"date"			=> "DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL",
		// nombre de segments dans url
		"segments"	=> "SMALLINT(3) DEFAULT '1' NOT NULL",
		// URL permanente, prioritaire
		"perma"     => "TINYINT(1) DEFAULT '0' NOT NULL",
	);

	$spip_urls_key = array(
		"PRIMARY KEY"		=> "id_parent, url",
		"KEY type"		=> "type, id_objet");

	$tables_auxiliaires['spip_urls'] = array(
		'field' => &$spip_urls,
		'key' => &$spip_urls_key);

	return $tables_auxiliaires;
}


?>