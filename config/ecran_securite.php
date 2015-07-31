<?php

/*
 * ecran_securite.php
 * ------------------
 */

define('_ECRAN_SECURITE', '1.1.9'); // 2014-03-13

/*
 * Documentation : http://www.spip.net/fr_article4200.html
 */

/*
 * Test utilisateur
 */
if (isset($_GET['test_ecran_securite']))
	$ecran_securite_raison = 'test '._ECRAN_SECURITE;

/*
 * Détecteur de robot d'indexation
 */
if (!defined('_IS_BOT'))
	define('_IS_BOT',
		isset($_SERVER['HTTP_USER_AGENT'])
		AND preg_match(
	    // mots generiques
	    ',bot|slurp|crawler|spider|webvac|yandex|'
	    // UA plus cibles
	    . '80legs|accoona|AltaVista|ASPSeek|Baidu|Charlotte|EC2LinkFinder|eStyle|Google|INA dlweb|Java VM|LiteFinder|Lycos|Rambler|Scooter|ScrubbyBloglines|Yahoo|Yeti'
	    . ',i',(string) $_SERVER['HTTP_USER_AGENT'])
	);

/*
 * Interdit de passer une variable id_article (ou id_xxx) qui ne
 * soit pas numérique (ce qui bloque l'exploitation de divers trous
 * de sécurité, dont celui de toutes les versions < 1.8.2f)
 * (sauf pour id_table, qui n'est pas numérique jusqu'à [5743])
 */
foreach ($_GET as $var => $val)
	if ($_GET[$var] AND strncmp($var,"id_",3)==0 AND $var!='id_table')
		$_GET[$var] = is_array($_GET[$var])?@array_map('intval',$_GET[$var]):intval($_GET[$var]);
foreach ($_POST as $var => $val)
	if ($_POST[$var] AND strncmp($var,"id_",3)==0 AND $var!='id_table')
		$_POST[$var] = is_array($_POST[$var])?@array_map('intval',$_POST[$var]):intval($_POST[$var]);
foreach ($GLOBALS as $var => $val)
	if ($GLOBALS[$var] AND strncmp($var,"id_",3)==0 AND $var!='id_table')
		$GLOBALS[$var] = is_array($GLOBALS[$var])?@array_map('intval',$GLOBALS[$var]):intval($GLOBALS[$var]);

/*
 * Interdit la variable $cjpeg_command, qui était utilisée sans
 * précaution dans certaines versions de dev (1.8b2 -> 1.8b5)
 */
$cjpeg_command='';

/*
 * Contrôle de quelques variables (XSS)
 */
foreach(array('lang', 'var_recherche', 'aide', 'var_lang_r', 'lang_r', 'var_ajax_ancre') as $var) {
	if (isset($_GET[$var]))
		$_REQUEST[$var] = $GLOBALS[$var] = $_GET[$var] = preg_replace(',[^\w\,/#&;-]+,',' ',(string)$_GET[$var]);
	if (isset($_POST[$var]))
		$_REQUEST[$var] = $GLOBALS[$var] = $_POST[$var] = preg_replace(',[^\w\,/#&;-]+,',' ',(string)$_POST[$var]);
}

/*
 * Filtre l'accès à spip_acces_doc (injection SQL en 1.8.2x)
 */
if (preg_match(',^(.*/)?spip_acces_doc\.,', (string)$_SERVER['REQUEST_URI'])) {
	$file = addslashes((string)$_GET['file']);
}

/*
 * Pas d'inscription abusive
 */
if (isset($_REQUEST['mode']) AND isset($_REQUEST['page'])
AND !in_array($_REQUEST['mode'],array("6forum","1comite"))
AND $_REQUEST['page'] == "identifiants")
	$ecran_securite_raison = "identifiants";

/*
 * Agenda joue à l'injection php
 */
if (isset($_REQUEST['partie_cal'])
AND $_REQUEST['partie_cal'] !== htmlentities((string)$_REQUEST['partie_cal']))
	$ecran_securite_raison = "partie_cal";
if (isset($_REQUEST['echelle'])
AND $_REQUEST['echelle'] !== htmlentities((string)$_REQUEST['echelle']))
	$ecran_securite_raison = "echelle";

