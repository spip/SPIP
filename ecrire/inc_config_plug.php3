<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CONFIG_PLUG")) return;
define("_ECRIRE_INC_CONFIG_PLUG", "1");



function fichiers_plugins($dir) {
	$fichiers = array();
	$d = opendir($dir);
	
	while ($f = readdir($d)) {
		if (is_file("$dir/$f") AND $f != 'remove.txt') {
			if (ereg("^plug", $f)) {
				$fichiers[] = "$dir/$f";
			}
		} else if (is_dir("$dir/$f") AND $f != '.' AND $f != '..') {
			$fichiers_dir = fichiers_plugins("$dir/$f");
			while (list(,$f2) = each ($fichiers_dir))
				$fichiers[] = $f2;
		}
	}	
	
	closedir($d);
	
	sort($fichiers);
	return $fichiers;
}

function installer_plugins () {
	$fichiers = array();
	$fichiers = fichiers_plugins("plugins");

	$plugs = "<"."?php\n\n";
	$plugs .= "if(defined('_ECRIRE_INC_PLUGINS')) return;\n";
	$plugs .= "define('_ECRIRE_INC_PLUGINS', '1');\n\n";
	while (list(, $nom_fichier) = each($fichiers)) {
		$plugs .= "include_plug('$nom_fichier');\n";
	}
	$plugs .= "\n?".">";


	if ($f = @fopen('inc_plugins.php3', 'w')) {
		@fwrite($f, $plugs);
		@fclose($f);
	}
}



?>