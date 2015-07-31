<?php

/* *************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Ce fichier contient les fonctions gerant
 * les instructions SQL pour Sqlite
 *
 * @package SPIP\SQL\SQLite
 */
 
if (!defined('_ECRIRE_INC_VERSION')) return;

// TODO: get/set_caracteres ?


/*
 * 
 * regroupe le maximum de fonctions qui peuvent cohabiter
 * D'abord les fonctions d'abstractions de SPIP
 * 
 */
// http://doc.spip.org/@req_sqlite_dist
function req_sqlite_dist($addr, $port, $login, $pass, $db = '', $prefixe = '', $sqlite_version = ''){
	static $last_connect = array();

	// si provient de selectdb
	// un code pour etre sur que l'on vient de select_db()
	if (strpos($db, $code = '@selectdb@')!==false){
		foreach (array('addr', 'port', 'login', 'pass', 'prefixe') as $a){
			$$a = $last_connect[$a];
		}
		$db = str_replace($code, '', $db);
	}

	/*
	 * En sqlite, seule l'adresse du fichier est importante.
	 * Ce sera $db le nom,
	 * le path est $addr
	 * (_DIR_DB si $addr est vide)
	 */
	_sqlite_init();

	// determiner le dossier de la base : $addr ou _DIR_DB
	$f = _DIR_DB;
	if ($addr AND strpos($addr, '/')!==false)
		$f = rtrim($addr, '/').'/';

	// un nom de base demande et impossible d'obtenir la base, on s'en va :
	// il faut que la base existe ou que le repertoire parent soit writable
	if ($db AND !is_file($f .= $db.'.sqlite') AND !is_writable(dirname($f))){
		spip_log("base $f non trouvee ou droits en ecriture manquants", 'sqlite.'._LOG_HS);
		return false;
	}

	// charger les modules sqlite au besoin
	if (!_sqlite_charger_version($sqlite_version)){
		spip_log("Impossible de trouver/charger le module SQLite ($sqlite_version)!", 'sqlite.'._LOG_HS);
		return false;
	}

	// chargement des constantes
	// il ne faut pas definir les constantes avant d'avoir charge les modules sqlite
	$define = "spip_sqlite".$sqlite_version."_constantes";
	$define();

	$ok = false;
	if (!$db){
		// si pas de db ->
		// base temporaire tant qu'on ne connait pas son vrai nom
		// pour tester la connexion
		$db = "_sqlite".$sqlite_version."_install";
		$tmp = _DIR_DB.$db.".sqlite";
		if ($sqlite_version==3){
			$ok = $link = new PDO("sqlite:$tmp");
		} else {
			$ok = $link = sqlite_open($tmp, _SQLITE_CHMOD, $err);
		}
	} else {
		// Ouvrir (eventuellement creer la base)
		// si pas de version fourni, on essaie la 3, sinon la 2
		if ($sqlite_version==3){
			$ok = $link = new PDO("sqlite:$f");
		} else {
			$ok = $link = sqlite_open($f, _SQLITE_CHMOD, $err);
		}
	}

	if (!$ok){
		$e = sqlite_last_error($db);
		spip_log("Impossible d'ouvrir la base SQLite($sqlite_version) $f : $e", 'sqlite.'._LOG_HS);
		return false;
	}

	if ($link){
		$last_connect = array(
			'addr' => $addr,
			'port' => $port,
			'login' => $login,
			'pass' => $pass,
			'db' => $db,
			'prefixe' => $prefixe,
		);
		// etre sur qu'on definit bien les fonctions a chaque nouvelle connexion
		include_spip('req/sqlite_fonctions');
		_sqlite_init_functions($link);
	}

	return array(
		'db' => $db,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $link,
	);
}


/**
 * Fonction de requete generale, munie d'une trace a la demande
 *
 * @param string $query
 * 		Requete a executer
 * @param string $serveur
 * 		Nom du connecteur
 * @param bool $requeter
 * 		Effectuer la requete ?
 * 		- true pour executer
 * 		- false pour retourner le texte de la requete
 * @return bool|SQLiteResult|string
 * 		Resultat de la requete
 */
function spip_sqlite_query($query, $serveur = '', $requeter = true){
	#spip_log("spip_sqlite_query() > $query",'sqlite.'._LOG_DEBUG);
	#_sqlite_init(); // fait la premiere fois dans spip_sqlite
	$query = spip_sqlite::traduire_requete($query, $serveur);
	if (!$requeter) return $query;
	return spip_sqlite::executer_requete($query, $serveur);
}


/* ordre alphabetique pour les autres */

// http://doc.spip.org/@spip_sqlite_alter
function spip_sqlite_alter($query, $serveur = '', $requeter = true){

	$query = spip_sqlite_query("ALTER $query", $serveur, false);
	// traduire la requete pour recuperer les bons noms de table
	$query = spip_sqlite::traduire_requete($query, $serveur);

	/*
		 * la il faut faire les transformations
		 * si ALTER TABLE x (DROP|CHANGE) y
		 *
		 * 1) recuperer "ALTER TABLE table "
		 * 2) spliter les sous requetes (,)
		 * 3) faire chaque requete independemment
		 */

	// 1
	if (preg_match("/\s*(ALTER(\s*IGNORE)?\s*TABLE\s*([^\s]*))\s*(.*)?/is", $query, $regs)){
		$debut = $regs[1];
		$table = $regs[3];
		$suite = $regs[4];
	} else {
		spip_log("SQLite : Probleme de ALTER TABLE mal forme dans $query", 'sqlite.'._LOG_ERREUR);
		return false;
	}

	// 2
	// il faudrait une regexp pour eviter de spliter ADD PRIMARY KEY (colA, colB)
	// tout en cassant "ADD PRIMARY KEY (colA, colB), ADD INDEX (chose)"... en deux
	// ou revoir l'api de sql_alter en creant un 
	// sql_alter_table($table,array($actions));
	$todo = explode(',', $suite);

	// on remet les morceaux dechires ensembles... que c'est laid !
	$todo2 = array();
	$i = 0;
	$ouverte = false;
	while ($do = array_shift($todo)){
		$todo2[$i] = isset($todo2[$i]) ? $todo2[$i].",".$do : $do;
		$o = (false!==strpos($do, "("));
		$f = (false!==strpos($do, ")"));
		if ($o AND !$f) $ouverte = true;
		elseif ($f) $ouverte = false;
		if (!$ouverte) $i++;
	}

	// 3	
	$resultats = array();
	foreach ($todo2 as $do){
		$do = trim($do);
		if (!preg_match('/(DROP PRIMARY KEY|DROP KEY|DROP INDEX|DROP COLUMN|DROP'
		                .'|CHANGE COLUMN|CHANGE|MODIFY|RENAME TO|RENAME'
		                .'|ADD PRIMARY KEY|ADD KEY|ADD INDEX|ADD UNIQUE KEY|ADD UNIQUE'
		                .'|ADD COLUMN|ADD'
		                .')\s*([^\s]*)\s*(.*)?/i', $do, $matches)){
			spip_log("SQLite : Probleme de ALTER TABLE, utilisation non reconnue dans : $do \n(requete d'origine : $query)", 'sqlite.'._LOG_ERREUR);
			return false;
		}

		$cle = strtoupper($matches[1]);
		$colonne_origine = $matches[2];
		$colonne_destination = '';

		$def = $matches[3];

		// eluder une eventuelle clause before|after|first inutilisable
		$defr = rtrim(preg_replace('/(BEFORE|AFTER|FIRST)(.*)$/is', '', $def));
		$defo = $defr; // garder la def d'origine pour certains cas
		// remplacer les definitions venant de mysql
		$defr = _sqlite_remplacements_definitions_table($defr);

		// reinjecter dans le do
		$do = str_replace($def, $defr, $do);
		$def = $defr;

		switch ($cle) {
			// suppression d'un index
			case 'DROP KEY':
			case 'DROP INDEX':
				$nom_index = $colonne_origine;
				spip_sqlite_drop_index($nom_index, $table, $serveur);
				break;

			// suppression d'une pk
			case 'DROP PRIMARY KEY':
				if (!_sqlite_modifier_table(
					$table,
					$colonne_origine,
					array('key' => array('PRIMARY KEY' => '')),
					$serveur)){
					return false;
				}
				break;
			// suppression d'une colonne
			case 'DROP COLUMN':
			case 'DROP':
				if (!_sqlite_modifier_table(
					$table,
					array($colonne_origine => ""),
					'',
					$serveur)){
					return false;
				}
				break;

			case 'CHANGE COLUMN':
			case 'CHANGE':
				// recuperer le nom de la future colonne
			  // on reprend la def d'origine car _sqlite_modifier_table va refaire la translation
			  // en tenant compte de la cle primaire (ce qui est mieux)
				$def = trim($defo);
				$colonne_destination = substr($def, 0, strpos($def, ' '));
				$def = substr($def, strlen($colonne_destination)+1);

				if (!_sqlite_modifier_table(
					$table,
					array($colonne_origine => $colonne_destination),
					array('field' => array($colonne_destination => $def)),
					$serveur)){
					return false;
				}
				break;

			case 'MODIFY':
			  // on reprend la def d'origine car _sqlite_modifier_table va refaire la translation
			  // en tenant compte de la cle primaire (ce qui est mieux)
				if (!_sqlite_modifier_table(
					$table,
					$colonne_origine,
					array('field' => array($colonne_origine => $defo)),
					$serveur)){
					return false;
				}
				break;

			// pas geres en sqlite2
			case 'RENAME':
				$do = "RENAME TO".substr($do, 6);
			case 'RENAME TO':
				if (_sqlite_is_version(3, '', $serveur)){
					if (!spip_sqlite::executer_requete("$debut $do", $serveur)){
						spip_log("SQLite : Erreur ALTER TABLE / RENAME : $query", 'sqlite.'._LOG_ERREUR);
						return false;
					}
					// artillerie lourde pour sqlite2 !
				} else {
					$table_dest = trim(substr($do, 9));
					if (!_sqlite_modifier_table(array($table => $table_dest), '', '', $serveur)){
						spip_log("SQLite : Erreur ALTER TABLE / RENAME : $query", 'sqlite.'._LOG_ERREUR);
						return false;
					}
				}
				break;

			// ajout d'une pk
			case 'ADD PRIMARY KEY':
				$pk = trim(substr($do, 16));
				$pk = ($pk[0]=='(') ? substr($pk, 1, -1) : $pk;
				if (!_sqlite_modifier_table(
					$table,
					$colonne_origine,
					array('key' => array('PRIMARY KEY' => $pk)),
					$serveur)){
					return false;
				}
				break;
			// ajout d'un index
			case 'ADD UNIQUE KEY':
			case 'ADD UNIQUE':
				$unique=true;
			case 'ADD INDEX':
			case 'ADD KEY':
				// peut etre "(colonne)" ou "nom_index (colonnes)"
				// bug potentiel si qqn met "(colonne, colonne)"
				//
				// nom_index (colonnes)
				if ($def){
					$colonnes = substr($def, 1, -1);
					$nom_index = $colonne_origine;
				}
				else {
					// (colonne)
					if ($colonne_origine[0]=="("){
						$colonnes = substr($colonne_origine, 1, -1);
						if (false!==strpos(",", $colonnes)){
							spip_log(_LOG_GRAVITE_ERREUR, "SQLite : Erreur, impossible de creer un index sur plusieurs colonnes"
							                              ." sans qu'il ait de nom ($table, ($colonnes))", 'sqlite');
							break;
						} else {
							$nom_index = $colonnes;
						}
					}
						// nom_index
					else {
						$nom_index = $colonnes = $colonne_origine;
					}
				}
				spip_sqlite_create_index($nom_index, $table, $colonnes, $unique, $serveur);
				break;

			// pas geres en sqlite2
			case 'ADD COLUMN':
				$do = "ADD".substr($do, 10);
			case 'ADD':
			default:
				if (_sqlite_is_version(3, '', $serveur) AND !preg_match(',primary\s+key,i',$do)){
					if (!spip_sqlite::executer_requete("$debut $do", $serveur)){
						spip_log("SQLite : Erreur ALTER TABLE / ADD : $query", 'sqlite.'._LOG_ERREUR);
						return false;
					}
					break;

				}
				// artillerie lourde pour sqlite2 !
				// ou si la colonne est aussi primary key
				// cas du add id_truc int primary key
				// ajout d'une colonne qui passe en primary key directe
				else {
					$def = trim(substr($do, 3));
					$colonne_ajoutee = substr($def, 0, strpos($def, ' '));
					$def = substr($def, strlen($colonne_ajoutee)+1);
					$opts = array();
					if (preg_match(',primary\s+key,i',$def)){
						$opts['key'] = array('PRIMARY KEY' => $colonne_ajoutee);
						$def = preg_replace(',primary\s+key,i','',$def);
					}
					$opts['field'] = array($colonne_ajoutee => $def);
					if (!_sqlite_modifier_table($table, array($colonne_ajoutee), $opts, $serveur)){
						spip_log("SQLite : Erreur ALTER TABLE / ADD : $query", 'sqlite.'._LOG_ERREUR);
						return false;
					}
				}
				break;
		}
		// tout est bon, ouf !
		spip_log("SQLite ($serveur) : Changements OK : $debut $do", 'sqlite.'._LOG_INFO);
	}

	spip_log("SQLite ($serveur) : fin ALTER TABLE OK !", 'sqlite.'._LOG_INFO);
	return true;
}


