<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_VERSION")) return;
define("_ECRIRE_INC_VERSION", "1");

function define_once ($constant, $valeur) {
	if (!defined($constant)) define($constant, $valeur);
}

// 6 constantes incontournables et prioritaires

define('_EXTENSION_PHP', '.php3'); # a etendre
define('_DIR_RESTREINT_ABS', 'ecrire/');
define('_DIR_RESTREINT', (!@is_dir(_DIR_RESTREINT_ABS) ? "" : _DIR_RESTREINT_ABS));
define('_FILE_OPTIONS', _DIR_RESTREINT . 'mes_options.php3');
define('_FILE_CONNECT_INS', (_DIR_RESTREINT . "inc_connect"));
define('_FILE_CONNECT',
	(@file_exists(_FILE_CONNECT_INS . _EXTENSION_PHP) ?
		(_FILE_CONNECT_INS . _EXTENSION_PHP)
	 : false));

//
// Gestion des inclusions et infos repertoires
//

$included_files = array();

function include_local($file) {
	if (!empty($GLOBALS['included_files'][$file])) return;
	$GLOBALS['included_files'][$file] = 1;
	include($file);
}

function include_ecrire($file) {
# Hack pour etre compatible avec les mes_options qui appellent cette fonction
	define_once('_DIR_INCLUDE', _DIR_RESTREINT);
	$file = _DIR_INCLUDE . $file;
	if (!empty($GLOBALS['included_files'][$file])) return;
	$GLOBALS['included_files'][$file] = 1;
	include($file);
}

function include_lang($file) {
	$file = _DIR_LANG . $file;
	if (!empty($GLOBALS['included_files'][$file])) return;
	$GLOBALS['included_files'][$file] = 1;
	include($file);
}

function include_plug($file) {
	$file = _DIR_RESTREINT . $file;
	if (!empty($GLOBALS['included_files'][$file])) return;
	if (file_exists($file)) include($file);
	$GLOBALS['included_files'][$file] = 1;
}


// Que faire si Spip n'est pas installe... sauf si justement on l'installe!
if (!(_FILE_CONNECT OR defined('_ECRIRE_INSTALL') OR defined('_TEST_DIRS'))) {
	// Soit on est dans ecrire/ et on envoie sur l'installation
	if (@file_exists("inc_version.php3")) {
		header("Location: " . _DIR_RESTREINT . "install.php3");
		exit;
	}
	// Soit on est dans le site public
	else if (defined("_INC_PUBLIC")) {
		# on ne peut pas deviner ces repertoires avant l'installation !
		define('_DIR_INCLUDE', _DIR_RESTREINT);
		define('_DIR_IMG_PACK', (_DIR_RESTREINT . 'img_pack/'));
		define('_DIR_LANG', (_DIR_RESTREINT . 'lang/'));
		$db_ok = false;
		include_ecrire ("inc_presentation.php3");
		install_debut_html(_T('info_travaux_titre'));
		echo "<p>"._T('info_travaux_texte')."</p>";
		install_fin_html();
		exit;
	}
	// Soit on est appele de l'exterieur (spikini, etc)
}

// *********** traiter les variables ************
// Magic quotes : on n'en veut pas sur la base,
// et on nettoie les GET/POST/COOKIE le cas echeant
//

function magic_unquote($table) {
	if (is_array($GLOBALS[$table])) {
		reset($GLOBALS[$table]);
		while (list($key, $val) = each($GLOBALS[$table])) {
			if (is_string($val))
				$GLOBALS[$table][$key] = stripslashes($val);
		}
	}
}

@set_magic_quotes_runtime(0);
$unquote_gpc = @get_magic_quotes_gpc();

if ($unquote_gpc) {
	magic_unquote('HTTP_GET_VARS');
	magic_unquote('HTTP_POST_VARS');
	magic_unquote('HTTP_COOKIE_VARS');
}

//
// Dirty hack contre le register_globals a 'Off' (PHP 4.1.x)
// A remplacer par une gestion propre des variables admissibles ;-)
//

$INSECURE = array();

function feed_globals($table, $insecure = true, $ignore_variables_contexte = false) {
	global $INSECURE;

	// ignorer des cookies qui contiendraient du contexte 
	$is_contexte = array('id_parent'=>1, 'id_rubrique'=>1, 'id_article'=>1, 'id_auteur'=>1,
		'id_breve'=>1, 'id_forum'=>1, 'id_secteur'=>1, 'id_syndic'=>1, 'id_syndic_article'=>1,
		'id_mot'=>1, 'id_groupe'=>1, 'id_document'=>1, 'date'=>1, 'lang'=>1);

	if (is_array($GLOBALS[$table])) {
        reset($GLOBALS[$table]);
        while (list($key, $val) = each($GLOBALS[$table])) {
			if ($ignore_variables_contexte AND isset($is_contexte[$key]))
				unset ($GLOBALS[$key]);
			else
				$GLOBALS[$key] = $val;
			if ($insecure) $INSECURE[$key] = $val;
        }
	}
}

feed_globals('HTTP_COOKIE_VARS', true, true);
feed_globals('HTTP_GET_VARS');
feed_globals('HTTP_POST_VARS');
feed_globals('HTTP_SERVER_VARS', false);


//
// Avec register_globals a Off sous PHP4, il faut utiliser
// la nouvelle variable HTTP_POST_FILES pour les fichiers uploades
// (pas valable sous PHP3...)
//

