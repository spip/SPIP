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

include_spip('inc/acces');
include_spip('base/serial');
include_spip('base/auxiliaires');
include_spip('base/typedoc');
include_spip('base/abstract_sql');

// http://doc.spip.org/@creer_ou_upgrader_table
function creer_ou_upgrader_table($table,$desc,$autoinc,$upgrade=false,$serveur='') {
	$sql_desc = $upgrade ? sql_showtable($table,true,$serveur) : false;
	if (!$sql_desc)
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

function alterer_base($tables_inc, $tables_noinc, $up=false, $serveur='')
{
	if ($up === false) {
		$old = false;
		$up = array();
	} else {
		$old = true;
		if (!is_array($up)) $up = array($up);
	}
	foreach($tables_inc as $k => $v)
		if (!$old OR in_array($k, $up))
			creer_ou_upgrader_table($k,$v,true,$old,$serveur);

	foreach($tables_noinc as $k => $v)
		if (!$old OR in_array($k, $up))
			creer_ou_upgrader_table($k,$v,false,$old,$serveur);
}

// http://doc.spip.org/@creer_base
function creer_base($serveur='') {

	// Note: les mises a jour reexecutent ce code pour s'assurer
	// de la conformite de la base
	// pas de panique sur  "already exists" et "duplicate entry" donc.

	alterer_base($GLOBALS['tables_principales'],
		     $GLOBALS['tables_auxiliaires'],
		     false,
		     $serveur);
}

// http://doc.spip.org/@maj_tables
function maj_tables($upgrade_tables=array(),$serveur=''){
	alterer_base($GLOBALS['tables_principales'],
		     $GLOBALS['tables_auxiliaires'],
		     $upgrade_tables,
		     $serveur);
}

// http://doc.spip.org/@creer_base_types_doc
function creer_base_types_doc($serveur='') {
	global $tables_images, $tables_sequences, $tables_documents, $tables_mime;
	// Init ou Re-init ==> replace pas insert

	$freplace = sql_serveur('replace', $serveur);
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

		$freplace('spip_types_documents',
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
