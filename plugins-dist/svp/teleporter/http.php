<?php
/**
 * Gestion du téléporteur HTTP. 
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Teleporteur
 */
 
/**
 * Téléporter et déballer un composant HTTP
 * 
 * @param string $methode
 *   Méthode de téléportation : http|git|svn|...
 * @param string $source
 *     URL de la source HTTP
 * @param string $dest
 *     Chemin du répertoire de destination
 * @param array $options
 *     Tableau d'options.
 *     Doit au moins avoir l'index :
 *     - dir_tmp : Indique un répertoire temporaire pour stocker
 *       les fichiers. Par exemple défini avec : sous_repertoire(_DIR_CACHE, 'chargeur');
 * @return bool|string
 *     Texte d'erreur si erreur,
 *     True si l'opération réussie.
 */
function teleporter_http_dist($methode,$source,$dest,$options=array()){

	$tmp = $options['dir_tmp'];
	# on ne se contente pas du basename qui peut etre un simple v1
	# exemple de l'url http://nodeload.github.com/kbjr/Git.php/zipball/v0.1.1-rc
	$fichier = $tmp . (basename($dest)."-".substr(md5($source),0,8)."-".basename($source));

	$res = teleporter_http_recuperer_source($source,$fichier);
	if (!is_array($res))
		return $res;

	list($fichier,$extension) = $res;
	if (!$deballe = charger_fonction("http_deballe_".preg_replace(",\W,","_",$extension),"teleporter",true))
		return _T('svp:erreur_teleporter_format_archive_non_supporte',array('extension' => $extension));

	$old = teleporter_nettoyer_vieille_version($dest);

	if (!$target = $deballe($fichier, $dest, $tmp)){
		// retablir l'ancien sinon
		if ($old)
			rename($old,$dest);
		return _T('svp:erreur_teleporter_echec_deballage_archive',array('fichier' => $fichier));
	}

	return true;
}

/**
 * Récupérer la source et détecter son extension
 *
 * @param string $source
 *     URL de la source HTTP
 * @param string $dest_tmp
 *     Répertoire de destination
 * @return array|string
 *     - Texte d'erreur si une erreur survient,
 *     - Liste sinon (répertoire de destination temporaire, extension du fichier source)
 */
function teleporter_http_recuperer_source($source,$dest_tmp){

	# securite : ici on repart toujours d'une source neuve
	if (file_exists($dest_tmp))
		spip_unlink($dest_tmp);

	$extension = "";

	# si on ne dispose pas encore du fichier
	# verifier que le zip en est bien un (sans se fier a son extension)
	#	en chargeant son entete car l'url initiale peut etre une simple
	# redirection et ne pas comporter d'extension .zip
	include_spip('inc/distant');
	$head = recuperer_page($source, false, true, 0);

	if (preg_match(",^Content-Type:\s*?(.*)$,Uims",$head,$m)
		AND include_spip('base/typedoc')){
		$mime = $m[1];
		// passer du mime a l'extension !
		if ($e = array_search($mime,$GLOBALS['tables_mime']))
			$extension = $e;
	}

	if (!$extension
	  // cas des extensions incertaines car mime-type ambigu
	  OR in_array($extension,array('bin','gz'))){
		if (preg_match(",^Content-Disposition:\s*attachment;\s*filename=(.*)['\"]?$,Uims",$head,$m)
		  AND $e=teleporter_http_extension($m[1])){
			$extension = $e;
		}
		// au cas ou, si le content-type n'est pas la
		// mais que l'extension est explicite
		else{
			$extension = teleporter_http_extension($source);
		}
	}

	# format de fichier inconnu
	if (!$extension) {
		spip_log("Type de fichier inconnu pour la source $source","teleport"._LOG_ERREUR);
		return _T('svp:erreur_teleporter_type_fichier_inconnu',array('source' => $source));
	}

	$dest_tmp = preg_replace(";\.[\w]{2,3}$;i","",$dest_tmp).".$extension";

	include_spip('inc/distant');
	$dest_tmp = copie_locale($source,'force',$dest_tmp);
	if (!$dest_tmp
	  OR !file_exists($dest_tmp = _DIR_RACINE . $dest_tmp)) {
		spip_log("Chargement impossible de la source $source","teleport"._LOG_ERREUR);
		return _T('svp:erreur_teleporter_chargement_source_impossible',array('source' => $source));
	}

	return array($dest_tmp,$extension);
}

/**
 * Retrouve l'extension d'un fichier 
 *
 * @note
 *     Retourne tgz pour un fichier .tar.gz
 * 
 * @param string $file
 *     Chemin du fichier
 * @return string
 *     Extension du fichier, sinon vide
**/
function teleporter_http_extension($file){
	$e = pathinfo($file, PATHINFO_EXTENSION);

	// cas particuliers : redresser .tar.gz
	if ($e=='gz'
		AND preg_match(',tar\.gz,i',$file))
		$e = 'tgz';

	return $e;
}

/**
 * Cherche la plus longue racine commune à tous les fichiers
 *
 * @param array $list
 *     Liste de chemin de fichiers
 * @return string
 *     Chemin commun entre tous les fichiers
**/
function http_deballe_recherche_racine($list){
	// on cherche la plus longue racine commune a tous les fichiers
	// pour l'enlever au deballage
	$max_n = 999999;
	$paths = array();
	foreach($list as $n) {
		$p = array();
		foreach(explode('/', $n['filename']) as $n => $x) {
			if ($n>$max_n)
				continue;
			$sofar = join('/',$p);
			$paths[$n][$sofar]++;
			$p[] = $x;
		}
		$max_n = min($n,$max_n);
	}

	$total = $paths[0][''];
	$i = 0;
	while (isset($paths[$i])
	AND count($paths[$i]) <= 1
	AND array_values($paths[$i]) == array($total))
		$i++;

	$racine = '';
	if ($i){
		$racine = array_keys($paths[$i-1]);
		$racine = array_pop($racine).'/';
	}

	return $racine;
}
