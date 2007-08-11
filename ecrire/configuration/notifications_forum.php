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

function configuration_notifications_forum_dist()
{
	global $spip_lang_left;

	$res = "<div class='verdana2'>"
		. _T('info_option_email')
		. "<br />\n";

	$res .= afficher_choix(
		'prevenir_auteurs',
		$GLOBALS['meta']["prevenir_auteurs"],
		array('oui' => _T('info_option_faire_suivre'),
			'non' => _T('info_option_ne_pas_faire_suivre')
		)
	);

	$res .= "</div>\n";

	$res = debut_cadre_trait_couleur("", true, "", _T('info_envoi_forum'))
	. ajax_action_post('configurer', 'notifications_forum', 'config_contenu','',$res) 
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-notifications_forum', '', $res);
}
?>