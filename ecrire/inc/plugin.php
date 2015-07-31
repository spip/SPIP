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
function liste_plugin_files(){
	$plugin_files=array();
	foreach (preg_files(_DIR_PLUGINS, '/plugin[.]xml$') as $plugin) {
		$infos = plugin_get_infos($file);
		if (isset($infos['nom']) && isset($infos['version'])
		&& isset($infos['prefix']))
			$plugin_files[]=substr(dirname($plugin), strlen(_DIR_PLUGINS));
	}
	sort($plugin_files);
	return $plugin_files;
}

//  à utiliser pour initialiser ma variable globale $plugin
function liste_plugin_actifs(){
  $meta_plugin = lire_meta('plugin');
  if (strlen($meta_plugin)>0)
		return explode(",",lire_meta('plugin')); // mieux avec un unserialize ?
	else
		return array();
}

function ecrire_plugin_actifs($plugin,$pipe_recherche=false){
	static $liste_pipe_manquants=array();
	if (($pipe_recherche)&&(!in_array($pipe_recherche,$liste_pipe_manquants)))
		$liste_pipe_manquants[]=$pipe_recherche;
	
	$plugin_valides = array();
	if (is_array($plugin)){
		// charger les infos de plugin en memoire
		$infos = array();
		foreach ($plugin as $plug) {
			$infos[$plug] = plugin_get_infos($plug);
			if (!isset($infos[$plug]['erreur']))
				$plugin_valides[] = $plug;
			else
				unset($infos[$plug]);
		}
	}

	ecrire_meta('plugin',implode(",", $plugin_valides)); // mieux avec un serialize ?

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
				if ($charge=='options')
					$splugs .= '$GLOBALS[\'plugins\'][]=\''.$plug.'\';'."\n";
				if (isset($info[$charge])){
					foreach($info[$charge] as $file)
						$s .= "include_once _DIR_PLUGINS.'$plug/".trim($file)."';\n";
				}
			}
		}
		ecrire_fichier(_DIR_SESSIONS."charger_plugins_$charge.php",
			$start_file . $splugs . $s . $end_file);
	}

	if (is_array($infos)){
		// construire tableaux de pipelines et matrices
		// $GLOBALS['spip_pipeline']
		// $GLOBALS['spip_matrice']
		foreach($infos as $plug=>$info){
			$prefix = "";
			$prefix = trim(array_pop($info['prefix']))."_";
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

	pipeline_precompile();
}

// precompilsation des pipelines
function pipeline_precompile(){
	global $spip_pipeline, $spip_matrice;
	
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
				$s_inc .= 'include_once(';
				// si _DIR_PLUGINS est dans la chaine, on extrait la constante
				if (($p = strpos($file,'_DIR_PLUGINS'))!==FALSE){
					if ($p)
						$s_inc .= "'".substr($file,0,$p)."'.";
					$s_inc .= "_DIR_PLUGINS.";
					$s_inc .= "'".substr($file,$p+12)."'";
				}
				else
					$s_inc .= "'$file'";
				$s_inc .= ');'."\n";
			}
		}
		$content .= "// Pipeline $action \n";
		$content .= "function execute_pipeline_$action(\$val){\n";
		$content .= $s_inc;
		$content .= $s_call;
		$content .= "return \$val;\n}\n\n";
	}
	ecrire_fichier(_DIR_SESSIONS."charger_pipelines.php",
		$start_file . $content . $end_file);
}

// pas sur que ça serve juste au cas où
function liste_plugin_inactifs(){
	return array_diff (liste_plugin_files(),liste_plugin_actifs());
}

// mise à jour du meta en fonction de l'état du répertoire
// penser à faire une maj du cache =>  ecrire_meta()
// en principe cela doit aussi initialiser la valeur à vide si elle n'esite pas 
// risque de pb en php5 à cause du typage ou de null (vérifier dans la doc php)
function verif_plugin($pipe_recherche = false){
	$plugin_actifs = liste_plugin_actifs();
	$plugin_liste = liste_plugin_files();
	$plugin_new = array_intersect($plugin_actifs,$plugin_liste);
	ecrire_plugin_actifs($plugin_new,$pipe_recherche);
	ecrire_metas();
}

// mise à jour des données si envoi via formulaire
function enregistre_modif_plugin(){
  // recuperer les plugins dans l'ordre des $_POST
  $test = array();
	foreach(liste_plugin_files() as $file){
	  $test["statusplug_$file"] = $file;
	}
	$plugin=array();
	if (!isset($_POST['desactive_tous'])){
		foreach($_POST as $choix=>$val){
			if (isset($test[$choix])&&$val=='O')
				$plugin[]=$test[$choix];
		}
	}
	ecrire_plugin_actifs($plugin);
	ecrire_metas();
	//echo "mise à jour ok";
}