/**
 * Fonction de creation d'une table SQL nommee $nom
 * http://doc.spip.org/@spip_sqlite_create
 *
 * @param string $nom
 * @param array $champs
 * @param array $cles
 * @param bool $autoinc
 * @param bool $temporary
 * @param string $serveur
 * @param bool $requeter
 * @return bool|SQLiteResult|string
 */
function spip_sqlite_create($nom, $champs, $cles, $autoinc = false, $temporary = false, $serveur = '', $requeter = true){
	$query = _sqlite_requete_create($nom, $champs, $cles, $autoinc, $temporary, $ifnotexists = true, $serveur, $requeter);
	if (!$query) return false;
	$res = spip_sqlite_query($query, $serveur, $requeter);

	// SQLite ne cree pas les KEY sur les requetes CREATE TABLE
	// il faut donc les faire creer ensuite
	if (!$requeter) return $res;

	$ok = $res ? true : false;
	if ($ok){
		foreach ($cles as $k => $v){
			if (preg_match(',^(KEY|UNIQUE)\s,i',$k,$m)){
				$index = trim(substr($k,strlen($m[1])));
				$unique = (strlen($m[1])>3);
				$ok &= spip_sqlite_create_index($index, $nom, $v, $unique, $serveur);
			}
		}
	}
	return $ok ? true : false;
}

/**
 * Fonction pour creer une base de donnees SQLite
 *
 * @param string $nom le nom de la base (sans l'extension de fichier)
 * @param string $serveur le nom de la connexion
 * @param string $option options
 *
 * @return bool true si la base est creee.
 **/
function spip_sqlite_create_base($nom, $serveur = '', $option = true){
	$f = $nom.'.sqlite';
	if (strpos($nom, "/")===false)
		$f = _DIR_DB.$f;
	if (_sqlite_is_version(2, '', $serveur)){
		$ok = sqlite_open($f, _SQLITE_CHMOD, $err);
	} else {
		$ok = new PDO("sqlite:$f");
	}
	if ($ok){
		unset($ok);
		return true;
	}
	unset($ok);
	return false;
}


/**
 * Fonction de creation d'une vue SQL nommee $nom
 * http://doc.spip.org/@spip_sqlite_create_view
 *
 * @param string $nom
 * 		Nom de la vue a creer
 * @param string $query_select
 * 		Texte de la requete de selection servant de base a la vue
 * @param string $serveur
 * 		Nom du connecteur
 * @param bool $requeter
 * 		Effectuer la requete ?
 * 		- true pour executer
 * 		- false pour retourner le texte de la requete
 * @return bool|SQLiteResult|string
 * 		Resultat de la requete ou
 * 		- false si erreur ou si la vue existe deja
 * 		- string texte de la requete si $requeter vaut false
 */
function spip_sqlite_create_view($nom, $query_select, $serveur = '', $requeter = true){
	if (!$query_select) return false;
	// vue deja presente
	if (sql_showtable($nom, false, $serveur)){
		spip_log("Echec creation d'une vue sql ($nom) car celle-ci existe deja (serveur:$serveur)", 'sqlite.'._LOG_ERREUR);
		return false;
	}

	$query = "CREATE VIEW $nom AS ".$query_select;
	return spip_sqlite_query($query, $serveur, $requeter);
}

/**
 * Fonction de creation d'un INDEX
 *
 * @param string $nom : nom de l'index
 * @param string $table : table sql de l'index
 * @param string/array $champs : liste de champs sur lesquels s'applique l'index
 * @param string $serveur : nom de la connexion sql utilisee
 * @param bool $requeter : true pour executer la requete ou false pour retourner le texte de la requete
 *
 * @return bool ou requete
 */
function spip_sqlite_create_index($nom, $table, $champs, $unique='', $serveur = '', $requeter = true){
	if (!($nom OR $table OR $champs)){
		spip_log("Champ manquant pour creer un index sqlite ($nom, $table, (".join(',', $champs)."))", 'sqlite.'._LOG_ERREUR);
		return false;
	}

	// SQLite ne differentie pas noms des index en fonction des tables
	// il faut donc creer des noms uniques d'index pour une base sqlite
	$nom = $table.'_'.$nom;
	// enlever d'eventuelles parentheses deja presentes sur champs
	if (!is_array($champs)){
		if ($champs[0]=="(") $champs = substr($champs, 1, -1);
		$champs = array($champs);
		// supprimer l'info de longueur d'index mysql en fin de champ
		$champs = preg_replace(",\(\d+\)$,","",$champs);
	}

	$ifnotexists = "";
	$version = spip_sqlite_fetch(spip_sqlite_query("select sqlite_version() AS sqlite_version",$serveur),'',$serveur);
	if (!function_exists('spip_version_compare')) include_spip('plugins/installer');

	if ($version AND spip_version_compare($version['sqlite_version'],'3.3.0','>=')) {
		$ifnotexists = ' IF NOT EXISTS';
	} else {
		/* simuler le IF EXISTS - version 2 et sqlite < 3.3a */
		$a = spip_sqlite_showtable($table, $serveur);
		if (isset($a['key']['KEY '.$nom])) return true;
	}

	$query = "CREATE ".($unique?"UNIQUE ":"")."INDEX$ifnotexists $nom ON $table (".join(',', $champs).")";
	$res = spip_sqlite_query($query, $serveur, $requeter);
	if (!$requeter) return $res;
	if ($res)
		return true;
	else
		return false;
}

/**
 * en PDO/sqlite3, il faut calculer le count par une requete count(*)
 * pour les resultats de SELECT
 * cela est fait sans spip_sqlite_query()
 * http://doc.spip.org/@spip_sqlite_count
 *
 * @param  $r
 * @param string $serveur
 * @param bool $requeter
 * @return int
 */
function spip_sqlite_count($r, $serveur = '', $requeter = true){
	if (!$r) return 0;

	if (_sqlite_is_version(3, '', $serveur)){
		// select ou autre (insert, update,...) ?

		// (link,requete) a compter
		if (is_array($r->spipSqliteRowCount)){
			list($link,$query) = $r->spipSqliteRowCount;
			// amelioration possible a tester intensivement : pas de order by pour compter !
			// $query = preg_replace(",ORDER BY .+(LIMIT\s|HAVING\s|GROUP BY\s|$),Uims","\\1",$query);
			$query = "SELECT count(*) as zzzzsqlitecount FROM ($query)";
			$l = $link->query($query);
			$i = 0;
			if ($l AND $z = $l->fetch())
				$i = $z['zzzzsqlitecount'];
			$r->spipSqliteRowCount = $i;
		}
		if (isset($r->spipSqliteRowCount)){
			// Ce compte est faux s'il y a des limit dans la requete :(
			// il retourne le nombre d'enregistrements sans le limit
			return $r->spipSqliteRowCount;
		} else {
			return $r->rowCount();
		}
	} else {
		return sqlite_num_rows($r);
	}
}


// http://doc.spip.org/@spip_sqlite_countsel
function spip_sqlite_countsel($from = array(), $where = array(), $groupby = '', $having = array(), $serveur = '', $requeter = true){
	$c = !$groupby ? '*' : ('DISTINCT '.(is_string($groupby) ? $groupby : join(',', $groupby)));
	$r = spip_sqlite_select("COUNT($c)", $from, $where, '', '', '',
	                        $having, $serveur, $requeter);
	if ((is_resource($r) or is_object($r)) && $requeter){ // ressource : sqlite2, object : sqlite3
		if (_sqlite_is_version(3, '', $serveur)){
			list($r) = spip_sqlite_fetch($r, SPIP_SQLITE3_NUM, $serveur);
		} else {
			list($r) = spip_sqlite_fetch($r, SPIP_SQLITE2_NUM, $serveur);
		}

	}
	return $r;
}


