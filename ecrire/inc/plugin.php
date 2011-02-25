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

// Regexp permettant de reperer les fichiers plugin.xml et paquet.xml
define('_FILE_PLUGIN_CONFIG', '/(plugin|paquet)[.]xml$');
// l'adresse du repertoire de telechargement et de decompactage des plugins
define('_DIR_PLUGINS_AUTO', _DIR_PLUGINS.'auto/');

include_spip('inc/texte'); // ?????
include_spip('plugins/installer');

// lecture des sous repertoire plugin existants
// $dir_plugins pour forcer un repertoire (ex: _DIR_EXTENSIONS)
// _DIR_PLUGINS_SUPPL pour aller en chercher ailleurs (separes par des ":")
// http://doc.spip.org/@liste_plugin_files
function liste_plugin_files($dir_plugins = null){
	static $plugin_files=array();

	if (is_null($dir_plugins)) {
		$dir_plugins = _DIR_PLUGINS;
		if (defined('_DIR_PLUGINS_SUPPL'))
			$dir_plugins_suppl = array_filter(explode(':',_DIR_PLUGINS_SUPPL));
	}

	if (!isset($plugin_files[$dir_plugins])
	OR count($plugin_files[$dir_plugins]) == 0){
		$plugin_files[$dir_plugins] = array();
		foreach (preg_files($dir_plugins, _FILE_PLUGIN_CONFIG) as $plugin) {
			$plugin_files[$dir_plugins][] = substr(dirname($plugin),strlen($dir_plugins));
		}
		sort($plugin_files[$dir_plugins]);

		// hack affreux pour avoir le bon chemin pour les repertoires
		// supplementaires ; chemin calcule par rapport a _DIR_PLUGINS.
		if (isset($dir_plugins_suppl)) {
			foreach($dir_plugins_suppl as $suppl) {
				foreach (preg_files($suppl, _FILE_PLUGIN_CONFIG) as $plugin) {
					$plugin_files[$dir_plugins][] = (_DIR_RACINE ?'': '../') .dirname($plugin);
				}
			}
		}
	}

	return $plugin_files[$dir_plugins];
}

// http://doc.spip.org/@plugin_version_compatible
function plugin_version_compatible($intervalle,$version){
	if (!strlen($intervalle)) return true;
	if (!preg_match(',^[\[\(]([0-9.a-zRC\s\-]*)[;]([0-9.a-zRC\s\-]*)[\]\)]$,',$intervalle,$regs)) return false;
	#var_dump("$version::$intervalle");
	$minimum = $regs[1];
	$maximum = $regs[2];
	$minimum_inc = $intervalle{0}=="[";
	$maximum_inc = substr($intervalle,-1)=="]";
	#var_dump("$version::$minimum_inc::$minimum::$maximum::$maximum_inc");
	#var_dump(spip_version_compare($version,$minimum,'<'));
	if (strlen($minimum)){
		if ($minimum_inc AND spip_version_compare($version,$minimum,'<')) return false;
		if (!$minimum_inc AND spip_version_compare($version,$minimum,'<=')) return false;
	}
	if (strlen($maximum)){
		if ($maximum_inc AND spip_version_compare($version,$maximum,'>')) return false;
		if (!$maximum_inc AND spip_version_compare($version,$maximum,'>=')) return false;
	}
	return true;
}



/**
 * Faire la liste des librairies disponibles
 * retourne un array ( nom de la lib => repertoire , ... )
 *
 * @return array
 */
// http://doc.spip.org/@liste_librairies
function plugins_liste_librairies() {
	$libs = array();
	foreach (array_reverse(creer_chemin()) as $d) {
		if (is_dir($dir = $d.'lib/')
		AND $t = @opendir($dir)) {
			while (($f = readdir($t)) !== false) {
				if ($f[0] != '.'
				AND is_dir("$dir/$f"))
					$libs[$f] = $dir;
			}
		}
	}
	return $libs;
}