function feed_post_files($table) {
	global $INSECURE;
	if (is_array($GLOBALS[$table])) {
	        reset($GLOBALS[$table]);
	        while (list($key, $val) = each($GLOBALS[$table])) {
	                $GLOBALS[$key] = $INSECURE[$key] = $val['tmp_name'];
	                $GLOBALS[$key.'_name'] = $INSECURE[$key.'_name'] = $val['name'];
	        }
	}
}

feed_post_files('HTTP_POST_FILES');


//
// 	*** Parametrage par defaut de SPIP ***
//
// Ces parametres d'ordre technique peuvent etre modifies
// dans FILE_OPTIONS Les valeurs specifiees
// dans ce dernier fichier remplaceront automatiquement
// les valeurs ci-dessous.
//
// Pour creer _FILE_OPTIONS : recopier simplement
// les lignes ci-dessous, et ajouter le marquage de debut et
// de fin de fichier PHP ("< ?php" et "? >", sans les espaces)
//

// Prefixe des tables dans la base de donnees
// (a modifier pour avoir plusieurs sites SPIP dans une seule base)
$table_prefix = "spip";

// Prefixe et chemin des cookies
// (a modifier pour installer des sites SPIP dans des sous-repertoires)
$cookie_prefix = "spip";
$cookie_path = "";

// Dossier des squelettes
// (a modifier si l'on veut passer rapidement d'un jeu de squelettes a un autre)
$dossier_squelettes = "";

// faut-il autoriser SPIP a compresser les pages a la volee quand le
// navigateur l'accepte (valable pour apache >= 1.3 seulement) ?
$auto_compress = true;

// compresser les fichiers du repertoire CACHE/ avec gzip ?
// attention : apres toute modification de ce reglage il faut vider le cache
$compresser_cache = true;

// Type d'URLs
// 'standard': article.php3?id_article=123
// 'html': article123.html
// 'propres': Titre-de-l-article.html <http://lab.spip.net/spikini/UrlsPropres>
$type_urls = 'standard';

// creation des vignettes avec image magick en ligne de commande : mettre
// le chemin complet '/bin/convert' (Linux) ou '/sw/bin/convert' (fink/Mac OS X)
// Note : preferer GD2 ou le module php imagick s'ils sont disponibles
$convert_command = 'convert';

// creation des vignettes avec pnmscale
// Note: plus facile a installer par FTP,
// voir http://gallery.menalto.com/modules.php?op=modload&name=GalleryFAQ&file=index&myfaq=yes&id_cat=2
$pnmscale_command = 'pnmscale';

// faut-il passer les connexions MySQL en mode debug ?
$mysql_debug = false;

// faut-il chronometrer les requetes MySQL ?
$mysql_profile = false;

// faut-il faire des connexions completes rappelant le nom du serveur et de
// la base MySQL ? (utile si vos squelettes appellent d'autres bases MySQL ;
// a desactiver en cas de soucis de connexion chez certains hebergeurs [??])
$mysql_rappel_connexion = true;

// faut-il afficher en rouge les chaines non traduites ?
$test_i18n = false;

// faut-il souligner en gris, dans articles.php3, les espaces insecables ?
$activer_revision_nbsp = false;

// gestion des extras (voir inc_extra.php3 pour plus d'informations)
$champs_extra = false;
$champs_extra_proposes = false;

// faut-il ignorer l'authentification par auth http/remote_user ?
// cela permet d'avoir un SPIP sous .htaccess (ignore_remote_user),
// mais aussi de fonctionner sur des serveurs debiles se
// bloquant sur PHP_AUTH_USER=root (ignore_auth_http)
$ignore_auth_http = false;
$ignore_remote_user = false;

// Faut-il "invalider" les caches quand on depublie ou modifie un article ?
// (experimental)
# NB: cette option ne concerne que articles,breves,rubriques et site
# car les forums et petitions sont toujours invalidants.
$invalider_caches = false;

// Quota : la variable $quota_cache, si elle est > 0, indique la taille
// totale maximale desiree des fichiers contenus dans le CACHE/ ;
// ce quota n'est pas "dur", il ne s'applique qu'une fois par heure et
// fait redescendre le cache a la taille voulue ; valeur en Mo
// Si la variable vaut 0 aucun quota ne s'applique
$quota_cache = 5;

// code a fournir pour obtenir le debuggueur (urls &var_debug=xxxx)
// par defaut seuls les admins : $code_activation_debug='';
// pour mettre un mot de passe : $code_activation_debug='x5g8jk9';
$code_activation_debug = '';

//
// Serveurs externes
//
# aide en ligne
$help_server = 'http://www.spip.net/aide';
# TeX
$tex_server = 'http://math.spip.org/tex.php';
# MathML (pas pour l'instant: manque un bon convertisseur)
// $mathml_server = 'http://arno.rezo.net/tex2mathml/latex.php';
# Orthographe (serveurs multiples) [pas utilise pour l'instant]
$ortho_servers = array ('http://ortho.spip.net/ortho_serveur.php');

// Produire du TeX ou du MathML ?
$traiter_math = 'tex';

// Masquer les warning
error_reporting(E_ALL ^ E_NOTICE);

/* ATTENTION CETTE VARIABLE NE FONCTIONNE PAS ENCORE */
// Extension du fichier du squelette 
$extension_squelette = 'html';
/* / MERCI DE VOTRE ATTENTION */

// Droits d'acces maximum par defaut
@umask(0);

//
// Définition des repertoires standards, _FILE_OPTIONS ayant priorite
//

if (@file_exists(_FILE_OPTIONS)) {
  include(_FILE_OPTIONS);
 }

