<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Utilitaires indispensables autour des serveurs SQL
 *
 * @package SPIP\Core\SQL
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
require_once _ROOT_RESTREINT . 'base/objets.php';


/**
 * Connexion à un serveur de base de données
 *
 * On charge le fichier `config/$serveur` (`$serveur='connect'` pour le principal)
 * qui est censé initaliser la connexion en appelant la fonction `spip_connect_db`
 * laquelle met dans la globale `db_ok` la description de la connexion.
 *
 * On la mémorise dans un tableau pour permettre plusieurs serveurs.
 *
 * À l'installation, il faut simuler l'existence de ce fichier.
 *
 * @uses spip_connect_main()
 *
 * @param string $serveur Nom du connecteur
 * @param string $version Version de l'API SQL
 * @return bool|array
 *     - false si la connexion a échouée,
 *     - tableau décrivant la connexion sinon
 **/
function spip_connect($serveur = '', $version = '') {

	$serveur = !is_string($serveur) ? '' : strtolower($serveur);
	$index = $serveur ? $serveur : 0;
	if (!$version) {
		$version = $GLOBALS['spip_sql_version'];
	}
	if (isset($GLOBALS['connexions'][$index][$version])) {
		return $GLOBALS['connexions'][$index];
	}

	include_spip('base/abstract_sql');
	$install = (_request('exec') == 'install');

	// Premiere connexion ?
	if (!($old = isset($GLOBALS['connexions'][$index]))) {
		$f = (!preg_match('/^[\w\.]*$/', $serveur))
			? '' // nom de serveur mal ecrit
			: ($serveur ?
				(_DIR_CONNECT . $serveur . '.php') // serveur externe
				: (_FILE_CONNECT ? _FILE_CONNECT // serveur principal ok
					: ($install ? _FILE_CONNECT_TMP // init du serveur principal
						: ''))); // installation pas faite

		unset($GLOBALS['db_ok']);
		unset($GLOBALS['spip_connect_version']);
		if ($f) {
			if (is_readable($f)) {
				include($f);
			} elseif ($serveur and !$install) {
				// chercher une declaration de serveur dans le path
				// qui pourra un jour servir a declarer des bases sqlite
				// par des plugins. Et sert aussi aux boucles POUR.
				find_in_path("$serveur.php", 'connect/', true);
			}
		}
		if (!isset($GLOBALS['db_ok'])) {
			// fera mieux la prochaine fois
			if ($install) {
				return false;
			}
			if ($f and is_readable($f)) {
				spip_log("spip_connect: fichier de connexion '$f' OK.", _LOG_INFO_IMPORTANTE);
			} else {
				spip_log("spip_connect: fichier de connexion '$f' non trouve", _LOG_INFO_IMPORTANTE);
			}
			spip_log("spip_connect: echec connexion ou serveur $index mal defini dans '$f'.", _LOG_HS);

			// ne plus reessayer si ce n'est pas l'install
			return $GLOBALS['connexions'][$index] = false;
		}
		$GLOBALS['connexions'][$index] = $GLOBALS['db_ok'];
	}
	// si la connexion a deja ete tentee mais a echoue, le dire!
	if (!$GLOBALS['connexions'][$index]) {
		return false;
	}

	// la connexion a reussi ou etait deja faite.
	// chargement de la version du jeu de fonctions
	// si pas dans le fichier par defaut
	$type = $GLOBALS['db_ok']['type'];
	$jeu = 'spip_' . $type . '_functions_' . $version;
	if (!isset($GLOBALS[$jeu])) {
		if (!find_in_path($type . '_' . $version . '.php', 'req/', true)) {
			spip_log("spip_connect: serveur $index version '$version' non defini pour '$type'", _LOG_HS);

			// ne plus reessayer
			return $GLOBALS['connexions'][$index][$version] = array();
		}
	}
	$GLOBALS['connexions'][$index][$version] = $GLOBALS[$jeu];
	if ($old) {
		return $GLOBALS['connexions'][$index];
	}

	$GLOBALS['connexions'][$index]['spip_connect_version'] = isset($GLOBALS['spip_connect_version']) ? $GLOBALS['spip_connect_version'] : 0;

	// initialisation de l'alphabet utilise dans les connexions SQL
	// si l'installation l'a determine.
	// Celui du serveur principal l'impose aux serveurs secondaires
	// s'ils le connaissent

	if (!$serveur) {
		$charset = spip_connect_main($GLOBALS[$jeu], $GLOBALS['db_ok']['charset']);
		if (!$charset) {
			unset($GLOBALS['connexions'][$index]);
			spip_log("spip_connect: absence de charset", _LOG_AVERTISSEMENT);

			return false;
		}
	} else {
		if ($GLOBALS['db_ok']['charset']) {
			$charset = $GLOBALS['db_ok']['charset'];
		}
		// spip_meta n'existe pas toujours dans la base
		// C'est le cas d'un dump sqlite par exemple 
		elseif ($GLOBALS['connexions'][$index]['spip_connect_version']
			and sql_showtable('spip_meta', true, $serveur)
			and $r = sql_getfetsel('valeur', 'spip_meta', "nom='charset_sql_connexion'", '', '', '', '', $serveur)
		) {
			$charset = $r;
		} else {
			$charset = -1;
		}
	}
	if ($charset != -1) {
		$f = $GLOBALS[$jeu]['set_charset'];
		if (function_exists($f)) {
			$f($charset, $serveur);
		}
	}

	return $GLOBALS['connexions'][$index];
}

