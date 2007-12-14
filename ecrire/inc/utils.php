<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Utilitaires indispensables autour du serveur Http.
//

// charge un fichier perso ou, a defaut, standard
// et retourne si elle existe le nom de la fonction homonyme (exec_$nom),
// ou de suffixe _dist
// Peut etre appelee plusieurs fois, donc optimiser
// http://doc.spip.org/@charger_fonction
function charger_fonction($nom, $dossier='exec', $continue=false) {

	if (substr($dossier,-1) != '/') $dossier .= '/';

	if (function_exists($f = str_replace('/','_',$dossier) . $nom))
		return $f;
	if (function_exists($g = $f . '_dist'))
		return $g;

	// Sinon charger le fichier de declaration si plausible

	if (!preg_match(',^\w+$,', $f))
		die(htmlspecialchars($nom)." pas autorise");

	// passer en minuscules (cf les balises de formulaires)
	$inc = find_in_path(($d = strtolower($nom) . '.php'), $dossier);
	if ($inc) {
		include_once $inc;
		if (function_exists($f)) return $f;
		if (function_exists($g)) return $g;
	}
	if ($continue) return false;

	// Echec : message d'erreur
	spip_log("fonction $nom ($f ou $g) indisponible" .
		($inc ? "" : " (fichier $d absent de $dossier)"));

	include_spip('inc/minipres');
	echo minipres(_T('forum_titre_erreur'),
		 _T('fichier_introuvable', array('fichier'=> '<b>'.htmlentities($d).'</b>')));
	exit;
}


//
// la fonction cherchant un fichier PHP dans le SPIP_PATH
//
// http://doc.spip.org/@include_spip
function include_spip($f, $include = true) {
	return find_in_path($f . '.php', '', $include);
}

// un pipeline est lie a une action et une valeur
// chaque element du pipeline est autorise a modifier la valeur
//
// le pipeline execute les elements disponibles pour cette action,
// les uns apres les autres, et retourne la valeur finale
//
// Cf. compose_filtres dans references.php, qui est la
// version compilee de cette fonctionnalite

// appel unitaire d'une fonction du pipeline
// utilisee dans le script pipeline precompile
// http://doc.spip.org/@minipipe
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
// http://doc.spip.org/@pipeline
function pipeline($action, $val=null) {
	static $charger;

	// chargement initial des fonctions mises en cache, ou generation du cache
	if (!$charger) {
		if (!($ok = @is_readable($charger = _DIR_TMP."charger_pipelines.php"))) {
			include_spip('inc/plugin');
			// generer les fichiers php precompiles
			// de chargement des plugins et des pipelines
			verif_plugin();
			if (!($ok = @is_readable($charger)))
				spip_log("fichier $charger pas cree");
		}

		if ($ok)
			include_once $charger;
	}

	// appliquer notre fonction si elle existe
	$fonc = 'execute_pipeline_'.$action;
	if (function_exists($fonc)) {
		$val = $fonc($val);
	}
	// plantage ?
	else {
		include_spip('inc/plugin');
		// on passe $action en arg pour creer la fonction meme si le pipe
		// n'est defini nul part ; vu qu'on est la c'est qu'il existe !
		verif_plugin($action);
		spip_log("fonction $fonc absente : pipeline desactive");
	}
	// si le flux est une table qui encapsule donnees et autres
	// on ne ressort du pipe que les donnees
	// array_key_exists pour php 4.1.0
	if (is_array($val) && in_array('data', array_keys($val)))
		$val = $val['data'];
	return $val;
}

//
// Enregistrement des evenements
//
// http://doc.spip.org/@spip_log
function spip_log($message, $logname=NULL, $logdir=NULL, $logsuf=NULL) {
	static $compteur = array();
	global $nombre_de_logs, $taille_des_logs;
	$logname = ($logname===NULL ? _FILE_LOG : $logname);
	if (!isset($compteur[$logname])) $compteur[$logname] = 0;
	if (($logname != 'maj') AND
	    ( $compteur[$logname]++ > 100 || !$nombre_de_logs || !$taille_des_logs))
		return;

	$logfile = ($logdir===NULL ? _DIR_LOG : $logdir)
	  . ($logname)
	  . ($logsuf===NULL ? _FILE_LOG_SUFFIX : $logname);

	$rotate = 0;
	$pid = '(pid '.@getmypid().')';

	// accepter spip_log( Array )
	if (!is_string($message)) $message = var_export($message, true);

	$m = date("M d H:i:s").' '.$GLOBALS['ip'].' '.$pid.' '
		.preg_replace("/\n*$/", "\n", $message);


	if (@is_readable($logfile)
	AND (!$s = @filesize($logfile) OR $s > $taille_des_logs * 1024)) {
		$rotate = $nombre_de_logs;
		$m .= "[-- rotate --]\n";
	}
	
	$f = @fopen($logfile, "ab");
	if ($f) {
		fputs($f, ($logname!==NULL) ? $m : str_replace('<','&lt;',$m));
		fclose($f);
	}

	if ($rotate-- > 0) {
		spip_unlink($logfile . '.' . $rotate);
		while ($rotate--) {
			@rename($logfile . ($rotate ? '.' . $rotate : ''), $logfile . '.' . ($rotate + 1));
		}
	}

	// Dupliquer les erreurs specifiques dans le log general
	if ($logname !== _FILE_LOG)
		spip_log($logname=='maj' ? 'cf maj.log' : $message);
}

