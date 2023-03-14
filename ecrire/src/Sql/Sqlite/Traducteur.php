<?php

namespace Spip\Sql\Sqlite;

/**
 * Cette classe est presente essentiellement pour un preg_replace_callback
 * avec des parametres dans la fonction appelee que l'on souhaite incrementer
 * (fonction pour proteger les textes)
 */
class Traducteur
{
	/** Pour les corrections à effectuer sur les requêtes : array(code=>'texte') trouvé
	 *
	 * @var array
	 */
	public $textes = [];

	/**
	 * Constructeur
	 */
	public function __construct(
		/** Requête à préparer */
		public string $query,
		/** Prefixe des tables à utiliser */
		public string $prefixe,
		/** Version SQLite (2 ou 3) */
		public string $sqlite_version
	)
	{
	}

	/**
	 * Transformer la requete pour SQLite
	 *
	 * Enlève les textes, transforme la requête pour quelle soit
	 * bien interprétée par SQLite, puis remet les textes
	 * la fonction affecte `$this->query`
	 */
	public function traduire_requete()
	{
		//
		// 1) Protection des textes en les remplacant par des codes
		//
		// enlever les 'textes' et initialiser avec
		[$this->query, $textes] = query_echappe_textes($this->query);

		//
		// 2) Corrections de la requete
		//
		// Correction Create Database
		// Create Database -> requete ignoree
		if (str_starts_with($this->query, 'CREATE DATABASE')) {
			spip_log("Sqlite : requete non executee -> $this->query", 'sqlite.' . _LOG_AVERTISSEMENT);
			$this->query = 'SELECT 1';
		}

		// Correction Insert Ignore
		// INSERT IGNORE -> insert (tout court et pas 'insert or replace')
		if (str_starts_with($this->query, 'INSERT IGNORE')) {
			spip_log("Sqlite : requete transformee -> $this->query", 'sqlite.' . _LOG_DEBUG);
			$this->query = 'INSERT ' . substr($this->query, '13');
		}

		// Correction des dates avec INTERVAL
		// utiliser sql_date_proche() de preference
		if (str_contains($this->query, 'INTERVAL')) {
			$this->query = preg_replace_callback(
				'/DATE_(ADD|SUB)(.*)INTERVAL\s+(\d+)\s+([a-zA-Z]+)\)/U',
				fn(array $matches): string => $this->_remplacerDateParTime($matches),
				$this->query
			);
		}

		if (str_contains($this->query, 'LEFT(')) {
			$this->query = str_replace('LEFT(', '_LEFT(', $this->query);
		}

		if (str_contains($this->query, 'TIMESTAMPDIFF(')) {
			$this->query = preg_replace('/TIMESTAMPDIFF\(\s*([^,]*)\s*,/Uims', "TIMESTAMPDIFF('\\1',", $this->query);
		}


		// Correction Using
		// USING (non reconnu en sqlite2)
		// problematique car la jointure ne se fait pas du coup.
		if (($this->sqlite_version == 2) && (str_contains($this->query, 'USING'))) {
			spip_log(
				"'USING (champ)' n'est pas reconnu en SQLite 2. Utilisez 'ON table1.champ = table2.champ'",
				'sqlite.' . _LOG_ERREUR
			);
			$this->query = preg_replace('/USING\s*\([^\)]*\)/', '', $this->query);
		}

		// Correction Field
		// remplace FIELD(table,i,j,k...) par CASE WHEN table=i THEN n ... ELSE 0 END
		if (str_contains($this->query, 'FIELD')) {
			$this->query = preg_replace_callback(
				'/FIELD\s*\(([^\)]*)\)/',
				fn(array $matches): string => $this->_remplacerFieldParCase($matches),
				$this->query
			);
		}

		// Correction des noms de tables FROM
		// mettre les bons noms de table dans from, update, insert, replace...
		if (preg_match('/\s(SET|VALUES|WHERE|DATABASE)\s/iS', $this->query, $regs)) {
			$suite = strstr($this->query, $regs[0]);
			$this->query = substr($this->query, 0, -strlen($suite));
		} else {
			$suite = '';
		}
		$pref = ($this->prefixe) ? $this->prefixe . '_' : '';
		$this->query = preg_replace('/([,\s])spip_/S', '\1' . $pref, $this->query) . $suite;

		// Correction zero AS x
		// pg n'aime pas 0+x AS alias, sqlite, dans le meme style,
		// n'apprecie pas du tout SELECT 0 as x ... ORDER BY x
		// il dit que x ne doit pas être un integer dans le order by !
		// on remplace du coup x par vide() dans ce cas uniquement
		//
		// apparait dans public/vertebrer.php et dans le plugin menu aussi qui genere aussi ce genre de requete via un {par num #GET{tri_num}}
		// mais est-ce encore un soucis pour sqlite en 2021 ? (ie commenter le preg_replace marche très bien en sqlite 3.28)
		// on ne remplace que dans ORDER BY ou GROUP BY
		if (str_contains($this->query, '0 AS') && preg_match('/\s(ORDER|GROUP) BY\s/i', $this->query, $regs)) {
			$suite = strstr($this->query, $regs[0]);
			$this->query = substr($this->query, 0, -strlen($suite));
			// on cherche les noms des x dans 0 AS x
			// on remplace dans $suite le nom par vide()
			preg_match_all('/\b0 AS\s*([^\s,]+)/', $this->query, $matches, PREG_PATTERN_ORDER);
			foreach ($matches[1] as $m) {
				if (str_contains($suite, $m)) {
					$suite = preg_replace(",\b$m\b,", 'VIDE()', $suite);
				}
			}
			$this->query .= $suite;
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
		if (($this->sqlite_version == 2) && (str_contains($this->query, 'REGEXP'))) {
			$this->query = preg_replace('/([^\s\(]*)(\s*)REGEXP(\s*)([^\s\)]*)/', 'REGEXP($4, $1)', $this->query);
		}

		//
		// 3) Remise en place des textes d'origine
		//
		// Correction Antiquotes et echappements
		// ` => rien
		if (str_contains($this->query, '`')) {
			$this->query = str_replace('`', '', $this->query);
		}

		$this->query = query_reinjecte_textes($this->query, $textes);

		return $this->query;
	}

	/**
	 * Callback pour remplacer `DATE_` / `INTERVAL`
	 * par `DATE ... strtotime`
	 *
	 * @param array $matches Captures
	 * @return string texte de date compris par SQLite
	 */
	public function _remplacerDateParTime($matches)
	{
		$op = strtoupper($matches[1] == 'ADD') ? '+' : '-';

		return "datetime$matches[2] '$op$matches[3] $matches[4]')";
	}

	/**
	 * Callback pour remplacer `FIELD(table,i,j,k...)`
	 * par `CASE WHEN table=i THEN n ... ELSE 0 END`
	 *
	 * @param array $matches Captures
	 * @return string texte de liste ordonnée compris par SQLite
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
