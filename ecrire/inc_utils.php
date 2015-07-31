<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Gestion des inclusions et infos repertoires
//

$included_files = array();

function include_local($file, $silence=false) {
	$nom = preg_replace("/\.php[3]?$/",'', $file);
#	spip_log("'$nom' '$file'");
	if (@$GLOBALS['included_files'][$nom]++) return;
	if (is_readable($f = $nom . '.php'))
	  include($f);
	else if (is_readable($f = $nom . _EXTENSION_PHP))
	  include($f);
	else if (!$silence) spip_log($file . " illisible");
}

function include_ecrire($file) {
# Hack pour etre compatible avec les mes_options qui appellent cette fonction
	define_once('_DIR_INCLUDE', _DIR_RESTREINT);
	include_local(_DIR_INCLUDE . $file);
}

// charge un fichier perso ou, a defaut, standard
// et retourne si elle existe le nom de la fonction homonyme, ou de prefixe _dist

function include_fonction($nom) {
# Hack pour etre compatible avec les mes_options qui appellent cette fonction
	define_once('_DIR_INCLUDE', _DIR_RESTREINT);
# vieillerie	$nom = preg_replace("/\.php[3]?$/",'', basename($nom));
	$inc = ("exec_" . $nom);
	$f = find_in_path($inc  . '.php');
	if ($f && is_readable($f)) {
		if (!$GLOBALS['included_files'][$f]++) include($f);
	} else {
		$f = _DIR_INCLUDE . $inc . '.php';
		if (is_readable($f)) {
			if (!$GLOBALS['included_files'][$f]++) include($f);
		} else {
		    $inc = "";
		}
	}
	$f = $nom;
	if (function_exists($f))
		return $f;
	elseif (function_exists($f .= "_dist"))
		return $f;
	else {
	  spip_log("fonction $nom indisponible" .
		   ($inc ? "" : "(aucun fichier exec_$nom disponible)"));
	  exit;
	}
}

// un pipeline est lie a une action et une valeur
// chaque element du pipeline est autorise a modifier la valeur
//
// le pipeline execute les elements disponibles pour cette action,
// les uns apres les autres, et retourne la valeur finale
//
// Cf. compose_filtres dans inc-compilo-index.php3, qui est le
// pendant "compilŽ" de cette fonctionnalite

function pipeline($action, $val) {
	global $spip_pipeline, $spip_matrice;
	static $pipe = array();
	if (!strlen($spip_pipeline[$action])) return $val;

	// Analyser le pipeline
	// TODO - traiter les arguments  |filtre{arg1,arg2}
	// TODO - traiter le filtre test |?{true,false}
	if (!isset($pipe[$action]))
		$pipe[$action] = array_filter(explode('|',$spip_pipeline[$action]));

	// Eclater le pipeline en filtres et appliquer chaque filtre
	foreach ($pipe[$action] as $fonc) {

		// fonction
		if (function_exists($fonc))
			$val = call_user_func($fonc, $val);

		// Class::Methode
		else if (preg_match("/^(\w*)::(\w*)$/", $fonc, $regs)                            
		AND $methode = array($regs[1], $regs[2])
		AND is_callable($methode))
			$val = call_user_func($methode, $val);

		// Charger un fichier
		else if ($f = $spip_matrice[$fonc]) {
			require_once($f);
			// fonction (2eme chance)
			if (function_exists($fonc))
				$val = call_user_func($fonc, $val);
			// Class::Methode (2eme chance)
			else if (preg_match("/^(\w*)::(\w*)$/", $fonc, $regs)                            
			AND $methode = array($regs[1], $regs[2])
			AND is_callable($methode))
				$val = call_user_func($methode, $val);
			else
				spip_log("Erreur - '$fonc' non definie !");
		}
	}

	return $val;
}


//
// Enregistrement des evenements
//
function spip_log($message, $logname='spip') {
	static $compteur;
	if ($compteur++ > 100) return;

	$pid = '(pid '.@getmypid().')';
	if (!$ip = $GLOBALS['REMOTE_ADDR']) $ip = '-';

	$message = date("M d H:i:s")." $ip $pid "
		.preg_replace("/\n*$/", "\n", $message);

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
	}

	// recopier les spip_log mysql (ce sont uniquement des erreurs)
	// dans le spip_log general
	if ($logname == 'mysql')
		spip_log($message);
}


