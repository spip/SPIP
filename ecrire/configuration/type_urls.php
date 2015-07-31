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

// Choix du type d'url

include_spip('inc/presentation');
include_spip('inc/config');

function configuration_type_urls_dist()
{
	if ($GLOBALS['type_urls'] != 'page') // fixe par mes_options
		return '';

	$dispo = array();
	foreach (find_all_in_path('urls/', '\w+\.php$', array()) as $f) {
		$r = basename($f, '.php');
		if ($r == 'index' OR strncmp('generer_',$r,8)==0) continue;
		include_once $f;
		$exemple = 'URLS_' . strtoupper($r) . '_EXEMPLE';
		$exemple = defined($exemple) ? constant($exemple) : '?';
		$dispo[$r] = "<em>$r</em> &mdash; <tt>" . $exemple . '</tt>';
	}

	$res = "<p class='verdana2'>"
		. _T('texte_type_urls')
		. "</p>"
		. "<div class='verdana2'>"
		. afficher_choix('type_urls', $GLOBALS['meta']['type_urls'], $dispo)
		. "</div>"
		. "<p><em>"
		. _T('texte_type_urls_attention', array('htaccess' => '<tt>.htaccess</tt>'))
		. "</em></p>";


	$res = '<br />'.debut_cadre_trait_couleur("", true, "",  _T('titre_type_urls').aide("confurl"))
	.  ajax_action_post('configurer', 'type_urls', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-type_urls", '', $res);
}
?>
