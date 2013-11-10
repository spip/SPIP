<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

# memoization minimale (preferer le plugin memoization)
function cache_get($key) {
	return @unserialize(file_get_contents(_DIR_CACHE."wheels/".$key.".txt"));
}
function cache_set($key, $value) {
	$dir = sous_repertoire(_DIR_CACHE,"wheels/");
	return ecrire_fichier($dir.$key.".txt", serialize($value));
}

?>
