<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

// #MENU_LANG_ECRIRE affiche le menu des langues de l'espace privé
// et preselectionne celle la globale $lang
// ou de l'arguemnt fourni: #MENU_LANG_ECRIRE{#ENV{malangue}} 

function balise_MENU_LANG_ECRIRE ($p) {

	return calculer_balise_dynamique($p,'MENU_LANG_ECRIRE', array('lang'));
}

// s'il n'y a qu'une langue proposee eviter definitivement la balise ?php 
function balise_MENU_LANG_ECRIRE_stat ($args, $filtres) {
	global $all_langs;
	include_spip('inc/lang');
	if (strpos($all_langs,',') === false) return '';
	return $filtres ? $filtres : $args;
}

// normalement $opt sera toujours non vide suite au test ci-dessus
function balise_MENU_LANG_ECRIRE_dyn($opt) {
	return menu_lang_pour_tous('var_lang_ecrire', $opt);
}

function menu_lang_pour_tous($nom, $default) {
	include_spip('inc/lang');

	if ($GLOBALS['spip_lang'] <> $default) {
		lang_select($default);	# et remplace
		if ($GLOBALS['spip_lang'] <> $default) {
			$default = '';	# annule tout choix par defaut
			lang_dselect();		#annule la selection
		}
	}

	$opt = liste_options_langues($nom, $default);
	if (!$opt)
		return '';

	$cible = str_replace('&amp;', '&', parametre_url(self( /* racine */ true), 'lang' , '')); # lien a partir de /
	$post = parametre_url(generer_url_action('cookie'), 'url', $cible, '&');

	return array('formulaires/formulaire_menu_lang',
		3600,
		array('nom' => $nom,
			'url' => $post,
			'langues' => $opt
		)
	);
}

?>
