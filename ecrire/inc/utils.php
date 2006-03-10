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

# fonction obsolete, assurant la compatilibite ascendante
function include_ecrire($file, $silence=false) {
# Hack pour etre compatible avec les mes_options qui appellent cette fonction
	define('_DIR_INCLUDE', _DIR_RESTREINT);
	preg_match('/^((inc_)?([^.]*))(\.php[3]?)?$/', $file, $r);

	// Version new style, surchargeable
	# cas speciaux
	if ($r[3] == 'index') return include_spip('inc/indexation');
	if ($r[3] == 'db_mysql') return include_spip('base/db_mysql');
	if ($r[3] == 'connect') { spip_connect(); return; }

	# cas general
	if ($f=include_spip('inc/'.$r[3]))
		return $f;

	// fichiers old-style, ecrire/inc_truc.php
	if (file_exists($f = _DIR_INCLUDE . $r[1] . '.php'))
		return include_once($f);
}


// charge un fichier perso ou, a defaut, standard
// et retourne si elle existe le nom de la fonction homonyme (exec_$nom),
// ou de suffixe _dist
function include_fonction($nom, $dossier='exec') {

	// Securite de base
	if (!preg_match(',^[a-z0-9_-]+$,', $nom))
		redirige_par_entete('./');

	// Si la fonction existe deja (definie par mes_options, par exemple)
	if (function_exists($f = $dossier.'_'.$nom)
	OR function_exists($f = $dossier.'_'.$nom.'_dist'))
		return $f;

	// Sinon charger le fichier de declaration
	$inc = include_spip($dossier.'/'.$nom);
	if (function_exists($f = $dossier.'_'.$nom) # definition perso ?
	OR function_exists($f = $dossier.'_'.$nom.'_dist')) # definition standard
		return $f;

	// Echec : message d'erreur
	spip_log("fonction $nom indisponible" .
		($inc ? "" : "(fichier $dossier/$nom absent)"));

	include_spip('inc/minipres');
	install_debut_html(_T('forum_titre_erreur'));
	echo '<p>' . _T('fichier_introuvable',
		array('fichier'=> '<b>'.htmlentities("$dossier/$nom").'</b>'));
	install_fin_html();
	exit;
}

//
// une fonction remplacant include_ecrire, et autorisant les surcharges
//
function include_spip($f, $include = true) {
	static $included_files = array();
	// deja charge (nom) ?
	if (isset($included_files[$f]))
		return $included_files[$f];

	// Hack pour pouvoir appeler cette fonction depuis mes_options.
	define('_DIR_INCLUDE', _DIR_RESTREINT);


	// chercher le fichier dans le chemin (eventuellement, surcharge)

	// est-ce dans les chemins du noyau (optimises) ?
	if (_DIR_RESTREINT) {
		if (isset($GLOBALS['meta']['noyau'][$f])) {
			$s = $GLOBALS['meta']['noyau'][$f];
			if (!$s) return false;
		}
		// sinon on se souvient qu'il faudra ecrire le noyau dans public/global
		else
			define('ecrire_noyau', 1);
	}

	if (!isset($s)
	AND !$s = find_in_path($f . '.php')
	AND (!_EXTENSION_PHP OR !$s = find_in_path($f . '.php3'))) {
		if (_DIR_RESTREINT)
			$GLOBALS['meta']['noyau'][$f] = false;
		return $included_files[$f] = false;
	}

	// deja charge (chemin complet) ?
	if (isset($included_files[$s])) {
		if (_DIR_RESTREINT)
			$GLOBALS['meta']['noyau'][$f] = $included_files[$s];
		return $included_files[$f] = $included_files[$s];
	}
	else {
		if (_DIR_RESTREINT)
			$GLOBALS['meta']['noyau'][$f] = $s;
		$included_files[$f] = $included_files[$s] = $s;
	}

	// alors on le charge (sauf si on ne voulait que son chemin)
	if ($include) {
		include($s);
	}

	return $s;
}

