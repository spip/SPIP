<?php

/*
 * ecran_securite.php
 * ------------------
 */

define('_ECRAN_SECURITE', '1.5.0'); // 2023-02-27

/*
 * Documentation : https://www.spip.net/fr_article4200.html
 */

/*
 * Test utilisateur
 */
if (isset($_GET['test_ecran_securite'])) {
	$ecran_securite_raison = 'test ' . _ECRAN_SECURITE;
}

if (file_exists($f = __DIR__ . DIRECTORY_SEPARATOR  . 'ecran_securite_options.php')) {
	include ($f);
}

/*
 * Monitoring
 * var_isbot=0 peut etre utilise par un bot de monitoring pour surveiller la disponibilite d'un site vu par les users
 * var_isbot=1 peut etre utilise pour monitorer la disponibilite pour les bots (sujets a 503 de delestage si
 * le load depasse ECRAN_SECURITE_LOAD)
 */
if (!defined('_IS_BOT') and isset($_GET['var_isbot'])) {
	define('_IS_BOT', $_GET['var_isbot'] ? true : false);
}

/*
 * Détecteur de robot d'indexation
 */
if (!defined('_IS_BOT')) {
	define(
		'_IS_BOT',
		isset($_SERVER['HTTP_USER_AGENT'])
			and preg_match(
				','
					. implode('|', array(
						// mots generiques
						'bot',
						'slurp',
						'crawler',
						'crwlr',
						'java',
						'monitoring',
						'spider',
						'webvac',
						'yandex',
						'MSIE 6\.0', // botnet 99,9% du temps
						// UA plus cibles
						'200please',
						'80legs',
						'a6-indexer',
						'aboundex',
						'accoona',
						'acrylicapps',
						'addthis',
						'adressendeutschland',
						'alexa',
						'altavista',
						'analyticsseo',
						'antennapod',
						'arachnys',
						'archive',
						'argclrint',
						'aspseek',
						'baidu',
						'begunadvertising',
						'bing',
						'bloglines',
						'buck',
						'browsershots',
						'bubing',
						'butterfly',
						'changedetection',
						'charlotte',
						'chilkat',
						'china',
						'coccoc',
						'crowsnest',
						'dataminr',
						'daumoa',
						'dlvr\.it',
						'dlweb',
						'drupal',
						'ec2linkfinder',
						'eset\.com',
						'estyle',
						'exalead',
						'ezooms',
						'facebookexternalhit',
						'facebookplatform',
						'fairshare',
						'feedfetcher',
						'feedfetcher-google',
						'feedly',
						'fetch',
						'flipboardproxy',
						'genieo',
						'google',
						'go-http-client',
						'grapeshot',
						'hatena-useragent',
						'head',
						'hosttracker',
						'hubspot',
						'ia_archiver',
						'ichiro',
						'iltrovatore-setaccio',
						'immediatenet',
						'ina',
						'inoreader',
						'infegyatlas',
						'infohelfer',
						'instapaper',
						'jabse',
						'james',
						'jersey',
						'kumkie',
						'linkdex',
						'linkfluence',
						'linkwalker',
						'litefinder',
						'loadimpactpageanalyzer',
						'ltx71',
						'luminate',
						'lycos',
						'lycosa',
						'mediapartners-google',
						'msai',
						'myapp',
						'nativehost',
						'najdi',
						'netcraftsurveyagent',
						'netestate',
						'netseer',
						'netnewswire',
						'newspaper',
						'newsblur',
						'nuhk',
						'nuzzel',
						'okhttp',
						'otmedia',
						'owlin',
						'owncloud',
						'panscient',
						'paper\.li',
						'parsijoo',
						'protopage',
						'plukkie',
						'proximic',
						'pubsub',
						'python',
						'qirina',
						'qoshe',
						'qualidator',
						'qwantify',
						'rambler',
						'readability',
						'ruby',
						'sbsearch',
						'scoop\.it',
						'scooter',
						'scoutjet',
						'scrapy',
						'scrubby',
						'scrubbybloglines',
						'shareaholic',
						'shopwiki',
						'simplepie',
						'sistrix',
						'sitechecker',
						'siteexplorer',
						'snapshot',
						'sogou',
						'special_archiver',
						'speedy',
						'spinn3r',
						'spreadtrum',
						'steeler',
						'subscriber',
						'suma',
						'superdownloads',
						'svenska-webbsido',
						'teoma',
						'the knowledge AI',
						'thumbshots',
						'tineye',
						'traackr',
						'trendiction',
						'trendsmap',
						'tweetedtimes',
						'tweetmeme',
						'universalfeedparser',
						'uaslinkchecker',
						'undrip',
						'unwindfetchor',
						'upday',
						'vedma',
						'vkshare',
						'vm',
						'wch',
						'webalta',
						'webcookies',
						'webparser',
						'webthumbnail',
						'wesee',
						'wise-guys',
						'woko',
						'wordpress',
						'wotbox',
						'y!j-bri',
						'y!j-bro',
						'y!j-brw',
						'y!j-bsc',
						'yahoo',
						'yahoo!',
						'yahooysmcm',
						'ymobactus',
						'yats',
						'yeti',
						'zeerch'
					)) . ',i',
				(string)$_SERVER['HTTP_USER_AGENT']
			)
	);
}
if (!defined('_IS_BOT_FRIEND')) {
	define(
		'_IS_BOT_FRIEND',
		isset($_SERVER['HTTP_USER_AGENT'])
			and preg_match(
				',' . implode('|', array(
					'facebookexternalhit',
					'twitterbot',
					'flipboardproxy',
					'wordpress'
				)) . ',i',
				(string)$_SERVER['HTTP_USER_AGENT']
			)
	);
}

