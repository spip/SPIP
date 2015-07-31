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

//
// Actives/desactiver les breves
//

function configuration_breves_dist(){

	$activer_breves = $GLOBALS['meta']["activer_breves"];

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"
	. "<tr><td class='verdana2'>"
	. _T('texte_breves')."<br />\n"
	. _T('info_breves')
	. "</td></tr>"
	. "<tr><td align='center' class='verdana2'>"
	. afficher_choix('activer_breves', $activer_breves,
		array('oui' => _T('item_utiliser_breves'),
			'non' => _T('item_non_utiliser_breves')), " &nbsp; ")
	. "</td></tr>\n"
	. "</table>\n";
	
	$res = debut_cadre_trait_couleur("breve-24.gif", true, "", _T('titre_breves').aide ("confbreves"))
	. ajax_action_post('configurer', 'breves', 'configuration','',$res)
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-breves', '', $res);

}

?>
