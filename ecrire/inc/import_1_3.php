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


// http://doc.spip.org/@insere_2_init
function insere_2_init($request) {

	// l'insertion porte sur les tables principales ...
	$t = array_keys($GLOBALS['tables_principales']);
	// ... mais pas cette table car elle n'est pas extensible ..
	// (si on essaye ==> duplication sur la cle secondaire)
	unset($t[array_search('spip_types_documents', $t)]);
	// .. ni celle-ci a cause de la duplication des login 
	unset($t[array_search('spip_auteurs', $t)]);
	// et les tables auxiliaires sur les mots car on sait les identifier
	$t[]= 'spip_mots_articles';
	$t[]= 'spip_mots_breves';
	$t[]= 'spip_mots_rubriques';
	$t[]= 'spip_mots_syndic';
	$t[]= 'spip_mots_forum';
	$t[]= 'spip_mots_documents';

	return $t;
}

// http://doc.spip.org/@insere_1_init
function insere_1_init($request) {

  //  preparation de la table des translations
	$spip_translate = array(
		"type" 	     =>  "VARCHAR(16) NOT NULL",
		"titre"	     =>  "text NOT NULL",
                "id_old"     => "BIGINT (21) DEFAULT '0' NOT NULL",
                "id_new"    => "BIGINT (21) DEFAULT '0' NOT NULL");

	$spip_translate_key = array(
                "PRIMARY KEY"   => "id_old, id_new, type",
                "KEY id_old"        => "id_old");

	include_spip('base/create');
	spip_create_table('spip_translate', $spip_translate, $spip_translate_key, true);
	// au cas ou la derniere fois ce serait terminee anormalement
	spip_query("DELETE FROM spip_translate");
	return insere_2_init($request);
}

// http://doc.spip.org/@translate_init
function translate_init($request) {
  /* 
   construire le tableau PHP de la table spip_translate
   (mis en table pour pouvoir reprendre apres interruption)
  */
	$q = spip_query("SELECT * FROM spip_translate");
	$trans = array();
	while ($r = spip_fetch_array($q)) {
		$trans[$r['type']][$r['id_old']] = array($r['id_new'], $r['titre']);
	}
	return $trans;
}

// http://doc.spip.org/@inc_import_1_3_dist
function inc_import_1_3_dist($lecteur, $request, $gz=false, $trans=array()) {
	global $import_ok, $abs_pos, $tables_trans;
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
// car les autres cles rentreraient en conflit avec les presentes
// Prendre le champ titre pour pouvoir identifier
				$b = array();
				if (isset($desc['field'][$p='titre']))
					$b[$p]= $desc['field'][$p];
				if (isset($desc['field'][$p='id_groupe']))
					$b[$p]= $desc['field'][$p];
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
	if ($values) $boucle($values, $new, $desc, $request, $trans);

	return $import_ok = $new;
}

/* --------- les 3 fonctions possibles pour l'index "boucle" -- */

// http://doc.spip.org/@import_replace
function import_replace($values, $table, $desc, $request, $trans) {
	if (!spip_query("REPLACE $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',',$values) . ')'))
		$GLOBALS['erreur_restauration'] = spip_sql_error();
}

// http://doc.spip.org/@import_insere
function import_insere($values, $table, $desc, $request, $trans) {
	$type_id = $desc['key']["PRIMARY KEY"];

	// reserver une place dans les tables principales si nouveau
	if ((!function_exists($f = 'import_identifie_' . $type_id))
	OR (!$n = $f($values, $table, $desc, $request, $trans))) {
		$n = spip_abstract_insert($table, '', '()');
		if (!$n)
			$GLOBALS['erreur_restauration'] = spip_sql_error();
	}

	// et memoriser la correspondance dans la table auxilaire
	// si different et pas de recherche dessus plus tard
	if ($n AND (($n != $values[$type_id]) OR $table != 'id_groupe')) {
		if (is_array($n))
		  list($id, $titre) = $n; // _q() deja applique sur $titre
		else {$id = $n; $titre = "''";}
		spip_abstract_insert('spip_translate',
				"(id_old, id_new, titre, type)",
				"(". $values[$type_id] .",$id, $titre, '$type_id')");
	}
}

// http://doc.spip.org/@import_translate
function import_translate($values, $table, $desc, $request, $trans) {
	$vals = '';

	foreach ($values as $k => $v) {

		if ($k=='id_parent' OR $k=='id_secteur') $k = 'id_rubrique';

		if (isset($trans[$k]) AND isset($trans[$k][$v])) {
			list($g, $titre) = $trans[$k][$v];
			if ($g < 0) {
			  // cas du  parent a verifier en plus du titre
				if (!($g = import_identifie_mot_si_groupe(0-$g, $titre, $trans)))
					$g = spip_abstract_insert($table, '', '()');
			// On connait enfin le nouveau numero.
			// Mise a jour de spip_translate pas indispensable,
			// on evite: si vraiment une interrupt a lieu et
			// retombe dessus, elle recalculera, et alors ?
				$trans[$k][$v] = array($g, $titre);

			}
			$v = $g;
		}

		$vals .= ",$v";
	}
	return spip_query("REPLACE $table (" . join(',',array_keys($values)) . ') VALUES (' .substr($vals,1) . ')');
}

/* --------- Fin des 3 fonctions possibles pour l'index "boucle" -- */

// deux groupes de mots ne peuvent avoir le meme titre ==> identification
function import_identifie_id_groupe($values, $table, $desc, $request, $trans) {
  // _q() deja appliquee
	$n = spip_fetch_array(spip_query("SELECT id_groupe FROM spip_groupes_mots WHERE titre=" . $values['titre']));
	return $n ? $n['id_groupe'] : false;
}

// pour un mot le titre est insuffisant, il faut aussi l'identite du groupe.
// Memoriser ces 2 infos et le signaler a import_translate grace a 1 negatif
function import_identifie_id_mot($values, $table, $desc, $request, $trans) {
	return array((0 - $values['id_groupe']), $values['titre']);
}

// mot de meme et de meme groupe ==> identification
function import_identifie_mot_si_groupe($id_groupe, $titre, $trans)
{
	if (!(isset($trans['id_groupe'])
	AND isset($trans['id_groupe'][$id_groupe])))
		return false;

	$new = $trans['id_groupe'][$id_groupe][0];

	$r = spip_fetch_array(spip_query($q = "SELECT id_mot, id_groupe FROM spip_mots WHERE titre=" . _q($titre) . " AND id_groupe=$new" ));

	return !$r ? false  : $r['id_mot'];
}

// http://doc.spip.org/@import_lire_champs
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
