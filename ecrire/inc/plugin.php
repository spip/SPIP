<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// l'adresse du repertoire de telechargement et de decompactage des plugins
define('_DIR_PLUGINS_AUTO', _DIR_PLUGINS.'auto/');

#include_spip('inc/texte'); // ????? Appelle public/parametrer trop tot avant la reconstruction du chemin des plugins.
include_spip('plugins/installer');

// lecture des sous repertoire plugin existants
// $dir_plugins pour forcer un repertoire (ex: _DIR_PLUGINS_DIST)
// _DIR_PLUGINS_SUPPL pour aller en chercher ailleurs
// (chemins relatifs a la racine du site, separes par des ":")
// http://doc.spip.org/@liste_plugin_files
function liste_plugin_files($dir_plugins = null){
	static $plugin_files=array();
	if (is_null($dir_plugins))
		$dir_plugins = _DIR_PLUGINS;
	if (!isset($plugin_files[$dir_plugins])
	OR count($plugin_files[$dir_plugins]) == 0){
		$plugin_files[$dir_plugins] = array();
		foreach (fast_find_plugin_dirs($dir_plugins) as $plugin) {
			$plugin_files[$dir_plugins][] = substr($plugin,strlen($dir_plugins));
		}
		
		sort($plugin_files[$dir_plugins]);
		// et on lit le XML de tous les plugins pour le mettre en cache
		// et en profiter pour nettoyer ceux qui n'existent plus du cache
		$get_infos = charger_fonction('get_infos','plugins');
		$get_infos($plugin_files[$dir_plugins],false,$dir_plugins,true);
	}
	return $plugin_files[$dir_plugins];
}

function fast_find_plugin_dirs($dir, $max_prof=100) {
	$fichiers = array();
	// revenir au repertoire racine si on a recu dossier/truc
	// pour regarder dossier/truc/ ne pas oublier le / final
	$dir = preg_replace(',/[^/]*$,', '', $dir);
	if ($dir == '') $dir = '.';

	if (!is_dir($dir))
		return $fichiers;
	if (is_plugin_dir($dir,'')) {
		$fichiers[] = $dir;
		return $fichiers;
	}
	if ($max_prof<=0)
		return $fichiers;

	$subdirs = array();
	if (@is_dir($dir) AND is_readable($dir) AND $d = @opendir($dir)) {
		while (($f = readdir($d)) !== false) {
			if ($f[0] != '.' # ignorer . .. .svn etc
			AND $f != 'CVS'
			AND is_dir($f = "$dir/$f"))
				$subdirs[] = $f;
		}
		closedir($d);
	}

	foreach($subdirs as $d){
		$fichiers = array_merge($fichiers,fast_find_plugin_dirs("$d/",$max_prof-1));
	}
	return $fichiers;
}

function is_plugin_dir($dir,$dir_plugins = null){
	if (is_array($dir)){
		foreach($dir as $k=>$d){
			if (!is_plugin_dir($d,$dir_plugins))
				unset($dir[$k]);
		}
		return $dir;
	}
	if (is_null($dir_plugins))
		$dir_plugins = _DIR_PLUGINS;
	$search = array("$dir_plugins$dir/plugin.xml","$dir_plugins$dir/paquet.xml");
	
	foreach($search as $s){
		if (file_exists($s)){
			return $dir;
		}
	}
	return '';
}

// Regexp d'extraction des informations d'un intervalle de compatibilité
define('_EXTRAIRE_INTERVALLE', ',^[\[\(\]]([0-9.a-zRC\s\-]*)[;]([0-9.a-zRC\s\-\*]*)[\]\)\[]$,');

