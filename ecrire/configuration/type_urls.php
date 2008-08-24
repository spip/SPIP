<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/config');

function configuration_type_urls_dist()
{
	global $spip_lang_right;

	$res = '';

	// Choix du type d'url
	if ($GLOBALS['type_urls'] != 'page') // fixe par mes_options
		return '';

	$types_dispo = array(
		'page' => '<em>page</em> &mdash; <tt>spip.php?article12</tt>',
		'html' => '<em>html</em> &mdash; <tt>article12.html</tt>',
		'arbo' => '<em>arbo</em> &mdash; <tt>/article/Titre</tt>',
		'standard' => '<em>standard</em> &mdash; <tt>article.php3?id_article=12</tt>',
		'propres' => '<em>propres</em> &mdash; <tt>Titre-de-l-article</tt>',
		'propres2' => '<em>propres2</em> &mdash; <tt>Titre-de-l-article.html</tt>',
		'propres_qs' => '<em>propres_qs</em> &mdash; <tt>?Titre-de-l-article</tt>'
	);

	$res .= "<p class='verdana2'>"
		. _T('texte_type_urls')
		. " "
		. "</p>"

		. "<div class='verdana2'>"
		. afficher_choix('type_urls',
			$GLOBALS['meta']['type_urls'],
			$types_dispo
		)
		. "</div>"

		. "<p><em>"._T('texte_type_urls_attention', array('htaccess' => '<tt>.htaccess</tt>'))."</em></p>";




	$res = '<br />'.debut_cadre_trait_couleur("", true, "",
		_T('titre_type_urls'))
	.  ajax_action_post('configurer', 'type_urls', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-type_urls", '', $res);
}
?>