// Cette fonction cree les fichiers a charger pour faire fonctionner les plug
// http://doc.spip.org/@liste_plugin_valides
function liste_plugin_valides($liste_plug, $force = false)
{
	$liste_ext = liste_plugin_files(_DIR_EXTENSIONS);
	$get_infos = charger_fonction('get_infos','plugins');
	$infos = array(
		// lister les extensions qui sont automatiquement actives
		'_DIR_EXTENSIONS' => $get_infos($liste_ext, $force, _DIR_EXTENSIONS),
		'_DIR_PLUGINS' => $get_infos($liste_plug, $force, _DIR_PLUGINS)
		       );

	// creer une premiere liste non ordonnee
	$liste_non_classee = array();
	foreach($liste_ext as $plug){
	  if (isset($infos['_DIR_EXTENSIONS'][$plug]))
	    plugin_ecrire_resume($liste_non_classee, $plug, $infos, '_DIR_EXTENSIONS');
	}
	foreach($liste_plug as $plug) {
	  if (isset($infos['_DIR_PLUGINS'][$plug]))
	    plugin_ecrire_resume($liste_non_classee, $plug, $infos, '_DIR_PLUGINS');
	}
	// Signaler les plugins necessitant une lib absente
	$err = $msg = array();
	foreach($liste_non_classee as $p => $resume) {
		foreach($infos[$resume['dir_type']][$resume['dir']]['lib'] as $l) {
		  if (!find_in_path($l['nom'], 'lib/')) {
				$err[$p] = $resume;
				$msg[$p][] = $l;
				unset($liste_non_classee[$p]);
			}
		}
	}
	if ($err) plugins_erreurs($err, '', $infos, $msg);
	return plugin_trier($infos, $liste_non_classee);
}

// Ne retenir un plugin que s'il est valide
// et dans leur plus recente version compatible
// avec la version presente de SPIP

function plugin_ecrire_resume(&$liste, $plug, $infos, $dir)
{
	$i = $infos[$dir][$plug];
	if (!plugin_version_compatible($i['compatible'], $GLOBALS['spip_version_branche']))
		return;
	$p = strtoupper($i['prefix']);
	if (!isset($liste[$p]) 
	OR spip_version_compare($i['version'],$liste[$p]['version'],'>')) {
		$liste[$p] = array(
			'nom' => $i['nom'],
			'etat' => $i['etat'],
			'version'=> $i['version'],
			'dir'=> $plug,
			'dir_type' => $dir
					       );
		}
}

function plugin_trier($infos, $liste_non_classee)
{
	// pour tester utilise, il faut connaitre tous les plugins 
	// qui seront forcement pas la a la fin,
	// car absent de la liste des plugins actifs.
	// Il faut donc construire une liste ordonnee des plugins
	$toute_la_liste = $liste_non_classee;
	$liste = $ordre = array();
	$count = 0;
	while ($c=count($liste_non_classee) AND $c!=$count){ // tant qu'il reste des plugins a classer, et qu'on ne stagne pas
	  #echo "tour::";var_dump($liste_non_classee);
		$count = $c;
		foreach($liste_non_classee as $p=>$resume) {
			$plug = $resume['dir'];
			$dir_type = $resume['dir_type'];
			$info1 = $infos[$dir_type][$plug];
			// si des plugins sont necessaires,
			// on ne peut inserer qu'apres eux
			foreach($info1['necessite'] as $need){
			  $nom = strtoupper($need['nom']);
			  if (!isset($liste[$nom]) OR !plugin_version_compatible($version,$liste[$nom]['version'])) {
			      $info1 = false;
			      break;
			  }
			}		    
			if (!$info1) continue;
			// idem si des plugins sont utiles,
			// sauf si ils sont de toute facon absents de la liste
			foreach($info1['utilise'] as $need){
			  $nom = strtoupper($need['nom']);
			  if (isset($toute_la_liste[$nom])) {
			    if (!isset($liste[$nom]) OR 
				!plugin_version_compatible($need['version'],$liste[$nom]['version'])) {
			      $info1 = false;
			      break;
			    }
			  }
			}
			if ($info1) {
			  $ordre[$p] = $info1;
			  $liste[$p] = $liste_non_classee[$p];
			  unset($liste_non_classee[$p]);
			}
		}
	}

	if ($liste_non_classee) plugins_erreurs($liste_non_classee, $liste, $infos);
	return array($liste, $ordre);
}
		
// dependance circulaire, ou utilise qu'on peut ignorer ?
// dans le doute on fait une erreur quand meme
// plutot que d'inserer silencieusement et de risquer un bug sournois latent

