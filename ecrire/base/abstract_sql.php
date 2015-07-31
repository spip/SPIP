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
include_spip('base/connect_sql');



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
 * 		Instruction de l'API SQL demandee (insertq, update, select...)
 * @param string $serveur
 * 		Nom du connecteur ('' pour celui par defaut a l'installation de SPIP)
 * @param bool $continue
 * 		true pour ne pas generer d'erreur si le serveur SQL ne dispose pas de la fonction demandee
 * @return string|array
 * 		Nom de la fonction a appeler pour l'instruction demandee pour le type de serveur SQL correspondant au fichier de connexion.
 * 		Si l'instruction demandee n'existe pas, retourne la liste de toutes les instructions du serveur SQL avec $continue a true.
 * 
**/
function sql_serveur($ins_sql='', $serveur='', $continue=false) {
	static $sql_serveur = array();
	if (!isset($sql_serveur[$serveur][$ins_sql])){
		$f = spip_connect_sql(sql_ABSTRACT_VERSION, $ins_sql, $serveur, $continue);
		if (!is_string($f) OR !$f) return $f;
		$sql_serveur[$serveur][$ins_sql] = $f;
	}
	return $sql_serveur[$serveur][$ins_sql];
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
function sql_get_charset($charset, $serveur='', $option=true){
  // le nom http du charset differe parfois du nom SQL utf-8 ==> utf8 etc.
	$desc = sql_serveur('', $serveur, true,true);
	$desc = $desc[sql_ABSTRACT_VERSION];
	$c = $desc['charsets'][$charset];
	if ($c) {
		if (function_exists($f=@$desc['get_charset'])) 
			if ($f($c, $serveur, $option!==false)) return $c;
	}
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
function sql_set_charset($charset,$serveur='', $option=true){
	$f = sql_serveur('set_charset', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($charset, $serveur, $option!==false);
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
function sql_select ($select = array(), $from = array(), $where = array(),
	$groupby = array(), $orderby = array(), $limit = '', $having = array(),
	$serveur='', $option=true) {
	$f = sql_serveur('select', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;

	$debug = (defined('_VAR_MODE') AND _VAR_MODE == 'debug');
	if (($option !== false) AND !$debug) {
		$res = $f($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, is_array($option) ? true : $option);
	} else {
		$query = $f($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, false);
		if (!$option) return $query;
		// le debug, c'est pour ce qui a ete produit par le compilateur
		if (isset($GLOBALS['debug']['aucasou'])) {
			list($table, $id,) = $GLOBALS['debug']['aucasou'];
			$nom = $GLOBALS['debug_objets']['courant'] . $id;
			$GLOBALS['debug_objets']['requete'][$nom] = $query;
		}
		$res = $f($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, true);
	}

	// en cas d'erreur
	if (!is_string($res)) return $res;
	// denoncer l'erreur SQL dans sa version brute
	spip_sql_erreur($serveur);
	// idem dans sa version squelette (prefixe des tables non substitue)
	erreur_squelette(array(sql_errno($serveur), sql_error($serveur), $res), $option);
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
function sql_get_select($select = array(), $from = array(), $where = array(),
	$groupby = array(), $orderby = array(), $limit = '', $having = array(),
	$serveur='') {
	return sql_select ($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, false);
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
function sql_countsel($from = array(), $where = array(),
		      $groupby = array(), $having = array(),
	$serveur='', $option=true) {
	$f = sql_serveur('countsel', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($from, $where, $groupby, $having, $serveur, $option!==false);
	if ($r===false) spip_sql_erreur($serveur);
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
function sql_alter($q, $serveur='', $option=true) {
	$f = sql_serveur('alter', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($q, $serveur, $option!==false);
	if ($r===false) spip_sql_erreur($serveur);
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
function sql_fetch($res, $serveur='', $option=true) {
	$f = sql_serveur('fetch', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($res, NULL, $serveur, $option!==false);
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
function sql_fetch_all($res, $serveur='', $option=true){
	$rows = array();
	if (!$res) return $rows;
	$f = sql_serveur('fetch', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return array();
	while ($r = $f($res, NULL, $serveur, $option!==false))
		$rows[] = $r;
	sql_free($res, $serveur);
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
function sql_seek($res, $row_number, $serveur='', $option=true) {
	$f = sql_serveur('seek', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($res, $row_number, $serveur, $option!==false);
	if ($r===false) spip_sql_erreur($serveur);
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
function sql_listdbs($serveur='', $option=true) {
	$f = sql_serveur('listdbs', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($serveur);
	if ($r===false) spip_sql_erreur($serveur);
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
function sql_selectdb($nom, $serveur='', $option=true)
{
	$f = sql_serveur('selectdb', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($nom, $serveur, $option!==false);
	if ($r===false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_count
function sql_count($res, $serveur='', $option=true)
{
	$f = sql_serveur('count', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($res, $serveur, $option!==false);
	if ($r===false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_free
function sql_free($res, $serveur='', $option=true)
{
	$f = sql_serveur('free', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($res);
}

// Cette fonction ne garantit pas une portabilite totale
//  ===> lui preferer la suivante.
// Elle est fournie pour permettre l'actualisation de vieux codes 
// par un Sed brutal qui peut donner des resultats provisoirement acceptables
// http://doc.spip.org/@sql_insert
function sql_insert($table, $noms, $valeurs, $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('insert', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $noms, $valeurs, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_insertq
function sql_insertq($table, $couples=array(), $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('insertq', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $couples, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_insertq_multi
function sql_insertq_multi($table, $couples=array(), $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('insertq_multi', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $couples, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_update
function sql_update($table, $exp, $where='', $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('update', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $exp, $where, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// Update est presque toujours appelee sur des constantes ou des dates
// Cette fonction est donc plus utile que la precedente,d'autant qu'elle
// permet de gerer les differences de representation des constantes.
// http://doc.spip.org/@sql_updateq
function sql_updateq($table, $exp, $where='', $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('updateq', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $exp, $where, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_delete
function sql_delete($table, $where='', $serveur='', $option=true)
{
	$f = sql_serveur('delete', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $where, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_replace
function sql_replace($table, $couples, $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('replace', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $couples, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}


// http://doc.spip.org/@sql_replace_multi
function sql_replace_multi($table, $tab_couples, $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('replace_multi', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $tab_couples, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_drop_table
function sql_drop_table($table, $exist='', $serveur='', $option=true)
{
	$f = sql_serveur('drop_table', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $exist, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// supprimer une vue sql
// http://doc.spip.org/@sql_drop_view
function sql_drop_view($table, $exist='', $serveur='', $option=true)
{
	$f = sql_serveur('drop_view', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $exist, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
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
function sql_showbase($spip=NULL, $serveur='', $option=true)
{
	$f = sql_serveur('showbase', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;

	// la globale n'est remplie qu'apres l'appel de sql_serveur.
	if ($spip == NULL){
		$connexion = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$spip = $connexion['prefixe'] . '\_%';
	}

	return $f($spip, $serveur, $option!==false);
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
function sql_alltable($spip=NULL, $serveur='', $option=true)
{
	$q = sql_showbase($spip, $serveur, $option);
	$r = array();
	if ($q) while ($t = sql_fetch($q, $serveur)) { $r[] = array_shift($t);}
	return $r;
}

// http://doc.spip.org/@sql_showtable
function sql_showtable($table, $table_spip = false, $serveur='', $option=true)
{
	$f = sql_serveur('showtable', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;

	// la globale n'est remplie qu'apres l'appel de sql_serveur.
	if ($table_spip){
		$connexion = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$prefixe = $connexion['prefixe'];
		$vraie_table = preg_replace('/^spip/', $prefixe, $table);
	} else $vraie_table = $table;

	$f = $f($vraie_table, $serveur, $option!==false);
	if (!$f) return array();
	if (isset($GLOBALS['tables_principales'][$table]['join']))
		$f['join'] = $GLOBALS['tables_principales'][$table]['join'];
	elseif (isset($GLOBALS['tables_auxiliaires'][$table]['join']))
		$f['join'] = $GLOBALS['tables_auxiliaires'][$table]['join'];
	return $f;
}

// http://doc.spip.org/@sql_create
function sql_create($nom, $champs, $cles=array(), $autoinc=false, $temporary=false, $serveur='', $option=true) {
	$f = sql_serveur('create', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($nom, $champs, $cles, $autoinc, $temporary, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

function sql_create_base($nom, $serveur='', $option=true)
{
	$f = sql_serveur('create_base', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($nom, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// Fonction pour creer une vue 
// nom : nom de la vue,
// select_query : une requete select, idealement cree avec $req = sql_select()
// (en mettant $option du sql_select a false pour recuperer la requete)
// http://doc.spip.org/@sql_create_view
function sql_create_view($nom, $select_query, $serveur='', $option=true) {
	$f = sql_serveur('create_view', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($nom, $select_query, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_multi
function sql_multi($sel, $lang, $serveur='', $option=true)
{
	$f = sql_serveur('multi', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
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
function sql_error($serveur='') {
	$f = sql_serveur('error', $serveur, 'continue');
	if (!is_string($f) OR !$f) return false;
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
function sql_errno($serveur='') {
	$f = sql_serveur('errno', $serveur, 'continue');
	if (!is_string($f) OR !$f) return false;
	return $f($serveur);
}

// http://doc.spip.org/@sql_explain
function sql_explain($q, $serveur='', $option=true) {
	$f = sql_serveur('explain', $serveur,  'continue');
	if (!is_string($f) OR !$f) return false;
	$r = $f($q, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_optimize
function sql_optimize($table, $serveur='', $option=true) {
	$f = sql_serveur('optimize', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_repair
function sql_repair($table, $serveur='', $option=true) {
	$f = sql_serveur('repair', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// Fonction la plus generale ... et la moins portable
// A n'utiliser qu'en derniere extremite

// http://doc.spip.org/@sql_query
function sql_query($ins, $serveur='', $option=true) {
	$f = sql_serveur('query', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($ins, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
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
 * 		$art = sql_fetsel(array('id_rubrique','id_secteur'), 'spip_articles', 'id_article='.sql_quote($id_article));
 *		$id_rubrique = $art['id_rubrique'];
 * 		</code>
 * 
 * @api
 * @uses sql_select()
 * @uses sql_fetch()
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
function sql_fetsel($select = array(), $from = array(), $where = array(),
	$groupby = array(), $orderby = array(), $limit = '',
	$having = array(), $serveur='', $option=true) {
	$q = sql_select($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option===false) return $q;
	if (!$q) return array();
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
 * 		<code>
 * 		$rubs = sql_allfetsel('id_rubrique', 'spip_articles', 'id_secteur='.sql_quote($id_secteur));
 *		// $rubs = array(array('id_rubrique'=>1), array('id_rubrique'=>3, ...)
 * 		</code>
 * 
 * @api
 * @uses sql_select()
 * @uses sql_fetch()
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
function sql_allfetsel($select = array(), $from = array(), $where = array(),
	$groupby = array(), $orderby = array(), $limit = '',
	$having = array(), $serveur='', $option=true) {
	$q = sql_select($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option===false) return $q;
	return sql_fetch_all($q, $serveur, $option);
}


/**
 * Retourne un unique champ d'une selection
 * 
 * Retourne dans la premiere ligne de resultat d'une selection
 * un unique champ demande
 * 
 * @example
 * 		<code>
 * 		$id_rubrique = sql_getfetsel('id_rubrique', 'spip_articles', 'id_article='.sql_quote($id_article));
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
function sql_getfetsel($select, $from = array(), $where = array(), $groupby = array(), 
	$orderby = array(), $limit = '', $having = array(), $serveur='', $option=true) {
	if (preg_match('/\s+as\s+(\w+)$/i', $select, $c)) $id = $c[1];
	elseif (!preg_match('/\W/', $select)) $id = $select;
	else {$id = 'n'; $select .= ' AS n';}
	$r = sql_fetsel($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option===false) return $r;
	if (!$r) return NULL;
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
function sql_version($serveur='', $option=true) {
	$row = sql_fetsel("version() AS n", '','','','','','',$serveur);
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
function sql_preferer_transaction($serveur='', $option=true) {
	$f = sql_serveur('preferer_transaction', $serveur,  'continue');
	if (!is_string($f) OR !$f) return false;
	$r = $f($serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
};

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
function sql_demarrer_transaction($serveur='', $option=true) {
	$f = sql_serveur('demarrer_transaction', $serveur,  'continue');
	if (!is_string($f) OR !$f) return false;
	$r = $f($serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
};

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
function sql_terminer_transaction($serveur='', $option=true) {
	$f = sql_serveur('terminer_transaction', $serveur,  'continue');
	if (!is_string($f) OR !$f) return false;
	$r = $f($serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
};


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
function sql_hex($val, $serveur='', $option=true)
{
	$f = sql_serveur('hex', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($val);
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
function sql_quote($val, $serveur='', $type='')
{
	$f = sql_serveur('quote', $serveur, true);
	if (!is_string($f) OR !$f) $f = '_q';
	return $f($val, $type);
}

function sql_date_proche($champ, $interval, $unite, $serveur='', $option=true)
{
	$f = sql_serveur('date_proche', $serveur, true);
	if (!is_string($f) OR !$f) return false;
	return $f($champ, $interval, $unite);
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
function sql_in($val, $valeurs, $not='', $serveur='', $option=true) {
	if (is_array($valeurs)) {
		$f = sql_serveur('quote', $serveur, true);
		if (!is_string($f) OR !$f) return false;
		$valeurs = join(',', array_map($f, array_unique($valeurs)));
	} elseif (isset($valeurs[0]) AND $valeurs[0]===',') $valeurs = substr($valeurs,1);
	if (!strlen(trim($valeurs))) return ($not ? "0=0" : '0=1');

	$f = sql_serveur('in', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($val, $valeurs, $not, $serveur, $option!==false);
}

// Penser a dire dans la description du serveur 
// s'il accepte les requetes imbriquees afin d'optimiser ca

// http://doc.spip.org/@sql_in_select
function sql_in_select($in, $select, $from = array(), $where = array(),
		    $groupby = array(), $orderby = array(), $limit = '', $having = array(), $serveur='')
{
	$liste = array(); 
	$res = sql_select($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur); 
	while ($r = sql_fetch($res)) {$liste[] = array_shift($r);}
	sql_free($res);
	return sql_in($in, $liste);
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
function sql_skip($res, $pos, $saut, $count, $serveur='', $option=true){
	// pas de saut en arriere qu'on ne sait pas faire sans sql_seek
	if (($saut=intval($saut))<=0) return $pos;

	$seek = $pos + $saut;
	// si le saut fait depasser le maxi, on libere la resource
	// et on sort
	if ($seek>=$count) {sql_free($res, $serveur, $option); return $count;}

	if (sql_seek($res, $seek))
		$pos = $seek;
	else
		while ($pos<$seek AND sql_fetch($res, $serveur, $option))
			$pos++;
	return $pos;
}

// http://doc.spip.org/@sql_test_int
function sql_test_int($type, $serveur='', $option=true)
{
  return preg_match('/^(TINYINT|SMALLINT|MEDIUMINT|INT|INTEGER|BIGINT)/i',trim($type));
}

// http://doc.spip.org/@sql_test_date
function sql_test_date($type, $serveur='', $option=true)
{
  return preg_match('/^(DATE|DATETIME|TIMESTAMP|TIME)/i',trim($type));
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
function sql_format_date($annee=0, $mois=0, $jour=0, $h=0, $m=0, $s=0, $serveur=''){
	$annee = sprintf("%04s",$annee);
	$mois = sprintf("%02s",$mois);

	if ($annee == "0000") $mois = 0;
	if ($mois == "00") $jour = 0;

	return sprintf("%04u",$annee) . '-' . sprintf("%02u",$mois) . '-'
		. sprintf("%02u",$jour) . ' ' . sprintf("%02u",$h) . ':'
		. sprintf("%02u",$m) . ':' . sprintf("%02u",$s);
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
function description_table($nom, $serveur=''){

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


?>
