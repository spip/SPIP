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
// Actives/desactiver systeme de syndication
//

function configuration_syndications_dist()
{
	global $spip_lang_left;
	
	$activer_sites = $GLOBALS['meta']['activer_sites'];
	$activer_syndic = $GLOBALS['meta']["activer_syndic"];
	$proposer_sites = $GLOBALS['meta']["proposer_sites"];
	$moderation_sites = $GLOBALS['meta']["moderation_sites"];
	
	$res = "\n<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	
	$res .= "<tr><td align='$spip_lang_left' class='verdana2'>";
	
	$res .= bouton_radio("activer_sites", "oui", _T('item_gerer_annuaire_site_web'), $activer_sites == "oui", "changeVisible(this.checked, 'config-site', 'block', 'none');");
	$res .= " &nbsp;";
	$res .= bouton_radio("activer_sites", "non", _T('item_non_gerer_annuaire_site_web'), $activer_sites == "non", "changeVisible(this.checked, 'config-site', 'none', 'block');");
	
	$res .= "</td></tr></table>\n";



	if ($activer_sites != 'non') $style = "display: block;";
	else $style = "display: none;";

	$res .= "<div id='config-site' style='$style'>";
	
	// Utilisateurs autorises a proposer des sites references
	//
	$res .= "<br />\n";
	$res .= debut_cadre_relief('',true);
	$res .= "\n<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "\n<tr><td style='color: #000000' class='verdana1 spip_x-small'>";
	$res .= "<label for='proposer_sites'>" . _T('info_question_proposer_site') ."</label>";
	$res .= "\n<div style='text-align: center'><select name='proposer_sites' id='proposer_sites' size='1'>\n";
	$res .= "<option".mySel('0',$proposer_sites).">"._T('item_choix_administrateurs')."</option>\n";
	$res .= "<option".mySel('1',$proposer_sites).">"._T('item_choix_redacteurs')."</option>\n";
	$res .= "<option".mySel('2',$proposer_sites).">"._T('item_choix_visiteurs')."</option>\n";
	$res .= "</select></div>\n";
	$res .= "</td></tr></table>\n";
	$res .= fin_cadre_relief(true);

	$res .= debut_cadre_relief("", true, "", _T('titre_syndication').aide ("rubsyn"));
	
	$res .= "\n<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	//
	// Reglage de la syndication
	//
	$res .= "<tr><td class='verdana2'>";
	$res .= _T('texte_syndication');
	$res .= "</td></tr>";

	$res .= "<tr><td align='$spip_lang_left' class='verdana2'>";

	$res .= bouton_radio("activer_syndic", "oui", _T('item_utiliser_syndication'), $activer_syndic == "oui", "changeVisible(this.checked, 'config-syndic', 'block', 'none');");
	$res .= "<br />\n";
	$res .= bouton_radio("activer_syndic", "non", _T('item_non_utiliser_syndication'), $activer_syndic == "non", "changeVisible(this.checked, 'config-syndic', 'none', 'block');");

	if ($activer_syndic != "non") $style = "display: block;";
	else $style = "display: none;";
			
	$res .= "<div id='config-syndic' style='$style'>";
		
	// Moderation par defaut des sites syndiques
	$res .= "<hr /><p style='text-align: $spip_lang_left'>";
	$res .= _T('texte_liens_sites_syndiques')."</p>";

	$res .= afficher_choix('moderation_sites', $moderation_sites,
		array('oui' => _T('item_bloquer_liens_syndiques'),
		'non' => _T('item_non_bloquer_liens_syndiques')));

	$res .= "</div>";
		
	$res .= "</td></tr>\n";

	$res .= "</table>\n";

	$res .= fin_cadre_relief(true);
	$res .= "</div>";

	//
	// Gestion des flux RSS
	//

	$res .= debut_cadre_relief("feed.png", true, "", _T('ical_titre_rss'));
	
	$res .= "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	
	$res .= "<tr><td class='verdana2'>";
	$res .= _T('info_syndication_integrale_1',
			array('url' => generer_url_ecrire('synchro'),
			'titre' => _T("icone_suivi_activite"))
		).
		'<p>' .
	  _T('info_syndication_integrale_2').
	  '</p>';
	$res .= "</td></tr>";
	
	$res .= "<tr>";
	$res .= "<td align='$spip_lang_left' class='verdana2'>";
	$res .= afficher_choix('syndication_integrale', $GLOBALS['meta']["syndication_integrale"],
		array('oui' => _T('item_autoriser_syndication_integrale'),
			'non' => _T('item_non_autoriser_syndication_integrale')), "<br />\n");
	$res .= "</td></tr>";
	$res .= "</table>\n";
	
	$res .= fin_cadre_relief(true);

	$res = debut_cadre_trait_couleur("site-24.gif",true, "", _T('titre_referencement_sites').aide ("reference"))
	. ajax_action_post('configurer', 'syndications', 'configuration','',$res) 
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-syndications', '', $res);
}
?>
