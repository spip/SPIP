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

// librairie pour parametrage plugin
//
define('_FILE_PLUGIN_CONFIG', "plugin.xml");

// besoin de inc_meta
include_spip('inc/meta');

// lecture des sous repertoire plugin existants
// http://doc.spip.org/@liste_plugin_files
function liste_plugin_files(){
	static $plugin_files=array();
	if (!count($plugin_files)){
		foreach (preg_files(_DIR_PLUGINS, '/plugin[.]xml$') as $plugin) {
			$plugin_files[]=substr(dirname($plugin), strlen(_DIR_PLUGINS));
		}
		sort($plugin_files);
	}
	return $plugin_files;
}

function liste_plugin_valides($liste_plug,&$infos){
	$liste = array();
	$infos = array();
	if (is_array($liste_plug))
		foreach($liste_plug as $plug){
			$infos[$plug] = plugin_get_infos($plug);
			if (!isset($infos[$plug]['erreur']) && !isset($plugin_valides[$p=strtoupper($infos[$plug]['prefix'])]))
				$liste[$p] = array('dir'=>$plug,'version'=>isset($infos[$plug]['version'])?$infos[$plug]['version']:NULL);
		}
	return $liste;
}

//  A utiliser pour initialiser ma variable globale $plugin
// http://doc.spip.org/@liste_plugin_actifs
function liste_plugin_actifs(){
  $meta_plugin = isset($GLOBALS['meta']['plugin'])?$GLOBALS['meta']['plugin']:'';
  if (strlen($meta_plugin)>0){
  	if (is_array($t=unserialize($meta_plugin)))
  		return $t;
  	else{ // compatibilite pre 1.9.2, mettre a jour la meta
  		$t = explode(",",$meta_plugin);
  		$liste = liste_plugin_valides($t,$infos);
			ecrire_meta('plugin',serialize($liste));
			ecrire_metas();
			return $liste;
  	}
  }
	else
		return array();
}
function liste_chemin_plugin_actifs(){
	$liste = liste_plugin_actifs();
	foreach ($liste as $prefix=>$infos) {
		$liste[$prefix] = $infos['dir'];
	}
	return $liste;
}

