<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

global $balise_MENU_LANG_collecte;
$balise_MENU_LANG_collecte = array('menu_lang');

// s'il n'y a qu'une langue eviter definitivement la balise ?php 

function balise_MENU_LANG_stat ($args, $filtres)
{
  if (!strpos(lire_meta('langues_multilingue'),',')) return '';
  return $args;
}

// normalement $opt sera toujours non vide suite au test ci-dessus

function balise_MENU_LANG_dyn($default)
{
  include_ecrire("inc_lang.php3");
  $opt = liste_options_langues('var_lang', $default);
  if (!$opt) return '';
  include_local("inc-menu_lang_ecrire.php3");
  return menu_lang_pour_tous('var_lang', $opt);
}
?>
