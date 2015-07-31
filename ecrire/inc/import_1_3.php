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

function description_table($nom){
	global $tables_principales, $tables_auxiliaires, $table_des_tables, $tables_des_serveurs_sql;

	$nom_table = $nom;
	if (in_array($nom, $table_des_tables))
	   $nom_table = 'spip_' . $nom;

	include_spip('base/serial');
	if (isset($tables_principales[$nom_table]))
		return array($nom_table, $tables_principales[$nom_table]);

	include_spip('base/auxiliaires');
	$nom_table = 'spip_' . $nom;
	if (isset($tables_auxiliaires[$nom_table]))
		return array($nom_table, $tables_auxiliaires[$nom_table]);

	if ($desc = spip_abstract_showtable($nom, '', true))
	  if (isset($desc['field'])) {
	    return array($nom, $desc);
	  }
	return array($nom,array());
}


function inc_import_1_3_dist($f, $gz=false) {
  global $import_ok, $abs_pos, $my_pos;
	static $tables = '';
	static $field_desc = array ();

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

	if (!isset($field_desc[$table])){
		// recuperer la description de la table pour connaitre ses champs valides
		list($nom,$desc) = description_table($table);
		if (isset($desc['field']))
			$field_desc[$table] = $desc['field'];
		else
			$field_desc[$table] = NULL;
	}
	$fields = $field_desc[$table];

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

		if ( ($col != 'maj')
			&& ($fields==NULL or isset($fields[$col])) ) {
			if ($phpmyadmin)
				$value = str_replace(array('&quot;','&gt;'),array('"','>'),$value);
			$values[$col] = spip_abstract_quote($value);
		}
	}
	
	if (isset($tables_trans[$table])) $table = $tables_trans[$table];
#	spip_log("import_objet_1_3 : $table " . in_array($table,$tables));
	if (in_array($table,$tables)){

		if (!spip_query("REPLACE $table (" . join(',', array_keys($values)) . ') VALUES (' . join(',', $values) . ')')) {
			echo "--><br><font color='red'><b>"._T('avis_erreur_mysql')."</b></font>\n<font color='black'><tt>".spip_sql_error()."</tt></font>\n<!--";
			$GLOBALS['erreur_restauration'] = true;
		}
	}


	return $import_ok = " $table ";
}
?>