define_once('_DIR_INCLUDE', _DIR_RESTREINT);
define_once('_DIR_PREFIX1', (_DIR_RESTREINT ? "" : "../"));
define_once('_DIR_PREFIX2', _DIR_RESTREINT);

// les repertoires des logos, des pieces rapportees, du CACHE et des sessions

define_once('_DIR_IMG', _DIR_PREFIX1 ."IMG/");
define_once('_DIR_DOC', _DIR_PREFIX1 ."IMG/");
define_once('_DIR_CACHE', _DIR_PREFIX1 ."CACHE/");

define_once('_DIR_SESSIONS', _DIR_PREFIX2 . "data/");
define_once('_DIR_TRANSFERT', _DIR_PREFIX2 . "upload/");


## c'est tres bete de charger ce fichier a chaque hit sur le serveur !
if (@file_exists(_DIR_SESSIONS . 'inc_plugins.php3')) {
	include(_DIR_SESSIONS . 'inc_plugins.php3');
}

// exemples de redefinition possible, 
// SOUS RESERVE QUE php.ini N'AIT PAS pas openbasedir=. !!!!!!
// il est recommande de mettre les deux premiers en dehors de l'arbo http
// pour _DIR_DOC, on ne peut le faire qu'en configuration securisee
// pour _DIR_IMG, NE PAS LE METTRE en dehors de l'arborescence http

//define('_DIR_CACHE', "/tmp/c/");
//define('_DIR_SESSIONS', "/tmp/s/");

//define('_DIR_DOC', "/tmp/d/");
//define('_DIR_INCLUDE', _DIR_RESTREINT  ? 'Include/' : '../Include/');
// globale des repertoires devant etre accessibles en ecriture
// (inutile de mettre leurs sous-repertoires)

$test_dirs = array(_DIR_CACHE, _DIR_IMG, _DIR_SESSIONS);

// les fichiers qu'on y met, entre autres,

define_once('_FILE_LOCK', _DIR_SESSIONS . 'lock');
define_once('_FILE_CRON_LOCK', _DIR_SESSIONS . 'cron.lock');
define_once('_FILE_MYSQL_OUT', _DIR_SESSIONS . 'mysql_out');
define_once('_FILE_GARBAGE', _DIR_SESSIONS . '.poubelle');


// sous-repertoires d'images accessible en ecriture

define_once('_DIR_IMG_ICONES', _DIR_IMG . "icones/");
define_once('_DIR_IMG_ICONES_BARRE', _DIR_IMG . "icones_barre/");
define_once('_DIR_TeX', _DIR_IMG . "TeX/");

// pour ceux qui n'aiment pas nos icones et notre vocabulaire, tout est prevu

define_once('_DIR_IMG_PACK', (_DIR_RESTREINT . 'img_pack/'));
define_once('_DIR_LANG', (_DIR_RESTREINT . 'lang/'));

// qq chaines standard

define_once('_ACCESS_FILE_NAME', '.htaccess');
define_once('_AUTH_USER_FILE', '.htpasswd');

# obsoletes: utiliser les constantes ci-dessus.
# Conserver pour compatibité vieilles contrib uniquement

$flag_ecrire = !@file_exists(_DIR_RESTREINT_ABS . 'inc_version.php3');
$dir_ecrire = (ereg("/ecrire/", $GLOBALS['REQUEST_URI'])) ? '' : 'ecrire/';

// Version courante de SPIP
// Stockee sous forme de nombre decimal afin de faciliter les comparaisons
// (utilise pour les modifs de la base de donnees)

// version de la base
$spip_version = 1.808;

// version de spip
$spip_version_affichee = "1.8 beta 2 CVS";

// version de spip / tag cvs
if (ereg('Name: v(.*) ','$Name$', $regs)) $spip_version_affichee = $regs[1];


// ** Securite **
$auteur_session = '';
$connect_statut = '';
$hash_recherche = '';
$hash_recherche_strict = '';


//
// Infos de version PHP
// (doit etre au moins egale a 3.0.8)
//

$php_version = phpversion();
$php_version_tab = explode('.', $php_version);
$php_version_maj = intval($php_version_tab[0]);
$php_version_med = intval($php_version_tab[1]);
if (ereg('([0-9]+)', $php_version_tab[2], $match)) $php_version_min = intval($match[1]);

$flag_levenshtein = ($php_version_maj >= 4);
$flag_uniqid2 = ($php_version_maj > 3 OR $php_version_min >= 13);
$flag_get_cfg_var = (@get_cfg_var('error_reporting') != "");
$flag_strtr2 = ($php_version_maj > 3);

$flag_ini_get = (function_exists("ini_get")
	&& (@ini_get('max_execution_time') > 0));	// verifier pas desactivee
$flag_gz = function_exists("gzencode"); #php 4.0.4
$flag_ob = ($flag_ini_get
	&& !ereg("ob_", ini_get('disable_functions'))
	&& function_exists("ob_start"));
$flag_obgz = ($flag_ob && ($php_version<>'4.0.4')
	&& function_exists("ob_gzhandler"));
$flag_pcre = function_exists("preg_replace");
$flag_crypt = function_exists("crypt");
$flag_wordwrap = function_exists("wordwrap");
$flag_apc = function_exists("apc_rm");
$flag_sapi_name = function_exists("php_sapi_name");
$flag_utf8_decode = function_exists("utf8_decode");
$flag_ldap = function_exists("ldap_connect");
$flag_flock = function_exists("flock");
$flag_ImageCreateTrueColor = function_exists("ImageCreateTrueColor");
$flag_ImageCopyResampled = function_exists("ImageCopyResampled");
$flag_ImageGif = function_exists("ImageGif");
$flag_ImageJpeg = function_exists("ImageJpeg");
$flag_ImagePng = function_exists("ImagePng");
$flag_imagick = function_exists("imagick_readimage");	// http://pear.sourceforge.net/en/packages.imagick.php
$flag_multibyte = function_exists("mb_encode_mimeheader");
$flag_iconv = function_exists("iconv");
$flag_strtotime = function_exists("strtotime");

