<?php

/* *************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Definition de l'API SQL
 *
 * Ce fichier definit la couche d'abstraction entre SPIP et ses serveurs SQL.
 * Cette version 1 est un ensemble de fonctions ecrites rapidement
 * pour generaliser le code strictement MySQL de SPIP <= 1.9.2
 * Des retouches sont a prevoir apres l'experience des premiers portages.
 * Les symboles sql_* (constantes et nom de fonctions) sont reserves
 * a cette interface, sans quoi le gestionnaire de version dysfonctionnera.
 *
 * @package SPIP\Core\SQL\API
 * @version 1
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Version de l'API SQL */
define('sql_ABSTRACT_VERSION', 1);
include_spip('base/connect_sql');

/**
 * Retourne la pile de fonctions utilisée lors de la précence d'une erreur SQL
 *
 * @note
 *     Ignore les fonctions `include_once`, `include_spip`, `find_in_path`
 * @param bool $compil_info
 *      - false : Retourne un texte présentant les fonctions utilisées
 *      - true : retourne un tableau indiquant un contexte de compilation à l'origine de la requête,
 *               utile pour présenter une erreur au débuggueur via `erreur_squelette()`
 * @return array|string
 *     contexte de l'erreur
 **/
function sql_error_backtrace($compil_info = false) {
	$trace = debug_backtrace();
	$caller = array_shift($trace);
	while (count($trace) and (empty($trace[0]['file']) or $trace[0]['file'] === $caller['file'] or $trace[0]['file'] === __FILE__)) {
		array_shift($trace);
	}

	if ($compil_info) {
		$contexte_compil = [
			$trace[0]['file'],// sourcefile
			'', //nom
			(isset($trace[1]) ? $trace[1]['function'] . "(){\n" : '')
			. $trace[0]['function'] . '();'
			. (isset($trace[1]) ? "\n}" : ''), //id_boucle
			$trace[0]['line'], // ligne
			$GLOBALS['spip_lang'], // lang
		];

		return $contexte_compil;
	}

	$message = count($trace) ? $trace[0]['file'] . ' L' . $trace[0]['line'] : '';
	$f = [];
	while (count($trace) and $t = array_shift($trace)) {
		if (in_array($t['function'], ['include_once', 'include_spip', 'find_in_path'])) {
			break;
		}
		$f[] = $t['function'];
	}
	if (count($f)) {
		$message .= ' [' . implode('(),', $f) . '()]';
	}

	return $message;
}


/**
 * Charge le serveur de base de donnees
 *
 * Fonction principale. Elle charge l'interface au serveur de base de donnees
 * via la fonction spip_connect_version qui etablira la connexion au besoin.
 * Elle retourne la fonction produisant la requete SQL demandee
 * Erreur fatale si la fonctionnalite est absente sauf si le 3e arg <> false
 *
 * @internal Cette fonction de base est appelee par les autres fonctions sql_*
 * @param string $ins_sql
 *    Instruction de l'API SQL demandee (insertq, update, select...)
 * @param string $serveur
 *    Nom du connecteur ('' pour celui par defaut a l'installation de SPIP)
 * @param bool $continue
 *    true pour ne pas generer d'erreur si le serveur SQL ne dispose pas de la fonction demandee
 * @return string|array
 *    Nom de la fonction a appeler pour l'instruction demandee pour le type de serveur SQL correspondant au fichier de connexion.
 *    Si l'instruction demandee n'existe pas, retourne la liste de toutes les instructions du serveur SQL avec $continue a true.
 *
 **/
function sql_serveur($ins_sql = '', $serveur = '', $continue = false) {
	static $sql_serveur = [];
	if (!isset($sql_serveur[$serveur][$ins_sql])) {
		$f = spip_connect_sql(sql_ABSTRACT_VERSION, $ins_sql, $serveur, $continue);
		if (!is_string($f) or !$f) {
			return $f;
		}
		$sql_serveur[$serveur][$ins_sql] = $f;
	}

	return $sql_serveur[$serveur][$ins_sql];
}

/**
 * Demande si un charset est disponible
 *
 * Demande si un charset (tel que utf-8) est disponible
 * sur le gestionnaire de base de données de la connexion utilisée
 *
 * @api
 * @see sql_set_charset() pour utiliser un charset
 *
 * @param string $charset
 *     Le charset souhaité
 * @param string $serveur
 *     Le nom du connecteur
 * @param bool $option
 *     Inutilise
 * @return string|bool
 *     Retourne le nom du charset si effectivement trouvé, sinon false.
 **/
function sql_get_charset($charset, $serveur = '', $option = true) {
	// le nom http du charset differe parfois du nom SQL utf-8 ==> utf8 etc.
	$desc = sql_serveur('', $serveur, true, true);
	$desc = $desc[sql_ABSTRACT_VERSION];
	$c = $desc['charsets'][$charset];
	if ($c) {
		if (function_exists($f = @$desc['get_charset'])) {
			if ($f($c, $serveur, $option !== false)) {
				return $c;
			}
		}
	}
	spip_log(
		"SPIP ne connait pas les Charsets disponibles sur le serveur $serveur. Le serveur choisira seul.",
		_LOG_AVERTISSEMENT
	);

	return false;
}


/**
 * Regler le codage de connexion
 *
 * Affecte un charset (tel que utf-8) sur la connexion utilisee
 * avec le gestionnaire de base de donnees
 *
 * @api
 * @see sql_get_charset() pour tester l'utilisation d'un charset
 *
 * @param string $charset
 *    Le charset souhaite
 * @param string $serveur
 *    Le nom du connecteur
 * @param bool|string $option
 *    Peut avoir 2 valeurs :
 *    - true pour executer la requete.
 *    - continue pour ne pas echouer en cas de serveur sql indisponible.
 *
 * @return bool
 *    Retourne true si elle reussie.
 **/
function sql_set_charset($charset, $serveur = '', $option = true) {
	$f = sql_serveur('set_charset', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}

	return $f($charset, $serveur, $option !== false);
}


/**
 * Effectue une requête de selection
 *
 * Fonction de selection (SELECT), retournant la ressource interrogeable par sql_fetch.
 *
 * @api
 * @see sql_fetch()      Pour boucler sur les resultats de cette fonction
 *
 * @param array|string $select
 *     Liste des champs a recuperer (Select)
 * @param array|string $from
 *     Tables a consulter (From)
 * @param array|string $where
 *     Conditions a remplir (Where)
 * @param array|string $groupby
 *     Critere de regroupement (Group by)
 * @param array|string $orderby
 *     Tableau de classement (Order By)
 * @param string $limit
 *     Critere de limite (Limit)
 * @param array $having
 *     Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 *     Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false -> ne pas l'exécuter mais la retourner,
 *     - continue -> ne pas echouer en cas de serveur sql indisponible,
 *     - true|array -> executer la requête.
 *     Le cas array est, pour une requete produite par le compilateur,
 *     un tableau donnnant le contexte afin d'indiquer le lieu de l'erreur au besoin
 *
 *
 * @return mixed
 *     Ressource SQL
 *
 *     - Ressource SQL pour sql_fetch, si la requete est correcte
 *     - false en cas d'erreur
 *     - Chaine contenant la requete avec $option=false
 *
 * Retourne false en cas d'erreur, apres l'avoir denoncee.
 * Les portages doivent retourner la requete elle-meme en cas d'erreur,
 * afin de disposer du texte brut.
 *
 **/