// http://doc.spip.org/@spip_sqlite_delete
function spip_sqlite_delete($table, $where = '', $serveur = '', $requeter = true){
	$res = spip_sqlite_query(
		_sqlite_calculer_expression('DELETE FROM', $table, ',')
		._sqlite_calculer_expression('WHERE', $where),
		$serveur, $requeter);

	// renvoyer la requete inerte si demandee
	if (!$requeter) return $res;

	if ($res){
		$link = _sqlite_link($serveur);
		if (_sqlite_is_version(3, $link)){
			return $res->rowCount();
		} else {
			return sqlite_changes($link);
		}
	}
	else
		return false;
}


// http://doc.spip.org/@spip_sqlite_drop_table
function spip_sqlite_drop_table($table, $exist = '', $serveur = '', $requeter = true){
	if ($exist) $exist = " IF EXISTS";

	/* simuler le IF EXISTS - version 2 */
	if ($exist && _sqlite_is_version(2, '', $serveur)){
		$a = spip_sqlite_showtable($table, $serveur);
		if (!$a) return true;
		$exist = '';
	}
	if (spip_sqlite_query("DROP TABLE$exist $table", $serveur, $requeter))
		return true;
	else
		return false;
}

/**
 * supprime une vue
 * http://doc.spip.org/@spip_sqlite_drop_view
 *
 * @param  $view
 * @param string $exist
 * @param string $serveur
 * @param bool $requeter
 * @return bool|SQLiteResult|string
 */
function spip_sqlite_drop_view($view, $exist = '', $serveur = '', $requeter = true){
	if ($exist) $exist = " IF EXISTS";

	/* simuler le IF EXISTS - version 2 */
	if ($exist && _sqlite_is_version(2, '', $serveur)){
		$a = spip_sqlite_showtable($view, $serveur);
		if (!$a) return true;
		$exist = '';
	}

	return spip_sqlite_query("DROP VIEW$exist $view", $serveur, $requeter);
}

/**
 * Fonction de suppression d'un INDEX
 *
 * @param string $nom : nom de l'index
 * @param string $table : table sql de l'index
 * @param string $serveur : nom de la connexion sql utilisee
 * @param bool $requeter : true pour executer la requete ou false pour retourner le texte de la requete
 *
 * @return bool ou requete
 */
function spip_sqlite_drop_index($nom, $table, $serveur = '', $requeter = true){
	if (!($nom OR $table)){
		spip_log("Champ manquant pour supprimer un index sqlite ($nom, $table)", 'sqlite.'._LOG_ERREUR);
		return false;
	}

	// SQLite ne differentie pas noms des index en fonction des tables
	// il faut donc creer des noms uniques d'index pour une base sqlite
	$index = $table.'_'.$nom;
	$exist = " IF EXISTS";

	/* simuler le IF EXISTS - version 2 */
	if (_sqlite_is_version(2, '', $serveur)){
		$a = spip_sqlite_showtable($table, $serveur);
		if (!isset($a['key']['KEY '.$nom])) return true;
		$exist = '';
	}

	$query = "DROP INDEX$exist $index";
	return spip_sqlite_query($query, $serveur, $requeter);
}

/**
 * Retourne la dernière erreur generée
 *
 * @param $serveur
 * 		nom de la connexion
 * @return string
 * 		erreur eventuelle
 **/
function spip_sqlite_error($query = '', $serveur = ''){
	$link = _sqlite_link($serveur);

	if (_sqlite_is_version(3, $link)){
		$errs = $link->errorInfo();
		/*
			$errs[0]
				numero SQLState ('HY000' souvent lors d'une erreur)
				http://www.easysoft.com/developer/interfaces/odbc/sqlstate_status_return_codes.html
			$errs[1]
				numéro d'erreur SQLite (souvent 1 lors d'une erreur)
				http://www.sqlite.org/c3ref/c_abort.html
			$errs[2]
				Le texte du message d'erreur
		*/
		$s = '';
		if (ltrim($errs[0],'0')) { // 00000 si pas d'erreur
			$s = "$errs[2]";
		}
	} elseif ($link) {
		$s = sqlite_error_string(sqlite_last_error($link));
	} else {
		$s = ": aucune ressource sqlite (link)";
	}
	if ($s) spip_log("$s - $query", 'sqlite.'._LOG_ERREUR);
	return $s;
}

/**
 * Retourne le numero de la dernière erreur SQL
 *
 * Le numéro (en sqlite3/pdo) est un retour ODBC tel que (très souvent) HY000
 * http://www.easysoft.com/developer/interfaces/odbc/sqlstate_status_return_codes.html
 * 
 * @param string $serveur
 * 		nom de la connexion
 * @return int|string
 * 		0 pas d'erreur
 * 		1 ou autre erreur (en sqlite 2)
 * 		'HY000/1' : numéro de l'erreur SQLState / numéro d'erreur interne SQLite (en sqlite 3)
 **/
function spip_sqlite_errno($serveur = ''){
	$link = _sqlite_link($serveur);

	if (_sqlite_is_version(3, $link)){
		$t = $link->errorInfo();
		$s = ltrim($t[0],'0'); // 00000 si pas d'erreur
		if ($s) $s .= ' / ' . $t[1]; // ajoute l'erreur du moteur SQLite
	} elseif ($link) {
		$s = sqlite_last_error($link);
	} else {
		$s = ": aucune ressource sqlite (link)";
	}

	if ($s) spip_log("Erreur sqlite $s", 'sqlite.'._LOG_ERREUR);

	return $s ? $s : 0;
}


// http://doc.spip.org/@spip_sqlite_explain
function spip_sqlite_explain($query, $serveur = '', $requeter = true){
	if (strpos(ltrim($query), 'SELECT')!==0) return array();

	$query = spip_sqlite::traduire_requete($query, $serveur);
	$query = 'EXPLAIN '.$query;
	if (!$requeter) return $query;
	// on ne trace pas ces requetes, sinon on obtient un tracage sans fin...
	$r = spip_sqlite::executer_requete($query, $serveur, false);

	return $r ? spip_sqlite_fetch($r, null, $serveur) : false; // hum ? etrange ca... a verifier
}


// http://doc.spip.org/@spip_sqlite_fetch
function spip_sqlite_fetch($r, $t = '', $serveur = '', $requeter = true){

	$link = _sqlite_link($serveur);
	$is_v3 = _sqlite_is_version(3, $link);
	if (!$t)
		$t = ($is_v3 ? SPIP_SQLITE3_ASSOC : SPIP_SQLITE2_ASSOC);

	$retour = false;
	if ($r)
		$retour = ($is_v3 ? $r->fetch($t) : sqlite_fetch_array($r, $t));

	// les version 2 et 3 parfois renvoie des 'table.titre' au lieu de 'titre' tout court ! pff !
	// suppression de 'table.' pour toutes les cles (c'est un peu violent !)
	// c'est couteux : on ne verifie que la premiere ligne pour voir si on le fait ou non
	if ($retour
	  AND strpos(implode('',array_keys($retour)),'.')!==false){
		foreach ($retour as $cle => $val){
			if (($pos = strpos($cle, '.'))!==false){
				$retour[substr($cle, $pos+1)] = &$retour[$cle];
				unset($retour[$cle]);
			}
		}
	}

	return $retour;
}


function spip_sqlite_seek($r, $row_number, $serveur = '', $requeter = true){
	if ($r){
		$link = _sqlite_link($serveur);
		if (_sqlite_is_version(3, $link)){
			// encore un truc de bien fichu : PDO ne PEUT PAS faire de seek ou de rewind...
			// je me demande si pour sqlite 3 il ne faudrait pas mieux utiliser
			// les nouvelles fonctions sqlite3_xx (mais encore moins presentes...)
			return false;
		}
		else {
			return sqlite_seek($r, $row_number);
		}
	}
}


// http://doc.spip.org/@spip_sqlite_free
function spip_sqlite_free(&$r, $serveur = '', $requeter = true){
	unset($r);
	return true;
	//return sqlite_free_result($r);
}


// http://doc.spip.org/@spip_sqlite_get_charset
function spip_sqlite_get_charset($charset = array(), $serveur = '', $requeter = true){
	//$c = !$charset ? '' : (" LIKE "._q($charset['charset']));
	//return spip_sqlite_fetch(sqlite_query(_sqlite_link($serveur), "SHOW CHARACTER SET$c"), NULL, $serveur);
}


// http://doc.spip.org/@spip_sqlite_hex
function spip_sqlite_hex($v){
	return hexdec($v);
}


// http://doc.spip.org/@spip_sqlite_in
function spip_sqlite_in($val, $valeurs, $not = '', $serveur = '', $requeter = true){
	$n = $i = 0;
	$in_sql = "";
	while ($n = strpos($valeurs, ',', $n+1)){
		if ((++$i)>=255){
			$in_sql .= "($val $not IN (".
			           substr($valeurs, 0, $n).
			           "))\n".
			           ($not ? "AND\t" : "OR\t");
			$valeurs = substr($valeurs, $n+1);
			$i = $n = 0;
		}
	}
	$in_sql .= "($val $not IN ($valeurs))";

	return "($in_sql)";
}


// http://doc.spip.org/@spip_sqlite_insert
function spip_sqlite_insert($table, $champs, $valeurs, $desc = '', $serveur = '', $requeter = true){

	$query = "INSERT INTO $table ".($champs ? "$champs VALUES $valeurs" : "DEFAULT VALUES");
	if ($r = spip_sqlite_query($query, $serveur, $requeter)){
		if (!$requeter) return $r;
		$nb = spip_sqlite::last_insert_id($serveur);
	}
	else
		$nb = 0;

	$err = spip_sqlite_error($query, $serveur);
	// cas particulier : ne pas substituer la reponse spip_sqlite_query si on est en profilage
	return isset($_GET['var_profile']) ? $r : $nb;

}


// http://doc.spip.org/@spip_sqlite_insertq
function spip_sqlite_insertq($table, $couples = array(), $desc = array(), $serveur = '', $requeter = true){
	if (!$desc) $desc = description_table($table, $serveur);
	if (!$desc) die("$table insertion sans description");
	$fields = isset($desc['field']) ? $desc['field'] : array();

	foreach ($couples as $champ => $val){
		$couples[$champ] = _sqlite_calculer_cite($val, $fields[$champ]);
	}

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$couples = _sqlite_ajouter_champs_timestamp($table, $couples, $desc, $serveur);

	$cles = $valeurs = "";
	if (count($couples)){
		$cles = "(".join(',', array_keys($couples)).")";
		$valeurs = "(".join(',', $couples).")";
	}

	return spip_sqlite_insert($table, $cles, $valeurs, $desc, $serveur, $requeter);
}


