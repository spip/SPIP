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
function creer_base($serveur='') {
	global $tables_principales, $tables_auxiliaires;

	// Note: les mises a jour reexecutent ce code pour s'assurer
	// de la conformite de la base
	// pas de panique sur  "already exists" et "duplicate entry" donc.

	$fcreate = sql_serveur('create', $serveur);

	foreach($tables_principales as $k => $v)
		$fcreate($k, $v['field'], $v['key'], true, false, $serveur);

	foreach($tables_auxiliaires as $k => $v)
		$fcreate($k, $v['field'], $v['key'], false, false, $serveur);

	creer_base_types_doc($tables_principales['spip_types_documents'], $serveur);
}


function creer_base_types_doc($desc, $serveur='') {
	global $tables_images, $tables_sequences, $tables_documents, $tables_mime;
	// Init ou Re-init ==> replace pas insert

	$freplace = sql_serveur('replace', $serveur);
	foreach ($tables_mime as $extension => $type_mime) {
		if (isset($tables_images[$extension])) {$titre = $tables_images[$extension]; $inclus='image';}
		elseif (isset($tables_sequences[$extension])) {$titre = $tables_sequences[$extension]; $inclus='embed';}
		else {
		    $inclus='non';
		    if (isset($tables_documents[$extension]))
		      $titre = $tables_documents[$extension];
		    else  $titre = '';
		}
		$freplace('spip_types_documents',
			  array('mime_type' => $type_mime,
				'titre' => $titre,
				'inclus' => $inclus,
				'extension' => $extension),
			  $desc, $serveur);
	}
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
