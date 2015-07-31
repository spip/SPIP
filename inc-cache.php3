<?

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CACHE")) return;
define("_INC_CACHE", "1");


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
			purger_repertoire($chemin, $age);
		}
	}
	closedir($handle);
}

?>