function sql_select(
	$select = [],
	$from = [],
	$where = [],
	$groupby = [],
	$orderby = [],
	$limit = '',
	$having = [],
	$serveur = '',
	$option = true
) {
	$f = sql_serveur('select', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}

	$debug = (defined('_VAR_MODE') and _VAR_MODE == 'debug');
	if (($option !== false) and !$debug) {
		$res = $f(
			$select,
			$from,
			$where,
			$groupby,
			$orderby,
			$limit,
			$having,
			$serveur,
			is_array($option) ? true : $option
		);
	} else {
		$query = $f($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, false);
		if (!$option) {
			return $query;
		}
		// le debug, c'est pour ce qui a ete produit par le compilateur
		if (isset($GLOBALS['debug']['aucasou'])) {
			list($table, $id, ) = $GLOBALS['debug']['aucasou'];
			$nom = $GLOBALS['debug_objets']['courant'] . $id;
			$GLOBALS['debug_objets']['requete'][$nom] = $query;
		}
		$res = $f($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, true);
	}

	// en cas d'erreur
	if (!is_string($res)) {
		return $res;
	}
	// denoncer l'erreur SQL dans sa version brute
	spip_sql_erreur($serveur);
	// idem dans sa version squelette (prefixe des tables non substitue)
	$contexte_compil = sql_error_backtrace(true);
	erreur_squelette([sql_errno($serveur), sql_error($serveur), $res], $contexte_compil);

	return false;
}


/**
 * Recupere la syntaxe de la requete select sans l'executer
 *
 * Passe simplement $option a false au lieu de true
 * sans obliger a renseigner tous les arguments de sql_select.
 * Les autres parametres sont identiques.
 *
 * @api
 * @uses sql_select()
 *
 * @param array|string $select
 *    Liste des champs a recuperer (Select)
 * @param array|string $from
 *    Tables a consulter (From)
 * @param array|string $where
 *    Conditions a remplir (Where)
 * @param array|string $groupby
 *    Critere de regroupement (Group by)
 * @param array|string $orderby
 *    Tableau de classement (Order By)
 * @param string $limit
 *    Critere de limite (Limit)
 * @param array $having
 *    Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 *    Le serveur sollicite (pour retrouver la connexion)
 *
 * @return mixed
 *    Chaine contenant la requete
 *    ou false en cas d'erreur
 *
 **/
function sql_get_select(
	$select = [],
	$from = [],
	$where = [],
	$groupby = [],
	$orderby = [],
	$limit = '',
	$having = [],
	$serveur = ''
) {
	return sql_select($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, false);
}


/**
 * Retourne le nombre de lignes d'une sélection
 *
 * Ramène seulement et tout de suite le nombre de lignes
 * Pas de colonne ni de tri à donner donc.
 *
 * @api
 * @see sql_count()
 * @example
 *     ```
 *     if (sql_countsel('spip_mots_liens', array(
 *         "objet=".sql_quote('article'),
 *         "id_article=".sql_quote($id_article)) > 0) {
 *             // ...
 *     }
 *     ```
 *
 * @param array|string $from
 *    Tables a consulter (From)
 * @param array|string $where
 *    Conditions a remplir (Where)
 * @param array|string $groupby
 *    Critere de regroupement (Group by)
 * @param array $having
 *    Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 *    Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 *    Peut avoir 3 valeurs :
 *
 *    - false -> ne pas l'executer mais la retourner,
 *    - continue -> ne pas echouer en cas de serveur sql indisponible,
 *    - true -> executer la requete.
 *
 * @return int|bool
 *     - Nombre de lignes de resultat
 *     - ou false en cas d'erreur
 *
 **/
