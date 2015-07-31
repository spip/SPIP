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

	// Note: les mises à jour reexecutent ce code pour s'assurer
	// de la conformite de la base
	// pas de panique sur  "already exists" et "duplicate entry" donc.

	$fcreate = spip_abstract_serveur('create', $server);
	$finsert = spip_abstract_serveur('insert', $server);
	$fupdate = spip_abstract_serveur('update', $server);
	foreach($tables_principales as $k => $v)
		$fcreate($k, $v['field'], $v['key'], true);

	foreach($tables_auxiliaires as $k => $v)
		$fcreate($k, $v['field'], $v['key'], false);


	// Pas de panique avec les messages d'erreur a la mise a jour
	foreach($tables_images as $k => $v) {
		@$finsert('spip_types_documents',
		   "(extension, inclus, titre)",
		   '('. _q($k).", 'image'," . _q($v).')');
	}

	foreach($tables_sequences as $k => $v)
		@$finsert('spip_types_documents',
			 "(extension, titre, inclus)",
			 "('$k', '$v', 'embed')");

	foreach($tables_documents as $k => $v)
		@$finsert('spip_types_documents',
			 "(extension, titre, inclus)",
			 "('$k', '$v', 'non')");

	foreach ($tables_mime as $extension => $type_mime)
		@$fupdate('spip_types_documents',
			 "mime_type='$type_mime'",
			 "extension='$extension'");
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
