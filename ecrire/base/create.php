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

include_spip('inc/acces');
include_spip('base/serial');
include_spip('base/auxiliaires');
include_spip('base/typedoc');
include_spip('base/abstract_sql');

// http://doc.spip.org/@creer_base
function creer_base($server='') {
	global $tables_principales, $tables_auxiliaires, $tables_images, $tables_sequences, $tables_documents, $tables_mime;

	// Note: les mises a jour reexecutent ce code pour s'assurer
	// de la conformite de la base
	// pas de panique sur  "already exists" et "duplicate entry" donc.

	$fcreate = sql_serveur('create', $server);
	$freplace = sql_serveur('replace', $server);
	$fupdate = sql_serveur('update', $server);
	foreach($tables_principales as $k => $v)
		$fcreate($k, $v['field'], $v['key'], true);

	foreach($tables_auxiliaires as $k => $v)
		$fcreate($k, $v['field'], $v['key'], false);


	// Init ou Re-init ==> replace pas insert
	$desc = $tables_principales['spip_types_documents'];
	foreach($tables_images as $k => $v) {
		$freplace('spip_types_documents',
			 array('extension' => $k,
			       'inclus' => 'image',
			       'titre' => $v),
			 $desc);
	}

	foreach($tables_sequences as $k => $v)
		$freplace('spip_types_documents',
			 array('extension' => $k,
			       'titre' => $v,
			       'inclus'=> 'embed'),
			 $desc);

	foreach($tables_documents as $k => $v)
		$freplace('spip_types_documents',
			 array('extension' => $k,
			       'titre' => $v,
			       'inclus' => 'non'),
			 $desc);

	foreach ($tables_mime as $extension => $type_mime)
		$fupdate('spip_types_documents',
			 'mime_type = '._q($type_mime),
			 'extension='._q($extension)
		);
}

// http://doc.spip.org/@stripslashes_base
function stripslashes_base($table, $champs) {
	$modifs = '';
	reset($champs);
	while (list(, $champ) = each($champs)) {
		$modifs[] = $champ . '=REPLACE(REPLACE(' .$champ. ',"\\\\\'", "\'"), \'\\\\"\', \'"\')';
	}
	spip_query("UPDATE $table SET ".join(',', $modifs));

}

?>
