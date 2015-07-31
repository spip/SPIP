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

function import_init($request, $my_pos) {

	// au premier appel destruction des tables a restaurer
	return (!$my_pos) ? import_init_tables() : import_table_choix();
}

function insere_2_init($request, $my_pos) {

	// l'insertion ne porte que sur les tables principales
	$t = array_keys($GLOBALS['tables_principales']);
	// mais pas cette table car elle n'est pas extensible
	// (si on essaye ==> duplication sur la cle secondaire)
	unset($t[array_search('spip_types_documents', $t)]);
	// ni celle-ci a cause de la duplication des login 
	unset($t[array_search('spip_auteurs', $t)]);
	return $t;
}

function insere_1_init($request, $my_pos) {

  //  preparation de la table des translations
	$spip_translate = array(
		"type" 	     =>  "VARCHAR(16) NOT NULL",
                "id_old"     => "BIGINT (21) DEFAULT '0' NOT NULL",
                "id_new"    => "BIGINT (21) DEFAULT '0' NOT NULL");

	$spip_translate_key = array(
                "PRIMARY KEY"   => "id_old, id_new, type",
                "KEY id_old"        => "id_old");

	include_spip('base/create');
	spip_create_table('spip_translate', $spip_translate, $spip_translate_key, true);
	// au cas ou la derniere fois ce serait terminee anormalement
	spip_query("DELETE FROM spip_translate");
	return insere_2_init($request, $my_pos);
}

function translate_init($request, $my_pos=0) {
  /* 
   construire le tableau PHP de la table spip_translate
   (on l'a mis en table pour pouvoir reprendre apres interruption
   mais cette reprise n'est pas encore programmee)
  */
	$q = spip_query("SELECT * FROM spip_translate");
	$trans = array();
	while ($r = spip_fetch_array($q)) {
		$trans[$r['type']][$r['id_old']] = $r['id_new'];
	}
	return $trans;
}

// http://doc.spip.org/@inc_import_1_3_dist
function inc_import_1_3_dist($lecteur, $request, $gz=false, $trans=array()) {
	global $import_ok, $abs_pos, $my_pos, $tables_trans;
	static $tables = '';
	static $phpmyadmin, $fin;
	static $field_desc = array ();
	static $defaut = array('field' => array());

	// au premier appel, init des invariants de boucle 

	if (!$tables OR $trans) {
		$init = $request['init'];
		$tables = $init($request, $my_pos);
		$phpmyadmin = preg_match("{^phpmyadmin::}is",
			$GLOBALS['meta']['version_archive_restauration'])
			? array(array('&quot;','&gt;'),array('"','>'))
			: false;
		$fin = '/' . $GLOBALS['meta']['tag_archive_restauration'];
	}

	$b = '';

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
// Ne memoriser que la cle primaire pour le premier tour de l'insertion.
// car les autres valeurs rentreraient en conflit avec les presentes
				$p = $desc['key']["PRIMARY KEY"];
				$desc['field'] = array($p => $desc['field'][$p]);
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
	if ($values) {
		if (!$boucle($values, $new, $desc, $trans)) {
			$GLOBALS['erreur_restauration'] = spip_sql_error();
		}
	}

	return $import_ok = $new;
}

function import_replace($values, $table, $desc, $trans) {
	return spip_query("REPLACE $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',',$values) . ')');
}

function import_insere($values, $table, $desc, $trans) {
	// reserver une place dans les tables principales
	$n = spip_abstract_insert($table, '', '()');
	// et memoriser la correspondance dans la table auxilaire
	if ($n) {
		$type_id = $desc['key']["PRIMARY KEY"];
		$n = spip_abstract_insert('spip_translate',
			"(id_old, id_new, type)",
			"(". $values[$type_id] .",$n,'$type_id')");
	}
	return $n;
}

function import_translate($values, $table, $desc, $trans) {
	$vals = '';

	foreach ($values as $k => $v) {

		if ($k=='id_parent' OR $k=='id_secteur') $k = 'id_rubrique';

		if (isset($trans[$k]) AND isset($trans[$k][$v])) {
			$v = $trans[$k][$v];
		}
		$vals .= ",$v";
	}
	return spip_query("REPLACE $table (" . join(',',array_keys($values)) . ') VALUES (' .substr($vals,1) . ')');
}

function import_lire_champs($f, $fields, $gz, $phpmyadmin, $table)
{
	$values = array();
	for (;;) {
		$b = '';
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col[0] == '/') { 
			if ($col != $table) 
		    // autre tag fermant ici est une erreur de format
				spip_log("restauration : table $table tag fermant $col innatendu");
			break;
		}
		$value = '';
		if (!xml_fetch_tag($f, $value, $gz)) return false;

		if ( ($col != 'maj') AND (isset($fields[$col])) ) {
			if ($phpmyadmin)
				$value = str_replace($phpmyadmin[0],$phpmyadmin[1],$value);
			$values[$col]= _q($value);
		}
	}

	return $values;
}
?>