/**
 * Log la dernière erreur SQL présente sur la connexion indiquée
 *
 * @param string $serveur Nom du connecteur de bdd utilisé
 **/
function spip_sql_erreur($serveur = '') {
	$connexion = spip_connect($serveur);
	$e = sql_errno($serveur);
	$t = (isset($connexion['type']) ? $connexion['type'] : 'sql');
	$m = "Erreur $e de $t: " . sql_error($serveur) . "\nin " . sql_error_backtrace() . "\n" . trim($connexion['last']);
	$f = $t . $serveur;
	spip_log($m, $f . '.' . _LOG_ERREUR);
}

/**
 * Retourne le nom de la fonction adaptée de l'API SQL en fonction du type de serveur
 *
 * Cette fonction ne doit être appelée qu'à travers la fonction sql_serveur
 * définie dans base/abstract_sql
 *
 * Elle existe en tant que gestionnaire de versions,
 * connue seulement des convertisseurs automatiques
 *
 * @param string $version Numéro de version de l'API SQL
 * @param string $ins Instruction de l'API souhaitée, tel que 'allfetsel'
 * @param string $serveur Nom du connecteur
 * @param bool $continue true pour continuer même si le serveur SQL ou l'instruction est indisponible
 * @return array|bool|string
 *     - string : nom de la fonction à utiliser,
 *     - false : si la connexion a échouée
 *     - array : description de la connexion, si l'instruction sql est indisponible pour cette connexion
 **/
function spip_connect_sql($version, $ins = '', $serveur = '', $continue = false) {
	$desc = spip_connect($serveur, $version);
	if (function_exists($f = @$desc[$version][$ins])) {
		return $f;
	}
	if ($continue) {
		return $desc;
	}
	if ($ins) {
		spip_log("Le serveur '$serveur' version $version n'a pas '$ins'", _LOG_ERREUR);
	}
	include_spip('inc/minipres');
	echo minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'), array('status' => 503));
	exit;
}

/**
 * Fonction appelée par le fichier connecteur de base de données
 * crée dans `config/` à l'installation.
 *
 * Il contient un appel direct à cette fonction avec comme arguments
 * les identifants de connexion.
 *
 * Si la connexion reussit, la globale `db_ok` mémorise sa description.
 * C'est un tableau également retourné en valeur, pour les appels
 * lors de l'installation.
 *
 * @param string $host Adresse du serveur de base de données
 * @param string $port Port utilisé pour la connexion
 * @param string $login Identifiant de connexion à la base de données
 * @param string $pass Mot de passe pour cet identifiant
 * @param string $db Nom de la base de données à utiliser
 * @param string $type Type de base de données tel que 'mysql', 'sqlite3' (cf ecrire/req/)
 * @param string $prefixe Préfixe des tables SPIP
 * @param string $auth Type d'authentification (cas si 'ldap')
 * @param string $charset Charset de la connexion SQL (optionnel)
 * @return array          Description de la connexion
 */