// Renvoie le _GET ou le _POST emis par l'utilisateur
// ou pioche dans $c si c'est un array()
// http://doc.spip.org/@_request
function _request($var, $c=false) {

	if (is_array($c))
		return isset($c[$var]) ? $c[$var] : NULL;

	if (isset($_GET[$var])) $a = $_GET[$var];
	elseif (isset($_POST[$var])) $a = $_POST[$var];
	else return NULL;

	// temporaire: si on est en ajax et en POST tout a ete encode
	// via encodeURIComponent, il faut donc repasser
	// dans le charset local.... on le connait grace
	// a la variable var_ajaxcharset ajoutee dans layer.js

	if (isset($_POST['var_ajaxcharset'])
	AND isset($GLOBALS['meta']['charset'])
	AND $GLOBALS['meta']['charset'] != $_POST['var_ajaxcharset']
	AND is_string($a)
	AND preg_match(',[\x80-\xFF],', $a)) {
		include_spip('inc/charsets');
		return importer_charset($a, $_POST['var_ajaxcharset']);
	}

	return $a;
}

// Methode set de la fonction _request()
// Attention au cas ou l'on fait set_request('truc', NULL);
// http://doc.spip.org/@set_request
function set_request($var, $val = NULL, $c=false) {
	if (is_array($c)) {
		unset($c[$var]);
		if ($val !== NULL)
			$c[$var] = $val;
		return $c;
	}

	unset($_GET[$var]);
	unset($_POST[$var]);
	if ($val !== NULL)
		$_GET[$var] = $val;
	
	return false; # n'affecte pas $c
}

//
// Prend une URL et lui ajoute/retire un parametre.
// Exemples : [(#SELF|parametre_url{suite,18})] (ajout)
//            [(#SELF|parametre_url{suite,''})] (supprime)
//            [(#SELF|parametre_url{suite})]    (prend $suite dans la _request)
// http://doc.spip.org/@parametre_url
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

	$regexp = ',^(' . $c . ')(=.*)?$,';
	$ajouts = array_flip(explode('|',$c));
	$u = is_array($v) ? $v : rawurlencode($v);
	// lire les variables et agir
	foreach ($url as $n => $val) {
		if (preg_match($regexp, urldecode($val), $r)) {
			if ($v === NULL) {
				return $r[2]?substr($r[2],1):''; 
			}
			elseif (!$v) {// suppression
				unset($url[$n]);
			} else {
				$url[$n] = $r[1].'='.$u;
				unset($ajouts[$r[1]]);
			}
		}
	}

	// traiter les parametres pas encore trouves
	if ($v === NULL
	AND $args = func_get_args()
	AND count($args)==2)
		return $v;
	elseif ($v) {
		foreach($ajouts as $k => $n) $url[] = $k .'=' . $u;
	} 

	// eliminer les vides
	$url = array_filter($url);

	// recomposer l'adresse
	if ($url)
		$a .= '?' . join($sep, $url);

	return $a . $ancre;
}

// Prend une URL et lui ajoute/retire une ancre apres l'avoir nettoyee
// pour l'ancre on translitere, vire les non alphanum du debut,
// et on remplace ceux a l'interieur ou au bout par -
// http://doc.spip.org/@ancre_url
function ancre_url($url, $ancre) {
	include_spip('inc/charsets');
	// lever l'#ancre
	if (preg_match(',^([^#]*)(#.*)$,', $url, $r)) {
		$url = $r[1];
	}
	$ancre = preg_replace(array('/^[^-_a-zA-Z0-9]+/', '/[^-_a-zA-Z0-9]/'), array('', '-'),
					translitteration($ancre));
	return $url .'#'. $ancre;
}

//
// pour calcul du nom du fichier cache et autres
//
// http://doc.spip.org/@nettoyer_uri
function nettoyer_uri() {
	$uri1 = $GLOBALS['REQUEST_URI'];
	do {
		$uri = $uri1;
		$uri1 = preg_replace
			(',([?&])(PHPSESSID|(var_[^=&]*))=[^&]*(&|$),i',
			'\1', $uri);
	} while ($uri<>$uri1);

	return preg_replace(',[?&]$,', '', $uri1);
}

//
// donner l'URL de base d'un lien vers "soi-meme", modulo
// les trucs inutiles
//
// http://doc.spip.org/@self
function self($amp = '&amp;', $root = false) {
	$url = nettoyer_uri();
	if (!$root)
		$url = preg_replace(',^[^?]*/,', '', $url);

	// ajouter le cas echeant les variables _POST['id_...']
	foreach ($_POST as $v => $c)
		if (substr($v,0,3) == 'id_')
			$url = parametre_url($url, $v, $c, '&');

	// supprimer les variables sans interet
	if (test_espace_prive()) {
		$url = preg_replace (',([?&])('
		.'lang|set_options|set_couleur|set_disp|set_ecran|show_docs|'
		.'changer_lang|var_lang|action)=[^&]*,i', '\1', $url);
		$url = preg_replace(',([?&])[&]+,', '\1', $url);
		$url = preg_replace(',[&]$,', '\1', $url);
	}

	// eviter les hacks
	$url = htmlspecialchars($url);

	// &amp; ?
	if ($amp != '&amp;')
		$url = str_replace('&amp;', $amp, $url);

	// Si c'est vide, donner './'
	$url = preg_replace(',^$,', './', $url);

	return $url;
}

// Indique si on est dans l'espace prive
// http://doc.spip.org/@test_espace_prive
function test_espace_prive() {
	return defined('_ESPACE_PRIVE') ? _ESPACE_PRIVE : false;
}

//
// Traduction des textes de SPIP
//
// http://doc.spip.org/@_T
function _T($texte, $args=array()) {

	static $traduire=false ;

 	if (!$traduire)
		$traduire = charger_fonction('traduire', 'inc');
	$text = $traduire($texte,$GLOBALS['spip_lang']);

	if (!$text) 
		// pour les chaines non traduites
		$text =	str_replace('_', ' ',
			 (($n = strpos($texte,':')) === false ? $texte :
				substr($texte, $n+1)));

	while (list($name, $value) = @each($args))
		$text = str_replace ("@$name@", $value, $text);
	return $text;

}

