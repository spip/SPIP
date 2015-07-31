<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CACHE")) return;
define("_INC_CACHE", "1");


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
		@fputs($f, '<?php $ok = true; ?'.'>');
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
			if (($t - filemtime($chemin)) > $age) {
				@unlink($chemin);
				$query = "DELETE FROM spip_forum_cache WHERE fichier='$fichier'";
				mysql_query($query);
			}
		}
		else if (is_dir($chemin)) {
			if ($fichier != 'CVS') purger_repertoire($chemin, $age);
		}
	}
	closedir($handle);
}

?>