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

function inc_import_1_3_dist($f, $gz=false) {
  global $import_ok, $pos, $abs_pos, $my_pos;
	static $tables = '';

	global $tables_trans;
	static $primary_table;
	static $relation_liste;
	global $tables_principales;
	global $tables_auxiliaires;

	// au premier appel, detruire les tables a restaurer

	if (!$tables)
		$tables = (!$my_pos) ? import_init_tables() : import_table_choix();

	$phpmyadmin = preg_match("{^phpmyadmin::}is", $GLOBALS['meta']['version_archive_restauration']);
	$tag_fermant = $GLOBALS['meta']['tag_archive_restauration'];
	$import_ok = false;

	$b = '';
	// Lire le type d'objet
	if (!($table = xml_fetch_tag($f, $b, $gz))) return false;
	if ($table == ('/'.$tag_fermant)) return !($import_ok = true);
	#spip_log("import_objet_1_3 : table $table");
	if (!isset($primary_table[$table]))
		$primary_table[$table]=primary_index_table($table);

	$primary = $primary_table[$table];
	$id_objet = 0;
	$liens = array();

	// Lire les champs de l'objet
	for (;;) {
		$b = '';
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col == '/'.$table) break;
		if (substr($col,0,1) == '/')
		{ // tag fermant ici : probleme erreur de format
			spip_log('restauration : table $table tag fermanr $col innatendu');
		  break;
		}
		$value = '';
		if (!xml_fetch_tag($f, $value, $gz)) return false;

		if ($col != 'maj') {
			if ($phpmyadmin)
				$value = str_replace(array('&quot;','&gt;'),array('"','>'),$value);
			$cols[] = $col;
			$values[] = spip_abstract_quote($value);
			if ($col == $primary) $id_objet = $value;
		}
	}
	
	if (isset($tables_trans[$table])) $table = $tables_trans[$table];
#	spip_log("import_objet_1_3 : $table " . in_array($table,$tables));
	if (in_array($table,$tables)){


		if (!spip_query("REPLACE $table (" . join(',', $cols) . ') VALUES (' . join(',', $values) . ')')) {
			echo "--><br><font color='red'><b>"._T('avis_erreur_mysql')."</b></font>\n<font color='black'><tt>".spip_sql_error()."</tt></font>\n<!--";
			$GLOBALS['erreur_restauration'] = true;
		}
	}


	return $import_ok = " $table ";
}
?>
