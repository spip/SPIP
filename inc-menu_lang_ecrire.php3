<?php

global $balise_MENU_LANG_ECRIRE_collecte;
$balise_MENU_LANG_ECRIRE_collecte = array('menu_lang');

// s'il n'y a qu'une langue proposee eviter definitivement la balise ?php 

function balise_MENU_LANG_ECRIRE_stat ($args, $filtres)
{
  if (!strpos(lire_meta('langues_proposees'),',')) return '';
  return $args;
}

// normalement $opt sera toujours non vide suite au test ci-dessus

function balise_MENU_LANG_ECRIRE_dyn($default)
{
  include_ecrire("inc_lang.php3");
  $opt = liste_options_langues('var_lang_ecrire', $default);
  return (!$opt ? '' : menu_lang_pour_tous('var_lang_ecrire', $opt));
}

function menu_lang_pour_tous($nom, $opt)
{
  $site = lire_meta("adresse_site");
  $post = ($site ? $site : '..') . "/spip_cookie.php3";
  $cible = $GLOBALS['clean_link']->getUrl();
  $postcomplet = new Link($post);
  $postcomplet->addvar('url', $cible);

  return array('formulaire_menu_lang',
	       10, 
	       array('nom' => $nom,
		     'url' => $post,
		     'cible' => $cible,
		     'retour' => $postcomplet->getUrl(),
		     'langues' => $opt));
}
?>
