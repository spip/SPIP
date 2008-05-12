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

function configuration_forums_prives_dist()
{
	$res = "<div class='verdana2'>"
	. _L('Dans l&#8217;espace priv&#233; du site, vous pouvez activer plusieurs types de forums&nbsp;:')
	. "<br />\n"

	. "<p>"._L('Un forum sous chaque article, br&#232;ve, site r&#233;f&#233;renc&#233;, etc.&nbsp;:')
	. "<br />\n"
	. afficher_choix('forum_prive_objets', $GLOBALS['meta']['forum_prive_objets'],
		array('oui' => _L('Activer ces forums'),
			'non' => _L('D&#233;sactiver ces forums')))
	."</p>\n"

	. "<p>"._L('Un forum global, ouvert &#224; tous les r&#233;dacteurs&nbsp;:')
	. "<br />\n"
	. afficher_choix('forum_prive', $GLOBALS['meta']['forum_prive'],
		array('oui' => _L('Activer le forum des r&#233;dacteurs'),
			'non' => _L('D&#233;sactiver le forum des r&#233;dacteurs')))
	."</p>\n"

	. "<p>"._L('Un forum r&#233;serv&#233; aux administrateurs du site&nbsp;:')
	. "<br />\n"
	. afficher_choix('forum_prive_admin', $GLOBALS['meta']['forum_prive_admin'],
		array('oui' => _T('item_activer_forum_administrateur'),
			'non' => _T('item_desactiver_forum_administrateur')))
	."</p>\n"

	. "</div>";

	$res = debut_cadre_trait_couleur("forum-interne-24.gif", true, "", _L('Forums de l&#8217;espace priv&#233;'))
	. ajax_action_post('configurer', 'forums_prives', 'config_contenu','',$res)
	 . fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-forums_prives', '', $res);
}
?>