/**
 * Teste si le numéro de version d'un plugin est dans un intervalle donné.
 *
 * Cette fonction peut être volontairement trompée (phase de développement) :
 * voir commentaire infra sur l'utilisation de la constante _DEV_PLUGINS
 *
 * @param string $intervalle
 * 		Un intervalle entre 2 versions. ex: [2.0.0-dev;2.1.*]
 * @param string $version
 * 		Un numéro de version. ex: 3.1.99]
 * @param string $avec_quoi
 * 		Ce avec quoi est testée la compatibilité. par défaut ('')
 * 		avec un plugin (cas des 'necessite'), parfois ('spip')
 * 		avec SPIP.
 * @return bool
 * 		True si dans l'intervalle, false sinon.
**/
function plugin_version_compatible($intervalle, $version, $avec_quoi = '') {

	if (!strlen($intervalle)) return true;
	if (!preg_match(_EXTRAIRE_INTERVALLE,$intervalle,$regs)) return false;
	// Extraction des bornes et traitement de * pour la borne sup :
	// -- on autorise uniquement les ecritures 3.0.*, 3.*
	$minimum = $regs[1];
	$maximum = $regs[2];

	//  si une borne de compatibilité supérieure a été définie (dans
	//  mes_options.php, sous la forme : define('_DEV_PLUGINS', '3.1.99');
	//  on l'utilise (phase de dev, de test...) mais *que* en cas de comparaison
	//  avec la version de SPIP (ne nuit donc pas aux tests de necessite
	//  entre plugins)
	if (defined('_DEV_PLUGINS') && $avec_quoi == 'spip') {
		$maximum = _DEV_PLUGINS.']';
	}

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
	$liste_ext = liste_plugin_files(_DIR_PLUGINS_DIST);
	$get_infos = charger_fonction('get_infos','plugins');
	$infos = array(
		// lister les extensions qui sont automatiquement actives
		'_DIR_PLUGINS_DIST' => $get_infos($liste_ext, $force, _DIR_PLUGINS_DIST),
		'_DIR_PLUGINS' => $get_infos($liste_plug, $force, _DIR_PLUGINS)
		       );

	// creer une premiere liste non ordonnee mais qui ne retient
	// que les plugins valides, et dans leur derniere version en cas de doublon
	$infos['_DIR_RESTREINT'][''] = $get_infos('./',$force,_DIR_RESTREINT);
	$infos['_DIR_RESTREINT']['SPIP']['version'] = $GLOBALS['spip_version_branche'];
	$infos['_DIR_RESTREINT']['SPIP']['chemin'] = array();
	$liste_non_classee = array('SPIP'=>array(
		'nom' => 'SPIP',
		'etat' => 'stable',
		'version' => $GLOBALS['spip_version_branche'],
		'dir_type' => '_DIR_RESTREINT',
		'dir'=> '',
	)
	);

	foreach($liste_ext as $plug){
	  if (isset($infos['_DIR_PLUGINS_DIST'][$plug]))
	    plugin_valide_resume($liste_non_classee, $plug, $infos, '_DIR_PLUGINS_DIST');
	}
	foreach($liste_plug as $plug) {
	  if (isset($infos['_DIR_PLUGINS'][$plug]))
	    plugin_valide_resume($liste_non_classee, $plug, $infos, '_DIR_PLUGINS');
	}
	
	if (defined('_DIR_PLUGINS_SUPPL') and _DIR_PLUGINS_SUPPL) {
		$infos['_DIR_PLUGINS_SUPPL'] = $get_infos($liste_plug, false, _DIR_PLUGINS_SUPPL);
		foreach($liste_plug as $plug) {
			if (isset($infos['_DIR_PLUGINS_SUPPL'][$plug]))
				plugin_valide_resume($liste_non_classee, $plug, $infos, '_DIR_PLUGINS_SUPPL');
		}
	}
	
	// les procure de core.xml sont consideres comme des plugins proposes,
	// mais surchargeables (on peut activer un plugin qui procure ca pour l'ameliorer,
	// donc avec le meme prefixe)
	foreach($infos['_DIR_RESTREINT']['']['procure'] as $procure) {
		$p = strtoupper($procure['nom']);
		if (!isset($liste_non_classee[$p])){
			$procure['etat'] = '?';
			$procure['dir_type'] = '_DIR_RESTREINT';
			$procure['dir'] = '';
			$liste_non_classee[$p] = $procure;
		}
	}

	return array($infos, $liste_non_classee);
}

// Ne retenir un plugin que s'il est valide
// et dans leur plus recente version compatible
// avec la version presente de SPIP

