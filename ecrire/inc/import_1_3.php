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

// http://doc.spip.org/@description_table
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

// http://doc.spip.org/@inc_import_1_3_dist
function inc_import_1_3_dist($lecteur, $request, $gz='fread') {
  global $import_ok, $tables_trans,  $trans;
	static $tables = '';
	static $phpmyadmin, $fin;
	static $field_desc = array ();
	static $defaut = array('field' => array());

	// au premier appel, init des invariants de boucle 

	if (!$tables OR $trans) {
		$init = $request['init'];
		$tables = $init($request);
		$phpmyadmin = preg_match("{^phpmyadmin::}is",
			$GLOBALS['meta']['version_archive_restauration'])
			? array(array('&quot;','&gt;'),array('"','>'))
			: false;
		$fin = '/' . $GLOBALS['meta']['tag_archive_restauration'];
	}

	$b = false;
	if (!($table = xml_fetch_tag($lecteur, $b, $gz))) return false;
	if ($table == $fin) return !($import_ok = true);
	$new = isset($tables_trans[$table]) ? $tables_trans[$table]: $table; 

	// indique a la fois la fonction a appliquer
	// et les infos qu'il faut lui communiquer
	$boucle = $request['boucle'];

	if (!in_array($new,$tables))
		$field_desc[$boucle][$table] = $desc = $defaut;
	elseif (isset($field_desc[$boucle][$table]))
		$desc = $field_desc[$boucle][$table];
	else {
// recuperer la description de la table pour connaitre ses champs valides
		list($nom,$desc) = description_table($table);
		if (!isset($desc['field']))
			$desc = $defaut;
		else {
			if ($request['insertion']=='on') {
// Au premier tour de l'insertion, ne memoriser que le strict necessaire 
// pour pouvoir identifier avec l'existant.
// (Faudrait convenir d'une structure de donnees, c'est lourd & inextensible)
				$b = array();
				if (isset($desc['field'][$p='titre']))
					$b[$p]= $desc['field'][$p];
				if (isset($desc['field'][$p='id_groupe']))
					$b[$p]= $desc['field'][$p];
				if (isset($desc['field'][$p='id_parent']))
					$b[$p]= $desc['field'][$p];
				if (isset($desc['field'][$p='id_rubrique']))
					$b[$p]= $desc['field'][$p];
				if (isset($desc['field'][$p='fichier'])) {
					$b[$p]= $desc['field'][$p];
					$b['taille']= $desc['field']['taille'];
				}
				$p = $desc['key']["PRIMARY KEY"];
				$b[$p] = $desc['field'][$p];
				$desc['field'] = $b; 
			}
		}
		$field_desc[$boucle][$table] = $desc;
	}

	$values = import_lire_champs($lecteur,
				     $desc['field'],
				     $gz,
				     $phpmyadmin,
				     '/' . $table);

	if ($values === false) return  ($import_ok = false);
	if ($values) $boucle($values, $new, $desc, $request);

	return $import_ok = $new;
}

// http://doc.spip.org/@import_replace
function import_replace($values, $table, $desc, $request) {
	static $where=array();
	if (!isset($desc['field']['impt'])) // pas de champ de gestion d'import
		if (!spip_query("REPLACE $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',',array_map('_q', $values)) . ')')) {
			$GLOBALS['erreur_restauration'] = spip_sql_error();
	  }
	else { 
		// la table contient un champ 'impt' qui permet de gerer des interdiction d'overwrite par import
		// impt=oui : la ligne est surchargeable par import
		// impt=non : la ligne ne doit pas etre ecrasee par un import
		// on essaye un insert si jamais la primary existe pas
		if (!spip_query("INSERT $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',',array_map('_q', $values)) . ')')) {
			// il faut gerer l'existence de la primary, et l'autorisation ou non de mettre a jour
			if (!isset($where[$table])){
				if (!isset($desc["PRIMARY KEY"]))
					$GLOBALS['erreur_restauration'] = "champ 'impt' sans cle primaire sur la table $table";
				else {
					$keys = $desc["PRIMARY KEY"];
					$keys = explode(",",$keys);
					if (!is_array($keys)) $keys = array($keys);
					$w = "";
					foreach($keys as $key){
						if (!isset($values[$key])){
							$GLOBALS['erreur_restauration'] = "champ $key manquant a l'import sur la table $table";
							$w .= " AND 0=1";
							continue;
						}
						$w .= " AND $key="._q($values[$key]);
					}
					$where[$table] = strlen($w)?substr($w,6):"0=1";
				}
			}
			if (isset($where[$table])) {
				$set = "";
				foreach($values as $key=>$value) $set .= ",$key="._q($value);
				$set = substr($set,1);
				if (!spip_query("UPDATE $table SET $set WHERE ".$where[$table]." AND impt='oui'")) {
					$GLOBALS['erreur_restauration'] = spip_sql_error();
				}
			}
		}
	}
}

// http://doc.spip.org/@import_lire_champs
function import_lire_champs($f, $fields, $gz, $phpmyadmin, $table)
{
	$values = array();
	
	$char = $GLOBALS['meta']['charset_insertion'];
	if ($char == $GLOBALS['meta']['charset_restauration']) $char = '';

	for (;;) {
		$b = false;
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col[0] == '/') { 
			if ($col != $table) {
				spip_log("restauration de la table $table, tag fermant inattendu:");
				spip_log($col);
		  }
			break;
		}
		$value = $b = (($col != 'maj') AND (isset($fields[$col])));
		if (!xml_fetch_tag($f, $value, $gz)) return false;

		if ($b) {
			if ($phpmyadmin)
				$value = str_replace($phpmyadmin[0],$phpmyadmin[1],$value);
			if ($char) 
				$value = importer_charset($value, $charset);
			$values[$col]= $value;
		}
	}

	return $values;
}
?>