// http://doc.spip.org/@ecrire_plugin_actifs
function ecrire_plugin_actifs($plugin,$pipe_recherche=false,$operation='raz'){
	static $liste_pipe_manquants=array();
	$liste_fichier_verif = array();
	if (($pipe_recherche)&&(!in_array($pipe_recherche,$liste_pipe_manquants)))
		$liste_pipe_manquants[]=$pipe_recherche;
	
	if ($operation!='raz'){
		$plugin_actifs = liste_chemin_plugin_actifs();
		$plugin_liste = liste_plugin_files();
		$plugin_valides = array_intersect($plugin_actifs,$plugin_liste);
		if ($operation=='ajoute')
			$plugin = array_merge($plugin_valides,$plugin);
		if ($operation=='enleve')
			$plugin = array_diff($plugin_valides,$plugin);
	}
	
	$plugin_valides = liste_plugin_valides($plugin,$infos);
	ecrire_meta('plugin',serialize($plugin_valides));

	$start_file = "<"."?php\nif (!defined('_ECRIRE_INC_VERSION')) return;\n";
	$end_file = "\n?".">";
	
	// generer les fichier 
	// charger_plugins_options.php
	// charger_plugins_fonctions.php
	foreach(array('options','fonctions') as $charge){
		$s = "";
		$splugs = "";
		if (is_array($infos)){
			foreach($infos as $plug=>$info){
				// definir le plugin, donc le path avant l'include du fichier options
				// permet de faire des include_ecrire pour attraper un inc_ du plugin
				if ($charge=='options'){
					$prefix = strtoupper($info['prefix']);
					$splugs .= '$GLOBALS[\'plugins\'][]=\''.$plug.'\';';
					$splugs .= "define(_DIR_PLUGIN_$prefix,_DIR_PLUGINS.'$plug/');";
					$splugs .= "\n";
				}
				if (isset($info[$charge])){
					foreach($info[$charge] as $file){
						$s .= "@include_once _DIR_PLUGINS.'$plug/".trim($file)."';\n";
						$liste_fichier_verif[] = "_DIR_PLUGINS.'$plug/".trim($file)."'";
					}
				}
			}
		}
		ecrire_fichier(_DIR_TMP."charger_plugins_$charge.php",
			$start_file . $splugs . $s . $end_file);
	}

	if (is_array($infos)){
		// construire tableaux de pipelines et matrices
		// $GLOBALS['spip_pipeline']
		// $GLOBALS['spip_matrice']
		foreach($infos as $plug=>$info){
			$prefix = "";
			$prefix = $info['prefix']."_";
			if (is_array($info['pipeline'])){
				foreach($info['pipeline'] as $pipe){
					$nom = trim(array_pop($pipe['nom']));
					if (isset($pipe['action']))
						$action = trim(array_pop($pipe['action']));
					else
						$action = $nom;
					if (strpos($GLOBALS['spip_pipeline'][$nom],"|$prefix$action")===FALSE)
						$GLOBALS['spip_pipeline'][$nom] .= "|$prefix$action";
					if (isset($pipe['inclure'])){
						$GLOBALS['spip_matrice']["$prefix$action"] = 
							"_DIR_PLUGINS$plug/".array_pop($pipe['inclure']);
					}
				}
			}
		}
	}
	// on ajoute les pipe qui ont ete recenses manquants
	foreach($liste_pipe_manquants as $add_pipe)
		if (!isset($GLOBALS['spip_pipeline'][$add_pipe]))
			$GLOBALS['spip_pipeline'][$add_pipe]= '';

	$liste_fichier_verif2 = pipeline_precompile();
	$liste_fichier_verif = array_merge($liste_fichier_verif,$liste_fichier_verif2);

	// horrible !
	foreach ($liste_fichier_verif as $k => $f)
		$liste_fichier_verif[$k] = _DIR_PLUGINS.preg_replace(",(_DIR_PLUGINS\.)?',", "", $f);
	ecrire_fichier(_DIR_TMP.'verifier_plugins.txt',
		serialize($liste_fichier_verif));
}

// precompilation des pipelines
// http://doc.spip.org/@pipeline_precompile
function pipeline_precompile(){
	global $spip_pipeline, $spip_matrice;
	$liste_fichier_verif = array();
	
	$start_file = "<"."?php\nif (!defined('_ECRIRE_INC_VERSION')) return;\n";
	$end_file = "\n?".">";
	$content = "";
	foreach($spip_pipeline as $action=>$pipeline){
		$s_inc = "";
		$s_call = "";
		$pipe = array_filter(explode('|',$pipeline));
		// Eclater le pipeline en filtres et appliquer chaque filtre
		foreach ($pipe as $fonc) {
			$s_call .= '$val = minipipe(\''.$fonc.'\', $val);'."\n";
			if (isset($spip_matrice[$fonc])){
				$file = $spip_matrice[$fonc];
				$s_inc .= '@include_once(';
				// si _DIR_PLUGINS est dans la chaine, on extrait la constante
				if (($p = strpos($file,'_DIR_PLUGINS'))!==FALSE){
					$f = "";
					if ($p)
						$f .= "'".substr($file,0,$p)."'.";
					$f .= "_DIR_PLUGINS.";
					$f .= "'".substr($file,$p+12)."'";
					$s_inc .= $f;
					$liste_fichier_verif[] = $f;
				}
				else{
					$s_inc .= "'$file'";
					$liste_fichier_verif[] = "'$file'";
				}
				$s_inc .= ');'."\n";
			}
		}
		$content .= "// Pipeline $action \n";
		$content .= "function execute_pipeline_$action(\$val){\n";
		$content .= $s_inc;
		$content .= $s_call;
		$content .= "return \$val;\n}\n\n";
	}
	ecrire_fichier(_DIR_TMP."charger_pipelines.php",
		$start_file . $content . $end_file);
	return $liste_fichier_verif;
}

// pas sur que ca serve...
// http://doc.spip.org/@liste_plugin_inactifs
function liste_plugin_inactifs(){
	return array_diff (liste_plugin_files(),liste_chemin_plugin_actifs());
}

