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
	if (@$GLOBALS['included_files'][$nom]++)
		return true;
	if (is_readable($f = $nom . '.php')) {
		include($f);
		return true;
	}
	else if (is_readable($f = $nom . _EXTENSION_PHP)) {
		include($f);
		return true;
	}
/*	else if (is_readable($f = _DIR_INCLUDE. preg_replace(',^inc-,', 'public-', $nom) . '.php')) {
		include($f);
		return true;
	}*/
	else {
		if (!$silence)
			spip_log($file . " illisible");
		return false;
	}
}

function include_ecrire($file, $silence=false) {
# Hack pour etre compatible avec les mes_options qui appellent cette fonction
	define('_DIR_INCLUDE', _DIR_RESTREINT);
	return include_local(_DIR_INCLUDE . $file, $silence);
}

// charge un fichier perso ou, a defaut, standard
// et retourne si elle existe le nom de la fonction homonyme, ou de suffixe _dist

function include_fonction($nom) {
	$inc = ("exec_" . $nom  . '.php');
	$f = find_in_path($inc);
	if ($f) {
		if (!$GLOBALS['included_files'][$f]++) include($f);
		#spip_log("surcharge de $nom trouvee dans $f");
	} else {
		$f = (defined(' _DIR_INCLUDE') ? _DIR_INCLUDE : _DIR_RESTREINT)
			. $inc;
		if (is_readable($f)) {
			if (!$GLOBALS['included_files'][$f]++) include($f);
		} else {
		    $inc = "";
		}
	}
	if (function_exists($nom))
		return $nom;
	elseif (function_exists($f = $nom . "_dist"))
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
function pipeline($action,$val){
	$ok = @is_readable($f = _DIR_SESSIONS."charger_pipelines.php");
	if (!$ok){
		include_ecrire('inc_plugin');
		// generer les fichiers php precompiles
		// de chargement des plugins et des pipelines
		verif_plugin();
		$ok = @is_readable($f = _DIR_SESSIONS."charger_pipelines.php");
		if (!$ok)
			spip_log("generation de $f impossible; tous les pipeline desactives");
	}
	if ($ok){
		require_once($f);
		$f = "execute_pipeline_$action";
		$ok = function_exists($f);
		if ($ok){
			$val = $f($val);
			// si le flux est une table qui encapsule donnees et autres
			// on ne ressort du pipe que les donnees
			if (is_array($val)&&isset($val['data']))
				$val = $val['data'];
		}
		else{
			include_ecrire('inc_plugin');
			//on passe $action en arg pour creer la fonction meme si le pipe n'est defini nul part
			// vu qu'on est la c'est qu'il existe !
			verif_plugin($action);
			spip_log("fonction $f absente : pipeline desactive");
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

	// ajouter le cas echeant les variables _POST
	foreach ($_POST as $v => $c)
		if (substr($v,0,3) == 'id_')
			$url = parametre_url($url, $v, $c, '&');

	// supprimer les variables sans interet
	if (!_DIR_RESTREINT)
		preg_replace (',[?&]('
		.'lang|set_options|set_couleur|set_disp|set_ecran|show_docs'
		.')=[^&]*,i', '', $url);

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
		include_ecrire('inc_filtres');

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
function spip_action_cron() {
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

function find_in_path ($filename, $sinon = NULL, $path='AUTO') {
	static $autopath;

	// Chemin standard depuis l'espace public
	if ($path == 'AUTO') {
		if (!$autopath) {

			// Depuis l'espace prive, remonter d'un cran, sauf pour :
			// - les absolus (/) ; - les locaux (./) ; les remontees (../)
			if (_DIR_RACINE) {
				$autopath = array();
				foreach (split(':', _SPIP_PATH) as $dir) {
					if (!preg_match('@^([.]{0,2}/)@', $dir))
						$dir = _DIR_RACINE.$dir;
					$autopath[] = $dir;
				}
				$autopath = join(':', $autopath);
			} else
				$autopath = _SPIP_PATH;

			// Ajouter les repertoires des plugins
			foreach ($GLOBALS['plugins'] as $plug)
				$autopath = _DIR_PLUGINS.$plug.'/:'.$autopath;
		}
		$path = $autopath;

		if ($GLOBALS['dossier_squelettes'])
			$path = $GLOBALS['dossier_squelettes'].'/:'.$path;
	}

	// Parcourir le chemin
	# Attention, dans l'espace prive on a parfois sinon='' pour _DIR_INCLUDE
	if ($sinon !== NULL) $path .= ':'.$sinon;

	foreach (split(':', $path) as $dir) {
		// ajouter un / eventuellement manquant a la fin
		if (strlen($dir) AND substr($dir,-1) != '/') $dir .= "/";
		if (@is_readable($f = "$dir$filename")) {
#			spip_log("find_in_path trouve $f");
			return $f;
		}
	}
	spip_log("find_in_path n'a pas vu '$filename' dans $path");
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
		include_ecrire('inc_urls');

	// espace public
	else {
		// fichier inc-urls ? (old style)
		include_local(_DIR_RACINE."inc-urls", true)
		// sinon fichier inc-urls-xxx
		OR include_local(find_in_path('urls/'.$GLOBALS['type_urls'].'.php', _DIR_RESTREINT));
	}
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

//
// Adresse des scripts publics (a passer dans inc-urls...)
//

// Detecter le fichier de base, a la racine, comme etant spip.php ou ''
// dans le cas de '', un $default = './' peut servir (comme dans urls/page.php)
function get_spip_script($default='') {
	if (!defined('_SPIP_SCRIPT')) {
		if (lire_fichier(_DIR_RACINE.'index.php', $contenu)
		AND preg_match(',spip\.php,', $contenu))
			@define('_SPIP_SCRIPT', '');
		else
			@define('_SPIP_SCRIPT', 'spip.php');
	}

	if (_SPIP_SCRIPT)
		return _SPIP_SCRIPT;
	else
		return $default;
}


function generer_url_public($script, $args="", $no_entities=false) {

	if (!$script) {
		$action = get_spip_script();

	} else {
		// transition : s'agit-il d'un fichier existant ?
		$fichier = $script . (ereg('[.]php[3]?$', $script) ? 
		'' : _EXTENSION_PHP);
		if (@file_exists(_DIR_RACINE . $fichier)) {
			$action = $fichier;
		}

		// sinon utiliser _SPIP_SCRIPT?page=script
		else {
			$action = get_spip_script() . '?page=' . $script;
		}
	}

	// si le script est une action (spip_pass, spip_inscription),
	// utiliser generer_url_action [hack temporaire pour faire
	// fonctionner #URL_PAGE{spip_pass} ]
	if (preg_match(',^spip_(.*),', $script, $regs))
		return generer_url_action($regs[1],$args,true);

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
// A remplacer (un jour!) par une gestion propre des variables admissibles ;-)
// Attention pour compatibilite max $_GET n'est pas superglobale
// NB: c'est une fonction de maniere a ne pas pourrir $GLOBALS
function spip_register_globals() {

	// Liste des variables dont on refuse qu'elles puissent provenir du client
	$refuse_gpc = array (
		# inc-public
		'fond', 'delais',

		# ecrire/inc_auth
		'REMOTE_USER',
		'PHP_AUTH_USER', 'PHP_AUTH_PW',

		# ecrire/inc_texte
		'debut_intertitre', 'fin_intertitre', 'ligne_horizontale',
		'ouvre_ref', 'ferme_ref', 'ouvre_note', 'ferme_note',
		'les_notes', 'compt_note', 'nombre_surligne',
		'url_glossaire_externe', 'puce', 'puce_rtl'
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
				foreach (array('_GET', '_POST', '_COOKIE') as $_table) {
					if (
					// demande par le client
					isset ($GLOBALS[$_table][$var])
					// et pas modifie par les fichiers d'appel
					AND $GLOBALS[$_table][$var] == $GLOBALS[$var]
					) // On ne sait pas si c'est un hack
					{
						# REMOTE_USER ou fond, c'est grave ;
						# pour le reste (cookie 'lang', par exemple), simplement
						# interdire la mise en cache de la page produite
						switch ($var) {
							case 'REMOTE_USER':
							case 'fond':
								die ("$var interdite");
								break;
							default:
								define ('spip_interdire_cache', true);
						}
					}
				}
			}
		}
		foreach ($refuse_c as $var) {
			if (isset($GLOBALS[$var])) {
				foreach (array('_COOKIE') as $_table) {
					if (
					// demande par le client
					isset ($GLOBALS[$_table][$var])
					// et pas modifie par les fichiers d'appel
					AND $GLOBALS[$_table][$var] == $GLOBALS[$var]
					)
						define ('spip_interdire_cache', true);
				}
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


// Annuler les magic quotes \' sur GET POST COOKIE et GLOBALS ;
// supprimer aussi les eventuels caracteres nuls %00, qui peuvent tromper
// la commande file_exists('chemin/vers/fichier/interdit%00truc_normal')
function spip_desinfecte(&$t) {
	static $magic_quotes;
	if (!isset($magic_quotes))
		$magic_quotes = @get_magic_quotes_gpc();

	if (is_array($t)) {
		foreach ($t as $key => $val) {
			if (!is_array($val)
			OR !isset($t['spip_recursions'])) { # interdire les recursions
				$t['spip_recursions'] = true;
				spip_desinfecte($t[$key]);
			}
		}
	} else {
		$t = str_replace(chr(0), '', $t);
		if ($magic_quotes)
			$t = stripslashes($t);
	}
}

?>
