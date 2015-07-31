<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
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

// Fonction de creation d'une table SQL nommee $nom
// a partir de 2 tableaux PHP :
// champs: champ => type
// cles: type-de-cle => champ(s)
// si $autoinc, c'est une auto-increment (i.e. serial) sur la Primary Key
// Le nom des caches doit etre inferieur a 64 caracteres

function spip_create_table($nom, $champs, $cles, $autoinc=false, $temporary=false) {
	$query = ''; $keys = ''; $s = ''; $p='';

	foreach($cles as $k => $v) {
		$keys .= "$s\n\t\t$k ($v)";
		if ($k == "PRIMARY KEY")
			$p = $v;
		$s = ",";
	}
	$s = '';

	foreach($champs as $k => $v) {
		$query .= "$s\n\t\t$k $v" .
		(($autoinc && ($p == $k)) ? " auto_increment" : '');
		$s = ",";
	}
	$temporary = $temporary ? 'TEMPORARY':'';
	spip_query_db("CREATE $temporary TABLE IF NOT EXISTS $nom ($query" . ($keys ? ",$keys" : '') . ")\n");
}


function creer_base() {
  global $tables_principales, $tables_auxiliaires, $tables_images, $tables_sequences, $tables_documents, $tables_mime;

	// ne pas revenir plusieurs fois (si, au contraire, il faut pouvoir
	// le faire car certaines mises a jour le demandent explicitement)
	# static $vu = false;
	# if ($vu) return; else $vu = true;

	foreach($tables_principales as $k => $v)
		spip_create_table($k, $v['field'], $v['key'], true);

	foreach($tables_auxiliaires as $k => $v)
		spip_create_table($k, $v['field'], $v['key'], false);

	foreach($tables_images as $k => $v)
		spip_query_db("INSERT IGNORE INTO spip_types_documents (extension, inclus, titre, id_type) VALUES ('$k', 'image', '" .
			      (is_numeric($v) ?
			       (strtoupper($k) . "', $v") :
			       "$v', 0") .
			      ")");

	foreach($tables_sequences as $k => $v)
		spip_query_db("INSERT IGNORE INTO spip_types_documents (extension, titre, inclus) VALUES ('$k', '$v', 'embed')");

	foreach($tables_documents as $k => $v)
		spip_query_db("INSERT IGNORE INTO spip_types_documents (extension, titre, inclus) VALUES ('$k', '$v', 'non')");

	foreach ($tables_mime as $extension => $type_mime)
	  spip_query_db("UPDATE spip_types_documents
		SET mime_type='$type_mime' WHERE extension='$extension'");
}

function stripslashes_base($table, $champs) {
	$modifs = '';
	reset($champs);
	while (list(, $champ) = each($champs)) {
		$modifs[] = $champ . '=REPLACE(REPLACE(' .$champ. ',"\\\\\'", "\'"), \'\\\\"\', \'"\')';
	}
	spip_query("UPDATE $table SET ".join(',', $modifs));

}

?>