$flag_gd = $flag_ImageGif || $flag_ImageJpeg || $flag_ImagePng;
$flag_revisions = ($flag_pcre AND function_exists("gzcompress"));


//
// Appliquer le prefixe cookie
//
function spip_setcookie ($name='', $value='', $expire=0, $path='AUTO', $domain='', $secure='') {
	$name = ereg_replace ('^spip_', $GLOBALS['cookie_prefix'].'_', $name);
	if ($path == 'AUTO') $path=$GLOBALS['cookie_path'];

	if ($secure)
		@setcookie ($name, $value, $expire, $path, $domain, $secure);
	else if ($domain)
		@setcookie ($name, $value, $expire, $path, $domain);
	else if ($path)
		@setcookie ($name, $value, $expire, $path);
	else if ($expire)
		@setcookie ($name, $value, $expire);
	else
		@setcookie ($name, $value);
}
if ($cookie_prefix != 'spip') {
	reset ($HTTP_COOKIE_VARS);
	while (list($name,$value) = each($HTTP_COOKIE_VARS)) {
		if (ereg('^spip_', $name)) {
			unset($HTTP_COOKIE_VARS[$name]);
			unset($$name);
		}
	}
	reset ($HTTP_COOKIE_VARS);
	while (list($name,$value) = each($HTTP_COOKIE_VARS)) {
		if (ereg('^'.$cookie_prefix.'_', $name)) {
			$spipname = ereg_replace ('^'.$cookie_prefix.'_', 'spip_', $name);
			$HTTP_COOKIE_VARS[$spipname] = $INSECURE[$spipname] = $value;
			$$spipname = $value;
		}
	}
}


//
// Infos sur l'hebergeur
//

$hebergeur = '';
$login_hebergeur = '';
$os_serveur = '';


// Lycos (ex-Multimachin)
if ($HTTP_X_HOST == 'membres.lycos.fr') {
	$hebergeur = 'lycos';
	ereg('^/([^/]*)', $REQUEST_URI, $regs);
	$login_hebergeur = $regs[1];
}
// Altern
if (ereg('altern\.com$', $SERVER_NAME)) {
	$hebergeur = 'altern';
	ereg('([^.]*\.[^.]*)$', $HTTP_HOST, $regs);
	$login_hebergeur = ereg_replace('[^a-zA-Z0-9]', '_', $regs[1]);
}
// Free
else if (ereg('^/([^/]*)\.free.fr/', $REQUEST_URI, $regs)) {
	$hebergeur = 'free';
	$login_hebergeur = $regs[1];
}
// NexenServices
else if ($SERVER_ADMIN == 'www@nexenservices.com') {
	if (!function_exists('email'))
		include ('mail.inc');
	$hebergeur = 'nexenservices';
}
// Online
else if (function_exists('email')) {
	$hebergeur = 'online';
}

if (eregi('\(Win', $HTTP_SERVER_VARS['SERVER_SOFTWARE']))
	$os_serveur = 'windows';

//
// Verifier la presence des .htaccess
//
function verifier_htaccess($rep) {
	$htaccess = "$rep/" . _ACCESS_FILE_NAME;
	if ((!@file_exists($htaccess)) AND 
	    !defined('_ECRIRE_INSTALL') AND !defined('_TEST_DIRS')) {
		spip_log("demande de creation de $htaccess");
		if ($GLOBALS['hebergeur'] != 'nexenservices'){
			if (!$f = fopen($htaccess, "w"))
				echo "<b>" .
				  _L("ECHEC DE LA CREATION DE $htaccess") .
				  "</b>";
			else
			  {
				fputs($f, "deny from all\n");
				fclose($f);
			  }
		} else {
			echo "<font color=\"#FF0000\">IMPORTANT : </font>";
			echo "Votre h&eacute;bergeur est Nexen Services.<br />";
			echo "La protection du r&eacute;pertoire <i>$rep/</i> doit se faire
			par l'interm&eacute;diaire de ";
			echo "<a href=\"http://www.nexenservices.com/webmestres/htlocal.php\"
			target=\"_blank\">l'espace webmestres</a>.";
			echo "Veuillez cr&eacute;er manuellement la protection pour
			ce r&eacute;pertoire (un couple login/mot de passe est
			n&eacute;cessaire).<br />";
		}
	}
}


//
// Enregistrement des evenements
//
function spip_log($message, $logname='spip') {

	$pid = '(pid '.@getmypid().')';
	if (!$ip = $GLOBALS['REMOTE_ADDR']) $ip = '-';

	$message = date("M d H:i:s")." $ip $pid "
		.ereg_replace("\n*$", "\n", $message);

	$logfile = _DIR_SESSIONS . $logname . '.log';
	if (@file_exists($logfile) && (@filesize($logfile) > 10*1024)) {
		$rotate = true;
		$message .= "[-- rotate --]\n";
	}
	$f = @fopen($logfile, "ab");
	if ($f) {
		fputs($f, $message);
		fclose($f);
	}
	if ($rotate) {
		@unlink($logfile.'.3');
		@rename($logfile.'.2',$logfile.'.3');
		@rename($logfile.'.1',$logfile.'.2');
		@rename($logfile,$logfile.'.1');
		#if (function_exists('logrotate'))
		#	logrotate($logfile);
	}

	// recopier les spip_log mysql (ce sont uniquement des erreurs)
	// dans le spip_log general
	if ($logname == 'mysql')
		spip_log($message);
}

