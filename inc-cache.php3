<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CACHE")) return;
define("_INC_CACHE", "1");


//
// Calcul du nom du fichier cache
//

function generer_nom_fichier_cache($fichier_requete) {
	$md_cache = md5($fichier_requete);

	$fichier_cache = ereg_replace('^/+', '', $fichier_requete);
	$fichier_cache = ereg_replace('\.[a-zA-Z0-9]*', '', $fichier_cache);
	$fichier_cache = ereg_replace('&[^&]+=([^&]+)', '&\1', $fichier_cache);
	$fichier_cache = rawurlencode(strtr($fichier_cache, '/&-', '--_'));
	if (strlen($fichier_cache) > 24)
		$fichier_cache = substr(ereg_replace('([a-zA-Z]{1,3})[^-]*-', '\1-', $fichier_cache), -24);

	if (!$fichier_cache)
		$fichier_cache = 'INDEX-';
	$fichier_cache .= '.'.substr($md_cache, 1, 6);

	$subdir_cache = substr($md_cache, 0, 1);

	if (creer_repertoire('CACHE', $subdir_cache))
		$fichier_cache = "$subdir_cache/$fichier_cache";

	return $fichier_cache;
}


//
// Doit-on recalculer le cache ?
//

function utiliser_cache($chemin_cache, $delais) {
	global $HTTP_SERVER_VARS, $HTTP_POST_VARS;
	global $lastmodified;

	// A priori cache
	$use_cache = true;

	// Existence du fichier
	if (!file_exists($chemin_cache)) {
		if (file_exists($chemin_cache.'.NEW')) {
			// Deuxieme acces : le fichier est marque comme utilise
			@rename($chemin_cache.'.NEW', $chemin_cache);
			clearstatcache();
		}
		else {
			// Double verification (cas renommage entre les deux file_exists)
			clearstatcache();
			$use_cache = file_exists($chemin_cache);
		}
	}

	// Date de creation du fichier
	if ($use_cache) {
		$t = filemtime($chemin_cache);
		$ledelais = time() - $t;
		$use_cache &= ($ledelais < $delais AND $ledelais >= 0);
		// Inclusions multiples : derniere modification
		if ($lastmodified < $t) $lastmodified = $t;
	}

	// recalcul obligatoire
	$use_cache &= ($GLOBALS['recalcul'] != 'oui');
	$use_cache &= empty($HTTP_POST_VARS);

	// ne jamais recalculer pour les moteurs de recherche, proxies...
	if ($HTTP_SERVER_VARS['REQUEST_METHOD'] == 'HEAD')
		$use_cache = true;

	// si pas de connexion, cache obligatoire
	if (!$use_cache) {
		include_ecrire("inc_connect.php3");
		if (!$GLOBALS['db_ok']) $use_cache = true;
	}

	return $use_cache;
}


function ecrire_fichier_cache($fichier, $contenu) {
	global $flag_flock;

	$fichier_tmp = $fichier.'_tmp';
	$f = fopen($fichier_tmp, "wb");
	if (!$f) return;

	// Essayer de poser un verrou
	if ($flag_flock) {
		@flock($f, 6, $r);
		if ($r) return;
	}
	$r = fwrite($f, $contenu);
	if ($flag_flock) @flock($f, 3);
	if ($r != strlen($contenu)) return;
	if (!fclose($f)) return;

	@unlink($fichier);
	$fichier = $fichier.'.NEW';
	@unlink($fichier);
	rename($fichier_tmp, $fichier);
	if ($GLOBALS['flag_apc']) apc_rm($fichier);
	return $fichier;
}


//
// Retourne true si le sous-repertoire peut etre cree, false sinon
//

function creer_repertoire($base, $subdir) {
	if (file_exists("$base/.plat")) return false;
	$path = $base.'/'.$subdir;
	if (file_exists($path)) return true;

	@mkdir($path, 0777);
	@chmod($path, 0777);
	$ok = false;
	if ($f = @fopen("$path/.test", "w")) {
		@fputs($f, '<'.'?php $ok = true; ?'.'>');
		@fclose($f);
		include("$path/.test");
	}
	if (!$ok) {
		$f = @fopen("$base/.plat", "w");
		if ($f)
			fclose($f);
		else {
			@header("Location: spip_test_dirs.php3");
			exit;
		}
	}
	return $ok;
}


function purger_repertoire($dir, $age, $regexp = '') {
	$handle = opendir($dir);
	$t = time();
	while (($fichier = readdir($handle)) != '') {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') continue;
		if ($regexp AND !ereg($regexp, $fichier)) continue;
		$chemin = "$dir/$fichier";
		if (is_file($chemin)) {
			if (($t - filemtime($chemin)) > $age OR ereg('\.NEW$', $fichier)) {
				@unlink($chemin);
				$query = "DELETE FROM spip_forum_cache WHERE fichier='$fichier'";
				spip_query($query);
			}
		}
		else if (is_dir($chemin)) {
			if ($fichier != 'CVS') purger_repertoire($chemin, $age);
		}
	}
	closedir($handle);
}

?>
