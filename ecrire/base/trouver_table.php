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
	global $tables_principales, $tables_auxiliaires, $table_des_tables, $connexions;

	if (!spip_connect($serveur)
	OR !preg_match('/^[a-zA-Z0-9._-]+/',$nom))
		return null;
	$s = $serveur ? $serveur : 0;
	$nom_sql = $nom;

	if ($connexions[$s]['spip_connect_version']) {
		include_spip('public/interfaces');
		// base sous SPIP, le nom SQL peut etre autre
		if (isset($table_des_tables[$nom])) {
		  // indirection (table principale avec nom!=type)
			$t = $table_des_tables[$nom];
			$nom_sql = 'spip_' . $t;
			if (!isset($connexions[$s]['tables'][$nom_sql])) {
				include_spip('base/serial');
				$connexions[$s]['tables'][$nom_sql] = $tables_principales[$nom_sql];
				$connexions[$s]['tables'][$nom_sql]['table']= $nom_sql;
				$connexions[$s]['tables'][$nom_sql]['id_table']= $t;
			} # table principale deja vue, ok.
		} else {
			include_spip('base/auxiliaires');
			if (isset($tables_auxiliaires['spip_' .$nom])) {
				$nom_sql = 'spip_' . $nom;
				if (!isset($connexions[$s]['tables'][$nom_sql])) {
					$connexions[$s]['tables'][$nom_sql] = $tables_auxiliaires[$nom_sql];
					$connexions[$s]['tables'][$nom_sql]['table']= $nom_sql;
					$connexions[$s]['tables'][$nom_sql]['id_table']= $nom;
				} # table locale a cote de SPIP: noms egaux
			} # auxiliaire deja vue, ok.
		}
	}

	if (isset($connexions[$s]['tables'][$nom_sql]))
		return $connexions[$s]['tables'][$nom_sql];

	$desc = sql_showtable($nom_sql, $serveur, ($nom_sql != $nom));
	if (!$desc OR !$desc['field']) {
		  spip_log("table inconnue $serveur $nom");
		  return null;
	} else {
		$desc['table']= $nom_sql;
		$desc['id_table']= $nom;
	}
	return	$connexions[$s]['tables'][$nom_sql] = $desc;
}
?>