// mise à jour du meta en fonction de l'état du répertoire
// penser à faire une maj du cache =>  ecrire_meta()
// en principe cela doit aussi initialiser la valeur à vide si elle n'esite pas 
// risque de pb en php5 à cause du typage ou de null (vérifier dans la doc php)
// http://doc.spip.org/@verif_plugin
function verif_plugin($pipe_recherche = false){
	$plugin_actifs = liste_chemin_plugin_actifs();
	$plugin_liste = liste_plugin_files();
	$plugin_new = array_intersect($plugin_actifs,$plugin_liste);
	ecrire_plugin_actifs($plugin_new,$pipe_recherche);
	ecrire_metas();
}

// mise à jour des données si envoi via formulaire
// http://doc.spip.org/@enregistre_modif_plugin
function enregistre_modif_plugin(){
  // recuperer les plugins dans l'ordre des $_POST
  $test = array();
	foreach(liste_plugin_files() as $file){
	  $test["statusplug_$file"] = $file;
	}
	// gerer les noms de repertoires qui ont un espace
	// sachant qu'ils vont arriver dans le $_POST avec un _ a la place
	// mais qu'il faut pas se melanger si jamais deux repertoire existent et ne different
	// que par un espace et un underscore
	foreach($test as $postvar=>$file){
		$alt_postvar = str_replace(" ","_",$postvar); // les espaces deviennent des _
		$alt_postvar = str_replace(".","_",$postvar); // les points deviennent des _
		if (!isset($test[$alt_postvar]))
	  	$test[$alt_postvar] = $file;
	}
	$plugin=array();
	if (!isset($_POST['desactive_tous'])){
		foreach($_POST as $choix=>$val){
			if (isset($test[$choix])&&$val=='O')
				$plugin[]=$test[$choix];
		}
	}
	global $connect_id_auteur, $connect_login;
	spip_log("Changement des plugins actifs par auteur id=$connect_id_auteur :".implode(',',$plugin));
	ecrire_plugin_actifs($plugin);
	ecrire_metas();
}

// http://doc.spip.org/@ordonne_plugin
function ordonne_plugin(){
	$liste = liste_chemin_plugin_actifs();
	$liste_triee = array();
	$i=2;
	foreach($liste as $plug){
		$index = $i;
		$i = $i+2;
		if (rawurldecode($_GET['monter'])==$plug) $index = $index-3;
		if (rawurldecode($_GET['descendre'])==$plug) $index = $index+3;
		$liste_triee[$index] = $plug;
	}
	ksort($liste_triee);
	ecrire_plugin_actifs($liste_triee);
	ecrire_metas();
}

// lecture du fichier de configuration d'un plugin
// http://doc.spip.org/@plugin_get_infos
function plugin_get_infos($plug){
	include_spip('inc/xml');
	static $infos=array();
	if (!isset($infos[$plug])){
	  $ret = array();
	  if ((@file_exists(_DIR_PLUGINS))&&(is_dir(_DIR_PLUGINS))){
			if (@file_exists(_DIR_PLUGINS."$plug/plugin.xml")) {
				$arbre = spip_xml_load(_DIR_PLUGINS."$plug/plugin.xml");
				if (!$arbre OR !isset($arbre['plugin']) OR !is_array($arbre['plugin']))
					$arbre = array('erreur' => array(_T('erreur_plugin_fichier_def_incorrect')." : $plug/plugin.xml"));
			}
			else {
				// pour arriver ici on l'a vraiment cherche...
				$arbre = array('erreur' => array(_T('erreur_plugin_fichier_def_absent')." : $plug/plugin.xml"));
			}
	
			plugin_verifie_conformite($plug,$arbre);
			
			$ret['nom'] = spip_xml_aplatit($arbre['nom']);
			$ret['version'] = trim(end($arbre['version']));
			if (isset($arbre['auteur']))
				$ret['auteur'] = spip_xml_aplatit($arbre['auteur']);
			if (isset($arbre['description']))
				$ret['description'] = spip_xml_aplatit($arbre['description']);
			if (isset($arbre['lien']))
				$ret['lien'] = join(' ',$arbre['lien']);
			if (isset($arbre['etat']))
				$ret['etat'] = trim(end($arbre['etat']));
			if (isset($arbre['options']))
				$ret['options'] = $arbre['options'];
			if (isset($arbre['fonctions']))
				$ret['fonctions'] = $arbre['fonctions'];
			$ret['prefix'] = trim(array_pop($arbre['prefix']));
			if (isset($arbre['pipeline']))
				$ret['pipeline'] = $arbre['pipeline'];
			if (isset($arbre['erreur']))
				$ret['erreur'] = $arbre['erreur'];
		}
		$infos[$plug] = $ret;
	}
	return $infos[$plug];
}

