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
	if (!preg_match(',^[\[\(\]]([0-9.a-zRC\s\-]*)[;]([0-9.a-zRC\s\-\*]*)[\]\)\[]$,',$intervalle,$regs)) return false;
	// Extraction des bornes et traitement de * pour la borne sup :
	// -- on autorise uniquement les ecritures 3.0.*, 3.*
	$minimum = $regs[1];
	$maximum = $regs[2];
	$minimum_inc = $intervalle{0}=="[";
	$maximum_inc = substr($intervalle,-1)=="]";

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



// Construire la liste des infos strictement necessaires aux plugins a activer
// afin de les memoriser dans une meta pas trop grosse
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

	// creer une premiere liste non ordonnee mais qui ne retient
	// que les plugins valides, et dans leur derniere version en cas de doublon
	$infos['_DIR_RESTREINT'][''] = $get_infos('./',$force,_DIR_RESTREINT,'plugin.xml');
	$infos['_DIR_RESTREINT']['SPIP']['version'] = $GLOBALS['spip_version_branche'];
	$infos['_DIR_RESTREINT']['SPIP']['path'] = array();
	$liste_non_classee = array('SPIP'=>array(
		'nom' => 'SPIP',
		'etat' => 'stable',
		'version' => $GLOBALS['spip_version_branche'],
		'dir_type' => '_DIR_RESTREINT',
		'dir'=> '',
	)
	);

	// les procure de core.xml sont consideres comme des plugins installes
	foreach($infos['_DIR_RESTREINT']['']['procure'] as $procure) {
		$procure['nom'] = $procure['id'];
		$procure['etat'] = '?';
		$procure['dir_type'] = '_DIR_RESTREINT';
		$procure['dir'] = '';
		$liste_non_classee[strtoupper($procure['id'])] = $procure;
	}
	
	foreach($liste_ext as $plug){
	  if (isset($infos['_DIR_EXTENSIONS'][$plug]))
	    plugin_valide_resume($liste_non_classee, $plug, $infos, '_DIR_EXTENSIONS');
	}
	foreach($liste_plug as $plug) {
	  if (isset($infos['_DIR_PLUGINS'][$plug]))
	    plugin_valide_resume($liste_non_classee, $plug, $infos, '_DIR_PLUGINS');
	}
	return array($infos, $liste_non_classee);
}

// Ne retenir un plugin que s'il est valide
// et dans leur plus recente version compatible
// avec la version presente de SPIP

function plugin_valide_resume(&$liste, $plug, $infos, $dir)
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

/**
 * Liste les chemins vers les plugins actifs du dossier fourni en argument
 * a partir d'une liste d'elelements construits par plugin_valide_resume
 *
 * @return array
 */
// http://doc.spip.org/@liste_chemin_plugin_actifs
function liste_chemin_plugin_actifs($dir_plugins=_DIR_PLUGINS){
	include_spip('plugins/installer');
	$liste = liste_plugin_actifs();
	foreach ($liste as $prefix=>$infos) {
		if (defined($infos['dir_type']) 
		AND constant($infos['dir_type'])==$dir_plugins)
			$liste[$prefix] = $infos['dir'];
		else 
			unset($liste[$prefix]);
	}
	return $liste;
}

// Pour tester utilise, il faut connaitre tous les plugins 
// qui seront forcement pas la a la fin,
// car absent de la liste des plugins actifs.
// Il faut donc construire une liste ordonnee
// Cette fonction detecte des dependances circulaires, 
// avec un doute sur un "utilise" qu'on peut ignorer.
// Mais ne pas inserer silencieusement et risquer un bug sournois latent

