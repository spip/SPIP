<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_VERSION")) return;
define("_ECRIRE_INC_VERSION", "1");

//
// Version courante de SPIP
// Stockee sous forme de nombre decimal afin de faciliter les comparaisons
// (utilise pour les modifs de la base de donnees)
//

// version de la base
$spip_version = 1.464;

// version de spip
// (mettre a jour a la main et conserver la mention "CVS")
$spip_version_affichee = "1.4 CVS";

// version de spip / tag
if (ereg('Name: v(.*) ','$Name$', $regs)) $spip_version_affichee = $regs[1];


// Pas de warnings idiots
error_reporting(E_ALL ^ E_NOTICE);

//
// Parametrage du prefixe des tables dans la base de donnees
// (a modifier pour avoir plusieurs sites SPIP dans une seule base)
//

$table_prefix = "spip";


function spip_query_profile($query) {
	static $tt = 0;
	$suite = "";
	if (eregi('[[:space:]](VALUES|WHERE)[[:space:]].*$', $query, $regs)) {
		$suite = $regs[0];
		$query = substr($query, 0, -strlen($suite));
	}
	$query = ereg_replace('([[:space:],])spip_', '\1'.$GLOBALS['table_prefix'].'_', $query) . $suite;
	$m1 = microtime();
	$result = mysql_query($query);
	$m2 = microtime();
	list($usec, $sec) = explode(" ", $m1);
	list($usec2, $sec2) = explode(" ", $m2);
	$dt = $sec2 + $usec2 - $sec - $usec;
	$tt += $dt;
	echo "<small>".htmlentities($query);
	echo " -> <font color='blue'>".sprintf("%3f", $dt)."</font> ($tt)</small><p>\n";
	return $result;
}

function spip_query_debug($query) {
	$suite = "";
	if (eregi('[[:space:]](VALUES|WHERE)[[:space:]].*$', $query, $regs)) {
		$suite = $regs[0];
		$query = substr($query, 0, -strlen($suite));
	}
	$query = ereg_replace('([[:space:],])spip_', '\1'.$GLOBALS['table_prefix'].'_', $query) . $suite;
	$r = mysql_query($query);
	if ($GLOBALS['connect_statut'] == '0minirezo' AND $s = mysql_error()) {
		echo "Erreur dans la requ&ecirc;te : ".htmlentities($query)."<br>";
		echo "&laquo; ".htmlentities($s)." &raquo;<p>";
	}
	return $r;
}

function spip_query($query) {
	// return spip_query_profile($query); // a decommenter pour chronometrer les requetes
	// return spip_query_debug($query); // a decommenter pour afficher toutes les erreurs
	$suite = "";
	if (eregi('[[:space:]](VALUES|WHERE)[[:space:]].*$', $query, $regs)) {
		$suite = $regs[0];
		$query = substr($query, 0, -strlen($suite));
	}
	$query = ereg_replace('([[:space:],])spip_', '\1'.$GLOBALS['table_prefix'].'_', $query) . $suite;
	return mysql_query($query);
}

//
// Infos de version PHP
//

$php_version = explode('.', phpversion());
$php_version_maj = (int) $php_version[0];
$php_version_med = (int) $php_version[1];
if (ereg('([0-9]+)', $php_version[2], $match)) $php_version_min = (int) $match[1];

$flag_function_exists = ($php_version_maj > 3 OR $php_version_min >= 7);
$flag_ignore_user_abort = ($php_version_maj > 3 OR $php_version_min >= 7);
$flag_levenshtein = ($php_version_maj >= 4);
$flag_mt_rand = ($php_version_maj > 3 OR $php_version_min >= 6);
$flag_str_replace = ($php_version_maj > 3 OR $php_version_min >= 8);
$flag_uniqid2 = ($php_version_maj > 3 OR $php_version_min >= 13);
$flag_strpos_3 = (@strpos('baba', 'a', 2) == 3);
$flag_get_cfg_var = (@get_cfg_var('error_reporting') != "");

if ($flag_function_exists) {
	$flag_ini_get = (function_exists("ini_get")
		&& (@ini_get('max_execution_time') > 0));	// verifier pas desactivee
	$flag_gz = function_exists("gzopen");
	$flag_ob = ($flag_ini_get
		&& !ereg("ob_", ini_get('disable_functions'))
		&& function_exists("ob_start"));
	$flag_obgz = ($flag_ob && function_exists("ob_gzhandler"));
	$flag_preg_replace = function_exists("preg_replace");
	$flag_crypt = function_exists("crypt");
	$flag_wordwrap = function_exists("wordwrap");
	$flag_apc = function_exists("apc_rm");
	$flag_sapi_name = function_exists("php_sapi_name");
	$flag_utf8_decode = function_exists("utf8_decode");
}
else {
	$flag_ini_get = false;
	$flag_gz = false;
	$flag_obgz = false;
	$flag_ob = false;
	$flag_preg_replace = false;
	$flag_crypt = true; // la non-existence de crypt est une exception
	$flag_wordwrap = false;
	$flag_apc = false;
	$flag_sapi_name = false;
}


