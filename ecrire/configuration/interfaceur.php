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

// Formulaire (provisoire ?) pour passer de l'interface onglet a linterface lineaire
// Gestion des libelles a revoir

function configuration_interfaceur_dist()
{
	
	global $spip_lang_right;

	$res = "<div class='verdana2'>"
	. _L('Vous pouvez definir le mode de pr&eacute;sentation des articles :')
	. "</div>"
	. "<div class='verdana2'>"
	. afficher_choix('interface_mode',$GLOBALS['meta']["interface_mode"] ,
		array('192' => _L('Utiliser la pr&eacute;sentation traditionelle de SPIP'),
			'193' => _L('Utiliser les onglets pour pr&eacute;senter les articles')
			))
	  . "</div>";

	$res = debut_cadre_trait_couleur("article-24.gif", true, "", _L('Choix de l\'interface'))
	.  ajax_action_post('configurer', 'interfaceur', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-interfaceur", '', $res);

}
?>