// Parano avant d'aller plus loin

verifier_htaccess(_DIR_SESSIONS);

//
// Infos sur le fichier courant
//

// Compatibilite avec serveurs ne fournissant pas $REQUEST_URI
if (!$REQUEST_URI) {
	$REQUEST_URI = $PHP_SELF;
	if (!strpos($REQUEST_URI, '?') && $QUERY_STRING)
		$REQUEST_URI .= '?'.$QUERY_STRING;
}

if (!$PATH_TRANSLATED) {
	if ($SCRIPT_FILENAME) $PATH_TRANSLATED = $SCRIPT_FILENAME;
	else if ($DOCUMENT_ROOT && $SCRIPT_URL) $PATH_TRANSLATED = $DOCUMENT_ROOT.$SCRIPT_URL;
}

function spip_query($query) {
	if (!_FILE_CONNECT)  {$GLOBALS['db_ok'] = false; return;}
	include_local(_FILE_CONNECT);
	if (!$GLOBALS['db_ok'])	return;
	if ($GLOBALS['spip_connect_version'] < 0.1) {
		if (!_DIR_RESTREINT) {$GLOBALS['db_ok'] = false; return;}
		@Header("Location: upgrade.php3?reinstall=oui");
		exit;
	}
	return spip_query_db($query);
}

function appliquer_fonction($lafonction, $entree) {

	$sortie = $entree;
	
	if (isset($GLOBALS["fonctions"]["$lafonction"]["avant"])) {
		foreach ($GLOBALS["fonctions"]["$lafonction"]["avant"] as $key => $value) {
			if (@function_exists($value)) $sortie = $value($sortie);
		}
	}
	
	if (@function_exists($lafonction)) $sortie = $lafonction($sortie);

	if (isset($GLOBALS["fonctions"]["$lafonction"]["apres"])) {
		foreach ($GLOBALS["fonctions"]["$lafonction"]["apres"] as $key => $value) {
			if (@function_exists($value)) $sortie = $value($sortie);
		}
	}	
	return $sortie;
}

function appliquer_fonction_avant($lafonction, $entree) {
	$sortie = $entree;
	
	if (isset($GLOBALS["fonctions"]["$lafonction"]["avant"])) {
		foreach ($GLOBALS["fonctions"]["$lafonction"]["avant"] as $key => $value) {
			if (@function_exists($value)) $sortie = $value($sortie);
		}
	}
			
	return $sortie;
}

function appliquer_fonction_apres($lafonction, $entree) {

	$sortie = $entree;
	
	if (isset($GLOBALS["fonctions"]["$lafonction"]["apres"])) {
		foreach ($GLOBALS["fonctions"]["$lafonction"]["apres"] as $key => $value) {
			if (@function_exists($value)) $sortie = $value($sortie);
		}
	}	
		
	return $sortie;
}


// Destine a "completer" une fonction
function completer_fonction($fonction_base, $fonction_avant="", $fonction_apres="") {
	if (strlen($fonction_avant) > 0) 
		$GLOBALS["fonctions"]["$fonction_base"]["avant"][] = $fonction_avant;
		
	if (strlen($fonction_apres) > 0) 
		$GLOBALS["fonctions"]["$fonction_base"]["apres"][] = $fonction_apres;	
}


//
// Infos de config PHP
//

// cf. liste des sapi_name - http://fr.php.net/php_sapi_name
$php_module = (($flag_sapi_name AND eregi("apache", @php_sapi_name())) OR
	ereg("^Apache.* PHP", $SERVER_SOFTWARE));
$php_cgi = ($flag_sapi_name AND eregi("cgi", @php_sapi_name()));

function http_status($status) {
	global $php_cgi, $REDIRECT_STATUS;

	if ($REDIRECT_STATUS && $REDIRECT_STATUS == $status) return;
	$status_string = array(
		200 => '200 OK',
		304 => '304 Not Modified',
		401 => '401 Unauthorized',
		403 => '403 Forbidden',
		404 => '404 Not Found'
	);
	if ($php_cgi) Header("Status: $status");
	else Header("HTTP/1.0 ".$status_string[$status]);
}

function http_gmoddate($lastmodified) {
	return gmdate("D, d M Y H:i:s", $lastmodified);
}
function http_last_modified($lastmodified, $expire = 0) {
	$gmoddate = http_gmoddate($lastmodified);
	if ($GLOBALS['HTTP_IF_MODIFIED_SINCE']) {
		$if_modified_since = ereg_replace(';.*$', '', $GLOBALS['HTTP_IF_MODIFIED_SINCE']);
		$if_modified_since = trim(str_replace('GMT', '', $if_modified_since));
		if ($if_modified_since == $gmoddate) {
			http_status(304);
			$headers_only = true;
		}
	}
	@Header ("Last-Modified: ".$gmoddate." GMT");
	if ($expire) 
		@Header ("Expires: ".http_gmoddate($expire)." GMT");
	return $headers_only;
}

$flag_upload = (!$flag_get_cfg_var || (get_cfg_var('upload_max_filesize') > 0));

function tester_upload() {
	return $GLOBALS['flag_upload'];
}


