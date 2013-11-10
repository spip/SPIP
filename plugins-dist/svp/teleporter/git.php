<?php
/**
 * Gestion du téléporteur GIT. 
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Teleporteur
 */
 
/**
 * Téléporter et déballer un composant GIT
 * 
 * Déployer un repository GIT depuis une source et une révision données
 *
 * @param string $methode
 *     Méthode de téléportation : http|git|svn|...
 * @param string $source
 *     URL de la source GIT
 * @param string $dest
 *     Chemin du répertoire de destination
 * @param array $options
 *     Tableau d'options. Index possibles :
 *     - revision => 'ae89'
 *     - branche => 'xxx'
 * @return bool
 *     True si l'opération réussie, false sinon.
 */
function teleporter_git_dist($methode,$source,$dest,$options=array()){

	$branche = (isset($options['branche'])?$options['branche']:'master');
	if (is_dir($dest)){
		$infos = teleporter_git_read($dest,array('format'=>'assoc'));
		if (!$infos){
			spip_log("Suppression de $dest qui n'est pas au format GIT","teleport");
			$old = teleporter_nettoyer_vieille_version($dest);
		}
		elseif ($infos['source']!==$source) {
			spip_log("Suppression de $dest qui n'est pas sur le bon repository GIT","teleport");
			$old = teleporter_nettoyer_vieille_version($dest);
		}
		elseif (!isset($options['revision'])
		  OR $options['revision']!=$infos['revision']){
			$command = "git checkout ".escapeshellarg($branche);
			teleporter_git_exec($dest,$command);
			$command = "git pull --all";
			teleporter_git_exec($dest,$command);

			if (isset($options['revision'])){
				$command = "git checkout ".escapeshellarg($options['revision']);
				teleporter_git_exec($dest,$command);
			}
			else {
				$command = "git checkout ".escapeshellarg($branche);
				teleporter_git_exec($dest,$command);
			}
		}
		else {
			spip_log("$dest deja sur GIT $source Revision ".$options['revision'],"teleport");
		}
	}

	if (!is_dir($dest)){
		$command = "git clone ";
		$command .= escapeshellarg($source)." ".escapeshellarg($dest);
		teleporter_git_exec($dest,$command);
		if (isset($options['revision'])){
			$command = "git checkout ".escapeshellarg($options['revision']);
			teleporter_git_exec($dest,$command);
		}
	}

	// verifier que tout a bien marche
	$infos = teleporter_git_read($dest);
	if (!$infos) return false;

	return true;
}

/**
 * Lire l'état GIT du repository
 *
 * Retourne les informations GIT d'un répertoire donné
 * 
 * @param string $dest
 *     Chemin du répertoire à tester
 * @param array $options
 *     Tableau d'options
 * @return string|bool|array
 *     - Chaîne vide si pas un dépot GIT
 *     - False si erreur sur le dépot GIT
 *     - array sinon. Tableau avec 3 index :
 *     -- source : Source du dépot GIT à cette destination
 *     -- revision : Révision du dépot
 *     -- dest : Répertoire du dépot.
 */
function teleporter_git_read($dest, $options=array()) {

	if (!is_dir("$dest/.git"))
		return "";

	$curdir = getcwd();
	chdir($dest);

	exec("git remote -v",$output);
	$output = implode("\n",$output);

	$source = "";
	if (preg_match(",(\w+://.*)\s+\(fetch\)$,Uims",$output,$m))
		$source = $m[1];
	elseif (preg_match(",([^@\s]+@[^:\s]+:.*)\s+\(fetch\)$,Uims",$output,$m))
		$source = $m[1];

	if (!$source){
		chdir($curdir);
		return "";
	}

	$source = $m[1];

	exec("git log -1",$output);
	$hash = explode(" ",reset($output));
	$hash = end($hash);

	// [TODO] lire la branche ?
	chdir($curdir);

	if (preg_match(",[^0-9a-f],i",$hash))
		return false;

	return array(
		'source' => $source,
		'revision' => substr($hash,0,7),
		'dest' => $dest
	);
}


/**
 * Exécuter une commande GIT
 *
 * @param string $dest
 *     Répertoire de destination
 * @param string $command
 *     Commande à exécuter
 * @return void
 */
function teleporter_git_exec($dest,$command) {
	spip_log("{$dest}:{$command}","teleport");
	$curdir = getcwd();
	chdir($dest);
	exec($command);
	chdir($curdir);
}
