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
 * @package SPIP\SQL\API
 * @version 1
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

/** Version de l'API SQL */
define('sql_ABSTRACT_VERSION', 1);

Class Sql
{
	protected static $driver = null;

public static function connect($host, $port, $login, $pass, $serveur='')
{
	self::$driver = new Mysql();
	
	$r = self::$driver->connect($host, $port, $login, $pass, $serveur);
	if ($r===false) self::error($serveur);
	return $r;
}

/**
 * Demande si un charset est disponible
 *
 * Demande si un charset (tel que utf-8) est disponible
 * sur le gestionnaire de base de donnees de la connexion utilisee
 *
 * @api
 * @see sql_set_charset() pour utiliser un charset
 * 
 * @param string $charset
 * 		Le charset souhaite
 * @param string $serveur
 * 		Le nom du connecteur
 * @param bool $option
 * 		Inutilise
 * @return string|bool
 * 		Retourne le nom du charset si effectivement trouve, sinon false.
**/
public static function get_charset($charset, $serveur='', $option=true)
{
	$c = self::$driver->get_charset($chartset, $serveur, $option);
	spip_log( "SPIP ne connait pas les Charsets disponibles sur le serveur $serveur. Le serveur choisira seul.", _LOG_AVERTISSEMENT);
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
 * 		Le charset souhaite
 * @param string $serveur
 * 		Le nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 2 valeurs : 
 * 		- true pour executer la requete.
 * 		- continue pour ne pas echouer en cas de serveur sql indisponible.
 * 
 * @return bool
 * 		Retourne true si elle reussie.
**/
public static function set_charset($charset, $serveur='', $option=true)
{
	return self::$driver->set_charset($charset, $serveur, $option);
}

/**
 * Effectue une requete de selection
 * 
 * Fonction de selection (SELECT), retournant la ressource interrogeable par sql_fetch.
 *
 * @api
 * @see sql_fetch()      Pour boucler sur les resultats de cette fonction
 * 
 * @param array|string $select
 * 		Liste des champs a recuperer (Select)
 * @param array|string $from
 * 		Tables a consulter (From)
 * @param array|string $where
 * 		Conditions a remplir (Where)
 * @param array|string $groupby
 * 		Critere de regroupement (Group by)
 * @param array|string $orderby
 * 		Tableau de classement (Order By)
 * @param string $limit
 * 		Critere de limite (Limit)
 * @param array $having
 * 		Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 * 		Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 * 		Peut avoir 3 valeurs : 
 * 		- false -> ne pas l'executer mais la retourner, 
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible,
 * 		- true|array -> executer la requete.
 * 		Le cas array est, pour une requete produite par le compilateur,
 * 		un tableau donnnant le contexte afin d'indiquer le lieu de l'erreur au besoin
 *
 * 
 * @return mixed
 * 		Ressource SQL
 * 		- Ressource SQL pour sql_fetch, si la requete est correcte
 * 		- false en cas d'erreur
 * 		- Chaine contenant la requete avec $option=false
 * 
 * Retourne false en cas d'erreur, apres l'avoir denoncee.
 * Les portages doivent retourner la requete elle-meme en cas d'erreur,
 * afin de disposer du texte brut.
 *
**/
public static function select($select = array(), $from = array(), $where = array(), $groupby = array(),
	$orderby = array(), $limit = '', $having = array(), $serveur='', $option=true)
{
	$debug = (defined('_VAR_MODE') AND _VAR_MODE == 'debug');
	$res = self::$driver->select($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, ($option !== false) AND !$debug);
	if (!$option) return $query;
	if ($debug) {
		// le debug, c'est pour ce qui a ete produit par le compilateur
		if (isset($GLOBALS['debug']['aucasou'])) {
			list($table, $id,) = $GLOBALS['debug']['aucasou'];
			$nom = $GLOBALS['debug_objets']['courant'] . $id;
			$GLOBALS['debug_objets']['requete'][$nom] = $res;
		}
		$res = self::select($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, true);
	}

	if ($res===false) self::error();
	return $res;
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
 * 		Liste des champs a recuperer (Select)
 * @param array|string $from
 * 		Tables a consulter (From)
 * @param array|string $where
 * 		Conditions a remplir (Where)
 * @param array|string $groupby
 * 		Critere de regroupement (Group by)
 * @param array|string $orderby
 * 		Tableau de classement (Order By)
 * @param string $limit
 * 		Critere de limite (Limit)
 * @param array $having
 * 		Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 * 		Le serveur sollicite (pour retrouver la connexion)
 * 
 * @return mixed
 * 		Chaine contenant la requete
 * 		ou false en cas d'erreur
 *  
**/
public static function get_select($select = array(), $from = array(), $where = array(), $groupby = array(),
	$orderby = array(), $limit = '', $having = array(), $serveur='')
{
	return self::select($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, false);
}


/**
 * Retourne le nombre de lignes d'une selection
 *
 * Ramene seulement et tout de suite le nombre de lignes
 * Pas de colonne ni de tri a donner donc.
 *
 * @api
 * 
 * @param array|string $from
 * 		Tables a consulter (From)
 * @param array|string $where
 * 		Conditions a remplir (Where)
 * @param array|string $groupby
 * 		Critere de regroupement (Group by)
 * @param array $having
 * 		Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 * 		Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 * 		Peut avoir 3 valeurs : 
 * 		- false -> ne pas l'executer mais la retourner, 
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible,
 * 		- true -> executer la requete.
 *
 * @return int|bool
 * 		Nombre de lignes de resultat
 * 		ou false en cas d'erreur
 *
**/
public static function countsel($from = array(), $where = array(), $groupby = array(),
	$having = array(), $serveur='', $option=true)
{
	$r = self::$driver->countsel($from, $where, $groupby, $having, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

/**
 * Modifie la structure de la base de donnees
 *
 * Effectue une operation ALTER.
 *
 * @example
 * 		<code>sql_alter('DROP COLUMN supprimer');</code>
 *
 * @api
 * @param string $q
 * 		La requete a executer (sans la preceder de 'ALTER ')
 * @param string $serveur
 * 		Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 * 		Peut avoir 2 valeurs : 
 * 		- true -> executer la requete
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible
 * @return mixed
 * 		2 possibilites :
 * 		- Incertain en cas d'execution correcte de la requete
 * 		- false en cas de serveur indiponible ou d'erreur
 * 		Ce retour n'est pas pertinent pour savoir si l'operation est correctement realisee.
**/
public static function alter($q, $serveur='', $option=true)
{
	$r = self::$driver->alter($q, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

/**
 * Retourne un enregistrement d'une selection
 *
 * Retourne un resultat d'une ressource obtenue avec sql_select()
 *
 * @api
 * @param mixed
 * 		Ressource retournee par sql_select()
 * @param string $serveur
 * 		Le nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 2 valeurs : 
 * 		- true -> executer la requete
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible
 * 
 * @return array
 * 		Tableau de cles (colonnes SQL ou alias) / valeurs (valeurs dans la colonne de la table ou calculee)
 * 		presentant une ligne de resultat d'une selection 
 */
public static function fetch($res, $type=MYSQL_ASSOC)
{
	$r = self::$driver->fetch($res, null, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}


/**
 * Retourne tous les enregistrements d'une selection
 *
 * Retourne tous les resultats d'une ressource obtenue avec sql_select()
 * dans un tableau
 *
 * @api
 * @param mixed
 * 		Ressource retournee par sql_select()
 * @param string $serveur
 * 		Le nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 2 valeurs : 
 * 		- true -> executer la requete
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible
 * 
 * @return array
 * 		Tableau contenant les enregistrements.
 * 		Chaque entree du tableau est un autre tableau
 * 		de cles (colonnes SQL ou alias) / valeurs (valeurs dans la colonne de la table ou calculee)
 * 		presentant une ligne de resultat d'une selection 
 */
public static function fetch_all($res, $serveur='', $option=true)
{
	$rows = array();	
	while ($r = self::fetch($q, null))
		$rows[] = $r;
	self::free($res, $serveur);
	return $rows;
}

/**
 * Deplace le pointeur d'une ressource de selection
 *
 * Deplace le pointeur sur un numero de ligne precise
 * sur une ressource issue de sql_select, afin que
 * le prochain sql_fetch recupere cette ligne.
 *
 * @api
 * @see sql_skip() Pour sauter des enregistrements
 *
 * @param mixed $res
 * 		Ressource issue de sql_select
 * @param int $row_number
 * 		Numero de ligne sur laquelle placer le pointeur
 * @param string $serveur
 * 		Le nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 2 valeurs : 
 * 		- true -> executer la requete
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible
 * 
 * @return bool
 * 		Operation effectuee (true), sinon false.
**/
public static function seek($res, $row_number)
{
	$r = self::$driver->seek($res, $row_number);
	if ($r===false) self::error($serveur);
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
 * 		Nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 2 valeurs : 
 * 		- true -> executer la requete
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible
 * 
 * @return array|bool
 * 		Tableau contenant chaque nom de base de donnees.
 * 		False en cas d'erreur.
**/
public static function listdbs($serveur='', $option=true)
{
	$r = self::$driver->listdbs($serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

/**
 * Demande d'utiliser d'une base de donnees
 *
 * @api
 * @param string $nom
 * 		Nom de la base a utiliser
 * @param string $serveur
 * 		Nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 2 valeurs : 
 * 		- true -> executer la requete
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible
 * 
 * @return bool|string
 * 		True ou nom de la base en cas de success.
 * 		False en cas d'erreur.
**/
public static function selectdb($nom, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->selectdb($nom, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// http://doc.spip.org/@sql_count
public static function count($res)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->count($res);
	if ($r===false) self::error($serveur);
	return $r;
}

// http://doc.spip.org/@sql_free
public static function free($res, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->free($res, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// Cette fonction ne garantit pas une portabilite totale
//  ===> lui preferer la suivante.
// Elle est fournie pour permettre l'actualisation de vieux codes 
// par un Sed brutal qui peut donner des resultats provisoirement acceptables
// http://doc.spip.org/@sql_insert
public static function insert($table, $noms, $valeurs, $desc=array(), $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->insert($table, $noms, $valeurs, $desc, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// http://doc.spip.org/@sql_insertq
public static function insertq($table, $couples=array(), $desc=array(), $serveur='', $option=true)
{
	if (!$desc) $desc = self::description_table($table, $serveur);
	if (!$desc) $couples = array();
	$fields =  isset($desc['field'])?$desc['field']:array();

	foreach ($couples as $champ => $val) {
		$couples[$champ]= self::cite($val, $fields[$champ]);
	}

	return self::insert($table,
		'('.join(',',array_keys($couples)).')',
		'('.join(',', $couples).')',
		$desc, $serveur, $option);
}

// http://doc.spip.org/@sql_insertq_multi
public static function insertq_multi($table, $couples=array(), $desc=array(), $serveur='', $option=true)
{
	if (!$desc) $desc = self::description_table($table, $serveur);
	if (!$desc) $tab_couples = array();
	$fields =  isset($desc['field']) ? $desc['field'] : array();
	
	$cles = '(' . join(',',array_keys(reset($tab_couples))) . ')';
	$valeurs = array();
	$r = false;

	// Quoter et Inserer par groupes de 100 max pour eviter un debordement de pile
	foreach ($tab_couples as $couples) {
		foreach ($couples as $champ => $val) {
			$couples[$champ]= self::cite($val, $fields[$champ]);
		}
		$valeurs[] = '(' .join(',', $couples) . ')';
		if (count($valeurs)>=100) {
			$r = self::insert($table, $cles, join(', ', $valeurs), $desc, $serveur, $option);
			$valeurs = array();
		}
	}
	if (count($valeurs))
		$r = self::insert($table, $cles, join(', ', $valeurs), $desc, $serveur, $option);

	return $r; // dans le cas d'une table auto_increment, le dernier insert_id
}

// http://doc.spip.org/@sql_delete
public static function update($table, $exp, $where='', $desc=array(), $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->update($table, $exp, $where, $desc, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// Update est presque toujours appelee sur des constantes ou des dates
// Cette fonction est donc plus utile que la precedente,d'autant qu'elle
// permet de gerer les differences de representation des constantes.
// http://doc.spip.org/@sql_updateq
public static function updateq($table, $exp, $where='', $desc=array(), $serveur='', $option=true)
{
	if (!$champs) return;
	if (!$desc) $desc = self::description_table($table, $serveur);
	if (!$desc) $champs = array(); else $fields =  $desc['field'];
	$set = array();
	foreach ($champs as $champ => $val) {
		$set[] = $champ . '=' . self::cite($val, $fields[$champ]);
	}
	return self::query(
			  self::$driver->calculer_expression('UPDATE', $table, ',')
			. self::$driver->calculer_expression('SET', $set, ',')
			. self::$driver->calculer_expression('WHERE', $where),
			$serveur, $option);
}

// http://doc.spip.org/@sql_delete
public static function delete($table, $where='', $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->delete($table, $where, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// http://doc.spip.org/@sql_replace
public static function replace($table, $couples, $desc=array(), $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->replace($table, $couples, $desc, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// http://doc.spip.org/@sql_replace_multi
public static function replace_multi($table, $tab_couples, $desc=array(), $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->replace_multi($table, $tab_couples, $desc, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// http://doc.spip.org/@sql_drop_table
public static function drop_table($table, $exist='', $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->drop_table($table, $exist, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// supprimer une vue sql
// http://doc.spip.org/@sql_drop_view
public static function drop_view($table, $exist='', $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->drop_view($table, $exist, $serveur, $option!=false);
	if ($r===false) self::error($serveur);
	return $r;
}

/**
 * Retourne une ressource de la liste des tables de la base de données 
 *
 * @api
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
public static function showbase($spip=NULL, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	// la globale n'est remplie qu'apres l'appel de sql_serveur.
	if ($spip == NULL) {
		$connexion = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$spip = $connexion['prefixe'] . '\_%';
	}

	$r = self::$driver->showbase($spip, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
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
public static function alltable($spip=NULL, $serveur='', $option=true)
{
	$q = self::showbase($spip, $serveur, $option);
	$r = array();
	if ($q)
		while ($t = self::fetch($q, $serveur)) {
			$r[] = array_shift($t);
		}
	return $r;
}

// http://doc.spip.org/@sql_showtable
public static function showtable($table, $table_spip=false, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	// la globale n'est remplie qu'apres l'appel de sql_serveur.
	if ($table_spip) {
		$connexion = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$prefixe = $connexion['prefixe'];
		$vraie_table = preg_replace('/^spip/', $prefixe, $table);
	} else $vraie_table = $table;

	$f = self::$driver->showtable($vraie_table, $serveur, $option);
	if (!$f) return array();
	if (isset($GLOBALS['tables_principales'][$table]['join']))
		$f['join'] = $GLOBALS['tables_principales'][$table]['join'];
	elseif (isset($GLOBALS['tables_auxiliaires'][$table]['join']))
		$f['join'] = $GLOBALS['tables_auxiliaires'][$table]['join'];
	return $f;
}

// http://doc.spip.org/@sql_create
public static function create($nom, $champs, $cles=array(), $autoinc=false, $temporary=false, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->create($nom, $champs, $cles, $autoinc, $temporary, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

public function create_base($nom, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->create_base($nom, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// Fonction pour creer une vue 
// nom : nom de la vue,
// select_query : une requete select, idealement cree avec $req = sql_select()
// (en mettant $option du sql_select a false pour recuperer la requete)
// http://doc.spip.org/@sql_create_view
public static function create_view($nom, $select_query, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->create_view($nom, $select_query, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// http://doc.spip.org/@sql_multi
public static function multi($sel, $lang, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->multi($sel, $lang, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
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
public static function error($serveur='')
{
	if (!is_object(self::$driver)) return false;
	
	return self::$driver->error($serveur);
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
public static function errno($serveur='')
{
	if (!is_object(self::$driver)) return false;
	
	return self::$driver->errno($serveur);
}

public static function tracer_erreur($serveur='')
{
	$connexion = spip_connect($serveur);
	$e = Sql::errno($serveur);
	$t = (isset($connexion['type']) ? $connexion['type'] : 'sql');
	$m = "Erreur $e de $t: " . Sql::error($serveur) . "\n" . $connexion['last'];
	$f = $t . $serveur;
	spip_log($m, $f.'.'._LOG_ERREUR);
}

// http://doc.spip.org/@sql_explain
public static function explain($q, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;

	$r = self::$driver->explain($q, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// http://doc.spip.org/@sql_optimize
public static function optimize($table, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;

	$r = self::$driver->optimize($table, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// http://doc.spip.org/@sql_repair
public static function repair($table, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;

	$r = self::$driver->repair($table, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// Fonction la plus generale ... et la moins portable
// A n'utiliser qu'en derniere extremite

// http://doc.spip.org/@sql_query
public static function query($ins, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;

	$r = self::$driver->query($ins, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

/**
 * Retourne la premiere ligne d'une selection
 * 
 * Retourne la premiere ligne de resultat d'une selection
 * comme si l'on appelait successivement sql_select() puis sql_fetch()
 * 
 * @example
 * 		<code>
 * 		$art = Sql::fetsel(array('id_rubrique','id_secteur'), 'spip_articles', 'id_article='.Sql::quote($id_article));
 *		$id_rubrique = $art['id_rubrique'];
 * 		</code>
 * 
 * @api
 * @uses Sql::select()
 * @uses Sql::fetch()
 * 
 * @param array|string $select
 * 		Liste des champs a recuperer (Select)
 * @param array|string $from
 * 		Tables a consulter (From)
 * @param array|string $where
 * 		Conditions a remplir (Where)
 * @param array|string $groupby
 * 		Critere de regroupement (Group by)
 * @param array|string $orderby
 * 		Tableau de classement (Order By)
 * @param string $limit
 * 		Critere de limite (Limit)
 * @param array $having
 * 		Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 * 		Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 * 		Peut avoir 3 valeurs : 
 * 		- true -> executer la requete.
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible.
 * 		- false -> ne pas l'executer mais la retourner.
 * 
 * @return array
 * 		Tableau de la premiere ligne de resultat de la selection
 * 		{@example
 * 			<code>array('id_rubrique' => 1, 'id_secteur' => 2)</code>
 * 		}
 *
**/
public static function fetsel($select = array(), $from = array(), $where = array(), $groupby = array(),
	$orderby = array(), $limit = '', $having = array(), $serveur='', $option=true)
{
	$q = self::select($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option===false) return $q;
	if (!$q) return array();
	$r = self::fetch($q, $serveur, $option);
	self::free($q, $serveur, $option);
	return $r;
}

/**
 * Retourne le tableau de toutes les lignes d'une selection
 * 
 * Retourne toutes les lignes de resultat d'une selection
 * comme si l'on appelait successivement sql_select() puis while(sql_fetch())
 * 
 * @example
 * 		<code>
 * 		$rubs = Sql::allfetsel('id_rubrique', 'spip_articles', 'id_secteur='.Sql::quote($id_secteur));
 *		// $rubs = array(array('id_rubrique'=>1), array('id_rubrique'=>3, ...)
 * 		</code>
 * 
 * @api
 * @uses Sql::select()
 * @uses Sql::fetch()
 * 
 * @param array|string $select
 * 		Liste des champs a recuperer (Select)
 * @param array|string $from
 * 		Tables a consulter (From)
 * @param array|string $where
 * 		Conditions a remplir (Where)
 * @param array|string $groupby
 * 		Critere de regroupement (Group by)
 * @param array|string $orderby
 * 		Tableau de classement (Order By)
 * @param string $limit
 * 		Critere de limite (Limit)
 * @param array $having
 * 		Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 * 		Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 * 		Peut avoir 3 valeurs : 
 * 		- true -> executer la requete.
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible.
 * 		- false -> ne pas l'executer mais la retourner.
 * 
 * @return array
 * 		Tableau de toutes les lignes de resultat de la selection
 * 		Chaque entree contient un tableau des elements demandees dans le SELECT.
 * 		{@example
 * 			<code>
 * 			array(
 * 				array('id_rubrique' => 1, 'id_secteur' => 2)
 * 				array('id_rubrique' => 4, 'id_secteur' => 2)
 * 				...
 * 			)
 * 			</code>
 * 		}
 *
**/
public static function allfetsel($select = array(), $from = array(), $where = array(), $groupby = array(),
	$orderby = array(), $limit = '', $having = array(), $serveur='', $option=true)
{
	$q = self::select($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option===false) return $q;
	return self::fetch_all($q, $serveur, $option);
}

/**
 * Retourne un unique champ d'une selection
 * 
 * Retourne dans la premiere ligne de resultat d'une selection
 * un unique champ demande
 * 
 * @example
 * 		<code>
 * 		$id_rubrique = Sql::getfetsel('id_rubrique', 'spip_articles', 'id_article='.Sql::quote($id_article));
 * 		</code>
 *
 * @api
 * @uses sql_fetsel()
 * 
 * @param array|string $select
 * 		Liste des champs a recuperer (Select)
 * @param array|string $from
 * 		Tables a consulter (From)
 * @param array|string $where
 * 		Conditions a remplir (Where)
 * @param array|string $groupby
 * 		Critere de regroupement (Group by)
 * @param array|string $orderby
 * 		Tableau de classement (Order By)
 * @param string $limit
 * 		Critere de limite (Limit)
 * @param array $having
 * 		Tableau des des post-conditions a remplir (Having)
 * @param string $serveur
 * 		Le serveur sollicite (pour retrouver la connexion)
 * @param bool|string $option
 * 		Peut avoir 3 valeurs : 
 * 		- true -> executer la requete.
 * 		- continue -> ne pas echouer en cas de serveur sql indisponible.
 * 		- false -> ne pas l'executer mais la retourner.
 * 
 * @return mixed
 * 		Contenu de l'unique valeur demandee du premier enregistrement retourne
 *
**/
public static function getfetsel($select, $from = array(), $where = array(), $groupby = array(), 
	$orderby = array(), $limit = '', $having = array(), $serveur='', $option=true)
{
	if (preg_match('/\s+as\s+(\w+)$/i', $select, $c)) $id = $c[1];
	elseif (!preg_match('/\W/', $select)) $id = $select;
	else {
		$id = 'n';
		$select .= ' AS n';
	}
	$r = self::fetsel($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option===false) return $r;
	if (!$r) return false;
	return $r[$id];
}

/**
 * Retourne le numero de version du serveur SQL 
 *
 * @api
 * @param string $serveur
 * 		Nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 2 valeurs : 
 * 		- true pour executer la requete.
 * 		- continue pour ne pas echouer en cas de serveur sql indisponible.
 * 
 * @return string
 * 		Numero de version du serveur SQL
**/
public static function version($serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;

	$r = self::$driver->version($serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
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
 * 		<code>
 * 		if (sql_preferer_transaction()) {
 * 			sql_demarrer_transaction();
 * 		}
 * 		</code>
 *
 * @api
 * @see sql_demarrer_transaction()
 * @see sql_terminer_transaction()
 *
 * @param string $serveur
 * 		Nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 2 valeurs : 
 * 		- true pour executer la requete.
 * 		- continue pour ne pas echouer en cas de serveur sql indisponible.
 *
 * @return bool
 * 		Le serveur SQL prefere t'il des transactions pour les insertions multiples ?
**/
public static function preferer_transaction($serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;

	$r = self::$driver->preferer_transaction($serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

/**
 * Demarre une transaction
 *
 * @api
 * @see sql_terminer_transaction() Pour cloturer la transaction
 * 
 * @param string $serveur
 * 		Nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 3 valeurs : 
 * 		- true pour executer la requete.
 * 		- continue pour ne pas echouer en cas de serveur sql indisponible.
 * 		- false pour obtenir le code de la requete
 * 
 * @return bool
 *      true si la transaction est demarree
 *      false en cas d'erreur
**/
public static function demarrer_transaction($serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;

	$r = self::$driver->demarrer_transaction($serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

/**
 * Termine une transaction
 *
 * @api
 * @see sql_demarrer_transaction() Pour demarrer une transaction
 * 
 * @param string $serveur
 * 		Nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 3 valeurs : 
 * 		- true pour executer la requete.
 * 		- continue pour ne pas echouer en cas de serveur sql indisponible.
 * 		- false pour obtenir le code de la requete
 * 
 * @return bool
 *      true si la transaction est demarree
 *      false en cas d'erreur
**/
public static function terminer_transaction($serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;

	$r = self::$driver->terminer_transaction($serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}


/**
 * Prepare une chaine hexadecimale
 * 
 * Prend une chaine sur l'aphabet hexa
 * et retourne sa representation numerique attendue par le serveur SQL.
 * Par exemple : FF ==> 0xFF en MySQL mais x'FF' en PG
 *
 * @api
 * @param string $val
 * 		Chaine hexadecimale
 * @param string $serveur
 * 		Nom du connecteur
 * @param bool|string $option
 * 		Peut avoir 2 valeurs : 
 * 		- true pour executer la demande.
 * 		- continue pour ne pas echouer en cas de serveur sql indisponible.
 * @return string
 * 		Retourne la valeur hexadecimale attendue par le serveur SQL
**/
public static function hex($val, $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;

	$r = self::$driver->hex($val, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

/**
 * Echapper du contenu
 * 
 * Echappe du contenu selon ce qu'attend le type de serveur SQL
 * et en fonction du type de contenu.
 * 
 * Cette fonction est automatiquement appelee par les fonctions sql_*q
 * tel que sql_instertq ou sql_updateq
 *
 * @api
 * @param string $val
 * 		Chaine a echapper
 * @param string $serveur
 * 		Nom du connecteur
 * @param string $type
 * 		Peut contenir une declaration de type de champ SQL
 * 		{@example <code>int NOT NULL</code>} qui sert alors aussi a calculer le type d'echappement
 * @return string
 * 		La chaine echappee
**/
public static function quote($val, $type='', $serveur='')
{
	if (!is_object(self::$driver)) return false;

	$r = self::$driver->quote($val, $type);
	if ($r===false) self::error($serveur);
	return $r;
}

public static function date_proche($champ, $interval, $unite)
{
	if (!is_object(self::$driver)) return false;
	
	$r = self::$driver->date_proche($champ, $interval, $unite);
	if ($r===false) self::error($serveur);
	return $r;
}

/**
 * Retourne une expression IN pour le gestionnaire de base de données
 *
 * Retourne un code à insérer dans une requête SQL pour récupérer
 * les éléments d'une colonne qui appartiennent à une liste donnée
 *
 * @example
 *     sql_in('id_rubrique', array(3,4,5))
 *     retourne approximativement «id_rubrique IN (3,4,5)» selon ce qu'attend
 *     le gestionnaire de base de donnée du connecteur en cours.
 *
 * @api
 * @param string $val
 *     Colonne SQL sur laquelle appliquer le test
 * @param string|array $valeurs
 *     Liste des valeurs possibles (séparés par des virgules si string)
 * @param string $not
 *     - '' sélectionne les éléments correspondant aux valeurs
 *     - 'NOT' inverse en sélectionnant les éléments ne correspondant pas aux valeurs
 * @param string $serveur
 *   Nom du connecteur
 * @param bool|string $option
 *   Peut avoir 2 valeurs : 
 *   - continue -> ne pas echouer en cas de serveur sql indisponible
 *   - true ou false -> retourne l'expression
 * @return string
 *     Expression de requête SQL
**/
public static function in($val, $valeurs, $not='', $serveur='', $option=true)
{
	if (!is_object(self::$driver)) return false;
	
	if (is_array($valeurs)) {
		$valeurs = join(',', array_map(array('Sql','quote'), array_unique($valeurs)));
	} elseif (isset($valeurs[0]) AND $valeurs[0]===',') 
		$valeurs = substr($valeurs,1);
	
	if (!strlen(trim($valeurs))) return ($not ? "0=0" : '0=1');
	
	$r = self::$driver->in($val, $valeurs, $not, $serveur, $option);
	if ($r===false) self::error($serveur);
	return $r;
}

// Penser a dire dans la description du serveur 
// s'il accepte les requetes imbriquees afin d'optimiser ca

// http://doc.spip.org/@sql_in_select
public static function in_select($in, $select, $from = array(), $where = array(),
	$groupby = array(), $orderby = array(), $limit = '', $having = array(), $serveur='')
{
	$liste = array(); 
	$res = self::select($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur); 
	while ($r = self::fetch($res)) {
		$liste[] = array_shift($r);
	}
	self::free($res);
	return self::in($in, $liste);
}

/**
 * Implementation securisee du saut en avant,
 * qui ne depend pas de la disponibilite de la fonction sql_seek
 * ne fait rien pour une valeur negative ou nulle de $saut
 * retourne la position apres le saut
 *
 * @see sql_seek()
 * 
 * @param resource $res
 * 		Ressource issue d'une selection sql_select
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
public static function skip($res, $pos, $saut, $count, $serveur='', $option=true)
{
	// pas de saut en arriere qu'on ne sait pas faire sans sql_seek
	if (($saut=intval($saut))<=0) return $pos;

	$seek = $pos + $saut;
	// si le saut fait depasser le maxi, on libere la resource
	// et on sort
	if ($seek>=$count) {
		self::free($res, $serveur, $option);
		return $count;
	}

	if (self::seek($res, $seek))
		$pos = $seek;
	else
		while ($pos<$seek AND self::fetch($res, $serveur, $option))
			$pos++;
	return $pos;
}

public static function test_int($type)
{
	if (!is_object(self::$driver)) return false;
	return self::$driver->test_int($type);
}

public static function test_date($type)
{
	if (!is_object(self::$driver)) return false;
	return self::$driver->test_int($type);
}

public static function cite($val, $type)
{
	if (!is_object(self::$driver)) return false;
	return self::$driver->cite($val, $type);
}

public static function select_as($args)
{
	if (!is_object(self::$driver)) return false;
	return self::$driver->select_as($args);
}

/**
 * Formate une date
 * 
 * Formater une date Y-m-d H:i:s sans passer par mktime
 * qui ne sait pas gerer les dates < 1970
 *
 * http://doc.spip.org/@format_mysql_date
 *
 * @param int $annee Annee
 * @param int $mois  Numero du mois
 * @param int $jour  Numero du jour dans le mois
 * @param int $h     Heures
 * @param int $m     Minutes
 * @param int $s     Secondes
 * @param string $serveur
 * 		Le serveur sollicite (pour retrouver la connexion)
 * @return string
 * 		La date formatee
 */
public static function format_date($annee=0, $mois=0, $jour=0, $h=0, $m=0, $s=0, $serveur='')
{
	if ($annee == 0) $mois = 0;
	if ($mois == 0) $jour = 0;

	return sprintf('%04u-%02u-%02u %02u:%02u:%02u', $annee, $mois, $jour, $h, $m, $s);
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
 * 		Nom de la table dont on souhait la description
 * @param string $serveur
 * 		Nom du connecteur
 * @return array|bool
 * 		Description de la table ou false si elle n'est pas trouvee ou declaree.
**/
public static function description_table($nom, $serveur='')
{
	global $tables_principales, $tables_auxiliaires;
	static $trouver_table;

	/* toujours utiliser trouver_table
	 qui renverra la description theorique
	 car sinon on va se comporter differement selon que la table est declaree
	 ou non
	 */
	if (!$trouver_table) $trouver_table = charger_fonction('trouver_table', 'base');
	if ($desc = $trouver_table($nom, $serveur))
		return $desc;

	// sauf a l'installation :
	include_spip('base/serial');
	if (isset($tables_principales[$nom]))
		return $tables_principales[$nom];

	include_spip('base/auxiliaires');
	if (isset($tables_auxiliaires[$nom]))
		return $tables_auxiliaires[$nom];

	return false;
}

}

?>