function ordonne_plugin(){
	$liste = liste_plugin_actifs();
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

function parse_plugin_xml($texte){
	$out = array();
  // enlever les commentaires
  $texte = preg_replace(',<!--(.*?)-->,is','',$texte);
  $texte = preg_replace(',<\?(.*?)\?>,is','',$texte);
  $txt = $texte;

	// tant qu'il y a des tags
	while(preg_match("{<([^>]*?)>}s",$txt)){
		// tag ouvrant
		$chars = preg_split("{<([^>]*?)>}s",$txt,2,PREG_SPLIT_DELIM_CAPTURE);
	
		// $before doit etre vide ou des espaces uniquements!
		$before = trim($chars[0]);

		if (strlen($before)>0)
			return $texte; // before non vide, donc on est dans du texte
	
		$tag = $chars[1];
		$txt = $chars[2];
	
		// tag fermant
		$chars = preg_split("{(</".preg_quote($tag).">)}s",$txt,2,PREG_SPLIT_DELIM_CAPTURE);
		if (!isset($chars[1])) { // tag fermant manquant
			$out[$tag][]="erreur : tag fermant $tag manquant::$txt"; 
			return $out;
		}
		$content = $chars[0];
		$txt = trim($chars[2]);
		$out[$tag][]=parse_plugin_xml($content);
	}
	if (count($out)&&(strlen($txt)==0))
		return $out;
	else
		return $texte;
}

function applatit_arbre($arbre,$separateur = " "){
	$s = "";
	foreach($arbre as $tag=>$feuille){
		if (is_array($feuille)){
			if ($tag!==intval($tag))
				$s.="<$tag>".applatit_arbre($feuille)."</$tag>";
			else
				$s.=applatit_arbre($feuille);
			$s .= $separateur;
		}				
		else
			$s.="$feuille$separateur";
	}
	return $s;
}
function chaines_lang($texte){
	// TODO : prendre en charge le fichier langue specifique du plugin
	// meme si pas encore charge
	$regexp = "|<:([^>]*):>|";
	if (preg_match_all($regexp, $texte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs)
		$texte = str_replace($regs[0],
		_T($regs[1]), $texte);
	return $texte;
}

// lecture du fichier de configuration d'un plugin
function plugin_get_infos($plug){
  $ret = array();
  if ((@file_exists(_DIR_PLUGINS))&&(is_dir(_DIR_PLUGINS))){
		if (@file_exists(_DIR_PLUGINS."$plug/plugin.xml")) {
			lire_fichier(_DIR_PLUGINS."$plug/plugin.xml", $texte);
			$arbre = parse_plugin_xml($texte);
			if (!isset($arbre['plugin'])&&is_array($arbre['plugin']))
				$arbre = array('erreur' => array(_T('erreur_plugin_fichier_def_incorrect')." : $plug/plugin.xml"));
		}
		else {
			// pour arriver ici on l'a vraiment cherche...
			$arbre = array('erreur' => array(_T('erreur_plugin_fichier_def_absent')." : $plug/plugin.xml"));
		}

		plugin_verifie_conformite($plug,$arbre);
		
		$ret['nom'] = applatit_arbre($arbre['nom']);
		$ret['version'] = trim(end($arbre['version']));
		if (isset($arbre['auteur']))
			$ret['auteur'] = applatit_arbre($arbre['auteur']);
		if (isset($arbre['description']))
			$ret['description'] = chaines_lang(applatit_arbre($arbre['description']));
		if (isset($arbre['lien']))
			$ret['lien'] = join(' ',$arbre['lien']);
		if (isset($arbre['etat']))
			$ret['etat'] = trim(end($arbre['etat']));
		if (isset($arbre['options']))
			$ret['options'] = $arbre['options'];
		if (isset($arbre['fonctions']))
			$ret['fonctions'] = $arbre['fonctions'];
		$ret['prefix'] = $arbre['prefix'];
		if (isset($arbre['pipeline']))
			$ret['pipeline'] = $arbre['pipeline'];
		if (isset($arbre['erreur']))
			$ret['erreur'] = $arbre['erreur'];
	}
	return $ret;
}

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
			if (!preg_match(',^(dev|experimental|test|stable)$,',$etat))
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

?>