/*
 * Espace privé
 */
if (isset($_REQUEST['exec'])
AND !preg_match(',^[\w-]+$,', (string)$_REQUEST['exec']))
	$ecran_securite_raison = "exec";
if (isset($_REQUEST['cherche_auteur'])
AND preg_match(',[<],', (string)$_REQUEST['cherche_auteur']))
	$ecran_securite_raison = "cherche_auteur";
if (isset($_REQUEST['exec'])
AND $_REQUEST['exec'] == 'auteurs'
AND preg_match(',[<],', (string)$_REQUEST['recherche']))
	$ecran_securite_raison = "recherche";
if (isset($_REQUEST['action'])
AND $_REQUEST['action'] == 'configurer') {
	if (@file_exists('inc_version.php')
	OR @file_exists('ecrire/inc_version.php')) {
		function action_configurer() {
			include_spip('inc/autoriser');
			if(!autoriser('configurer', _request('configuration'))) {
				include_spip('inc/minipres');
				echo minipres(_T('info_acces_interdit'));
				exit;
			}
			require _DIR_RESTREINT.'action/configurer.php';
			action_configurer_dist();
		}
	}
}

/*
 * Bloque les requêtes contenant %00 (manipulation d'include)
 */
if (strpos(
	@get_magic_quotes_gpc() ?
		stripslashes(serialize($_REQUEST)) : serialize($_REQUEST),
	chr(0)
) !== false)
	$ecran_securite_raison = "%00";

/*
 * Bloque les requêtes fond=formulaire_
 */
if (isset($_REQUEST['fond'])
AND preg_match(',^formulaire_,i', $_REQUEST['fond']))
	$ecran_securite_raison = "fond=formulaire_";

/*
 * Bloque les requêtes du type ?GLOBALS[type_urls]=toto (bug vieux php)
 */
if (isset($_REQUEST['GLOBALS']))
	$ecran_securite_raison = "GLOBALS[GLOBALS]";

/*
 * Bloque les requêtes des bots sur:
 * les agenda
 * les paginations entremélées
 */
if (_IS_BOT AND (
	(isset($_REQUEST['echelle']) AND isset($_REQUEST['partie_cal']) AND isset($_REQUEST['type']))
	OR (strpos((string)$_SERVER['REQUEST_URI'],'debut_') AND preg_match(',[?&]debut_.*&debut_,', (string)$_SERVER['REQUEST_URI']))
)
)
	$ecran_securite_raison = "robot agenda/double pagination";

/*
 * Bloque une vieille page de tests de CFG (<1.11)
 * Bloque un XSS sur une page inexistante
 */
if (isset($_REQUEST['page'])) {
	if ($_REQUEST['page']=='test_cfg')
		$ecran_securite_raison = "test_cfg";
	if ($_REQUEST['page'] !== htmlspecialchars((string)$_REQUEST['page']))
		$ecran_securite_raison = "xsspage";
	if ($_REQUEST['page'] == '404'
	AND isset($_REQUEST['erreur']))
		$ecran_securite_raison = "xss404";
}

/*
 * XSS par array
 */
foreach (array('var_login') as $var)
if (isset($_REQUEST[$var]) AND is_array($_REQUEST[$var]))
	$ecran_securite_raison = "xss ".$var;

/*
 * Parade antivirale contre un cheval de troie
 */
if (!function_exists('tmp_lkojfghx')) {
	function tmp_lkojfghx() {}
	function tmp_lkojfghx2($a=0, $b=0, $c=0, $d=0) {
		// si jamais on est arrivé ici sur une erreur php
		// et qu'un autre gestionnaire d'erreur est défini, l'appeller
		if ($b&&$GLOBALS['tmp_xhgfjokl'])
			call_user_func($GLOBALS['tmp_xhgfjokl'],$a,$b,$c,$d);
	}
}
if (isset($_POST['tmp_lkojfghx3']))
	$ecran_securite_raison = "gumblar";

/*
 * Outils XML mal sécurisés < 2.0.9
 */
if (isset($_REQUEST['transformer_xml']))
	$ecran_securite_raison = "transformer_xml";