/*
 * Interdit de passer une variable id_article (ou id_xxx) qui ne
 * soit pas numérique (ce qui bloque l'exploitation de divers trous
 * de sécurité, dont celui de toutes les versions < 1.8.2f)
 * (sauf pour id_table, qui n'est pas numérique jusqu'à [5743])
 * (id_base est une variable de la config des widgets de WordPress)
 */
$_exceptions = array('id_table', 'id_base', 'id_parent', 'id_article_pdf');
foreach ($_GET as $var => $val) {
	if (
		$_GET[$var] and strncmp($var, "id_", 3) == 0
		and !in_array($var, $_exceptions)
	) {
		$_GET[$var] = is_array($_GET[$var]) ? @array_map('intval', $_GET[$var]) : intval($_GET[$var]);
	}
}
foreach ($_POST as $var => $val) {
	if (
		$_POST[$var] and strncmp($var, "id_", 3) == 0
		and !in_array($var, $_exceptions)
	) {
		$_POST[$var] = is_array($_POST[$var]) ? @array_map('intval', $_POST[$var]) : intval($_POST[$var]);
	}
}
foreach ($GLOBALS as $var => $val) {
	if (
		$GLOBALS[$var] and strncmp($var, "id_", 3) == 0
		and !in_array($var, $_exceptions)
	) {
		$GLOBALS[$var] = is_array($GLOBALS[$var]) ? @array_map('intval', $GLOBALS[$var]) : intval($GLOBALS[$var]);
	}
}

/*
 * Interdit la variable $cjpeg_command, qui était utilisée sans
 * précaution dans certaines versions de dev (1.8b2 -> 1.8b5)
 */
$cjpeg_command = '';

/*
 * Contrôle de quelques variables (XSS)
 */
foreach (array('lang', 'var_recherche', 'aide', 'var_lang_r', 'lang_r', 'var_ajax_ancre', 'nom_fichier') as $var) {
	if (isset($_GET[$var])) {
		$_REQUEST[$var] = $GLOBALS[$var] = $_GET[$var] = preg_replace(',[^\w\,/#&;-]+,', ' ', (string)$_GET[$var]);
	}
	if (isset($_POST[$var])) {
		$_REQUEST[$var] = $GLOBALS[$var] = $_POST[$var] = preg_replace(',[^\w\,/#&;-]+,', ' ', (string)$_POST[$var]);
	}
}

/*
 * Filtre l'accès à spip_acces_doc (injection SQL en 1.8.2x)
 */
if (isset($_SERVER['REQUEST_URI'])) {
	if (preg_match(',^(.*/)?spip_acces_doc\.,', (string)$_SERVER['REQUEST_URI'])) {
		$file = addslashes((string)$_GET['file']);
	}
}

/*
 * Pas d'inscription abusive
 */
if (
	isset($_REQUEST['mode']) and isset($_REQUEST['page'])
	and !in_array($_REQUEST['mode'], array("6forum", "1comite"))
	and $_REQUEST['page'] == "identifiants"
) {
	$ecran_securite_raison = "identifiants";
}

/*
 * Agenda joue à l'injection php
 */
if (
	isset($_REQUEST['partie_cal'])
	and $_REQUEST['partie_cal'] !== htmlentities((string)$_REQUEST['partie_cal'])
) {
	$ecran_securite_raison = "partie_cal";
}
if (
	isset($_REQUEST['echelle'])
	and $_REQUEST['echelle'] !== htmlentities((string)$_REQUEST['echelle'])
) {
	$ecran_securite_raison = "echelle";
}

