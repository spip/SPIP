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

// http://doc.spip.org/@installe_plugins
function plugins_installer_dist($liste){

	$meta_plug_installes = $new = array();
	
	// vider le cache des descriptions de tables a chaque installation
	$trouver_table = charger_fonction('trouver_table', 'base');
	$trouver_table('');

	foreach ($liste as $prefix=>$resume) {
		$plug = $resume['dir'];
		$dir_type = $resume['dir_type'];		
		$infos = charge_instal_plugin($plug, $dir_type); 
		if ($infos) {
			$version = isset($infos['version_base'])?$infos['version_base']:'';
			$f = $infos['prefix']."_install";
			$arg2 = $infos ;
			if (!function_exists($f))
			  $f = isset($infos['version_base']) ? 'spip_plugin_install' : '';
			else $arg2 = $prefix; // stupide: info deja dans le nom
			$ok = !$f ? true : $f('test', $arg2, $version);
			if (!$ok) {
				$f('install',$arg2,$version);
				$new[$infos['nom']] = $f('test',$arg2,$version);
				$trouver_table('');
			}
			// on peut enregistrer le chemin ici car 
			// il est mis a jour juste avant l'affichage du panneau
			// -> cela suivra si le plugin demenage
			if ($ok)
				$meta_plug_installes[] = $plug;
		}
	}
	ecrire_meta('plugin_installes',serialize($meta_plug_installes),'non');
	return $new;
}

// http://doc.spip.org/@spip_plugin_install
function spip_plugin_install($action, $infos, $version_cible){
	$prefix = $infos['prefix'];
	if (isset($infos['meta']) AND (($table = $infos['meta']) !== 'meta'))
		$nom_meta = "base_version";
	else {  
		$nom_meta = $prefix."_base_version";
		$table = 'meta';
	}
	switch ($action){
		case 'test':
			return  (isset($GLOBALS[$table])
			AND isset($GLOBALS[$table][$nom_meta]) 
			AND spip_version_compare($GLOBALS[$table][$nom_meta],$version_cible,'>='));
			break;
		case 'install':
			if (function_exists($upgrade = $prefix."_upgrade"))
				$upgrade($nom_meta, $version_cible, $table);
			break;
		case 'uninstall':
		  if (function_exists($vider_tables = $prefix."_vider_tables"))
				$vider_tables($nom_meta, $table);
			break;
	}
}

// http://doc.spip.org/@desinstalle_un_plugin
function desinstalle_un_plugin($plug){
	$infos = charge_instal_plugin($plug);
	$erreur = 'erreur_plugin_desinstalation_echouee';
	if ($infos) {
		$prefix_install = $infos['prefix']."_install";
		$ok = true;
		if (function_exists($prefix_install)){
			$prefix_install('uninstall',$infos['prefix'],$infos['version_base']);
			$ok = !$prefix_install('test',$infos['prefix'],$infos['version_base']);
		}
		if (isset($infos['version_base'])) {
		  spip_plugin_install('uninstall',$infos, $infos['version_base']);
		  $ok = spip_plugin_install('test',$infos, $infos['version_base']);
		}
		// desactiver si il a bien ete desinstalle
		if (!$ok) {
			include_spip('inc/plugin');
			ecrire_plugin_actifs(array($plug),false,'enleve');
			$erreur = '';
		}
	}
	return $erreur;
}

// charge et retourne les infos
// et inclut les fichiers necessaires a l'install/desinstal
// en passant en chemin absolu si possible

function charge_instal_plugin($plug, $dir_type='_DIR_PLUGINS'){
	$get_infos = charger_fonction('get_infos','plugins');
	$infos = $get_infos($plug);
	if (!$infos['install']) return false;
	$dir = str_replace('_DIR_','_ROOT_',$dir_type);
	if (!defined($dir)) $dir = $dir_type;
	$dir = constant($dir);
	foreach($infos['install'] as $file) {
		$file = $dir . $plug . "/" . trim($file);
		if (file_exists($file)){
			include_once($file);
		}
	}
	return $infos;
}

function spip_version_compare($v1,$v2,$op){
	$v1 = strtolower(preg_replace(',([0-9])[\s-.]?(dev|alpha|a|beta|b|rc|pl|p),i','\\1.\\2',$v1));
	$v2 = strtolower(preg_replace(',([0-9])[\s-.]?(dev|alpha|a|beta|b|rc|pl|p),i','\\1.\\2',$v2));
	$v1 = str_replace('rc','RC',$v1); // certaines versions de PHP ne comprennent RC qu'en majuscule
	$v2 = str_replace('rc','RC',$v2); // certaines versions de PHP ne comprennent RC qu'en majuscule
	$v1 = explode('.',$v1);
	$v2 = explode('.',$v2);
	while (count($v1)<count($v2))
		$v1[] = '0';
	while (count($v2)<count($v1))
		$v2[] = '0';
	$v1 = implode('.',$v1);
	$v2 = implode('.',$v2);

	return version_compare($v1, $v2,$op);
}

//  A utiliser pour initialiser ma variable globale $plugin
// http://doc.spip.org/@liste_plugin_actifs
function liste_plugin_actifs(){
  $meta_plugin = isset($GLOBALS['meta']['plugin'])?$GLOBALS['meta']['plugin']:'';
  if (strlen($meta_plugin)>0){
  	if (is_array($t=unserialize($meta_plugin)))
  		return $t;
  	else{ // compatibilite pre 1.9.2, mettre a jour la meta
	  // Ca aurait du etre fait par le tableau des mises a jour
		spip_log("MAJ meta plugin vieille version : $meta_plugin","plugin");
  		$t = explode(",",$meta_plugin);
  		list($liste,) = liste_plugin_valides($t);
			ecrire_meta('plugin',serialize($liste));
			return $liste;
  	}
  }
	else
		return array();
}

?>
