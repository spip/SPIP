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

// http://doc.spip.org/@base_auxiliaires
function base_auxiliaires(&$tables_auxiliaires){
$spip_resultats = array(
 		"recherche"	=> "char(16) DEFAULT '' NOT NULL",
		"id"	=> "INT UNSIGNED NOT NULL",
 		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"maj"	=> "TIMESTAMP" );

$spip_resultats_key = array(
// pas de cle ni index, ca fait des insertions plus rapides et les requetes jointes utilisees en recheche ne sont pas plus lentes ...
);

$spip_auteurs_liens = array(
		"id_auteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_objet"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"objet"	=> "VARCHAR (25) DEFAULT '' NOT NULL",
		"vu"	=> "ENUM('non', 'oui') DEFAULT 'non' NOT NULL");

$spip_auteurs_liens_key = array(
		"PRIMARY KEY"		=> "id_auteur,id_objet,objet",
		"KEY id_auteur"	=> "id_auteur");

$spip_meta = array(
		"nom"	=> "VARCHAR (255) NOT NULL",
		"valeur"	=> "text DEFAULT ''",
		"impt"	=> "ENUM('non', 'oui') DEFAULT 'oui' NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_meta_key = array(
		"PRIMARY KEY"	=> "nom");

$tables_auxiliaires['spip_auteurs_liens'] = array(
	'field' => &$spip_auteurs_liens,
	'key' => &$spip_auteurs_liens_key);

$tables_auxiliaires['spip_meta'] = array(
	'field' => &$spip_meta,
	'key' => &$spip_meta_key);
$tables_auxiliaires['spip_resultats'] = array(
	'field' => &$spip_resultats,
	'key' => &$spip_resultats_key);
	
	$tables_auxiliaires = pipeline('declarer_tables_auxiliaires',$tables_auxiliaires);
}

global $tables_auxiliaires;
base_auxiliaires($tables_auxiliaires);
?>