function plugins_erreurs($liste_non_classee, $liste, $infos, $msg=array())
{
	static $erreurs = "";

	include_spip('inc/lang');
	utiliser_langue_visiteur();
	foreach($liste_non_classee as $p=>$resume){
		$dir_type = $resume['dir_type'];
		$plug = $resume['dir'];
		$k = $infos[$dir_type][$plug];
		$plug = array('plugin' => constant($dir_type) . $plug);
		if (!isset($msg[$p])) {
		  if (!$msg[$p] = plugin_necessite($k['necessite'], $liste))
		    $msg[$p] = plugin_necessite($k['utilise'], $liste);
		} else {
		  foreach($msg[$p] as $c => $l)
		    $msg[$p][$c] = plugin_controler_lib($l['nom'], $l['lien']);
		}
		$erreurs .= "<li>" . _T('plugin_impossible_activer', $plug)
		  . "<ul><li>" . implode("</li><li>", $msg[$p]) . "</li></ul></li>";
	}
	ecrire_meta('plugin_erreur_activation',	"<ul>$erreurs</ul>");
}

function plugin_necessite($n, $liste) {
	$msg = array();
	foreach($n as $need){
		$id = strtoupper($need['nom']);
		if ($r = plugin_controler_necessite($liste, $id, $need['version'])) {
			$msg[] = $r;
		}
	}
	return $msg;
}

function plugin_controler_necessite($liste, $nom, $version)
{
  return (isset($liste[$nom])
	  AND plugin_version_compatible($version,$liste[$nom]['version']))
    ? '' :    _T('plugin_necessite_plugin', array(
				'plugin' => $nom,
				'version' => $version));
}

function plugin_controler_lib($lib, $url)
{
	if ($url) {
		include_spip('inc/charger_plugin');
		$url = '<br />'	. bouton_telechargement_plugin($url, 'lib');
	}
	return _T('plugin_necessite_lib', array('lib'=>$lib)) . $url;
}
/*
function plugin_controler_spip($version)
{
	return plugin_version_compatible($version, $GLOBALS['spip_version_branche'])
	  ? '' : _T('plugin_necessite_spip', array('version' => $version));
}
*/
/**
 * Lister les chemins vers les plugins actifs d'un dossier plugins/
 *
 * @return unknown
 */
// http://doc.spip.org/@liste_chemin_plugin_actifs
function liste_chemin_plugin_actifs($dir_plugins=_DIR_PLUGINS){
	$liste = liste_plugin_actifs();
	foreach ($liste as $prefix=>$infos) {
		// compat au moment d'une migration depuis version anterieure
		// si pas de dir_type, alors c'est _DIR_PLUGINS
		if (!isset($infos['dir_type']))
			$infos['dir_type'] = "_DIR_PLUGINS";
		if (defined($infos['dir_type']) 
		AND constant($infos['dir_type'])==$dir_plugins)
			$liste[$prefix] = $infos['dir'];
		else 
			unset($liste[$prefix]);
	}
	return $liste;
}

// http://doc.spip.org/@ecrire_plugin_actifs
function ecrire_plugin_actifs($plugin,$pipe_recherche=false,$operation='raz') {

	// creer le repertoire cache/ si necessaire ! (installation notamment)
	sous_repertoire(_DIR_CACHE, '', false,true);
	if ($operation!='raz'){
		$plugin_valides = array_intersect(liste_chemin_plugin_actifs(),liste_plugin_files());
		if ($operation=='ajoute')
			$plugin = array_merge($plugin_valides,$plugin);
		if ($operation=='enleve')
			$plugin = array_diff($plugin_valides,$plugin);
	}

	// recharger le xml des plugins a activer
	list($plugin_valides,$ordre) = liste_plugin_valides($plugin,true);
	ecrire_meta('plugin',serialize($plugin_valides));
	effacer_meta('message_crash_plugins'); // baisser ce flag !
	$plugin_header_info = array();
	foreach($plugin_valides as $p=>$resume){
		$plugin_header_info[]= $p.($resume['version']?"(".$resume['version'].")":"");
	}
	ecrire_meta('plugin_header',substr(strtolower(implode(",",$plugin_header_info)),0,900));
	// generer charger_plugins_chemin.php
	plugins_precompile_chemin($plugin_valides, $ordre);
	// generer les fichiers
	// charger_plugins_options.php
	// charger_plugins_fonctions.php
	// et retourner les fichiers a verifier
	$verifs = plugins_precompile_xxxtions($plugin_valides, $ordre);
	// mise a jour de la matrice des pipelines
	pipeline_matrice_precompile($plugin_valides, $ordre, $pipe_recherche);
	// ecrire les fichiers
	// _CACHE_PLUGINS_VERIF et _CACHE_PIPELINE
	pipeline_precompile($verifs);
}