// API d'appel a la base de donnees
function spip_query($query) {

	// Remarque : si on est appele par l'install,
	// la connexion initiale a ete faite avant
	if (!$GLOBALS['db_ok']) {
		// Essaie de se connecter
		if (_FILE_CONNECT)
			include_local(_FILE_CONNECT);
	}

	// Erreur de connexion
	if (!$GLOBALS['db_ok'])
		return;

	// Vieux format de fichier connexion
	// Note: la version 0.1 est compatible avec la 0.2 (mais elle gere
	// moins bien les erreurs timeout sur SQL), on ne force donc pas l'upgrade
	if ($GLOBALS['spip_connect_version'] < 0.1) {
		if (!_DIR_RESTREINT) {$GLOBALS['db_ok'] = false; return;}
		redirige_par_entete(
			generer_url_ecrire('upgrade', 'reinstall=oui', true));
	}

	// Faire la requete
	return spip_query_db($query);
}


class Link {
	var $file;
	var $vars;
	var $arrays;

	//
	// Contructeur : a appeler soit avec l'URL du lien a creer,
	// soit sans parametres, auquel cas l'URL est l'URL courante
	//
	// parametre $root = demander un lien a partir de la racine du serveur /
	function Link($url = '', $root = false) {
		global $_POST;
		static $link = '';

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
					if (preg_match(',^(.*)\[\]$,', $name, $regs)) {
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
			// (_GET may contain additional variables
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
			if (!$root) $url = substr($url, $v + 1);
			if (!$url) $url = "./";
			if (count($_POST)) {
				$vars = array();
				foreach ($_POST as $var => $val)
					if (preg_match('/^id_/', $var))
						$vars[$var] = $val;
			}
		}
		$v = split('[\?\&]', $url);
		list(, $this->file) = each($v);
		if (!$vars) {
			while (list(,$var) = each($v)) {
				list($name, $value) = split('=', $var, 2);
				$name = urldecode($name);
				$value = urldecode($value);
				if (preg_match(',^(.*)\[\]$,', $name, $regs))
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
					'var_mode|show_docs')
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
		if(isset($this->vars[$name])) unset($this->vars[$name]);
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
		foreach($this->vars as $name => $value) {
			$query .= '&'.$name;
			if (strlen($value))
				$query .= '='.urlencode($value);
		}

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

	function getForm($method = 'get', $query = '', $enctype = '') {
		if (preg_match(',^[a-z],i', $query))
			$action = $query;
		else
			$action = $this->file.$query;

		$form = "<form method='$method' action='"
		.quote_amp($action)."'";
		if ($enctype) $form .= " enctype='$enctype'";
		$form .= " style='border: 0px; margin: 0px;'>\n";
		foreach ($this->vars as $name => $value) {
			$value = preg_replace(',&amp;(#[0-9]+;),', '&\1',
				htmlspecialchars($value));
			$form .= "<input type=\"hidden\" name=\"$name\" "
				. "value=\"$value\" />\n";
		}
		foreach ($this->arrays as $name => $table)
		foreach ($table as $value) {
			$value = preg_replace(',&amp;(#[0-9]+;),', '&\1',
				htmlspecialchars($value));
			$form .= "<input type=\"hidden\" name=\"".$name."[]\" "
				. "value=\"".$value."\" />\n";
		}
		return $form;
	}
}


//
// Gerer les valeurs meta 
//
// Fonction lire_meta abandonnee, remplacee par son contenu. Ne plus utiliser
function lire_meta($nom) {
	global $meta;
	return $meta[$nom];
}


//
// Traduction des textes de SPIP
//
function _T($texte, $args = '') {
	include_ecrire('inc_lang');
	$text = traduire_chaine($texte, $args);
	if (!empty($GLOBALS['xhtml'])) {
		include_ecrire("inc_charsets");
		$text = html2unicode($text, true /* secure */);
	}

	return $text ? $text : 
	  // pour les chaines non traduites
	  (($n = strpos($texte,':')) === false ? $texte :
	   substr($texte, $n+1));
}

// chaines en cours de traduction
function _L($text) {
	if ($GLOBALS['test_i18n'])
		return "<span style='color:red;'>$text</span>";
	else
		return $text;
}


// Nommage bizarre des tables d'objets
function table_objet($type) {
	if ($type == 'site' OR $type == 'syndic')
		return 'syndic';
	else if ($type == 'forum')
		return 'forum';
	else
		return $type.'s';
}
function id_table_objet($type) {
	if ($type == 'site' OR $type == 'syndic')
		return 'id_syndic';
	else if ($type == 'forum')
		return 'id_forum';
	else
		return 'id_'.$type;
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


// spip_touch : verifie si un fichier existe et n'est pas vieux (duree en s)
// et le cas echeant le touch() ; renvoie true si la condition est verifiee
// et fait touch() sauf si ca n'est pas souhaite
// (regle aussi le probleme des droits sur les fichiers touch())
function spip_touch($fichier, $duree=0, $touch=true) {
	if (!($exists = @file_exists($fichier))
	|| ($duree == 0)
	|| (@filemtime($fichier) < time() - $duree)) {
		if ($touch) {
			if (!@touch($fichier)) { @unlink($fichier); @touch($fichier); };
			if (!$exists) @chmod($fichier, 0666);
		}
		return true;
	}
	return false;
}

// Pour executer des taches de fond discretement, on utilise background-image
// car contrairement a un iframe vide, les navigateurs ne diront pas qu'ils
// n'ont pas fini de charger, c'est plus rassurant.
// C'est aussi plus discret qu'un <img> sous un navigateur non graphique.
// Cette fonction est utilisee pour l'espace prive (cf inc_presentation)
// et pour l'espace public (cf #SPIP_CRON dans inc_balise)

function generer_spip_cron() {
  return '<div style="background-image: url(\'' . generer_url_action('cron') .
	'\');"></div>';
}

// envoi de l'image demandee dans le code ci-dessus

function spip_action_cron() {

	$image = pack("H*", "47494638396118001800800000ffffff00000021f90401000000002c0000000018001800000216848fa9cbed0fa39cb4da8bb3debcfb0f86e248965301003b");
	header("Content-Type: image/gif");
	header("Content-Length: ".strlen($image));
	header("Cache-Control: no-cache,no-store");
	header("Pragma: no-cache");
	header("Connection: close");
	echo $image;
	flush();
	cron (1);
}

//
// cron() : execution des taches de fond
// quand il est appele par inc-public.php il n'est pas gourmand;
// quand il est appele par spip_action.php, il est gourmand 

function cron ($gourmand=false) {

	// Si on est gourmand, ou si le fichier gourmand n'existe pas
	// (ou est trop vieux -> 60 sec), on va voir si un cron est necessaire.
	// Au passage si on est gourmand on le dit aux autres
	if (spip_touch(_FILE_CRON_LOCK.'-gourmand', 60, $gourmand)
	OR $gourmand) {

		// Faut-il travailler ? Pas tous en meme temps svp
		// Au passage si on travaille on bloque les autres
		if (spip_touch(_FILE_CRON_LOCK, 2)) {
			include_ecrire('inc_cron');
			spip_cron();
		}
	}
}


//
// Entetes les plus courants (voir inc_headers.php pour les autres)
//

function http_gmoddate($lastmodified) {
	return gmdate("D, d M Y H:i:s", $lastmodified);
}

function http_last_modified($lastmodified, $expire = 0) {
	$gmoddate = http_gmoddate($lastmodified);
	if ($GLOBALS['HTTP_IF_MODIFIED_SINCE']
	AND !preg_match(',IIS/,', $_SERVER['SERVER_SOFTWARE'])) # MSoft IIS is dumb
	{
		$if_modified_since = preg_replace('/;.*/', '',
			$GLOBALS['HTTP_IF_MODIFIED_SINCE']);
		$if_modified_since = trim(str_replace('GMT', '', $if_modified_since));
		if ($if_modified_since == $gmoddate) {
			include_ecrire('inc_headers');
			http_status(304);
			$headers_only = true;
		}
	}
	@Header ("Last-Modified: ".$gmoddate." GMT");
	if ($expire) 
		@Header ("Expires: ".http_gmoddate($expire)." GMT");
	return $headers_only;
}

// envoyer le navigateur sur une nouvelle adresse
// en evitant les attaques par la redirection (souvent indique par 1 $_GET)

function redirige_par_entete($url, $fin="") {
#	spip_log("redirige $url$fin");
	header("Location: " . strtr("$url$fin", "\n\r", "  "));

	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>302 Found</title>
</head>
<body>
<h1>302 Found</h1>
<a href="'
.quote_amp("$url$fin")
.'">Click here</a>.
</body></html>';

	exit;
}

function redirige_url_public($script, $args="") {
	redirige_par_entete(generer_url_public($script, $args, true));
}

// transformation XML des "&" en "&amp;"
function quote_amp($u) {
	return preg_replace(
		"/&(?![a-z]{0,4}\w{2,3};|#x?[0-9a-f]{2,5};)/i",
		"&amp;",$u);
}

// Transforme n'importe quel champ en une chaine utilisable
// en PHP ou Javascript en toute securite
// < ? php $x = '[(#TEXTE|texte_script)]'; ? >
function texte_script($texte) {
	return str_replace('\'', '\\\'', str_replace('\\', '\\\\', $texte));
}

//
// find_in_path() : chercher un fichier nomme x selon le chemin rep1:rep2:rep3
//

function find_in_path ($filename, $path='AUTO', $sinon='') {
	static $autopath;

	// Chemin standard depuis l'espace public

	if ($path == 'AUTO') {
		if (!$autopath) {
			$autopath = _SPIP_PATH;
			// Ajouter les repertoires des plugins
			foreach ($GLOBALS['plugins'] as $plug)
				$autopath = _DIR_PLUGINS.$plug.'/:'.$autopath;
		}
		$path = $autopath;

		if ($GLOBALS['dossier_squelettes'])
			$path = $GLOBALS['dossier_squelettes'].'/:'.$path;
	}
#spip_log("path = $path");

	// Parcourir le chemin
	foreach (split(':', $path) as $dir) {
		// Depuis l'espace prive, remonter d'un cran, sauf pour :
		// - les absolus (/) ; - les locaux (./) ; les remontees (../)
		$racine = preg_match('@^([.]{0,2}/)@', $dir) ?  '' : _DIR_RACINE;
		// ajouter un / eventuellement manquant
		if (substr($dir,-1) <> '/')
			$dir .= "/";
#spip_log("find_in_path: essai $racine $dir $filename");
		if (@is_readable($f = "$racine$dir$filename")) {
			#spip_log("trouve $f");
			return $f;
		}
	}
	
	// sinon essayer $sinon
	if ($sinon AND @is_readable($f = "$sinon$filename"))
		return $f;
}

// charger les definitions des plugins
function charger_plugins($plugins) {
	foreach ($plugins as $plug) {
		if (@is_readable($f = _DIR_PLUGINS.$plug.'/version.php'))
			include($f);
		else if (isset($_COOKIE['spip_admin']))
			echo _L("Erreur plugin &laquo; $plug &raquo; absent.<br />\n");
	}
#var_dump($plugins);var_dump($spip_pipeline);var_dump($spip_matrice);exit;
}

// predicat sur les scripts de ecrire qui n'authentifient pas par cookie

function autoriser_sans_cookie($nom)
{
  static $autsanscookie = array('aide_index', 'install', 'admin_repair', 'spip_cookie');
  $nom = preg_replace('/.php[3]?$/', '', basename($nom));
  return in_array($nom, $autsanscookie);
}

// Cette fonction charge le bon inc-urls selon qu'on est dans l'espace
// public ou prive, la presence d'un (old style) inc-urls.php3, etc.
function charger_generer_url() {
	static $ok;

	if ($ok++) return; # fichier deja charge

	// espace prive ?
	if (!_DIR_RESTREINT)
		include_ecrire('inc_urls');
	// fichier inc-urls ?
	else if (@file_exists("inc-urls" . _EXTENSION_PHP))
		include_local("inc-urls");
	// fichier inc-urls-xxx ?
	else
		include_local("inc-urls-".$GLOBALS['type_urls']);
}


//
// Fonctions de fabrication des URL des scripts de Spip
//

// l'URL de base du site, sans se fier a meta(adresse_site) qui peut etre fausse
// (sites a plusieurs noms d'hotes, deplacements, erreurs)
function url_de_base() {
	global $_SERVER;
	global $REQUEST_URI;

	static $url;

	if ($url)
		return $url;

	$http = (substr($_SERVER["SCRIPT_URI"],0,5) == 'https') ? 'https' : 'http';
	# note : HTTP_HOST contient le :port si necessaire
	$myself = $http.'://' .$_SERVER['HTTP_HOST'].$REQUEST_URI;

	# supprimer (ecrire/)?xxxxx
	$url = preg_replace(',/('._DIR_RESTREINT_ABS.')?[^/]*$,', '/', $myself);
	return $url;
}


// Pour une redirection, la liste des arguments doit etre separee par "&"
// Pour du code XHTML, ca doit etre &amp;
// Bravo au W3C qui n'a pas ete capable de nous eviter ca
// faute de separer proprement langage et meta-langage

// Attention, X?y=z et "X/?y=z" sont completement differents!
// http://httpd.apache.org/docs/2.0/mod/mod_dir.html

function generer_url_ecrire($script, $args="", $no_entities=false, $rel=false) {

	if (!$rel)
		$ecrire = url_de_base() . _DIR_RESTREINT_ABS;
	else
		$ecrire = _DIR_RESTREINT ? _DIR_RESTREINT : './';

	if ($script AND $script<>'accueil')
		$exec = "exec=$script";

	if ($args AND $exec)
		$args = "?$exec&$args";
	else if ($args)
		$args = "?$args";
	else if ($exec)
		$args = "?$exec";

	if (!$no_entities) $args = str_replace('&', '&amp;', $args);

	return "$ecrire$args";
}

// scripts publics appeles a partir de l'espace prive ou de l'exterieur (mail)
function generer_url_public($script, $args="", $no_entities=false) {
	
	if (!$no_entities) $args = str_replace('&', '&amp;', $args);

	$ext = (ereg('.php[3]?$', $script) ? '' :_EXTENSION_PHP).($args ? '?' : "");

	return url_de_base() . $script . $ext . $args;
}

function generer_url_action($script, $args="", $no_entities=false) {

	return  generer_url_public('spip_action.php',
				  "action=$script" .($args ? "&$args" : ''),
				  $no_entities);
	
}
?>