function plugin_trier($infos, $liste_non_classee)
{
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
			  if (!isset($liste[$nom]) OR !plugin_version_compatible($need['version'],$liste[$nom]['version'])) {
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
	return array($liste, $ordre, $liste_non_classee);
}
		
// Collecte les erreurs dans la meta 

function plugins_erreurs($liste_non_classee, $liste, $infos, $msg=array())
{
	static $erreurs = array();
	foreach($liste_non_classee as $p=>$resume){
		$dir_type = $resume['dir_type'];
		$plug = $resume['dir'];
		$k = $infos[$dir_type][$plug];
		$plug = constant($dir_type) . $plug;
		if (!isset($msg[$p])) {
		  if (!$msg[$p] = plugin_necessite($k['necessite'], $liste))
		    $msg[$p] = plugin_necessite($k['utilise'], $liste);
		} else {
		  foreach($msg[$p] as $c => $l)
		    $msg[$p][$c] = plugin_controler_lib($l['nom'], $l['lien']);
		}
		$erreurs[$plug] = $msg[$p];
	}
	ecrire_meta('plugin_erreur_activation',	serialize($erreurs));
}

function plugin_donne_erreurs() {
	if (!isset($GLOBALS['meta']['plugin_erreur_activation'])) return '';
	$list = @unserialize($GLOBALS['meta']['plugin_erreur_activation']);
	// Compat ancienne version
	if (!$list)
	  $list = $GLOBALS['meta']['plugin_erreur_activation'];
	else {
	  foreach($list as $plug => $msg)
	    $list[$plug] = "<li>" . _T('plugin_impossible_activer', array('plugin' => $plug))
		  . "<ul><li>" . implode("</li><li>", $msg) . "</li></ul></li>";
	  $list ="<ul>" . join("\n", $list) . "</ul>";
	}
	effacer_meta('plugin_erreur_activation');
	return $list;
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

// Pour compatibilite et lisibilite du code
function actualise_plugins_actifs($pipe_recherche = false){
	return ecrire_plugin_actifs('', $pipe_recherche, 'force');
}

// mise a jour du meta en fonction de l'etat du repertoire
// Les  ecrire_meta() doivent en principe aussi initialiser la valeur a vide
// si elle n'existe pas
// risque de pb en php5 a cause du typage ou de null (verifier dans la doc php)
// @return true/false si il y a du nouveau
// http://doc.spip.org/@ecrire_plugin_actifs
function ecrire_plugin_actifs($plugin,$pipe_recherche=false,$operation='raz') {

	// creer le repertoire cache/ si necessaire ! (installation notamment)
	sous_repertoire(_DIR_CACHE, '', false,true);
	if (!spip_connect()) return false;
	if ($operation!='raz') {
		$plugin_valides = array_intersect(liste_chemin_plugin_actifs(),liste_plugin_files());
		if ($operation=='ajoute')
			$plugin = array_merge($plugin_valides,$plugin);
		elseif ($operation=='enleve')
			$plugin = array_diff($plugin_valides,$plugin);
		else $plugin = $plugin_valides;
	}
	$actifs_avant = $GLOBALS['meta']['plugin'];
	// recharger le xml des plugins a activer
	list($infos,$liste) = liste_plugin_valides($plugin,true);
	// trouver l'ordre d'activation
	list($plugin_valides,$ordre,$reste) = plugin_trier($infos, $liste);
	if ($reste) plugins_erreurs($reste, $liste, $infos);
	// Ignorer les plugins necessitant une lib absente
	// et preparer la meta d'entete Http
	$err = $msg = $header = array();
	foreach($plugin_valides as $p => $resume) {
		$header[]= $p.($resume['version']?"(".$resume['version'].")":"");
		if ($resume['dir']){ 
			foreach($infos[$resume['dir_type']][$resume['dir']]['lib'] as $l) {
				if (!find_in_path($l['nom'], 'lib/')) {
					$err[$p] = $resume;
					$msg[$p][] = $l;
					unset($plugin_valides[$p]);
				}
			}
		}
	}
	if ($err) plugins_erreurs($err, '', $infos, $msg);

	effacer_meta('message_crash_plugins');
	ecrire_meta('plugin',serialize($plugin_valides));
	ecrire_meta('plugin_header',substr(strtolower(implode(",",$header)),0,900));
	// generer charger_plugins_chemin.php
	plugins_precompile_chemin($plugin_valides, $ordre);
	// generer les fichiers
	// 	charger_plugins_options.php
	// 	charger_plugins_fonctions.php
	// et retourner les fichiers a verifier
	plugins_precompile_xxxtions($plugin_valides, $ordre);
	// mise a jour de la matrice des pipelines
	pipeline_matrice_precompile($plugin_valides, $ordre, $pipe_recherche);
	// generer le fichier _CACHE_PIPELINE
	pipeline_precompile();
	return ($GLOBALS['meta']['plugin'] != $actifs_avant);
}

function plugins_precompile_chemin($plugin_valides, $ordre)
{
	if (defined('_DIR_PLUGINS_SUPPL'))
		$dir_plugins_suppl = ":" . implode(array_filter(explode(':',_DIR_PLUGINS_SUPPL)),'|') . ":";
	
	$chemins = array();
	$contenu = "";
	foreach($ordre as $p => $info){
		$dir_type = $plugin_valides[$p]['dir_type'];
		$plug = $plugin_valides[$p]['dir'];
		// definir le plugin, donc le path avant l'include du fichier options
		// permet de faire des include_spip pour attraper un inc_ du plugin

		if($dir_plugins_suppl && preg_match($dir_plugins_suppl,$plug)){
			$dir = "_DIR_RACINE.'".str_replace(_DIR_RACINE,'',$plug)."/'";
		}else{
			$dir = $dir_type.".'" . $plug ."/'";
		}
		$prefix = strtoupper(preg_replace(',\W,','_',$info['prefix']));
		if ($prefix!=="SPIP"){
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
	$boutons = array();
	$onglets = array();

	foreach($ordre as $p => $info){
		$dir_type = $plugin_valides[$p]['dir_type'];
		$plug = $plugin_valides[$p]['dir'];
		$dir = constant($dir_type);
		$root_dir_type = str_replace('_DIR_','_ROOT_',$dir_type);
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
				$contenu[$charge] .= "include_once_check($_file);\n";
			}
		}
	}

	$contenu['fonctions'] .= plugin_ongletbouton("boutons_plugins", $boutons)
	. plugin_ongletbouton("onglets_plugins", $onglets);

	ecrire_fichier_php(_CACHE_PLUGINS_OPT, $contenu['options']);
	ecrire_fichier_php(_CACHE_PLUGINS_FCT, $contenu['fonctions']);
}

function plugin_ongletbouton($nom, $val)
{
	$val =!$val ? 'array()'
	: ("unserialize('".str_replace("'","\'",serialize($val))."')");
	return "if (!function_exists('$nom')) {function $nom(){return $val;}}\n";
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
		$prefix = (($info['prefix']=="spip")?"":$info['prefix']."_");
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
function pipeline_precompile(){
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
				$s_inc .= "include_once_check($file);\n";
			}
		}
		if (strlen($s_inc))
			$s_inc = "static \$inc=null;\nif (!\$inc){\n$s_inc\$inc=true;\n}\n";
		$content .= "// Pipeline $action \n"
		.	"function execute_pipeline_$action(&\$val){\n"
		. $s_inc
		. $s_call
		. "return \$val;\n}\n";
	}
	ecrire_fichier_php(_CACHE_PIPELINES, $content);
	clear_path_cache();
}


