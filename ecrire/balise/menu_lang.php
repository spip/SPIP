<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// #MENU_LANG affiche le menu des langues de l'espace public
// et preselectionne celle la globale $lang
// ou de l'argument fourni: #MENU_LANG{#ENV{malangue}} 


// http://doc.spip.org/@balise_MENU_LANG
function balise_MENU_LANG ($p) {
	return calculer_balise_dynamique($p,'MENU_LANG', array('lang'));
}

// s'il n'y a qu'une langue eviter definitivement la balise ?php 
// http://doc.spip.org/@balise_MENU_LANG_stat
function balise_MENU_LANG_stat ($args, $context_compil) {
	if (strpos($GLOBALS['meta']['langues_multilingue'],',') === false) return '';
	return $args;
}

// normalement $opt sera toujours non vide suite au test ci-dessus
// http://doc.spip.org/@balise_MENU_LANG_dyn
function balise_MENU_LANG_dyn($opt) {
	include_spip('balise/menu_lang_ecrire');
	return menu_lang_pour_tous('var_lang', $opt);
}

?>
