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

// http://doc.spip.org/@creer_base
function creer_base($server='mysql') {
  global $tables_principales, $tables_auxiliaires, $tables_images, $tables_sequences, $tables_documents, $tables_mime;

	// ne pas revenir plusieurs fois (si, au contraire, il faut pouvoir
	// le faire car certaines mises a jour le demandent explicitement)
	# static $vu = false;
	# if ($vu) return; else $vu = true;

	$fcreate = 'spip_'  . $server . '_create';
	$finsert = 'spip_'  . $server . '_insert';
	$fupdate = 'spip_'  . $server . '_update';
	foreach($tables_principales as $k => $v)
		$fcreate($k, $v['field'], $v['key'], true);

	foreach($tables_auxiliaires as $k => $v)
		$fcreate($k, $v['field'], $v['key'], false);

	foreach($tables_images as $k => $v) {
		$finsert('spip_types_documents',
		   "(extension, inclus, titre)",
		   '('. _q($k).", 'image'," . _q($v).')');
	}

	foreach($tables_sequences as $k => $v)
		$finsert('spip_types_documents',
			 "(extension, titre, inclus)",
			 "('$k', '$v', 'embed')");

	foreach($tables_documents as $k => $v)
		$finsert('spip_types_documents',
			 "(extension, titre, inclus)",
			 "('$k', '$v', 'non')");

	foreach ($tables_mime as $extension => $type_mime)
		$fupdate('spip_types_documents',
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
