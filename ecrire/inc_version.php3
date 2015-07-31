<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_VERSION")) return;
define("_ECRIRE_INC_VERSION", "1");


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

function feed_globals($table, $insecure = true) {
	global $INSECURE;
	if (is_array($GLOBALS[$table])) {
	        reset($GLOBALS[$table]);
	        while (list($key, $val) = each($GLOBALS[$table])) {
			$GLOBALS[$key] = $val;
			if ($insecure) $INSECURE[$key] = $val;
	        }
	}
}

feed_globals('HTTP_COOKIE_VARS');
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
// dans ecrire/mes_options.php3. Les valeurs specifiees
// dans ce dernier fichier remplaceront automatiquement
// les valeurs ci-dessous.
//
// Pour creer ecrire/mes_options.php3 : recopier simplement
// les lignes ci-dessous, et ajouter le marquage de debut et
// de fin de fichier PHP ("< ?php" et "? >", sans les espaces)
//

// Prefixe des tables dans la base de donnees
// (a modifier pour avoir plusieurs sites SPIP dans une seule base)
$table_prefix = "spip";

// Prefixe des cookies
// (a modifier pour installer des sites SPIP dans des sous-repertoires)
$cookie_prefix = "spip";

// Dossier des squelettes
// (a modifier si l'on veut passer rapidement d'un jeu de squelettes a un autre)
$dossier_squelettes = "";

// faut-il autoriser SPIP a compresser les pages a la volee quand le
// navigateur l'accepte (valable pour apache 1.3 seulement) ?
$auto_compress = true;

// faut-il loger les infos de debug dans data/spip.log ?  (peu utilise)
$debug = false;

// faut-il passer les connexions MySQL en mode debug ?
$mysql_debug = false;

// faut-il chronometrer les requetes MySQL ?
$mysql_profile = false;

// faut-il faire des connexions completes rappelant le nom du serveur et de
// la base MySQL ? (utile si vos squelettes appellent d'autres bases MySQL)
$mysql_rappel_connexion = false;

// faut-il afficher en rouge les chaines non traduites ?
$test_i18n = false;

// gestion des extras (voir ecrire/inc_extra.php3 pour plus d'informations)
$champs_extra = false;
$champs_extra_proposes = false;


//
// 	*** Fin du parametrage ***
//


$flag_ecrire = !file_exists('./ecrire/inc_version.php3');

if ($flag_ecrire) {
	if (file_exists('mes_options.php3')) {
		include('mes_options.php3');
	}
} else {
	if (file_exists('ecrire/mes_options.php3')) {
		include('ecrire/mes_options.php3');
	}
}


// Version courante de SPIP
// Stockee sous forme de nombre decimal afin de faciliter les comparaisons
// (utilise pour les modifs de la base de donnees)

// version de la base
$spip_version = 1.721;

// version de spip
$spip_version_affichee = "1.7b1 CVS";

// version de spip / tag cvs
if (ereg('Name: v(.*) ','$Name$', $regs)) $spip_version_affichee = $regs[1];


// Pas de warnings idiots
error_reporting(E_ALL ^ E_NOTICE);

// ** Securite **
$auteur_session = '';
$connect_statut = '';
$hash_recherche = '';


//
// Infos de version PHP
// (doit etre au moins egale a 3.0.8)
//

$php_version = explode('.', phpversion());
$php_version_maj = intval($php_version[0]);
$php_version_med = intval($php_version[1]);
if (ereg('([0-9]+)', $php_version[2], $match)) $php_version_min = intval($match[1]);

$flag_levenshtein = ($php_version_maj >= 4);
$flag_uniqid2 = ($php_version_maj > 3 OR $php_version_min >= 13);
$flag_get_cfg_var = (@get_cfg_var('error_reporting') != "");
$flag_strtr2 = ($php_version_maj > 3);

$flag_ini_get = (function_exists("ini_get")
	&& (@ini_get('max_execution_time') > 0));	// verifier pas desactivee
$flag_gz = function_exists("gzopen");
$flag_ob = ($flag_ini_get
	&& !ereg("ob_", ini_get('disable_functions'))
	&& function_exists("ob_start"));