// un pipeline est lie a une action et une valeur
// chaque element du pipeline est autorise a modifier la valeur
//
// le pipeline execute les elements disponibles pour cette action,
// les uns apres les autres, et retourne la valeur finale
//
// Cf. compose_filtres dans inc-compilo-index.php3, qui est le
// pendant "compilŽ" de cette fonctionnalite

// appel unitaire d'une fonction du pipeline
// utilisee dans le script pipeline precompile
function minipipe($fonc,$val){
	// fonction
	if (function_exists($fonc))
		$val = call_user_func($fonc, $val);

	// Class::Methode
	else if (preg_match("/^(\w*)::(\w*)$/", $fonc, $regs)
	AND $methode = array($regs[1], $regs[2])
	AND is_callable($methode))
		$val = call_user_func($methode, $val);
	else
		spip_log("Erreur - '$fonc' non definie !");
	return $val;
}

// chargement du pipeline sous la forme d'un fichier php prepare
function pipeline($action,$val) {
	static $charger;

	// chargement initial des fonctions mises en cache, ou generation du cache
	if (!$charger) {
		if (!@is_readable($charger = _DIR_SESSIONS."charger_pipelines.php")) {
			include_spip('inc/plugin');
			// generer les fichiers php precompiles
			// de chargement des plugins et des pipelines
			verif_plugin();
			if ($bug = !@is_readable($charger))
				spip_log("fichier $charger pas cree");
		}

		if (!$bug)
			include_once $charger;
	}

	// appliquer notre fonction si elle existe
	$fonc = 'execute_pipeline_'.$action;
	if (function_exists($fonc)) {
		$val = $fonc($val);
		// si le flux est une table qui encapsule donnees et autres
		// on ne ressort du pipe que les donnees
		if (is_array($val)&&isset($val['data']))
			$val = $val['data'];
	}

	// plantage ?
	else {
		include_spip('inc/plugin');
		// on passe $action en arg pour creer la fonction meme si le pipe
		// n'est defini nul part ; vu qu'on est la c'est qu'il existe !
		verif_plugin($action);
		spip_log("fonction $f absente : pipeline desactive");
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

	$message = date("M d H:i:s").' '.$GLOBALS['ip'].' '.$pid.' '
		.preg_replace("/\n*$/", "\n", $message);

	$logfile = _DIR_SESSIONS . $logname . '.log';
	if (@file_exists($logfile)
	AND (!$s = @filesize($logfile) OR $s > 10*1024)) {
		$rotate = true;
		$message .= "[-- rotate --]\n";
	}
	$f = @fopen($logfile, "ab");
	if ($f) {
		fputs($f, htmlspecialchars($message));
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
function spip_connect() {
	static $t;
	if ($t++) return;

	include_spip('base/db_mysql');
	include_once(_FILE_CONNECT);

	// Version courante = 0.3
	//
	// les versions 0.1 et 0.2 fonctionnent toujours, meme si :
	// - la version 0.1 est moins performante que la 0.2
	// - la 0.2 fait un include_ecrire('inc_db_mysql.php3')
	// En tout cas on ne force pas la mise a niveau
	if ($GLOBALS['spip_connect_version'] >= 0.1)
		return;

	// La version 0.0 (non numerotee) doit etre refaite
	if ($GLOBALS['spip_connect_version'] < 0.1) {
		if (!_DIR_RESTREINT) {
			$GLOBALS['db_ok'] = false;
			return;
		}
		redirige_par_entete(
			generer_url_ecrire('upgrade', 'reinstall=oui', true));
	}
}

function spip_query($query) {

	// Remarque : si on est appele par l'install,
	// la connexion initiale a ete faite avant
	if (!$GLOBALS['db_ok']) {
		// Essaie de se connecter
		if (_FILE_CONNECT)
			spip_connect();

		// Erreur de connexion
		if (!$GLOBALS['db_ok'])
			return;
	}

	// Faire la requete
	return spip_query_db($query);
}


// Renvoie le _GET ou le _POST emis par l'utilisateur
function _request($var) {
	global $_GET, $_POST;
	if (isset($_GET[$var])) return $_GET[$var];
	if (isset($_POST[$var])) return $_POST[$var];
	return NULL;
}


//
// Prend une URL et lui ajoute/retire un parametre.
// Exemples : [(#SELF|parametre_url{suite,18})] (ajout)
//            [(#SELF|parametre_url{suite,''})] (supprime)
//            [(#SELF|parametre_url{suite})]    (prend $suite dans la _request)
// http://www.spip.net/@parametre_url
//
function parametre_url($url, $c, $v=NULL, $sep='&amp;') {

	// lever l'#ancre
	if (preg_match(',^([^#]*)(#.*)$,', $url, $r)) {
		$url = $r[1];
		$ancre = $r[2];
	} else
		$ancre = '';

	// eclater
	$url = preg_split(',[?]|&amp;|&,', $url);

	// recuperer la base
	$a = array_shift($url);
	if (!$a) $a= './';

	// ajout de la globale ?
	if ($v === NULL)
		$v = _request($c);

	// lire les variables et agir
	foreach ($url as $n => $val) {
		if (preg_match(',^'.$c.'(=.*)?$,', $val)) {
			// suppression
			if (!$v) {
				unset($url[$n]);
			} else {
				$url[$n] = $c.'='.urlencode($v);
				$v = '';
			}
		}
	}

	// ajouter notre parametre si on ne l'a pas encore trouve
	if ($v)
		$url[] = $c.'='.urlencode($v);

	// eliminer les vides
	$url = array_filter($url);

	// recomposer l'adresse
	if ($url)
		$a .= '?' . join($sep, $url);

	return $a . $ancre;
}

//
// pour calcul du nom du fichier cache et autres
//
function nettoyer_uri() {
	return preg_replace
		(',[?&](PHPSESSID|(var_[^=&]*))=[^&]*,i',
		'', 
		$GLOBALS['REQUEST_URI']);
}

//
// donner l'URL de base d'un lien vers "soi-meme", modulo
// les trucs inutiles
//
function self($root = false) {
	$url = nettoyer_uri();
	if (!$root)
		$url = preg_replace(',^[^?]*/,', '', $url);

	// ajouter le cas echeant les variables _POST['id_...']
	foreach ($_POST as $v => $c)
		if (substr($v,0,3) == 'id_')
			$url = parametre_url($url, $v, $c, '&');

	// supprimer les variables sans interet
	if (!_DIR_RESTREINT) {
		$url = preg_replace (',([?&])('
		.'lang|set_options|set_couleur|set_disp|set_ecran|show_docs|'
		.'changer_lang|var_lang|action)=[^&]*,i', '\1', $url);
		$url = preg_replace(',([?&])[&]+,', '\1', $url);
		$url = preg_replace(',[&]$,', '\1', $url);
	}

	// eviter les hacks
	$url = htmlspecialchars($url);

	return $url;
}


class Link {
	var $uri;

	//
	// Contructeur : a appeler soit avec l'URL du lien a creer,
	// soit sans parametres, auquel cas l'URL est l'URL courante
	//
	// parametre $root = demander un lien a partir de la racine du serveur /
	function Link($url = '', $root = false) {
		if (!$url)
			$url = self($root);
		$this->uri = $url;
	}

	//
	// Effacer une variable
	//
	function delVar($name) {
		$this->uri = parametre_url($this->uri, $name, '', '&');
	}

	//
	// Ajouter une variable
	// (si aucune valeur n'est specifiee, prend la valeur globale actuelle)
	//
	function addVar($name, $value = NULL) {
		$this->uri = parametre_url($this->uri, $name, $value, '&');
	}

	//
	// Recuperer l'URL correspondant au lien
	//
	function getUrl($anchor = '') {
		return $this->uri . ($anchor ? '#'.$anchor : '');
	}

	//
	// Recuperer le debut de formulaire correspondant au lien
	// (tag ouvrant + entrees cachees representant les variables)
	//

	function getForm($method = 'get', $query = '', $enctype = '') {
		include_spip('inc/filtres');

		if (preg_match(',^[a-z],i', $query))
			$action = $query;
		else
			$action = preg_replace(',[?].*,', '', $this->uri).$query;

		$form = "<form method='$method' action='"
		.quote_amp($action)."'";
		if ($enctype) $form .= " enctype='$enctype'";
		$form .= " style='border: 0px; margin: 0px;'>\n";
		$form .= form_hidden($this->uri);
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
	include_spip('inc/lang');
	$text = traduire_chaine($texte, $args);
	if (!empty($GLOBALS['xhtml'])) {
		include_spip('inc/charsets');
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
function envoie_image_vide() {
	$image = pack("H*", "47494638396118001800800000ffffff00000021f90401000000002c0000000018001800000216848fa9cbed0fa39cb4da8bb3debcfb0f86e248965301003b");
	header("Content-Type: image/gif");
	header("Content-Length: ".strlen($image));
	header("Cache-Control: no-cache,no-store");
	header("Pragma: no-cache");
	header("Connection: close");
	echo $image;
	flush();
}
function action_cron() {
	envoie_image_vide();
	cron (1);
}

//
// cron() : execution des taches de fond
// quand il est appele par public.php il n'est pas gourmand;
// quand il est appele par ?action=cron, il est gourmand

function cron ($gourmand=false) {

	// Si on est gourmand, ou si le fichier gourmand n'existe pas
	// (ou est trop vieux -> 60 sec), on va voir si un cron est necessaire.
	// Au passage si on est gourmand on le dit aux autres
	if (spip_touch(_FILE_CRON_LOCK.'-gourmand', 60, $gourmand)
	OR $gourmand) {

		// Faut-il travailler ? Pas tous en meme temps svp
		// Au passage si on travaille on bloque les autres
		if (spip_touch(_FILE_CRON_LOCK, 2)) {
			include_spip('inc/cron');
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
			include_spip('inc/headers');
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
	# en theorie on devrait faire ca tout le temps, mais quand la chaine
	# commence par ? c'est imperatif, sinon l'url finale n'est pas la bonne
	if ($url[0]=='?')
		$url = url_de_base().$url;

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

function find_in_path ($filename) {
	static $path_a = array();
	static $c = '';

	// on calcule le chemin si le nombre de plugins a change
	if ($c != count($GLOBALS['plugins']).$GLOBALS['dossier_squelettes']) {
		$c = count($GLOBALS['plugins']).$GLOBALS['dossier_squelettes'];

		// Chemin standard depuis l'espace public
		$path = defined('_SPIP_PATH') ? _SPIP_PATH : 
			_DIR_RACINE.':'.
			_DIR_RACINE.'dist/:'.
			_DIR_RACINE.'formulaires/:'.
			_DIR_RESTREINT;

		// Ajouter les repertoires des plugins
		if ($GLOBALS['plugins'])
			$path = _DIR_PLUGINS
				. join(':'._DIR_PLUGINS, $GLOBALS['plugins'])
				. ':' . $path;

		// Ajouter squelettes/
		if (@is_dir(_DIR_RACINE.'squelettes'))
			$path = _DIR_RACINE.'squelettes/:' . $path;

		// Et le(s) dossier(s) des squelettes nommes
		if ($GLOBALS['dossier_squelettes'])
			foreach (explode(':', $GLOBALS['dossier_squelettes']) as $d)
				$path = 
					($d[0] == '/' ? '' : _DIR_RACINE) . $d . '/:' . $path;

		// nettoyer les / du path
		$path_a = array();
		foreach (explode(':', $path) as $dir) {
			if (strlen($dir) AND substr($dir,-1) != '/')
				$dir .= "/";
			$path_a[] = $dir;
		}
	}

	// Parcourir le chemin
	foreach ($path_a as $dir) {
		if (@is_readable($f = "$dir$filename")) {
# spip_log("find_in_path trouve $f");
			return $f;
		}
	}

# spip_log("find_in_path n'a pas vu '$filename' dans " . join(':',$path_a));
	return false;
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

	// espace prive
	if (!_DIR_RESTREINT)
		include_spip('inc/urls');

	// espace public
	else {
		// fichier inc-urls ? (old style)
		if (@file_exists($f = _DIR_RACINE.'inc-urls.php3')
		OR @file_exists($f = _DIR_RACINE.'inc-urls.php')
		OR $f = find_in_path('inc-urls-'.$GLOBALS['type_urls'].'.php3')
		OR $f = include_spip('urls/'.$GLOBALS['type_urls'], false)
		)
			include_once($f);
	}
}


//
// Fonctions de fabrication des URL des scripts de Spip
//

// l'URL de base du site, sans se fier a meta(adresse_site) qui
// peut etre fausse (sites a plusieurs noms d'hotes, deplacements, erreurs)
// Note : la globale $profondeur_url doit etre initialisee de maniere a
// indiquer le nombre de sous-repertoires de l'url courante par rapport a la
// racine de SPIP : par exemple, sur ecrire/ elle vaut 1, sur sedna/ 1, et a
// la racine 0. Sur url/perso/ elle vaut 2
function url_de_base() {
	global $REQUEST_URI;

	static $url;

	if ($url)
		return $url;

	$http = (
		substr($_SERVER["SCRIPT_URI"],0,5) == 'https'
		OR $_SERVER['HTTPS']
	) ? 'https' : 'http';
	# note : HTTP_HOST contient le :port si necessaire
	$myself = $http.'://'.$_SERVER['HTTP_HOST'].$REQUEST_URI;

	# supprimer n sous-repertoires
	$supprime_preg = '/+';
	for ($i=0; $i<$GLOBALS['profondeur_url']; $i++)
		$supprime_preg .= '[^/]+/+';
	$url = preg_replace(','.$supprime_preg.'[^/]*$,', '/', $myself);
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

//
// Adresse des scripts publics (a passer dans inc-urls...)
//

// Detecter le fichier de base, a la racine, comme etant spip.php ou ''
// dans le cas de '', un $default = './' peut servir (comme dans urls/page.php)
function get_spip_script($default='') {
	# cas define('_SPIP_SCRIPT', '');
	if (_SPIP_SCRIPT)
		return _SPIP_SCRIPT;
	else
		return $default;
}


function generer_url_public($script, $args="", $no_entities=false) {

	// si le script est une action (spip_pass, spip_inscription),
	// utiliser generer_url_action [hack temporaire pour faire
	// fonctionner #URL_PAGE{spip_pass} ]
	if (preg_match(',^spip_(.*),', $script, $regs))
		return generer_url_action($regs[1],$args,true); # attention a la recursivite !!

	$action = get_spip_script();
	if ($script)
		$action = parametre_url($action, 'page', $script, '&');

	if ($args)
		$action .=
			(strpos($action, '?') !== false ? '&' : '?') . $args;

	if (!$no_entities)
		$action = quote_amp($action);

	return url_de_base() . $action;
}

function generer_url_action($script, $args="", $no_entities=false) {

	return  generer_url_public('',
				  "action=$script" .($args ? "&$args" : ''),
				  $no_entities);
	
}


// Dirty hack contre le register_globals a 'Off' (PHP 4.1.x)
// A remplacer (bientot ?) par une gestion propre des variables admissibles ;-)
// Attention pour compatibilite max $_GET n'est pas superglobale
// NB: c'est une fonction de maniere a ne pas pourrir $GLOBALS
function spip_register_globals() {

	// Liste des variables dont on refuse qu'elles puissent provenir du client
	$refuse_gpc = array (
		# inc-public
		'fond', 'delais',

		# ecrire/inc_auth
		'REMOTE_USER',
		'PHP_AUTH_USER', 'PHP_AUTH_PW'
	);

	// Liste des variables (contexte) dont on refuse qu'elles soient cookie
	// (histoire que personne ne vienne fausser le cache)
	$refuse_c = array (
		# inc-calcul
		'id_parent', 'id_rubrique', 'id_article',
		'id_auteur', 'id_breve', 'id_forum', 'id_secteur',
		'id_syndic', 'id_syndic_article', 'id_mot', 'id_groupe',
		'id_document', 'date', 'lang'
	);


	// Si les variables sont passees en global par le serveur, il faut
	// faire quelques verifications de base
	if (@ini_get('register_globals')) {
		foreach ($refuse_gpc as $var) {
			if (isset($GLOBALS[$var])) {
				if (
				// demande par le client
				$_REQUEST[$var] !== NULL
				// et pas modifie par les fichiers d'appel
				AND $GLOBALS[$var] == $_REQUEST[$var]
				) // Alors on ne sait pas si c'est un hack
					die ("register_globals: $var interdite");
			}
		}
		foreach ($refuse_c as $var) {
			if (isset($GLOBALS[$var])) {
				if (
				isset ($_COOKIE[$var])
				AND $_COOKIE[$var] == $GLOBALS[$var]
				)
					define ('spip_interdire_cache', true);
			}
		}
	}

	// sinon il faut les passer nous-memes, a l'exception des interdites.
	// (A changer en une liste des variables admissibles...)
	else {
		foreach (array('_SERVER', '_COOKIE', '_POST', '_GET') as $_table) {
			foreach ($GLOBALS[$_table] as $var => $val) {
				if (!isset($GLOBALS[$var]) # indispensable securite
				AND isset($GLOBALS[$_table][$var])
				AND ($_table == '_SERVER' OR !in_array($var, $refuse_gpc))
				AND ($_table <> '_COOKIE' OR !in_array($var, $refuse_c)))
					$GLOBALS[$var] = $val;
			}
		}
	}
}

function spip_initialisation() {
	// la taille maxi des logos (0 : pas de limite)
	define('_LOGO_MAX_SIZE', 0); # poids en ko
	define('_LOGO_MAX_WIDTH', 0); # largeur en pixels
	define('_LOGO_MAX_HEIGHT', 0); # hauteur en pixels

	// Le fichier de connexion a la base de donnees
	define('_FILE_CONNECT_INS', (_DIR_RESTREINT . "inc_connect"));
	define('_FILE_CONNECT',
		(@is_readable($f = _FILE_CONNECT_INS . '.php') ? $f
	:	(@is_readable($f = _FILE_CONNECT_INS . '.php3') ? $f
	:	false)));

	// les repertoires annexes
	define('_DIR_IMG', _DIR_RACINE ."IMG/");
	define('_DIR_DOC', _DIR_RACINE ."IMG/");
	define('_DIR_CACHE', _DIR_RACINE ."CACHE/");
	define('_DIR_SESSIONS', _DIR_RESTREINT . "data/");
	define('_DIR_TRANSFERT', _DIR_RESTREINT . "upload/");
	define('_DIR_PLUGINS', _DIR_RACINE . "plugins/");
	define('_DIR_LOGOS', _DIR_RACINE ."IMG/");

//	define('_DIR_POLICES', _DIR_RESTREINT ."polices/");
	define('_DIR_POLICES', "polices/");

	// les fichiers qu'on y met, entre autres
	define('_FILE_CRON_LOCK', _DIR_SESSIONS . 'cron.lock');
	define('_FILE_MYSQL_OUT', _DIR_SESSIONS . 'mysql_out');
	define('_FILE_GARBAGE', _DIR_SESSIONS . '.poubelle');
	define('_FILE_META', _DIR_SESSIONS . 'meta_cache.txt');

	// sous-repertoires d'images 
	define('_DIR_TeX', _DIR_IMG . "cache-TeX/");

	// Icones
	# le chemin http (relatif) vers les images standard
	define('_DIR_IMG_PACK', (_DIR_RESTREINT . 'img_pack/'));
	# vers les logos de type de document, standard & perso
	define('_DIR_IMG_ICONES_DIST', _DIR_IMG_PACK . "icones/");
	define('_DIR_IMG_ICONES', _DIR_IMG . "icones/");
	# les icones de la barre d'edition des formulaires
	define('_DIR_IMG_ICONES_BARRE', _DIR_IMG_PACK . "icones_barre/");

	# le chemin php (absolu) vers les images standard (pour hebergement centralise)
	define('_ROOT_IMG_PACK', (dirname(dirname(__FILE__)) . '/img_pack/'));
	define('_ROOT_IMG_ICONES_DIST', (dirname(dirname(__FILE__)) . '/img_pack/icones/'));

	// Le charset par defaut lors de l'installation
	define('_DEFAULT_CHARSET', 'utf-8');

	// les repertoires devant etre TOUJOURS accessibles en ecriture
	$GLOBALS['test_dirs'] = array(_DIR_CACHE, _DIR_IMG, _DIR_SESSIONS);

	// qq chaines standard
	define('_ACCESS_FILE_NAME', '.htaccess');
	define('_AUTH_USER_FILE', '.htpasswd');

	define('_DOCTYPE_ECRIRE', "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n");

	// L'adresse de base du site ; on peut mettre '' si la racine est geree par
	// le script index.php
	define('_SPIP_SCRIPT', 'spip.php');


	// *********** traiter les variables ************

	// Recuperer les superglobales $_GET si non definies
	// (en theorie c'est impossible depuis PHP 4.0.3, cf. track_vars)
	// et les identifier aux $HTTP_XX_VARS
	foreach (array('_GET', '_POST', '_COOKIE', '_SERVER') as $_table) {
		$http_table_vars = 'HTTP'.$_table.'_VARS';
		if (!is_array($GLOBALS[$_table])) {
			$GLOBALS[$_table] = array();
			if (is_array($GLOBALS[$http_table_vars]))
				$GLOBALS[$_table] = & $GLOBALS[$http_table_vars];
		}
			$GLOBALS[$http_table_vars] = & $GLOBALS[$_table];
	}

	//
	// Securite
	//

	// Ne pas se faire manger par un bug php qui accepte ?GLOBALS[truc]=toto
	if (isset($_REQUEST['GLOBALS'])) die();
	// nettoyer les magic quotes \' et les caracteres nuls %00
	spip_desinfecte($_GET);
	spip_desinfecte($_POST);
	spip_desinfecte($_COOKIE);
	spip_desinfecte($_REQUEST);
	spip_desinfecte($GLOBALS);

	// Par ailleurs on ne veut pas de magic_quotes au cours de l'execution
	@set_magic_quotes_runtime(0);

	// Remplir $GLOBALS avec $_GET et $_POST (methode a revoir pour fonctionner
	// completement en respectant register_globals = off)
	spip_register_globals();

	// appliquer le cookie_prefix
	if ($GLOBALS['cookie_prefix'] != 'spip') {
		include_spip('inc/cookie');
		recuperer_cookies_spip($GLOBALS['cookie_prefix']);
	}


	//
	// Capacites php (en fonction de la version)
	//
	$GLOBALS['flag_gz'] = function_exists("gzencode"); #php 4.0.4
	$GLOBALS['flag_ob'] = (function_exists("ob_start")
		&& function_exists("ini_get")
		&& (@ini_get('max_execution_time') > 0)
		&& !strstr(ini_get('disable_functions'), 'ob_'));
	$GLOBALS['flag_sapi_name'] = function_exists("php_sapi_name");
	$GLOBALS['flag_revisions'] = function_exists("gzcompress");
	$GLOBALS['flag_get_cfg_var'] = (@get_cfg_var('error_reporting') != "");
	$GLOBALS['flag_upload'] = (!$GLOBALS['flag_get_cfg_var'] ||
		(get_cfg_var('upload_max_filesize') > 0));

	//
	// Sommes-nous dans l'empire du Mal ?
	// (ou sous le signe du Pingouin, ascendant GNU ?)
	//

	if (strpos($_SERVER['SERVER_SOFTWARE'], '(Win') !== false)
		define ('os_serveur', 'windows');

	//
	// Infos sur le fichier courant
	//

	// Compatibilite avec serveurs ne fournissant pas $REQUEST_URI
	if (!$GLOBALS['REQUEST_URI']) {
		$GLOBALS['REQUEST_URI'] = $PHP_SELF;
		if ($_SERVER['QUERY_STRING'] AND !strpos($_SERVER['REQUEST_URI'], '?'))
			$GLOBALS['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
	}


	// tidy en ligne de commande (si on ne l'a pas en module php,
	// ou si le module php ne marche pas)
	// '/bin/tidy' ou '/usr/local/bin/tidy' ou tout simplement 'tidy'
	#define('_TIDY_COMMAND', 'tidy');

	//
	// Module de lecture/ecriture/suppression de fichiers utilisant flock()
	// (non surchargeable en l'etat ; attention si on utilise include_spip()
	// pour le rendre surchargeable, on va provoquer un reecriture
	// systematique du noyau ou une baisse de perfs => a etudier)
	include_once(_DIR_RESTREINT.'inc/flock.php');


	// Lire les meta cachees
	if (lire_fichier(_DIR_SESSIONS . 'meta_cache.txt', $meta))
		$GLOBALS['meta'] = @unserialize($meta);
	// en cas d'echec refaire le fichier
	if (!is_array($GLOBALS['meta']) AND _FILE_CONNECT) {
		include_spip('inc/meta');
		ecrire_metas();
	}

	// supprimer le noyau si on recalcul
	if ($_REQUEST['var_mode']) {
		$GLOBALS['meta']['noyau'] = array();
		define('ecrire_noyau', 1);
	}

	// Langue principale du site
	$GLOBALS['langue_site'] = $GLOBALS['meta']['langue_site'];
	if (!$GLOBALS['langue_site']) include_spip('inc/lang');
	$GLOBALS['spip_lang'] = $GLOBALS['langue_site'];

	// Verifier le visiteur
	verifier_visiteur();
}

//
// Gerer les variables de personnalisation, qui peuvent provenir
// des fichiers d'appel, en verifiant qu'elles n'ont pas ete passees
// par le visiteur (sinon, pas de cache)
//
function tester_variable($var, $val){
	if (!isset($GLOBALS[$var]))
		$GLOBALS[$var] = $val;

	if (
		$_REQUEST[$var] !== NULL
		AND $GLOBALS[$var] == $_REQUEST[$var]
	)
		die ("tester_variable: $var interdite");
}

// Annuler les magic quotes \' sur GET POST COOKIE et GLOBALS ;
// supprimer aussi les eventuels caracteres nuls %00, qui peuvent tromper
// la commande file_exists('chemin/vers/fichier/interdit%00truc_normal')
function spip_desinfecte(&$t) {
	static $magic_quotes;
	if (!isset($magic_quotes))
		$magic_quotes = @get_magic_quotes_gpc();

	foreach ($t as $key => $val) {
		if (is_string($t[$key])) {
			if ($magic_quotes)
				$t[$key] = stripslashes($t[$key]);
			$t[$key] = str_replace(chr(0), '-', $t[$key]);
		}
		// traiter aussi les "texte_plus" de articles_edit
		else if ($key == 'texte_plus' AND is_array($t[$key]))
			spip_desinfecte($t[$key]);
	}
}

// Authentifier le visiteur s'il s'annonce
// Rq: pour que cette fonction marche depuis mes_options elle a besoin
// que quelques petites constantes soient deja initialisees
function verifier_visiteur() {
	define('_DIR_SESSIONS', _DIR_RESTREINT . "data/");
	if ($_COOKIE['spip_session'] OR
	($_SERVER['PHP_AUTH_USER']  AND !$GLOBALS['ignore_auth_http'])) {
		include_spip('inc/session');
		verifier_session_visiteur();
	}
}


//
// INITIALISER LES CONSTANTES ET LES VARIABLES SYSTEMES DE SPIP
//
spip_initialisation();

?>