// http://doc.spip.org/@plugin_verifie_conformite
function plugin_verifie_conformite($plug,&$arbre){
	$silence = false;
	if (isset($arbre['plugin'])&&is_array($arbre['plugin']))
		$arbre = end($arbre['plugin']); // derniere def plugin
	else{
		$arbre = array('erreur' => array(_T('erreur_plugin_tag_plugin_absent')." : $plug/plugin.xml"));
		$silence = true;
	}
  // verification de la conformite du plugin avec quelques
  // precautions elementaires
  if (!isset($arbre['nom'])){
  	if (!$silence)
			$arbre['erreur'][] = _T('erreur_plugin_nom_manquant');
		$arbre['nom'] = array("");
	}
  if (!isset($arbre['version'])){
  	if (!$silence)
			$arbre['erreur'][] = _T('erreur_plugin_version_manquant');
		$arbre['version'] = array("");
	}
  if (!isset($arbre['prefix'])){
  	if (!$silence)
			$arbre['erreur'][] = _T('erreur_plugin_prefix_manquant');
		$arbre['prefix'] = array("");
	}
	else{
		$prefix = "";
		$prefix = trim(end($arbre['prefix']));
		if (isset($arbre['etat'])){
			$etat = trim(end($arbre['etat']));
			if (!in_array($etat,array('dev','experimental','test','stable')))
				$arbre['erreur'][] = _T('erreur_plugin_etat_inconnu')." : $etat";
		}
		if (isset($arbre['options'])){
			foreach($arbre['options'] as $optfile){
				$optfile = trim($optfile);
				if (!@is_readable(_DIR_PLUGINS."$plug/$optfile"))
  				if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $optfile";
			}
		}
		if (isset($arbre['fonctions'])){
			foreach($arbre['fonctions'] as $optfile){
				$optfile = trim($optfile);
				if (!@is_readable(_DIR_PLUGINS."$plug/$optfile"))
  				if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $optfile";
			}
		}
		$fonctions = array();
		if (isset($arbre['fonctions']))
			$fonctions = $arbres['fonctions'];
	  $liste_methodes_reservees = array('__construct','__destruct','plugin','install','uninstall',strtolower($prefix));
		if (is_array($arbre['pipeline'])){
			foreach($arbre['pipeline'] as $pipe){
				$nom = trim(end($pipe['nom']));
				if (isset($pipe['action']))
					$action = trim(end($pipe['action']));
				else
					$action = $nom;
				// verif que la methode a un nom autorise
				if (in_array(strtolower($action),$liste_methodes_reservees)){
					if (!$silence)
						$arbre['erreur'][] = _T("erreur_plugin_nom_fonction_interdit")." : $action";
				}
				else{
					// verif que le fichier de def est bien present
					if (isset($pipe['inclure'])){
						$inclure = _DIR_PLUGINS."$plug/".end($pipe['inclure']);
						if (!@is_readable($inclure))
		  				if (!$silence)
								$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $inclure";
					}
				}
			}
		}
	}
}

// http://doc.spip.org/@verifie_include_plugins
function verifie_include_plugins() {
	if (_request('exec')!="admin_plugin"){
		if (@is_readable(_DIR_PLUGINS)) {
			include_spip('inc/headers');
			redirige_par_entete(generer_url_ecrire("admin_plugin"));
		}
		// plus de repertoire plugin existant, le menu n'existe plus
		// on fait une mise a jour silencieuse
		// generer les fichiers php precompiles
		// de chargement des plugins et des pipelines
		verif_plugin();
		spip_log("desactivation des plugins suite a suppression du repertoire");
	}
}
?>
