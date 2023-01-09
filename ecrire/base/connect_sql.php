<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
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
	$index = $serveur ?: 0;
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
		$f = '';
		if ($serveur) {
			// serveur externe et nom de serveur bien ecrit ?
			if (defined('_DIR_CONNECT')
				and preg_match('/^[\w\.]*$/', $serveur)) {
				$f = _DIR_CONNECT . $serveur . '.php';
				if (!is_readable($f) and !$install) {
					// chercher une declaration de serveur dans le path
					// qui peut servir à des plugins à declarer des connexions à une base sqlite
					// Ex: sert aux boucles POUR et au plugin-dist dump pour se connecter sur le sqlite du dump
					$f = find_in_path("$serveur.php", 'connect/');
				}
			}
		}
		else {
			if (defined('_FILE_CONNECT') and _FILE_CONNECT) {
				// init du serveur principal
				$f = _FILE_CONNECT;
			}
			elseif ($install and defined('_FILE_CONNECT_TMP')) {
				// installation en cours
				$f = _FILE_CONNECT_TMP;
			}
		}

		unset($GLOBALS['db_ok']);
		unset($GLOBALS['spip_connect_version']);
		if ($f and is_readable($f)) {
			include($f);
			if (!isset($GLOBALS['db_ok'])) {
				spip_log("spip_connect: fichier de connexion '$f' OK mais echec connexion au serveur", _LOG_HS);
			}
		}
		else {
			spip_log("spip_connect: fichier de connexion '$f' non trouve, pas de connexion serveur", _LOG_HS);
		}
		if (!isset($GLOBALS['db_ok'])) {
			// fera mieux la prochaine fois
			if ($install) {
				return false;
			}
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
			return $GLOBALS['connexions'][$index][$version] = [];
		}
	}
	$GLOBALS['connexions'][$index][$version] = $GLOBALS[$jeu];
	if ($old) {
		return $GLOBALS['connexions'][$index];
	}

	$GLOBALS['connexions'][$index]['spip_connect_version'] = $GLOBALS['spip_connect_version'] ?? 0;

	// initialisation de l'alphabet utilise dans les connexions SQL
	// si l'installation l'a determine.
	// Celui du serveur principal l'impose aux serveurs secondaires
	// s'ils le connaissent

	if (!$serveur) {
		$charset = spip_connect_main($GLOBALS[$jeu], $GLOBALS['db_ok']['charset']);
		if (!$charset) {
			unset($GLOBALS['connexions'][$index]);
			spip_log('spip_connect: absence de charset', _LOG_AVERTISSEMENT);

			return false;
		}
	} else {
		if ($GLOBALS['db_ok']['charset']) {
			$charset = $GLOBALS['db_ok']['charset'];
		}
		// spip_meta n'existe pas toujours dans la base
		// C'est le cas d'un dump sqlite par exemple
		elseif (
			$GLOBALS['connexions'][$index]['spip_connect_version']
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
	$t = ($connexion['type'] ?? 'sql');
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
	if (
		$desc
		and $f = ($desc[$version][$ins] ?? '')
		and function_exists($f)
	) {
		return $f;
	}
	if ($continue) {
		return $desc;
	}
	if ($ins) {
		spip_log("Le serveur '$serveur' version $version n'a pas '$ins'", _LOG_ERREUR);
	}
	include_spip('inc/minipres');
	echo minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'), ['status' => 503]);
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
 * @return array|null Description de la connexion
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

	$f = '';
	// un fichier de identifiant par combinaison (type,host,port,db)
	// pour ne pas declarer tout indisponible d'un coup
	// si en cours d'installation ou si db=@test@ on ne pose rien
	// car c'est un test de connexion
	if (!defined('_ECRIRE_INSTALL') and $db !== '@test@') {
		$f = _DIR_TMP . $type . '.' . substr(md5($host . $port . $db), 0, 8) . '.out';
	} elseif ($db == '@test@') {
		$db = '';
	}

	if (
		$f
		and @file_exists($f)
		and (time() - @filemtime($f) < _CONNECT_RETRY_DELAY)
	) {
		spip_log("Echec : $f recent. Pas de tentative de connexion", _LOG_HS);

		return null;
	}

	if (!$prefixe) {
		$prefixe = $GLOBALS['table_prefix'] ?? $db;
	}
	$h = charger_fonction($type, 'req', true);
	if (!$h) {
		spip_log("les requetes $type ne sont pas fournies", _LOG_HS);

		return null;
	}
	if ($g = $h($host, $port, $login, $pass, $db, $prefixe)) {
		if (!is_array($auth)) {
			// compatibilite version 0.7 initiale
			$g['ldap'] = $auth;
			$auth = ['ldap' => $auth];
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
	return null;
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
	if (
		!$r = $f('valeur', 'spip_meta', "nom='charset_sql_connexion'")
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
 * @param int|float|string|array $a Valeur à échapper
 * @return string Valeur échappée.
 **/
function _q($a): string {
	if (is_numeric($a)) {
		return strval($a);
	} elseif (is_array($a)) {
		return join(',', array_map('_q', $a));
	} elseif (is_scalar($a)) {
		return ("'" . addslashes($a) . "'");
	} elseif ($a === null) {
		return "''";
	}
	throw new \RuntimeException('Can’t use _q with ' . gettype($a));
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
function query_echappe_textes($query, $uniqid = null) {
	static $codeEchappements = null;
	if (is_null($codeEchappements) or $uniqid) {
		if (is_null($uniqid)) {
			$uniqid = uniqid();
		}
		$uniqid = substr(md5($uniqid), 0, 4);
		$codeEchappements = ['\\\\' => "\x1@#{$uniqid}#@\x1", "\\'" => "\x2@#{$uniqid}#@\x2", '\\"' => "\x3@#{$uniqid}#@\x3", '%' => "\x4@#{$uniqid}#@\x4"];
	}
	if ($query === null) {
		return $codeEchappements;
	}

	// si la query contient deja des codes d'echappement on va s'emmeler les pinceaux et donc on ne touche a rien
	// ce n'est pas un cas legitime
	foreach ($codeEchappements as $codeEchappement) {
		if (strpos($query, (string) $codeEchappement) !== false) {
			return [$query, []];
		}
	}

	$query_echappees = str_replace(array_keys($codeEchappements), array_values($codeEchappements), $query);
	if (preg_match_all("/('[^']*')|(\"[^\"]*\")/S", $query_echappees, $textes)) {
		$textes = reset($textes);

		$parts = [];
		$currentpos = 0;
		$k = 0;
		while (count($textes)) {
			$part = array_shift($textes);
			$nextpos = strpos($query_echappees, $part, $currentpos);
			// si besoin recoller ensemble les doubles '' de sqlite (echappement des ')
			while (count($textes) and substr($part, -1) === "'") {
				$next = reset($textes);
				if (
					strpos($next, "'") === 0
					and strpos($query_echappees, $part . $next, $currentpos) === $nextpos
				) {
					$part .= array_shift($textes);
				}
				else {
					break;
				}
			}
			$k++;
			$parts[$k] = [
				'texte' => $part,
				'position' => $nextpos,
				'placeholder' => '%' . $k . '$s',
			];
			$currentpos = $nextpos + strlen($part);
		}

		// et on replace les parts une par une en commencant par la fin
		while ($k > 0) {
			$query_echappees = substr_replace($query_echappees, $parts[$k]['placeholder'], $parts[$k]['position'], strlen($parts[$k]['texte']));
			$k--;
		}
		$textes = array_column($parts, 'texte');
	} else {
		$textes = [];
	}

	// si il reste des quotes simples ou doubles, c'est qu'on s'est emmelle les pinceaux
	// dans le doute on ne touche a rien
	if (strpbrk($query_echappees, "'\"") !== false) {
		return [$query, []];
	}

	return [$query_echappees, $textes];
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
	// recuperer les codes echappements
	$codeEchappements = query_echappe_textes(null);

	if (!empty($textes)) {
		$query = sprintf($query, ...$textes);
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
 * @param string $query texte de la requête
 * @param string $serveur Nom du connecteur pour la base de données
 * @return bool|mixed
 *     - false si on ne peut pas exécuter la requête
 *     - indéfini sinon.
 **/
function spip_query($query, $serveur = '') {

	$f = spip_connect_sql($GLOBALS['spip_sql_version'], 'query', $serveur, true);

	return function_exists($f) ? $f($query, $serveur) : false;
}
