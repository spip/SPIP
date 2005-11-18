<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

// Ce "menu_lang" collecte dans le contexte permet de forcer la langue
// par defaut proposee dans le menu, en faisant une inclusion
// <INCLURE(toto){menu_lang=xxx}> ; mais a quoi ca sert concretement ?
global $balise_MENU_LANG_collecte;
$balise_MENU_LANG_collecte = array('menu_lang');

// s'il n'y a qu'une langue eviter definitivement la balise ?php 
function balise_MENU_LANG_stat ($args, $filtres) {
	if (strpos($GLOBALS['meta']['langues_multilingue'],',') === false) return '';
	return $args;
}

// normalement $opt sera toujours non vide suite au test ci-dessus
function balise_MENU_LANG_dyn($menu_lang) {
	include_local(find_in_path("inc-menu_lang_ecrire.php3"));
	return menu_lang_pour_tous('var_lang', $opt);
}

?>
