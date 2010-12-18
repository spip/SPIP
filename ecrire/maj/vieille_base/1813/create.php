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
include_spip('maj/vieille_base/1813/inc_serialbase');
include_spip('maj/vieille_base/1813/inc_auxbase');
include_spip('maj/vieille_base/1813/inc_typebase');

function maj_vieille_base_1813_create() {
  global $tables_principales, $tables_auxiliaires, $tables_images, $tables_sequences, $tables_documents, $tables_mime;

	// ne pas revenir plusieurs fois (si, au contraire, il faut pouvoir
	// le faire car certaines mises a jour le demandent explicitement)
	# static $vu = false;
	# if ($vu) return; else $vu = true;

	foreach($tables_principales as $k => $v)
		spip_create_vieille_table($k, $v['field'], $v['key'], true);

	foreach($tables_auxiliaires as $k => $v)
		spip_create_vieille_table($k, $v['field'], $v['key'], false);

	foreach($tables_images as $k => $v)
		spip_query("INSERT IGNORE spip_types_documents (extension, inclus, titre, id_type) VALUES ('$k', 'image', '" .
			      (is_numeric($v) ?
			       (strtoupper($k) . "', $v") :
			       "$v', 0") .
			      ")");

	foreach($tables_sequences as $k => $v)
		spip_query("INSERT IGNORE spip_types_documents (extension, titre, inclus) VALUES ('$k', '$v', 'embed')");

	foreach($tables_documents as $k => $v)
		spip_query("INSERT IGNORE spip_types_documents (extension, titre, inclus) VALUES ('$k', '$v', 'non')");

	foreach ($tables_mime as $extension => $type_mime)
	  spip_query("UPDATE spip_types_documents
		SET mime_type='$type_mime' WHERE extension='$extension'");
}


?>