// chaines en cours de traduction
// http://doc.spip.org/@_L
function _L($text, $args=array()) {
	while (list($name, $value) = @each($args))
		$text = str_replace ("@$name@", $value, $text);
	if ($GLOBALS['test_i18n'])
		return "<span style='color:red;'>$text</span>";
	else
		return $text;
}

// Afficher "ecrire/data/" au lieu de "data/" dans les messages
// ou tmp/ au lieu de ../tmp/
// http://doc.spip.org/@joli_repertoire
function joli_repertoire($rep) {
	$a = substr($rep,0,1);
	if ($a<>'.' AND $a<>'/')
		$rep = (_DIR_RESTREINT?'':_DIR_RESTREINT_ABS).$rep;
	$rep = preg_replace(',(^\.\.\/),', '', $rep);
	return $rep;
}


//
// spip_timer : on l'appelle deux fois et on a la difference, affichable
//
// http://doc.spip.org/@spip_timer
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


// Renvoie False si un fichier n'est pas plus vieux que $duree secondes,
// sinon renvoie True et le date sauf si ca n'est pas souhaite
// http://doc.spip.org/@spip_touch
function spip_touch($fichier, $duree=0, $touch=true) {
	if ($duree) {
		clearstatcache();
		if ((@$f=filemtime($fichier)) AND ($f >= time() - $duree))
			return false;
	}
	if ($touch!==false) {
		if (!@touch($fichier)) { spip_unlink($fichier); @touch($fichier); };
		@chmod($fichier, _SPIP_CHMOD & ~0111);
	}
	return true;
}

// Ce declencheur de tache de fond, de l'espace prive (cf inc_presentation)
// et de l'espace public (cf #SPIP_CRON dans inc_balise), est appelee
// par un background-image  car contrairement a un iframe vide, 
// les navigateurs ne diront pas qu'ils n'ont pas fini de charger,
// c'est plus rassurant.
// C'est aussi plus discret qu'un <img> sous un navigateur non graphique.

// http://doc.spip.org/@action_cron
function action_cron() {
	include_spip('inc/headers');
	http_status(204); // No Content
	header("Connection: close");
	cron (2);
}

// cron() : execution des taches de fond
// Le premier argument indique l'intervalle demande entre deux taches 
// par defaut, 60 secondes (quand il est appele par public.php)
// il vaut 2 quand il est appele par ?action=cron, voire 0 en urgence
// On peut lui passer en 2e arg le tableau de taches attendu par inc_genie()
// Retourne Vrai si un tache a pu etre effectuee

// http://doc.spip.org/@cron
function cron ($gourmand=false, $taches= array()) {

	// Si base inaccessible, laisser tomber.
	if (!spip_connect()) return false; 

	// Si on est gourmand, ou si le fichier gourmand n'existe pas
	// ou est trop vieux (> 60 sec), on va voir si un cron est necessaire.
	// Au passage si on est gourmand on le dit aux autres
	if (spip_touch(_DIR_TMP.'cron.lock-gourmand', 60, $gourmand)
	OR ($gourmand!==false)) {

	// Le fichier cron.lock indique la date de la derniere tache
	// Il permet d'imposer qu'il n'y ait qu'une tache a la fois
	// et 2 secondes minimum entre chaque:
	// ca soulage le serveur et ca evite
	// les conflits sur la base entre taches.

	if (spip_touch(_DIR_TMP.'cron.lock', 
			(is_int($gourmand) ? $gourmand : 2))) {
			$genie = charger_fonction('genie', 'inc', true);
			if ($genie) {
				$genie($taches);
				// redater a la fin du cron
				// car il peut prendre plus de 2 secondes.
				spip_touch(_DIR_TMP.'cron.lock', 0);
				return true;
			}
		}# else spip_log("busy");
	}
	return false;
}


// transformation XML des "&" en "&amp;"
// http://doc.spip.org/@quote_amp
function quote_amp($u) {
	return preg_replace(
		"/&(?![a-z]{0,4}\w{2,3};|#x?[0-9a-f]{2,5};)/i",
		"&amp;",$u);
}

// Transforme n'importe quel champ en une chaine utilisable
// en PHP ou Javascript en toute securite
// < ? php $x = '[(#TEXTE|texte_script)]'; ? >
// http://doc.spip.org/@texte_script
function texte_script($texte) {
	return str_replace('\'', '\\\'', str_replace('\\', '\\\\', $texte));
}

