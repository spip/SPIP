<?php

/* *************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

class Sqlite
{
	static $requeteurs = array();
	static $transaction_en_cours = array();

	public function Sqlite()
	{
	}

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
	public static function requeteur($serveur)
	{
		if (!isset(self::$requeteurs[$serveur]))
			self::$requeteurs[$serveur] = new Sqlite_requeteur($serveur);
		return self::$requeteurs[$serveur];
	}

	public static function traduire_requete($query, $serveur)
	{
		$requeteur = self::requeteur($serveur);
		$traducteur = new Sqlite_traducteur($query, $requeteur->prefixe,$requeteur->sqlite_version);
		return $traducteur->traduire_requete();
	}

	public static function demarrer_transaction($serveur)
	{
		self::executer_requete("BEGIN TRANSACTION",$serveur);
		self::$transaction_en_cours[$serveur] = true;
	}

	public static function executer_requete($query, $serveur, $tracer=null)
	{
		$requeteur = self::requeteur($serveur);
		return $requeteur->executer_requete($query, $tracer);
	}

	public static function last_insert_id($serveur)
	{
		$requeteur = self::requeteur($serveur);
		return $requeteur->last_insert_id($serveur);
	}

	public static function annuler_transaction($serveur)
	{
		self::executer_requete("ROLLBACK",$serveur);
		self::$transaction_en_cours[$serveur] = false;
	}

	public static function finir_transaction($serveur)
	{
		// si pas de transaction en cours, ne rien faire et le dire
		if (!isset (self::$transaction_en_cours[$serveur])
		OR self::$transaction_en_cours[$serveur]==false)
			return false;
		// sinon fermer la transaction et retourner true
		self::executer_requete("COMMIT",$serveur);
		self::$transaction_en_cours[$serveur] = false;
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
class Sqlite_requeteur
{
	public $query = ''; // la requete
	public $serveur = ''; // le serveur
	public $link = ''; // le link (ressource) sqlite
	public $prefixe = ''; // le prefixe des tables
	public $db = ''; // le nom de la base 
	public $tracer = false; // doit-on tracer les requetes (var_profile)

	public $sqlite_version = ''; // Version de sqlite (2 ou 3)

	/**
	 * constructeur
	 * http://doc.spip.org/@sqlite_traiter_requete
	 *
	 * @param  $query
	 * @param string $serveur
	 * @return bool
	 */
	public function sqlite_requeteur($serveur = '')
	{
		_sqlite_init();
		$this->serveur = strtolower($serveur);

		if (!($this->link = _sqlite_link($this->serveur)) && (!defined('_ECRIRE_INSTALL') || !_ECRIRE_INSTALL)) {
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
	public function executer_requete($query, $tracer=null)
	{
		if (is_null($tracer))
			$tracer = $this->tracer;
		$err = "";
		$t = 0;
		if ($tracer) {
			include_spip('public/tracer');
			$t = trace_query_start();
		}
		
		# spip_log("requete: $this->serveur >> $query",'sqlite.'._LOG_DEBUG); // boum ? pourquoi ?
		if ($this->link) {
			// memoriser la derniere erreur PHP vue
			$e = (function_exists('error_get_last')?error_get_last():"");
			// sauver la derniere requete
			$GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['last'] = $query;

			if ($this->sqlite_version==3) {
				$r = $this->link->query($query);
				// sauvegarde de la requete (elle y est deja dans $r->queryString)
				# $r->spipQueryString = $query;

				// comptage : oblige de compter le nombre d'entrees retournees 
				// par une requete SELECT
				// aucune autre solution ne donne le nombre attendu :( !
				// particulierement s'il y a des LIMIT dans la requete.
				if (strtoupper(substr(ltrim($query), 0, 6))=='SELECT') {
					if ($r) {
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
			if ($err = (function_exists('error_get_last')?error_get_last():"")
			AND $err!=$e) {
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

	public function last_insert_id()
	{
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
class Sqlite_traducteur
{
	public $query = '';
	public $prefixe = ''; // le prefixe des tables
	public $sqlite_version = ''; // Version de sqlite (2 ou 3)
	
	// Pour les corrections a effectuer sur les requetes :
	public $textes = array(); // array(code=>'texte') trouvé

	public function sqlite_traducteur($query, $prefixe, $sqlite_version)
	{
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
	public function traduire_requete()
	{
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
		if (strpos($this->query, 'CREATE DATABASE')===0) {
			spip_log("Sqlite : requete non executee -> $this->query", 'sqlite.'._LOG_AVERTISSEMENT);
			$this->query = "SELECT 1";
		}

		// Correction Insert Ignore
		// INSERT IGNORE -> insert (tout court et pas 'insert or replace')
		if (strpos($this->query, 'INSERT IGNORE')===0) {
			spip_log("Sqlite : requete transformee -> $this->query", 'sqlite.'._LOG_DEBUG);
			$this->query = 'INSERT '.substr($this->query, '13');
		}

		// Correction des dates avec INTERVAL
		// utiliser Sql::date_proche() de preference
		if (strpos($this->query, 'INTERVAL')!==false) {
			$this->query = preg_replace_callback("/DATE_(ADD|SUB)(.*)INTERVAL\s+(\d+)\s+([a-zA-Z]+)\)/U",
			                                     array(&$this, '_remplacerDateParTime'),
			                                     $this->query);
		}

		if (strpos($this->query, 'LEFT(')!==false) {
			$this->query = str_replace('LEFT(','_LEFT(',$this->query);
		}

		if (strpos($this->query, 'TIMESTAMPDIFF(')!==false) {
			$this->query = preg_replace('/TIMESTAMPDIFF\(\s*([^,]*)\s*,/Uims',"TIMESTAMPDIFF('\\1',",$this->query);
		}


		// Correction Using
		// USING (non reconnu en sqlite2)
		// problematique car la jointure ne se fait pas du coup.
		if (($this->sqlite_version==2)
		AND (strpos($this->query, "USING")!==false)) {
			spip_log("'USING (champ)' n'est pas reconnu en SQLite 2. Utilisez 'ON table1.champ = table2.champ'", 'sqlite.'._LOG_ERREUR);
			$this->query = preg_replace('/USING\s*\([^\)]*\)/', '', $this->query);
		}

		// Correction Field
		// remplace FIELD(table,i,j,k...) par CASE WHEN table=i THEN n ... ELSE 0 END
		if (strpos($this->query, 'FIELD')!==false) {
			$this->query = preg_replace_callback('/FIELD\s*\(([^\)]*)\)/',
			                                     array(&$this, '_remplacerFieldParCase'),
			                                     $this->query);
		}

		// Correction des noms de tables FROM
		// mettre les bons noms de table dans from, update, insert, replace...
		if (preg_match('/\s(SET|VALUES|WHERE|DATABASE)\s/iS', $this->query, $regs)) {
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
		if ((strpos($this->query, "0 AS")!==false)) {
			// on ne remplace que dans ORDER BY ou GROUP BY
			if (preg_match('/\s(ORDER|GROUP) BY\s/i', $this->query, $regs)) {
				$suite = strstr($this->query, $regs[0]);
				$this->query = substr($this->query, 0, -strlen($suite));

				// on cherche les noms des x dans 0 AS x
				// on remplace dans $suite le nom par vide()
				preg_match_all('/\b0 AS\s*([^\s,]+)/', $this->query, $matches, PREG_PATTERN_ORDER);
				foreach ($matches[1] as $m) {
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
		if (($this->sqlite_version==2)
		AND (strpos($this->query, 'REGEXP')!==false)) {
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
	public function _remplacerDateParTime($matches)
	{
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
	public function _remplacerFieldParCase($matches)
	{
		$fields = substr($matches[0], 6, -1); // ne recuperer que l'interieur X de field(X)
		$t = explode(',', $fields);
		$index = array_shift($t);

		$res = '';
		$n = 0;
		foreach ($t as $v) {
			$n++;
			$res .= "\nWHEN $index=$v THEN $n";
		}
		return "CASE $res ELSE 0 END ";
	}

}
