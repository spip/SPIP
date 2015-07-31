<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// http://doc.spip.org/@plugin_verifie_conformite
function plugins_verifie_conformite_dist($plug, &$arbre, $dir_plugins = _DIR_PLUGINS){
	$silence = false;
	if (isset($arbre['plugin']) AND is_array($arbre['plugin']))
		$arbre = end($arbre['plugin']); // derniere def plugin
	else{
		$arbre = array('erreur' => array(_T('erreur_plugin_tag_plugin_absent')." : $plug/plugin.xml"));
		$silence = true;
	}
	if (!is_array($arbre)) $arbre = array();
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
		if (strtoupper($prefix)=='SPIP'){
			$arbre['erreur'][] = _T('erreur_plugin_prefix_interdit');
		}
		if (isset($arbre['etat'])){
			$etat = trim(end($arbre['etat']));
			if (!in_array($etat,array('dev','experimental','test','stable')))
				$arbre['erreur'][] = _T('erreur_plugin_etat_inconnu')." : $etat";
		}
		if (isset($arbre['options'])){
			foreach($arbre['options'] as $optfile){
				$optfile = trim($optfile);
				if (!@is_readable($dir_plugins."$plug/$optfile"))
  				if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $optfile";
			}
		}
		if (isset($arbre['fonctions'])){
			foreach($arbre['fonctions'] as $optfile){
				$optfile = trim($optfile);
				if (!@is_readable($dir_plugins."$plug/$optfile"))
  				if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $optfile";
			}
		}
		$fonctions = array();
		if (isset($arbre['fonctions']))
			$fonctions = $arbre['fonctions'];
	  $liste_methodes_reservees = array('__construct','__destruct','plugin','install','uninstall',strtolower($prefix));

		$extraire_pipelines = charger_fonction("extraire_pipelines","plugins");
	  $arbre['pipeline'] = $extraire_pipelines($arbre);
		foreach($arbre['pipeline'] as $pipe){
			if (!isset($pipe['nom']))
				if (!$silence)
					$arbre['erreur'][] = _T("erreur_plugin_nom_pipeline_non_defini");
			if (isset($pipe['action'])) $action = $pipe['action'];
			else $action = $pipe['nom'];
			// verif que la methode a un nom autorise
			if (in_array(strtolower($action),$liste_methodes_reservees)){
				if (!$silence)
					$arbre['erreur'][] = _T("erreur_plugin_nom_fonction_interdit")." : $action";
			}
			if (isset($pipe['inclure'])) {
				$inclure = $dir_plugins."$plug/".$pipe['inclure'];
				if (!@is_readable($inclure))
	  			if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $inclure";
			}
		}
		$necessite = array();
		if (spip_xml_match_nodes(',^necessite,',$arbre,$needs)){
			foreach(array_keys($needs) as $tag){
				list($tag,$att) = spip_xml_decompose_tag($tag);
				$necessite[] = $att;
			}
		}
		$arbre['necessite'] = $necessite;
		$utilise = array();
		if (spip_xml_match_nodes(',^utilise,',$arbre,$uses)){
			foreach(array_keys($uses) as $tag){
				list($tag,$att) = spip_xml_decompose_tag($tag);
				$utilise[] = $att;
			}
		}
		$arbre['utilise'] = $utilise;
		$path = array();
		if (spip_xml_match_nodes(',^chemin,',$arbre,$paths)){
			foreach(array_keys($paths) as $tag){
				list($tag,$att) = spip_xml_decompose_tag($tag);
				$path[] = $att;
			}
		}
		else
			$path = array(array('dir'=>'')); // initialiser par defaut
		$arbre['path'] = $path;
		// exposer les noisettes
		if (isset($arbre['noisette'])){
			foreach($arbre['noisette'] as $k=>$nut){
				$nut = preg_replace(',[.]html$,uims','',trim($nut));
				$arbre['noisette'][$k] = $nut;
				if (!@is_readable($dir_plugins."$plug/$nut.html"))
  				if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $nut";
			}
		}
	}
}

?>