/*
 * Espace privé
 */
if (
	isset($_REQUEST['exec'])
	and !preg_match(',^[\w-]+$,', (string)$_REQUEST['exec'])
) {
	$ecran_securite_raison = "exec";
}
if (
	isset($_REQUEST['cherche_auteur'])
	and preg_match(',[<],', (string)$_REQUEST['cherche_auteur'])
) {
	$ecran_securite_raison = "cherche_auteur";
}
if (
	isset($_REQUEST['exec'])
	and $_REQUEST['exec'] == 'auteurs'
	and isset($_REQUEST['recherche'])
	and preg_match(',[<],', (string)$_REQUEST['recherche'])
) {
	$ecran_securite_raison = "recherche";
}
if (
	isset($_REQUEST['exec'])
	and $_REQUEST['exec'] == 'info_plugin'
	and isset($_REQUEST['plugin'])
	and preg_match(',[<],', (string)$_REQUEST['plugin'])
) {
	$ecran_securite_raison = "plugin";
}
if (
	isset($_REQUEST['exec'])
	and $_REQUEST['exec'] == 'puce_statut'
	and isset($_REQUEST['id'])
	and !intval($_REQUEST['id'])
) {
	$ecran_securite_raison = "puce_statut";
}
if (
	isset($_REQUEST['action'])
	and $_REQUEST['action'] == 'configurer'
) {
	if (
		@file_exists('inc_version.php')
		or @file_exists('ecrire/inc_version.php')
	) {
		function action_configurer() {
			include_spip('inc/autoriser');
			if (!autoriser('configurer', _request('configuration'))) {
				include_spip('inc/minipres');
				echo minipres(_T('info_acces_interdit'));
				exit;
			}
			require _DIR_RESTREINT . 'action/configurer.php';
			action_configurer_dist();
		}
	}
}
if (
	isset($_REQUEST['action'])
	and $_REQUEST['action'] == 'ordonner_liens_documents'
	and isset($_REQUEST['ordre'])
	and is_string($_REQUEST['ordre'])
) {
	$ecran_securite_raison = "ordre a la chaine";
}


/*
 * Bloque les requêtes contenant %00 (manipulation d'include)
 */
if (strpos(
	(function_exists('get_magic_quotes_gpc') and @get_magic_quotes_gpc())
		? stripslashes(serialize($_REQUEST))
		: serialize($_REQUEST),
	chr(0)
) !== false) {
	$ecran_securite_raison = "%00";
}

/*
 * Bloque les requêtes fond=formulaire_
 */
if (
	isset($_REQUEST['fond'])
	and preg_match(',^formulaire_,i', $_REQUEST['fond'])
) {
	$ecran_securite_raison = "fond=formulaire_";
}

/*
 * Bloque les requêtes du type ?GLOBALS[type_urls]=toto (bug vieux php)
 */
if (isset($_REQUEST['GLOBALS'])) {
	$ecran_securite_raison = "GLOBALS[GLOBALS]";
}

/*
 * Bloque les requêtes des bots sur:
 * les agenda
 * les paginations entremélées
 */
if (_IS_BOT) {
	if (
		(isset($_REQUEST['echelle']) and isset($_REQUEST['partie_cal']) and isset($_REQUEST['type']))
		or (strpos((string)$_SERVER['REQUEST_URI'], 'debut_') and preg_match(',[?&]debut_.*&debut_,', (string)$_SERVER['REQUEST_URI']))
		or (isset($_REQUEST['calendrier_annee']) and strpos((string)$_SERVER['REQUEST_URI'], 'debut_'))
		or (isset($_REQUEST['calendrier_annee']) and preg_match(',[?&]calendrier_annee=.*&calendrier_annee=,', (string)$_SERVER['REQUEST_URI']))
	) {
		$ecran_securite_raison = "robot agenda/double pagination";
	}
}

/*
 * Bloque une vieille page de tests de CFG (<1.11)
 * Bloque un XSS sur une page inexistante
 */
if (isset($_REQUEST['page'])) {
	if ($_REQUEST['page'] == 'test_cfg') {
		$ecran_securite_raison = "test_cfg";
	}
	if ($_REQUEST['page'] !== htmlspecialchars((string)$_REQUEST['page'])) {
		$ecran_securite_raison = "xsspage";
	}
	if (
		$_REQUEST['page'] == '404'
		and isset($_REQUEST['erreur'])
	) {
		$ecran_securite_raison = "xss404";
	}
}