$flag_obgz = ($flag_ob && function_exists("ob_gzhandler"));
$flag_pcre = function_exists("preg_replace");
$flag_crypt = function_exists("crypt");
$flag_wordwrap = function_exists("wordwrap");
$flag_apc = function_exists("apc_rm");
$flag_sapi_name = function_exists("php_sapi_name");
$flag_utf8_decode = function_exists("utf8_decode");
$flag_ldap = function_exists("ldap_connect");
$flag_flock = function_exists("flock");
$flag_ImageCreateTrueColor = function_exists("ImageCreateTrueColor") && function_exists("gd_info");
$flag_ImageCopyResampled = function_exists("ImageCopyResampled") && function_exists("gd_info");
$flag_ImageGif = function_exists("ImageGif");
$flag_ImageJpeg = function_exists("ImageJpeg");
$flag_ImagePng = function_exists("ImagePng");
$flag_multibyte = function_exists("mb_encode_mimeheader");
$flag_iconv = function_exists("iconv");
$flag_strtotime = function_exists("strtotime");

$flag_gd = $flag_ImageGif || $flag_ImageJpeg || $flag_ImagePng;



//
// Appliquer le prefixe cookie
//
function spip_setcookie ($name='', $value='', $expire=0, $path='', $domain='', $secure='') {
	$name = ereg_replace ('^spip', $GLOBALS['cookie_prefix'], $name);

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
if ($cookie_prefix <> 'spip') {
	reset ($HTTP_COOKIE_VARS);
	while (list($name,$value) = each($HTTP_COOKIE_VARS)) {
		if (ereg('^spip', $name)) {
			unset($HTTP_COOKIE_VARS[$name]);
			unset($$name);
		}
	}
	reset ($HTTP_COOKIE_VARS);
	while (list($name,$value) = each($HTTP_COOKIE_VARS)) {
		if (ereg('^'.$cookie_prefix, $name)) {
			$spipname = ereg_replace ('^'.$cookie_prefix, 'spip', $name);
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
// Online
else if (ereg('pro\.proxad\.net$', $HTTP_HOST) || ereg('pro\.proxad\.net$', $SERVER_NAME)) {
	$hebergeur = 'online';
}
// NexenServices
else if ($SERVER_ADMIN == 'www@nexenservices.com') {
	if (!function_exists('email'))
		include ('mail.inc');
	$hebergeur = 'nexenservices';
}

if (eregi('\(Win', $HTTP_SERVER_VARS['SERVER_SOFTWARE']))
	$os_serveur = 'windows';

// Droits d'acces maximum par defaut
@umask(0);


//
// Infos sur le fichier courant
//

// Compatibilite avec serveurs ne fournissant pas $REQUEST_URI
if (!$REQUEST_URI) {
	$REQUEST_URI = $PHP_SELF;
	if (!strpos($REQUEST_URI, '?') && $QUERY_STRING)
		$REQUEST_URI .= '?'.$QUERY_STRING;
}
$dir_ecrire = (ereg("/ecrire/", $GLOBALS['REQUEST_URI'])) ? '' : 'ecrire/';

if (!$PATH_TRANSLATED) {
	if ($SCRIPT_FILENAME) $PATH_TRANSLATED = $SCRIPT_FILENAME;
	else if ($DOCUMENT_ROOT && $SCRIPT_URL) $PATH_TRANSLATED = $DOCUMENT_ROOT.$SCRIPT_URL;
}



//
// Gestion des inclusions et infos repertoires
//

$included_files = '';

function include_local($file) {
	if ($GLOBALS['included_files'][$file]) return;
	include($file);
	$GLOBALS['included_files'][$file] = 1;
}

function include_ecrire($file) {
	if (!$GLOBALS['flag_ecrire']) $file = "ecrire/$file";
	if ($GLOBALS['included_files'][$file]) return;
	include($file);
	$GLOBALS['included_files'][$file] = 1;
}


$flag_connect = file_exists(($flag_ecrire ? "" : "ecrire/")."inc_connect.php3");

function spip_query($query) {
	if ($GLOBALS['flag_connect']) {
		include_ecrire("inc_connect.php3");
		if (!$GLOBALS['db_ok'])
			return;
		if ($GLOBALS['spip_connect_version'] < 0.1) {
			if (!$GLOBALS['flag_ecrire']) {
				$GLOBALS['db_ok'] = false;
				return;
			}
			@Header("Location: upgrade.php3?reinstall=oui");
			exit;
		}
	}
	return spip_query_db($query);
}


//
// Infos de config PHP
//

$php_module = (($flag_sapi_name AND @php_sapi_name() == 'apache') OR
	ereg("^Apache.* PHP", $SERVER_SOFTWARE));

$flag_upload = (!$flag_get_cfg_var || (get_cfg_var('upload_max_filesize') > 0));


function tester_upload() {
	return $GLOBALS['flag_upload'];
}

function tester_accesdistant() {
	global $hebergeur;
	$test_acces = true;
	return $test_acces;
}


//
// Reglage de l'output buffering : si possible, generer une sortie
// compressee pour economiser de la bande passante
//

if ($auto_compress && $flag_obgz) {
	$use_gz = true;

	// si un buffer est deja ouvert, stop
	if (ob_get_contents())
		$use_gz = false;

	// si la compression est deja commencee, stop
	else if (@ini_get("zlib.output_compression") || @ini_get("output_handler"))
		$use_gz = false;

	// special bug de proxy
	else if (eregi("NetCache|Hasd_proxy", $HTTP_VIA))
		$use_gz = false;

	// special bug Netscape Win 4.0x
	else if (eregi("Mozilla/4\.0[^ ].*Win", $HTTP_USER_AGENT))
		$use_gz = false;

	// special bug Apache2x + php4.2.x (versions a preciser eventuellement si on retrouve)
	else if (eregi("Apache(-[^ ]+)?/2", $SERVER_SOFTWARE))
		$use_gz = false;

	if ($use_gz) {
		@ob_start("ob_gzhandler");
	}
	@header("Vary: Cookie, Accept-Encoding");
}
else @header("Vary: Cookie");


class Link {
	var $file;
	var $vars;
	var $arrays;
	var $s_vars;
	var $t_vars, $t_var_idx, $t_var_cnt;

	//
	// Contructeur : a appeler soit avec l'URL du lien a creer,
	// soit sans parametres, auquel cas l'URL est l'URL courante
	//
	function Link($url = '', $reentrant = false) {
		static $link = '';

		// If root link not defined, create it
		if (!$link && !$reentrant) {
			$link = new Link('', true);
		}

		$this->vars = array();
		$this->s_vars = array();
		$this->t_vars = array();
		$this->t_var_idx = array();
		$this->t_var_cnt = 0;

		// Normal case
		if ($link) {
			$this->s_vars = $link->s_vars;
			$this->t_vars = $link->t_vars;
			$this->t_var_idx = $link->t_var_idx;
			$this->t_var_cnt = $link->t_var_cnt;
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

		// Special case : create root link

		// If no URL specified, take current one
		if (!$url) {
			$url = $GLOBALS['REQUEST_URI'];
			$url = substr($url, strrpos($url, '/') + 1);
			if (!$url) $url = "./";
			if (count($GLOBALS['HTTP_POST_VARS'])) $vars = $GLOBALS['HTTP_POST_VARS'];
		}
		$v = split('[\?\&]', $url);
		list(, $this->file) = each($v);

		// GET variables are read from the original URL
		// (HTTP_GET_VARS may contain additional variables introduced by rewrite-rules)
		if (!$vars) {
			while (list(, $var) = each($v)) {
				list($name, $value) = split('=', $var, 2);
				$name = urldecode($name);
				$value = urldecode($value);
				if (ereg('^(.*)\[\]$', $name, $regs)) {
					$vars[$regs[1]][] = $value;
				}
				else {
					$vars[$name] = $value;
				}
			}
		}

		if (is_array($vars)) {
			reset($vars);
			while (list($name, $value) = each($vars)) {
				$p = substr($name, 0, 2);
				if ($p == 's_') {
					$this->s_vars[$name] = $value;
				}
				else if ($p == 't_') {
					$this->_addTmpHash($name, $value);
				}
				else {
					if (is_array($value))
						$this->arrays[$name] = $value;
					else
						$this->vars[$name] = $value;
				}
			}
		}
	}

	function _addTmpHash($name, $value) {
		if ($i = $this->t_var_idx[$name]) {
			$this->t_vars[--$i] = $value;
		}
		else {
			$this->t_vars[$this->t_var_cnt] = $value;
			$this->t_var_idx[$name] = ++$this->t_var_cnt;
			if ($this->t_var_cnt >= 5) $this->t_var_cnt = 0;
		}
	}

	//
	// Effacer toutes les variables
	//

	function clearVars() {
		$this->vars = '';
		$this->arrays = '';
	}

	//
	// Effacer une variable
	//

	function delVar($name) {
		unset($this->vars[$name]);
		unset($this->arrays[$name]);
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

	function getVar($name) {
		return $this->vars[$name];
	}

	//
	// Ajouter une variable de session
	// (variable dont la valeur est transmise d'un lien a l'autre)
	//

	function addSessionVar($name, $value) {
		$this->addVar('s_'.$name, $value);
	}

	function getSessionVar($name) {
		return $this->vars['s_'.$name];
	}

	//
	// Ajouter une variable temporaire
	// (variable dont le nom est arbitrairement long, et dont la valeur
	// est transmise de lien en lien dans la limite de cinq variables)
	//

	function addTmpVar($name, $value) {
		$this->_addTmpHash('t_'.substr(md5($name), 0, 4), $value);
	}

	function getTmpVar($name) {
		if ($i = $this->t_var_idx['t_'.substr(md5($name), 0, 4)]) {
			return $this->t_vars[--$i];
		}
	}

	function getAllVars() {
		if (is_array($this->t_var_idx)) {
			reset($this->t_var_idx);
			while (list($name, $i) = each($this->t_var_idx)) $vars[$name] = $this->t_vars[--$i];
		}
		if (is_array($this->vars)) {
			reset($this->vars);
			while (list($name, $value) = each($this->vars)) $vars[$name] = $value;
		}
		if (is_array($this->s_vars)) {
			reset($this->s_vars);
			while (list($name, $value) = each($this->s_vars)) $vars[$name] = $value;
		}
		return $vars;
	}

	//
	// Recuperer l'URL correspondant au lien
	//

	function getUrl($anchor = '') {
		$url = $this->file;
		$query = '';
		$vars = $this->getAllVars();
		if (is_array($vars)) {
			$first = true;
			reset($vars);
			while (list($name, $value) = each($vars)) {
				$query .= (($query) ? '&' : '?').$name.'='.urlencode($value);
			}
		}
		if (is_array($this->arrays)) {
			reset($this->arrays);
			while (list($name, $table) = each($this->arrays)) {
				reset($table);
				while (list(, $value) = each($table)) {
					$query .= (($query) ? '&' : '?').$name.'[]='.urlencode($value);
				}
			}
		}
		if ($anchor) $anchor = '#'.$anchor;
		return $url.$query.$anchor;
	}

	//
	// Recuperer le debut de formulaire correspondant au lien
	// (tag ouvrant + entrees cachees representant les variables)
	//

	function getForm($method = 'get', $anchor = '', $enctype = '') {
		if ($anchor) $anchor = '#'.$anchor;
		$form = "<form method='$method' action='".$this->file.$anchor."'";
		if ($enctype) $form .= " enctype='$enctype'";
		$form .= ">\n";
		$vars = $this->getAllVars();
		if (is_array($vars)) {
			reset($vars);
			while (list($name, $value) = each($vars)) {
				$value = ereg_replace('&amp;(#[0-9]+;)', '&\1', htmlspecialchars($value));
				$form .= "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
			}
		}
		if (is_array($this->arrays)) {
			reset($this->arrays);
			while (list($name, $table) = each($this->vars)) {
				reset($table);
				while (list(, $value) = each($table)) {
					$value = ereg_replace('&amp;(#[0-9]+;)', '&\1', htmlspecialchars($value));
					$form .= "<input type=\"hidden\" name=\"".$name."[]\" value=\"$value\" />\n";
				}
			}
		}
		return $form;
	}

	//
	// Recuperer l'attribut href="<URL>" correspondant au lien
	//

	function getHref($anchor = '') {
		return 'href="'.$this->getUrl($anchor).'"';
	}
}

//
// Creer un lien et retourner directement le href="<URL>" correspondant
//

function newLinkHref($url) {
	$link = new Link($url);
	return $link->getHref();
}

function newLinkUrl($url) {
	$link = new Link($url);
	return $link->getUrl();
}

//
// Recuperer la valeur d'une variable de session sur la page courante
//

function getSessionVar($name) {
	return $GLOBALS['this_link']->getSessionVar($name);
}

//
// Recuperer la valeur d'une variable temporaire sur la page courante
//

function getTmpVar($name) {
	return $GLOBALS['this_link']->getTmpVar($name);
}


// Lien vers la page demandee et lien nettoye ne contenant que des id_objet

$this_link = new Link();

$clean_link = $this_link;

if (count($GLOBALS['HTTP_POST_VARS'])) {
	$clean_link->clearVars();
	$vars = array('id_article', 'coll', 'id_breve', 'id_rubrique', 'id_syndic', 'id_mot', 'id_auteur'); // il en manque probablement ?
	while (list(,$var) = each($vars)) {
		if (isset($$var)) {
			$clean_link->addVar($var, $$var);
			break;
		}
	}
}

//
// Lire les meta cachees
//
$inc_meta_cache = ($flag_ecrire ? '' : 'ecrire/').'data/inc_meta_cache.php3';
if (file_exists($inc_meta_cache) AND !defined('_ECRIRE_INC_META_CACHE')  AND !defined('_ECRIRE_INC_META')) {
	include_ecrire('data/inc_meta_cache.php3');
}
if (!defined("_ECRIRE_INC_META_CACHE")) {
	function lire_meta($nom) {
		global $meta;
		return $meta[$nom];
	}
	function lire_meta_maj($nom) {
		global $meta_maj;
		return $meta_maj[$nom];
	}
	define("_ECRIRE_INC_META_CACHE", "1");
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
	return traduire_chaine($text, $args);
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

//
// Enregistrement des evenements
//
function spip_log($message) {
	global $flag_ecrire;

	$pid = '(pid '.@getmypid().')';
	if (!$ip = $GLOBALS['REMOTE_ADDR']) $ip = '-';

	$message = date("M d H:i:s")." $ip $pid "
		.ereg_replace("\n*$", "\n", $message);

	$logfile = ($flag_ecrire ? "" : "ecrire/") . "data/spip.log";
	if (@filesize($logfile) > 10*1024) {
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
	}
}

//
// Savoir si on peut lancer de gros calculs, et eventuellement poser un lock
// Resultat : true=vas-y ; false=stop
//
function timeout($lock=false, $action=true, $connect_mysql=true) {
	static $ok = true;
	global $db_ok, $dir_ecrire;

	// Fichier lock hebergeur ?  (age maxi, 10 minutes)
	$timeoutfile = $dir_ecrire.'data/lock';
	if (@file_exists($timeoutfile)
	AND ((time() - @filemtime($timeoutfile)) < 600)) {
		spip_debug ("lock hebergeur $timeoutfile");
		return $ok = false;
	}

	// Ne rien faire ?
	if (!$action || !$ok)
		return $ok;

	$ok = false;

	// Base connectee ?
	if ($connect_mysql) {
		include_ecrire('inc_connect.php3');
		if (!$db_ok)
			return false;

		// Verrou demande ?
		if ($lock) {
			spip_debug("test lock mysql $lock");
			if (!spip_get_lock($lock)) {
				spip_debug ("lock mysql $lock");
				return false;
			}
		}
	}

	// C'est bon
	return true;
}

//
// Tests sur le nom du butineur
//
function verif_butineur() {
	global $HTTP_USER_AGENT, $browser_name, $browser_version, $browser_description, $browser_rev;
	ereg("^([A-Za-z]+)/([0-9]+\.[0-9]+) (.*)$", $HTTP_USER_AGENT, $match);
	$browser_name = $match[1];
	$browser_version = $match[2];
	$browser_description = $match[3];

	if (eregi("opera", $browser_description)) {
		eregi("Opera ([^\ ]*)", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
	}
	else if (eregi("msie", $browser_description)) {
		eregi("MSIE ([^;]*)", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
	}
	else if (eregi("mozilla", $browser_name) AND $browser_version >= 5) {
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


function spip_debug($message) {
	if ($GLOBALS['debug'])
		spip_log($message);
}


// En mode debug, logger l'URI appelante (pas efficace, c'est vraiment pour debugguer !)
if ($debug)
	spip_debug("$REQUEST_METHOD: ".$GLOBALS['REQUEST_URI']);

?>