//
// Magic quotes : on n'en veut pas sur la base,
// et on nettoie les GET/POST/COOKIE le cas echeant
//

function magic_unquote($table) {
	if (is_array($GLOBALS[$table])) {
	        reset($GLOBALS[$table]);
	        while (list($key, $val) = each($GLOBALS[$table])) {
	        	if (is_string($val))
		                $GLOBALS[$key] = $GLOBALS[$table][$key] = stripslashes($val);
	        }
	}
}

$flag_magic_quotes = ($php_version_maj > 3 OR $php_version_min >= 6);
if ($flag_magic_quotes) {
	@set_magic_quotes_runtime(0);
	$unquote_gpc = get_magic_quotes_gpc();
}
else $unquote_gpc = true;

if ($unquote_gpc) {
	magic_unquote('HTTP_GET_VARS');
	magic_unquote('HTTP_POST_VARS');
	magic_unquote('HTTP_COOKIE_VARS');
}


//
// Dirty hack contre le register_globals a 'Off' (PHP 4.1.x)
// A remplacer par une gestion propre des variables admissibles ;-)
//

function feed_globals($table) {
	if (is_array($GLOBALS[$table])) {
	        reset($GLOBALS[$table]);
	        while (list($key, $val) = each($GLOBALS[$table])) {
	                $GLOBALS[$key] = $val;
	        }
	}
}

feed_globals('HTTP_GET_VARS');
feed_globals('HTTP_POST_VARS');
feed_globals('HTTP_COOKIE_VARS');
feed_globals('HTTP_SERVER_VARS');


//
// Avec register_globals a Off sous PHP4, il faut utiliser
// la nouvelle variable HTTP_POST_FILES pour les fichiers uploades
// (pas valable sous PHP3...)
//

function feed_post_files($table) {
	if (is_array($GLOBALS[$table])) {
	        reset($GLOBALS[$table]);
	        while (list($key, $val) = each($GLOBALS[$table])) {
	                $GLOBALS[$key] = $val['tmp_name'];
	                $GLOBALS[$key.'_name'] = $val['name'];
	        }
	}
}

feed_post_files('HTTP_POST_FILES');


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

if (!$PATH_TRANSLATED) {
	if ($SCRIPT_FILENAME) $PATH_TRANSLATED = $SCRIPT_FILENAME;
	else if ($DOCUMENT_ROOT && $SCRIPT_URL) $PATH_TRANSLATED = $DOCUMENT_ROOT.$SCRIPT_URL;
}



//
// Gestion des inclusions et infos repertoires
//

$included_files = '';
$flag_ecrire = ereg('^[^?]*/ecrire/[^/]*', $REQUEST_URI)
	|| ereg('/ecrire/[^/]*', $SCRIPT_URL);

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

if ($flag_obgz) {
	$use_gz = true;

	// si un buffer est deja ouvert, stop
	if (ob_get_contents())
		$use_gz = false;

	// special bug de proxy
	if (eregi("NetCache|Hasd_proxy", $HTTP_VIA)) {
		$use_gz = false;
	}
	// special bug Netscape Win 4.0x
	if (eregi("Mozilla/4\.0[^ ].*Win", $HTTP_USER_AGENT)) {
		$use_gz = false;
	}

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

	function getForm($method = 'GET', $anchor = '', $enctype = '') {
		include_ecrire("inc_filtres.php3");
		if ($anchor) $anchor = '#'.$anchor;
		$form = "<form method='$method' action='".$this->file.$anchor."'";
		if ($enctype) $form .= " enctype='$enctype'";
		$form .= ">\n";
		$vars = $this->getAllVars();
		if (is_array($vars)) {
			reset($vars);
			while (list($name, $value) = each($vars)) {
				$form .= "<input type=\"hidden\" name=\"$name\" value=\"".entites_html($value)."\">\n";
			}
		}
		if (is_array($this->arrays)) {
			reset($this->arrays);
			while (list($name, $table) = each($this->vars)) {
				reset($table);
				while (list(, $value) = each($table)) {
					$form .= "<input type=\"hidden\" name=\"".$name."[]\" value=\"".entites_html($value)."\">\n";
				}
			}
		}
		return $form;
	}

	//
	// Recuperer l'attribut href="<URL>" correspondant au lien
	//

	function getHref() {
		return 'href="'.$this->getUrl().'"';
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

//
// Lien vers la page demandee et lien nettoye ne contenant que des id_objet
//
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
// Verifier la conformite d'une adresse email
//

function email_valide($adresse) {
	if (strpos($adresse,',')) {					// autoriser plusieurs emails
		$valide = true;
		$lesadresses = split(',', $adresse);
		while (list(,$ad) = each($lesadresses))
			$valide &= email_valide($ad);
		return $valide;
	}

	return (eregi( 
		'^[-!#$%&\'*+\\./0-9=?a-z^_`{|}~]+'.	// nom d'utilisateur
		'@'.									// @
		'([-0-9a-z]+\.)+' .						// hote, sous-domaine
		'([0-9a-z]){2,4}$',						// tld 
		trim($adresse)));
} 


?>
