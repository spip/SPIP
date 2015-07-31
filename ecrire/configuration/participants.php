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
// Mode de fonctionnement des forums publics
//

function configuration_participants_dist()
{
	global $spip_lang_left ;

	$forums_publics=$GLOBALS['meta']["forums_publics"];

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "\n<tr><td  style='text-align: $spip_lang_left;' class='verdana2'>";

	if ($forums_publics == "non") $block = "'none', 'block'"; 
	else $block= "'block', 'none'";
	$res .= bouton_radio("forums_publics", "non", _T('info_desactiver_forum_public'), $forums_publics == "non", "changeVisible(this.checked, 'config-options', $block);");


	$res .= "</td></tr>";
	$res .= "\n<tr><td class='verdana2'>";
	$res .= _T('info_activer_forum_public');
	$res .= "</td></tr>";
	$res .= "\n<tr><td style='text-align: $spip_lang_left:' class='verdana2'>";

	if ($forums_publics == "posteriori") $block = "'none', 'block'"; 
	else $block= "'block', 'none'";
	$res .= bouton_radio("forums_publics", "posteriori", _T('bouton_radio_publication_immediate'), $forums_publics == "posteriori", "changeVisible(this.checked, 'config-options', $block);");
	$res .= "<br />\n";
	if ($forums_publics == "priori") $block = "'none', 'block'"; 
	else $block= "'block', 'none'";
	$res .= bouton_radio("forums_publics", "priori", _T('bouton_radio_moderation_priori'), $forums_publics == "priori", "changeVisible(this.checked, 'config-options', $block);");

	$res .= "<br />\n";
	if ($forums_publics == "abo") $block = "'none', 'block'"; 
	else $block= "'block', 'none'";
	$res .= bouton_radio("forums_publics", "abo", _T('bouton_radio_enregistrement_obligatoire'), $forums_publics == "abo", "changeVisible(this.checked, 'config-options', $block);");

$res .= "</td></tr>";

$res .= "\n<tr><td style='text-align: $spip_lang_left' class='verdana2'>";

	$res .= "<div id='config-options' class='display_au_chargement' style='margin-left: 40px;'>";
	
	$res .= debut_cadre_relief("", true, "", _T('info_options_avancees'));
	
	$res .= "<table width='100%' cellpadding='2' border='0' class='hauteur'>\n";
	$res .= "\n<tr><td class='verdana2'>";
	$res .= _T('info_appliquer_choix_moderation')."<br />\n";

	$res .= "<input type='radio' checked='checked' name='forums_publics_appliquer' value='futur' id='forums_appliquer_futur' />";
	$res .= "\n<b><label for='forums_appliquer_futur'>"._T('bouton_radio_articles_futurs')."</label></b><br />\n";
	$res .= "<input type='radio' name='forums_publics_appliquer' value='saufnon' id='forums_appliquer_saufnon' />";
	$res .= "\n<label for='forums_appliquer_saufnon'>"._T('bouton_radio_articles_tous_sauf_forum_desactive')."</label><br />\n";
	$res .= "<input type='radio' name='forums_publics_appliquer' value='tous' id='forums_appliquer_tous' />";
	$res .= "\n<label for='forums_appliquer_tous'>"._T('bouton_radio_articles_tous')."</label><br />\n";
	$res .= "</td></tr></table>";
	$res .= fin_cadre_relief(true);
	$res .= "</div>";
	$res .= "</td></tr></table>\n";

	$res = debut_cadre_trait_couleur("forum-interne-24.gif", true, "", _T('info_mode_fonctionnement_defaut_forum_public').aide ("confforums"))
	  . ajax_action_post('configurer', 'participants', 'config_contenu','',$res)
	  . fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-participants', '', $res);
}
?>
