<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// #MENU_LANG_ECRIRE affiche le menu des langues de l'espace privé
// et preselectionne celle la globale $lang
// ou de l'arguemnt fourni: #MENU_LANG_ECRIRE{#ENV{malangue}} 

// http://doc.spip.org/@balise_MENU_LANG_ECRIRE
function balise_MENU_LANG_ECRIRE ($p) {
	return calculer_balise_dynamique($p,'MENU_LANG_ECRIRE', array('lang'));
}

// s'il n'y a qu'une langue proposee eviter definitivement la balise ?php 
// http://doc.spip.org/@balise_MENU_LANG_ECRIRE_stat
function balise_MENU_LANG_ECRIRE_stat ($args, $context_compil) {
	include_spip('inc/lang');
	if (strpos($GLOBALS['meta']['langues_proposees'],',') === false) return '';
	return $args;
}

// normalement $opt sera toujours non vide suite au test ci-dessus
// http://doc.spip.org/@balise_MENU_LANG_ECRIRE_dyn
function balise_MENU_LANG_ECRIRE_dyn($opt) {
	return menu_lang_pour_tous('var_lang_ecrire', $opt);
}

// http://doc.spip.org/@menu_lang_pour_tous
function menu_lang_pour_tous($nom, $default) {
	include_spip('inc/lang');

	if ($GLOBALS['spip_lang'] <> $default) {
		$opt = lang_select($default);	# et remplace
		if ($GLOBALS['spip_lang'] <> $default) {
			$default = '';	# annule tout choix par defaut
			if ($opt) lang_select();
		}
	}

	# lien a partir de /
	$cible = parametre_url(self(), 'lang' , '', '&');
	$post = generer_url_action('converser', 'redirect='. rawurlencode($cible), '&');

	return array('formulaires/menu_lang',
		3600,
		array('nom' => $nom,
			'url' => $post,
			'name' => $nom,
			'default' => $default,
		)
	);
}

?>
