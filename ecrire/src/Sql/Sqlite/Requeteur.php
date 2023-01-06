<?php

namespace Spip\Sql\Sqlite;

/*
 * Classe pour partager les lancements de requête
 *
 * Instanciée une fois par `$serveur` :
 *
 * - peut corriger la syntaxe des requêtes pour la conformité à SQLite
 * - peut tracer les requêtes
 */

class Requeteur
{
	/** @var string texte de la requête */
	public $query = ''; // la requete
	/** @var string Nom de la connexion */
	public $serveur = '';
	/** @var \PDO|null Identifiant de la connexion SQLite */
	public $link = null;
	/** @var string Prefixe des tables SPIP */
	public $prefixe = '';
	/** @var string Nom de la base de donnée */
	public $db = '';
	/** @var bool Doit-on tracer les requetes (var_profile) ? */
	public $tracer = false; // doit-on tracer les requetes (var_profile)

	/** @var string Version de SQLite (2 ou 3) */
	public $sqlite_version = '';

	/**
	 * Constructeur
	 *
	 * @param string $serveur
	 */
	public function __construct($serveur = '')
	{
		_sqlite_init();
		$this->serveur = strtolower($serveur);

		if (!($this->link = _sqlite_link($this->serveur)) && (!defined('_ECRIRE_INSTALL') || !_ECRIRE_INSTALL)) {
			spip_log('Aucune connexion sqlite (link)', 'sqlite.' . _LOG_ERREUR);

			return;
		}

		$this->sqlite_version = _sqlite_is_version('', $this->link);

		$this->prefixe = $GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['prefixe'];
		$this->db = $GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['db'];

		// tracage des requetes ?
		$this->tracer = (isset($_GET['var_profile']) && $_GET['var_profile']);
	}

	/**
	 * Lancer la requête transmise et faire le tracage si demandé
	 *
	 * @param string $query
	 *     Requête à exécuter
	 * @param bool|null $tracer
	 *     true pour tracer la requête
	 * @return bool|\PDOStatement|array
	 */
	public function executer_requete($query, $tracer = null)
	{
		if (is_null($tracer)) {
			$tracer = $this->tracer;
		}
		$err = '';
		$t = 0;
		if ($tracer or (defined('_DEBUG_TRACE_QUERIES') and _DEBUG_TRACE_QUERIES)) {
			include_spip('public/tracer');
			$t = trace_query_start();
		}

		# spip_log("requete: $this->serveur >> $query",'sqlite.'._LOG_DEBUG); // boum ? pourquoi ?
		if ($this->link) {
			// memoriser la derniere erreur PHP vue
			$last_error = (function_exists('error_get_last') ? error_get_last() : '');
			$e = null;
			// sauver la derniere requete
			$GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['last'] = $query;
			$GLOBALS['connexions'][$this->serveur ? $this->serveur : 0]['total_requetes']++;

			try {
				$r = $this->link->query($query);
			} catch (\PDOException $e) {
				spip_log('PDOException: ' . $e->getMessage(), 'sqlite.' . _LOG_DEBUG);
				$r = false;
			}

			// loger les warnings/erreurs eventuels de sqlite remontant dans PHP
			if ($e and $e instanceof \PDOException) {
				$err = strip_tags($e->getMessage()) . ' in ' . $e->getFile() . ' line ' . $e->getLine();
				spip_log("$err - " . $query, 'sqlite.' . _LOG_ERREUR);
			} elseif ($err = (function_exists('error_get_last') ? error_get_last() : '') and $err != $last_error) {
				$err = strip_tags($err['message']) . ' in ' . $err['file'] . ' line ' . $err['line'];
				spip_log("$err - " . $query, 'sqlite.' . _LOG_ERREUR);
			} else {
				$err = '';
			}
		} else {
			$r = false;
		}

		if (spip_sqlite_errno($this->serveur)) {
			$err .= spip_sqlite_error($query, $this->serveur);
		}

		return $t ? trace_query_end($query, $t, $r, $err, $this->serveur) : $r;
	}

	/**
	 * Obtient l'identifiant de la dernière ligne insérée ou modifiée
	 *
	 * @return string|false
	 **/
	public function last_insert_id()
	{
		return $this->link->lastInsertId();
	}
}
