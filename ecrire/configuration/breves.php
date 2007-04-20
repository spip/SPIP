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

//
// Actives/desactiver les breves
//

function configuration_breves(){
	global $spip_lang_left, $spip_lang_right;

	$activer_breves = $GLOBALS['meta']["activer_breves"];

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "<tr><td class='verdana2'>";
	$res .= _T('texte_breves')."<br />\n";
	$res .= _T('info_breves');
	$res .= "</td></tr>";
	
	$res .= "<tr><td align='center' class='verdana2'>";
	$res .= afficher_choix('activer_breves', $activer_breves,
		array('oui' => _T('item_utiliser_breves'),
			'non' => _T('item_non_utiliser_breves')), " &nbsp; ");
	$res .= "</td></tr>\n";
	$res .= "</table>\n";
	
	$res = debut_cadre_trait_couleur("breve-24.gif", true, "", _T('titre_breves').aide ("confbreves"))
	. ajax_action_post('configurer', 'breves', 'configuration','',$res)
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-breves', $res);

}

?>