function plugins_precompile_chemin($plugin_valides, $ordre)
{
	if (defined('_DIR_PLUGINS_SUPPL'))
		$dir_plugins_suppl = ":" . implode(array_filter(explode(':',_DIR_PLUGINS_SUPPL)),'|') . ":";
	
	$chemins = array();
	foreach($ordre as $p => $info){
		$dir_type = $plugin_valides[$p]['dir_type'];
		$plug = $plugin_valides[$p]['dir'];
		// definir le plugin, donc le path avant l'include du fichier options
		// permet de faire des include_spip pour attraper un inc_ du plugin

		if($dir_plugins_suppl && preg_match($dir_plugins_suppl,$plug)){
			$dir = "_DIR_RACINE.'".str_replace(_DIR_RACINE,'',$plug)."/'";
		}else{
			$dir = $dir_type.".'"
			  . str_replace(constant($dir_type), '', $plug)
			  ."/'";
		}
		$prefix = strtoupper(preg_replace(',\W,','_',$info['prefix']));
		$contenu .= "define('_DIR_PLUGIN_$prefix',$dir);\n";
		foreach($info['path'] as $chemin){
			if (!isset($chemin['version']) OR plugin_version_compatible($chemin['version'],$GLOBALS['spip_version_branche'])){
				$dir = $chemin['dir'];
				if (strlen($dir) AND $dir{0}=="/") $dir = substr($dir,1);
				if (!isset($chemin['type']) OR $chemin['type']=='public')
					$chemins['public'][]="_DIR_PLUGIN_$prefix".(strlen($dir)?".'$dir'":"");
				if (!isset($chemin['type']) OR $chemin['type']=='prive')
					$chemins['prive'][]="_DIR_PLUGIN_$prefix".(strlen($dir)?".'$dir'":"");
			}
		}
	}
	if (count($chemins)){
		$contenu .= "if (_DIR_RESTREINT) _chemin(implode(':',array(".implode(',',array_reverse($chemins['public'])).")));\n"
		  . "else _chemin(implode(':',array(".implode(',',array_reverse($chemins['prive'])).")));\n";
	}

	ecrire_fichier_php(_CACHE_PLUGINS_PATH, $contenu);
}

function plugins_precompile_xxxtions($plugin_valides, $ordre)
{
	$contenu = array('options' => '', 'fonctions' =>'');
	$liste_fichier_verif = array();
	$boutons = array();
	$onglets = array();

	foreach($ordre as $p => $info){
		$dir_type = $plugin_valides[$p]['dir_type'];
		$plug = $plugin_valides[$p]['dir'];
		$dir = constant($dir_type);
		$root_dir_type = str_replace('_DIR_','_ROOT_',$dir_type);
		// Eliminer le rep s'il y est deja
		// (j'espere que c'est du code mort, c'est abominable).
		if (strpos($plug, $dir) === 0) {
			  $plug = substr($plug,strlen($dir));
		}
		if ($info['bouton'])
			$boutons = array_merge($boutons,$info['bouton']);
		if ($info['onglet'])
			$onglets = array_merge($onglets,$info['onglet']);
		foreach($contenu as $charge => $v){
		  if (isset($info[$charge])) foreach($info[$charge] as $file){
		// on genere un if file_exists devant chaque include
		// pour pouvoir garder le meme niveau d'erreur general
				$file = trim($file);
				$_file = $root_dir_type . ".'$plug/$file'";
				$contenu[$charge] .= "if (file_exists(\$f=$_file)){include_once \$f;}\n";
				$liste_fichier_verif["$root_dir_type:$plug/$file"]=1;
			}
		}
	}

	$contenu['options'] .= plugin_ongletbouton("boutons_plugins", $boutons)
	. plugin_ongletbouton("onglets_plugins", $onglets);

	ecrire_fichier_php(_CACHE_PLUGINS_OPT, $contenu['options']);
	ecrire_fichier_php(_CACHE_PLUGINS_FCT, $contenu['fonctions']);
	return $liste_fichier_verif;
}

function plugin_ongletbouton($nom, $val)
{
	$val =!$val ? 'array()'
	: ("unserialize('".str_replace("'","\'",serialize($val))."')");
	return "function $nom(){return $val;}\n";
}

