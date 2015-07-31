<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/config');

function configuration_administrateurs_dist()
{
  global $connect_statut, $connect_toutes_rubriques, $options, $spip_lang_right, $spip_lang_left,$changer_config, $envoi_now ;

	$res .= "<div class='verdana2'>";
	$res .= _T('info_forum_ouvert');
	$res .= "<br />\n";
	$res .= afficher_choix('forum_prive_admin', $GLOBALS['meta']['forum_prive_admin'],
		array('oui' => _T('item_activer_forum_administrateur'),
			'non' => _T('item_desactiver_forum_administrateur')));
	$res .= "</div>";

	$res = debut_cadre_trait_couleur("forum-admin-24.gif", true, "", _T('titre_cadre_forum_administrateur'))
	. ajax_action_post('configurer', 'administrateurs', 'config_contenu','',$res)
	 . fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-administrateurs', $res);
}
?>