// http://doc.spip.org/@plugin_est_installe
function plugin_est_installe($plug_path){
	$plugin_installes = isset($GLOBALS['meta']['plugin_installes'])?unserialize($GLOBALS['meta']['plugin_installes']):array();
	if (!$plugin_installes) return false;
	return in_array($plug_path,$plugin_installes);
}


function plugin_installes_meta()
{
	$installer_plugins = charger_fonction('installer', 'plugins');
	$meta_plug_installes = array();
	foreach (unserialize($GLOBALS['meta']['plugin']) as $prefix=>$resume) {
		$plug = $resume['dir'];
 		$infos = $installer_plugins($plug, 'install', $resume['dir_type']); 
		if ($infos) {
			if (!is_array($infos) OR $infos['install_test'][0])
				$meta_plug_installes[] = $plug;
			if (is_array($infos)) {
				list($ok, $trace) = $infos['install_test'];
				echo  "<div class='install-plugins'><div>"
				  . _T('plugin_titre_installation', 
				       array('plugin'=>typo($infos['nom'])))
				  . '</div>'
				  . $trace
				  . "<div class='"
				  . ($ok?'ok':'erreur')
				  . "'>"
				  . ($ok ? _L("OK"):_L("Echec"))
				  . "</div></div>";
			}
		}
	}
	ecrire_meta('plugin_installes',serialize($meta_plug_installes),'non');
}

function ecrire_fichier_php($nom, $contenu, $comment='')
{
	ecrire_fichier($nom, 
		       '<'.'?php' . "\n" . $comment ."\nif (defined('_ECRIRE_INC_VERSION')) {\n". $contenu . "}\n?".'>');
}
?>
