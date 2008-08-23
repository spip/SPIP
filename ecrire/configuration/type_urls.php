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

	$res .= debut_cadre_relief("", true, "", _L('Type d\'adresses URL'))
		.  "<p class='verdana2'>"
		. _L('Vous pouvez choisir ci-dessous le mode de calcul de l\'adresse des pages.')
		. " "
		. "</p>"

		. "<div class='verdana2'>"
		. afficher_choix('type_urls',
			$GLOBALS['meta']['type_urls'],
			array(
				'page' => '<em>page</em> &mdash; <tt>spip.php?article12</tt>',
				'html' => '<em>html</em> &mdash; <tt>article12.html</tt>',
				'arbo' => '<em>arbo</em> &mdash; <tt>/article/Titre</tt>',
				'propres' => '<em>propres</em> &mdash; <tt>Titre-de-l-article</tt>',
				'propres2' => '<em>propres2</em> &mdash; <tt>Titre-de-l-article.html</tt>'
#				'propresqs' => '<em>propresqs</em> &mdash; <tt>?Titre-de-l-article</tt>', // ne fonctionne plus
			)
		)
		. "</div>"

		. "<p><em>"._L('Attention ce r&#233;glage ne fonctionnera que si le fichier <tt>.htaccess</tt> est correctement install&#233; &#224; la racine du site.')."</em></p>"


		. fin_cadre_relief(true);




	$res = '<br />'.debut_cadre_trait_couleur("", true, "",
		_T('info_compresseur_titre'))
	.  ajax_action_post('configurer', 'type_urls', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-type_urls", '', $res);
}
?>