// la fonction _chemin ajoute un repertoire au chemin courant si un repertoire lui est passe en parametre
// retourne le chemin courant sinon, sous forme de array
// seul le dossier squelette peut etre modifie en dehors de cette fonction, pour raison historique
// http://doc.spip.org/@_chemin
function _chemin($dir_path=NULL){
	static $path_base = NULL;
	static $path_full = NULL;
	if ($path_base==NULL){
		// Chemin standard depuis l'espace public
		$path = defined('_SPIP_PATH') ? _SPIP_PATH : 
			_DIR_RACINE.':'.
			_DIR_RACINE.'dist/:'.
			_DIR_RESTREINT.':';
		// Ajouter squelettes/
		if (@is_dir(_DIR_RACINE.'squelettes'))
			$path = _DIR_RACINE.'squelettes/:' . $path;
		foreach (explode(':', $path) as $dir) {
			if (strlen($dir) AND substr($dir,-1) != '/')
				$dir .= "/";
			$path_base[] = $dir;
		}
		$path_full = $path_base;
		// Et le(s) dossier(s) des squelettes nommes
		if (strlen($GLOBALS['dossier_squelettes']))
			foreach (array_reverse(explode(':', $GLOBALS['dossier_squelettes'])) as $d)
				array_unshift($path_full, ($d[0] == '/' ? '' : _DIR_RACINE) . $d . '/');
		
	}
	if ($dir_path===NULL) return $path_full;

	if (strlen($dir_path)){
		if ($dir_path{0}!='/')
			$dir_path = $dir_path;
		if (substr($dir_path,-1) != '/')
			$dir_path .= "/";
		if (!in_array($dir_path,$path_base)){
			$tete = "";
			if (reset($path_base)==_DIR_RACINE.'squelettes/')
				$tete = array_shift($path_base);
			
			array_unshift($path_base,$dir_path);
			if (strlen($tete))
				array_unshift($path_base,$tete);
		}
	}
	$path_full = $path_base;
	// Et le(s) dossier(s) des squelettes nommes
	if (strlen($GLOBALS['dossier_squelettes']))
		foreach (array_reverse(explode(':', $GLOBALS['dossier_squelettes'])) as $d)
			array_unshift($path_full, ($d[0] == '/' ? '' : _DIR_RACINE) . $d . '/');
		
	return $path_full;
}

// http://doc.spip.org/@creer_chemin
function creer_chemin() {
	$path_a = _chemin();
	static $c = '';

	// provisoire, a remplacer par un spip_unlink sur les fichiers compiles lors d'un prochain upgrade
	if (isset($GLOBALS['plugins'])){
		$c = '';
		foreach($GLOBALS['plugins'] as $dir) {
			$path_base = _chemin(_DIR_PLUGINS.$dir);
		}
		unset($GLOBALS['plugins']);
	}
	// on calcule le chemin si le dossier skel a change
	if ($c != $GLOBALS['dossier_squelettes']) {
		// assurer le non plantage lors de la montee de version :
		$c = $GLOBALS['dossier_squelettes'];
		$path_a = _chemin(''); // forcer un recalcul du chemin
	}
	return $path_a;
}

//
// chercher un fichier $file dans le SPIP_PATH
// si on donne un sous-repertoire en 2e arg optionnel, il FAUT le / final
// si 3e arg vrai, on inclut si ce n'est fait.

// http://doc.spip.org/@find_in_path
function find_in_path ($file, $dirname='', $include=false) {
	static $files=array(), $dirs=array();

	$a = strrpos($file,'/');
	if ($a !== false) {
		$dirname .= substr($file, 0, ++$a);
		$file = substr($file, $a);
	}

	if (isset($files[$dirname][$file])) {
		if ($include) include_once $files[$dirname][$file];
		return  $files[$dirname][$file];
	}
	foreach(creer_chemin() as $dir) {
		if (!isset($dirs[$a = $dir . $dirname]))
			$dirs[$a] = is_dir($a);
		if ($dirs[$a]) {
			if (is_readable($a .= $file)) {
				if ($include) include_once $a;
				return $files[$dirname][$file] = $a;
			}
		}
	}
}


// http://doc.spip.org/@find_all_in_path
function find_all_in_path($dir,$pattern){
	$liste_fichiers=array();
	$maxfiles = 10000;
	
	// Parcourir le chemin
	foreach (creer_chemin() as $d)
		if (@is_dir($f = $d.$dir)){
			$liste = preg_files($d.$dir,$pattern,$maxfiles-count($liste_fichiers),false);
			foreach($liste as $chemin){
				$nom = basename($chemin);
				// ne prendre que les fichiers pas deja trouves
				// car find_in_path prend le premier qu'il trouve,
				// les autres sont donc masques
				if (!isset($liste_fichiers[$nom]))
					$liste_fichiers[$nom] = $chemin;
			}
		}
			
	return $liste_fichiers;
}

// predicat sur les scripts de ecrire qui n'authentifient pas par cookie

// http://doc.spip.org/@autoriser_sans_cookie
function autoriser_sans_cookie($nom)
{
  static $autsanscookie = array('aide_index', 'install', 'admin_repair');
  $nom = preg_replace('/.php[3]?$/', '', basename($nom));
  return in_array($nom, $autsanscookie);
}

// Cette fonction charge le bon inc-urls selon qu'on est dans l'espace
// public ou prive, la presence d'un (old style) inc-urls.php3, etc.
// http://doc.spip.org/@charger_generer_url
function charger_generer_url() {
	static $ok;

	// espace prive
	if (!_DIR_RESTREINT)
		include_spip('inc/urls');

	// espace public
	else {
		if ($ok++) return; # fichier deja charge
		// fichier inc-urls ? (old style)
		if (@is_readable($f = _DIR_RACINE.'inc-urls.php3')
		OR @is_readable($f = _DIR_RACINE.'inc-urls.php')
		OR $f = find_in_path('inc-urls-'.$GLOBALS['type_urls'].'.php3'))
			include_once($f);

		else include_spip('urls/'.$GLOBALS['type_urls']);
	}
}