// http://doc.spip.org/@spip_sqlite_insertq_multi
function spip_sqlite_insertq_multi($table, $tab_couples = array(), $desc = array(), $serveur = '', $requeter = true){
	if (!$desc) $desc = description_table($table, $serveur);
	if (!$desc) die("$table insertion sans description");
	if (!isset($desc['field']))
		$desc['field'] = array();

	// recuperer les champs 'timestamp' pour mise a jour auto de ceux-ci
	$maj = _sqlite_ajouter_champs_timestamp($table, array(), $desc, $serveur);

	// seul le nom de la table est a traduire ici :
	// le faire une seule fois au debut
	$query_start = "INSERT INTO $table ";
	$query_start = spip_sqlite::traduire_requete($query_start,$serveur);

	// ouvrir une transaction
	if ($requeter)
		spip_sqlite::demarrer_transaction($serveur);

	while ($couples = array_shift($tab_couples)){
		foreach ($couples as $champ => $val){
			$couples[$champ] = _sqlite_calculer_cite($val, $desc['field'][$champ]);
		}

		// inserer les champs timestamp par defaut
		$couples = array_merge($maj,$couples);

		$champs = $valeurs = "";
		if (count($couples)){
			$champs = "(".join(',', array_keys($couples)).")";
			$valeurs = "(".join(',', $couples).")";
			$query = $query_start."$champs VALUES $valeurs";
		}
		else
			$query = $query_start."DEFAULT VALUES";
		
		if ($requeter)
			$retour = spip_sqlite::executer_requete($query,$serveur);

		// sur le dernier couple uniquement
		if (!count($tab_couples)){
			$nb = 0;
			if ($requeter)
				$nb = spip_sqlite::last_insert_id($serveur);
			else
				return $query;
		}

		$err = spip_sqlite_error($query, $serveur);
	}

	if ($requeter)
		spip_sqlite::finir_transaction($serveur);

	// renvoie le dernier id d'autoincrement ajoute
	// cas particulier : ne pas substituer la reponse spip_sqlite_query si on est en profilage
	return isset($_GET['var_profile']) ? $retour : $nb;
}


/**
 * Retourne si le moteur SQL prefere utiliser des transactions.
 *
 * @param 
 * @return bool true / false
**/
function spip_sqlite_preferer_transaction($serveur = '', $requeter = true) {
	return true;
}

/**
 * Demarre une transaction.
 * Pratique pour des sql_updateq() dans un foreach,
 * parfois 100* plus rapide s'ils sont nombreux en sqlite ! 
 *
**/
function spip_sqlite_demarrer_transaction($serveur = '', $requeter = true) {
	if (!$requeter) return "BEGIN TRANSACTION";
	spip_sqlite::demarrer_transaction($serveur);
	return true;
}

/**
 * Cloture une transaction.
 *
**/
function spip_sqlite_terminer_transaction($serveur = '', $requeter = true) {
	if (!$requeter) return "COMMIT";
	spip_sqlite::finir_transaction($serveur);
	return true;
}


// http://doc.spip.org/@spip_sqlite_listdbs
function spip_sqlite_listdbs($serveur = '', $requeter = true){
	_sqlite_init();

	if (!is_dir($d = substr(_DIR_DB, 0, -1))){
		return array();
	}

	include_spip('inc/flock');
	$bases = preg_files($d, $pattern = '(.*)\.sqlite$');
	$bds = array();

	foreach ($bases as $b){
		// pas de bases commencant pas sqlite 
		// (on s'en sert pour l'installation pour simuler la presence d'un serveur)
		// les bases sont de la forme _sqliteX_tmp_spip_install.sqlite
		if (strpos($b, '_sqlite')) continue;
		$bds[] = preg_replace(";.*/$pattern;iS", '$1', $b);
	}

	return $bds;
}


// http://doc.spip.org/@spip_sqlite_multi
function spip_sqlite_multi($objet, $lang){
	$r = "EXTRAIRE_MULTI(" . $objet . ", '" . $lang . "') AS multi";
	return $r;
}


/**
 * Optimise une table SQL
 * Note: Sqlite optimise TOUTE un fichier sinon rien.
 * On evite donc 2 traitements sur la meme base dans un hit.
 *
 * @param $table nom de la table a optimiser
 * @param $serveur nom de la connexion
 * @param $requeter effectuer la requete ? sinon retourner son code
 * @return bool|string true / false / requete
 **/
// http://doc.spip.org/@spip_sqlite_optimize
function spip_sqlite_optimize($table, $serveur = '', $requeter = true){
	static $do = false;
	if ($requeter and $do){
		return true;
	}
	if ($requeter){
		$do = true;
	}
	return spip_sqlite_query("VACUUM", $serveur, $requeter);
}


//
/**
 * echapper une valeur selon son type ou au mieux
 * comme le fait _q() mais pour sqlite avec ses specificites
 *
 * @param string|array|number $v
 * @param string $type
 * @return string|number
 */
function spip_sqlite_quote($v, $type = ''){
		if (!is_array($v))
			return _sqlite_calculer_cite($v,$type);
		// si c'est un tableau, le parcourir en propageant le type
		foreach($v as $k=>$r)
			$v[$k] = spip_sqlite_quote($r, $type);
		return join(",", $v);
}


/**
 * Tester si une date est proche de la valeur d'un champ
 *
 * @param string $champ le nom du champ a tester
 * @param int $interval valeur de l'interval : -1, 4, ...
 * @param string $unite utite utilisee (DAY, MONTH, YEAR, ...)
 * @return string expression SQL
 **/
function spip_sqlite_date_proche($champ, $interval, $unite){
	$op = (($interval <= 0) ? '>' : '<');
	return "($champ $op datetime('".date("Y-m-d H:i:s")."', '$interval $unite'))";
}


// http://doc.spip.org/@spip_sqlite_replace
function spip_sqlite_replace($table, $couples, $desc = array(), $serveur = '', $requeter = true){
	if (!$desc) $desc = description_table($table, $serveur);
	if (!$desc) die("$table insertion sans description");
	$fields = isset($desc['field']) ? $desc['field'] : array();

	foreach ($couples as $champ => $val){
		$couples[$champ] = _sqlite_calculer_cite($val, $fields[$champ]);
	}

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$couples = _sqlite_ajouter_champs_timestamp($table, $couples, $desc, $serveur);

	return spip_sqlite_query("REPLACE INTO $table (".join(',', array_keys($couples)).') VALUES ('.join(',', $couples).')', $serveur);
}


// http://doc.spip.org/@spip_sqlite_replace_multi
function spip_sqlite_replace_multi($table, $tab_couples, $desc = array(), $serveur = '', $requeter = true){

	// boucler pour trainter chaque requete independemment
	foreach ($tab_couples as $couples){
		$retour = spip_sqlite_replace($table, $couples, $desc, $serveur, $requeter);
	}
	// renvoie le dernier id	
	return $retour;
}


// http://doc.spip.org/@spip_sqlite_select
function spip_sqlite_select($select, $from, $where = '', $groupby = '', $orderby = '', $limit = '', $having = '', $serveur = '', $requeter = true){

	// version() n'est pas connu de sqlite
	$select = str_replace('version()', 'sqlite_version()', $select);

	// recomposer from
	$from = (!is_array($from) ? $from : _sqlite_calculer_select_as($from));

	$query =
		_sqlite_calculer_expression('SELECT', $select, ', ')
		._sqlite_calculer_expression('FROM', $from, ', ')
		._sqlite_calculer_expression('WHERE', $where)
		._sqlite_calculer_expression('GROUP BY', $groupby, ',')
		._sqlite_calculer_expression('HAVING', $having)
		.($orderby ? ("\nORDER BY "._sqlite_calculer_order($orderby)) : '')
		.($limit ? "\nLIMIT $limit" : '');

	// dans un select, on doit renvoyer la requête en cas d'erreur
	$res = spip_sqlite_query($query, $serveur, $requeter);
	// texte de la requete demande ?
	if (!$requeter) return $res;
	// erreur survenue ?
	if ($res === false) {
		return spip_sqlite::traduire_requete($query, $serveur);
	}
	return $res;
}


/**
 * Selectionne un fichier de base de donnees
 *
 * @param string $nom
 * 		Nom de la base a utiliser
 * @param string $serveur
 * 		Nom du connecteur
 * @param bool $requeter
 * 		Inutilise
 * 
 * @return bool|string
 * 		Nom de la base en cas de success.
 * 		False en cas d'erreur.
**/
function spip_sqlite_selectdb($db, $serveur = '', $requeter = true){
	_sqlite_init();

	// interdire la creation d'une nouvelle base, 
	// sauf si on est dans l'installation
	if (!is_file($f = _DIR_DB.$db.'.sqlite')
	    && (!defined('_ECRIRE_INSTALL') || !_ECRIRE_INSTALL)){
		spip_log("Il est interdit de creer la base $db", 'sqlite.'._LOG_HS);
		return false;
	}

	// se connecter a la base indiquee
	// avec les identifiants connus
	$index = $serveur ? $serveur : 0;

	if ($link = spip_connect_db('', '', '', '', '@selectdb@'.$db, $serveur, '', '')){
		if (($db==$link['db']) && $GLOBALS['connexions'][$index] = $link)
			return $db;
	} else {
		spip_log("Impossible de selectionner la base $db", 'sqlite.'._LOG_HS);
		return false;
	}

}


// http://doc.spip.org/@spip_sqlite_set_charset
function spip_sqlite_set_charset($charset, $serveur = '', $requeter = true){
	# spip_log("Gestion charset sql a ecrire : "."SET NAMES "._q($charset), 'sqlite.'._LOG_ERREUR);
	# return spip_sqlite_query("SET NAMES ". spip_sqlite_quote($charset), $serveur); //<-- Passe pas !
}


/**
 * Retourne une ressource de la liste des tables de la base de données 
 *
 * @param string $match
 *     Filtre sur tables à récupérer
 * @param string $serveur
 *     Connecteur de la base
 * @param bool $requeter
 *     true pour éxecuter la requête
 *     false pour retourner le texte de la requête.
 * @return ressource
 *     Ressource à utiliser avec sql_fetch()
**/
function spip_sqlite_showbase($match, $serveur = '', $requeter = true){
	// type est le type d'entrée : table / index / view
	// on ne retourne que les tables (?) et non les vues...
	# ESCAPE non supporte par les versions sqlite <3
	#	return spip_sqlite_query("SELECT name FROM sqlite_master WHERE type='table' AND tbl_name LIKE "._q($match)." ESCAPE '\'", $serveur, $requeter);
	$match = preg_quote($match);
	$match = str_replace("\\\_", "[[TIRETBAS]]", $match);
	$match = str_replace("\\\%", "[[POURCENT]]", $match);
	$match = str_replace("_", ".", $match);
	$match = str_replace("%", ".*", $match);
	$match = str_replace("[[TIRETBAS]]", "_", $match);
	$match = str_replace("[[POURCENT]]", "%", $match);
	$match = "^$match$";
	return spip_sqlite_query("SELECT name FROM sqlite_master WHERE type='table' AND tbl_name REGEXP "._q($match), $serveur, $requeter);
}


