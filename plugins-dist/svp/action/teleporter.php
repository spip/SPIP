<?php
/**
 * Gestion de l'action teleporter
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Actions
 */
 

/**
 * Téléporter et déballer un composant
 * 
 * @param string $methode
 *   http|git|svn|...
 * @param string $source
 *   URL source du composant
 * @param string $dest
 *   Chemin du répertoire où déballer le composant. Inclus le dernier segment
 * @param array $options
 *   revision => ''
 *   --ignore-externals
 * @return bool|string
 *   String : texte d'une erreur
 *   true si l'opération est correctement réalisée
 */
function action_teleporter_composant_dist($methode,$source,$dest,$options=array()){
	# Si definie a '', le chargeur est interdit ; mais on n'aurait de toutes
	# facons jamais pu venir ici avec toutes les securisations faites :^)

	if (!preg_match(','.substr(_DIR_LIB,0,-1).',',$dest) && !_DIR_PLUGINS_AUTO) 
		die('Vous ne pouvez pas télécharger, absence de _DIR_PLUGINS_AUTO');

	// verifier que la methode est connue
	if (!$teleporter =  charger_fonction($methode,"teleporter",true)){
		spip_log("Methode $methode inconnue pour teleporter $source vers $dest","teleport"._LOG_ERREUR);
		return _T('svp:erreur_teleporter_methode_inconue',array('methode' => $methode));
	}

	if (!$dest = teleporter_verifier_destination($d = $dest)){
		spip_log("Rerpertoire $d non accessible pour teleporter $source vers $d","teleport"._LOG_ERREUR);
		return _T('svp:erreur_teleporter_destination_erreur',array('dir' => $d));
		#$texte = "<p>"._T('plugin_erreur_droit1',array('dest'=>$dest))."</p>"
		#  . "<p>"._T('plugin_erreur_droit2').aide('install0')."</p>";
	}

	# destination temporaire des fichiers si besoin
	$options['dir_tmp'] = sous_repertoire(_DIR_CACHE, 'chargeur');

	return $teleporter($methode,$source,$dest,$options);
}


/**
 * Vérifier et préparer l'arborescence jusqu'au répertoire parent
 *
 * @param string $dest
 * @return bool|string
 *     false en cas d'échec
 *     Chemin du répertoire sinon
 */
function teleporter_verifier_destination($dest){
	$dest = rtrim($dest,"/");
	$final = basename($dest);
	$base = dirname($dest);
	$create = array();
	// on cree tout le chemin jusqu'a dest non inclus
	while (!is_dir($base)){
		$create[] = basename($base);
		$base = dirname($base);
	}
	while (count($create)){
		if (!is_writable($base))
			return false;
		$base = sous_repertoire($base,array_pop($create));
		if (!$base)
			return false;
	}

	if (!is_writable($base))
		return false;

	return $base."/$final";
}

/**
 * Déplace un répertoire pour libérer l'emplacement.
 *
 * Si le répertoire donné existe, le déplace dans un répertoire de backup.
 * Si ce backup existe déjà, il est supprimé auparavant.
 * Retourne le nouveau chemin du répertoire.
 * 
 * @param string $dest
 *     Chemin du répertoire à déplacer
 * @return string
 *     Nouveau chemin du répertoire s'il existait,
 *     Chaîne vide sinon
**/
function teleporter_nettoyer_vieille_version($dest){
	$old = "";
	if (is_dir($dest)){
		$dir = dirname($dest);
		$base = basename($dest);
		$old="$dir/.$base.bck";
		if (is_dir($old))
			supprimer_repertoire($old);
		rename($dest,$old);
	}
	return $old;
}
