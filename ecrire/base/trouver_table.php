<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('base/objets');

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
// l'ensemble des descriptions de table d'un serveur est stocke dans un fichier cache/sql_desc.txt
// par soucis de performance
// un appel avec $nom vide est une demande explicite de vidange du cache des descriptions

// http://doc.spip.org/@base_trouver_table_dist
function base_trouver_table_dist($nom, $serveur='', $table_spip = true){
	static $nom_cache_desc_sql=array();
	global $tables_principales, $tables_auxiliaires, $table_des_tables;

	if (!spip_connect($serveur)
	OR !preg_match('/^[a-zA-Z0-9._-]*/',$nom))
		return null;

	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$objets_sql = lister_tables_objets_sql("::md5");

	// le nom du cache depend du serveur mais aussi du nom de la db et du prefixe
	// ce qui permet une auto invalidation en cas de modif manuelle du fichier
	// de connexion, et tout risque d'ambiguite
	if (!isset($nom_cache_desc_sql[$serveur][$objets_sql])){
		$nom_cache_desc_sql[$serveur][$objets_sql] =
		  _DIR_CACHE . 'sql_desc_'
		  . ($serveur ? "{$serveur}_":"")
		  . substr(md5($connexion['db'].":".$connexion['prefixe'].":$objets_sql"),0,8)
			.'.txt';
		// nouveau nom de cache = nouvelle version en memoire
		unset($connexion['tables']);
	}

	// un appel avec $nom vide est une demande explicite de vidange du cache des descriptions
	if (!$nom){
		spip_unlink($nom_cache_desc_sql[$serveur][$objets_sql]);
		$connexion['tables'] = array();
		return null;
	}

	$nom_sql = $nom;
	if (preg_match('/\.(.*)$/', $nom, $s))
		$nom_sql = $s[1];
	else
		$nom_sql = $nom;

	$fdesc = $desc = '';
	$connexion = &$GLOBALS['connexions'][$serveur ? $serveur : 0];

	// base sous SPIP: gerer les abreviations explicites des noms de table
	if ($connexion['spip_connect_version']) {
		if (isset($table_des_tables[$nom])) {
			$nom = $table_des_tables[$nom];
			$nom_sql = 'spip_' . $nom;
		}
	}

	// si c'est la premiere table qu'on cherche
	// et si on est pas explicitement en recalcul
	// on essaye de recharger le cache des decriptions de ce serveur
	// dans le fichier cache
	if (!isset($connexion['tables'][$nom_sql])
	  AND defined('_VAR_MODE') AND _VAR_MODE!=='recalcul'
	  AND (!isset($connexion['tables']) OR !$connexion['tables'])) {
		if (lire_fichier($nom_cache_desc_sql[$serveur][$objets_sql],$desc_cache)
		  AND $desc_cache=unserialize($desc_cache))
		  $connexion['tables'] = $desc_cache;
	}
	if (!isset($connexion['tables'][$nom_sql])) {

		if (isset($tables_principales[$nom_sql]))
			$fdesc = $tables_principales[$nom_sql];
		// meme si pas d'abreviation declaree, trouver la table spip_$nom
		// si c'est une table principale,
		// puisqu'on le fait aussi pour les tables auxiliaires
		elseif ($nom_sql==$nom AND isset($tables_principales['spip_' .$nom])){
			$nom_sql = 'spip_' . $nom;
			$fdesc = &$tables_principales[$nom_sql];
		}
		else {
			if (isset($tables_auxiliaires[$n=$nom])
			  OR isset($tables_auxiliaires[$n='spip_'.$nom])) {
				$nom_sql = $n;
				$fdesc = &$tables_auxiliaires[$n];
			}  # table locale a cote de SPIP, comme non SPIP:
		}
	}
	if (!isset($connexion['tables'][$nom_sql])) {

		// La *vraie* base a la priorite
		$desc = sql_showtable($nom_sql, $table_spip, $serveur);
		if (!$desc OR !$desc['field']) {
			if (!$fdesc) {
				spip_log("trouver_table: table inconnue '$serveur' '$nom'",_LOG_INFO_IMPORTANTE);
				return null;
			}
			// on ne sait pas lire la structure de la table :
			// on retombe sur la description donnee dans les fichiers spip
			$desc = $fdesc;
		}
		else
			$desc['exist'] = true;

		$desc['table'] = $desc['table_sql'] = $nom_sql;
		$desc['connexion']= $serveur;

		// charger les infos declarees pour cette table
		// en lui passant les infos connues
		// $desc est prioritaire pour la description de la table
		$desc = array_merge(lister_tables_objets_sql($nom_sql,$desc),$desc);

		// si tables_objets_sql est bien fini d'init, on peut cacher
		$connexion['tables'][$nom_sql] = $desc;
		$res = &$connexion['tables'][$nom_sql];
		// une nouvelle table a ete decrite
		// mettons donc a jour le cache des descriptions de ce serveur
		if (is_writeable(_DIR_CACHE))
			ecrire_fichier($nom_cache_desc_sql[$serveur][$objets_sql],serialize($connexion['tables']),true);
	}
	else
		$res = &$connexion['tables'][$nom_sql];

	// toujours retourner $nom dans id_table
	$res['id_table']=$nom;

	return $res;
}
?>