/*
 * XSS par array
 */
foreach (array('var_login') as $var) {
	if (isset($_REQUEST[$var]) and is_array($_REQUEST[$var])) {
		$ecran_securite_raison = "xss " . $var;
	}
}

/*
 * Parade antivirale contre un cheval de troie
 */
if (!function_exists('tmp_lkojfghx')) {
	function tmp_lkojfghx() {}
	function tmp_lkojfghx2($a = 0, $b = 0, $c = 0, $d = 0) {
		// si jamais on est arrivé ici sur une erreur php
		// et qu'un autre gestionnaire d'erreur est défini, l'appeller
		if ($b && $GLOBALS['tmp_xhgfjokl']) {
			call_user_func($GLOBALS['tmp_xhgfjokl'], $a, $b, $c, $d);
		}
	}
}
if (isset($_POST['tmp_lkojfghx3'])) {
	$ecran_securite_raison = "gumblar";
}

/*
 * Outils XML mal sécurisés < 2.0.9
 */
if (isset($_REQUEST['transformer_xml'])) {
	$ecran_securite_raison = "transformer_xml";
}

/*
 * Outils XML mal sécurisés again
 */
if (isset($_REQUEST['var_url']) and $_REQUEST['var_url'] and isset($_REQUEST['exec']) and $_REQUEST['exec'] == 'valider_xml') {
	$url = trim($_REQUEST['var_url']);
	if (
		strncmp($url, '/', 1) == 0
		or (($p = strpos($url, '..')) !== false and strpos($url, '..', $p + 3) !== false)
		or (($p = strpos($url, '..')) !== false and strpos($url, 'IMG', $p + 3) !== false)
		or (strpos($url, '://') !== false or strpos($url, ':\\') !== false)
	) {
		$ecran_securite_raison = 'URL interdite pour var_url';
	}
}

/*
 * Sauvegarde mal securisée < 2.0.9
 */
if (
	isset($_REQUEST['nom_sauvegarde'])
	and strstr((string)$_REQUEST['nom_sauvegarde'], '/')
) {
	$ecran_securite_raison = 'nom_sauvegarde manipulee';
}
if (
	isset($_REQUEST['znom_sauvegarde'])
	and strstr((string)$_REQUEST['znom_sauvegarde'], '/')
) {
	$ecran_securite_raison = 'znom_sauvegarde manipulee';
}


/*
 * op permet des inclusions arbitraires ;
 * on vérifie 'page' pour ne pas bloquer ... drupal
 */
if (
	isset($_REQUEST['op']) and isset($_REQUEST['page'])
	and $_REQUEST['op'] !== preg_replace('/[^\\-\w]/', '', $_REQUEST['op'])
) {
	$ecran_securite_raison = 'op';
}

/*
 * Forms & Table ne se méfiait pas assez des uploads de fichiers
 */
if (count($_FILES)) {
	foreach ($_FILES as $k => $v) {
		if (
			preg_match(',^fichier_\d+$,', $k)
			and preg_match(',\.php,i', $v['name'])
		) {
			unset($_FILES[$k]);
		}
	}
}
/*
 * et Contact trop laxiste avec une variable externe
 * on bloque pas le post pour eviter de perdre des donnees mais on unset la variable et c'est tout
 */
if (isset($_REQUEST['pj_enregistrees_nom']) and $_REQUEST['pj_enregistrees_nom']) {
	unset($_REQUEST['pj_enregistrees_nom']);
	unset($_GET['pj_enregistrees_nom']);
	unset($_POST['pj_enregistrees_nom']);
}

/*
 * reinstall=oui un peu trop permissif
 */
if (
	isset($_REQUEST['reinstall'])
	and $_REQUEST['reinstall'] == 'oui'
) {
	$ecran_securite_raison = 'reinstall=oui';
}

/*
 * Pas d'action pendant l'install
 */
if (isset($_REQUEST['exec']) and $_REQUEST['exec'] === 'install' and isset($_REQUEST['action'])) {
	$ecran_securite_raison = 'install&action impossibles';
}

/*
 * Échappement xss referer
 */
if (isset($_SERVER['HTTP_REFERER'])) {
	$_SERVER['HTTP_REFERER'] = strtr($_SERVER['HTTP_REFERER'], '<>"\'', '[]##');
}


/*
 * Echappement HTTP_X_FORWARDED_HOST
 */
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
	$_SERVER['HTTP_X_FORWARDED_HOST'] = strtr($_SERVER['HTTP_X_FORWARDED_HOST'], "<>?\"\{\}\$'` \r\n", '____________');
}


