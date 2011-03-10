<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// infos :
// il ne faut pas avoir de PDO::CONSTANTE dans ce fichier sinon php4 se tue !
// idem, il ne faut pas de $obj->toto()->toto sinon php4 se tue !

# todo : get/set_caracteres ?


/*
 * 
 * regroupe le maximum de fonctions qui peuvent cohabiter
 * D'abord les fonctions d'abstractions de SPIP
 * 
 */
// http://doc.spip.org/@req_sqlite_dist
function req_sqlite_dist($addr, $port, $login, $pass, $db='', $prefixe='', $sqlite_version=''){
	static $last_connect = array();

	// si provient de selectdb
	// un code pour etre sur que l'on vient de select_db()
	if (strpos($db, $code = '@selectdb@')!==false) {
		foreach (array('addr','port','login','pass','prefixe') as $a){
			$$a = $last_connect[$a];
		}
		$db = str_replace($code, '', $db);
	}

	/*
	 * En sqlite, seule l'adresse du fichier est importante.
	 * Ce sera $db le nom, et le path _DIR_DB
	 */
	_sqlite_init();

	// un nom de base demande et impossible d'obtenir la base, on s'en va
	if ($db && !is_file($f = _DIR_DB . $db . '.sqlite') && !is_writable(_DIR_DB))
			return false;

	
	// charger les modules sqlite au besoin
	if (!_sqlite_charger_version($sqlite_version)) {
		spip_log("Impossible de trouver/charger le module SQLite ($sqlite_version)!");
		return false;
	}

	// chargement des constantes
	// il ne faut pas definir les constantes avant d'avoir charge les modules sqlite
	$define = "spip_sqlite".$sqlite_version."_constantes";
	$define();
	
	$ok = false;
	if (!$db){
		// si installation -> base temporaire tant qu'on ne connait pas son vrai nom
		if (defined('_ECRIRE_INSTALL') && _ECRIRE_INSTALL){
			// creation d'une base temporaire pour le debut d'install
			$db = "_sqlite".$sqlite_version."_install";	
			$tmp = _DIR_DB . $db . ".sqlite";
			if ($sqlite_version == 3) {
				$ok = $link = new PDO("sqlite:$tmp");
			} else {
				$ok = $link = sqlite_open($tmp, _SQLITE_CHMOD, $err);
			}
		// sinon, on arrete finalement
		} else {
			return false;
		}
	} else {
		// Ouvrir (eventuellement creer la base)
		// si pas de version fourni, on essaie la 3, sinon la 2
		if ($sqlite_version == 3) {
			$ok = $link = new PDO("sqlite:$f");
		} else {
			$ok = $link = sqlite_open($f, _SQLITE_CHMOD, $err);
		}
	}

	if (!$ok){
		spip_log("Impossible d'ouvrir la base de donnee SQLite ($sqlite_version) : $f ");
		return false;
	}
	
	if ($link) {
		$last_connect = array (
			'addr' => $addr,
			'port' => $port,
			'login' => $login,
			'pass' => $pass,
			'db' => $db,
			'prefixe' => $prefixe,
		);
	}

	return array(
		'db' => $db,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $link,
		);	
}



// Fonction de requete generale, munie d'une trace a la demande
// http://doc.spip.org/@spip_sqlite_query
function spip_sqlite_query($query, $serveur='',$requeter=true) {
#spip_log("spip_sqlite_query() > $query");
	_sqlite_init();
	
	$requete = new sqlite_traiter_requete($query, $serveur);
	$requete->traduire_requete(); // mysql -> sqlite
	if (!$requeter) return $requete->query;
	return $requete->executer_requete();
}


/* ordre alphabetique pour les autres */

// http://doc.spip.org/@spip_sqlite_alter
function spip_sqlite_alter($query, $serveur='',$requeter=true){

	$query = spip_sqlite_query("ALTER $query",$serveur,false);
	
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
		spip_log("SQLite : Probleme de ALTER TABLE mal forme dans $query", 'sqlite');
		return false;
	}

	// 2
	// il faudrait une regexp pour eviter de spliter ADD PRIMARY KEY (colA, colB)
	// tout en cassant "ADD PRIMARY KEY (colA, colB), ADD INDEX (chose)"... en deux
	// ou revoir l'api de sql_alter en creant un 
	// sql_alter_table($table,array($actions));
	$todo = explode(',', $suite);

	// on remet les morceaux dechires ensembles... que c'est laid !
	$todo2 = array(); $i=0;
	$ouverte = false;
	while ($do = array_shift($todo)) {
		$todo2[$i] = isset($todo2[$i]) ? $todo2[$i] . "," . $do : $do;
		$o=(false!==strpos($do,"("));
		$f=(false!==strpos($do,")"));
		if ($o AND !$f) $ouverte=true;
		elseif ($f) $ouverte=false;
		if (!$ouverte) $i++;
	}
	
	// 3	
	$resultats = array();
	foreach ($todo2 as $do){
		$do = trim($do);
		if (!preg_match('/(DROP PRIMARY KEY|DROP INDEX|DROP COLUMN|DROP'
				.'|CHANGE COLUMN|CHANGE|MODIFY|RENAME TO|RENAME'
				.'|ADD PRIMARY KEY|ADD INDEX|ADD COLUMN|ADD'
				.')\s*([^\s]*)\s*(.*)?/', $do, $matches)){
			spip_log("SQLite : Probleme de ALTER TABLE, utilisation non reconnue dans : $do \n(requete d'origine : $query)", 'sqlite');
			return false;
		}

		$cle = strtoupper($matches[1]);
		$colonne_origine = $matches[2];
		$colonne_destination = '';

		$def = $matches[3];

		// eluder une eventuelle clause before|after|first inutilisable
		$defr = rtrim(preg_replace('/(BEFORE|AFTER|FIRST)(.*)$/is','', $def));
		// remplacer les definitions venant de mysql
		$defr = _sqlite_remplacements_definitions_table($defr);

		// reinjecter dans le do
		$do = str_replace($def,$defr,$do);
		$def = $defr;
		
		switch($cle){
			// suppression d'un index
			case 'DROP INDEX':
				$nom_index = $colonne_origine;
				spip_sqlite_drop_index($nom_index, $table, $serveur);
				break;
			
			// suppression d'une pk
			case 'DROP PRIMARY KEY':
				if (!_sqlite_modifier_table(
					$table, 
					$colonne_origine, 
					array('key'=>array('PRIMARY KEY'=>'')), 
					$serveur)){
						return false;
				}
				break;
			// suppression d'une colonne
			case 'DROP COLUMN':
			case 'DROP':
				if (!_sqlite_modifier_table(
					$table, 
					array($colonne_origine=>""), 
					'', 
					$serveur)){
						return false;
				}
				break;
			
			case 'CHANGE COLUMN':
			case 'CHANGE':
				// recuperer le nom de la future colonne 
				$def = trim($def);
				$colonne_destination = substr($def, 0, strpos($def,' '));
				$def = substr($def, strlen($colonne_destination)+1);
				
				if (!_sqlite_modifier_table(
					$table, 
					array($colonne_origine=>$colonne_destination), 
					array('field'=>array($colonne_destination=>$def)), 
					$serveur)){
						return false;
				}
				break;
				
			case 'MODIFY':
				if (!_sqlite_modifier_table(
					$table, 
					$colonne_origine, 
					array('field'=>array($colonne_origine=>$def)), 
					$serveur)){
						return false;
				}
				break;
			
			// pas geres en sqlite2
			case 'RENAME':
					$do = "RENAME TO" . substr($do,6);
			case 'RENAME TO':
				if (_sqlite_is_version(3, '', $serveur)){
					$requete = new sqlite_traiter_requete("$debut $do", $serveur);
					if (!$requete->executer_requete()){
						spip_log("SQLite : Erreur ALTER TABLE / RENAME : $query", 'sqlite');
						return false;
					}
				// artillerie lourde pour sqlite2 !
				} else {
					$table_dest = trim(substr($do, 9));
					if (!_sqlite_modifier_table(array($table=>$table_dest), '', '', $serveur)){
						spip_log("SQLite : Erreur ALTER TABLE / RENAME : $query", 'sqlite');
						return false;
					}
				}
				break;

			// ajout d'une pk
			case 'ADD PRIMARY KEY':
				$pk = trim(substr($do,16));
				$pk = ($pk[0]=='(') ? substr($pk,1,-1) : $pk;
				if (!_sqlite_modifier_table(
					$table, 
					$colonne_origine, 
					array('key'=>array('PRIMARY KEY'=>$pk)), 
					$serveur)){
						return false;
				}
				break;			
			// ajout d'un index
			case 'ADD INDEX':
				// peut etre "(colonne)" ou "nom_index (colonnes)"
				// bug potentiel si qqn met "(colonne, colonne)"
				//
				// nom_index (colonnes)
				if ($def) {
					$colonnes = substr($def,1,-1);
					$nom_index = $colonne_origine;
				}
				else {
					// (colonne)
					if ($colonne_origine[0] == "(") {
						$colonnes = substr($colonne_origine,1,-1);
						if (false!==strpos(",",$colonnes)) {
							spip_log("SQLite : Erreur, impossible de creer un index sur plusieurs colonnes"
							 	." sans qu'il ait de nom ($table, ($colonnes))", 'sqlite');	
							break;
						} else {
							$nom_index = $colonnes;
						}
					}
					// nom_index
					else {
						$nom_index = $colonnes = $colonne_origine ;
					}
				}
				spip_sqlite_create_index($nom_index, $table, $colonnes, $serveur);
				break;
				
			// pas geres en sqlite2
			case 'ADD COLUMN':
					$do = "ADD".substr($do, 10);
			case 'ADD':
			default:
				if (_sqlite_is_version(3, '', $serveur)){
					$requete = new sqlite_traiter_requete("$debut $do", $serveur);
					if (!$requete->executer_requete()){
						spip_log("SQLite : Erreur ALTER TABLE / ADD : $query", 'sqlite');
						return false;
					}
					break;
				// artillerie lourde pour sqlite2 !
				} else {
					$def = trim(substr($do, 3));
					$colonne_ajoutee = substr($def, 0, strpos($def,' '));
					$def = substr($def, strlen($colonne_ajoutee)+1);
					if (!_sqlite_modifier_table($table, array($colonne_ajoutee), array('field'=>array($colonne_ajoutee=>$def)), $serveur)){
						spip_log("SQLite : Erreur ALTER TABLE / ADD : $query", 'sqlite');
						return false;
					}
				}
				break;
		}
		// tout est bon, ouf !
		spip_log("SQLite ($serveur) : Changements OK : $debut $do");
	}

	spip_log("SQLite ($serveur) : fin ALTER TABLE OK !");
	return true;
}