function spip_connect_db(
	$host,
	$port,
	$login,
	$pass,
	$db = '',
	$type = 'mysql',
	$prefixe = '',
	$auth = '',
	$charset = ''
) {
	// temps avant nouvelle tentative de connexion
	// suite a une connection echouee
	if (!defined('_CONNECT_RETRY_DELAY')) {
		define('_CONNECT_RETRY_DELAY', 30);
	}

	$f = "";
	// un fichier de identifiant par combinaison (type,host,port,db)
	// pour ne pas declarer tout indisponible d'un coup
	// si en cours d'installation ou si db=@test@ on ne pose rien
	// car c'est un test de connexion
	if (!defined('_ECRIRE_INSTALL') and $db !== "@test@") {
		$f = _DIR_TMP . $type . '.' . substr(md5($host . $port . $db), 0, 8) . '.out';
	} elseif ($db == '@test@') {
		$db = '';
	}

	if ($f
		and @file_exists($f)
		and (time() - @filemtime($f) < _CONNECT_RETRY_DELAY)
	) {
		spip_log("Echec : $f recent. Pas de tentative de connexion", _LOG_HS);

		return;
	}

	if (!$prefixe) {
		$prefixe = isset($GLOBALS['table_prefix'])
			? $GLOBALS['table_prefix'] : $db;
	}
	$h = charger_fonction($type, 'req', true);
	if (!$h) {
		spip_log("les requetes $type ne sont pas fournies", _LOG_HS);

		return;
	}
	if ($g = $h($host, $port, $login, $pass, $db, $prefixe)) {

		if (!is_array($auth)) {
			// compatibilite version 0.7 initiale
			$g['ldap'] = $auth;
			$auth = array('ldap' => $auth);
		}
		$g['authentification'] = $auth;
		$g['type'] = $type;
		$g['charset'] = $charset;

		return $GLOBALS['db_ok'] = $g;
	}
	// En cas d'indisponibilite du serveur, eviter de le bombarder
	if ($f) {
		@touch($f);
		spip_log("Echec connexion serveur $type : host[$host] port[$port] login[$login] base[$db]", $type . '.' . _LOG_HS);
	}
}


/**
 * Première connexion au serveur principal de base de données
 *
 * Retourner le charset donnée par la table principale
 * mais vérifier que le fichier de connexion n'est pas trop vieux
 *
 * @note
 *   Version courante = 0.8
 *
 *   - La version 0.8 indique un charset de connexion comme 9e arg
 *   - La version 0.7 indique un serveur d'authentification comme 8e arg
 *   - La version 0.6 indique le prefixe comme 7e arg
 *   - La version 0.5 indique le serveur comme 6e arg
 *
 *   La version 0.0 (non numerotée) doit être refaite par un admin.
 *   Les autres fonctionnent toujours, même si :
 *
 *   - la version 0.1 est moins performante que la 0.2
 *   - la 0.2 fait un include_ecrire('inc_db_mysql.php3').
 *
 * @param array $connexion Description de la connexion
 * @param string $charset_sql_connexion charset de connexion fourni dans l'appal a spip_connect_db
 * @return string|bool|int
 *     - false si pas de charset connu pour la connexion
 *     - -1 charset non renseigné
 *     - nom du charset sinon
 **/
function spip_connect_main($connexion, $charset_sql_connexion = '') {
	if ($GLOBALS['spip_connect_version'] < 0.1 and _DIR_RESTREINT) {
		include_spip('inc/headers');
		redirige_url_ecrire('upgrade', 'reinstall=oui');
	}

	if (!($f = $connexion['select'])) {
		return false;
	}
	// si le charset est fourni, l'utiliser
	if ($charset_sql_connexion) {
		return $charset_sql_connexion;
	}
	// sinon on regarde la table spip_meta
	// en cas d'erreur select retourne la requette (is_string=true donc)
	if (!$r = $f('valeur', 'spip_meta', "nom='charset_sql_connexion'")
		or is_string($r)
	) {
		return false;
	}
	if (!($f = $connexion['fetch'])) {
		return false;
	}
	$r = $f($r);

	return (isset($r['valeur']) && $r['valeur']) ? $r['valeur'] : -1;
}

/**
 * Connection à LDAP
 *
 * Fonction présente pour compatibilité
 *
 * @deprecated 3.1
 * @see Utiliser l'authentification LDAP de auth/ldap
 * @uses auth_ldap_connect()
 *
 * @param string $serveur Nom du connecteur
 * @return array
 */
function spip_connect_ldap($serveur = '') {
	include_spip('auth/ldap');

	return auth_ldap_connect($serveur);
}

/**
 * Échappement d'une valeur sous forme de chaîne PHP
 *
 * Échappe une valeur (num, string, array) pour en faire une chaîne pour PHP.
 * Un `array(1,'a',"a'")` renvoie la chaine `"'1','a','a\''"`
 *
 * @note
 *   L'usage comme échappement SQL est déprécié, à remplacer par sql_quote().
 *
 * @param num|string|array $a Valeur à échapper
 * @return string Valeur échappée.
 **/
function _q($a) {
	return (is_numeric($a)) ? strval($a) :
		(!is_array($a) ? ("'" . addslashes($a) . "'")
			: join(",", array_map('_q', $a)));
}


/**
 * Récupérer le nom de la table de jointure `xxxx` sur l'objet `yyyy`
 *
 * @deprecated
 *     Utiliser l'API editer_liens ou les tables de liaisons spip_xx_liens
 *     ou spip_yy_liens selon.
 *
 * @param string $x Table de destination
 * @param string $y Objet source
 * @return array|string
 *     - array : Description de la table de jointure si connue
 *     - chaîne vide si non trouvé.
 **/