function plugin_valide_resume(&$liste, $plug, $infos, $dir)
{
	$i = $infos[$dir][$plug];
	if (isset($i['erreur']) AND $i['erreur'])
		return;
	if (!plugin_version_compatible($i['compatibilite'], $GLOBALS['spip_version_branche'],'spip'))
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
 * extrait les chemins d'une liste de plugin
 * selectionne au passage ceux qui sont dans $dir_plugins uniquement
 * si valeur non vide
 * 
 * @param array $liste
 * @param string $dir_plugins
 * @return array
 */
function liste_chemin_plugin($liste, $dir_plugins=_DIR_PLUGINS){
	foreach ($liste as $prefix=>$infos) {
		if (!$dir_plugins
			OR (
				defined($infos['dir_type'])
		    AND constant($infos['dir_type'])==$dir_plugins))
			$liste[$prefix] = $infos['dir'];
		else
			unset($liste[$prefix]);
	}
	return $liste;
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
	return liste_chemin_plugin(liste_plugin_actifs(), $dir_plugins);
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
			  $compat = isset($need['compatibilite']) ? $need['compatibilite'] : '';
			  if (!isset($liste[$nom]) OR !plugin_version_compatible($compat,$liste[$nom]['version'])) {
			      $info1 = false;
			      break;
			  }
			}
			if (!$info1) continue;
			// idem si des plugins sont utiles,
			// sauf si ils sont de toute facon absents de la liste
			foreach($info1['utilise'] as $need){
			  $nom = strtoupper($need['nom']);
			  $compat = isset($need['compatibilite']) ? $need['compatibilite'] : '';
			  if (isset($toute_la_liste[$nom])) {
			    if (!isset($liste[$nom]) OR 
				!plugin_version_compatible($compat, $liste[$nom]['version'])) {
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

function plugin_donne_erreurs($raw=false, $raz=true) {
	if (!isset($GLOBALS['meta']['plugin_erreur_activation'])) return $raw?array():'';
	$list = @unserialize($GLOBALS['meta']['plugin_erreur_activation']);
	// Compat ancienne version
	if (!$list)
	  $list = $raw?array():$GLOBALS['meta']['plugin_erreur_activation'];
	elseif(!$raw) {
	  foreach($list as $plug => $msg)
	    $list[$plug] = "<li>" . _T('plugin_impossible_activer', array('plugin' => $plug))
		  . "<ul><li>" . implode("</li><li>", $msg) . "</li></ul></li>";
	  $list ="<ul>" . join("\n", $list) . "</ul>";
	}
	if ($raz)
		effacer_meta('plugin_erreur_activation');
	return $list;
}

/**
 * Teste des dependances
 * Et verifie que chaque dependance est presente
 * dans la liste de plugins donnee
 *
 * @param array $n
 * 		Tableau de dependances dont on souhaite verifier leur presence
 * @param array $liste
 * 		Tableau des plugins presents
 * @return array
 * 		Tableau des messages d'erreurs recus. Il sera vide si tout va bien.
 * 
**/
function plugin_necessite($n, $liste) {
	$msg = array();
	foreach($n as $need){
		$id = strtoupper($need['nom']);
		if ($r = plugin_controler_necessite($liste, $id, $need['compatibilite'])) {
			$msg[] = $r;
		}
	}
	return $msg;
}

/**
 * Verifie qu'une dependance (plugin) est bien presente. 
 *
 * @param $liste
 * 		Liste de description des plugins
 * @param $nom
 * 		Le plugin donc on cherche la presence
 * @param $version
 * 		L'éventuelle intervalle de compatibilité de la dependance. ex: [1.1.0;]
 * @return string.
 * 		Vide si ok,
 * 		Message d'erreur lorsque la dependance est absente.
**/
function plugin_controler_necessite($liste, $nom, $version)
{
	if (isset($liste[$nom]) AND plugin_version_compatible($version,$liste[$nom]['version'])) {
		return '';
	}
	// retrouver le minimum
	if (preg_match(_EXTRAIRE_INTERVALLE, $version, $regs)) {
		$minimum = $regs[1];
		if ($minimum) {
			return _T('plugin_necessite_plugin', array(
				'plugin' => $nom,
				'version' => $minimum));
		}
	}
	return _T('plugin_necessite_plugin_sans_version', array('plugin' => $nom));
}

function plugin_controler_lib($lib, $url)
{
	/* Feature sortie du core, voir STP
	 * if ($url) {
		include_spip('inc/charger_plugin');
		$url = '<br />'	. bouton_telechargement_plugin($url, 'lib');
	}*/
	return _T('plugin_necessite_lib', array('lib'=>$lib)) . " <a href='$url'>$url</a>";
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
		$plugin_valides = liste_chemin_plugin_actifs();
		$plugin_valides = is_plugin_dir($plugin_valides);
		if(defined('_DIR_PLUGINS_SUPPL') && _DIR_PLUGINS_SUPPL){
			$plugin_valides_supp = liste_chemin_plugin_actifs(_DIR_PLUGINS_SUPPL);
			$plugin_valides_supp = is_plugin_dir($plugin_valides_supp,_DIR_PLUGINS_SUPPL);
			$plugin_valides = array_merge($plugin_valides,$plugin_valides_supp);
		}
		// si des plugins sont en attentes (coches mais impossible a activer)
		// on les reinjecte ici
		if (isset($GLOBALS['meta']['plugin_attente'])
		  AND $a = unserialize($GLOBALS['meta']['plugin_attente']))
		$plugin_valides = $plugin_valides + liste_chemin_plugin($a);
		
		if ($operation=='ajoute')
			$plugin = array_merge($plugin_valides,$plugin);
		elseif ($operation=='enleve')
			$plugin = array_diff($plugin_valides,$plugin);
		else $plugin = $plugin_valides;
	}
	$actifs_avant = $GLOBALS['meta']['plugin'];

	// si une fonction de gestion de dependances existe, l'appeler ici
	if ($ajouter_dependances = charger_fonction("ajouter_dependances","plugins",true)){
		$plugin = $ajouter_dependances($plugin);
	}

	// recharger le xml des plugins a activer
	// on forcer le reload ici, meme si le fichier xml n'a pas change
	// pour ne pas rater l'ajout ou la suppression d'un fichier fonctions/options/administrations
	// pourra etre evite quand on ne supportera plus les plugin.xml
	// en deplacant la detection de ces fichiers dans la compilation ci dessous
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

	if (isset($GLOBALS['meta']['message_crash_plugins']))
		effacer_meta('message_crash_plugins');
	ecrire_meta('plugin',serialize($plugin_valides));
	$liste = array_diff_key($liste,$plugin_valides);
	ecrire_meta('plugin_attente',serialize($liste));
	$header = strtolower(implode(",",$header));
	ecrire_meta('plugin_header',substr($header,0,900));
	if (!isset($GLOBALS['spip_header_silencieux']) OR !$GLOBALS['spip_header_silencieux'])
		ecrire_fichier(_DIR_VAR."config.txt", (defined('_HEADER_COMPOSED_BY') ? _HEADER_COMPOSED_BY:"Composed-By: SPIP") . ' '. $GLOBALS['spip_version_affichee'] . " @ www.spip.net + " . $header);
	else
		@unlink(_DIR_VAR."config.txt");
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

	// lancer et initialiser les nouveaux crons !
	include_spip('inc/genie');
	genie_queue_watch_dist();

	return ($GLOBALS['meta']['plugin'] != $actifs_avant);
}

function plugins_precompile_chemin($plugin_valides, $ordre)
{
	$chemins = array();
	$contenu = "";
	foreach($ordre as $p => $info){
		// $ordre peur contenir des plugins en attente et non valides pour ce hit
		if (isset($plugin_valides[$p])){
			$dir_type = $plugin_valides[$p]['dir_type'];
			$plug = $plugin_valides[$p]['dir'];
			// definir le plugin, donc le path avant l'include du fichier options
			// permet de faire des include_spip pour attraper un inc_ du plugin

			$dir = $dir_type.".'" . $plug ."/'";
			
			$prefix = strtoupper(preg_replace(',\W,','_',$info['prefix']));
			if ($prefix!=="SPIP"){
				$contenu .= "define('_DIR_PLUGIN_$prefix',$dir);\n";
				foreach($info['chemin'] as $chemin){
					if (!isset($chemin['version']) OR plugin_version_compatible($chemin['version'],$GLOBALS['spip_version_branche'],'spip')){
						$dir = $chemin['path'];
						if (strlen($dir) AND $dir{0}=="/") $dir = substr($dir,1);
						if (strlen($dir) AND $dir=="./") $dir = '';
						if (strlen($dir)) $dir = rtrim($dir,'/').'/';
						if (!isset($chemin['type']) OR $chemin['type']=='public')
							$chemins['public'][]="_DIR_PLUGIN_$prefix".(strlen($dir)?".'$dir'":"");
						if (!isset($chemin['type']) OR $chemin['type']=='prive')
							$chemins['prive'][]="_DIR_PLUGIN_$prefix".(strlen($dir)?".'$dir'":"");
					}
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
	$sign = "";

	foreach($ordre as $p => $info){
		// $ordre peur contenir des plugins en attente et non valides pour ce hit
		if (isset($plugin_valides[$p])){
			$dir_type = $plugin_valides[$p]['dir_type'];
			$plug = $plugin_valides[$p]['dir'];
			$dir = constant($dir_type);
			$root_dir_type = str_replace('_DIR_','_ROOT_',$dir_type);
			if ($info['menu'])
				$boutons = array_merge($boutons,$info['menu']);
			if ($info['onglet'])
				$onglets = array_merge($onglets,$info['onglet']);
			foreach($contenu as $charge => $v){
				// si pas declare/detecte a la lecture du paquet.xml,
				// detecer a nouveau ici puisque son ajout ne provoque pas une modif du paquet.xml
				// donc ni sa relecture, ni sa detection
				if (!isset($info[$charge])
					AND $dir // exclure le cas du plugin "SPIP"
					AND file_exists("$dir$plug/paquet.xml") // uniquement pour les paquet.xml
					){
					if (is_readable("$dir$plug/".($file=$info['prefix']."_".$charge.".php"))){
						$info[$charge] = array($file);
					}
				}
				if (isset($info[$charge])){
					$files = $info[$charge];
					foreach($files as $k=>$file){
						// on genere un if file_exists devant chaque include
						// pour pouvoir garder le meme niveau d'erreur general
						$file = trim($file);
						if (!is_readable("$dir$plug/$file")
							// uniquement pour les paquet.xml
							AND file_exists("$dir$plug/paquet.xml")){
							unset($info[$charge][$k]);
						}
						else {
							$_file = $root_dir_type . ".'$plug/$file'";
							$contenu[$charge] .= "include_once_check($_file);\n";
						}
					}
				}
			}
			$sign .= md5(serialize($info));
		}
	}

	$contenu['options'] = "define('_PLUGINS_HASH','".md5($sign)."');\n" . $contenu['options'];
	$contenu['fonctions'] .= plugin_ongletbouton("boutons_plugins", $boutons)
	. plugin_ongletbouton("onglets_plugins", $onglets);

	ecrire_fichier_php(_CACHE_PLUGINS_OPT, $contenu['options']);
	ecrire_fichier_php(_CACHE_PLUGINS_FCT, $contenu['fonctions']);
}

function plugin_ongletbouton($nom, $val)
{
	if (!$val) $val = array();
	define("_UPDATED_$nom",$val = serialize($val));
	define("_UPDATED_md5_$nom",$md5=md5($val));
	$val = "unserialize('".str_replace("'","\'",$val)."')";
	return
		"if (!function_exists('$nom')) {\n"
	 ."function $nom(){return defined('_UPDATED_$nom')?unserialize(_UPDATED_$nom):$val;}\n"
		."function md5_$nom(){return defined('_UPDATED_md5_$nom')?_UPDATED_md5_$nom:'".$md5."';}\n"
	 ."}\n";
}

// creer le fichier CACHE_PLUGIN_VERIF a partir de
// $GLOBALS['spip_pipeline']
// $GLOBALS['spip_matrice']

function pipeline_matrice_precompile($plugin_valides, $ordre, $pipe_recherche)
{
	static $liste_pipe_manquants=array();
	if (($pipe_recherche)&&(!in_array($pipe_recherche,$liste_pipe_manquants)))
		$liste_pipe_manquants[]=$pipe_recherche;

	foreach($ordre as $p => $info){
		// $ordre peur contenir des plugins en attente et non valides pour ce hit
		if (isset($plugin_valides[$p])){
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
					// une action vide est une declaration qui ne doit pas etre compilee !
					if (!isset($GLOBALS['spip_pipeline'][$nom])) // creer le pipeline eventuel
						$GLOBALS['spip_pipeline'][$nom]="";
					if ($action){
						if (strpos($GLOBALS['spip_pipeline'][$nom],"|$prefix$action")===FALSE)
							$GLOBALS['spip_pipeline'][$nom] = preg_replace(",(\|\||$),","|$prefix$action\\1",$GLOBALS['spip_pipeline'][$nom],1);
						if (isset($pipe['inclure'])){
							$GLOBALS['spip_matrice']["$prefix$action"] =
								"$root_dir_type:$plug/".$pipe['inclure'];
						}
					}
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
		if ($plug = $resume['dir']){
			$infos = $installer_plugins($plug, 'install', $resume['dir_type']);
			if ($infos){
				if (!is_array($infos) OR $infos['install_test'][0])
					$meta_plug_installes[] = $plug;
				if (is_array($infos)){
					list($ok, $trace) = $infos['install_test'];
					include_spip('inc/filtres_boites');
					echo  "<div class='install-plugins svp_retour'>"
						  .boite_ouvrir(_T('plugin_titre_installation', array('plugin' => typo($infos['nom']))), ($ok ? 'success' : 'error'))
					      .$trace
					      ."<div class='result'>"
					      .($ok ? ((isset($infos['upgrade']) && $infos['upgrade']) ? _T("plugin_info_upgrade_ok") : _T("plugin_info_install_ok")) : _T("avis_operation_echec"))
					      ."</div>"
					      .boite_fermer()
					      ."</div>";
				}
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