// Fonction de creation d'une table SQL nommee $nom
// http://doc.spip.org/@spip_sqlite_create
function spip_sqlite_create($nom, $champs, $cles, $autoinc=false, $temporary=false, $serveur='',$requeter=true) {
	$query = _sqlite_requete_create($nom, $champs, $cles, $autoinc, $temporary, $ifnotexists=true, $serveur, $requeter);
	if (!$query) return false;
	$res = spip_sqlite_query($query, $serveur, $requeter);
	
	// SQLite ne cree pas les KEY sur les requetes CREATE TABLE
	// il faut donc les faire creer ensuite
	if (!$requeter) return $res;

	$ok = $res ? true : false;
	if ($ok) {
		foreach($cles as $k=>$v) {
			if (strpos($k, "KEY ") === 0) {
				$index = preg_replace("/KEY +/", '',$k);
				$ok &= $res = spip_sqlite_create_index($index, $nom, $v, $serveur);
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
function spip_sqlite_create_base($nom, $serveur='', $option=true) {
	$f = _DIR_DB . $nom . '.sqlite';
	if (_sqlite_is_version(2, '', $serveur)) {
		$ok = sqlite_open($f, _SQLITE_CHMOD, $err);
	} else {
		$ok = new PDO("sqlite:$f");
	}
	if ($ok) {
		unset($ok);
		return true;
	}
	unset($ok);
	return false;
}


// Fonction de creation d'une vue SQL nommee $nom
// http://doc.spip.org/@spip_sqlite_create_view
function spip_sqlite_create_view($nom, $query_select, $serveur='',$requeter=true) {
	if (!$query_select) return false;
	// vue deja presente
	if (sql_showtable($nom, false, $serveur)) {
		spip_log("Echec creation d'une vue sql ($nom) car celle-ci existe deja (serveur:$serveur)");
		return false;
	}
	
	$query = "CREATE VIEW $nom AS ". $query_select;
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
function spip_sqlite_create_index($nom, $table, $champs, $serveur='', $requeter=true) {
	if (!($nom OR $table OR $champs)) {
		spip_log("Champ manquant pour creer un index sqlite ($nom, $table, (".join(',',$champs)."))");
		return false;
	}
	
	// SQLite ne differentie pas noms des index en fonction des tables
	// il faut donc creer des noms uniques d'index pour une base sqlite
	$nom = $table.'_'.$nom;
	// enlever d'eventuelles parentheses deja presentes sur champs
	if (!is_array($champs)){
		 if ($champs[0]=="(") $champs = substr($champs,1,-1);
		 $champs = array($champs);
	}
	$query = "CREATE INDEX $nom ON $table (" . join(',',$champs) . ")";
	$res = spip_sqlite_query($query, $serveur, $requeter);
	if (!$requeter) return $res;
	if ($res)
		return true;
	else
		return false;
}

// en PDO/sqlite3, il faut calculer le count par une requete count(*)
// pour les resultats de SELECT
// cela est fait sans spip_sqlite_query()
// http://doc.spip.org/@spip_sqlite_count
function spip_sqlite_count($r, $serveur='',$requeter=true) {
	if (!$r) return 0;
		
	if (_sqlite_is_version(3, '', $serveur)){
		// select ou autre (insert, update,...) ?
		if (isset($r->spipSqliteRowCount)) {
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
function spip_sqlite_countsel($from = array(), $where = array(), $groupby = '', $having = array(), $serveur='',$requeter=true) {
	$c = !$groupby ? '*' : ('DISTINCT ' . (is_string($groupby) ? $groupby : join(',', $groupby)));
	$r = spip_sqlite_select("COUNT($c)", $from, $where,'', '', '',$having, $serveur, $requeter);
	if ((is_resource($r) or is_object($r)) && $requeter) { // ressource : sqlite2, object : sqlite3
		if (_sqlite_is_version(3,'',$serveur)){
			list($n) = spip_sqlite_fetch($r, SPIP_SQLITE3_NUM, $serveur);
		} else {
			list($n) = spip_sqlite_fetch($r, SPIP_SQLITE2_NUM, $serveur);
		}
		spip_sqlite_free($r,$serveur);
	}
	return $n;
}



// http://doc.spip.org/@spip_sqlite_delete
function spip_sqlite_delete($table, $where='', $serveur='',$requeter=true) {
	$res = spip_sqlite_query(
			  _sqlite_calculer_expression('DELETE FROM', $table, ',')
			. _sqlite_calculer_expression('WHERE', $where),
			$serveur, $requeter);

	// renvoyer la requete inerte si demandee
	if (!$requeter) return $res;
	
  if ($res){
	  $link  = _sqlite_link($serveur);
	  if (_sqlite_is_version(3, $link)) {
		  return $res->rowCount();
	  } else {
		  return sqlite_changes($link);
	  }
  }
  else
	  return false;
}


// http://doc.spip.org/@spip_sqlite_drop_table
function spip_sqlite_drop_table($table, $exist='', $serveur='',$requeter=true) {
	if ($exist) $exist =" IF EXISTS";
	
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

// supprime une vue 
// http://doc.spip.org/@spip_sqlite_drop_view
function spip_sqlite_drop_view($view, $exist='', $serveur='',$requeter=true) {
	if ($exist) $exist =" IF EXISTS";
	
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
function spip_sqlite_drop_index($nom, $table, $serveur='', $requeter=true) {
	if (!($nom OR $table)) {
		spip_log("Champ manquant pour supprimer un index sqlite ($nom, $table)");
		return false;
	}
	
	// SQLite ne differentie pas noms des index en fonction des tables
	// il faut donc creer des noms uniques d'index pour une base sqlite
	$index = $table.'_'.$nom;
	$exist =" IF EXISTS";
	
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
 * Retourne la derniere erreur generee
 *
 * @param $serveur nom de la connexion
 * @return string erreur eventuelle
**/
// http://doc.spip.org/@spip_sqlite_error
function spip_sqlite_error($query='', $serveur='') {
	$link  = _sqlite_link($serveur);
	
	if (_sqlite_is_version(3, $link)) {
		$errs = $link->errorInfo();
		$s = '';
		foreach($errs as $n=>$e){
			$s .= "\n$n : $e";
		}
	} elseif ($link) {
		$s = sqlite_error_string(sqlite_last_error($link));
	} else {
		$s = ": aucune ressource sqlite (link)";
	}
	if ($s) spip_log("$s - $query", 'sqlite');
	return $s;
}

/**
 * Retourne le numero de la derniere erreur SQL
 * (sauf que SQLite semble ne connaitre que 0 ou 1)
 *
 * @param $serveur nom de la connexion
 * @return int 0 pas d'erreur / 1 une erreur
**/
// http://doc.spip.org/@spip_sqlite_errno
function spip_sqlite_errno($serveur='') {
	$link  = _sqlite_link($serveur);
	
	if (_sqlite_is_version(3, $link)){
		$t = $link->errorInfo();
		$s = $t[1];
	} elseif ($link) {
		$s = sqlite_last_error($link);
	} else {
		$s = ": aucune ressource sqlite (link)";	
	}
		
	if ($s) spip_log("Erreur sqlite $s");

	return $s ? 1 : 0;
}


// http://doc.spip.org/@spip_sqlite_explain
function spip_sqlite_explain($query, $serveur='',$requeter=true){
	if (strpos(ltrim($query), 'SELECT') !== 0) return array();

	$requete = new sqlite_traiter_requete("$query", $serveur);
	$requete->traduire_requete(); // mysql -> sqlite
	$requete->query = 'EXPLAIN ' . $requete->query;
	if (!$requeter) return $requete;
	// on ne trace pas ces requetes, sinon on obtient un tracage sans fin...
	$requete->tracer = false; 
	$r = $requete->executer_requete();

	return $r ? spip_sqlite_fetch($r, null, $serveur) : false; // hum ? etrange ca... a verifier
}


// http://doc.spip.org/@spip_sqlite_fetch
function spip_sqlite_fetch($r, $t='', $serveur='',$requeter=true) {

	$link = _sqlite_link($serveur);
	if (!$t) {
		if (_sqlite_is_version(3, $link)) {
			$t = SPIP_SQLITE3_ASSOC;
		} else {
			$t = SPIP_SQLITE2_ASSOC;
		}
	}


	if (_sqlite_is_version(3, $link)){
		if ($r) $retour = $r->fetch($t);
	} elseif ($r) {
		$retour = sqlite_fetch_array($r, $t);
	}
	
	// les version 2 et 3 parfois renvoie des 'table.titre' au lieu de 'titre' tout court ! pff !
	// suppression de 'table.' pour toutes les cles (c'est un peu violent !)
	if ($retour){
		$new = array();
		foreach ($retour as $cle=>$val){
			if (($pos = strpos($cle, '.'))!==false){
				$cle = substr($cle,++$pos);
			}
			$new[$cle] = $val;
		}
		$retour = &$new;
	}

	return $retour;
}


function spip_sqlite_seek($r, $row_number, $serveur='',$requeter=true) {
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
function spip_sqlite_free(&$r, $serveur='',$requeter=true) {
	unset($r);
	return true;
	//return sqlite_free_result($r);
}


// http://doc.spip.org/@spip_sqlite_get_charset
function spip_sqlite_get_charset($charset=array(), $serveur='',$requeter=true){
	//$c = !$charset ? '' : (" LIKE "._q($charset['charset']));
	//return spip_sqlite_fetch(sqlite_query(_sqlite_link($serveur), "SHOW CHARACTER SET$c"), NULL, $serveur);
}


// http://doc.spip.org/@spip_sqlite_hex
function spip_sqlite_hex($v){
	return hexdec($v);
}


// http://doc.spip.org/@spip_sqlite_in
function spip_sqlite_in($val, $valeurs, $not='', $serveur='',$requeter=true) {
	$n = $i = 0;
	$in_sql ="";
	while ($n = strpos($valeurs, ',', $n+1)) {
	  if ((++$i) >= 255) {
			$in_sql .= "($val $not IN (" .
			  substr($valeurs, 0, $n) .
			  "))\n" .
			  ($not ? "AND\t" : "OR\t");
			$valeurs = substr($valeurs, $n+1);
			$i = $n = 0;
		}
	}
	$in_sql .= "($val $not IN ($valeurs))";

	return "($in_sql)";
}


// http://doc.spip.org/@spip_sqlite_insert
function spip_sqlite_insert($table, $champs, $valeurs, $desc='', $serveur='',$requeter=true) {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$sqlite = $connexion['link'];
	$db = $connexion['db'];

	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);


	if (isset($_GET['var_profile'])) {
		include_spip('public/tracer');
		$t = trace_query_start();
	} else $t = 0 ;
 
	$query="INSERT INTO $table ".($champs?"$champs VALUES $valeurs":"DEFAULT VALUES");
	
	
	if ($r = spip_sqlite_query($query, $serveur, $requeter)) {
		if (!$requeter) return $r;
		
		if (_sqlite_is_version(3, $sqlite)) $nb = $sqlite->lastInsertId();
		else $nb = sqlite_last_insert_rowid($sqlite);
	} else $nb = 0;

	$err = spip_sqlite_error($query, $serveur);
	return $t ? trace_query_end($query, $t, $nb, $err, $serveur) : $nb;

}


// http://doc.spip.org/@spip_sqlite_insertq
function spip_sqlite_insertq($table, $couples=array(), $desc=array(), $serveur='',$requeter=true) {
	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  isset($desc['field'])?$desc['field']:array();

	foreach ($couples as $champ => $val) {
		$couples[$champ]= _sqlite_calculer_cite($val, $fields[$champ]);
	}

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$couples = _sqlite_ajouter_champs_timestamp($table, $couples, $desc, $serveur);

	// si aucun champ donne pour l'insertion, on en cherche un avec un DEFAULT
	// sinon sqlite3 ne veut pas inserer
	$cles = $valeurs = "";
	if (count($couples)) {
		$cles = "(".join(',',array_keys($couples)).")";
		$valeurs = "(".join(',', $couples).")";
	}
	
	return spip_sqlite_insert($table, $cles , $valeurs , $desc, $serveur, $requeter);
}



// http://doc.spip.org/@spip_sqlite_insertq_multi
function spip_sqlite_insertq_multi($table, $tab_couples=array(), $desc=array(), $serveur='',$requeter=true) {
	foreach ($tab_couples as $couples) {
		$retour = spip_sqlite_insertq($table, $couples, $desc, $serveur, $requeter);
	}
	// renvoie le dernier id d'autoincrement ajoute
	return $retour;
}



// http://doc.spip.org/@spip_sqlite_listdbs
function spip_sqlite_listdbs($serveur='',$requeter=true) {
	_sqlite_init();
	
	if (!is_dir($d = substr(_DIR_DB,0,-1))){
		return array();
	}
	
	include_spip('inc/flock');
	$bases = preg_files($d, $pattern = '(.*)\.sqlite$');
	$bds = array();

	foreach($bases as $b){
		// pas de bases commencant pas sqlite 
		// (on s'en sert pour l'installation pour simuler la presence d'un serveur)
		// les bases sont de la forme _sqliteX_tmp_spip_install.sqlite
		if (strpos($b, '_sqlite')) continue;
		$bds[] = preg_replace(";.*/$pattern;iS",'$1', $b);
	}

	return $bds;
}


// http://doc.spip.org/@spip_sqlite_multi
function spip_sqlite_multi ($objet, $lang) {
	$r = "PREG_REPLACE("
	  . $objet
	  . ",'<multi>.*[\[]"
	  . $lang
	  . "[\]]([^\[]*).*</multi>', '$1') AS multi";
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
function spip_sqlite_optimize($table, $serveur='', $requeter=true) {
	static $do = false;
	if ($requeter and $do) {return true;}
	if ($requeter) { $do = true; }
	return spip_sqlite_query("VACUUM", $serveur, $requeter);
}


// avoir le meme comportement que _q()
function spip_sqlite_quote($v, $type=''){
	if (is_array($v)) return join(",", array_map('spip_sqlite_quote', $v));
	if (is_int($v)) return strval($v);
	if (strncmp($v,'0x',2)==0 AND ctype_xdigit(substr($v,2))) return hexdec(substr($v,2));
	if ($type === 'int' AND !$v) return '0';

	if (function_exists('sqlite_escape_string')) {
		return "'" . sqlite_escape_string($v) . "'";
	}
	
	// trouver un link sqlite3 pour faire l'echappement
	foreach ($GLOBALS['connexions'] as $s) {
		if (_sqlite_is_version(3, $l = $s['link'])){
			return	$l->quote($v);
		}	
	}
}


/**
 * Tester si une date est proche de la valeur d'un champ
 *
 * @param string $champ le nom du champ a tester
 * @param int $interval valeur de l'interval : -1, 4, ...
 * @param string $unite utite utilisee (DAY, MONTH, YEAR, ...)
 * @return string expression SQL
**/
function spip_sqlite_date_proche($champ, $interval, $unite)
{
	$op = $interval > 0 ? '>' : '<';
	return "($champ $op datetime('" . date("Y-m-d H:i:s") . "', '$interval $unite'))";
}


// http://doc.spip.org/@spip_sqlite_replace
function spip_sqlite_replace($table, $couples, $desc=array(), $serveur='',$requeter=true) {
	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  isset($desc['field'])?$desc['field']:array();

	foreach ($couples as $champ => $val) {
		$couples[$champ]= _sqlite_calculer_cite($val, $fields[$champ]);
	}
	
	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$couples = _sqlite_ajouter_champs_timestamp($table, $couples, $desc, $serveur);
	
	return spip_sqlite_query("REPLACE INTO $table (" . join(',',array_keys($couples)) . ') VALUES (' .join(',',$couples) . ')', $serveur);
}



// http://doc.spip.org/@spip_sqlite_replace_multi
function spip_sqlite_replace_multi($table, $tab_couples, $desc=array(), $serveur='',$requeter=true) {
	
	// boucler pour trainter chaque requete independemment
	foreach ($tab_couples as $couples){
		$retour = spip_sqlite_replace($table, $couples, $desc, $serveur,$requeter);
	}
	// renvoie le dernier id	
	return $retour; 
}


// http://doc.spip.org/@spip_sqlite_select
function spip_sqlite_select($select, $from, $where='', $groupby='', $orderby='', $limit='', $having='', $serveur='',$requeter=true) {	

	// version() n'est pas connu de sqlite
	$select = str_replace('version()', 'sqlite_version()',$select);

	// recomposer from
	$from = (!is_array($from) ? $from : _sqlite_calculer_select_as($from));

	$query = 
		  _sqlite_calculer_expression('SELECT', $select, ', ')
		. _sqlite_calculer_expression('FROM', $from, ', ')
		. _sqlite_calculer_expression('WHERE', $where)
		. _sqlite_calculer_expression('GROUP BY', $groupby, ',')
		. _sqlite_calculer_expression('HAVING', $having)
		. ($orderby ? ("\nORDER BY " . _sqlite_calculer_order($orderby)) :'')
		. ($limit ? "\nLIMIT $limit" : '');

	return spip_sqlite_query($query, $serveur, $requeter);
}


// http://doc.spip.org/@spip_sqlite_selectdb
function spip_sqlite_selectdb($db, $serveur='',$requeter=true) {
	_sqlite_init();

	// interdire la creation d'une nouvelle base, 
	// sauf si on est dans l'installation
	if (!is_file($f = _DIR_DB . $db . '.sqlite')
		&& (!defined('_ECRIRE_INSTALL') || !_ECRIRE_INSTALL))
		return false;

	// se connecter a la base indiquee
	// avec les identifiants connus
	$index = $serveur ? $serveur : 0;

	if ($link = spip_connect_db('', '', '', '', '@selectdb@' . $db , $serveur, '', '')){
		if (($db==$link['db']) && $GLOBALS['connexions'][$index] = $link)
			return $db;					
	} else {
		spip_log("Impossible de selectionner la base $db", 'sqlite');
		return false;
	}

}


// http://doc.spip.org/@spip_sqlite_set_charset
function spip_sqlite_set_charset($charset, $serveur='',$requeter=true){
	#spip_log("changement de charset sql : "."SET NAMES "._q($charset));
	# return spip_sqlite_query("SET NAMES ". spip_sqlite_quote($charset), $serveur); //<-- Passe pas !
}


// http://doc.spip.org/@spip_sqlite_showbase
function spip_sqlite_showbase($match, $serveur='',$requeter=true){
	// type est le type d'entrée : table / index / view
	// on ne retourne que les tables (?) et non les vues...
	# ESCAPE non supporte par les versions sqlite <3
	#	return spip_sqlite_query("SELECT name FROM sqlite_master WHERE type='table' AND tbl_name LIKE "._q($match)." ESCAPE '\'", $serveur, $requeter);
	$match = preg_quote($match);
	$match = str_replace("\\\_","[[TIRETBAS]]",$match);
	$match = str_replace("\\\%","[[POURCENT]]",$match);
	$match = str_replace("_",".",$match);
	$match = str_replace("%",".*",$match);
	$match = str_replace("[[TIRETBAS]]","_",$match);
	$match = str_replace("[[POURCENT]]","%",$match);
	$match = "^$match$";
	return spip_sqlite_query("SELECT name FROM sqlite_master WHERE type='table' AND tbl_name REGEXP "._q($match), $serveur, $requeter);
}


// http://doc.spip.org/@spip_sqlite_showtable
function spip_sqlite_showtable($nom_table, $serveur='',$requeter=true){
	
	$query = 
			'SELECT sql, type FROM'
   			. ' (SELECT * FROM sqlite_master UNION ALL'
   			. ' SELECT * FROM sqlite_temp_master)'
			. " WHERE tbl_name LIKE '$nom_table'"
			. " AND type!='meta' AND sql NOT NULL AND name NOT LIKE 'sqlite_%'"
			. ' ORDER BY substr(type,2,1), name';
	
	$a = spip_sqlite_query($query, $serveur, $requeter);
	if (!$a) return "";
	if (!$requeter) return $a;
	if (!($a = spip_sqlite_fetch($a, null, $serveur))) return "";
	$vue = ($a['type'] == 'view'); // table | vue

	// c'est une table
	// il faut parser le create
	if (!$vue) {
		if (!preg_match("/^[^(),]*\((([^()]*(\([^()]*\))?[^()]*)*)\)[^()]*$/", array_shift($a), $r))
			return "";
		else {
			$dec = $r[1];
			if (preg_match("/^(.*?),([^,]*KEY.*)$/s", $dec, $r)) {
				$namedkeys = $r[2];
				$dec = $r[1];
			}
			else 
				$namedkeys = "";

			$fields = array();
			foreach (explode(",",$dec) as $v) {
				preg_match("/^\s*([^\s]+)\s+(.*)/",$v,$r);
				// trim car 'Sqlite Manager' (plugin Firefox) utilise des guillemets
				// lorsqu'on modifie une table avec cet outil.
				// possible que d'autres fassent de meme.
				$fields[ trim(strtolower($r[1]),'"') ] = $r[2];
			}
			// key inclues dans la requete
			$keys = array();
			foreach(preg_split('/\)\s*,?/',$namedkeys) as $v) {
				if (preg_match("/^\s*([^(]*)\((.*)$/",$v,$r)) {
					$k = str_replace("`", '', trim($r[1]));
					$t = trim(strtolower(str_replace("`", '', $r[2])), '"');
					if ($k && !isset($keys[$k])) $keys[$k] = $t; else $keys[] = $t;
				}
			}
			// sinon ajouter les key index
			$query = 
				'SELECT name,sql FROM'
				. ' (SELECT * FROM sqlite_master UNION ALL'
				. ' SELECT * FROM sqlite_temp_master)'
				. " WHERE tbl_name LIKE '$nom_table'"
				. " AND type='index' AND name NOT LIKE 'sqlite_%'"
				. 'ORDER BY substr(type,2,1), name';
			$a = spip_sqlite_query($query, $serveur, $requeter);
			while ($r = spip_sqlite_fetch($a, null, $serveur)) {
				$key = str_replace($nom_table.'_','',$r['name']); // enlever le nom de la table ajoute a l'index
				$colonnes = preg_replace(',.*\((.*)\).*,','$1',$r['sql']);
				$keys['KEY '.$key] = $colonnes;
			}			
		}
	// c'est une vue, on liste les champs disponibles simplement
	} else {
		if ($res = sql_fetsel('*',$nom_table,'','','','1','',$serveur)){ // limit 1
			$fields = array();
			foreach($res as $c=>$v) $fields[$c]='';
			$keys = array();		
		} else {	
			return "";	
		}
	}
	return array('field' => $fields, 'key' => $keys);
	
}


// http://doc.spip.org/@spip_sqlite_update
function spip_sqlite_update($table, $champs, $where='', $desc='', $serveur='',$requeter=true) {
	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$champs = _sqlite_ajouter_champs_timestamp($table, $champs, $desc, $serveur);
	
	$set = array();
	foreach ($champs as $champ => $val)
		$set[] = $champ . "=$val";
	if (!empty($set))
		return spip_sqlite_query(
			  _sqlite_calculer_expression('UPDATE', $table, ',')
			. _sqlite_calculer_expression('SET', $set, ',')
			. _sqlite_calculer_expression('WHERE', $where), 
			$serveur, $requeter);
}


// http://doc.spip.org/@spip_sqlite_updateq
function spip_sqlite_updateq($table, $champs, $where='', $desc=array(), $serveur='',$requeter=true) {

	if (!$champs) return;
	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  $desc['field'];
	
	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$champs = _sqlite_ajouter_champs_timestamp($table, $champs, $desc, $serveur);
	
	$set = array();
	foreach ($champs as $champ => $val) {
		$set[] = $champ . '=' . _sqlite_calculer_cite($val, $fields[$champ]);
	}
	return spip_sqlite_query(
			  _sqlite_calculer_expression('UPDATE', $table, ',')
			. _sqlite_calculer_expression('SET', $set, ',')
			. _sqlite_calculer_expression('WHERE', $where),
			$serveur, $requeter);
}



/*
 * 
 * Ensuite les fonctions non abstraites
 * crees pour l'occasion de sqlite
 * 
 */


// fonction pour la premiere connexion a un serveur SQLite
// http://doc.spip.org/@_sqlite_init
function _sqlite_init(){
	if (!defined('_DIR_DB')) define('_DIR_DB', _DIR_ETC . 'bases/');
	if (!defined('_SQLITE_CHMOD')) define('_SQLITE_CHMOD', _SPIP_CHMOD);
	
	if (!is_dir($d = _DIR_DB)){
		include_spip('inc/flock');
		sous_repertoire($d);
	}
}


// teste la version sqlite du link en cours
// http://doc.spip.org/@_sqlite_is_version
function _sqlite_is_version($version='', $link='', $serveur='',$requeter=true){
	if ($link==='') $link = _sqlite_link($serveur);
	if (!$link) return false;
	if (is_a($link, 'PDO')){
		$v = 3;	
	} else {
		$v = 2;	
	}
	
	if (!$version) return $v;
	return ($version == $v);
}


// retrouver un link (et definir les fonctions externes sqlite->php)
// $recharger devient inutile (a supprimer ?)
// http://doc.spip.org/@_sqlite_link
function _sqlite_link($serveur = '', $recharger = false){
	static $charge = array();
	if ($recharger) $charge[$serveur] = false;
	
	$link = &$GLOBALS['connexions'][$serveur ? $serveur : 0]['link'];

	if ($link && !$charge[$serveur]){
		include_spip('req/sqlite_fonctions');
		_sqlite_init_functions($link);
		$charge[$serveur] = true;
	}
	return $link;
}


/* ordre alphabetique pour les autres */


// renvoie les bons echappements (pas sur les fonctions now())
// http://doc.spip.org/@_sqlite_calculer_cite
function _sqlite_calculer_cite($v, $type) {
	if (sql_test_date($type) AND preg_match('/^\w+\(/', $v))
		return $v;
	if (sql_test_int($type)) {
		if (is_numeric($v))
			return $v;
		if (ctype_xdigit(substr($v,2)) AND strncmp($v,'0x',2)==0)
			return hexdec(substr($v,2));
	}
	//else return  ("'" . spip_sqlite_quote($v) . "'");
	return  (spip_sqlite_quote($v));
}


// renvoie grosso modo "$expression join($join, $v)"
// http://doc.spip.org/@_sqlite_calculer_expression
function _sqlite_calculer_expression($expression, $v, $join = 'AND'){
	if (empty($v))
		return '';
	
	$exp = "\n$expression ";
	
	if (!is_array($v)) {
		return $exp . $v;
	} else {
		if (strtoupper($join) === 'AND')
			return $exp . join("\n\t$join ", array_map('_sqlite_calculer_where', $v));
		else
			return $exp . join($join, $v);
	}
}




// pour conversion 0+x ? (pas la peine en sqlite)
// http://doc.spip.org/@_sqlite_calculer_order
function _sqlite_calculer_order($orderby) {
	return (is_array($orderby)) ? join(", ", $orderby) :  $orderby;
}


// renvoie des 'nom AS alias' 
// http://doc.spip.org/@_sqlite_calculer_select_as
function _sqlite_calculer_select_as($args){
	$res = '';
	foreach($args as $k => $v) {
		if (substr($k,-1)=='@') {
			// c'est une jointure qui se refere au from precedent
			// pas de virgule
		  $res .= '  ' . $v ;
		}
		else {
			if (!is_numeric($k)) {
				$p = strpos($v, " ");
				if ($p)
		  		$v = substr($v,0,$p) . " AS '$k'" . substr($v,$p);
				else $v .= " AS '$k'";
	  	}
	  	$res .= ', ' . $v ;
		}
	}
	return substr($res,2) . $join;
}


// renvoie les bonnes parentheses pour des where imbriquees
// http://doc.spip.org/@_sqlite_calculer_where
function _sqlite_calculer_where($v){
	if (!is_array($v))
	  return $v ;

	$op = array_shift($v);
	if (!($n=count($v)))
		return $op;
	else {
		$arg = _sqlite_calculer_where(array_shift($v));
		if ($n==1) {
			  return "$op($arg)";
		} else {
			$arg2 = _sqlite_calculer_where(array_shift($v));
			if ($n==2) {
				return "($arg $op $arg2)";
			} else return "($arg $op ($arg2) : $v[0])";
		}
	}
}



/*
 * Charger les modules sqlite (si possible) (juste la version demandee),
 * ou, si aucune version, renvoie les versions sqlite dispo 
 * sur ce serveur dans un array
 */
// http://doc.spip.org/@_sqlite_charger_version
function _sqlite_charger_version($version=''){
	$versions = array();
	
	// version 2
	if (!$version || $version == 2){
		if (charger_php_extension('sqlite')) {
			$versions[]=2;
		}
	}
	
	// version 3
	if (!$version || $version == 3){
		if (charger_php_extension('pdo') && charger_php_extension('pdo_sqlite')) {
			$versions[]=3;
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
 * @param string/array $table : nom_table, array(nom_table=>nom_futur)
 * @param string/array $col : nom_colonne, array(nom_colonne=>nom_futur)
 * @param array $opt : options comme les tables spip, qui sera merge a la table creee : array('field'=>array('nom'=>'syntaxe', ...), 'key'=>array('KEY nom'=>'colonne', ...))
 * @param string $serveur : nom de la connexion sql en cours
 * 
 */
// http://doc.spip.org/@_sqlite_modifier_table
function _sqlite_modifier_table($table, $colonne, $opt=array(), $serveur=''){

	if (is_array($table)) {
		$table_origine = array_shift(array_keys($table));
		$table_destination = array_shift($table);
	} else {
		$table_origine = $table_destination = $table;
	}
	// ne prend actuellement qu'un changement
	// mais pourra etre adapte pour changer plus qu'une colonne a la fois
	if (is_array($colonne)) {
		$colonne_origine = array_shift(array_keys($colonne));
		$colonne_destination = array_shift($colonne);
	} else {
		$colonne_origine = $colonne_destination = $colonne;
	}	
	if (!isset($opt['field'])) $opt['field'] = array();
	if (!isset($opt['key'])) $opt['key'] = array();
	
	// si les noms de tables sont differents, pas besoin de table temporaire
	// on prendra directement le nom de la future table
	$meme_table = ($table_origine == $table_destination);
	
	$def_origine = sql_showtable($table_origine, false, $serveur);
	$table_tmp = $table_origine . '_tmp';

	// 1) creer une table temporaire avec les modifications	
	// - DROP : suppression de la colonne
	// - CHANGE : modification de la colonne
	// (foreach pour conserver l'ordre des champs)
	
	// field 
	$fields = array();
	// pour le INSERT INTO plus loin
	// stocker la correspondance nouvelles->anciennes colonnes
	$fields_correspondances = array(); 
	foreach ($def_origine['field'] as $c=>$d){

		if ($colonne_origine && ($c == $colonne_origine)) {
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
	foreach ($def_origine['key'] as $c=>$d){
		$c = str_replace($colonne_origine,$colonne_destination,$c);
		$d = str_replace($colonne_origine,$colonne_destination,$d);
		// seulement si on ne supprime pas la colonne !
		if ($d)
			$keys[$c] = $d;
	}

	// autres keys, on merge
	$keys = array_merge($keys,$opt['key']);
	$queries = array();
	$queries[] = 'BEGIN TRANSACTION';
	
	// copier dans destination (si differente de origine), sinon tmp
	$table_copie = ($meme_table) ? $table_tmp : $table_destination;
	
	if ($q = _sqlite_requete_create(
			$table_copie, 
			$fields, 
			$keys, 
			$autoinc=false,
			$temporary=false, 
			$ifnotexists=true,
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
					$autoinc=false,
					$temporary=false, 
					$ifnotexists=false, // la table existe puisqu'on est dans une transaction
					$serveur);	
			$queries[] = "INSERT INTO $table_destination SELECT * FROM $table_copie";		
			$queries[] = "DROP TABLE $table_copie";
		}
	}
	
	// 5) remettre les index !
	foreach ($keys as $k=>$v) {
		if ($k=='PRIMARY KEY'){}
		else {
			// enlever KEY
			$k = substr($k,4);
			$queries[] = "CREATE INDEX $table_destination"."_$k ON $table_destination ($v)";
		}
	}
	
	$queries[] = "COMMIT";
	

	// il faut les faire une par une car $query = join('; ', $queries).";"; ne fonctionne pas
	foreach ($queries as $q){
		$req = new sqlite_traiter_requete($q, $serveur);
		if (!$req->executer_requete()){	
			spip_log("SQLite : ALTER TABLE table :" 
			." Erreur a l'execution de la requete : $q",'sqlite'); 
			return false;
		}
	}

	return true;					
}




/*
 * Nom des fonctions
 */
// http://doc.spip.org/@_sqlite_ref_fonctions
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
	);
	
	// association de chaque nom http d'un charset aux couples sqlite 
	// SQLite supporte utf-8 et utf-16 uniquement.
	$charsets = array(
		'utf-8'=>array('charset'=>'utf8','collation'=>'utf8_general_ci'), 
		//'utf-16be'=>array('charset'=>'utf16be','collation'=>'UTF-16BE'),// aucune idee de quoi il faut remplir dans es champs la
		//'utf-16le'=>array('charset'=>'utf16le','collation'=>'UTF-16LE')
	);
	
	$fonctions['charsets'] = $charsets;
	
	return $fonctions;
}



// $query est une requete ou une liste de champs
// http://doc.spip.org/@_sqlite_remplacements_definitions_table
function _sqlite_remplacements_definitions_table($query,$autoinc=false){
	// quelques remplacements
	$num = "(\s*\([0-9]*\))?";
	$enum = "(\s*\([^\)]*\))?";
	
	$remplace = array(
		'/enum'.$enum.'/is' => 'VARCHAR',
		'/binary/is' => '',
		'/COLLATE \w+_bin/is' => '',
		'/auto_increment/is' => '',
		'/(timestamp .* )ON .*$/is' => '\\1',
		'/character set \w+/is' => '',
		'/((big|small|medium|tiny)?int(eger)?)'.$num.'\s*unsigned/is' => '\\1 UNSIGNED',
		'/(text\s+not\s+null)\s*$/is' => "\\1 DEFAULT ''",
	);

	// pour l'autoincrement, il faut des INTEGER NOT NULL PRIMARY KEY
	if ($autoinc)
		$remplace['/(big|small|medium|tiny)?int(eger)?'.$num.'/is'] = 'INTEGER';

	return preg_replace(array_keys($remplace), $remplace, $query);
}


/*
 * Creer la requete pour la creation d'une table
 * retourne la requete pour utilisation par sql_create() et sql_alter()
 */
// http://doc.spip.org/@_sqlite_requete_create
function _sqlite_requete_create($nom, $champs, $cles, $autoinc=false, $temporary=false, $_ifnotexists=true, $serveur='',$requeter=true) {
	$query = $keys = $s = $p = '';

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) 
		return;

	// sqlite ne gere pas KEY tout court dans une requete CREATE TABLE
	// il faut passer par des create index
	// Il gere par contre primary key !
	// Soit la PK est definie dans les cles, soit dans un champs
	if (!$c = $cles[$pk = "PRIMARY KEY"]) {
		foreach($champs as $k => $v) {
			if (false !== stripos($v,$pk)) {
				$c = $k;
				// on n'en a plus besoin dans field, vu que defini dans key
				$champs[$k] = preg_replace("/$pk/is", '', $champs[$k]); 
				break;	
			}
		}
	}
	if ($c) $keys = "\n\t\t$pk ($c)";
	
	$champs = _sqlite_remplacements_definitions_table($champs, $autoinc);
	foreach($champs as $k => $v) {
		$query .= "$s\n\t\t$k $v";
		$s = ",";
	}

	$ifnotexists = "";
	if ($_ifnotexists) {
		// simuler le IF NOT EXISTS - version 2 
		if (_sqlite_is_version(2, '', $serveur)){
			$a = spip_sqlite_showtable($nom, $serveur);
			if ($a) return false;
		} 
		// sinon l'ajouter en version 3
		else {
			$ifnotexists = ' IF NOT EXISTS';
		}
	}

	$temporary = $temporary ? ' TEMPORARY':'';
	$q = "CREATE$temporary TABLE$ifnotexists $nom ($query" . ($keys ? ",$keys" : '') . ")\n";

	return $q;
}
	


/*
 * Retrouver les champs 'timestamp'
 * pour les ajouter aux 'insert' ou 'replace'
 * afin de simuler le fonctionnement de mysql 
 * 
 * stocke le resultat pour ne pas faire 
 * de requetes showtable intempestives
 */
// http://doc.spip.org/@_sqlite_ajouter_champs_timestamp
function _sqlite_ajouter_champs_timestamp($table, $couples, $desc='', $serveur=''){
	static $tables = array();
	
	if (!isset($tables[$table])){
		
		if (!$desc){
			$f = charger_fonction('trouver_table', 'base');
			$desc = $f($table, $serveur);
			// si pas de description, on ne fait rien, ou on die() ?
			if (!$desc OR !$desc['field']) return $couples;
		}
		
		// recherche des champs avec simplement 'TIMESTAMP'
		// cependant, il faudra peut etre etendre
		// avec la gestion de DEFAULT et ON UPDATE
		// mais ceux-ci ne sont pas utilises dans le core
		$tables[$table] = array();

		foreach ($desc['field'] as $k=>$v){
			if (strpos(strtolower(ltrim($v)), 'timestamp')===0)
			$tables[$table][] = $k;
		}
	}
	
	// ajout des champs type 'timestamp' absents
	foreach ($tables[$table] as $maj){
		if (!array_key_exists($maj, $couples))
			$couples[$maj] = "datetime('now')";	
	}
	return $couples;
}
 	
 	

/*
 * renvoyer la liste des versions sqlite disponibles
 * sur le serveur 
 */
// http://doc.spip.org/@spip_versions_sqlite
function spip_versions_sqlite(){
	return 	_sqlite_charger_version();
}




/*
 * Classe pour partager les lancements de requete
 * - peut corriger la syntaxe des requetes pour la conformite a sqlite
 * - peut tracer les requetes
 * 
 * Cette classe est presente essentiellement pour un preg_replace_callback 
 * avec des parametres dans la fonction appelee que l'on souhaite incrementer 
 * (fonction pour proteger les textes)
 * 
 */
class sqlite_traiter_requete{
	var $query = ''; // la requete
	var $queryCount = ''; // la requete pour compter
	var $serveur = ''; // le serveur
	var $link = ''; // le link (ressource) sqlite
	var $prefixe = ''; // le prefixe des tables
	var $db = ''; // le nom de la base 
	var $tracer = false; // doit-on tracer les requetes (var_profile)
	
	var $sqlite_version = ''; // Version de sqlite (2 ou 3)
	
	// Pour les corrections a effectuer sur les requetes :
	var $textes = array(); 	// array(code=>'texte') trouvé
	var $codeEchappements = "%@##@%";
	
	
	// constructeur
// http://doc.spip.org/@sqlite_traiter_requete
	function sqlite_traiter_requete($query, $serveur = ''){
		$this->query = $query;
		$this->serveur = strtolower($serveur);
		
		if (!($this->link = _sqlite_link($this->serveur)) && (!defined('_ECRIRE_INSTALL') || !_ECRIRE_INSTALL)){
			spip_log("Aucune connexion sqlite (link)");
			return false;	
		}

		$this->sqlite_version =_sqlite_is_version('', $this->link);
		
		$this->prefixe 	= $GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['prefixe'];
		$this->db 		= $GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['db'];
		
		// tracage des requetes ?
		$this->tracer = (isset($_GET['var_profile']) && $_GET['var_profile']);
	}
	
	
	// lancer la requete $this->query,
	// faire le tracage si demande 
// http://doc.spip.org/@executer_requete
	function executer_requete(){
		$err = "";
		if ($this->tracer) {
			include_spip('public/tracer');
			$t = trace_query_start();
		} else $t = 0 ;
 
# spip_log("requete: $this->serveur >> $this->query",'query'); // boum ? pourquoi ?
		if ($this->link){
			// memoriser la derniere erreur PHP vue
			$e = error_get_last();
			// sauver la derniere requete
			$GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['last'] = $this->query;
			
			if ($this->sqlite_version == 3) {
				$r = $this->link->query($this->query);
				// sauvegarde de la requete (elle y est deja dans $r->queryString)
				# $r->spipQueryString = $this->query;

				// comptage : oblige de compter le nombre d'entrees retournees 
				// par une requete SELECT
				// aucune autre solution ne donne le nombre attendu :( !
				// particulierement s'il y a des LIMIT dans la requete.
				if (strtoupper(substr(ltrim($this->query),0,6)) == 'SELECT'){
					if ($r) {
						$l = $this->link->query($this->query);
						$r->spipSqliteRowCount =  count($l->fetchAll());
						unset($l);
					} elseif (is_a($r, 'PDOStatement')) {
						$r->spipSqliteRowCount = 0;
					}
				}
			} else {
				$r = sqlite_query($this->link, $this->query);
			}

			// loger les warnings/erreurs eventuels de sqlite remontant dans PHP
			if ($err = error_get_last() AND $err!=$e) {
				$err = strip_tags($err['message'])." in ".$err['file']." line ".$err['line'];
				spip_log("$err - ".$this->query, 'sqlite');
			}
		  else $err="";

		} else {
			$r = false;	
		}

		if (spip_sqlite_errno($serveur))
			$err .= spip_sqlite_error($this->query, $serveur);
		return $t ? trace_query_end($this->query, $t, $r, $err, $serveur) : $r;
	}
		
	// transformer la requete pour sqlite 
	// enleve les textes, transforme la requete pour quelle soit
	// bien interpretee par sqlite, puis remet les textes
	// la fonction affecte $this->query
// http://doc.spip.org/@traduire_requete
	function traduire_requete(){
		//
		// 1) Protection des textes en les remplacant par des codes
		//
		// enlever les echappements ''
		$this->query = str_replace("''", $this->codeEchappements, $this->query);
		// enlever les 'textes'
		$this->textes = array(); // vider 
		$this->query = preg_replace_callback("/('[^']*')/", array(&$this, '_remplacerTexteParCode'), $this->query);
		
		//
		// 2) Corrections de la requete
		//
		// Correction Create Database
		// Create Database -> requete ignoree
		if (strpos($this->query, 'CREATE DATABASE')===0){
			spip_log("Sqlite : requete non executee -> $this->query","sqlite");
			$this->query = "SELECT 1";	
		}
		
		// Correction Insert Ignore
		// INSERT IGNORE -> insert (tout court et pas 'insert or replace')
		if (strpos($this->query, 'INSERT IGNORE')===0){
			#spip_log("Sqlite : requete transformee -> $this->query","sqlite");
			$this->query = 'INSERT ' . substr($this->query,'13');	
		}
		
		// Correction des dates avec INTERVAL
		// utiliser sql_date_proche() de preference
		if (strpos($this->query, 'INTERVAL')!==false){
			$this->query = preg_replace_callback("/DATE_(ADD|SUB).*INTERVAL\s+(\d+)\s+([a-zA-Z]+)\)/U", 
							array(&$this, '_remplacerDateParTime'), 
							$this->query);
		}
		
		// Correction Using
		// USING (non reconnu en sqlite2)
		// problematique car la jointure ne se fait pas du coup.
		if (($this->sqlite_version == 2) && (strpos($this->query, "USING")!==false)) {
			spip_log("'USING (champ)' n'est pas reconnu en SQLite 2. Utilisez 'ON table1.champ = table2.champ', 'sqlite'");
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
		if (preg_match('/\s(SET|VALUES|WHERE|DATABASE)\s/i', $this->query, $regs)) {
			$suite = strstr($this->query, $regs[0]);
			$this->query = substr($this->query, 0, -strlen($suite));
		} else $suite ='';
		$pref = ($this->prefixe) ? $this->prefixe . "_": "";
		$this->query = preg_replace('/([,\s])spip_/', '\1'.$pref, $this->query) . $suite;

		// Correction zero AS x
		// pg n'aime pas 0+x AS alias, sqlite, dans le meme style, 
		// n'apprecie pas du tout SELECT 0 as x ... ORDER BY x
		// il dit que x ne doit pas être un integer dans le order by !
		// on remplace du coup x par vide() dans ce cas uniquement
		//
		// rien que pour public/vertebrer.php ?
		if ((strpos($this->query, "0 AS")!==false)){
			// on ne remplace que dans ORDER BY ou GROUP BY
			if (preg_match('/\s(ORDER|GROUP) BY\s/i', $this->query, $regs)) {
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
			
		// Correction Antiquotes
		// ` => rien
		$this->query = str_replace('`','',$this->query);
		
		// Correction critere REGEXP, non reconnu en sqlite2
		if (($this->sqlite_version == 2) && (strpos($this->query, 'REGEXP')!==false)){
			$this->query = preg_replace('/([^\s\(]*)(\s*)REGEXP(\s*)([^\s\)]*)/', 'REGEXP($4, $1)', $this->query);
		}
		
		
		//
		// 3) Remise en place des textes d'origine
		//
		// remettre les 'textes'
		foreach ($this->textes as $cle=>$val){
			$this->query = str_replace($cle, $val, $this->query);
		}
		// remettre les echappements ''
		$this->query = str_replace($this->codeEchappements,"''",$this->query);
	}
	


	// les callbacks	
	// remplacer DATE_ / INTERVAL par DATE...strtotime
// http://doc.spip.org/@_remplacerDateParTime
	function _remplacerDateParTime($matches){
		$op = strtoupper($matches[1] == 'ADD')?'+':'-';	
		return "datetime('" . date("Y-m-d H:i:s") . "', '$op$matches[2] $matches[3]')";
	}

	// callback ou l'on remplace FIELD(table,i,j,k...) par CASE WHEN table=i THEN n ... ELSE 0 END
// http://doc.spip.org/@_remplacerFieldParCase
	function _remplacerFieldParCase($matches){
		$fields = substr($matches[0],6,-1); // ne recuperer que l'interieur X de field(X)
		$t = explode(',', $fields);
		$index = array_shift($t);

		$res = '';
		$n=0;
		foreach($t as $v) {
			$n++;
			$res .= "\nWHEN $index=$v THEN $n";
		}
		return "CASE $res ELSE 0 END ";			
	}

	// callback ou l'on sauve le texte qui est cache dans un tableau $this->textes
// http://doc.spip.org/@_remplacerTexteParCode
	function _remplacerTexteParCode($matches){
		$this->textes[$code = "%@##".count($this->textes)."##@%"] = $matches[1];
		return $code;	
	}

}

?>
