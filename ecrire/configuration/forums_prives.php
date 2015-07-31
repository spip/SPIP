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

include_spip('inc/presentation');
include_spip('inc/config');

function configuration_forums_prives_dist()
{
	$res = "<div class='verdana2'>"
	. _T('info_config_forums_prive')
	. "<br />\n"

	. "<p>"._T('info_config_forums_prive_objets')
	. "<br />\n"
	. afficher_choix('forum_prive_objets', $GLOBALS['meta']['forum_prive_objets'],
		array('oui' => _T('item_config_forums_prive_objets'),
			'non' => _T('item_non_config_forums_prive_objets')))
	."</p>\n"

	. "<p>"._T('info_config_forums_prive_global')
	. "<br />\n"
	. afficher_choix('forum_prive', $GLOBALS['meta']['forum_prive'],
		array('oui' => _T('item_config_forums_prive_global'),
			'non' => _T('item_non_config_forums_prive_global')))
	."</p>\n"

	. "<p>"._T('info_config_forums_prive_admin')
	. "<br />\n"
	. afficher_choix('forum_prive_admin', $GLOBALS['meta']['forum_prive_admin'],
		array('oui' => _T('item_activer_forum_administrateur'),
			'non' => _T('item_desactiver_forum_administrateur')))
	."</p>\n"

	. "</div>";

	$res = debut_cadre_trait_couleur("forum-interne-24.gif", true, "", _T('titre_config_forums_prive'))
	. ajax_action_post('configurer', 'forums_prives', 'config_contenu','',$res)
	 . fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-forums_prives', '', $res);
}
?>