// Sur certains serveurs, la valeur 'Off' tient lieu de false dans certaines
// variables d'environnement comme $_SERVER[HTTPS] ou ini_get(register_globals)
// http://doc.spip.org/@test_valeur_serveur
function test_valeur_serveur($truc) {
	if (!$truc) return false;
	if (strtolower($truc) == 'off') return false;
	return true;
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
// http://doc.spip.org/@url_de_base
function url_de_base() {

	static $url;

	if ($url)
		return $url;

	// cas particulier des sites filtres par un proxy entrant
	// cf. http://trac.rezo.net/trac/spip/ticket/401
	// le forwarded_host peut prendre plusieurs valeurs separees par des virgules
	// chez ovh notamment
	if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])){
		$server = explode(',',$_SERVER['HTTP_X_FORWARDED_HOST']);
		$server = trim(reset($server));
	}
	else
		$server = $_SERVER['HTTP_HOST'];

	$http = (
		(isset($_SERVER["SCRIPT_URI"]) AND
			substr($_SERVER["SCRIPT_URI"],0,5) == 'https')
		OR (isset($_SERVER['HTTPS']) AND
		    test_valeur_serveur($_SERVER['HTTPS']))
	) ? 'https' : 'http';
	# note : HTTP_HOST contient le :port si necessaire
	$myself = $http.'://'.$server.$GLOBALS['REQUEST_URI'];

	# supprimer la chaine de GET
	list($myself) = explode('?', $myself);

	# supprimer n sous-repertoires
	$url = join('/', array_slice(explode('/', $myself), 0, -1-$GLOBALS['profondeur_url'])).'/';

	return $url;
}


// Pour une redirection, la liste des arguments doit etre separee par "&"
// Pour du code XHTML, ca doit etre &amp;
// Bravo au W3C qui n'a pas ete capable de nous eviter ca
// faute de separer proprement langage et meta-langage

// Attention, X?y=z et "X/?y=z" sont completement differents!
// http://httpd.apache.org/docs/2.0/mod/mod_dir.html

// http://doc.spip.org/@generer_url_ecrire
function generer_url_ecrire($script='', $args="", $no_entities=false, $rel=false) {
	if (!$rel)
		$rel = url_de_base() . _DIR_RESTREINT_ABS . _SPIP_ECRIRE_SCRIPT;
	else if (!is_string($rel))
		$rel = _DIR_RESTREINT ? _DIR_RESTREINT :
			('./'  . _SPIP_ECRIRE_SCRIPT);

	@list($script, $ancre) = split('#', $script);
	if ($script AND ($script<>'accueil' OR $rel))
		$args = "?exec=$script" . (!$args ? '' : "&$args");
	elseif ($args)
		$args ="?$args";
	if ($ancre) $args .= "#$ancre";
	return $rel . ($no_entities ? $args : str_replace('&', '&amp;', $args));
}

// http://doc.spip.org/@generer_url_retour
function generer_url_retour($script, $args="")
{
	return rawurlencode(generer_url_ecrire($script, $args, true, true));
}

//
// Adresse des scripts publics (a passer dans inc-urls...)
//

// Detecter le fichier de base, a la racine, comme etant spip.php ou ''
// dans le cas de '', un $default = './' peut servir (comme dans urls/page.php)
// http://doc.spip.org/@get_spip_script
function get_spip_script($default='') {
	# cas define('_SPIP_SCRIPT', '');
	if (_SPIP_SCRIPT)
		return _SPIP_SCRIPT;
	else
		return $default;
}

// http://doc.spip.org/@generer_url_public
function generer_url_public($script='', $args="", $no_entities=false, $rel=false) {
	// si le script est une action (spip_pass, spip_inscription),
	// standardiser vers la nouvelle API
  	// [hack temporaire pour faire fonctionner #URL_PAGE{spip_pass} ]

	if (preg_match(',^spip_(.*),', $script, $regs)) {
		$args = "action=" . $regs[1]  .($args ? "&$args" :'');
		$script = "";
	}

	$action = get_spip_script();
	if ($script)
		$action = parametre_url($action, 'page', $script, '&');

	if ($args)
		$action .=
			(strpos($action, '?') !== false ? '&' : '?') . $args;

	if (!$no_entities)
		$action = quote_amp($action);

	return ($rel ? '' : url_de_base()) . $action;
}

// http://doc.spip.org/@generer_url_prive
function generer_url_prive($script, $args="", $no_entities=false) {

	$action = 'prive.php';
	if ($script)
		$action = parametre_url($action, 'page', $script, '&');

	if ($args)
		$action .=
			(strpos($action, '?') !== false ? '&' : '?') . $args;

	if (!$no_entities)
		$action = quote_amp($action);

	return url_de_base() . _DIR_RESTREINT_ABS . $action;
}

// Pour les formulaires en methode POST,
// mettre le nom du script a la fois en input-hidden et dans le champ action:
// 1) on peut ainsi memoriser le signet comme si c'etait un GET
// 2) ca suit http://en.wikipedia.org/wiki/Representational_State_Transfer

// http://doc.spip.org/@generer_form_ecrire
function generer_form_ecrire($script, $corps, $atts='', $submit='') {
	global $spip_lang_right;

	return "<form action='"
	. ($script ? generer_url_ecrire($script) : '')
	. "' "
	. ($atts ? $atts : " method='post'")
	.  "><div>\n"
	. "<input type='hidden' name='exec' value='$script' />"
	. $corps
	. (!$submit ? '' :
	     ("<div style='text-align: $spip_lang_right'><input class='fondo' type='submit' value='$submit' /></div>"))
	. "</div></form>\n";
}

// Attention, JS/Ajax n'aime pas le melange de param GET/POST
// On n'applique pas la recommandation ci-dessus pour les scripts publics
// qui ne sont pas destines a etre mis en signets 

// http://doc.spip.org/@generer_form_public
function generer_form_public($script, $corps, $atts='') {
	return "\n<form action='" . generer_url_public() .
	  "'" .
	  $atts .
	  ">\n" .
	  "<div>" .
  	  "\n<input type='hidden' name='action' value='$script' />" .
	  $corps .
	  "</div></form>";
}

// http://doc.spip.org/@generer_url_action
function generer_url_action($script, $args="", $no_entities=false ,$rel = false) {

	return  generer_url_public('',
				  "action=$script" .($args ? "&$args" : ''),
				  $no_entities,$rel);
}