/*
 * Sauvegarde mal securisée < 2.0.9
 */
if (isset($_REQUEST['nom_sauvegarde'])
AND strstr((string)$_REQUEST['nom_sauvegarde'], '/'))
	$ecran_securite_raison = 'nom_sauvegarde manipulee';
if (isset($_REQUEST['znom_sauvegarde'])
AND strstr((string)$_REQUEST['znom_sauvegarde'], '/'))
	$ecran_securite_raison = 'znom_sauvegarde manipulee';


/*
 * op permet des inclusions arbitraires ;
 * on vérifie 'page' pour ne pas bloquer ... drupal
 */
if (isset($_REQUEST['op']) AND isset($_REQUEST['page'])
AND $_REQUEST['op'] !== preg_replace('/[^\-\w]/', '', $_REQUEST['op']))
	$ecran_securite_raison = 'op';

/*
 * Forms & Table ne se méfiait pas assez des uploads de fichiers
 */
if (count($_FILES)){
	foreach($_FILES as $k=>$v){
		 if (preg_match(',^fichier_\d+$,',$k)
		 AND preg_match(',\.php,i',$v['name']))
		 	unset($_FILES[$k]);
	}
}

/*
 * reinstall=oui un peu trop permissif
 */
if (isset($_REQUEST['reinstall'])
AND $_REQUEST['reinstall'] == 'oui')
	$ecran_securite_raison = 'reinstall=oui';

/*
 * Échappement xss referer
 */
if (isset($_SERVER['HTTP_REFERER']))
	$_SERVER['HTTP_REFERER'] = strtr($_SERVER['HTTP_REFERER'], '<>"\'', '[]##');

/*
 * Réinjection des clés en html dans l'admin r19561
 */
if (strpos($_SERVER['REQUEST_URI'],"ecrire/")!==false){
	$zzzz=implode("",array_keys($_REQUEST));
	if (strlen($zzzz)!=strcspn($zzzz,'<>"\''))
		$ecran_securite_raison = 'Cle incorrecte en $_REQUEST';
}

/*
 * Injection par connect
 */
if (isset($_REQUEST['connect'])
	AND
	// cas qui permettent de sortir d'un commentaire PHP
	(strpos($_REQUEST['connect'], "?")!==false
	 OR strpos($_REQUEST['connect'], "<")!==false
	 OR strpos($_REQUEST['connect'], ">")!==false
	 OR strpos($_REQUEST['connect'], "\n")!==false
	 OR strpos($_REQUEST['connect'], "\r")!==false)
	) {
	$ecran_securite_raison = "malformed connect argument";
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
	die("<html><title>Error 403: Forbidden</title><body><h1>Error 403</h1><p>You are not authorized to view this page ($ecran_securite_raison)</p></body></html>");
}

/*
 * Fin sécurité
 */



/*
 * Bloque les bots quand le load déborde
 */
if (!defined('_ECRAN_SECURITE_LOAD'))
	define('_ECRAN_SECURITE_LOAD', 4);

if (
	defined('_ECRAN_SECURITE_LOAD')
	AND _ECRAN_SECURITE_LOAD>0
	AND _IS_BOT
	AND $_SERVER['REQUEST_METHOD'] === 'GET'
	AND (
		(function_exists('sys_getloadavg')
		  AND $load = sys_getloadavg()
		  AND is_array($load)
		  AND $load = array_shift($load)
		)
		OR
		(@is_readable('/proc/loadavg')
		  AND $load = file_get_contents('/proc/loadavg')
		  AND $load = floatval($load)
		)
	)
	AND $load > _ECRAN_SECURITE_LOAD // eviter l'evaluation suivante si de toute facon le load est inferieur a la limite
	AND rand(0, $load*$load) > _ECRAN_SECURITE_LOAD*_ECRAN_SECURITE_LOAD
) {
	header("HTTP/1.0 503 Service Unavailable");
	header("Retry-After: 300");
	header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	header("Content-Type: text/html");
	die("<html><title>Status 503: Site temporarily unavailable</title><body><h1>Status 503</h1><p>Site temporarily unavailable (load average $load)</p></body></html>");
}


?>