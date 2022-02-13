<?php

namespace Spip\Sql;

/**
 * Gère l'envoi et la réception de requêtes à SQLite, qui peuvent être
 * encadrées de transactions.
 **/
class Sqlite
{
	/** @var SqliteRequeteur[] Liste des instances de requêteurs créés */
	public static $requeteurs = [];
	/** @var bool[] Pour chaque connexion, flag pour savoir si une transaction est en cours */
	public static $transaction_en_cours = [];


	/**
	 * Retourne une unique instance du requêteur
	 *
	 * Retourne une instance unique du requêteur pour une connexion SQLite
	 * donnée
	 *
	 * @param string $serveur
	 *    Nom du connecteur
	 * @return SqliteRequeteur
	 *    Instance unique du requêteur
	 **/
	public static function requeteur($serveur)
	{
		if (!isset(static::$requeteurs[$serveur])) {
			static::$requeteurs[$serveur] = new SqliteRequeteur($serveur);
		}

		return static::$requeteurs[$serveur];
	}

	/**
	 * Prépare le texte d'une requête avant son exécution
	 *
	 * Adapte la requête au format plus ou moins MySQL par un format
	 * compris de SQLite.
	 *
	 * Change les préfixes de tables SPIP par ceux véritables
	 *
	 * @param string $query Requête à préparer
	 * @param string $serveur Nom de la connexion
	 * @return string           Requête préparée
	 */
	public static function traduire_requete($query, $serveur)
	{
		$requeteur = static::requeteur($serveur);
		$traducteur = new SqliteTraducteur($query, $requeteur->prefixe, $requeteur->sqlite_version);

		return $traducteur->traduire_requete();
	}

	/**
	 * Démarre une transaction
	 *
	 * @param string $serveur Nom de la connexion
	 **/
	public static function demarrer_transaction($serveur)
	{
		Sqlite::executer_requete('BEGIN TRANSACTION', $serveur);
		Sqlite::$transaction_en_cours[$serveur] = true;
	}

	/**
	 * Exécute la requête donnée
	 *
	 * @param string $query Requête
	 * @param string $serveur Nom de la connexion
	 * @param null|bool $tracer Demander des statistiques (temps) ?
	 **/
	public static function executer_requete($query, $serveur, $tracer = null)
	{
		$requeteur = Sqlite::requeteur($serveur);

		return $requeteur->executer_requete($query, $tracer);
	}

	/**
	 * Obtient l'identifiant de la dernière ligne insérée ou modifiée
	 *
	 * @param string $serveur Nom de la connexion
	 * return int                Identifiant
	 **/
	public static function last_insert_id($serveur)
	{
		$requeteur = Sqlite::requeteur($serveur);

		return $requeteur->last_insert_id($serveur);
	}

	/**
	 * Annule une transaction
	 *
	 * @param string $serveur Nom de la connexion
	 **/
	public static function annuler_transaction($serveur)
	{
		Sqlite::executer_requete('ROLLBACK', $serveur);
		Sqlite::$transaction_en_cours[$serveur] = false;
	}

	/**
	 * Termine une transaction
	 *
	 * @param string $serveur Nom de la connexion
	 **/
	public static function finir_transaction($serveur)
	{
		// si pas de transaction en cours, ne rien faire et le dire
		if (
			!isset(Sqlite::$transaction_en_cours[$serveur])
			or Sqlite::$transaction_en_cours[$serveur] == false
		) {
			return false;
		}
		// sinon fermer la transaction et retourner true
		Sqlite::executer_requete('COMMIT', $serveur);
		Sqlite::$transaction_en_cours[$serveur] = false;

		return true;
	}
}