// http://doc.spip.org/@spip_sqlite_showtable
function spip_sqlite_showtable($nom_table, $serveur = '', $requeter = true){
	$query =
		'SELECT sql, type FROM'
		.' (SELECT * FROM sqlite_master UNION ALL'
		.' SELECT * FROM sqlite_temp_master)'
		." WHERE tbl_name LIKE '$nom_table'"
		." AND type!='meta' AND sql NOT NULL AND name NOT LIKE 'sqlite_%'"
		.' ORDER BY substr(type,2,1), name';

	$a = spip_sqlite_query($query, $serveur, $requeter);
	if (!$a) return "";
	if (!$requeter) return $a;
	if (!($a = spip_sqlite_fetch($a, null, $serveur))) return "";
	$vue = ($a['type']=='view'); // table | vue

	// c'est une table
	// il faut parser le create
	if (!$vue){
		if (!preg_match("/^[^(),]*\((([^()]*(\([^()]*\))?[^()]*)*)\)[^()]*$/", array_shift($a), $r))
			return "";
		else {
			$desc = $r[1];
			// extraction d'une KEY éventuelle en prenant garde de ne pas
			// relever un champ dont le nom contient KEY (ex. ID_WHISKEY)
			if (preg_match("/^(.*?),([^,]*KEY[ (].*)$/s", $desc, $r)){
				$namedkeys = $r[2];
				$desc = $r[1];
			}
			else
				$namedkeys = "";

			$fields = array();
			$keys   = array();

			// enlever les contenus des valeurs DEFAULT 'xxx' qui pourraient perturber
			// par exemple s'il contiennent une virgule.
			// /!\ cela peut aussi echapper le nom des champs si la table a eu des operations avec SQLite Manager !
			list($desc, $echaps) = query_echappe_textes($desc);

			// separer toutes les descriptions de champs, separes par des virgules
			# /!\ explode peut exploser aussi DECIMAL(10,2) !
			$k_precedent = null;
			foreach (explode(",", $desc) as $v){

				preg_match("/^\s*([^\s]+)\s+(.*)/", $v, $r);
				// Les cles de champs peuvent etre entourees
				// de guillements doubles " , simples ', graves ` ou de crochets [ ],  ou rien.
				// http://www.sqlite.org/lang_keywords.html
				$k = strtolower(query_reinjecte_textes($r[1], $echaps)); // champ, "champ", [champ]...
				if ($char = strpbrk($k[0], '\'"[`')) {
					$k = trim($k, $char);
					if ($char == '[') $k = rtrim($k, ']');
				}
				$def = query_reinjecte_textes($r[2], $echaps); // valeur du champ

				# rustine pour DECIMAL(10,2)
				if (false !== strpos($k, ')')) {
					$fields[$k_precedent] .= ',' . $k . ' ' . $def;
					continue;
				}
				
				$fields[$k] = $def;
				$k_precedent = $k;
				
				// la primary key peut etre dans une des descriptions de champs
				// et non en fin de table, cas encore decouvert avec Sqlite Manager
				if (stripos($r[2], 'PRIMARY KEY') !== false) {
					$keys['PRIMARY KEY'] = $k;
				}
			}
			// key inclues dans la requete
			foreach (preg_split('/\)\s*,?/', $namedkeys) as $v){
				if (preg_match("/^\s*([^(]*)\((.*)$/", $v, $r)){
					$k = str_replace("`", '', trim($r[1]));
					$t = trim(strtolower(str_replace("`", '', $r[2])), '"');
					if ($k && !isset($keys[$k])) $keys[$k] = $t; else $keys[] = $t;
				}
			}
			// sinon ajouter les key index
			$query =
				'SELECT name,sql FROM'
				.' (SELECT * FROM sqlite_master UNION ALL'
				.' SELECT * FROM sqlite_temp_master)'
				." WHERE tbl_name LIKE '$nom_table'"
				." AND type='index' AND name NOT LIKE 'sqlite_%'"
				.'ORDER BY substr(type,2,1), name';
			$a = spip_sqlite_query($query, $serveur, $requeter);
			while ($r = spip_sqlite_fetch($a, null, $serveur)){
				$key = str_replace($nom_table.'_', '', $r['name']); // enlever le nom de la table ajoute a l'index
				$colonnes = preg_replace(',.*\((.*)\).*,', '$1', $r['sql']);
				$keys['KEY '.$key] = $colonnes;
			}
		}
		// c'est une vue, on liste les champs disponibles simplement
	} else {
		if ($res = sql_fetsel('*', $nom_table, '', '', '', '1', '', $serveur)){ // limit 1
			$fields = array();
			foreach ($res as $c => $v) $fields[$c] = '';
			$keys = array();
		} else {
			return "";
		}
	}
	return array('field' => $fields, 'key' => $keys);

}


// http://doc.spip.org/@spip_sqlite_update
function spip_sqlite_update($table, $champs, $where = '', $desc = '', $serveur = '', $requeter = true){
	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$champs = _sqlite_ajouter_champs_timestamp($table, $champs, $desc, $serveur);

	$set = array();
	foreach ($champs as $champ => $val)
		$set[] = $champ."=$val";
	if (!empty($set))
		return spip_sqlite_query(
			_sqlite_calculer_expression('UPDATE', $table, ',')
			._sqlite_calculer_expression('SET', $set, ',')
			._sqlite_calculer_expression('WHERE', $where),
			$serveur, $requeter);
}


// http://doc.spip.org/@spip_sqlite_updateq
function spip_sqlite_updateq($table, $champs, $where = '', $desc = array(), $serveur = '', $requeter = true){

	if (!$champs) return;
	if (!$desc) $desc = description_table($table, $serveur);
	if (!$desc) die("$table insertion sans description");
	$fields = $desc['field'];

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$champs = _sqlite_ajouter_champs_timestamp($table, $champs, $desc, $serveur);

	$set = array();
	foreach ($champs as $champ => $val){
		$set[] = $champ.'='._sqlite_calculer_cite($val, $fields[$champ]);
	}
	return spip_sqlite_query(
		_sqlite_calculer_expression('UPDATE', $table, ',')
		._sqlite_calculer_expression('SET', $set, ',')
		._sqlite_calculer_expression('WHERE', $where),
		$serveur, $requeter);
}


/*
 * 
 * Ensuite les fonctions non abstraites
 * crees pour l'occasion de sqlite
 * 
 */


/**
 * fonction pour la premiere connexion a un serveur SQLite
 * http://doc.spip.org/@_sqlite_init
 *
 * @return void
 */
function _sqlite_init(){
	if (!defined('_DIR_DB')) define('_DIR_DB', _DIR_ETC.'bases/');
	if (!defined('_SQLITE_CHMOD')) define('_SQLITE_CHMOD', _SPIP_CHMOD);

	if (!is_dir($d = _DIR_DB)){
		include_spip('inc/flock');
		sous_repertoire($d);
	}
}


/**
 * teste la version sqlite du link en cours
 * http://doc.spip.org/@_sqlite_is_version
 *
 * @param string $version
 * @param string $link
 * @param string $serveur
 * @param bool $requeter
 * @return bool|int
 */
function _sqlite_is_version($version = '', $link = '', $serveur = '', $requeter = true){
	if ($link==='') $link = _sqlite_link($serveur);
	if (!$link) return false;
	if ($link instanceof PDO){
		$v = 3;
	} else {
		$v = 2;
	}

	if (!$version) return $v;
	return ($version==$v);
}


/**
 * retrouver un link
 * http://doc.spip.org/@_sqlite_link
 *
 * @param string $serveur
 * @return
 */
function _sqlite_link($serveur = ''){
	$link = &$GLOBALS['connexions'][$serveur ? $serveur : 0]['link'];
	return $link;
}


/* ordre alphabetique pour les autres */


/**
 * renvoie les bons echappements (pas sur les fonctions now())
 * http://doc.spip.org/@_sqlite_calculer_cite
 *
 * @param string|array|number $v
 * @param string $type
 * @return string|array|number
 */
function _sqlite_calculer_cite($v, $type){
	if ($type){
		if(is_null($v)
			AND stripos($type,"NOT NULL")===false) return 'NULL'; // null php se traduit en NULL SQL

		if (sql_test_date($type) AND preg_match('/^\w+\(/', $v))
			return $v;
		if (sql_test_int($type)){
			if (is_numeric($v))
				return $v;
			elseif (ctype_xdigit(substr($v, 2)) AND strncmp($v, '0x', 2)==0)
				return hexdec(substr($v, 2));
			else
				return intval($v);
		}
	}
	else {
		// si on ne connait pas le type on le deduit de $v autant que possible
		if (is_numeric($v))
			return strval($v);
	}

	if (function_exists('sqlite_escape_string')){
		return "'".sqlite_escape_string($v)."'";
	}

	// trouver un link sqlite3 pour faire l'echappement
	foreach ($GLOBALS['connexions'] as $s){
		if (_sqlite_is_version(3, $l = $s['link'])){
			return $l->quote($v);
		}
	}

	// echapper les ' en ''
	spip_log("Pas de methode sqlite_escape_string ni ->quote pour echapper","sqlite."._LOG_INFO_IMPORTANTE);
	return  ("'" . str_replace("'","''",$v) . "'");
}


/**
 * renvoie grosso modo "$expression join($join, $v)"
 * http://doc.spip.org/@_sqlite_calculer_expression
 *
 * @param  $expression
 * @param  $v
 * @param string $join
 * @return string
 */
function _sqlite_calculer_expression($expression, $v, $join = 'AND'){
	if (empty($v))
		return '';

	$exp = "\n$expression ";

	if (!is_array($v)){
		return $exp.$v;
	} else {
		if (strtoupper($join)==='AND')
			return $exp.join("\n\t$join ", array_map('_sqlite_calculer_where', $v));
		else
			return $exp.join($join, $v);
	}
}


/**
 * pour conversion 0+x ? (pas la peine en sqlite)
 * http://doc.spip.org/@_sqlite_calculer_order
 *
 * @param  $orderby
 * @return string
 */
function _sqlite_calculer_order($orderby){
	return (is_array($orderby)) ? join(", ", $orderby) : $orderby;
}


// renvoie des 'nom AS alias' 
// http://doc.spip.org/@_sqlite_calculer_select_as
function _sqlite_calculer_select_as($args){
	$res = '';
	foreach ($args as $k => $v){
		if (substr($k, -1)=='@'){
			// c'est une jointure qui se refere au from precedent
			// pas de virgule
			$res .= '  '.$v;
		}
		else {
			if (!is_numeric($k)){
				$p = strpos($v, " ");
				if ($p)
					$v = substr($v, 0, $p)." AS '$k'".substr($v, $p);
				else $v .= " AS '$k'";
			}
			$res .= ', '.$v;
		}
	}
	return substr($res, 2);
}