//
// Reglage de l'output buffering : si possible, generer une sortie
// compressee pour economiser de la bande passante
//
function test_obgz () {
	return
	$GLOBALS['auto_compress']
	&& $GLOBALS['flag_ob']
	&& $GLOBALS['flag_obgz']
	// special bug de proxy
	&& !eregi("NetCache|Hasd_proxy", $GLOBALS['HTTP_VIA'])
	// special bug Netscape Win 4.0x
	&& !eregi("Mozilla/4\.0[^ ].*Win", $GLOBALS['HTTP_USER_AGENT'])
	// special bug Apache2x
	&& !eregi("Apache(-[^ ]+)?/2", $GLOBALS['SERVER_SOFTWARE'])
	&& !($GLOBALS['flag_sapi_name'] AND ereg("^apache2", @php_sapi_name()))
	// si la compression est deja commencee, stop
	&& !@ini_get("zlib.output_compression")
	&& !@ini_get("output_handler");
}
// si un buffer est deja ouvert, stop
if ($flag_ob AND !strlen(@ob_get_contents())) {
	@header("Vary: Cookie, Accept-Encoding");
#	if (test_obgz())
#		ob_start('ob_gzhandler');
}


class Link {
	var $file;
	var $vars;
	var $arrays;

	//
	// Contructeur : a appeler soit avec l'URL du lien a creer,
	// soit sans parametres, auquel cas l'URL est l'URL courante
	//
	function Link($url = '', $reentrant = false) {
		static $link = '';

/*		// If root link not defined, create it
		if (!$link && !$reentrant) {
			$link = new Link('', true);
		}
*/

		$this->vars = array();
		$this->arrays = array();

		// Normal case
		if ($link) {
			if ($url) {
				$v = split('[\?\&]', $url);
				list(, $this->file) = each($v);
				while (list(, $var) = each($v)) {
					list($name, $value) = split('=', $var, 2);
					$name = urldecode($name);
					$value = urldecode($value);
					if (ereg('^(.*)\[\]$', $name, $regs)) {
						$this->arrays[$regs[1]][] = $value;
					}
					else {
						$this->vars[$name] = $value;
					}
				}
			}
			else {
				$this->file = $link->file;
				$this->vars = $link->vars;
				$this->arrays = $link->arrays;
			}
			return;
		}

		// Si aucun URL n'est specifie, creer le lien "propre"
		// ou l'on supprime de l'URL courant les bidules inutiles
		if (!$url) {
			// GET variables are read from the original URL
			// (HTTP_GET_VARS may contain additional variables
			// introduced by rewrite-rules)
			$url = $GLOBALS['REQUEST_URI'];
			// Warning !!!! 
			// since non encoded arguments may be present
			// (especially those coming from Rewrite Rule)
			// find the begining of the query string
			// to compute the script-name
			if ($v = strpos($url,'?'))
			  $v = strrpos(substr($url, 0, $v), '/');
			else $v = strrpos($url, '/');
			$url = substr($url, $v + 1);
			if (!$url) $url = "./";
			if (count($GLOBALS['HTTP_POST_VARS']))
				$vars = $GLOBALS['HTTP_POST_VARS'];
		}
		$v = split('[\?\&]', $url);
		list(, $this->file) = each($v);
		if (!$vars) {
			while (list(,$var) = each($v)) {
				list($name, $value) = split('=', $var, 2);
				$name = urldecode($name);
				$value = urldecode($value);
				if (ereg('^(.*)\[\]$', $name, $regs))
					$vars[$regs[1]][] = $value;
				else
					$vars[$name] = $value;
			}
		}

		if (is_array($vars)) {
			foreach ($vars as $name => $value) {
				// items supprimes
				if (!preg_match('/^('.
				(!_DIR_RESTREINT ?
					'|lang|set_options|set_couleur|set_disp|set_ecran':
					'|submit|recalcul')
				. ')$/i', $name)) {
					if (is_array($value))
						$this->arrays[$name] = $value;
					else
						$this->vars[$name] = $value;
				}
			}
		}
	}

	//
	// Effacer une variable
	//
	function delVar($name) {
		if($this->vars[$name]) unset($this->vars[$name]);
		if($this->arrays[$name]) unset($this->arrays[$name]);
	}

	//
	// Ajouter une variable
	// (si aucune valeur n'est specifiee, prend la valeur globale actuelle)
	//
	function addVar($name, $value = '__global__') {
		if ($value == '__global__') $value = $GLOBALS[$name];
		if (is_array($value))
			$this->arrays[$name] = $value;
		else
			$this->vars[$name] = $value;
	}

	//
	// Recuperer l'URL correspondant au lien
	//
	function getUrl($anchor = '') {
		$url = $this->file;
		if (!$url) $url = './';
		$query = '';
		foreach($this->vars as $name => $value)
			$query .= '&'.$name.'='.urlencode($value);

		foreach ($this->arrays as $name => $table)
		foreach ($table as $value)
			$query .= '&'.$name.'[]='.urlencode($value);

		if ($query) $query = '?'. substr($query, 1);
		if ($anchor) $anchor = '#'.$anchor;
		return "$url$query$anchor";
	}

	//
	// Recuperer le debut de formulaire correspondant au lien
	// (tag ouvrant + entrees cachees representant les variables)
	//

