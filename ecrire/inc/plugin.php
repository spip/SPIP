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

// http://doc.spip.org/@liste_plugin_valides
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
// http://doc.spip.org/@liste_chemin_plugin_actifs
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
	$plugin_header_info = array();
	foreach($plugin_valides as $p=>$info){
		$plugin_header_info[]= $p.($info['version']?"(".$info['version'].")":"");
	}
	ecrire_meta('plugin_header',substr(strtolower(implode(",",$plugin_header_info)),0,900));

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

// mise a jour du meta en fonction de l'etat du repertoire
// penser a faire une maj du cache =>  ecrire_meta()
// en principe cela doit aussi initialiser la valeur a vide si elle n'esite pas
// risque de pb en php5 a cause du typage ou de null (verifier dans la doc php)
// http://doc.spip.org/@verif_plugin
function verif_plugin($pipe_recherche = false){
	$plugin_actifs = liste_chemin_plugin_actifs();
	$plugin_liste = liste_plugin_files();
	$plugin_new = array_intersect($plugin_actifs,$plugin_liste);
	ecrire_plugin_actifs($plugin_new,$pipe_recherche);
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
function installe_un_plugin($plug,$prefix,$install){
	// faire les include qui vont bien
	foreach($install as $file){
		$file = trim($file);
		@include_once(_DIR_PLUGINS."$plug/$file");
	}
	$prefix_install = $prefix."_install";
	if (!function_exists($prefix_install))
		return false;
	// voir si on a besoin de faire l'install
	$ok = $prefix_install('test');
	if (!$ok) {
		$prefix_install('install');
		$ok = $prefix_install('test');
	}
	return $ok; // le plugin est deja installe et ok
}

function installe_plugins(){
	$meta_plug_installes = array();
	$liste = liste_chemin_plugin_actifs();
	foreach($liste as $plug){
		$infos = plugin_get_infos($plug);
		if (isset($infos['install'])){
			$ok = installe_un_plugin($plug,$infos['prefix'],$infos['install']);
			// on peut enregistrer le chemin ici car il est mis a jour juste avant l'affichage
			// du panneau -> cela suivra si le plugin demenage
			if ($ok)
				$meta_plug_installes[] = $plug;
		}
	}
	ecrire_meta('plugin_installes',serialize($meta_plug_installes),'non');
	ecrire_metas();
}

// lecture du fichier de configuration d'un plugin
// http://doc.spip.org/@plugin_get_infos
function plugin_get_infos($plug){
	include_spip('inc/xml');
	static $infos=array();
	static $plugin_xml_cache=NULL;
	if (!isset($infos[$plug])){
		if ($plugin_xml_cache==NULL){
			$plugin_xml_cache = array();
			if (is_file($f=_DIR_TMP."plugin_xml.cache")){
				lire_fichier($f,$contenu);
				$plugin_xml_cache = unserialize($contenu);
				if (!is_array($plugin_xml_cache)) $plugin_xml_cache = array();
			}
		}
		$ret = array();
		if (isset($plugin_xml_cache[$plug])){
			$info = $plugin_xml_cache[$plug];
			if (isset($info['filemtime']) && (@filemtime(_DIR_PLUGINS."$plug/plugin.xml")<=$info['filemtime']))
				$ret = $info;
		}
		if (!count($ret)){
		  if ((@file_exists(_DIR_PLUGINS))&&(is_dir(_DIR_PLUGINS))){
				if (@file_exists(_DIR_PLUGINS."$plug/plugin.xml")) {
					$arbre = spip_xml_load($f = _DIR_PLUGINS."$plug/plugin.xml");
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
				if (isset($arbre['icon']))
					$ret['icon'] = spip_xml_aplatit($arbre['icon']);
				if (isset($arbre['description']))
					$ret['description'] = spip_xml_aplatit($arbre['description']);
				if (isset($arbre['lien']))
					$ret['lien'] = join(' ',$arbre['lien']);
				if (isset($arbre['etat']))
					$ret['etat'] = trim(end($arbre['etat']));
				if (isset($arbre['options']))
					$ret['options'] = $arbre['options'];
				if (isset($arbre['install']))
					$ret['install'] = $arbre['install'];
				if (isset($arbre['fonctions']))
					$ret['fonctions'] = $arbre['fonctions'];
				$ret['prefix'] = trim(array_pop($arbre['prefix']));
				if (isset($arbre['pipeline']))
					$ret['pipeline'] = $arbre['pipeline'];
				if (isset($arbre['erreur']))
					$ret['erreur'] = $arbre['erreur'];
			}
			if ($t=filemtime($f)){
				$ret['filemtime'] = $t;
				$plugin_xml_cache[$plug]=$ret;
				ecrire_fichier(_DIR_TMP."plugin_xml.cache",serialize($plugin_xml_cache));
			}
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

// http://doc.spip.org/@affiche_bloc_plugin
function affiche_bloc_plugin($plug_file, $info) {
	global $spip_lang_right;

	// puce d'etat du plugin
	// <etat>dev|experimental|test|stable</etat>
	$etat = 'dev';
	if (isset($info['etat']))
		$etat = $info['etat'];
	switch ($etat) {
		case 'experimental':
			$puce = 'puce-rouge.gif';
			$titre_etat = _T('plugin_etat_experimental');
			break;
		case 'test':
			$puce = 'puce-orange.gif';
			$titre_etat = _T('plugin_etat_test');
			break;
		case 'stable':
			$puce = 'puce-verte.gif';
			$titre_etat = _T('plugin_etat_stable');
			break;
		default:
			$puce = 'puce-poubelle.gif';
			$titre_etat = _T('plugin_etat_developpement');
			break;
	}

	$s .= "<div class='detailplugin verdana2'>";

	if (isset($info['icon']))
		$s .= "<img src='". _DIR_PLUGINS.$plug_file.'/'.trim($info['icon'])."' style='float:$spip_lang_right;' alt=' ' />\n";

	$s .= _T('version') .' '.  $info['version'] . " | <strong>$titre_etat</strong><br/>";
	$s .= _T('repertoire_plugins') .' '. $plug_file . "<br/>";

	if (isset($info['description']))
		$s .= "<hr/>" . propre($info['description']) . "<br/>";

	if (isset($info['auteur']))
		$s .= "<hr/>" . _T('auteur') .' '. propre($info['auteur']) . "<br/>";

	if (trim($info['lien'])) {
		if (preg_match(',^https?://,iS', $info['lien']))
			$s .= "<hr/>" . _T('info_url') .' '. propre("[->".$info['lien']."]") . "<br/>";
		else
			$s .= "<hr/>" . _T('info_url') .' '. propre($info['lien']) . "<br/>";
	}
	$s .= "</div>";

	return $s;
}

?>