/**
 * renvoie les bonnes parentheses pour des where imbriquees
 * http://doc.spip.org/@_sqlite_calculer_where
 *
 * @param  $v
 * @return array|mixed|string
 */
function _sqlite_calculer_where($v){
	if (!is_array($v))
		return $v;

	$op = array_shift($v);
	if (!($n = count($v)))
		return $op;
	else {
		$arg = _sqlite_calculer_where(array_shift($v));
		if ($n==1){
			return "$op($arg)";
		} else {
			$arg2 = _sqlite_calculer_where(array_shift($v));
			if ($n==2){
				return "($arg $op $arg2)";
			} else return "($arg $op ($arg2) : $v[0])";
		}
	}
}


/**
 * Charger les modules sqlite (si possible) (juste la version demandee),
 * ou, si aucune version, renvoie les versions sqlite dispo
 * sur ce serveur dans un array
 *
 * http://doc.spip.org/@_sqlite_charger_version
 *
 * @param string $version
 * @return array|bool
 */
function _sqlite_charger_version($version = ''){
	$versions = array();

	// version 2
	if (!$version || $version==2){
		if (charger_php_extension('sqlite')){
			$versions[] = 2;
		}
	}

	// version 3
	if (!$version || $version==3){
		if (charger_php_extension('pdo') && charger_php_extension('pdo_sqlite')){
			$versions[] = 3;
		}
	}
	if ($version) return in_array($version, $versions);
	return $versions;
}


/**
 * Gestion des requetes ALTER non reconnues de SQLite :
 * ALTER TABLE table DROP column
 * ALTER TABLE table CHANGE [COLUMN] columnA columnB definition
 * ALTER TABLE table MODIFY column definition
 * ALTER TABLE table ADD|DROP PRIMARY KEY
 *
 * (MODIFY transforme en CHANGE columnA columnA) par spip_sqlite_alter()
 *
 * 1) creer une table B avec le nouveau format souhaite
 * 2) copier la table d'origine A vers B
 * 3) supprimer la table A
 * 4) renommer la table B en A
 * 5) remettre les index (qui sont supprimes avec la table A)
 *
 * http://doc.spip.org/@_sqlite_modifier_table
 *
 * @param string/array $table : nom_table, array(nom_table=>nom_futur)
 * @param string/array $col : nom_colonne, array(nom_colonne=>nom_futur)
 * @param array $opt : options comme les tables spip, qui sera merge a la table creee : array('field'=>array('nom'=>'syntaxe', ...), 'key'=>array('KEY nom'=>'colonne', ...))
 * @param string $serveur : nom de la connexion sql en cours
 *
 */
function _sqlite_modifier_table($table, $colonne, $opt = array(), $serveur = ''){

	if (is_array($table)){
		reset($table);
		list($table_origine,$table_destination) = each($table);
	} else {
		$table_origine = $table_destination = $table;
	}
	// ne prend actuellement qu'un changement
	// mais pourra etre adapte pour changer plus qu'une colonne a la fois
	if (is_array($colonne)){
		reset($colonne);
		list($colonne_origine,$colonne_destination) = each($colonne);
	} else {
		$colonne_origine = $colonne_destination = $colonne;
	}
	if (!isset($opt['field'])) $opt['field'] = array();
	if (!isset($opt['key'])) $opt['key'] = array();

	// si les noms de tables sont differents, pas besoin de table temporaire
	// on prendra directement le nom de la future table
	$meme_table = ($table_origine==$table_destination);

	$def_origine = sql_showtable($table_origine, false, $serveur);
	if (!$def_origine OR !isset($def_origine['field'])){
		spip_log("Alter table impossible sur $table_origine : table non trouvee",'sqlite'._LOG_ERREUR);
		return false;
	}


	$table_tmp = $table_origine.'_tmp';

	// 1) creer une table temporaire avec les modifications	
	// - DROP : suppression de la colonne
	// - CHANGE : modification de la colonne
	// (foreach pour conserver l'ordre des champs)

	// field 
	$fields = array();
	// pour le INSERT INTO plus loin
	// stocker la correspondance nouvelles->anciennes colonnes
	$fields_correspondances = array();
	foreach ($def_origine['field'] as $c => $d){

		if ($colonne_origine && ($c==$colonne_origine)){
			// si pas DROP
			if ($colonne_destination){
				$fields[$colonne_destination] = $opt['field'][$colonne_destination];
				$fields_correspondances[$colonne_destination] = $c;
			}
		} else {
			$fields[$c] = $d;
			$fields_correspondances[$c] = $c;
		}
	}
	// cas de ADD sqlite2 (ajout du champ en fin de table):
	if (!$colonne_origine && $colonne_destination){
		$fields[$colonne_destination] = $opt['field'][$colonne_destination];
	}

	// key...
	$keys = array();
	foreach ($def_origine['key'] as $c => $d){
		$c = str_replace($colonne_origine, $colonne_destination, $c);
		$d = str_replace($colonne_origine, $colonne_destination, $d);
		// seulement si on ne supprime pas la colonne !
		if ($d)
			$keys[$c] = $d;
	}

	// autres keys, on merge
	$keys = array_merge($keys, $opt['key']);
	$queries = array();

	// copier dans destination (si differente de origine), sinon tmp
	$table_copie = ($meme_table) ? $table_tmp : $table_destination;
	$autoinc = (isset($keys['PRIMARY KEY'])
					AND stripos($keys['PRIMARY KEY'],',')===false
					AND stripos($fields[$keys['PRIMARY KEY']],'default')===false);

	if ($q = _sqlite_requete_create(
		$table_copie,
		$fields,
		$keys,
		$autoinc,
		$temporary = false,
		$ifnotexists = true,
		$serveur)){
		$queries[] = $q;
	}


	// 2) y copier les champs qui vont bien
	$champs_dest = join(', ', array_keys($fields_correspondances));
	$champs_ori = join(', ', $fields_correspondances);
	$queries[] = "INSERT INTO $table_copie ($champs_dest) SELECT $champs_ori FROM $table_origine";

	// 3) supprimer la table d'origine
	$queries[] = "DROP TABLE $table_origine";

	// 4) renommer la table temporaire 
	// avec le nom de la table destination
	// si necessaire
	if ($meme_table){
		if (_sqlite_is_version(3, '', $serveur)){
			$queries[] = "ALTER TABLE $table_copie RENAME TO $table_destination";
		} else {
			$queries[] = _sqlite_requete_create(
				$table_destination,
				$fields,
				$keys,
				$autoinc,
				$temporary = false,
				$ifnotexists = false, // la table existe puisqu'on est dans une transaction
				$serveur);
			$queries[] = "INSERT INTO $table_destination SELECT * FROM $table_copie";
			$queries[] = "DROP TABLE $table_copie";
		}
	}

	// 5) remettre les index !
	foreach ($keys as $k => $v){
		if ($k=='PRIMARY KEY'){
		}
		else {
			// enlever KEY
			$k = substr($k, 4);
			$queries[] = "CREATE INDEX $table_destination"."_$k ON $table_destination ($v)";
		}
	}


	if (count($queries)){
		spip_sqlite::demarrer_transaction($serveur);
		// il faut les faire une par une car $query = join('; ', $queries).";"; ne fonctionne pas
		foreach ($queries as $q){
			if (!spip_sqlite::executer_requete($q, $serveur)){
				spip_log(_LOG_GRAVITE_ERREUR, "SQLite : ALTER TABLE table :"
																			." Erreur a l'execution de la requete : $q", 'sqlite');
				spip_sqlite::annuler_transaction($serveur);
				return false;
			}
		}
		spip_sqlite::finir_transaction($serveur);
	}

	return true;
}


/**
 * Nom des fonctions
 * http://doc.spip.org/@_sqlite_ref_fonctions
 *
 * @return array
 */
function _sqlite_ref_fonctions(){
	$fonctions = array(
		'alter' => 'spip_sqlite_alter',
		'count' => 'spip_sqlite_count',
		'countsel' => 'spip_sqlite_countsel',
		'create' => 'spip_sqlite_create',
		'create_base' => 'spip_sqlite_create_base',
		'create_view' => 'spip_sqlite_create_view',
		'date_proche' => 'spip_sqlite_date_proche',
		'delete' => 'spip_sqlite_delete',
		'drop_table' => 'spip_sqlite_drop_table',
		'drop_view' => 'spip_sqlite_drop_view',
		'errno' => 'spip_sqlite_errno',
		'error' => 'spip_sqlite_error',
		'explain' => 'spip_sqlite_explain',
		'fetch' => 'spip_sqlite_fetch',
		'seek' => 'spip_sqlite_seek',
		'free' => 'spip_sqlite_free',
		'hex' => 'spip_sqlite_hex',
		'in' => 'spip_sqlite_in',
		'insert' => 'spip_sqlite_insert',
		'insertq' => 'spip_sqlite_insertq',
		'insertq_multi' => 'spip_sqlite_insertq_multi',
		'listdbs' => 'spip_sqlite_listdbs',
		'multi' => 'spip_sqlite_multi',
		'optimize' => 'spip_sqlite_optimize',
		'query' => 'spip_sqlite_query',
		'quote' => 'spip_sqlite_quote',
		'replace' => 'spip_sqlite_replace',
		'replace_multi' => 'spip_sqlite_replace_multi',
		'select' => 'spip_sqlite_select',
		'selectdb' => 'spip_sqlite_selectdb',
		'set_charset' => 'spip_sqlite_set_charset',
		'get_charset' => 'spip_sqlite_get_charset',
		'showbase' => 'spip_sqlite_showbase',
		'showtable' => 'spip_sqlite_showtable',
		'update' => 'spip_sqlite_update',
		'updateq' => 'spip_sqlite_updateq',
		'preferer_transaction' => 'spip_sqlite_preferer_transaction',
		'demarrer_transaction' => 'spip_sqlite_demarrer_transaction',
		'terminer_transaction' => 'spip_sqlite_terminer_transaction',
	);

	// association de chaque nom http d'un charset aux couples sqlite 
	// SQLite supporte utf-8 et utf-16 uniquement.
	$charsets = array(
		'utf-8' => array('charset' => 'utf8', 'collation' => 'utf8_general_ci'),
		//'utf-16be'=>array('charset'=>'utf16be','collation'=>'UTF-16BE'),// aucune idee de quoi il faut remplir dans es champs la
		//'utf-16le'=>array('charset'=>'utf16le','collation'=>'UTF-16LE')
	);

	$fonctions['charsets'] = $charsets;

	return $fonctions;
}


/**
 * $query est une requete ou une liste de champs
 * http://doc.spip.org/@_sqlite_remplacements_definitions_table
 *
 * @param  $query
 * @param bool $autoinc
 * @return mixed
 */
