<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
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
#	spip_log("$nom $file");
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
	$nom = preg_replace("/\.php[3]?$/",'', basename($nom));
	$inc = ('inc_' . $nom);
#	spip_log("if $inc");
	$f = find_in_path($inc  . '.php');
	if ($f && is_readable($f)) {
		if (!$GLOBALS['included_files'][$inc]++) include($f);
	} else {
		$f = _DIR_INCLUDE . $inc  . '.php';
		if (is_readable($f)) {
			if (!$GLOBALS['included_files'][$inc]++) include($f);
		} else {
		  // provisoire avant renommage 
			$f = _DIR_INCLUDE . ('inc_' . $nom . _EXTENSION_PHP);
			if (is_readable($f)) {
				if (!$GLOBALS['included_files'][$inc]++) include($f);
			} else  $inc = "";
		}
	}
	$f = str_replace('-','_',$nom); // pour config-fonc etc. A renommer
	if (function_exists($f))
		return $f;
	elseif (function_exists($f .= "_dist"))
		return $f;
	else {
	  spip_log("fonction $nom indisponible" .
		   ($inc ? "" : "(aucun fichier inc_$f disponible)"));
	  exit;
	}
}



// un pipeline est lie a une action et une valeur
// chaque element du pipeline est autorise a modifier la valeur
//
// le pipeline execute les elements disponibles pour cette action,
// les uns apres les autres, et retourne la valeur finale

function pipeline($cause, $val) {
	global $spip_pipeline, $spip_matrice;
	if (!is_array($spip_pipeline[$cause])) return $val;

	foreach ($spip_pipeline[$cause] as $plug) {

		// charger un fichier le cas echeant
		if (!function_exists($plug)) {
			if ($f = $spip_matrice[$plug]) {
				include($f);
				$ok = function_exists($plug);
			}
			if (!$ok) {
				spip_log("Erreur - $plug n'est pas definie ($f)");
				return $val;
			}
		}

		// appliquer le filtre
		$val = $plug($val);
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
		redirige_par_entete(generer_url_ecrire("upgrade","reinstall=oui"));
		exit;
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
		$text = html2unicode($text);
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

//
// cron() : execution des taches de fond
// quand il est appele par spip_background, il est gourmand ;
// quand il est appele par inc-public il n'est pas gourmand
//
function cron($gourmand = false) {
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

function redirige_par_entete($url, $fin="") {
	spip_log("redirige $url$fin");
	include_ecrire('inc_headers');
	spip_header("Location: $url$fin");
	exit;
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

function find_in_path ($filename, $path='AUTO') {
	// Chemin standard depuis l'espace public

	if ($path == 'AUTO') {
		$path = _SPIP_PATH;
		if ($GLOBALS['dossier_squelettes'])
			$path = $GLOBALS['dossier_squelettes'].'/:'.$path;
	}


	$racine = (_DIR_RESTREINT ? '' : '../');

	// Parcourir le chemin
	foreach (split(':', $path) as $dir) {
	// Depuis l'espace prive, remonter d'un cran, sauf pour les absolus
		$racine = ($dir[0] <> '/' && !_DIR_RESTREINT) ? '../' : '';
		if ($dir[0] == '.') $dir = "";
		else if ($dir && $dir[strlen($dir)-1] <> '/') $dir .= "/";
		$f = "$racine$dir$filename";
#		spip_log("find_in_path: essai $racine $dir $filename");
		if (@is_readable($f)) {
			return $f;
		}
	}
}

// charger les definitions des plugins
function charger_plugins($plugins) {
	foreach ($plugins as $plug) {
		include(_DIR_RACINE.'plugins/'.$plug.'/version.php');
	}
#var_dump($plugins);var_dump($spip_pipeline);var_dump($spip_matrice);exit;
}



// cette fonction fabrique un appel a un script php
// elle est destinees a assurer la transition
// entre les scripts ecrire/*.php[3] et le script generique ecrire/index.php

function generer_url_ecrire($script, $args="", $retour="", $retour_args="") {
	return $script .
		_EXTENSION_PHP .
		(!$args ? "" : ('?'  .str_replace('&', '&amp;', $args))) .
		(!$retour ? "" : 
		urlencode($retour . _EXTENSION_PHP .
			  (!$retour_args ? "" : ('?' . $retour_args))));
}

?>