	function getForm($method = 'get', $anchor = '', $enctype = '') {
		if ($anchor) $anchor = '#'.$anchor;
		$form = "<form method='$method' action='".$this->file.$anchor."'";
		if ($enctype) $form .= " enctype='$enctype'";
		$form .= " style='border: 0px; margin: 0px;'>\n";
		foreach ($this->vars as $name => $value) {
			$value = ereg_replace('&amp;(#[0-9]+;)', '&\1', htmlspecialchars($value));
			$form .= "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
		}
		foreach ($this->arrays as $name => $table)
		foreach ($table as $value) {
			$value = ereg_replace('&amp;(#[0-9]+;)', '&\1', htmlspecialchars($value));
			$form .= "<input type=\"hidden\" name=\"".$name."[]\" value=\"".$value."\" />\n";
		}

		return $form;
	}
}


// Lien vers la page demandee et lien nettoye ne contenant que des id_objet
$clean_link = new Link();


// URLs avec passage & -> &amp;
function quote_amp ($url) {
	$url = str_replace("&amp;", "&", $url);
	$url = str_replace("&", "&amp;", $url);
	return $url;
}


//
// Module de lecture/ecriture/suppression de fichiers utilisant flock()
//
include_ecrire('inc_flock.php3');


//
// Gerer les valeurs meta
//
function lire_meta($nom) {
	global $meta;
	return $meta[$nom];
}
function lire_meta_maj($nom) {
	global $meta_maj;
	return $meta_maj[$nom];
}

// Lire les meta cachees
if (!defined('_DATA_META_CACHE') AND !defined('_ECRIRE_INC_META')) {
	unset($meta); # parano

	if (file_exists(_DIR_SESSIONS . 'meta_cache.php3'))
		include(_DIR_SESSIONS . 'meta_cache.php3');
	// en cas d'echec refaire le fichier
	if (!is_array($meta) AND _FILE_CONNECT) {

		include_ecrire('inc_meta.php3');
		ecrire_metas();
	}
}

// Verifier la conformite d'une ou plusieurs adresses email
function email_valide($adresse) {
	$adresses = explode(',', $adresse);
	if (is_array($adresses)) {
		while (list(, $adresse) = each($adresses)) {
			// nettoyer certains formats
			// "Marie Toto <Marie@toto.com>"
			$adresse = eregi_replace("^[^<>\"]*<([^<>\"]+)>$", "\\1", $adresse);
			// RFC 822
			if (!eregi('^[^()<>@,;:\\"/[:space:]]+(@([-_0-9a-z]+\.)*[-_0-9a-z]+)?$', trim($adresse)))
				return false;
		}
		return true;
	}
	return false;
}

//
// Traduction des textes de SPIP
//
function _T($text, $args = '') {
	include_ecrire('inc_lang.php3');
	$text = traduire_chaine($text, $args);

	if (!empty($GLOBALS['xhtml'])) {
		include_ecrire("inc_charsets.php3");
		$text = html2unicode($text);
	}

	return $text;
}

// chaines en cours de traduction
function _L($text) {
	if ($GLOBALS['test_i18n'])
		return "<span style='color:red;'>$text</span>";
	else
		return $text;
}

// Langue principale du site
$langue_site = lire_meta('langue_site');
if (!$langue_site) include_ecrire('inc_lang.php3');
$spip_lang = $langue_site;


// Nommage bizarre des tables d'objets
function table_objet($type) {
	if ($type == 'syndic' OR $type == 'forum')
		return $type;
	else
		return $type.'s';
}


//
// Savoir si on peut lancer de gros calculs, et eventuellement poser un lock SQL
// Resultat : true=vas-y ; false=stop
//
function timeout($lock=false, $action=true, $connect_mysql=true) {
	static $ok = true;
	global $db_ok;

	// Fichier lock hebergeur ?  (age maxi, 10 minutes)

	if (@file_exists(_FILE_LOCK)
	AND ((time() - @filemtime(_FILE_LOCK)) < 600))
		return $ok = false;

	// Ne rien faire ?
	if (!$action || !$ok)
		return $ok;

	$ok = false;

	// Base connectee ?
	if ($connect_mysql) {
		include_local(_FILE_CONNECT);
		if (!$db_ok)
			return false;

		// Verrou demande ?
		if ($lock AND !spip_get_lock($lock))
			return false;
	}

	// C'est bon
	return true;
}

//
// Tests sur le nom du butineur
//
function verif_butineur() {
  global $HTTP_USER_AGENT, $browser_name, $browser_version, $browser_description, $browser_rev, $browser_layer ;
	ereg("^([A-Za-z]+)/([0-9]+\.[0-9]+) (.*)$", $HTTP_USER_AGENT, $match);
	$browser_name = $match[1];
	$browser_version = $match[2];
	$browser_description = $match[3];
	$browser_layer = '';

	if (eregi("opera", $browser_description)) {
		eregi("Opera ([^\ ]*)", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 7) ? '' :  http_script('', _DIR_INCLUDE . 'layer.js',''));
	}
	else if (eregi("msie", $browser_description)) {
		eregi("MSIE ([^;]*)", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
		$browser_layer = (($browser_version < 5) ? '' :  http_script('', _DIR_INCLUDE . 'layer.js',''));
	}
	else if (eregi("KHTML", $browser_description) &&
		eregi("Safari/([^;]*)", $browser_description, $match)) {
		$browser_name = "Safari";
		$browser_version = $match[1];
		$browser_layer = http_script('', _DIR_INCLUDE . 'layer.js','');
	}
	else if (eregi("mozilla", $browser_name) AND $browser_version >= 5) {
		$browser_layer = http_script('', _DIR_INCLUDE . 'layer.js','');
		// Numero de version pour Mozilla "authentique"
		if (ereg("rv:([0-9]+\.[0-9]+)", $browser_description, $match))
			$browser_rev = doubleval($match[1]);
		// Autres Gecko => equivalents 1.4 par defaut (Galeon, etc.)
		else if (strpos($browser_description, "Gecko") and !strpos($browser_description, "KHTML"))
			$browser_rev = 1.4;
		// Machins quelconques => equivalents 1.0 par defaut (Konqueror, etc.)
		else $browser_rev = 1.0;
	}

	if (!$browser_name) $browser_name = "Mozilla";
}