// creer le fichier CACHE_PLUGIN_VERIF à partir de
// $GLOBALS['spip_pipeline']
// $GLOBALS['spip_matrice']

function pipeline_matrice_precompile($plugin_valides, $ordre, $pipe_recherche)
{
	static $liste_pipe_manquants=array();
	if (($pipe_recherche)&&(!in_array($pipe_recherche,$liste_pipe_manquants)))
		$liste_pipe_manquants[]=$pipe_recherche;

	foreach($ordre as $p => $info){
		$dir_type = $plugin_valides[$p]['dir_type'];
		$root_dir_type = str_replace('_DIR_','_ROOT_',$dir_type);
		$plug = $plugin_valides[$p]['dir'];
		$prefix = $info['prefix']."_";
		if (isset($info['pipeline']) AND is_array($info['pipeline'])){
			foreach($info['pipeline'] as $pipe){
				$nom = $pipe['nom'];
				if (isset($pipe['action']))
						$action = $pipe['action'];
				else
						$action = $nom;
				$nomlower = strtolower($nom);
				if ($nomlower!=$nom 
				AND isset($GLOBALS['spip_pipeline'][$nom]) 
				AND !isset($GLOBALS['spip_pipeline'][$nomlower])){
					$GLOBALS['spip_pipeline'][$nomlower] = $GLOBALS['spip_pipeline'][$nom];
					unset($GLOBALS['spip_pipeline'][$nom]);
				}
				$nom = $nomlower;
				if (!isset($GLOBALS['spip_pipeline'][$nom])) // creer le pipeline eventuel
					$GLOBALS['spip_pipeline'][$nom]="";
				if (strpos($GLOBALS['spip_pipeline'][$nom],"|$prefix$action")===FALSE)
					$GLOBALS['spip_pipeline'][$nom] = preg_replace(",(\|\||$),","|$prefix$action\\1",$GLOBALS['spip_pipeline'][$nom],1);
				if (isset($pipe['inclure'])){
					$GLOBALS['spip_matrice']["$prefix$action"] =
						"$root_dir_type:$plug/".$pipe['inclure'];
				}
			}
		}
	}
	
	// on charge les fichiers d'options qui peuvent completer 
	// la globale spip_pipeline egalement
	if (@is_readable(_CACHE_PLUGINS_PATH))
		include_once(_CACHE_PLUGINS_PATH); // securite : a priori n'a pu etre fait plus tot 
	if (@is_readable(_CACHE_PLUGINS_OPT)) {
		include_once(_CACHE_PLUGINS_OPT);
	} else {
		spip_log("pipelines desactives: impossible de produire " . _CACHE_PLUGINS_OPT);
	}
	
	// on ajoute les pipe qui ont ete recenses manquants
	foreach($liste_pipe_manquants as $add_pipe)
		if (!isset($GLOBALS['spip_pipeline'][$add_pipe]))
			$GLOBALS['spip_pipeline'][$add_pipe]= '';
}

// precompilation des pipelines
// http://doc.spip.org/@pipeline_precompile
function pipeline_precompile($verifs){
	global $spip_pipeline, $spip_matrice;

	$content = "";
	foreach($spip_pipeline as $action=>$pipeline){
		$s_inc = "";
		$s_call = "";
		$pipe = array_filter(explode('|',$pipeline));
		// Eclater le pipeline en filtres et appliquer chaque filtre
		foreach ($pipe as $fonc) {
			$fonc = trim($fonc);
			$s_call .= '$val = minipipe(\''.$fonc.'\', $val);'."\n";
			if (isset($spip_matrice[$fonc])){
				$file = $spip_matrice[$fonc];
				$verifs[$file] = 1;
				$file = "'$file'";
				// si un _DIR_XXX: est dans la chaine, on extrait la constante
				if (preg_match(",(_(DIR|ROOT)_[A-Z_]+):,Ums",$file,$regs)){
					$dir = $regs[1];
					$root_dir = str_replace('_DIR_','_ROOT_',$dir);
					if (defined($root_dir))
						$dir = $root_dir;
					$file = str_replace($regs[0],"'.".$dir.".'",$file);
					$file = str_replace("''.","",$file);
					$file = str_replace(constant($dir), '', $file);
				}
				$s_inc .= 'if (file_exists($f='
				. $file . ')){include_once($f);}'."\n";
			}
		}
		$content .= "function execute_pipeline_$action(&\$val){\n"
		. $s_inc
		. $s_call
		. "return \$val;\n}\n";
	}
	ecrire_fichier_php(_CACHE_PIPELINES, $content, "// Pipeline $action \n");
	// on note dans tmp la liste des fichiers qui doivent etre presents,
	// pour les verifier "souvent"
	// ils ne sont verifies que depuis l'espace prive,
	// mais peuvent etre reconstruit depuis l'espace public
	// dans le cas d'un plugin non declare, 
	// spip etant mis devant le fait accompli
	// hackons donc avec un "../" en dur dans ce cas,
	// qui ne manquera pas de nous embeter un jour...
	$verifs = array_keys($verifs);
	foreach ($verifs as $k => $f){
		// si un _DIR_XXX: est dans la chaine, on extrait la constante
		if (preg_match(",(_(DIR|ROOT)_[A-Z_]+):,Ums",$f,$regs))
			$f = str_replace($regs[0],$regs[2]=="ROOT"?constant($regs[1]):(_DIR_RACINE?"":"../").constant($regs[1]),$f);
		$verifs[$k] = $f;
	}
	ecrire_fichier(_CACHE_PLUGINS_VERIF, serialize($verifs));
	clear_path_cache();
}