function _sqlite_remplacements_definitions_table($query, $autoinc = false){
	// quelques remplacements
	$num = "(\s*\([0-9]*\))?";
	$enum = "(\s*\([^\)]*\))?";

	$remplace = array(
		'/enum'.$enum.'/is' => 'VARCHAR(255)',
		'/COLLATE \w+_bin/is' => 'COLLATE BINARY',
		'/COLLATE \w+_ci/is' => 'COLLATE NOCASE',
		'/auto_increment/is' => '',
		'/(timestamp .* )ON .*$/is' => '\\1',
		'/character set \w+/is' => '',
		'/((big|small|medium|tiny)?int(eger)?)'.$num.'\s*unsigned/is' => '\\1 UNSIGNED',
		'/(text\s+not\s+null(\s+collate\s+\w+)?)\s*$/is' => "\\1 DEFAULT ''",
		'/((char|varchar)'.$num.'\s+not\s+null(\s+collate\s+\w+)?)\s*$/is' => "\\1 DEFAULT ''",
		'/(datetime\s+not\s+null)\s*$/is' => "\\1 DEFAULT '0000-00-00 00:00:00'",
		'/(date\s+not\s+null)\s*$/is' => "\\1 DEFAULT '0000-00-00'",
	);

	// pour l'autoincrement, il faut des INTEGER NOT NULL PRIMARY KEY
	$remplace_autocinc = array(
		'/(big|small|medium|tiny)?int(eger)?'.$num.'/is' => 'INTEGER'
	);
	// pour les int non autoincrement, il faut un DEFAULT
	$remplace_nonautocinc = array(
		'/((big|small|medium|tiny)?int(eger)?'.$num.'\s+not\s+null)\s*$/is' => "\\1 DEFAULT 0",
	);

	if (is_string($query)){
		$query = preg_replace(array_keys($remplace), $remplace, $query);
		if ($autoinc OR preg_match(',AUTO_INCREMENT,is',$query))
			$query = preg_replace(array_keys($remplace_autocinc), $remplace_autocinc, $query);
		else{
			$query = preg_replace(array_keys($remplace_nonautocinc), $remplace_nonautocinc, $query);
			$query = _sqlite_collate_ci($query);
		}
	}
	elseif(is_array($query)){
		foreach($query as $k=>$q) {
			$ai = ($autoinc?$k==$autoinc:preg_match(',AUTO_INCREMENT,is',$q));
			$query[$k] = preg_replace(array_keys($remplace), $remplace, $query[$k]);
			if ($ai)
				$query[$k] = preg_replace(array_keys($remplace_autocinc), $remplace_autocinc, $query[$k]);
			else{
				$query[$k] = preg_replace(array_keys($remplace_nonautocinc), $remplace_nonautocinc, $query[$k]);
				$query[$k] = _sqlite_collate_ci($query[$k]);
			}
		}
	}
	return $query;
}

/**
 * Definir la collation d'un champ en fonction de si une collation est deja explicite
 * et du par defaut que l'on veut NOCASE
 * @param string $champ
 * @return string
 */
function _sqlite_collate_ci($champ){
	if (stripos($champ,"COLLATE")!==false)
		return $champ;
	if (stripos($champ,"BINARY")!==false)
		return str_ireplace("BINARY","COLLATE BINARY",$champ);
	if (preg_match(",^(char|varchar|(long|small|medium|tiny)?text),i",$champ))
		return $champ . " COLLATE NOCASE";

	return $champ;
}


/**
 * Creer la requete pour la creation d'une table
 * retourne la requete pour utilisation par sql_create() et sql_alter()
 *
 * http://doc.spip.org/@_sqlite_requete_create
 *
 * @param  $nom
 * @param  $champs
 * @param  $cles
 * @param bool $autoinc
 * @param bool $temporary
 * @param bool $_ifnotexists
 * @param string $serveur
 * @param bool $requeter
 * @return bool|string
 */
function _sqlite_requete_create($nom, $champs, $cles, $autoinc = false, $temporary = false, $_ifnotexists = true, $serveur = '', $requeter = true){
	$query = $keys = $s = $p = '';

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles))
		return;

	// sqlite ne gere pas KEY tout court dans une requete CREATE TABLE
	// il faut passer par des create index
	// Il gere par contre primary key !
	// Soit la PK est definie dans les cles, soit dans un champs
	$c = ""; // le champ de cle primaire
	if (!isset($cles[$pk = "PRIMARY KEY"]) OR !$c = $cles[$pk]){
		foreach ($champs as $k => $v){
			if (false!==stripos($v, $pk)){
				$c = $k;
				// on n'en a plus besoin dans field, vu que defini dans key
				$champs[$k] = preg_replace("/$pk/is", '', $champs[$k]);
				break;
			}
		}
	}
	if ($c) $keys = "\n\t\t$pk ($c)";
	// Pas de DEFAULT 0 sur les cles primaires en auto-increment
	if (isset($champs[$c])
		AND stripos($champs[$c],"default 0")!==false){
		$champs[$c] = trim(str_ireplace("default 0","",$champs[$c]));
	}

	$champs = _sqlite_remplacements_definitions_table($champs, $autoinc?$c:false);
	foreach ($champs as $k => $v){
		$query .= "$s\n\t\t$k $v";
		$s = ",";
	}

	$ifnotexists = "";
	if ($_ifnotexists){

		$version = spip_sqlite_fetch(spip_sqlite_query("select sqlite_version() AS sqlite_version",$serveur),'',$serveur);
		if (!function_exists('spip_version_compare')) include_spip('plugins/installer');

		if ($version AND spip_version_compare($version['sqlite_version'],'3.3.0','>=')) {
			$ifnotexists = ' IF NOT EXISTS';
		} else {
			/* simuler le IF EXISTS - version 2 et sqlite < 3.3a */
			$a = spip_sqlite_showtable($table, $serveur);
			if (isset($a['key']['KEY '.$nom])) return true;
		}

	}

	$temporary = $temporary ? ' TEMPORARY' : '';
	$q = "CREATE$temporary TABLE$ifnotexists $nom ($query".($keys ? ",$keys" : '').")\n";

	return $q;
}


/**
 * Retrouver les champs 'timestamp'
 * pour les ajouter aux 'insert' ou 'replace'
 * afin de simuler le fonctionnement de mysql
 *
 * stocke le resultat pour ne pas faire
 * de requetes showtable intempestives
 *
 * http://doc.spip.org/@_sqlite_ajouter_champs_timestamp
 *
 * @param  $table
 * @param  $couples
 * @param string $desc
 * @param string $serveur
 * @return
 */
function _sqlite_ajouter_champs_timestamp($table, $couples, $desc = '', $serveur = ''){
	static $tables = array();

	if (!isset($tables[$table])){

		if (!$desc){
			$trouver_table = charger_fonction('trouver_table', 'base');
			$desc = $trouver_table($table, $serveur);
			// si pas de description, on ne fait rien, ou on die() ?
			if (!$desc) return $couples;
		}

		// recherche des champs avec simplement 'TIMESTAMP'
		// cependant, il faudra peut etre etendre
		// avec la gestion de DEFAULT et ON UPDATE
		// mais ceux-ci ne sont pas utilises dans le core
		$tables[$table] = array();

		foreach ($desc['field'] as $k => $v){
			if (strpos(strtolower(ltrim($v)), 'timestamp')===0)
				$tables[$table][$k] = "datetime('now')";
		}
	}

	// ajout des champs type 'timestamp' absents
	return array_merge($tables[$table],$couples);
}


/**
 * renvoyer la liste des versions sqlite disponibles
 * sur le serveur
 * http://doc.spip.org/@spip_versions_sqlite
 *
 * @return array|bool
 */
function spip_versions_sqlite(){
	return _sqlite_charger_version();
}


class spip_sqlite {
	static $requeteurs = array();
	static $transaction_en_cours = array();

	function spip_sqlite(){}

	/**
	 * Retourne une unique instance du requêteur
	 *
	 * Retourne une instance unique du requêteur pour une connexion SQLite
	 * donnée
	 *
	 * @param string $serveur
	 * 		Nom du connecteur
	 * @return sqlite_requeteur
	 * 		Instance unique du requêteur
	**/
	static function requeteur($serveur){
		if (!isset(spip_sqlite::$requeteurs[$serveur]))
			spip_sqlite::$requeteurs[$serveur] = new sqlite_requeteur($serveur);
		return spip_sqlite::$requeteurs[$serveur];
	}

	static function traduire_requete($query, $serveur){
		$requeteur = spip_sqlite::requeteur($serveur);
		$traducteur = new sqlite_traducteur($query, $requeteur->prefixe,$requeteur->sqlite_version);
		return $traducteur->traduire_requete();
	}

	static function demarrer_transaction($serveur){
		spip_sqlite::executer_requete("BEGIN TRANSACTION",$serveur);
		spip_sqlite::$transaction_en_cours[$serveur] = true;
	}

	static function executer_requete($query, $serveur, $tracer=null){
		$requeteur = spip_sqlite::requeteur($serveur);
		return $requeteur->executer_requete($query, $tracer);
	}

	static function last_insert_id($serveur){
		$requeteur = spip_sqlite::requeteur($serveur);
		return $requeteur->last_insert_id($serveur);
	}

	static function annuler_transaction($serveur){
		spip_sqlite::executer_requete("ROLLBACK",$serveur);
		spip_sqlite::$transaction_en_cours[$serveur] = false;
	}

	static function finir_transaction($serveur){
		// si pas de transaction en cours, ne rien faire et le dire
		if (!isset (spip_sqlite::$transaction_en_cours[$serveur])
		  OR spip_sqlite::$transaction_en_cours[$serveur]==false)
			return false;
		// sinon fermer la transaction et retourner true
		spip_sqlite::executer_requete("COMMIT",$serveur);
		spip_sqlite::$transaction_en_cours[$serveur] = false;
		return true;
	}
}

/*
 * Classe pour partager les lancements de requete
 * instanciee une fois par $serveur
 * - peut corriger la syntaxe des requetes pour la conformite a sqlite
 * - peut tracer les requetes
 * 
 */
class sqlite_requeteur {
	var $query = ''; // la requete
	var $serveur = ''; // le serveur
	var $link = ''; // le link (ressource) sqlite
	var $prefixe = ''; // le prefixe des tables
	var $db = ''; // le nom de la base 
	var $tracer = false; // doit-on tracer les requetes (var_profile)

	var $sqlite_version = ''; // Version de sqlite (2 ou 3)

