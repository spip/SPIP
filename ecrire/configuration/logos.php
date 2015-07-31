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
// Gestion des documents joints
//

function configuration_logos_dist(){
	global $spip_lang_left, $spip_lang_right;
	
	$activer_logos = $GLOBALS['meta']["activer_logos"];
	$activer_logos_survol = $GLOBALS['meta']["activer_logos_survol"];
	
	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "<tr><td class='verdana2'>";
	$res .= _L('Chaque objet du site peut avoir un logo, ainsi qu\'un logo &#171;&nbsp;de survol&nbsp;&#187;').aide('logoart');
	$res .= "</td></tr>";
	
	$res .= "<tr>";
	$res .= "<td align='$spip_lang_left' class='verdana2'>";
	
	
	$res .= bouton_radio("activer_logos", "oui", _L('Utiliser les logos'), $activer_logos == "oui", "changeVisible(this.checked, 'logos_survol_config', 'block', 'none');")
	. " <br /> "
	. bouton_radio("activer_logos", "non", _L('Ne pas utiliser les logos'), $activer_logos == "non", "changeVisible(this.checked, 'logos_survol_config', 'none', 'block');");

	if ($activer_logos != "non") $style = "display: block;";
	else $style = "display: none;";
	
	$res .= "<br /><br /><div id='logos_survol_config' style='$style'>";
	

	$res .= afficher_choix('activer_logos_survol', $activer_logos_survol,
		array('oui' => _L('Utiliser les logos de survol'),
			'non' => _L('Ne pas utiliser les logos de survol')), " <br /> ");
			
			
	$res .= "</div>";
	
	$res .= "</td></tr>";
	$res .= "</table>\n";

	$res = debut_cadre_trait_couleur("image-24.gif", true, "", _L('Les logos'))
	. ajax_action_post('configurer', 'logos', 'configuration','',$res) 
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-logos', '', $res);
}
?>
