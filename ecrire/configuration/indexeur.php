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

function configuration_indexeur_dist()
{
	global $spip_lang_right;

	$res = "<div class='verdana2'>"
	.  _T('info_question_utilisation_moteur_recherche')
	.  "</div>"
	.  "<div class='verdana2'>"
	.  afficher_choix('activer_moteur', $GLOBALS['meta']["activer_moteur"],
		array('oui' => _T('item_utiliser_moteur_recherche'),
			'non' => _T('item_non_utiliser_moteur_recherche')), ' &nbsp; ')
	  .  "</div>";

	return ajax_action_greffe("configurer-indexeur", 
	  debut_cadre_trait_couleur("racine-site-24.gif", true, "", _T('info_moteur_recherche').aide ("confmoteur"))
	.  ajax_action_post('configurer', 'indexeur', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true)
	.  "<br />");
}
?>