function sql_countsel(
	$from = [],
	$where = [],
	$groupby = [],
	$having = [],
	$serveur = '',
	$option = true
) {
	$f = sql_serveur('countsel', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($from, $where, $groupby, $having, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Modifie la structure de la base de données
 *
 * Effectue une opération ALTER.
 *
 * @example
 *     ```
 *     sql_alter('DROP COLUMN supprimer');
 *     ```
 *
 * @api
 * @param string $q
 *     La requête à exécuter (sans la préceder de 'ALTER ')
 * @param string $serveur
 *     Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 *     Peut avoir 2 valeurs :
 *
 *     - true : exécuter la requete
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return mixed
 *     2 possibilités :
 *
 *     - Incertain en cas d'exécution correcte de la requête
 *     - false en cas de serveur indiponible ou d'erreur
 *
 *     Ce retour n'est pas pertinent pour savoir si l'opération est correctement réalisée.
 **/
function sql_alter($q, $serveur = '', $option = true) {
	$f = sql_serveur('alter', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($q, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Retourne un enregistrement d'une selection
 *
 * Retourne un resultat d'une ressource obtenue avec sql_select()
 *
 * @api
 * @param mixed $res
 *    Ressource retournee par sql_select()
 * @param string $serveur
 *    Le nom du connecteur
 * @param bool|string $option
 *    Peut avoir 2 valeurs :
 *    - true -> executer la requete
 *    - continue -> ne pas echouer en cas de serveur sql indisponible
 *
 * @return array
 *    Tableau de cles (colonnes SQL ou alias) / valeurs (valeurs dans la colonne de la table ou calculee)
 *    presentant une ligne de resultat d'une selection
 */
function sql_fetch($res, $serveur = '', $option = true) {
	$f = sql_serveur('fetch', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}

	return $f($res, null, $serveur, $option !== false);
}


/**
 * Retourne tous les enregistrements d'une selection
 *
 * Retourne tous les resultats d'une ressource obtenue avec sql_select()
 * dans un tableau
 *
 * @api
 * @param mixed $res
 *    Ressource retournee par sql_select()
 * @param string $serveur
 *    Le nom du connecteur
 * @param bool|string $option
 *    Peut avoir 2 valeurs :
 *    - true -> executer la requete
 *    - continue -> ne pas echouer en cas de serveur sql indisponible
 *
 * @return array
 *    Tableau contenant les enregistrements.
 *    Chaque entree du tableau est un autre tableau
 *    de cles (colonnes SQL ou alias) / valeurs (valeurs dans la colonne de la table ou calculee)
 *    presentant une ligne de resultat d'une selection
 */
function sql_fetch_all($res, $serveur = '', $option = true) {
	$rows = [];
	if (!$res) {
		return $rows;
	}
	$f = sql_serveur('fetch', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return [];
	}
	while ($r = $f($res, null, $serveur, $option !== false)) {
		$rows[] = $r;
	}
	sql_free($res, $serveur);

	return $rows;
}

/**
 * Déplace le pointeur d'une ressource de sélection
 *
 * Deplace le pointeur sur un numéro de ligne précisé
 * sur une ressource issue de sql_select, afin que
 * le prochain sql_fetch récupère cette ligne.
 *
 * @api
 * @see sql_skip() Pour sauter des enregistrements
 *
 * @param mixed $res
 *    Ressource issue de sql_select
 * @param int $row_number
 *    Numero de ligne sur laquelle placer le pointeur
 * @param string $serveur
 *    Le nom du connecteur
 * @param bool|string $option
 *    Peut avoir 2 valeurs :
 *    - true -> executer la requete
 *    - continue -> ne pas echouer en cas de serveur sql indisponible
 *
 * @return bool
 *    Operation effectuée (true), sinon false.
 **/
function sql_seek($res, $row_number, $serveur = '', $option = true) {
	$f = sql_serveur('seek', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($res, $row_number, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}


/**
 * Liste des bases de donnees accessibles
 *
 * Retourne un tableau du nom de toutes les bases de donnees
 * accessibles avec les permissions de l'utilisateur SQL
 * de cette connexion.
 * Attention on n'a pas toujours les droits !
 *
 * @api
 * @param string $serveur
 *    Nom du connecteur
 * @param bool|string $option
 *    Peut avoir 2 valeurs :
 *    - true -> executer la requete
 *    - continue -> ne pas echouer en cas de serveur sql indisponible
 *
 * @return array|bool
 *    Tableau contenant chaque nom de base de donnees.
 *    False en cas d'erreur.
 **/
function sql_listdbs($serveur = '', $option = true) {
	$f = sql_serveur('listdbs', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($serveur);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}


/**
 * Demande d'utiliser d'une base de donnees
 *
 * @api
 * @param string $nom
 *     Nom de la base a utiliser
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 2 valeurs :
 *
 *     - true -> executer la requete
 *     - continue -> ne pas echouer en cas de serveur sql indisponible
 *
 * @return bool|string
 *     - True ou nom de la base en cas de success.
 *     - False en cas d'erreur.
 **/
function sql_selectdb($nom, $serveur = '', $option = true) {
	$f = sql_serveur('selectdb', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($nom, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Retourne le nombre de lignes d’une ressource de sélection obtenue
 * avec `sql_select()`
 *
 * @api
 * @see sql_select()
 * @see sql_countsel()
 *
 * @param Ressource $res
 *     Ressource SQL
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 2 valeurs :
 *
 *     - true -> executer la requete
 *     - continue -> ne pas echouer en cas de serveur sql indisponible
 * @return bool|string
 *     - int Nombre de lignes,
 *     - false en cas d'erreur.
 **/
function sql_count($res, $serveur = '', $option = true) {
	$f = sql_serveur('count', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($res, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Libère une ressource de résultat
 *
 * Indique au gestionnaire SQL de libérer de sa mémoire la ressoucre de
 * résultat indiquée car on n'a plus besoin de l'utiliser.
 *
 * @param Ressource|Object $res
 *     Ressource de résultat
 * @param string $serveur
 *     Nom de la connexion
 * @param bool|string $option
 *     Peut avoir 2 valeurs :
 *
 *     - true -> exécuter la requete
 *     - continue -> ne pas échouer en cas de serveur SQL indisponible
 * @return bool
 *     True si réussi
 */
function sql_free($res, $serveur = '', $option = true) {
	$f = sql_serveur('free', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}

	return $f($res);
}


/**
 * Insère une ligne dans une table
 *
 * @see sql_insertq()
 * @see sql_quote()
 * @note
 *   Cette fonction ne garantit pas une portabilité totale,
 *   et n'est là que pour faciliter des migrations de vieux scripts.
 *   Préférer sql_insertq.
 *
 * @param string $table
 *     Nom de la table SQL
 * @param string $noms
 *     Liste des colonnes impactées,
 * @param string $valeurs
 *     Liste des valeurs,
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 *
 * @return bool|string
 *     - int|true identifiant de l'élément inséré (si possible), ou true, si réussite
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function sql_insert($table, $noms, $valeurs, $desc = [], $serveur = '', $option = true) {
	$f = sql_serveur('insert', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $noms, $valeurs, $desc, $serveur, $option !== false);
	if ($r === false or $r === null) {
		spip_sql_erreur($serveur);
		$r = false;
	}

	return $r;
}

/**
 * Insère une ligne dans une table
 *
 * Protègera chaque valeur comme sql_quote.
 *
 * @api
 * @see sql_insert()
 * @see sql_insertq_multi()
 * @see sql_quote()
 * @see objet_inserer()
 * @example
 *     ```
 *     $titre = _request('titre');
 *     $id = sql_insertq('spip_rubriques', array('titre' => $titre));
 *     ```
 *
 * @param string $table
 *     Nom de la table SQL
 * @param array $couples
 *     Tableau (nom => valeur)
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 *
 * @return bool|string
 *     - int|true identifiant de l'élément inséré (si possible), ou true, si réussite
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function sql_insertq($table, $couples = [], $desc = [], $serveur = '', $option = true) {
	$f = sql_serveur('insertq', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $couples, $desc, $serveur, $option !== false);
	if ($r === false or $r === null) {
		spip_sql_erreur($serveur);
		$r = false;
	}

	return $r;
}

/**
 * Insère plusieurs lignes d'un coup dans une table
 *
 * Insère en une opération plusieurs éléments au schéma identique
 * dans une table de la base de données. Lorsque les portages le permettent,
 * ils utilisent une seule requête SQL pour réaliser l’ajout.
 *
 * @api
 * @see sql_insertq()
 *
 * @param string $table
 *     Nom de la table SQL
 * @param array $couples
 *     Tableau de tableaux associatifs (nom => valeur)
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 *
 * @return bool|string
 *     - True en cas de succès,
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function sql_insertq_multi($table, $couples = [], $desc = [], $serveur = '', $option = true) {
	$f = sql_serveur('insertq_multi', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $couples, $desc, $serveur, $option !== false);
	if ($r === false or $r === null) {
		spip_sql_erreur($serveur);
		$r = false;
	}

	return $r;
}

/**
 * Met à jour des enregistrements d'une table SQL
 *
 * Les valeurs ne sont pas échappées, ce qui permet de modifier une colonne
 * en utilisant la valeur d'une autre colonne ou une expression SQL.
 *
 * Il faut alors protéger avec sql_quote() manuellement les valeurs qui
 * en ont besoin.
 *
 * Dans les autres cas, préférer sql_updateq().
 *
 * @api
 * @see sql_updateq()
 *
 * @param string $table
 *     Nom de la table
 * @param array $exp
 *     Couples (colonne => valeur)
 * @param string|array $where
 *     Conditions a remplir (Where)
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom de la connexion
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return array|bool|string
 *     - string : texte de la requête si demandé
 *     - true si la requête a réussie, false sinon
 *     - array Tableau décrivant la requête et son temps d'exécution si var_profile est actif
 */
function sql_update($table, $exp, $where = '', $desc = [], $serveur = '', $option = true) {
	$f = sql_serveur('update', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $exp, $where, $desc, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}


/**
 * Met à jour du contenu d’une table SQL
 *
 * Le contenu transmis à la fonction est protégé automatiquement
 * comme sql_quote().
 *
 * @api
 * @see sql_quote()
 * @see sql_update()
 * @example
 *     ```
 *     sql_updateq('table', array('colonne' => $valeur), 'id_table=' . intval($id_table));
 *     sql_updateq("spip_auteurs", array("statut" => '6forum'), "id_auteur=$id_auteur") ;
 *     ```
 * @note
 *   sql_update() est presque toujours appelée sur des constantes ou des dates
 *   Cette fonction (sql_updateq) est donc plus utile que la précédente,
 *   d'autant qu'elle permet de gerer les différences de représentation des constantes.
 *
 * @param string $table
 *     Nom de la table SQL
 * @param array $exp
 *     Couples (colonne => valeur)
 * @param array|string $where
 *     Conditions à vérifier
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return bool|string
 *     - true si réussite
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function sql_updateq($table, $exp, $where = '', $desc = [], $serveur = '', $option = true) {
	$f = sql_serveur('updateq', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $exp, $where, $desc, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Supprime des enregistrements d'une table
 *
 * @example
 *     ```
 *     sql_delete('spip_articles', 'id_article='.sql_quote($id_article));
 *     ```
 *
 * @api
 * @param string $table
 *     Nom de la table SQL
 * @param string|array $where
 *     Conditions à vérifier
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 *
 * @return bool|string
 *     - int : nombre de suppressions réalisées,
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function sql_delete($table, $where = '', $serveur = '', $option = true) {
	$f = sql_serveur('delete', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $where, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Insère où met à jour une entrée d’une table SQL
 *
 * La clé ou les cles primaires doivent être présentes dans les données insérés.
 * La fonction effectue une protection automatique des données.
 *
 * Préférez sql_insertq() et sql_updateq().
 *
 * @see sql_insertq()
 * @see sql_updateq()
 *
 * @param string $table
 *     Nom de la table SQL
 * @param array $couples
 *     Couples colonne / valeur à modifier,
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 *
 * @return bool|string
 *     - true si réussite
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function sql_replace($table, $couples, $desc = [], $serveur = '', $option = true) {
	$f = sql_serveur('replace', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $couples, $desc, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}


/**
 * Insère où met à jour des entrées d’une table SQL
 *
 * La clé ou les cles primaires doivent être présentes dans les données insérés.
 * La fonction effectue une protection automatique des données.
 *
 * Préférez sql_insertq_multi() et sql_updateq().
 *
 * @see sql_insertq_multi()
 * @see sql_updateq()
 * @see sql_replace()
 *
 * @param string $table
 *     Nom de la table SQL
 * @param array $tab_couples
 *     Tableau de tableau (colonne / valeur à modifier),
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 *
 * @return bool|string
 *     - true si réussite
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function sql_replace_multi($table, $tab_couples, $desc = [], $serveur = '', $option = true) {
	$f = sql_serveur('replace_multi', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $tab_couples, $desc, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Supprime une table SQL (structure et données)
 *
 * @api
 * @see sql_create()
 * @see sql_drop_view()
 *
 * @param string $table
 *     Nom de la table
 * @param string $exist
 *     true pour ajouter un test sur l'existence de la table, false sinon
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return bool|string
 *     - True en cas de succès,
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function sql_drop_table($table, $exist = '', $serveur = '', $option = true) {
	$f = sql_serveur('drop_table', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $exist, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Supprime une vue SQL
 *
 * @api
 * @see sql_create_view()
 * @see sql_drop_table()
 *
 * @param string $table Nom de la vue SQL
 * @param string $exist True pour ajouter un test d'existence avant de supprimer
 * @param string $serveur Nom de la connexion
 * @param bool $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return bool|string
 *     - string Texte de la requête si demandé
 *     - true si la requête a réussie, false sinon
 */
function sql_drop_view($table, $exist = '', $serveur = '', $option = true) {
	$f = sql_serveur('drop_view', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $exist, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Retourne une ressource de la liste des tables de la base de données
 *
 * @api
 * @see sql_alltable()
 *
 * @param string $spip
 *     Filtre sur tables retournées
 *     - NULL : retourne les tables SPIP uniquement (tables préfixées avec le préfixe de la connexion)
 *     - '%' : retourne toutes les tables de la base
 * @param string $serveur
 *     Le nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *     - false -> ne pas l'executer mais la retourner,
 *     - continue -> ne pas echouer en cas de serveur sql indisponible,
 *     - true -> executer la requete.
 * @return ressource
 *     Ressource à utiliser avec sql_fetch()
 **/
function sql_showbase($spip = null, $serveur = '', $option = true) {
	$f = sql_serveur('showbase', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}

	// la globale n'est remplie qu'apres l'appel de sql_serveur.
	if ($spip == null) {
		$connexion = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$spip = $connexion['prefixe'] . '\_%';
	}

	return $f($spip, $serveur, $option !== false);
}

/**
 * Retourne la liste des tables SQL
 *
 * @api
 * @uses sql_showbase()
 * @param string $spip
 *     Filtre sur tables retournées
 *     - NULL : retourne les tables SPIP uniquement (tables préfixées avec le préfixe de la connexion)
 *     - '%' : retourne toutes les tables de la base
 * @param string $serveur
 *     Le nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *     - false -> ne pas l'executer mais la retourner,
 *     - continue -> ne pas echouer en cas de serveur sql indisponible,
 *     - true -> executer la requete.
 * @return array
 *     Liste des tables SQL
 **/
function sql_alltable($spip = null, $serveur = '', $option = true) {
	$q = sql_showbase($spip, $serveur, $option);
	$r = [];
	if ($q) {
		while ($t = sql_fetch($q, $serveur)) {
			$r[] = array_shift($t);
		}
	}

	return $r;
}

/**
 * Retourne la liste (et description) des colonnes et key d’une table SQL
 *
 * @note
 *     Dans la plupart des situations, il vaut mieux utiliser directement
 *     la fonction trouver_table() qui possède un cache.
 *
 * @api
 * @see base_trouver_table_dist()
 *
 * @param string $table
 *     Nom de la table SQL
 * @param bool $table_spip
 *     true pour remplacer automatiquement « spip » par le vrai préfixe de table
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - 'continue' : ne pas échouer en cas de serveur SQL indisponible,
 *     - true : exécuter la requete.
 * @return bool|array
 *     - false en cas d'echec
 *     - sinon array : Tableau avec
 *       - 'field' => array(colonne => description)
 *       - 'key' => array(type => key)
 *       - 'join' => array() // jointures, si déclarées.
 **/
function sql_showtable($table, $table_spip = false, $serveur = '', $option = true) {
	$f = sql_serveur('showtable', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}

	// la globale n'est remplie qu'apres l'appel de sql_serveur.
	if ($table_spip) {
		$connexion = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$prefixe = $connexion['prefixe'];
		$vraie_table = prefixer_table_spip($table, $prefixe);
	} else {
		$vraie_table = $table;
	}

	$f = $f($vraie_table, $serveur, $option !== false);
	if (!$f) {
		return [];
	}
	if (isset($GLOBALS['tables_principales'][$table]['join'])) {
		$f['join'] = $GLOBALS['tables_principales'][$table]['join'];
	} elseif (isset($GLOBALS['tables_auxiliaires'][$table]['join'])) {
		$f['join'] = $GLOBALS['tables_auxiliaires'][$table]['join'];
	}

	return $f;
}

/**
 * Crée une table dans la base de données
 *
 * @api
 * @example
 *     ```
 *     sql_create("spip_tables",
 *       array(
 *           "id_table" => "bigint(20) NOT NULL default '0'",
 *           "colonne1"=> "varchar(3) NOT NULL default 'oui'",
 *           "colonne2"=> "text NOT NULL default ''"
 *        ),
 *        array(
 *           'PRIMARY KEY' => "id_table",
 *           'KEY colonne1' => "colonne1"
 *        )
 *     );
 *     ```
 *
 * @param string $nom
 *     Nom de la table
 * @param array $champs
 *     Couples (colonne => description)
 * @param array $cles
 *     Clé (nomdelaclef => champ)
 * @param bool $autoinc
 *     Si un champ est clef primaire est numérique alors la propriété
 *     d’autoincrémentation sera ajoutée
 * @param bool $temporary
 *     true pour créer une table temporaire (au sens SQL)
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - 'continue' : ne pas échouer en cas de serveur SQL indisponible,
 *     - true : exécuter la requete.
 * @return bool
 *     true si succès, false en cas d'echec
 **/
function sql_create(
	$nom,
	$champs,
	$cles = [],
	$autoinc = false,
	$temporary = false,
	$serveur = '',
	$option = true
) {
	$f = sql_serveur('create', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($nom, $champs, $cles, $autoinc, $temporary, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Crée une base de données
 *
 * @api
 * @param string $nom Nom de la base (sans l'extension de fichier si gestionnaire SQLite)
 * @param string $serveur Nom de la connexion
 * @param bool $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return bool true si la base est créee.
 **/
function sql_create_base($nom, $serveur = '', $option = true) {
	$f = sql_serveur('create_base', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($nom, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}


/**
 * Crée une vue SQL
 *
 * @api
 * @see sql_drop_view()
 * @see sql_create()
 * @see sql_get_select() Pour obtenir le texte de la requête SELECT pour créer la vue.
 *
 * @param string $nom
 *     Nom de la vue
 * @param string $select_query
 *     Une requête SELECT, idéalement crée avec `sql_get_select(...)`
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return bool|string
 *     - true si succès,
 *     - texte de la requête si demandé
 *     - false en cas d'échec.
 **/
function sql_create_view($nom, $select_query, $serveur = '', $option = true) {
	$f = sql_serveur('create_view', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($nom, $select_query, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Retourne l'instruction SQL pour obtenir le texte d'un champ contenant
 * une balise `<multi>` dans la langue indiquée
 *
 * Cette sélection est mise dans l'alias `multi` (instruction AS multi).
 *
 * @example
 *     ```
 *     $t = sql_multi('chapo', 'fr');
 *     ```
 * @api
 * @param string $sel
 *     Colonne ayant le texte
 * @param string $lang
 *     Langue à extraire
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 2 valeurs :
 *
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return string
 *     Texte de sélection pour la requête
 */
function sql_multi($sel, $lang, $serveur = '', $option = true) {
	$f = sql_serveur('multi', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}

	return $f($sel, $lang);
}


/**
 * Retourne la dernière erreur connue
 *
 * @api
 * @param string $serveur
 *      Nom du connecteur
 * @return bool|string
 *      Description de l'erreur
 *      False si le serveur est indisponible
 */
function sql_error($serveur = '') {
	$f = sql_serveur('error', $serveur, 'continue');
	if (!is_string($f) or !$f) {
		return false;
	}

	return $f('query inconnue', $serveur);
}

/**
 * Retourne le numéro de la derniere erreur connue
 *
 * @api
 * @param string $serveur
 *      Nom du connecteur
 * @return bool|int
 *      Numéro de l'erreur
 *      False si le serveur est indisponible
 */
function sql_errno($serveur = '') {
	$f = sql_serveur('errno', $serveur, 'continue');
	if (!is_string($f) or !$f) {
		return false;
	}

	return $f($serveur);
}

/**
 * Retourne une explication de requête (Explain) SQL
 *
 * @api
 * @param string $q Texte de la requête
 * @param string $serveur Nom de la connexion
 * @param bool $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return array           Tableau de l'explication
 */
function sql_explain($q, $serveur = '', $option = true) {
	$f = sql_serveur('explain', $serveur, 'continue');
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($q, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Optimise une table SQL
 *
 * @api
 * @param string $table Nom de la table
 * @param string $serveur Nom de la connexion
 * @param bool $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return bool            Toujours true
 */
function sql_optimize($table, $serveur = '', $option = true) {
	$f = sql_serveur('optimize', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Répare une table SQL
 *
 * @api
 * @param string $table Nom de la table SQL
 * @param string $serveur Nom de la connexion
 * @param bool $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return bool|string
 *     - string Texte de la requête si demandée,
 *     - true si la requête a réussie, false sinon
 */
function sql_repair($table, $serveur = '', $option = true) {
	$f = sql_serveur('repair', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($table, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}


/**
 * Exécute une requête SQL
 *
 * Fonction la plus générale ... et la moins portable
 * À n'utiliser qu'en dernière extrémité
 *
 * @api
 * @param string $ins Requête
 * @param string $serveur Nom de la connexion
 * @param bool $option
 *     Peut avoir 3 valeurs :
 *
 *     - false : ne pas l'exécuter mais la retourner,
 *     - true : exécuter la requête
 *     - 'continue' : ne pas échouer en cas de serveur sql indisponible
 * @return array|resource|string|bool
 *     - string : Texte de la requête si on ne l'exécute pas
 *     - ressource|bool : Si requête exécutée
 *     - array : Tableau décrivant requête et temps d'exécution si var_profile actif pour tracer.
 */
function sql_query($ins, $serveur = '', $option = true) {
	$f = sql_serveur('query', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($ins, $serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

/**
 * Retourne la première ligne d'une sélection
 *
 * Retourne la première ligne de résultat d'une sélection
 * comme si l'on appelait successivement `sql_select()` puis `sql_fetch()`
 *
 * @example
 *     ```
 *     $art = sql_fetsel(array('id_rubrique','id_secteur'), 'spip_articles', 'id_article='.sql_quote($id_article));
 *     $id_rubrique = $art['id_rubrique'];
 *     ```
 *
 * @api
 * @uses sql_select()
 * @uses sql_fetch()
 * @see  sql_getfetsel()
 *
 * @param array|string $select
 *    Liste des champs a recuperer (Select)
 * @param array|string $from
 *    Tables a consulter (From)
 * @param array|string $where
 *    Conditions a remplir (Where)
 * @param array|string $groupby
 *    Critere de regroupement (Group by)
 * @param array|string $orderby
 *    Tableau de classement (Order By)
 * @param string $limit
 *    Critere de limite (Limit)
 * @param array $having
 *    Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 *    Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 *    Peut avoir 3 valeurs :
 *    - true -> executer la requete.
 *    - continue -> ne pas echouer en cas de serveur sql indisponible.
 *    - false -> ne pas l'executer mais la retourner.
 *
 * @return array
 *     Tableau de la premiere ligne de resultat de la selection tel que
 *     `array('id_rubrique' => 1, 'id_secteur' => 2)`
 *
 **/
function sql_fetsel(
	$select = [],
	$from = [],
	$where = [],
	$groupby = [],
	$orderby = [],
	$limit = '',
	$having = [],
	$serveur = '',
	$option = true
) {
	$q = sql_select($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option === false) {
		return $q;
	}
	if (!$q) {
		return [];
	}
	$r = sql_fetch($q, $serveur, $option);
	sql_free($q, $serveur, $option);

	return $r;
}


/**
 * Retourne le tableau de toutes les lignes d'une selection
 *
 * Retourne toutes les lignes de resultat d'une selection
 * comme si l'on appelait successivement sql_select() puis while(sql_fetch())
 *
 * @example
 *    ```
 *    $rubs = sql_allfetsel('id_rubrique', 'spip_articles', 'id_secteur='.sql_quote($id_secteur));
 *    // $rubs = array(array('id_rubrique'=>1), array('id_rubrique'=>3, ...)
 *    ```
 *
 * @api
 * @uses sql_select()
 * @uses sql_fetch()
 *
 * @param array|string $select
 *    Liste des champs a recuperer (Select)
 * @param array|string $from
 *    Tables a consulter (From)
 * @param array|string $where
 *    Conditions a remplir (Where)
 * @param array|string $groupby
 *    Critere de regroupement (Group by)
 * @param array|string $orderby
 *    Tableau de classement (Order By)
 * @param string $limit
 *    Critere de limite (Limit)
 * @param array $having
 *    Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 *    Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 *    Peut avoir 3 valeurs :
 *    - true -> executer la requete.
 *    - continue -> ne pas echouer en cas de serveur sql indisponible.
 *    - false -> ne pas l'executer mais la retourner.
 *
 * @return array
 *    Tableau de toutes les lignes de resultat de la selection
 *    Chaque entree contient un tableau des elements demandees dans le SELECT.
 *    {@example
 *      ```
 *      array(
 *        array('id_rubrique' => 1, 'id_secteur' => 2)
 *        array('id_rubrique' => 4, 'id_secteur' => 2)
 *        ...
 *      )
 *      ```
 *    }
 *
 **/
function sql_allfetsel(
	$select = [],
	$from = [],
	$where = [],
	$groupby = [],
	$orderby = [],
	$limit = '',
	$having = [],
	$serveur = '',
	$option = true
) {
	$q = sql_select($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option === false) {
		return $q;
	}

	return sql_fetch_all($q, $serveur, $option);
}


/**
 * Retourne un unique champ d'une selection
 *
 * Retourne dans la premiere ligne de resultat d'une selection
 * un unique champ demande
 *
 * @example
 *     ```
 *     $id_rubrique = sql_getfetsel('id_rubrique', 'spip_articles', 'id_article='.sql_quote($id_article));
 *     ```
 *
 * @api
 * @uses sql_fetsel()
 *
 * @param array|string $select
 *     Liste des champs à récupérer (Select)
 * @param array|string $from
 *     Tables à consulter (From)
 * @param array|string $where
 *     Conditions à remplir (Where)
 * @param array|string $groupby
 *     Critère de regroupement (Group by)
 * @param array|string $orderby
 *     Tableau de classement (Order By)
 * @param string $limit
 *     Critère de limite (Limit)
 * @param array $having
 *     Tableau des des post-conditions à remplir (Having)
 * @param string $serveur
 *     Le serveur sollicité (pour retrouver la connexion)
 * @param bool|string $option
 *     Peut avoir 3 valeurs :
 *
 *     - true -> executer la requete.
 *     - continue -> ne pas echouer en cas de serveur sql indisponible.
 *     - false -> ne pas l'executer mais la retourner.
 *
 * @return mixed
 *     Contenu de l'unique valeur demandee du premier enregistrement retourne
 *     ou NULL si la requete ne retourne aucun enregistrement
 *
 **/
function sql_getfetsel(
	$select,
	$from = [],
	$where = [],
	$groupby = [],
	$orderby = [],
	$limit = '',
	$having = [],
	$serveur = '',
	$option = true
) {
	if (preg_match('/\s+as\s+(\w+)$/i', $select, $c)) {
		$id = $c[1];
	} elseif (!preg_match('/\W/', $select)) {
		$id = $select;
	} else {
		$id = 'n';
		$select .= ' AS n';
	}
	$r = sql_fetsel($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option === false) {
		return $r;
	}
	if (!$r) {
		return null;
	}

	return $r[$id];
}

/**
 * Retourne le numero de version du serveur SQL
 *
 * @api
 * @param string $serveur
 *    Nom du connecteur
 * @param bool|string $option
 *    Peut avoir 2 valeurs :
 *    - true pour executer la requete.
 *    - continue pour ne pas echouer en cas de serveur sql indisponible.
 *
 * @return string
 *    Numero de version du serveur SQL
 **/
function sql_version($serveur = '', $option = true) {
	$row = sql_fetsel('version() AS n', '', '', '', '', '', '', $serveur);

	return ($row['n']);
}

/**
 * Informe si le moteur SQL prefere utiliser des transactions
 *
 * Cette fonction experimentale est pour l'instant presente pour accelerer certaines
 * insertions multiples en SQLite, en les encadrant d'une transaction.
 * SQLite ne cree alors qu'un verrou pour l'ensemble des insertions
 * et non un pour chaque, ce qui accelere grandement le processus.
 * Evidemment, si une des insertions echoue, rien ne sera enregistre.
 * Pour ne pas perturber les autres moteurs, cette fonction permet
 * de verifier que le moteur prefere utiliser des transactions dans ce cas.
 *
 * @example
 *    ```
 *    if (sql_preferer_transaction()) {
 *      sql_demarrer_transaction();
 *    }
 *    ```
 *
 * @api
 * @see sql_demarrer_transaction()
 * @see sql_terminer_transaction()
 *
 * @param string $serveur
 *    Nom du connecteur
 * @param bool|string $option
 *    Peut avoir 2 valeurs :
 *    - true pour executer la requete.
 *    - continue pour ne pas echouer en cas de serveur sql indisponible.
 *
 * @return bool
 *    Le serveur SQL prefere t'il des transactions pour les insertions multiples ?
 **/
function sql_preferer_transaction($serveur = '', $option = true) {
	$f = sql_serveur('preferer_transaction', $serveur, 'continue');
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

;

/**
 * Démarre une transaction
 *
 * @api
 * @see sql_terminer_transaction() Pour cloturer la transaction
 *
 * @param string $serveur
 *    Nom du connecteur
 * @param bool|string $option
 *    Peut avoir 3 valeurs :
 *
 *    - true pour executer la requete.
 *    - continue pour ne pas echouer en cas de serveur sql indisponible.
 *    - false pour obtenir le code de la requete
 *
 * @return bool
 *      true si la transaction est demarree
 *      false en cas d'erreur
 **/
function sql_demarrer_transaction($serveur = '', $option = true) {
	$f = sql_serveur('demarrer_transaction', $serveur, 'continue');
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

;

/**
 * Termine une transaction
 *
 * @api
 * @see sql_demarrer_transaction() Pour demarrer une transaction
 *
 * @param string $serveur
 *    Nom du connecteur
 * @param bool|string $option
 *    Peut avoir 3 valeurs :
 *
 *    - true pour executer la requete.
 *    - continue pour ne pas echouer en cas de serveur sql indisponible.
 *    - false pour obtenir le code de la requete
 *
 * @return bool
 *      true si la transaction est demarree
 *      false en cas d'erreur
 **/
function sql_terminer_transaction($serveur = '', $option = true) {
	$f = sql_serveur('terminer_transaction', $serveur, 'continue');
	if (!is_string($f) or !$f) {
		return false;
	}
	$r = $f($serveur, $option !== false);
	if ($r === false) {
		spip_sql_erreur($serveur);
	}

	return $r;
}

;


/**
 * Prépare une chaine hexadécimale
 *
 * Prend une chaîne sur l'aphabet hexa
 * et retourne sa représentation numérique attendue par le serveur SQL.
 * Par exemple : FF ==> 0xFF en MySQL mais x'FF' en PG
 *
 * @api
 * @param string $val
 *     Chaine hexadécimale
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 2 valeurs :
 *
 *     - true pour exécuter la demande.
 *     - 'continue' pour ne pas échouer en cas de serveur SQL indisponible.
 * @return string
 *     Valeur hexadécimale attendue par le serveur SQL
 **/
function sql_hex($val, $serveur = '', $option = true) {
	$f = sql_serveur('hex', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}

	return $f($val);
}

/**
 * Echapper du contenu
 *
 * Echappe du contenu selon ce qu'attend le type de serveur SQL
 * et en fonction du type de contenu.
 *
 * Permet entre autres de se protéger d'injections SQL.
 *
 * Cette fonction est automatiquement appelée par les fonctions `sql_*q`
 * tel que `sql_instertq` ou `sql_updateq`
 *
 * @api
 * @param string $val
 *     Chaine à echapper
 * @param string $serveur
 *     Nom du connecteur
 * @param string $type
 *     Peut contenir une declaration de type de champ SQL.
 *     Exemple : `int NOT NULL` qui sert alors aussi à calculer le type d'échappement
 * @return string
 *     La chaine echappee
 **/
function sql_quote($val, $serveur = '', $type = '') {
	$f = sql_serveur('quote', $serveur, true);
	if (!is_string($f) or !$f) {
		$f = '_q';
	}

	return $f($val, $type);
}

/**
 * Tester si une date est proche de la valeur d'un champ
 *
 * @api
 * @param string $champ
 *     Nom du champ a tester
 * @param int $interval
 *     Valeur de l'intervalle : -1, 4, ...
 * @param string $unite
 *     Utité utilisée (DAY, MONTH, YEAR, ...)
 * @param string $serveur
 *     Nom du connecteur
 * @param bool|string $option
 *     Peut avoir 2 valeurs :
 *
 *     - true pour exécuter la demande.
 *     - 'continue' pour ne pas échouer en cas de serveur SQL indisponible.
 * @return string|bool
 *     - string : Expression SQL
 *     - false si le serveur SQL est indisponible
 **/
function sql_date_proche($champ, $interval, $unite, $serveur = '', $option = true) {
	$f = sql_serveur('date_proche', $serveur, true);
	if (!is_string($f) or !$f) {
		return false;
	}

	return $f($champ, $interval, $unite);
}

/**
 * Retourne une expression IN pour le gestionnaire de base de données
 *
 * Retourne un code à insérer dans une requête SQL pour récupérer
 * les éléments d'une colonne qui appartiennent à une liste donnée
 *
 * @example
 *     `sql_in_quote('id_rubrique', array(3,4,5))`
 *     retourne approximativement «id_rubrique IN (3,4,5)» selon ce qu'attend
 *     le gestionnaire de base de donnée du connecteur en cours.
 *
 * @api
 * @param string $champ
 *     Colonne SQL sur laquelle appliquer le test
 * @param array $valeurs
 *     Liste des valeurs possibles (séparés par des virgules si string)
 *     le format string est historique et n'est accepte que pour des ids numeriques car autrement la securite ne peut etre garantie
 * @param string $not
 *     - '' sélectionne les éléments correspondant aux valeurs
 *     - 'NOT' inverse en sélectionnant les éléments ne correspondant pas aux valeurs
 * @param string $serveur
 *   Nom du connecteur
 * @param string $type
 *   type du champ pour le sql_quote
 * @param bool|string $option
 *   Peut avoir 2 valeurs :
 *
 *   - continue -> ne pas echouer en cas de serveur sql indisponible
 *   - true ou false -> retourne l'expression
 * @return string
 *     Expression de requête SQL
 **/
function sql_in_quote($champ, $valeurs, $not = '', $serveur = '', $type = '', $option = true) {
	$quote = sql_serveur('quote', $serveur, true);
	if (!is_string($quote) or !$quote) {
		return false;
	}

	// sql_quote produit une chaine dans tous les cas
	$valeurs = array_filter($valeurs, function ($v) {
 return !is_array($v);
	});
	$valeurs = array_unique($valeurs);
	$valeurs = $quote($valeurs, $type);

	if (!strlen(trim($valeurs))) {
		return ($not ? '0=0' : '0=1');
	}

	$f = sql_serveur('in', $serveur, $option === 'continue' or $option === false);
	if (!is_string($f) or !$f) {
		return false;
	}

	return $f($champ, $valeurs, $not ? 'NOT' : '', $serveur, $option !== false);
}

/**
 * shorthand historique qui ne permet pas de specifier le type et qui accepte une string pour $valeurs
 *
 * @see sql_in_quote
 *
 * @param string $champ
 * @param array|string $valeurs
 *     Liste des valeurs possibles (séparés par des virgules si string)
 *     le format string est historique et n'est accepte que pour des ids numeriques car autrement la securite ne peut etre garantie
 * @param string $not
 * @param string $serveur
 * @param bool $option
 */
function sql_in($champ, $valeurs, $not = '', $serveur = '', $option = true) {
	$type = '';
	if (!is_array($valeurs)) {
		$valeurs = strval($valeurs);
		if (isset($valeurs[0]) and $valeurs[0] === ',') {
			$valeurs = substr($valeurs, 1);
		}
		// on explode en tableau pour pouvoir securiser le contenu
		$valeurs = explode(',', $valeurs);
		// et on force un cast de type int donc
		$type = 'int';
	}

	return sql_in_quote($champ, $valeurs, $not, $serveur, $type, $option);
}



/**
 * Retourne une expression IN pour le gestionnaire de base de données
 * à partir d'une sélection de données
 *
 * Sélectionne les données (comme sql_select()) et prépare avec l'expression IN
 *
 * @see sql_select()
 * @see sql_in()
 * @note
 *   Penser à dire dans la description du serveur
 *   s'il accepte les requêtes imbriquées afin d'optimiser ca
 *
 * @api
 * @param string $in
 *     Colonne SQL sur laquelle appliquer le test
 * @param array|string $select
 *     Liste des champs à récupérer (Select).
 *     La donnée extraite est le premier élément de la sélection.
 * @param array|string $from
 *     Tables a consulter (From)
 * @param array|string $where
 *     Conditions a remplir (Where)
 * @param array|string $groupby
 *     Critere de regroupement (Group by)
 * @param array|string $orderby
 *     Tableau de classement (Order By)
 * @param string $limit
 *     Critere de limite (Limit)
 * @param array $having
 *     Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 *     Nom du connecteur
 * @return string
 *     Expression de requête SQL
 **/
function sql_in_select(
	$in,
	$select,
	$from = [],
	$where = [],
	$groupby = [],
	$orderby = [],
	$limit = '',
	$having = [],
	$serveur = ''
) {
	$liste = [];
	$res = sql_select($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur);
	while ($r = sql_fetch($res)) {
		$liste[] = array_shift($r);
	}
	sql_free($res);

	return sql_in($in, $liste);
}

/**
 * Implémentation sécurisée du saut en avant.
 *
 * Ne dépend pas de la disponibilité de la fonction `sql_seek()`.
 * Ne fait rien pour une valeur négative ou nulle de `$saut`.
 * Retourne la position après le saut
 *
 * @see sql_seek()
 *
 * @param resource $res
 *    Ressource issue d'une selection sql_select
 * @param int $pos
 *   position courante
 * @param int $saut
 *   saut demande
 * @param int $count
 *   position maximale
 *   (nombre de resultat de la requete OU position qu'on ne veut pas depasser)
 * @param string $serveur
 *   Nom du connecteur
 * @param bool|string $option
 *   Peut avoir 2 valeurs :
 *   - true -> executer la requete
 *   - continue -> ne pas echouer en cas de serveur sql indisponible
 *
 * @return int
 *    Position apres le saut.
 */
function sql_skip($res, $pos, $saut, $count, $serveur = '', $option = true) {
	// pas de saut en arriere qu'on ne sait pas faire sans sql_seek
	if (($saut = intval($saut)) <= 0) {
		return $pos;
	}

	$seek = $pos + $saut;
	// si le saut fait depasser le maxi, on libere la resource
	// et on sort
	if ($seek >= $count) {
		sql_free($res, $serveur, $option);

		return $count;
	}

	if (sql_seek($res, $seek)) {
		$pos = $seek;
	} else {
		while ($pos < $seek and sql_fetch($res, $serveur, $option)) {
			$pos++;
		}
	}

	return $pos;
}


/**
 * Teste qu'une description de champ SQL est de type entier
 *
 * @api
 * @see sql_test_date() pour tester les champs de date
 *
 * @param string $type
 *     Description de la colonne SQL
 * @param string $serveur
 *    Nom du connecteur
 * @param bool $option
 *     Inutilisé
 * @return bool
 *     True si le champ est de type entier
 */
function sql_test_int($type, $serveur = '', $option = true) {
	return preg_match('/^(TINYINT|SMALLINT|MEDIUMINT|INT|INTEGER|BIGINT)/i', trim($type));
}

/**
 * Teste qu'une description de champ SQL est de type entier
 *
 * @api
 * @see sql_test_int() pour tester les champs d'entiers
 *
 * @param string $type
 *     Description de la colonne SQL
 * @param string $serveur
 *    Nom du connecteur
 * @param bool $option
 *     Inutilisé
 * @return bool
 *     True si le champ est de type entier
 */
function sql_test_date($type, $serveur = '', $option = true) {
	return preg_match('/^(DATE|DATETIME|TIMESTAMP|TIME)/i', trim($type));
}

/**
 * Formate une date
 *
 * Formater une date Y-m-d H:i:s sans passer par mktime
 * qui ne sait pas gerer les dates < 1970
 *
 * @api
 * @param int $annee Annee
 * @param int $mois Numero du mois
 * @param int $jour Numero du jour dans le mois
 * @param int $h Heures
 * @param int $m Minutes
 * @param int $s Secondes
 * @param string $serveur
 *     Le serveur sollicite (pour retrouver la connexion)
 * @return string
 *     La date formatee
 */
function sql_format_date($annee = 0, $mois = 0, $jour = 0, $h = 0, $m = 0, $s = 0, $serveur = '') {
	$annee = sprintf('%04s', $annee);
	$mois = sprintf('%02s', $mois);

	if ($annee == '0000') {
		$mois = 0;
	}
	if ($mois == '00') {
		$jour = 0;
	}

	return sprintf('%04u', $annee) . '-' . sprintf('%02u', $mois) . '-'
	. sprintf('%02u', $jour) . ' ' . sprintf('%02u', $h) . ':'
	. sprintf('%02u', $m) . ':' . sprintf('%02u', $s);
}


/**
 * Retourne la description de la table SQL
 *
 * Retrouve la description de la table SQL en privilegiant
 * la structure reelle de la base de donnees.
 * En absence, ce qui arrive lors de l'installation, la fonction
 * s'appuie sur la declaration des tables SQL principales ou auxiliaires.
 *
 * @internal Cette fonction devrait disparaître
 *
 * @param string $nom
 *    Nom de la table dont on souhait la description
 * @param string $serveur
 *    Nom du connecteur
 * @return array|bool
 *    Description de la table ou false si elle n'est pas trouvee ou declaree.
 **/
function description_table($nom, $serveur = '') {

	static $trouver_table;

	/* toujours utiliser trouver_table
	 qui renverra la description theorique
	 car sinon on va se comporter differement selon que la table est declaree
	 ou non
	 */
	if (!$trouver_table) {
		$trouver_table = charger_fonction('trouver_table', 'base');
	}
	if ($desc = $trouver_table($nom, $serveur)) {
		return $desc;
	}

	// sauf a l'installation :
	include_spip('base/serial');
	if (isset($GLOBALS['tables_principales'][$nom])) {
		return $GLOBALS['tables_principales'][$nom];
	}

	include_spip('base/auxiliaires');
	if (isset($GLOBALS['tables_auxiliaires'][$nom])) {
		return $GLOBALS['tables_auxiliaires'][$nom];
	}

	return false;
}

/**
 * Corrige le nom d’une table SQL en utilisant le bon préfixe
 *
 * Ie: si prefixe 'dev', retournera, pour la table 'spip_articles' : 'dev_articles'.
 *
 * @param string $table
 * @param string $prefixe
 * @return string Table sql éventuellement renommée
 */
function prefixer_table_spip($table, $prefixe) {
	if ($prefixe) {
		$table = preg_replace('/^spip_/', $prefixe . '_', $table);
	}
	return $table;
}