// pas sur que ca serve...
// http://doc.spip.org/@liste_plugin_inactifs
function liste_plugin_inactifs(){
	return array_diff (liste_plugin_files(),liste_chemin_plugin_actifs());
}

// mise a jour du meta en fonction de l'etat du repertoire
// Les  ecrire_meta() doivent en principe aussi initialiser la valeur a vide
// si elle n'existe pas
// risque de pb en php5 a cause du typage ou de null (verifier dans la doc php)
function actualise_plugins_actifs($pipe_recherche = false){
	if (!spip_connect()) return false;
	$plugin_actifs = liste_chemin_plugin_actifs();
	$plugin_liste = liste_plugin_files();
	$plugin_new = array_intersect($plugin_actifs,$plugin_liste);
	$actifs_avant = $GLOBALS['meta']['plugin'];
	ecrire_plugin_actifs($plugin_new,$pipe_recherche);
	// retourner -1 si la liste des plugins actifs change
	return (strcmp($GLOBALS['meta']['plugin'],$actifs_avant)==0) ? 1 : -1;
}


// http://doc.spip.org/@plugin_est_installe
function plugin_est_installe($plug_path){
	$plugin_installes = isset($GLOBALS['meta']['plugin_installes'])?unserialize($GLOBALS['meta']['plugin_installes']):array();
	if (!$plugin_installes) return false;
	return in_array($plug_path,$plugin_installes);
}

// http://doc.spip.org/@verifie_include_plugins
function verifie_include_plugins() {
	include_spip('inc/meta');
	ecrire_meta('message_crash_plugins', 1);

/*	if (_request('exec')!="admin_plugin"
	AND $_SERVER['X-Requested-With'] != 'XMLHttpRequest'){
		if (@is_readable(_DIR_PLUGINS)) {
			include_spip('inc/headers');
			redirige_url_ecrire("admin_plugin");
		}
		// plus de repertoire plugin existant, le menu n'existe plus
		// on fait une mise a jour silencieuse
		// generer les fichiers php precompiles
		// de chargement des plugins et des pipelines
		actualise_plugins_actifs();
		spip_log("desactivation des plugins suite a suppression du repertoire");
	}
*/
}


// http://doc.spip.org/@message_crash_plugins
function message_crash_plugins() {
	if (autoriser('configurer')
	AND lire_fichier(_CACHE_PLUGINS_VERIF,$l)
	AND $l = @unserialize($l)) {
		$err = array();
		foreach ($l as $fichier) {
			if (!@is_readable($fichier)) {
				spip_log("Verification plugin: echec sur $fichier !");
				$err[] = $fichier;
			}
		}

		if ($err) {
			$err = array_map('joli_repertoire', array_unique($err));
			return "<a href='".generer_url_ecrire('admin_plugin')."'>"
				._T('plugins_erreur',
					array('plugins' => join(', ', $err)))
				.'</a>';
		}
	}
}

function ecrire_fichier_php($nom, $contenu, $comment='')
{
	ecrire_fichier($nom, 
		       '<'.'?php' . "\n" . $comment ."\nif (defined('_ECRIRE_INC_VERSION')) {\n". $contenu . "}\n?".'>');
}
?>
