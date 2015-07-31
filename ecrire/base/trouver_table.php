<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
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

// http://doc.spip.org/@base_trouver_table_dist
function base_trouver_table_dist($nom, $serveur='')
{
	global $tables_principales, $tables_auxiliaires, $table_des_tables;

	if (!spip_connect($serveur)
	OR !preg_match('/^[a-zA-Z0-9._-]+/',$nom))
		return null;
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

	if (!isset($connexion['tables'][$nom_sql])) {
		// La *vraie* base a la priorite
		if (true /*  !$bdesc OR !$bdesc['field']  */) {
			$t = ($nom_sql != $nom);
			$desc = sql_showtable($nom_sql, $t, $serveur);
			if (!$desc OR !$desc['field']) {
				if (!$fdesc) {
					spip_log("table inconnue $serveur $nom");
					return null;
				}
				// on ne sait pas lire la structure de la table :
				// on retombe sur la description donnee dans les fichiers spip
				$desc = $fdesc;
			}
		}
		$desc['table']= $nom_sql;
		$desc['id_table']= $nom;
		$desc['connexion']= $serveur;
		$desc['titre'] = isset($GLOBALS['table_titre'][$nom])
		? $GLOBALS['table_titre'][$nom] : '';
		$connexion['tables'][$nom_sql] = $desc;
	}

	return $connexion['tables'][$nom_sql];
}
?>