//
// spip_timer : on l'appelle deux fois et on a la difference, affichable
//
function spip_timer($t='rien') {
	static $time;
	$a=time(); $b=microtime();

	if (isset($time[$t])) {
		$p = $a + $b - $time[$t];
		unset($time[$t]);
		return sprintf("%.2fs", $p);
	} else
		$time[$t] = $a + $b;
}


//
// qq  fonctions service pour les 2 niveaux
//
function calculer_hierarchie($id_rubrique, $exclure_feuille = false) {
	if (!$id_rubrique)
		return '0';
	if (!$exclure_feuille)
		$hierarchie = ",$id_rubrique";

	do {
		$q = spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_rubrique");
		list($id_rubrique) = spip_fetch_array($q);
		$hierarchie = ",$id_rubrique".$hierarchie;
	} while ($id_rubrique);

	return substr($hierarchie, 1); // Attention ca demarre toujours par '0'
}


//
// Retourne $subdir/ si le sous-repertoire peut etre cree, '' sinon
//

function creer_repertoire($base, $subdir) {
	if (@file_exists("$base/.plat")) return '';
	$path = $base.'/'.$subdir;
	if (@file_exists($path)) return "$subdir/";

	@mkdir($path, 0777);
	@chmod($path, 0777);
	$ok = false;
	if ($f = @fopen("$path/.test", "w")) {
		@fputs($f, '<'.'?php $ok = true; ?'.'>');
		@fclose($f);
		include("$path/.test");
	}
	if (!$ok) {
		$f = @fopen("$base/.plat", "w");
		if ($f)
			fclose($f);
		else {
			redirige_par_entete("spip_test_dirs.php3");
		}
	}
	return ($ok? "$subdir/" : '');
}

function creer_repertoire_documents($ext) {
# est-il bien raisonnable d'accepter de creer si creer_rep retourne '' ?
	$rep = _DIR_DOC . creer_repertoire(_DIR_DOC, $ext);
	if (lire_meta("creer_htaccess") == 'oui')
		verifier_htaccess($rep);
	return $rep;
}

// Pour les documents comme pour les logos, le filtre |fichier donne
// le chemin du fichier apres 'IMG/' ;  peut-etre pas d'une purete
// remarquable, mais a conserver pour compatibilite ascendante.
// -> http://www.spip.net/fr_article901.html

function calcule_fichier_logo($on) {
  $r= ereg_replace("^" . _DIR_IMG, "", $on);
  return $r;
}

function cherche_image_nommee($nom, $formats = array ('gif', 'jpg', 'png')) {
	if (ereg("^../",$nom))	$nom = substr($nom,3);
	if (ereg("^" . _DIR_IMG, $nom)) {
		$nom = substr($nom,strlen(_DIR_IMG));
	}
	$pos = strrpos($nom, "/");
	if ($pos > 0) {
		$chemin = substr($nom, 0, $pos+1);
		$nom = substr($nom, $pos+1);
	} else {
		$chemin = "";
	}

	reset($formats);
	while (list(, $format) = each($formats)) {
		$d = _DIR_IMG . "$chemin$nom.$format";
		if (@file_exists($d)) return array(_DIR_IMG."$chemin", $nom, $format);
	}
}

function redirige_par_entete($url)
{
	header("Location: $url");
	include_ecrire('inc_cron.php3');
#	spip_cron();
	spip_log("redirige $url");
	exit;
}

// Envoi des en-tetes

function debut_entete($title, $entete='')
{
  /* '<html xmlns:m="http://www.w3.org/1998/Math/MathML">'."\n".'<head>'."\n";

//if (eregi("msie", $browser_name)) {
// '<object id="mathplayer" classid="clsid:32F66A20-7614-11D4-BD11-00104BD3F987">'."\n".'</object>'."\n";
// '<'.'?import namespace="m" implementation="#mathplayer"?'.'>'."\n"; 

	$base=lire_meta("adresse_site");
	if (!$base)
		$base = dirname($GLOBALS['HTTP_SERVERS_VARS']['SCRIPT_NAME']);
	else
	  $base .= '/' . (_DIR_RESTREINT ? '' : _DIR_RESTREINT_ABS);
*/
	if (!$entete) {
		if (!$charset = lire_meta('charset')) $charset = 'utf-8';
		$entete = "Content-Type: text/html; charset=$charset";
	}
	@header($entete);
	return "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n" .
	  "<html lang='".$GLOBALS['spip_lang']."' dir='".($GLOBALS['spip_lang_rtl'] ? 'rtl' : 'ltr')."'>\n" .
	  "<head>\n" .
#	  "<base href='$base' />\n" .
	  "<title>$title</title>\n" .
	  "<meta http-equiv='Content-Type' content='text/html; charset=$charset' />\n";
}

function http_script($script, $src='', $noscript='')
{
	return '<script type="text/javascript"'
		. ($src ? " src=\"$src\"" : '')
		. ">"
		. ($script ? "<!--\n$script\n//-->" : '')
		. "</script>\n"
		. (!$noscript ? '' : "<noscript>\n\t$noscript\n</noscript>\n");
}

?>
