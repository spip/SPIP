<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Trouve la description d'une table, en particulier celle d'une boucle
// Si on ne la trouve pas, on demande au serveur SQL
// retourne False si lui non plus  ne la trouve pas.
// Si on la trouve, le tableau resultat a les entrees:
// field (comme dans serial.php)
// key (comme dans serial.php)
// table = nom SQL de la table (avec le prefixe spip_ pour les stds)
// id_table = nom SPIP de la table (i.e. type de boucle)
// le compilateur produit  FROM $r['table'] AS $r['id_table']
// Cette fonction intervient a la compilation, 
// mais aussi pour la balise contextuelle EXPOSE.
// l'ensemble des descriptions de table d'un serveur est stocke dans un fichier cache/sql_dec.txt
// par soucis de performance
// un appel avec $nom vide est une demande explicite de vidange du cache des descriptions

// http://doc.spip.org/@base_trouver_table_dist
function base_trouver_table_dist($nom, $serveur=''){
	static $nom_cache_desc_sql=array();
	global $tables_principales, $tables_auxiliaires, $table_des_tables;
	
	if (!spip_connect($serveur)
	OR !preg_match('/^[a-zA-Z0-9._-]*/',$nom))
		return null;
	if (!isset($nom_cache_desc_sql[$serveur]))
		$nom_cache_desc_sql[$serveur] = _DIR_CACHE . 'sql_desc' . ($serveur ? '_'.md5($serveur):'') . '.txt';

	// un appel avec $nom vide est une demande explicite de vidange du cache des descriptions
	if (!$nom){
		spip_unlink($nom_cache_desc_sql[$serveur]);
		$connexion['tables'] = array();
		return null;
	}

	$nom_sql = $nom;
	if (preg_match('/\.(.*)$/', $nom, $s))
		$nom_sql = $s[1];
	else
		$nom_sql = $nom;

	$desc = '';
	$connexion = &$GLOBALS['connexions'][$serveur ? $serveur : 0];

	// base sous SPIP: gerer les abreviations des noms de table
	if ($connexion['spip_connect_version']) {
		include_spip('public/interfaces');
		if (isset($table_des_tables[$nom])) {
			$nom = $table_des_tables[$nom];
			$nom_sql = 'spip_' . $nom;
		}
		if (!isset($connexion['tables'][$nom_sql])) {
			include_spip('base/serial');
			if (isset($tables_principales[$nom_sql]))
				$fdesc = $tables_principales[$nom_sql];
			else {
				include_spip('base/auxiliaires');
				if (isset($tables_auxiliaires['spip_' .$nom])) {
					$nom_sql = 'spip_' . $nom;
					$fdesc = $tables_auxiliaires[$nom_sql];
				}  # table locale a cote de SPIP, comme non SPIP:
			}
		}
	}

	// si c'est la premiere table qu'on cherche
	// et si on est pas explicitement en recalcul
	// on essaye de recharger le cache des decriptions de ce serveur
	// dans le fichier cache
	if (!isset($connexion['tables'][$nom_sql])
	  AND $GLOBALS['var_mode']!=='recalcul'
	  AND (!isset($connexion['tables']) OR !$connexion['tables'])) {
		if (lire_fichier($nom_cache_desc_sql[$serveur],$desc_cache)
		  AND $desc_cache=unserialize($desc_cache))
		  $connexion['tables'] = $desc_cache;
	}
	if (!isset($connexion['tables'][$nom_sql])) {
		
		// La *vraie* base a la priorite
		if (true /*  !$bdesc OR !$bdesc['field']  */) {
			$t = ($nom_sql != $nom);
			$desc = sql_showtable($nom_sql, $t, $serveur);
			if (!$desc OR !$desc['field']) {
				if (!$fdesc) {
					spip_log("trouver_table: table inconnue '$serveur' '$nom'");
					return null;
				}
				// on ne sait pas lire la structure de la table :
				// on retombe sur la description donnee dans les fichiers spip
				$desc = $fdesc;
			}
		}
		// S'il n'y a pas de key (cas d'une VIEW),
		// on va inventer une PRIMARY KEY en prenant le premier champ
		// de la table
		if (!$desc['key'])
			$desc['key']['PRIMARY KEY'] = array_shift(array_keys($desc['field']));

		$desc['table']= $nom_sql;
		$desc['id_table']= $nom;
		$desc['connexion']= $serveur;
		$desc['titre'] = isset($GLOBALS['table_titre'][$nom])
		? $GLOBALS['table_titre'][$nom] : '';
		$connexion['tables'][$nom_sql] = $desc;
		// une nouvelle table a ete descrite
		// mettons donc a jour le cache des descriptions de ce serveur
		if (is_writeable(_DIR_CACHE))
			ecrire_fichier($nom_cache_desc_sql[$serveur],serialize($connexion['tables']));
	}

	return $connexion['tables'][$nom_sql];
}
?>