function table_jointure($x, $y) {
	$trouver_table = charger_fonction('trouver_table', 'base');
	$xdesc = $trouver_table(table_objet($x));
	$ydesc = $trouver_table(table_objet($y));
	$ix = @$xdesc['key']["PRIMARY KEY"];
	$iy = @$ydesc['key']["PRIMARY KEY"];
	if ($table = $ydesc['tables_jointures'][$ix]) {
		return $table;
	}
	if ($table = $xdesc['tables_jointures'][$iy]) {
		return $table;
	}

	return '';
}

/**
 * Echapper les textes entre ' ' ou " " d'une requête SQL
 * avant son pre-traitement
 *
 * On renvoi la query sans textes et les textes séparés, dans
 * leur ordre d'apparition dans la query
 *
 * @see query_reinjecte_textes()
 *
 * @param string $query
 * @return array
 */
function query_echappe_textes($query) {
	static $codeEchappements = array("''" => "\x1@##@\x1", "\'" => "\x2@##@\x2", "\\\"" => "\x3@##@\x3");
	$query = str_replace(array_keys($codeEchappements), array_values($codeEchappements), $query);
	if (preg_match_all("/((['])[^']*(\\2))|(([\"])[^\"]*(\\5))/S", $query, $textes)) {
		$textes = reset($textes); // indice 0 du match
		switch (count($textes)) {
			case 0:
				$replace = array();
				break;
			case 1:
				$replace = array('%1$s');
				break;
			case 2:
				$replace = array('%1$s', '%2$s');
				break;
			case 3:
				$replace = array('%1$s', '%2$s', '%3$s');
				break;
			case 4:
				$replace = array('%1$s', '%2$s', '%3$s', '%4$s');
				break;
			case 5:
				$replace = array('%1$s', '%2$s', '%3$s', '%4$s', '%5$s');
				break;
			default:
				$replace = range(1, count($textes));
				$replace = '%' . implode('$s,%', $replace) . '$s';
				$replace = explode(',', $replace);
				break;
		}
		$query = str_replace($textes, $replace, $query);
	} else {
		$textes = array();
	}

	return array($query, $textes);
}

/**
 * Réinjecter les textes d'une requete SQL à leur place initiale,
 * après traitement de la requête
 *
 * @see query_echappe_textes()
 *
 * @param string $query
 * @param array $textes
 * @return string
 */
function query_reinjecte_textes($query, $textes) {
	static $codeEchappements = array("''" => "\x1@##@\x1", "\'" => "\x2@##@\x2", "\\\"" => "\x3@##@\x3");
	# debug de la substitution
	#if (($c1=substr_count($query,"%"))!=($c2=count($textes))){
	#	spip_log("$c1 ::". $query,"tradquery"._LOG_ERREUR);
	#	spip_log("$c2 ::". var_export($textes,1),"tradquery"._LOG_ERREUR);
	#	spip_log("ini ::". $qi,"tradquery"._LOG_ERREUR);
	#}
	switch (count($textes)) {
		case 0:
			break;
		case 1:
			$query = sprintf($query, $textes[0]);
			break;
		case 2:
			$query = sprintf($query, $textes[0], $textes[1]);
			break;
		case 3:
			$query = sprintf($query, $textes[0], $textes[1], $textes[2]);
			break;
		case 4:
			$query = sprintf($query, $textes[0], $textes[1], $textes[2], $textes[3]);
			break;
		case 5:
			$query = sprintf($query, $textes[0], $textes[1], $textes[2], $textes[3], $textes[4]);
			break;
		default:
			array_unshift($textes, $query);
			$query = call_user_func_array('sprintf', $textes);
			break;
	}

	$query = str_replace(array_values($codeEchappements), array_keys($codeEchappements), $query);

	return $query;
}


/**
 * Exécute une requête sur le serveur SQL
 *
 * @note Ne génère pas d’erreur fatale si la connexion à la BDD n’existe pas
 * @deprecated 3.1 Pour compatibilité.
 * @see sql_query() ou l'API `sql_*`.
 *
 * @param string $query Texte de la requête
 * @param string $serveur Nom du connecteur pour la base de données
 * @return bool|mixed
 *     - false si on ne peut pas exécuter la requête
 *     - indéfini sinon.
 **/
function spip_query($query, $serveur = '') {

	$f = spip_connect_sql($GLOBALS['spip_sql_version'], 'query', $serveur, true);

	return function_exists($f) ? $f($query, $serveur) : false;
}