// Dirty hack contre le register_globals a 'Off' (PHP 4.1.x)
// A remplacer (bientot ?) par une gestion propre des variables admissibles ;-)
// NB: c'est une fonction de maniere a ne pas pourrir $GLOBALS
// http://doc.spip.org/@spip_register_globals
function spip_register_globals() {

	define('_FEED_GLOBALS', false); // si ca marche on simplifiera tout ca

	// Liste des variables dont on refuse qu'elles puissent provenir du client
	$refuse_gpc = array (
		# inc-public
		'fond', 'delais' /*,

		# ecrire/inc_auth (ceux-ci sont bien verifies dans $_SERVER)
		'REMOTE_USER',
		'PHP_AUTH_USER', 'PHP_AUTH_PW'
		*/
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
	if (test_valeur_serveur(@ini_get('register_globals'))) {
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
	else if (_FEED_GLOBALS) {
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


// Fonction d'initialisation, appellee dans inc_version ou mes_options
// Elle definit les repertoires et fichiers non partageables
// et indique dans $test_dirs ceux devant etre accessibles en ecriture
// mais ne touche pas a cette variable si elle est deja definie
// afin que mes_options.php puisse en specifier d'autres.
// Elle definit ensuite les noms des fichiers et les droits.
// Puis simule un register_global=on securise.

// http://doc.spip.org/@spip_initialisation
function spip_initialisation($pi=NULL, $pa=NULL, $ti=NULL, $ta=NULL) {

	static $too_late = 0;
	if ($too_late++) return;

	define('_DIR_IMG', $pa);
	define('_DIR_LOGOS', $pa);
	define('_DIR_IMG_ICONES', _DIR_LOGOS . "icones/");

	define('_DIR_DUMP', $ti . "dump/");
	define('_DIR_SESSIONS', $ti . "sessions/");
	define('_DIR_TRANSFERT', $ti . "upload/");
	define('_DIR_CACHE', $ti . "cache/");
	define('_DIR_CACHE_XML', _DIR_CACHE . "xml/");
	define('_DIR_SKELS',  _DIR_CACHE . "skel/");
	define('_DIR_AIDE',  _DIR_CACHE . "aide/");
	define('_DIR_TMP', $ti);

	define('_FILE_META', $ti . 'meta_cache.txt');

	define('_DIR_VAR', $ta);

	define('_DIR_ETC', $pi);
	define('_DIR_CONNECT', $pi);
	define('_DIR_CHMOD', $pi);

	define('_DIR_LOG', _DIR_TMP);
	define('_FILE_LOG', 'spip');
	define('_FILE_LOG_SUFFIX', '.log');

	if (!isset($GLOBALS['test_dirs']))
	  // Pas $pi car il est bon de le mettre hors ecriture apres intstall
	  // il sera rajoute automatiquement si besoin a l'etape 2 de l'install
		$GLOBALS['test_dirs'] =  array($pa, $ti, $ta);

	// Le fichier de connexion a la base de donnees
	// tient compte des anciennes versions (inc_connect...)
	define('_FILE_CONNECT_INS', 'connect');
	define('_FILE_CONNECT',
		(@is_readable($f = _DIR_CONNECT . _FILE_CONNECT_INS . '.php') ? $f
	:	(@is_readable($f = _DIR_RESTREINT . 'inc_connect.php') ? $f
	:	(@is_readable($f = _DIR_RESTREINT . 'inc_connect.php3') ? $f
	:	false))));

	// Le fichier de reglages des droits
	define('_FILE_CHMOD_INS', 'chmod');
	define('_FILE_CHMOD',
		(@is_readable($f = _DIR_CHMOD . _FILE_CHMOD_INS . '.php') ? $f
	:	false));

	define('_FILE_LDAP', 'ldap.php');

	define('_FILE_TMP_SUFFIX', '.tmp.php');
	define('_FILE_CONNECT_TMP', _DIR_CONNECT . _FILE_CONNECT_INS . _FILE_TMP_SUFFIX);
	define('_FILE_CHMOD_TMP', _DIR_CHMOD . _FILE_CHMOD_INS . _FILE_TMP_SUFFIX);

	// Definition des droits d'acces en ecriture
	if (!defined('_SPIP_CHMOD')) {
		if(_FILE_CHMOD)
			include_once _FILE_CHMOD;
		else
			define('_SPIP_CHMOD', 0777);
	}
	
	// la taille maxi des logos (0 : pas de limite)
	define('_LOGO_MAX_SIZE', 0); # poids en ko
	define('_LOGO_MAX_WIDTH', 0); # largeur en pixels
	define('_LOGO_MAX_HEIGHT', 0); # hauteur en pixels
	
	define('_DOC_MAX_SIZE', 0); # poids en ko

	define('_IMG_MAX_SIZE', 0); # poids en ko
	define('_IMG_MAX_WIDTH', 0); # largeur en pixels
	define('_IMG_MAX_HEIGHT', 0); # hauteur en pixels

	// Le charset par defaut lors de l'installation
	define('_DEFAULT_CHARSET', 'utf-8');

	// qq chaines standard
	define('_ACCESS_FILE_NAME', '.htaccess');
	define('_AUTH_USER_FILE', '.htpasswd');
	define('_SPIP_DUMP', 'dump@nom_site@@stamp@.xml');
	define('_CACHE_RUBRIQUES', _DIR_TMP.'menu-rubriques-cache.txt');

	define('_DOCTYPE_ECRIRE', 
		// "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n");
		//"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n");
		"<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n");
	       // "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.1 //EN' 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd'>\n");
	define('_DOCTYPE_AIDE', 
	       "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01 Frameset//EN' 'http://www.w3.org/TR/1999/REC-html401-19991224/frameset.dtd'>");

	// L'adresse de base du site ; on peut mettre '' si la racine est geree par
	// le script de l'espace public, alias  index.php
	define('_SPIP_SCRIPT', 'spip.php');

	// le script de l'espace prive
	// Mettre a "index.php" si DirectoryIndex ne le fait pas ou pb connexes:
	// les anciens IIS n'acceptent pas les POST sur ecrire/ (#419)
	// meme pb sur thttpd cf. http://forum.spip.org/fr_184153.html

	define('_SPIP_ECRIRE_SCRIPT', // true ? #decommenter ici et commenter la
	       preg_match(',IIS|thttpd,',$_SERVER['SERVER_SOFTWARE']) ?
	       'index.php' : '');

	// le nom du repertoire plugins/
	define('_DIR_PLUGINS', _DIR_RACINE . "plugins/");

	// *********** traiter les variables ************

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

	// Remplir $GLOBALS avec $_GET et $_POST
	spip_register_globals();

	// appliquer le cookie_prefix
	if ($GLOBALS['cookie_prefix'] != 'spip') {
		include_spip('inc/cookie');
		recuperer_cookies_spip($GLOBALS['cookie_prefix']);
	}

	define('_SPIP_AJAX',  (!isset($_COOKIE['spip_accepte_ajax'])) 
		? 1
	       : (($_COOKIE['spip_accepte_ajax'] != -1) ? 1 : 0));

	//
	// Capacites php (en fonction de la version)
	//
	$GLOBALS['flag_gz'] = function_exists("gzencode"); #php 4.0.4
	$GLOBALS['flag_ob'] = (function_exists("ob_start")
		&& function_exists("ini_get")
		&& (@ini_get('max_execution_time') > 0)
		&& !strstr(ini_get('disable_functions'), 'ob_'));
	$GLOBALS['flag_sapi_name'] = function_exists("php_sapi_name");
	$GLOBALS['flag_get_cfg_var'] = (@get_cfg_var('error_reporting') != "");
	$GLOBALS['flag_upload'] = (!$GLOBALS['flag_get_cfg_var'] ||
		(get_cfg_var('upload_max_filesize') > 0));


	// Sommes-nous dans l'empire du Mal ?
	// (ou sous le signe du Pingouin, ascendant GNU ?)
	if (strpos($_SERVER['SERVER_SOFTWARE'], '(Win') !== false){
		define ('_OS_SERVEUR', 'windows');
		define('_SPIP_LOCK_MODE',1); // utiliser le flock php
	}
	else {
		define ('_OS_SERVEUR', '');
		define('_SPIP_LOCK_MODE',1); // utiliser le flock php
		#define('_SPIP_LOCK_MODE',2); // utiliser le nfslock de spip mais link() est tres souvent interdite
	}

	// Compatibilite avec serveurs ne fournissant pas $REQUEST_URI
	if (isset($_SERVER['REQUEST_URI'])) {
		$GLOBALS['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
	} else {
		$GLOBALS['REQUEST_URI'] = $_SERVER['PHP_SELF'];
		if ($_SERVER['QUERY_STRING']
		AND !strpos($_SERVER['REQUEST_URI'], '?'))
			$GLOBALS['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
	}

	//
	// Module de lecture/ecriture/suppression de fichiers utilisant flock()
	// (non surchargeable en l'etat ; attention si on utilise include_spip()
	// pour le rendre surchargeable, on va provoquer un reecriture
	// systematique du noyau ou une baisse de perfs => a etudier)
	include_once _DIR_RESTREINT . 'inc/flock.php';

	// Duree de validite de l'alea pour les cookies et ce qui s'ensuit.
	define('_RENOUVELLE_ALEA', 12 * 3600);

	// charger les meta si possible et renouveller l'alea au besoin
	// charge aussi effacer_meta et ecrire_meta
	$inc_meta = charger_fonction('meta', 'inc'); 
	$inc_meta();

	// nombre de repertoires depuis la racine
	// on compare a l'adresse donnee en meta ; si celle-ci est fausse
	// le calcul est faux. Meilleure idee ??
	if (!_DIR_RESTREINT)
		$GLOBALS['profondeur_url'] = 1;
	else {
		$uri = isset($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI']) : '';
		if (!$uri OR  !isset($GLOBALS['meta']['adresse_site']))
			$GLOBALS['profondeur_url'] = 0;
		else {
			$GLOBALS['profondeur_url'] = max(0, 1+
				substr_count($uri[0], '/')
				- substr_count($GLOBALS['meta']['adresse_site'],'/'));
		}
	}
	// s'il y a un cookie ou PHP_AUTH, initialiser visiteur_session
	if (_FILE_CONNECT) verifier_visiteur();

	# nombre de pixels maxi pour calcul de la vignette avec gd
	define('_IMG_GD_MAX_PIXELS', isset($GLOBALS['meta']['max_taille_vignettes'])?$GLOBALS['meta']['max_taille_vignettes']:0); 
}

// Annuler les magic quotes \' sur GET POST COOKIE et GLOBALS ;
// supprimer aussi les eventuels caracteres nuls %00, qui peuvent tromper
// la commande is_readable('chemin/vers/fichier/interdit%00truc_normal')
// http://doc.spip.org/@spip_desinfecte
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

//  retourne le statut du visiteur s'il s'annonce

// http://doc.spip.org/@verifier_visiteur
function verifier_visiteur() {

	// Demarrer une session NON AUTHENTIFIEE si on donne son nom
	// dans un formulaire sans login (ex: #FORMULAIRE_FORUM)
	// Attention on separe bien session_nom et nom, pour eviter
	// les melanges entre donnees SQL et variables plus aleatoires
	$variables_session = array('nom', 'email');
	while (list(,$var) = each($variables_session)) {
		if (_request('session_'.$var) !== null) {
			$init = true;
			break;
		}
	}
	if (isset($init)) {
		@spip_initialisation();
		$session = charger_fonction('session', 'inc');
		$session();
		include_spip('inc/texte');
		foreach($variables_session as $var)
			if (($a = _request('session_'.$var)) !== null)
				$GLOBALS['visiteur_session']['session_'.$var] = safehtml($a);
		if (!isset($GLOBALS['visiteur_session']['id_auteur']))
			$GLOBALS['visiteur_session']['id_auteur'] = 0;
		ajouter_session($GLOBALS['visiteur_session']);
		return 0;
	}


	if (isset($_COOKIE['spip_session']) OR
	(isset($_SERVER['PHP_AUTH_USER'])  AND !$GLOBALS['ignore_auth_http'])) {

		// Rq: pour que cette fonction marche depuis mes_options
		// il faut forcer l'init si ce n'est fait
		@spip_initialisation();

		$session = charger_fonction('session', 'inc');
		if ($session()) {
			return $GLOBALS['visiteur_session']['statut'];
		}
		include_spip('inc/actions');
		return verifier_php_auth();
	}

	return false;
}

// selectionne la langue donnee en argument et memorise la courante
// ou restaure l'ancienne si appel sans argument
// On pourrait economiser l'empilement en cas de non changemnt
// et lui faire retourner False pour prevenir l'appelant
// Le noyau de Spip sait le faire, mais pour assurer la compatibilite
// cette fonction retourne toujours non False

// http://doc.spip.org/@lang_select
function lang_select ($lang=NULL) {
	static $pile_langues = array();
	include_spip('inc/lang');
	if ($lang === NULL)
		$lang = array_pop($pile_langues);
	else {
		array_push($pile_langues, $GLOBALS['spip_lang']);
	}
	if ($lang == $GLOBALS['spip_lang'])
		return $lang;
	changer_langue($lang);
	return $lang;
}


// Renvoie une chaine qui decrit la session courante pour savoir si on peut
// utiliser un cache enregistre pour cette session.
// Par convention cette chaine ne doit pas contenir de caracteres [^0-9A-Za-z]
// Attention on ne peut *pas* inferer id_auteur a partir de la session, qui
// est une chaine arbitraire
// Cette chaine est courte (8 cars) pour pouvoir etre utilisee dans un nom
// de fichier cache
// http://doc.spip.org/@spip_session
function spip_session($force = false) {
	static $session;
	if ($force OR !isset($session)) {
		$s = pipeline('definir_session',
			$GLOBALS['visiteur_session']
			? serialize($GLOBALS['visiteur_session'])
				. '_' . @$_COOKIE['spip_session']
			: ''
		);
		$session = $s ? substr(md5($s), 0, 8) : '';
	}
	#spip_log('session: '.$session);
	return $session;
}

//
// Aide, aussi depuis l'espace prive a present.
//  Surchargeable mais pas d'ereur fatale si indisponible.
//

// http://doc.spip.org/@aide
function aide($aide='') {
	$aider = charger_fonction('aider', 'inc', true);
	return $aider ?  $aider($aide) : '';
}

// normalement il faudrait creer exec/info.php, mais pour mettre juste ca:
// http://doc.spip.org/@exec_info_dist
function exec_info_dist() {
	global $connect_statut;
	if ($connect_statut == '0minirezo')
		phpinfo();
	else
		echo "pas admin";
}

// La fonction de base de SPIP : un squelette + un contexte => une page
// on recupere le resultat sous la forme d'une $page['texte', 'headers'...]
// options :
// 'protect_xml' => false,  conserver le \1 du xml-hack
// http://doc.spip.org/@evaluer_fond
function evaluer_fond ($fond, $contexte=array(), $options=array(), $connect=null) {
	include_spip('public/assembler');

	// on est peut etre dans l'espace prive au moment de l'appel
	if (!isset($GLOBALS['_INC_PUBLIC'])) $GLOBALS['_INC_PUBLIC'] = 0;
	$GLOBALS['_INC_PUBLIC']++;

	if (isset($contexte['fond'])
	AND $fond === '')
		$fond = $contexte['fond'];

	$page = inclure_page($fond, $contexte, $connect);
	if ($GLOBALS['flag_ob'] AND ($page['process_ins'] != 'html')) {
		ob_start();
		xml_hack($page, true);
		eval('?' . '>' . $page['texte']);
		$page['texte'] = ob_get_contents();
		xml_hack($page);
		$page['process_ins'] = 'html';
		ob_end_clean();
	}

	$GLOBALS['_INC_PUBLIC']--;

	return $page;
}


/*
 * Bloc de compatibilite : quasiment tous les plugins utilisent ces fonctions
 * desormais depreciees ; plutot que d'obliger tout le monde a charger
 * vieilles_defs, on va assumer l'histoire de ces 3 fonctions ubiquitaires
 */
// Fonction depreciee
// http://doc.spip.org/@lire_meta
function lire_meta($nom) {
	return $GLOBALS['meta'][$nom];
}

// Fonction depreciee
// http://doc.spip.org/@ecrire_metas
function ecrire_metas() {}

// Fonction depreciee, cf. http://doc.spip.org/@sql_fetch
// http://doc.spip.org/@spip_fetch_array
function spip_fetch_array($r, $t=NULL) {
	if (!isset($t)) {
		if ($r) return sql_fetch($r);
	} else {
		spip_log("appel deprecie de spip_fetch_array(..., $t)", 'vieilles_defs');
		if ($r) return mysql_fetch_array($r, $t);
	}
}

?>
