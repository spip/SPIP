<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

//
// Utilitaires indispensables autour du serveur Http.
//

// charge un fichier perso ou, a defaut, standard
// et retourne si elle existe le nom de la fonction homonyme (exec_$nom),
// ou de suffixe _dist
// Peut etre appelee plusieurs fois, donc optimiser
// http://doc.spip.org/@charger_fonction
function charger_fonction($nom, $dossier='exec', $continue=false) {

	if (strlen($dossier) AND substr($dossier,-1) != '/') $dossier .= '/';

	if (function_exists($f = str_replace('/','_',$dossier) . $nom))
		return $f;
	if (function_exists($g = $f . '_dist'))
		return $g;

	// Sinon charger le fichier de declaration si plausible

	if (!preg_match(',^\w+$,', $f))
		die(htmlspecialchars($nom)." pas autorise");

	// passer en minuscules (cf les balises de formulaires)
	// et inclure le fichier
	if (!$inc = include_spip($dossier.($d = strtolower($nom)))
		// si le fichier truc/machin/nom.php n'existe pas,
		// la fonction peut etre definie dans truc/machin.php qui regroupe plusieurs petites fonctions
		AND strlen(dirname($dossier)) AND dirname($dossier)!='.')
		include_spip(substr($dossier,0,-1));
	if (function_exists($f)) return $f;
	if (function_exists($g)) return $g;

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
// on passe $val par reference pour limiter les allocations memoire
// http://doc.spip.org/@minipipe
function minipipe($fonc,&$val){
	// fonction
	if (function_exists($fonc))
		$val = call_user_func($fonc, $val);

	// Class::Methode
	else if (preg_match("/^(\w*)::(\w*)$/S", $fonc, $regs)
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
		if (!($ok = @is_readable($charger = _CACHE_PIPELINES))) {
			include_spip('inc/plugin');
			// generer les fichiers php precompiles
			// de chargement des plugins et des pipelines
			actualise_plugins_actifs();
			if (!($ok = @is_readable($charger)))
				spip_log("fichier $charger pas cree");
		}

		if ($ok)
			include_once $charger;
	}

	// appliquer notre fonction si elle existe
	$fonc = 'execute_pipeline_'.strtolower($action);
	if (function_exists($fonc)) {
		$val = $fonc($val);
	}
	// plantage ?
	else {
		include_spip('inc/plugin');
		// on passe $action en arg pour creer la fonction meme si le pipe
		// n'est defini nul part ; vu qu'on est la c'est qu'il existe !
		actualise_plugins_actifs(strtolower($action));
		spip_log("fonction $fonc absente : pipeline desactive");
	}

	// si le flux est une table qui encapsule donnees et autres
	// on ne ressort du pipe que les donnees
	// array_key_exists pour php 4.1.0
	if (is_array($val)
	  AND count($val)==2
	  AND (array_key_exists('data',$val)))
		$val = $val['data'];
	return $val;
}

//
// Enregistrement des evenements
//
// http://doc.spip.org/@spip_log
function spip_log($message, $logname=NULL, $logdir=NULL, $logsuf=NULL) {
	$log = charger_fonction('log', 'inc');
	$log( $message, $logname, $logdir, $logsuf);
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

	// Si on est en ajax et en POST tout a ete encode
	// via encodeURIComponent, il faut donc repasser
	// dans le charset local...
	if (defined('_AJAX')
	AND _AJAX
	AND isset($GLOBALS['meta']['charset'])
	AND $GLOBALS['meta']['charset'] != 'utf-8'
	AND is_string($a)
	AND preg_match(',[\x80-\xFF],', $a)) {
		include_spip('inc/charsets');
		return importer_charset($a, 'utf-8');
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


/**
 * Tester si une url est absolue
 * @param  $url
 * @return bool
 */
function tester_url_absolue($url){
	return preg_match(";^([a-z]+:)?//;Uims",trim($url))?true:false;
}

//
// Prend une URL et lui ajoute/retire un parametre.
// Exemples : [(#SELF|parametre_url{suite,18})] (ajout)
//            [(#SELF|parametre_url{suite,''})] (supprime)
//            [(#SELF|parametre_url{suite})]    (prend $suite dans la _request)
//            [(#SELF|parametre_url{suite[],1})] (tableaux valeurs multiples)
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

	$regexp = ',^(' . str_replace('[]','\[\]',$c) . '[[]?[]]?)(=.*)?$,';
	$ajouts = array_flip(explode('|',$c));
	$u = is_array($v) ? $v : rawurlencode($v);
	// lire les variables et agir
	foreach ($url as $n => $val) {
		if (preg_match($regexp, urldecode($val), $r)) {
			if ($v === NULL) {
				return $r[2]?substr($r[2],1):'';
			}
			// suppression
			elseif (!$v) {
				unset($url[$n]);
			}
	// Ajout. Pour une variable, remplacer au meme endroit,
	// pour un tableau ce sera fait dans la prochaine boucle
			elseif (substr($r[1],-2) != '[]') {
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
		foreach($ajouts as $k => $n) {
		  if (!is_array($v))
		    $url[] = $k .'=' . $u;
		  else {
		    $id = (substr($k,-2) == '[]') ? $k : ($k ."[]");
		    foreach ($v as $w) $url[]= $id .'=' . $w;
		  }
		}
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
// pour le nom du cache, les types_urls et self
//
// http://doc.spip.org/@nettoyer_uri
function nettoyer_uri($reset = null)
{
	static $done = false;
	static $propre = '';
	if (!is_null($reset)) return $propre=$reset;
	if ($done) return $propre;
	$done = true;

	$uri1 = $GLOBALS['REQUEST_URI'];
	do {
		$uri = $uri1;
		$uri1 = preg_replace
			(',([?&])(PHPSESSID|(var_[^=&]*))=[^&]*(&|$),i',
			'\1', $uri);
	} while ($uri<>$uri1);

	return $propre = (preg_replace(',[?&]$,', '', $uri1));
}

//
// donner l'URL de base d'un lien vers "soi-meme", modulo
// les trucs inutiles
//
// http://doc.spip.org/@self
function self($amp = '&amp;', $root = false) {
	$url = nettoyer_uri();
	if (!$root AND (!defined('_SET_HTML_BASE') OR !_SET_HTML_BASE OR !$GLOBALS['profondeur_url']))
		$url = preg_replace(',^[^?]*/,', '', $url);

	// ajouter le cas echeant les variables _POST['id_...']
	foreach ($_POST as $v => $c)
		if (substr($v,0,3) == 'id_')
			$url = parametre_url($url, $v, $c, '&');

	// supprimer les variables sans interet
	if (test_espace_prive()) {
		$url = preg_replace (',([?&])('
		.'lang|show_docs|'
		.'changer_lang|var_lang|action)=[^&]*,i', '\1', $url);
		$url = preg_replace(',([?&])[&]+,', '\1', $url);
		$url = preg_replace(',[&]$,', '\1', $url);
	}

	// eviter les hacks
	$url = htmlspecialchars($url);

	// &amp; ?
	if ($amp != '&amp;')
		$url = str_replace('&amp;', $amp, $url);

	// Si ca demarre par ? ou vide, donner './'
	$url = preg_replace(',^([?].*)?$,', './\1', $url);

	return $url;
}

// Indique si on est dans l'espace prive
// http://doc.spip.org/@test_espace_prive
function test_espace_prive() {
	return defined('_ESPACE_PRIVE') ? _ESPACE_PRIVE : false;
}

/**
 * Verifie la presence d'un plugin active, identifie par son prefix
 *
 *
 * @param string $plugin
 * @return bool
 */
function test_plugin_actif($plugin){
	return ($plugin AND defined('_DIR_PLUGIN_'.strtoupper($plugin)))? true:false;
}

//
// Traduction des textes de SPIP
//
// http://doc.spip.org/@_T
function _T($texte, $args=array(), $class='') {

	static $traduire=false ;

 	if (!$traduire) {
		$traduire = charger_fonction('traduire', 'inc');
		include_spip('inc/lang');
	}
	$text = $traduire($texte,$GLOBALS['spip_lang']);

	if (!strlen($text))
		// pour les chaines non traduites, assurer un service minimum
		$text = str_replace('_', ' ',
			 (($n = strpos($texte,':')) === false ? $texte :
				substr($texte, $n+1)));

	return _L($text, $args, $class);

}

// Remplacer les variables @....@ par leur valeur dans une chaine de langue.
// Aussi appelee quand une chaine n'est pas encore dans les fichiers de langue
// http://doc.spip.org/@_L
function _L($text, $args=array(), $class=NULL) {
	$f = $text;
	if (is_array($args)) {
		foreach ($args as $name => $value) {
			if ($class)
				$value = "<span class='$class'>$value</span>";
			$t = str_replace ("@$name@", $value, $text);
			if ($text !== $t) {unset($args[$name]); $text = $t;}
		}
		// Si des variables n'ont pas ete inserees, le signaler
		// (chaines de langues pas a jour)
		// NOTE: c'est du debug, gere comme tel pour SPIP >= 2.3
		## if ($args) spip_log("$f:  variables inutilisees " . join(', ', array_keys($args)));
	}

	if ($GLOBALS['test_i18n'] AND $class===NULL)
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
function spip_timer($t='rien', $raw = false) {
	static $time;
	$a=time(); $b=microtime();
	// microtime peut contenir les microsecondes et le temps
	$b=explode(' ',$b);
	if (count($b)==2) $a = end($b); // plus precis !
	$b = reset($b);
	if (!isset($time[$t])) {
		$time[$t] = $a + $b;
	} else {
		$p = ($a + $b - $time[$t]) * 1000;
		unset($time[$t]);
#			echo "'$p'";exit;
		if ($raw) return $p;
		if ($p < 1000)
			$s = '';
		else {
			$s = sprintf("%d ", $x = floor($p/1000));
			$p -= ($x*1000);
		}
		return $s . sprintf("%.3f ms", $p);
	}
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
	if (!defined(_CRON_DELAI_GOURMAND))
		define('_CRON_DELAI_GOURMAND',60);
	if (!defined(_CRON_DELAI))
		define('_CRON_DELAI',is_int($gourmand) ? $gourmand : 2);

	// Si on est gourmand, ou si le fichier gourmand n'existe pas
	// ou est trop vieux (> 60 sec), on va voir si un cron est necessaire.
	// Au passage si on est gourmand on le dit aux autres
	if (!_CRON_DELAI_GOURMAND
	 OR spip_touch(_DIR_TMP.'cron.lock-gourmand', _CRON_DELAI_GOURMAND, $gourmand)
	 OR ($gourmand!==false)) {

	// Le fichier cron.lock indique la date de la derniere tache
	// Il permet d'imposer qu'il n'y ait qu'une tache a la fois
	// et 2 secondes minimum entre chaque:
	// ca soulage le serveur et ca evite
	// les conflits sur la base entre taches.

	if (!_CRON_DELAI 
	  OR spip_touch(_DIR_TMP.'cron.lock',_CRON_DELAI)) {
			// Si base inaccessible, laisser tomber.
			if (!spip_connect()) return false;

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

// Production d'une balise Script valide
// http://doc.spip.org/@http_script
function http_script($script, $src='', $noscript='') {
	static $done = array();

	if ($src && !isset($done[$src])){
		$done[$src] = true;
		$src = find_in_path($src, _JAVASCRIPT);
		$src = " src='$src'";
	}
	else $src = '';
	if ($script)
		$script = ("<!--\n" .
		preg_replace(',</([^>]*)>,','<\/\1>', $script) .
		"\n//-->\n");
	if ($noscript)
		$noscript = "<noscript>\n\t$noscript\n</noscript>\n";

	return ($src OR $script OR $noscript)
	? "<script type='text/javascript'$src>$script</script>$noscript"
	: '';
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
			_DIR_RACINE.'squelettes-dist/:'.
			_DIR_RACINE.'prive/:'.
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
		$GLOBALS['path_sig'] = md5(serialize($path_full));
	}
	if ($dir_path===NULL) return $path_full;

	if (strlen($dir_path)){
		$tete = "";
		if (reset($path_base)==_DIR_RACINE.'squelettes/')
			$tete = array_shift($path_base);
		$dirs = array_reverse(explode(':',$dir_path));
		foreach($dirs as $dir_path){
				#if ($dir_path{0}!='/')
				#	$dir_path = $dir_path;
				if (substr($dir_path,-1) != '/')
					$dir_path .= "/";
				if (!in_array($dir_path,$path_base))
					array_unshift($path_base,$dir_path);
		}
		if (strlen($tete))
			array_unshift($path_base,$tete);
	}
	$path_full = $path_base;
	// Et le(s) dossier(s) des squelettes nommes
	if (strlen($GLOBALS['dossier_squelettes']))
		foreach (array_reverse(explode(':', $GLOBALS['dossier_squelettes'])) as $d)
			array_unshift($path_full, ($d[0] == '/' ? '' : _DIR_RACINE) . $d . '/');

	$GLOBALS['path_sig'] = md5(serialize($path_full));
	return $path_full;
}

// http://doc.spip.org/@creer_chemin
function creer_chemin() {
	$path_a = _chemin();
	static $c = '';

	// on calcule le chemin si le dossier skel a change
	if ($c != $GLOBALS['dossier_squelettes']) {
		// assurer le non plantage lors de la montee de version :
		$c = $GLOBALS['dossier_squelettes'];
		$path_a = _chemin(''); // forcer un recalcul du chemin
	}
	return $path_a;
}



// Cherche une image dans les dossiers images
// definis par _NOM_IMG_PACK et _DIR_IMG_PACK
// http://doc.spip.org/@chemin_image
function chemin_image($file){
	return _DIR_IMG_PACK . $file;
	#return find_in_path ($file, _NOM_IMG_PACK);
}


// Alias de find_in_path
// http://doc.spip.org/@chemin
function chemin($file, $dirname='', $include=false){
	return find_in_path ($file, $dirname, $include);
}


//
// chercher un fichier $file dans le SPIP_PATH
// si on donne un sous-repertoire en 2e arg optionnel, il FAUT le / final
// si 3e arg vrai, on inclut si ce n'est fait.
$GLOBALS['path_sig'] = '';
$GLOBALS['path_files'] = null;

// http://doc.spip.org/@find_in_path
function find_in_path ($file, $dirname='', $include=false) {
	static $dirs=array();
	static $inc = array(); # cf http://trac.rezo.net/trac/spip/changeset/14743
	static $c = '';

	// on calcule le chemin si le dossier skel a change
	if ($c != $GLOBALS['dossier_squelettes']){
		// assurer le non plantage lors de la montee de version :
		$c = $GLOBALS['dossier_squelettes'];
		creer_chemin(); // forcer un recalcul du chemin et la mise a jour de path_sig
	}

	if (isset($GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file])) {
		if (!$GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file])
			return false;
		if ($include AND !isset($inc[$dirname][$file])) {
			include_once _ROOT_CWD . $GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file];
			$inc[$dirname][$file] = $inc[''][$dirname . $file] = true;
		}
		return $GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file];
	}

	$a = strrpos($file,'/');
	if ($a !== false) {
		$dirname .= substr($file, 0, ++$a);
		$file = substr($file, $a);
	}

	foreach(creer_chemin() as $dir) {
		if (!isset($dirs[$a = $dir . $dirname]))
			$dirs[$a] = (is_dir(_ROOT_CWD . $a) || !$a) ;
		if ($dirs[$a]) {
			if (file_exists(_ROOT_CWD . ($a .= $file))) {
				if ($include AND !isset($inc[$dirname][$file])) {
					include_once _ROOT_CWD . $a;
					$inc[$dirname][$file] = $inc[''][$dirname . $file] = true;
				}
				if (!defined('_SAUVER_CHEMIN'))
					define('_SAUVER_CHEMIN',true);
				return $GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file] = $GLOBALS['path_files'][$GLOBALS['path_sig']][''][$dirname . $file] = $a;
			}
		}
	}

	if (!defined('_SAUVER_CHEMIN'))
		define('_SAUVER_CHEMIN',true);
	return $GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file] = $GLOBALS['path_files'][$GLOBALS['path_sig']][''][$dirname . $file] = false;
}

function clear_path_cache(){
	$GLOBALS['path_files'] = array();
	spip_unlink(_CACHE_CHEMIN);
}
function load_path_cache(){
	// charger le path des plugins
	if (@is_readable(_CACHE_PLUGINS_PATH)){
		include_once(_CACHE_PLUGINS_PATH);
	}
	$GLOBALS['path_files'] = array();
	// si le visiteur est admin,
	// on ne recharge pas le cache pour forcer sa mise a jour
	// le cache de chemin n'est utilise que dans le public
	if (_DIR_RESTREINT
		// la session n'est pas encore chargee a ce moment, on ne peut donc pas s'y fier
		//AND (!isset($GLOBALS['visiteur_session']['statut']) OR $GLOBALS['visiteur_session']['statut']!='0minirezo')
		// utiliser le cookie est un pis aller qui marche 'en general'
		// on blinde par un second test au moment de la lecture de la session
		AND !isset($_COOKIE[$GLOBALS['cookie_prefix'].'_admin'])
		// et en ignorant ce cache en cas de recalcul explicite
		AND _request('var_mode')!=='recalcul'
		){
		// on essaye de lire directement sans verrou pour aller plus vite
		if ($contenu = spip_file_get_contents(_CACHE_CHEMIN)){
			// mais si semble corrompu on relit avec un verrou
			if (!$GLOBALS['path_files']=unserialize($contenu)){
				lire_fichier(_CACHE_CHEMIN,$contenu);
				if (!$GLOBALS['path_files']=unserialize($contenu))
					$GLOBALS['path_files'] = array();
			}
		}
	}
	// pas de sauvegarde du chemin si on est pas dans le public
	if (!_DIR_RESTREINT)
		define('_SAUVER_CHEMIN',false);
}

function save_path_cache(){
	if (defined('_SAUVER_CHEMIN')
		AND _SAUVER_CHEMIN)
		ecrire_fichier(_CACHE_CHEMIN,serialize($GLOBALS['path_files']));
}


/**
 * Trouve tous les fichiers du path correspondants a un pattern
 * pour un nom de fichier donne, ne retourne que le premier qui sera trouve
 * par un find_in_path
 *
 * @param string $dir
 * @param string $pattern
 * @param bool $recurs
 * @return array
 */
// http://doc.spip.org/@find_all_in_path
function find_all_in_path($dir,$pattern, $recurs=false){
	$liste_fichiers=array();
	$maxfiles = 10000;

	// Parcourir le chemin
	foreach (creer_chemin() as $d) {
		$f = $d.$dir;
		if (@is_dir($f)){
			$liste = preg_files($f,$pattern,$maxfiles-count($liste_fichiers),$recurs);
			foreach($liste as $chemin){
				$nom = basename($chemin);
				// ne prendre que les fichiers pas deja trouves
				// car find_in_path prend le premier qu'il trouve,
				// les autres sont donc masques
				if (!isset($liste_fichiers[$nom]))
					$liste_fichiers[$nom] = $chemin;
			}
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

// Fonction codant et decodant les URLS des objets SQL mis en page par SPIP
// $id = numero de la cle primaire si nombre, URL a decoder si pas numerique
// $entite = surnom de la table SQL (donne acces au nom de cle primaire)
// $args = query_string a placer apres cle=$id&....
// $ancre = ancre a mettre a la fin de l'URL a produire
// $public = produire l'URL publique ou privee (par defaut: selon espace)
// $type = fichier dans le repertoire ecrire/urls determinant l'apparence
// @return string : url codee
// @return string : fonction de decodage
// http://doc.spip.org/@generer_url_entite
function generer_url_entite($id='', $entite='', $args='', $ancre='', $public=NULL, $type=NULL)
{
	if ($public === NULL) $public = !test_espace_prive();

	if (!$public) {
		if (!$entite) return '';
		include_spip('inc/urls');
		$f = 'generer_url_ecrire_' . $entite;
	        $res = !function_exists($f) ? '' : $f($id, $args, $ancre, ' ');
	} else {
		if (is_string($public)) {
			include_spip('base/connect_sql');
			$id_type = id_table_objet($entite,$public);
			return _DIR_RACINE . get_spip_script('./')
			  . "?"._SPIP_PAGE."=$entite&$id_type=$id&connect=$public"
			  . (!$args ? '' : "&$args")
			  . (!$ancre ? '' : "#$ancre");
		} else {
			if ($type === NULL) {
				$type = ($GLOBALS['type_urls'] === 'page'
					AND $GLOBALS['meta']['type_urls'])
				?  $GLOBALS['meta']['type_urls']
				:  $GLOBALS['type_urls']; // pour SPIP <2
			}

			$f = charger_fonction($type, 'urls', true);
		// si $entite='', on veut la fonction de passage URL ==> id
			if (!$entite) return $f;
		// sinon on veut effectuer le passage id ==> URL
			$res = !$f ? '' : $f(intval($id), $entite, $args, $ancre);
		}
	}
	if ($res) return $res;
	// Sinon c'est un raccourci ou compat SPIP < 2
	include_spip('inc/lien');
	if (!function_exists($f = 'generer_url_' . $entite)) {
		if (!function_exists($f .= '_dist')) $f = '';
	}
	if ($f) {
		$url = $f($id, $args, $ancre);
		if (strlen($args))
			$url .= strstr($url, '?')
				? '&amp;'.$args
				: '?'.$args;
		return $url;
	}
	// On a ete gentil mais la ....
	spip_log("generer_url_entite: entite $entite ($f) inconnue $type $public");
	return '';
}

// Transformer les caracteres utf8 d'une URL (farsi par ex) selon la RFC 1738
function urlencode_1738($url) {
	$uri = '';
	for ($i=0; $i < strlen($url); $i++) {
		if (ord($a = $url[$i]) > 127)
			$a = rawurlencode($a);
		$uri .= $a;
	}
	return quote_amp($uri);
}

// http://doc.spip.org/@generer_url_entite_absolue
function generer_url_entite_absolue($id='', $entite='', $args='', $ancre='', $connect=NULL)
{
	if (!$connect) $connect = true;
	$h = generer_url_entite($id, $entite, $args, $ancre, $connect);
	if (!preg_match(',^\w+:,', $h)) {
		include_spip('inc/filtres_mini');
		$h = url_absolue($h);
	}
	return  $h;
}

// Sur certains serveurs, la valeur 'Off' tient lieu de false dans certaines
// variables d'environnement comme $_SERVER[HTTPS] ou ini_get(register_globals)
// http://doc.spip.org/@test_valeur_serveur
function test_valeur_serveur($truc) {
	if (!$truc) return false;
	return (strtolower($truc) !== 'off');
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
function url_de_base($profondeur=null) {

	static $url = array();
	if (is_array($profondeur)) return $url = $profondeur;
	if ($profondeur===false) return $url;

	if (is_null($profondeur)) $profondeur = $GLOBALS['profondeur_url'];

	if (isset($url[$profondeur]))
		return $url[$profondeur];

	$http = (
		(isset($_SERVER["SCRIPT_URI"]) AND
			substr($_SERVER["SCRIPT_URI"],0,5) == 'https')
		OR (isset($_SERVER['HTTPS']) AND
		    test_valeur_serveur($_SERVER['HTTPS']))
	) ? 'https' : 'http';
	# note : HTTP_HOST contient le :port si necessaire
	if (!$GLOBALS['REQUEST_URI']){
		if (isset($_SERVER['REQUEST_URI'])) {
			$GLOBALS['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
		} else {
			$GLOBALS['REQUEST_URI'] = $_SERVER['PHP_SELF'];
			if ($_SERVER['QUERY_STRING']
			AND !strpos($_SERVER['REQUEST_URI'], '?'))
				$GLOBALS['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
		}
	}

	$url[$profondeur] = url_de_($http,$_SERVER['HTTP_HOST'],$GLOBALS['REQUEST_URI'],$profondeur);

	return $url[$profondeur];
}
/**
 * fonction testable de construction d'une url appelee par url_de_base()
 * @param string $http
 * @param string $host
 * @param string $request
 * @param int $prof
 * @return string
 */
function url_de_($http,$host,$request,$prof=0){
	$prof = max($prof,0);

	$myself = ltrim($request,'/');
	# supprimer la chaine de GET
	list($myself) = explode('?', $myself);
	$url = join('/', array_slice(explode('/', $myself), 0, -1-$prof)).'/';

	$url = $http.'://'.rtrim($host,'/').'/'.ltrim($url,'/');
	return $url;
}


function tester_url_ecrire($nom){
	// tester si c'est une page en squelette
	if (find_in_path('prive/exec/' . $nom . '.' . _EXTENSION_SQUELETTES))
		return 'fond';
	// attention, il ne faut pas inclure l'exec ici car sinon on modifie l'environnement
	// par un simple #URL_ECRIRE dans un squelette (cas d'un define en debut d'exec/nom )
	return (find_in_path("{$nom}.php",'exec/') OR charger_fonction($nom,'exec',true))?$nom:'';
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

	@list($script, $ancre) = explode('#', $script);
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
function generer_url_public($script='', $args="", $no_entities=false, $rel=false, $action='') {
	// si le script est une action (spip_pass, spip_inscription),
	// standardiser vers la nouvelle API

	if (!$action) $action = get_spip_script();
	if ($script)
		$action = parametre_url($action, _SPIP_PAGE, $script, '&');

	if ($args) {
		if (is_array($args)) {
			$r = '';
			foreach($args as $k => $v) $r .= '&' . $k . '=' . $v;
			$args = substr($r,1);
		}
		$action .=
			(strpos($action, '?') !== false ? '&' : '?') . $args;
	}
	if (!$no_entities)
		$action = quote_amp($action);

	// ne pas generer une url avec /./?page= en cas d'url absolue et de _SPIP_SCRIPT vide
	return ($rel ? _DIR_RACINE . $action : rtrim(url_de_base(),'/') . preg_replace(",^/[.]/,","/","/$action"));
}

// http://doc.spip.org/@generer_url_prive
function generer_url_prive($script, $args="", $no_entities=false) {

	return generer_url_public($script, $args, $no_entities, false, _DIR_RESTREINT_ABS .  'prive.php');
}

// Pour les formulaires en methode POST,
// mettre le nom du script a la fois en input-hidden et dans le champ action:
// 1) on peut ainsi memoriser le signet comme si c'etait un GET
// 2) ca suit http://en.wikipedia.org/wiki/Representational_State_Transfer

// http://doc.spip.org/@generer_form_ecrire
function generer_form_ecrire($script, $corps, $atts='', $submit='') {
	global $spip_lang_right;

	$script1 = array_shift(explode('&', $script));

	return "<form action='"
	. ($script ? generer_url_ecrire($script) : '')
	. "' "
	. ($atts ? $atts : " method='post'")
	.  "><div>\n"
	. "<input type='hidden' name='exec' value='$script1' />"
	. $corps
	. (!$submit ? '' :
	     ("<div style='text-align: $spip_lang_right'><input type='submit' value='$submit' /></div>"))
	. "</div></form>\n";
}

// Attention, JS/Ajax n'aime pas le melange de param GET/POST
// On n'applique pas la recommandation ci-dessus pour les scripts publics
// qui ne sont pas destines a etre mis en signets

// http://doc.spip.org/@generer_form_action
function generer_form_action($script, $corps, $atts='', $public=false) {
	// si l'on est dans l'espace prive, on garde dans l'url
	// l'exec a l'origine de l'action, qui permet de savoir si il est necessaire
	// ou non de proceder a l'authentification (cas typique de l'install par exemple)
	$h = (_DIR_RACINE AND !$public)
	? generer_url_ecrire(_request('exec'))
	: generer_url_public();

	return "\n<form action='" .
	  $h .
	  "'" .
	  $atts .
	  ">\n" .
	  "<div>" .
  	  "\n<input type='hidden' name='action' value='$script' />" .
	  $corps .
	  "</div></form>";
}

// http://doc.spip.org/@generer_url_action
function generer_url_action($script, $args="", $no_entities=false , $public = false) {
	// si l'on est dans l'espace prive, on garde dans l'url
	// l'exec a l'origine de l'action, qui permet de savoir si il est necessaire
	// ou non de proceder a l'authentification (cas typique de l'install par exemple)
	$url = (_DIR_RACINE  AND !$public)
	  ? generer_url_ecrire(_request('exec'))
	  :  generer_url_public();
	$url = parametre_url($url,'action',$script);
	if ($args) $url .= quote_amp('&'.$args);

	if ($no_entities) $url = str_replace('&amp;','&',$url);
	return $url;
}


/**
 * Fonction d'initialisation groupee pour compatibilite ascendante
 *
 * @param string $pi
 * @param string $pa
 * @param string $ti
 * @param string $ta
 */
function spip_initialisation($pi=NULL, $pa=NULL, $ti=NULL, $ta=NULL) {
	spip_initialisation_core($pi,$pa,$ti,$ta);
	spip_initialisation_suite();
}

/**
 * Fonction d'initialisation, appellee dans inc_version ou mes_options
 * Elle definit les repertoires et fichiers non partageables
 * et indique dans $test_dirs ceux devant etre accessibles en ecriture
 * mais ne touche pas a cette variable si elle est deja definie
 * afin que mes_options.php puisse en specifier d'autres.
 * Elle definit ensuite les noms des fichiers et les droits.
 * Puis simule un register_global=on securise.
 *
 * @param string $pi
 * @param string $pa
 * @param string $ti
 * @param string $ta
 */
function spip_initialisation_core($pi=NULL, $pa=NULL, $ti=NULL, $ta=NULL) {
	static $too_late = 0;
	if ($too_late++) return;

	// Declaration des repertoires

	// le nom du repertoire plugins/ activables/desactivables
	define('_DIR_PLUGINS', _DIR_RACINE . "plugins/");

	// le nom du repertoire des extensions/ permanentes du core, toujours actives
	define('_DIR_EXTENSIONS', _DIR_RACINE . "extensions/");

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

	define('_DIR_VAR', $ta);

	define('_DIR_ETC', $pi);
	define('_DIR_CONNECT', $pi);
	define('_DIR_CHMOD', $pi);

	if (!isset($GLOBALS['test_dirs']))
	  // Pas $pi car il est bon de le mettre hors ecriture apres intstall
	  // il sera rajoute automatiquement si besoin a l'etape 2 de l'install
		$GLOBALS['test_dirs'] =  array($pa, $ti, $ta);

	// Declaration des fichiers

	define('_CACHE_PLUGINS_PATH', _DIR_CACHE . "charger_plugins_chemins.php");
	define('_CACHE_PLUGINS_OPT', _DIR_CACHE . "charger_plugins_options.php");
	define('_CACHE_PLUGINS_FCT', _DIR_CACHE . "charger_plugins_fonctions.php");
	define('_CACHE_PLUGINS_VERIF', _DIR_CACHE . "verifier_plugins.txt");
	define('_CACHE_PIPELINES',  _DIR_CACHE."charger_pipelines.php");
	define('_CACHE_CHEMIN',  _DIR_CACHE."chemin.txt");

	# attention .php obligatoire pour ecrire_fichier_securise
	define('_FILE_META', $ti . 'meta_cache.php');
	define('_DIR_LOG', _DIR_TMP);
	define('_FILE_LOG', 'spip');
	define('_FILE_LOG_SUFFIX', '.log');

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
	if (!defined('_SPIP_CHMOD') AND _FILE_CHMOD)
		include_once _FILE_CHMOD;

	// Se mefier des fichiers mal remplis!
	if (!defined('_SPIP_CHMOD')) define('_SPIP_CHMOD', 0777);

	// Le charset par defaut lors de l'installation
	define('_DEFAULT_CHARSET', 'utf-8');

	define('_ROOT_PLUGINS', _ROOT_RACINE . "plugins/");
	define('_ROOT_EXTENSIONS', _ROOT_RACINE . "extensions/");

	// La taille des Log
	define('_MAX_LOG', 100);

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

	//
	// Module de lecture/ecriture/suppression de fichiers utilisant flock()
	// (non surchargeable en l'etat ; attention si on utilise include_spip()
	// pour le rendre surchargeable, on va provoquer un reecriture
	// systematique du noyau ou une baisse de perfs => a etudier)
	include_once  _ROOT_RESTREINT . 'inc/flock.php';

	// charger tout de suite le path et son cache
	load_path_cache();

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

	// Par ailleurs on ne veut pas de magic_quotes au cours de l'execution
	@set_magic_quotes_runtime(0);

	// Si les variables sont passees en global par le serveur,
	// ou si on veut la compatibilite php3
	// il faut faire quelques verifications de base
	if ($x = test_valeur_serveur(@ini_get('register_globals'))
	OR  _FEED_GLOBALS) {
		// ne pas desinfecter les globales en profondeur car elle contient aussi les
		// precedentes, qui seraient desinfectees 2 fois.
		spip_desinfecte($GLOBALS,false);
		include_spip('inc/php3');
		spip_register_globals($x);
	}

	// appliquer le cookie_prefix
	if ($GLOBALS['cookie_prefix'] != 'spip') {
		include_spip('inc/cookie');
		recuperer_cookies_spip($GLOBALS['cookie_prefix']);
	}

	//
	// Capacites php (en fonction de la version)
	//
	$GLOBALS['flag_ob'] = (function_exists("ob_start")
		&& function_exists("ini_get")
		&& !strstr(@ini_get('disable_functions'), 'ob_'));
	$GLOBALS['flag_sapi_name'] = function_exists("php_sapi_name");
	$GLOBALS['flag_get_cfg_var'] = (@get_cfg_var('error_reporting') != "");
	$GLOBALS['flag_upload'] = (!$GLOBALS['flag_get_cfg_var'] ||
		(get_cfg_var('upload_max_filesize') > 0));


	// Compatibilite avec serveurs ne fournissant pas $REQUEST_URI
	if (isset($_SERVER['REQUEST_URI'])) {
		$GLOBALS['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
	} else {
		$GLOBALS['REQUEST_URI'] = $_SERVER['PHP_SELF'];
		if ($_SERVER['QUERY_STRING']
		AND !strpos($_SERVER['REQUEST_URI'], '?'))
			$GLOBALS['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
	}

	// Duree de validite de l'alea pour les cookies et ce qui s'ensuit.
	define('_RENOUVELLE_ALEA', 12 * 3600);

	// charger les meta si possible et renouveller l'alea au besoin
	// charge aussi effacer_meta et ecrire_meta
	$inc_meta = charger_fonction('meta', 'inc');
	$inc_meta();

	// nombre de repertoires depuis la racine
	// on compare a l'adresse de spip.php : $_SERVER["SCRIPT_NAME"]
	// ou a defaut celle donnee en meta ; (mais si celle-ci est fausse
	// le calcul est faux)
	if (!_DIR_RESTREINT)
		$GLOBALS['profondeur_url'] = 1;
	else {
		$uri = isset($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI']) : '';
		$uri_ref = $_SERVER["SCRIPT_NAME"];
		if (!$uri_ref
			// si on est appele avec un autre ti, on est sans doute en mutu
			// si jamais c'est de la mutu avec sous rep, on est perdu si on se fie
			// a spip.php qui est a la racine du spip, et vue qu'on sait pas se reperer
			// s'en remettre a l'adresse du site. alea jacta est.
			OR $ti!==_NOM_TEMPORAIRES_INACCESSIBLES){

			if (isset($GLOBALS['meta']['adresse_site'])) {
				$uri_ref = parse_url($GLOBALS['meta']['adresse_site']);
				$uri_ref = $uri_ref['path'].'/';
			}
		  else
			  $uri_ref = "";
		}
		if (!$uri OR !$uri_ref)
			$GLOBALS['profondeur_url'] = 0;
		else {
			$GLOBALS['profondeur_url'] = max(0,
				substr_count($uri[0], '/')
				- substr_count($uri_ref,'/'));
		}
	}
	// s'il y a un cookie ou PHP_AUTH, initialiser visiteur_session
	if (_FILE_CONNECT) {
		if (verifier_visiteur()=='0minirezo'
			// si c'est un admin sans cookie admin, il faut ignorer le cache chemin !
		  AND !isset($COOKIE['spip_admin']))
			clear_path_cache();
	}

}

/**
 * Complements d'initialisation non critiques pouvant etre realises
 * par les plugins
 *
 */
function spip_initialisation_suite() {
	static $too_late = 0;
	if ($too_late++) return;

	// taille mini des login
	define('_LOGIN_TROP_COURT', 4);

	// la taille maxi des logos (0 : pas de limite)
	define('_LOGO_MAX_SIZE', 0); # poids en ko
	define('_LOGO_MAX_WIDTH', 0); # largeur en pixels
	define('_LOGO_MAX_HEIGHT', 0); # hauteur en pixels

	define('_DOC_MAX_SIZE', 0); # poids en ko

	define('_IMG_MAX_SIZE', 0); # poids en ko
	define('_IMG_MAX_WIDTH', 0); # largeur en pixels
	define('_IMG_MAX_HEIGHT', 0); # hauteur en pixels

	define('_COPIE_LOCALE_MAX_SIZE',16777216); // poids en octet

	// qq chaines standard
	define('_ACCESS_FILE_NAME', '.htaccess');
	define('_AUTH_USER_FILE', '.htpasswd');
	define('_SPIP_DUMP', 'dump@nom_site@@stamp@.xml');
	define('_CACHE_RUBRIQUES', _DIR_TMP.'menu-rubriques-cache.txt');
	define('_CACHE_RUBRIQUES_MAX', 500);

	define('_EXTENSION_SQUELETTES', 'html');

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
	// argument page, personalisable en cas de conflit avec un autre script
	define('_SPIP_PAGE', 'page');

	// le script de l'espace prive
	// Mettre a "index.php" si DirectoryIndex ne le fait pas ou pb connexes:
	// les anciens IIS n'acceptent pas les POST sur ecrire/ (#419)
	// meme pb sur thttpd cf. http://forum.spip.org/fr_184153.html

	define('_SPIP_ECRIRE_SCRIPT', // true ? #decommenter ici et commenter la
	       preg_match(',IIS|thttpd,',$_SERVER['SERVER_SOFTWARE']) ?
	       'index.php' : '');


	// Gestion AJAX sauf pour le mode oo (et en espace prive)

	if (isset($GLOBALS['visiteur_session']['prefs'])AND !_DIR_RESTREINT) {
		$x = $GLOBALS['visiteur_session']['prefs'];
		if (!is_array($x)) $x = unserialize($x); // prive.php l'a fait
		if ($x['display'] == 4) {
			define('_SPIP_AJAX', -1);
			if (isset($_COOKIE['spip_accepte_ajax']))
				spip_setcookie('spip_accepte_ajax', -1, 0);
		}
	}

	if (!defined('_SPIP_AJAX'))
		define('_SPIP_AJAX', ((!isset($_COOKIE['spip_accepte_ajax']))
			? 1
		       : (($_COOKIE['spip_accepte_ajax'] != -1) ? 1 : 0)));

	// La requete est-elle en ajax ?
	define('_AJAX',
		(isset($_SERVER['HTTP_X_REQUESTED_WITH']) # ajax jQuery
		OR @$_REQUEST['var_ajax_redir'] # redirection 302 apres ajax jQuery
		OR @$_REQUEST['var_ajaxcharset'] # compat ascendante pour plugins
		)
		AND !@$_REQUEST['var_noajax'] # horrible exception, car c'est pas parce que la requete est ajax jquery qu'il faut tuer tous les formulaires ajax qu'elle contient
	);

	# nombre de pixels maxi pour calcul de la vignette avec gd
	# au dela de 5500000 on considere que php n'est pas limite en memoire pour cette operation
	# les configurations limitees en memoire ont un seuil plutot vers 1MPixel
	define('_IMG_GD_MAX_PIXELS', (isset($GLOBALS['meta']['max_taille_vignettes'])&&$GLOBALS['meta']['max_taille_vignettes']<5500000)?$GLOBALS['meta']['max_taille_vignettes']:0);
	define('_IMG_GD_QUALITE', 85);

	if (!defined('_MEMORY_LIMIT_MIN')) define('_MEMORY_LIMIT_MIN', 16);
	// si on est dans l'espace prive et si le besoin est superieur a 8Mo (qui est vraiment le standard)
	// on verifie que la memoire est suffisante pour le compactage css+js pour eviter la page blanche
	// il y aura d'autres problemes et l'utilisateur n'ira pas tres loin, mais ce sera plus comprehensible qu'une page blanche
	if (test_espace_prive() AND _MEMORY_LIMIT_MIN>8){
		if ($memory = trim(ini_get('memory_limit'))){
			$unit = strtolower(substr($memory,strlen($memory/1),1));
			switch($unit) {
				// Le modifieur 'G' est disponible depuis PHP 5.1.0
				case 'g': $memory *= 1024;
				case 'm': $memory *= 1024;
				case 'k': $memory *= 1024;
			}
			if ($memory<_MEMORY_LIMIT_MIN*1024*1024){
				ini_set('memory_limit',$m=_MEMORY_LIMIT_MIN.'M');
				if (trim(ini_get('memory_limit'))!=$m){
					define('_INTERDIRE_COMPACTE_HEAD_ECRIRE',true); // evite une page blanche car on ne saura pas calculer la css dans ce hit
				}
			}
		}
		else
			define('_INTERDIRE_COMPACTE_HEAD_ECRIRE',true); // evite une page blanche car on ne saura pas calculer la css dans ce hit
	}

	init_var_mode();
}

// Reperer les variables d'URL qui conditionnent la perennite du cache, des urls
// ou d'autres petit caches (trouver_table, css et js compactes ...)
// http://doc.spip.org/@init_var_mode
function init_var_mode(){
	static $done = false;
	if (!$done) {
		// On fixe $GLOBALS['var_mode']
		$GLOBALS['var_mode'] = false;
		$GLOBALS['var_preview'] = false;
		$GLOBALS['var_images'] = false;
		$GLOBALS['var_inclure'] = false;
		$GLOBALS['var_urls'] = false;
		if (isset($_GET['var_mode'])) {
			// tout le monde peut calcul/recalcul
			if ($_GET['var_mode'] == 'calcul'
			OR $_GET['var_mode'] == 'recalcul')
				$GLOBALS['var_mode'] = $_GET['var_mode'];

			// preview, debug, blocs, urls et images necessitent une autorisation
			else if (in_array($_GET['var_mode'],array('preview','debug','inclure','urls','images'))) {
				include_spip('inc/autoriser');
				if (autoriser(
					($_GET['var_mode'] == 'preview')
						? 'previsualiser'
						: 'debug'
				)) {
					switch($_GET['var_mode']){
						case 'preview':
							// forcer le compilo et ignorer les caches existants
							$GLOBALS['var_mode'] = 'recalcul';
							// truquer les boucles
							$GLOBALS['var_preview'] = true;
							// et ne pas enregistrer de cache
							$GLOBALS['var_nocache'] = true;
							break;
						case 'inclure':
							// forcer le compilo et ignorer les caches existants
							$GLOBALS['var_mode'] = 'calcul';
							$GLOBALS['var_inclure'] = true;
							// et ne pas enregistrer de cache
							$GLOBALS['var_nocache'] = true;
							break;
						case 'urls':
							// forcer le compilo et ignorer les caches existants
							$GLOBALS['var_mode'] = 'calcul';
							$GLOBALS['var_urls'] = true;
							break;
						case 'images':
							// forcer le compilo et ignorer les caches existants
							$GLOBALS['var_mode'] = 'calcul';
							// indiquer qu'on doit recalculer les images
							$GLOBALS['var_images'] = true;
							break;
						case 'debug':
							$GLOBALS['var_mode'] = 'debug';
							// et ne pas enregistrer de cache
							$GLOBALS['var_nocache'] = true;
							break;
						default :
							$GLOBALS['var_mode'] = $_GET['var_mode'];
							break;
					}
					spip_log($GLOBALS['visiteur_session']['nom']
						. " ".$GLOBALS['var_mode']);
				}
				// pas autorise ?
				else {
					// si on n'est pas connecte on se redirige
					if (!$GLOBALS['visiteur_session']) {
						include_spip('inc/headers');
						redirige_par_entete(generer_url_public('login',
						'url='.rawurlencode(
						parametre_url(self(), 'var_mode', $_GET['var_mode'], '&')
						), true));
					}
					// sinon tant pis
				}
			}
		}
		$done = true;
	}
}

// Annuler les magic quotes \' sur GET POST COOKIE et GLOBALS ;
// supprimer aussi les eventuels caracteres nuls %00, qui peuvent tromper
// la commande is_readable('chemin/vers/fichier/interdit%00truc_normal')
// http://doc.spip.org/@spip_desinfecte
function spip_desinfecte(&$t,$deep = true) {
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
		else if ($deep AND is_array($t[$key]) AND $key!=='GLOBALS')
			spip_desinfecte($t[$key],$deep);
	}
}

//  retourne le statut du visiteur s'il s'annonce


// http://doc.spip.org/@verifier_visiteur
function verifier_visiteur() {

	// Demarrer une session NON AUTHENTIFIEE si on donne son nom
	// dans un formulaire sans login (ex: #FORMULAIRE_FORUM)
	// Attention on separe bien session_nom et nom, pour eviter
	// les melanges entre donnees SQL et variables plus aleatoires
	$variables_session = array('session_nom', 'session_email');
	foreach($variables_session as $var) {
		if (_request($var) !== null) {
			$init = true;
			break;
		}
	}
	if (isset($init)) {
		@spip_initialisation_core(
			(_DIR_RACINE  . _NOM_PERMANENTS_INACCESSIBLES),
			(_DIR_RACINE  . _NOM_PERMANENTS_ACCESSIBLES),
			(_DIR_RACINE  . _NOM_TEMPORAIRES_INACCESSIBLES),
			(_DIR_RACINE  . _NOM_TEMPORAIRES_ACCESSIBLES)
		);
		#@spip_initialisation_suite();
		$session = charger_fonction('session', 'inc');
		$session();
		include_spip('inc/texte');
		foreach($variables_session as $var)
			if (($a = _request($var)) !== null)
				$GLOBALS['visiteur_session'][$var] = safehtml($a);
		if (!isset($GLOBALS['visiteur_session']['id_auteur']))
			$GLOBALS['visiteur_session']['id_auteur'] = 0;
		$session($GLOBALS['visiteur_session']);
		return 0;
	}

	$h = (isset($_SERVER['PHP_AUTH_USER'])  AND !$GLOBALS['ignore_auth_http']);
	if ($h OR isset($_COOKIE['spip_session']) OR isset($_COOKIE[$GLOBALS['cookie_prefix'].'_session'])) {

		// Rq: pour que cette fonction marche depuis mes_options
		// il faut forcer l'init si ce n'est fait
		// mais on risque de perturber des plugins en initialisant trop tot
		// certaines constantes
		@spip_initialisation_core(
			(_DIR_RACINE  . _NOM_PERMANENTS_INACCESSIBLES),
			(_DIR_RACINE  . _NOM_PERMANENTS_ACCESSIBLES),
			(_DIR_RACINE  . _NOM_TEMPORAIRES_INACCESSIBLES),
			(_DIR_RACINE  . _NOM_TEMPORAIRES_ACCESSIBLES)
		);
		#@spip_initialisation_suite();

		$session = charger_fonction('session', 'inc');
		if ($session()) {
			return $GLOBALS['visiteur_session']['statut'];
		}
		if ($h  AND isset($_SERVER['PHP_AUTH_PW'])) {
			include_spip('inc/auth');
			$h = lire_php_auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		}
		if ($h) {
			$GLOBALS['visiteur_session'] = $h;
			return $GLOBALS['visiteur_session']['statut'];
		}
	}
	// au moins son navigateur nous dit la langue preferee de cet inconnu
	include_spip('inc/lang');
	utiliser_langue_visiteur();
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

function erreur_squelette($message='', $lieu='') {
	$debusquer = charger_fonction('debusquer', 'public');
	if (is_array($lieu)) {
		include_spip('public/compiler');
		$lieu = reconstruire_contexte_compil($lieu);
	}
	return $debusquer($message, $lieu);
}

/**
 * La fonction de base de SPIP : un squelette + un contexte => une page.
 * $fond peut etre un nom de squelette, ou une liste de squelette au format array.
 * Dans ce dernier cas, les squelettes sont tous evalues et mis bout a bout
 * $options permet de selectionner les options suivantes :
 * 	trim => true (valeur par defaut) permet de ne rien renvoyer si le fond ne produit que des espaces ;
 * 	raw => true permet de recuperer la strucure $page complete avec entetes et invalideurs pour chaque $fond fourni.
 *
 * @param string/array $fond
 * @param array $contexte
 * @param array $options
 * @param string $connect
 * @return string/array
 */
// http://doc.spip.org/@recuperer_fond
function recuperer_fond($fond, $contexte=array(), $options = array(), $connect='') {
	include_spip('public/assembler');
	// assurer la compat avec l'ancienne syntaxe
	// (trim etait le 3eme argument, par defaut a true)
	if (!is_array($options)) $options = array('trim'=>$options);
	if (!isset($options['trim'])) $options['trim']=true;

	if (isset($contexte['connect'])){
		$connect = ($connect ? $connect : $contexte['connect']);
		unset($contexte['connect']);
	}

	if (isset($options['modele']))
		$contexte = creer_contexte_de_modele($contexte);

	$texte = "";
	$pages = array();
	if (isset($contexte['fond'])
	 // securite anti injection pour permettre aux plugins de faire
	 // des interfaces avec simplement recuperer_fond($fond,$_GET);
	 AND $contexte['fond']!==_request('fond'))
		$fond = $contexte['fond'];

	$lang_select = '';
	if (!isset($options['etoile']) OR !$options['etoile']){
		// Si on a inclus sans fixer le critere de lang, on prend la langue courante
		if (!isset($contexte['lang']))
			$contexte['lang'] = $GLOBALS['spip_lang'];

		if ($contexte['lang'] != $GLOBALS['meta']['langue_site']) {
			$lang_select = lang_select($contexte['lang']);
		}
	}

	@$GLOBALS['_INC_PUBLIC']++;

	foreach(is_array($fond) ? $fond : array($fond) as $f){
		$page = evaluer_fond($f, $contexte, $connect);
		if ($page === '') {
			$c = isset($options['compil']) ? $options['compil'] :'';
			$a = array('fichier'=>$fond.'.'._EXTENSION_SQUELETTES);
			erreur_squelette(_T('info_erreur_squelette2', $a), $c);
		}
					 
		if (isset($options['ajax'])AND $options['ajax']){
			include_spip('inc/filtres');
			$page['texte'] = encoder_contexte_ajax(array_merge($contexte,array('fond'=>$f)),'',$page['texte']);
		}

		$page = pipeline('recuperer_fond',array(
			'args'=>array('fond'=>$fond,'contexte'=>$contexte,'options'=>$options,'connect'=>$connect),
			'data'=>$page
		));
		if (isset($options['raw']) AND $options['raw'])
			$pages[] = $page;
		else
			$texte .= $options['trim'] ? rtrim($page['texte']) : $page['texte'];
	}

	$GLOBALS['_INC_PUBLIC']--;

	if ($lang_select) lang_select();
	if (isset($options['raw']) AND $options['raw'])
		return is_array($fond)?$pages:reset($pages);
	else
		return $options['trim'] ? ltrim($texte) : $texte;
}

function trouve_modele($nom)
{
	return find_in_path( 'modeles/' . $nom.'.'. _EXTENSION_SQUELETTES);
}

// Charger dynamiquement une extension php
// http://doc.spip.org/@charger_php_extension
function charger_php_extension($module) {
	if (extension_loaded($module)) {
		return true;
	} else {
		$charger_php_extension = charger_fonction('charger_php_extension','inc');
		return $charger_php_extension($module);
	}
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
		if ($t=='SPIP_NUM') $t = MYSQL_NUM;
		if ($t=='SPIP_BOTH') $t = MYSQL_BOTH;
		if ($t=='SPIP_ASSOC') $t = MYSQL_ASSOC;
		spip_log("appel deprecie de spip_fetch_array(..., $t)", 'vieilles_defs');
		if ($r) return mysql_fetch_array($r, $t);
	}
}

?>
