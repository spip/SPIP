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
function plugins_installer_dist(){
	$meta_plug_installes = array();

	// vider le cache des descriptions de tables avant installation
	$trouver_table = charger_fonction('trouver_table', 'base');
	$trouver_table('');

	$liste = liste_plugin_actifs();
	$get_infos = charger_fonction('get_infos','plugins');

	foreach ($liste as $prefix=>$resume) {
		$plug = $resume['dir'];
		$dir_type = $resume['dir_type'];		
		$infos = $get_infos($plug,false,constant($dir_type));
		if ($infos AND isset($infos['install'])){
			$ok = installe_un_plugin($plug,$infos,$dir_type);
			// on peut enregistrer le chemin ici car 
			// il est mis a jour juste avant l'affichage du panneau
			// -> cela suivra si le plugin demenage
			if ($ok)
				$meta_plug_installes[] = $plug;
			// vider le cache des descriptions de tables apres chaque installation
			$trouver_table('');
		}
	}
	ecrire_meta('plugin_installes',serialize($meta_plug_installes),'non');
	return true; // succes
}

// http://doc.spip.org/@spip_plugin_install
function spip_plugin_install($action, $infos){
	$prefix = $infos['prefix'];
	$version_cible = $infos['version_base'];
	if (isset($infos['meta']) AND (($table = $infos['meta']) !== 'meta'))
		$nom_meta = "base_version";
	else {  
		$nom_meta = $prefix."_base_version";
		$table = 'meta';
	}
	switch ($action){
		case 'test':
			return (isset($GLOBALS[$table])
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
function desinstalle_un_plugin($plug,$infos){
	// faire les include qui vont bien
	charge_instal_plugin($plug, $infos);
	$version_cible = isset($infos['version_base'])?$infos['version_base']:'';
	$prefix_install = $infos['prefix']."_install";
	if (function_exists($prefix_install)){
		$prefix_install('uninstall',$infos['prefix'],$version_cible);
		$ok = $prefix_install('test',$infos['prefix'],$version_cible);
		return $ok;
	}
	if (isset($infos['version_base'])){
		spip_plugin_install('uninstall',$infos);
		$ok = spip_plugin_install('test',$infos);
		return $ok;
	}

	return false;
}

function charge_instal_plugin($plug,$infos,$dir_plugins = '_DIR_PLUGINS'){
	// passer en chemin absolu si possible
	$dir = str_replace('_DIR_','_ROOT_',$dir_plugins);
	if (!defined($dir))
		$dir = $dir_plugins;
	
	// faire les include qui vont bien
	foreach($infos['install'] as $file){
		$file = trim($file);
		if (file_exists($f=constant($dir)."$plug/$file")){
			include_once($f);
		}
	}
}

function installe_un_plugin($plug,$infos,$dir_plugins = '_DIR_PLUGINS'){

	charge_instal_plugin($plug, $infos, $dir_plugins);

	$version_cible = isset($infos['version_base'])?$infos['version_base']:'';
	$prefix_install = $infos['prefix']."_install";
	// cas de la fonction install fournie par le plugin
	if (function_exists($prefix_install)){
		// voir si on a besoin de faire l'install
		$ok = $prefix_install('test',$infos['prefix'],$version_cible);
		if (!$ok) {
			echo "<div class='install-plugins'>";
			echo _T('plugin_titre_installation',array('plugin'=>typo($infos['nom'])))."<br />";
			$prefix_install('install',$infos['prefix'],$version_cible);
			$ok = $prefix_install('test',$infos['prefix'],$version_cible);
			// vider le cache des definitions des tables
			$trouver_table = charger_fonction('trouver_table','base');
			$trouver_table(false);
			echo "<span class='".($ok?'ok':'erreur')."'>".($ok ? _L("OK"):_L("Echec"))."</span>";
			echo "</div>";
		}
		return $ok; // le plugin est deja installe et ok
	}
	// pas de fonction instal fournie, mais une version_base dans le plugin
	// on utilise la fonction par defaut
	if (isset($infos['version_base'])){
		$ok = spip_plugin_install('test',$infos);
		if (!$ok) {
			echo "<div class='install-plugins'>";
			echo _T('plugin_titre_installation',array('plugin'=>typo($infos['nom'])))."<br />";
			spip_plugin_install('install',$infos);
			$ok = spip_plugin_install('test',$infos);
			// vider le cache des definitions des tables
			$trouver_table = charger_fonction('trouver_table','base');
			$trouver_table(false);
			echo "<span class='".($ok?'ok':'erreur')."'>".($ok ? _L("OK"):_L("Echec"))."</span>";
			echo "</div>";
		}
		return $ok; // le plugin est deja installe et ok
	}
	return false;
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