/*
 * Pas d'erreur dans l'erreur
 */
if (isset($_REQUEST['var_erreur']) and isset($_REQUEST['page']) and $_REQUEST['page'] === 'login') {
	if (strlen($_REQUEST['var_erreur']) !== strcspn($_REQUEST['var_erreur'], '<>')) {
		$ecran_securite_raison = 'var_erreur incorrecte';
	}
}


/*
 * Réinjection des clés en html dans l'admin r19561
 */
if (
	(isset($_SERVER['REQUEST_URI']) and strpos($_SERVER['REQUEST_URI'], "ecrire/") !== false)
	or isset($_REQUEST['var_memotri'])
) {
	$zzzz = implode("", array_keys($_REQUEST));
	if (strlen($zzzz) != strcspn($zzzz, '<>"\'')) {
		$ecran_securite_raison = 'Cle incorrecte en $_REQUEST';
	}
}

/*
 * Injection par connect
 */
if (
	isset($_REQUEST['connect'])
	// cas qui permettent de sortir d'un commentaire PHP
	and (
		strpos($_REQUEST['connect'], "?") !== false
		or strpos($_REQUEST['connect'], "<") !== false
		or strpos($_REQUEST['connect'], ">") !== false
		or strpos($_REQUEST['connect'], "\n") !== false
		or strpos($_REQUEST['connect'], "\r") !== false
	)
) {
	$ecran_securite_raison = "malformed connect argument";
}


/*
 * _oups donc
 */
if (
	isset($_REQUEST['_oups'])
	and base64_decode($_REQUEST['_oups'], true) === false) {
	$ecran_securite_raison = "malformed _oups argument";
}

if (
	isset($_REQUEST['formulaire_action_args'])
) {
	foreach ($_REQUEST as $k => $v) {
		if (is_string($v)
		  and strpos($v, ':') !== false
		  and strpos($v, '"') !==false
		  and preg_match(',[bidsaO]:,', $v)
		  and @unserialize($v)) {
			$_REQUEST[$k] = htmlentities($v);
			if (isset($_POST[$k])) $_POST[$k] = $_REQUEST[$k];
			if (isset($_GET[$k])) $_GET[$k] = $_REQUEST[$k];
		}
	}
}


/*
 * S'il y a une raison de mourir, mourons
 */
if (isset($ecran_securite_raison)) {
	header("HTTP/1.0 403 Forbidden");
	header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	header("Content-Type: text/html");
	header("Connection: close");
	die("<html><title>Error 403: Forbidden</title><body><h1>Error 403</h1><p>You are not authorized to view this page ($ecran_securite_raison)</p></body></html>");
}

/*
 * Un filtre filtrer_entites securise
 */
if (!function_exists('filtre_filtrer_entites_dist')) {
	function filtre_filtrer_entites_dist($t) {
		include_spip('inc/texte');
		return interdire_scripts(filtrer_entites($t));
	}
}


/*
 * Fin sécurité
 */



/*
 * Bloque les bots quand le load déborde
 */
if (!defined('_ECRAN_SECURITE_LOAD')) {
	define('_ECRAN_SECURITE_LOAD', 4);
}

if (
	defined('_ECRAN_SECURITE_LOAD')
	and _ECRAN_SECURITE_LOAD > 0
	and _IS_BOT
	and !_IS_BOT_FRIEND
	and $_SERVER['REQUEST_METHOD'] === 'GET'
	and (
		(function_exists('sys_getloadavg')
			and $load = sys_getloadavg()
			and is_array($load)
			and $load = array_shift($load))
		or
		(@is_readable('/proc/loadavg')
			and $load = file_get_contents('/proc/loadavg')
			and $load = floatval($load))
	)
	and $load > _ECRAN_SECURITE_LOAD // eviter l'evaluation suivante si de toute facon le load est inferieur a la limite
	and rand(0, (int) ($load * $load)) > _ECRAN_SECURITE_LOAD * _ECRAN_SECURITE_LOAD
) {
	//https://webmasters.stackexchange.com/questions/65674/should-i-return-a-429-or-503-status-code-to-a-bot
	header("HTTP/1.0 429 Too Many Requests");
	header("Retry-After: 300");
	header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	header("Content-Type: text/html");
	header("Connection: close");
	die("<html><title>Status 429: Too Many Requests</title><body><h1>Status 429</h1><p>Too Many Requests (try again soon)</p></body></html>");
}
