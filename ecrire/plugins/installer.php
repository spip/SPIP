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

/**
 * Installe ou retire un plugin
 * 
 * Fonction surchargeable permettant d'installer ou retirer un plugin
 * en incluant les fichiers associes et en lancant les fonctions specifiques
 * 1. d'abord sur l'argument 'test',
 * 2. ensuite sur l'action demandee si le test repond False
 * 3. enfin sur l'argument 'test' a nouveau.
 * l'index install_test du tableau resultat est un tableau forme:
 *  - du resultat 3 
 *  - des Echo de l'etape 2
 *
 * @param string $plug, nom du plugin
 * @param string $action, nom de l'action (install|uninstall)
 * @param string $dir_type, repertoire du plugin
 * 
 * @return array|boolean True si deja installe, le tableau de get_infos sinon
 *
 */
function plugins_installer_dist($plug, $action, $dir_type='_DIR_PLUGINS')
{
	$get_infos = charger_fonction('get_infos','plugins');
	$infos = $get_infos($plug, false, constant($dir_type));
	if (!isset($infos['install']) OR !$infos['install']) return false;
	// passer en chemin absolu si possible, c'est plus efficace
	$dir = str_replace('_DIR_','_ROOT_',$dir_type);
	if (!defined($dir)) $dir = $dir_type;
	$dir = constant($dir);
	foreach($infos['install'] as $file) {
		$file = $dir . $plug . "/" . trim($file);
		if (file_exists($file)){
			include_once($file);
		}
	}
	$version = isset($infos['schema'])?$infos['schema']:'';
	$arg = $infos ;
	$f = $infos['prefix']."_install";
	if (!function_exists($f))
		$f = isset($infos['schema']) ? 'spip_plugin_install' : '';
	else
		$arg = $infos['prefix']; // stupide: info deja dans le nom

	if (!$f) {
		// installation sans operation particuliere
		$infos['install_test'] = array(true, '');
		return $infos; 
	}
	$test = $f('test', $arg, $version);
	if ($action == 'uninstall') $test = !$test;
	// Si deja fait, on ne dit rien
	if ($test)  return true;
	// Si install et que l'on a la meta d'installation, c'est un upgrade
	if($action == 'install' && !is_null(lire_meta($infos['prefix'].'_base_version')))
		$infos['upgrade'] = true;

	// executer l'installation ou l'inverse
	// et renvoyer la trace (mais il faudrait passer en AJAX plutot)
	ob_start(); 
	$f($action, $arg, $version);
	$aff = ob_get_contents(); 
	ob_end_clean();
	// vider le cache des descriptions de tables a chaque (de)installation
	$trouver_table = charger_fonction('trouver_table', 'base');
	$trouver_table('');
	$infos['install_test'] = array($f('test', $arg, $version), $aff);
	return $infos;
}

// Fonction par defaut pour install/desinstall

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


/**
 * Compare 2 numeros de version entre elles.
 * 
 * Cette fonction est identique (arguments et retours) a la fonction PHP
 * version_compare() qu'elle appelle. Cependant, cette fonction reformate
 * les numeros de versions pour ameliorer certains usages dans SPIP ou bugs
 * dans PHP. On permet ainsi de comparer 3.0.4 à 3.0.* par exemple.
 * 
 * @param string $v1
 * 		Numero de version servant de base a la comparaison.
 * 		Ce numero ne peut pas comporter d'etoile.
 * @param string $v2
 * 		Numero de version a comparer.
 * 		Il peut posseder des etoiles tel que 3.0.*
 * @param string $op
 * 		Un operateur eventuel (<, >, <=, >=, =, == ...)
 * @return int|bool
 * 		Sans operateur : int. -1 pour inferieur, 0 pour egal, 1 pour superieur
 * 		Avec operateur : bool. 
**/
function spip_version_compare($v1,$v2,$op=null){
	$v1 = strtolower(preg_replace(',([0-9])[\s-.]?(dev|alpha|a|beta|b|rc|pl|p),i','\\1.\\2',$v1));
	$v2 = strtolower(preg_replace(',([0-9])[\s-.]?(dev|alpha|a|beta|b|rc|pl|p),i','\\1.\\2',$v2));
	$v1 = str_replace('rc','RC',$v1); // certaines versions de PHP ne comprennent RC qu'en majuscule
	$v2 = str_replace('rc','RC',$v2); // certaines versions de PHP ne comprennent RC qu'en majuscule

	$v1 = explode('.',$v1);
	$v2 = explode('.',$v2);
	// $v1 est toujours une version, donc sans etoile
	while (count($v1)<count($v2))
		$v1[] = '0';

	// $v2 peut etre une borne, donc accepte l'etoile
	$etoile = false;
	foreach ($v1 as $k => $v) {
		if (!isset($v2[$k]))
			$v2[] = ($etoile AND (is_numeric($v) OR $v=='pl' OR $v=='p')) ? $v : '0';
		else if ($v2[$k] == '*') {
			$etoile = true;
			$v2[$k] = $v;
		}
	}
	$v1 = implode('.',$v1);
	$v2 = implode('.',$v2);

	return $op?version_compare($v1, $v2,$op):version_compare($v1, $v2);
}

// Cette fonction retourne la meta "plugin" deserialisee
// mais en fait une mise a jour qui aurait du intervenir au chgt de version
// http://doc.spip.org/@liste_plugin_actifs
function liste_plugin_actifs(){
	$liste = isset($GLOBALS['meta']['plugin'])?$GLOBALS['meta']['plugin']:'';
	if (!$liste) return array();
  	if (!is_array($liste=unserialize($liste))) {
		// compatibilite pre 1.9.2, mettre a jour la meta
		spip_log("MAJ meta plugin vieille version : $liste","plugin");
		$new = true;
		list(, $liste) = liste_plugin_valides(explode(",",$liste));
	} else {
		$new = false;
		// compat au moment d'une migration depuis version anterieure
		// si pas de dir_type, alors c'est _DIR_PLUGINS
		foreach ($liste as $prefix=>$infos) {
			if (!isset($infos['dir_type'])) {
				$liste[$prefix]['dir_type'] = "_DIR_PLUGINS";
				$new = true;
			}
		}
	}
	if ($new) ecrire_meta('plugin',serialize($liste));
	return $liste;
}

?>