	/**
	 * constructeur
	 * http://doc.spip.org/@sqlite_traiter_requete
	 *
	 * @param  $query
	 * @param string $serveur
	 * @return bool
	 */
	function sqlite_requeteur($serveur = ''){
		_sqlite_init();
		$this->serveur = strtolower($serveur);

		if (!($this->link = _sqlite_link($this->serveur)) && (!defined('_ECRIRE_INSTALL') || !_ECRIRE_INSTALL)){
			spip_log("Aucune connexion sqlite (link)", 'sqlite.'._LOG_ERREUR);
			return false;
		}

		$this->sqlite_version = _sqlite_is_version('', $this->link);

		$this->prefixe = $GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['prefixe'];
		$this->db = $GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['db'];

		// tracage des requetes ?
		$this->tracer = (isset($_GET['var_profile']) && $_GET['var_profile']);
	}

	/**
	 * lancer la requete $query,
	 * faire le tracage si demande
	 * http://doc.spip.org/@executer_requete
	 *
	 * @return bool|SQLiteResult
	 */
	function executer_requete($query, $tracer=null){
		if (is_null($tracer))
			$tracer = $this->tracer;
		$err = "";
		$t = 0;
		if ($tracer){
			include_spip('public/tracer');
			$t = trace_query_start();
		}
		
		# spip_log("requete: $this->serveur >> $query",'sqlite.'._LOG_DEBUG); // boum ? pourquoi ?
		if ($this->link){
			// memoriser la derniere erreur PHP vue
			$e = (function_exists('error_get_last')?error_get_last():"");
			// sauver la derniere requete
			$GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['last'] = $query;

			if ($this->sqlite_version==3){
				$r = $this->link->query($query);
				// sauvegarde de la requete (elle y est deja dans $r->queryString)
				# $r->spipQueryString = $query;

				// comptage : oblige de compter le nombre d'entrees retournees 
				// par une requete SELECT
				// aucune autre solution ne donne le nombre attendu :( !
				// particulierement s'il y a des LIMIT dans la requete.
				if (strtoupper(substr(ltrim($query), 0, 6))=='SELECT'){
					if ($r){
						// noter le link et la query pour faire le comptage *si* on en a besoin
						$r->spipSqliteRowCount = array($this->link,$query);
					}
					elseif ($r instanceof PDOStatement) {
						$r->spipSqliteRowCount = 0;
					}
				}
			}
			else {
				$r = sqlite_query($this->link, $query);
			}

			// loger les warnings/erreurs eventuels de sqlite remontant dans PHP
			if ($err = (function_exists('error_get_last')?error_get_last():"") AND $err!=$e){
				$err = strip_tags($err['message'])." in ".$err['file']." line ".$err['line'];
				spip_log("$err - ".$query, 'sqlite.'._LOG_ERREUR);
			}
			else $err = "";

		}
		else {
			$r = false;
		}

		if (spip_sqlite_errno($this->serveur))
			$err .= spip_sqlite_error($query, $this->serveur);
		return $t ? trace_query_end($query, $t, $r, $err, $this->serveur) : $r;
	}

	function last_insert_id(){
		if ($this->sqlite_version==3)
			return $this->link->lastInsertId();
		else
			return sqlite_last_insert_rowid($this->link);
	}
}


/**
 * Cette classe est presente essentiellement pour un preg_replace_callback
 * avec des parametres dans la fonction appelee que l'on souhaite incrementer
 * (fonction pour proteger les textes)
 */
class sqlite_traducteur {
	var $query = '';
	var $prefixe = ''; // le prefixe des tables
	var $sqlite_version = ''; // Version de sqlite (2 ou 3)
	
	// Pour les corrections a effectuer sur les requetes :
	var $textes = array(); // array(code=>'texte') trouvé

	function sqlite_traducteur($query, $prefixe, $sqlite_version){
		$this->query = $query;
		$this->prefixe = $prefixe;
		$this->sqlite_version = $sqlite_version;
	}

	/**
	 * transformer la requete pour sqlite
	 * enleve les textes, transforme la requete pour quelle soit
	 * bien interpretee par sqlite, puis remet les textes
	 * la fonction affecte $this->query
	 * http://doc.spip.org/@traduire_requete
	 *
	 * @return void
	 */
	function traduire_requete(){
		//
		// 1) Protection des textes en les remplacant par des codes
		//
		// enlever les 'textes' et initialiser avec
		list($this->query, $textes) = query_echappe_textes($this->query);

		//
		// 2) Corrections de la requete
		//
		// Correction Create Database
		// Create Database -> requete ignoree
		if (strpos($this->query, 'CREATE DATABASE')===0){
			spip_log("Sqlite : requete non executee -> $this->query", 'sqlite.'._LOG_AVERTISSEMENT);
			$this->query = "SELECT 1";
		}

		// Correction Insert Ignore
		// INSERT IGNORE -> insert (tout court et pas 'insert or replace')
		if (strpos($this->query, 'INSERT IGNORE')===0){
			spip_log("Sqlite : requete transformee -> $this->query", 'sqlite.'._LOG_DEBUG);
			$this->query = 'INSERT '.substr($this->query, '13');
		}

		// Correction des dates avec INTERVAL
		// utiliser sql_date_proche() de preference
		if (strpos($this->query, 'INTERVAL')!==false){
			$this->query = preg_replace_callback("/DATE_(ADD|SUB)(.*)INTERVAL\s+(\d+)\s+([a-zA-Z]+)\)/U",
			                                     array(&$this, '_remplacerDateParTime'),
			                                     $this->query);
		}

		if (strpos($this->query, 'LEFT(')!==false){
			$this->query = str_replace('LEFT(','_LEFT(',$this->query);
		}

		if (strpos($this->query, 'TIMESTAMPDIFF(')!==false){
			$this->query = preg_replace('/TIMESTAMPDIFF\(\s*([^,]*)\s*,/Uims',"TIMESTAMPDIFF('\\1',",$this->query);
		}


		// Correction Using
		// USING (non reconnu en sqlite2)
		// problematique car la jointure ne se fait pas du coup.
		if (($this->sqlite_version==2) && (strpos($this->query, "USING")!==false)){
			spip_log("'USING (champ)' n'est pas reconnu en SQLite 2. Utilisez 'ON table1.champ = table2.champ'", 'sqlite.'._LOG_ERREUR);
			$this->query = preg_replace('/USING\s*\([^\)]*\)/', '', $this->query);
		}

		// Correction Field
		// remplace FIELD(table,i,j,k...) par CASE WHEN table=i THEN n ... ELSE 0 END
		if (strpos($this->query, 'FIELD')!==false){
			$this->query = preg_replace_callback('/FIELD\s*\(([^\)]*)\)/',
			                                     array(&$this, '_remplacerFieldParCase'),
			                                     $this->query);
		}

		// Correction des noms de tables FROM
		// mettre les bons noms de table dans from, update, insert, replace...
		if (preg_match('/\s(SET|VALUES|WHERE|DATABASE)\s/iS', $this->query, $regs)){
			$suite = strstr($this->query, $regs[0]);
			$this->query = substr($this->query, 0, -strlen($suite));
		}
		else
			$suite = '';
		$pref = ($this->prefixe) ? $this->prefixe."_" : "";
		$this->query = preg_replace('/([,\s])spip_/S', '\1'.$pref, $this->query).$suite;

		// Correction zero AS x
		// pg n'aime pas 0+x AS alias, sqlite, dans le meme style, 
		// n'apprecie pas du tout SELECT 0 as x ... ORDER BY x
		// il dit que x ne doit pas être un integer dans le order by !
		// on remplace du coup x par vide() dans ce cas uniquement
		//
		// rien que pour public/vertebrer.php ?
		if ((strpos($this->query, "0 AS")!==false)){
			// on ne remplace que dans ORDER BY ou GROUP BY
			if (preg_match('/\s(ORDER|GROUP) BY\s/i', $this->query, $regs)){
				$suite = strstr($this->query, $regs[0]);
				$this->query = substr($this->query, 0, -strlen($suite));

				// on cherche les noms des x dans 0 AS x
				// on remplace dans $suite le nom par vide()
				preg_match_all('/\b0 AS\s*([^\s,]+)/', $this->query, $matches, PREG_PATTERN_ORDER);
				foreach ($matches[1] as $m){
					$suite = str_replace($m, 'VIDE()', $suite);
				}
				$this->query .= $suite;
			}
		}

		// Correction possible des divisions entieres
		// Le standard SQL (lequel? ou?) semble indiquer que
		// a/b=c doit donner c entier si a et b sont entiers 4/3=1.
		// C'est ce que retournent effectivement SQL Server et SQLite
		// Ce n'est pas ce qu'applique MySQL qui retourne un reel : 4/3=1.333...
		// 
		// On peut forcer la conversion en multipliant par 1.0 avant la division
		// /!\ SQLite 3.5.9 Debian/Ubuntu est victime d'un bug en plus ! 
		// cf. https://bugs.launchpad.net/ubuntu/+source/sqlite3/+bug/254228
		//     http://www.sqlite.org/cvstrac/tktview?tn=3202
		// (4*1.0/3) n'est pas rendu dans ce cas !
		# $this->query = str_replace('/','* 1.00 / ',$this->query);


		// Correction critere REGEXP, non reconnu en sqlite2
		if (($this->sqlite_version==2) && (strpos($this->query, 'REGEXP')!==false)){
			$this->query = preg_replace('/([^\s\(]*)(\s*)REGEXP(\s*)([^\s\)]*)/', 'REGEXP($4, $1)', $this->query);
		}

		//
		// 3) Remise en place des textes d'origine
		//
		// Correction Antiquotes et echappements
		// ` => rien
		if (strpos($this->query,'`')!==false)
			$this->query = str_replace('`','', $this->query);

		$this->query = query_reinjecte_textes($this->query, $textes);

		return $this->query;
	}


	/**
	 * les callbacks
	 * remplacer DATE_ / INTERVAL par DATE...strtotime
	 * http://doc.spip.org/@_remplacerDateParTime
	 *
	 * @param  $matches
	 * @return string
	 */
	function _remplacerDateParTime($matches){
		$op = strtoupper($matches[1]=='ADD') ? '+' : '-';
		return "datetime$matches[2] '$op$matches[3] $matches[4]')";
	}

	/**
	 * callback ou l'on remplace FIELD(table,i,j,k...) par CASE WHEN table=i THEN n ... ELSE 0 END
	 * http://doc.spip.org/@_remplacerFieldParCase
	 *
	 * @param  $matches
	 * @return string
	 */
	function _remplacerFieldParCase($matches){
		$fields = substr($matches[0], 6, -1); // ne recuperer que l'interieur X de field(X)
		$t = explode(',', $fields);
		$index = array_shift($t);

		$res = '';
		$n = 0;
		foreach ($t as $v){
			$n++;
			$res .= "\nWHEN $index=$v THEN $n";
		}
		return "CASE $res ELSE 0 END ";
	}

}

?>
