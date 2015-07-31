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

function configuration_avertisseur_dist()
{
	global $spip_lang_right;

	$res = "<div class='verdana2'>"
	. _T('texte_travail_collaboratif')
	. "</div>"
	. "<div class='verdana2'>"
	. afficher_choix('articles_modif',$GLOBALS['meta']["articles_modif"] ,
		array('oui' => _T('item_activer_messages_avertissement'),
			'non' => _T('item_non_activer_messages_avertissement')))
	  . "</div>";

	return ajax_action_greffe("configurer-avertisseur", 
	  debut_cadre_trait_couleur("article-24.gif", true, "", _T('info_travail_colaboratif').aide("artmodif"))
	.  ajax_action_post('configurer', 'avertisseur', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true)
	.  "<br />");
}
?>
