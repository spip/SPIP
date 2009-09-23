<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/acces');
include_spip('base/serial');
include_spip('base/auxiliaires');
include_spip('base/typedoc');
include_spip('base/abstract_sql');


// http://doc.spip.org/@creer_ou_upgrader_table
function creer_ou_upgrader_table($table,$desc,$autoinc,$upgrade=false,$serveur='') {
	$sql_desc = sql_showtable($table,true,$serveur);
	if (!$upgrade OR !$sql_desc)
		sql_create($table, $desc['field'], $desc['key'], $autoinc, false, $serveur);
	else {
		// ajouter les champs manquants
		$last = '';
		foreach($desc['field'] as $field=>$type){
			if (!isset($sql_desc['field'][$field]))
				sql_alter("TABLE $table ADD $field $type".($last?" AFTER $last":""),$serveur);
			$last = $field;
		}
	}
}

// http://doc.spip.org/@creer_base
function creer_base($serveur='') {
	global $tables_principales, $tables_auxiliaires;

	// Note: les mises a jour reexecutent ce code pour s'assurer
	// de la conformite de la base
	// pas de panique sur  "already exists" et "duplicate entry" donc.

	foreach($tables_principales as $k => $v)
		creer_ou_upgrader_table($k,$v,true,false,$serveur);

	foreach($tables_auxiliaires as $k => $v)
		creer_ou_upgrader_table($k,$v,false,false,$serveur);
}

// http://doc.spip.org/@maj_tables
function maj_tables($upgrade_tables=array(),$serveur=''){
	global $tables_principales, $tables_auxiliaires;
	foreach($tables_principales as $k => $v)
		if (($upgrade_tables==$k OR (is_array($upgrade_tables) && in_array($k,$upgrade_tables))))
			creer_ou_upgrader_table($k,$v,true,true,$serveur);

	foreach($tables_auxiliaires as $k => $v)
		if (($upgrade_tables==$k OR (is_array($upgrade_tables) && in_array($k,$upgrade_tables))))
			creer_ou_upgrader_table($k,$v,false,true,$serveur);
}


// http://doc.spip.org/@creer_base_types_doc
function creer_base_types_doc($serveur='') {
	global $tables_images, $tables_sequences, $tables_documents, $tables_mime;

	foreach ($tables_mime as $extension => $type_mime) {
		if (isset($tables_images[$extension])) {
			$titre = $tables_images[$extension];
			$inclus='image';
		}
		else if (isset($tables_sequences[$extension])) {
			$titre = $tables_sequences[$extension];
			$inclus='embed';
		}
		else {
			$inclus='non';
			if (isset($tables_documents[$extension]))
				$titre = $tables_documents[$extension];
			else
				$titre = '';
		}
		// Init ou Re-init ==> replace pas insert
		sql_replace('spip_types_documents',
			array('mime_type' => $type_mime,
				'titre' => $titre,
				'inclus' => $inclus,
				'extension' => $extension,
				'upload' => 'oui'
			),
			'', $serveur);
	}
}
?>
