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
// Gestion des mots-cles
//

function configuration_mots(){
	global $spip_lang_left, $spip_lang_right;

	$articles_mots = $GLOBALS['meta']["articles_mots"];
	$config_precise_groupes = $GLOBALS['meta']["config_precise_groupes"];
	$mots_cles_forums = $GLOBALS['meta']["mots_cles_forums"];
	$forums_publics = $GLOBALS['meta']["forums_publics"];

	$res .= "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "<tr><td class='verdana2'>";
	$res .= _T('texte_mots_cles')."<br />\n";
	$res .= _T('info_question_mots_cles');
	$res .= "</td></tr>";

	$res .= "<tr>";
	$res .= "<td align='center' class='verdana2'>";


	$res .= bouton_radio("articles_mots", "oui", _T('item_utiliser_mots_cles'), $articles_mots == "oui", "changeVisible(this.checked, 'mots-config', 'block', 'none');");
	$res .= " &nbsp;";
	$res .= bouton_radio("articles_mots", "non", _T('item_non_utiliser_mots_cles'), $articles_mots == "non", "changeVisible(this.checked, 'mots-config', 'none', 'block');");

	//	$res .= afficher_choix('articles_mots', $articles_mots,
	//		array('oui' => _T('item_utiliser_mots_cles'),
	//			'non' => _T('item_non_utiliser_mots_cles')), "<br />");
	$res .= "</td></tr></table>";

	if ($articles_mots != "non") $style = "display: block;";
	else $style = "display: none;";
	
	$res .= "<div id='mots-config' style='$style'>";
	
	$res .= "<br />\n";
	$res .= debut_cadre_relief("", true, "", _T('titre_config_groupe_mots_cles'));

	$res .= "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "<tr><td class='verdana2'>";
	$res .= _T('texte_config_groupe_mots_cles');
	$res .= "</td></tr>";

	$res .= "<tr>";
	$res .= "<td align='$spip_lang_left' class='verdana2'>";
	$res .= afficher_choix('config_precise_groupes', $config_precise_groupes,
		array('oui' => _T('item_utiliser_config_groupe_mots_cles'),
			'non' => _T('item_non_utiliser_config_groupe_mots_cles')));
	$res .= "</td></tr></table>";
	$res .= fin_cadre_relief(true);

	if ($forums_publics != "non"){
		$res .= "<br />\n";
		$res .= debut_cadre_relief("", true, "", _T('titre_mots_cles_dans_forum'));
		$res .= "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
		$res .= "<tr><td class='verdana2'>";
		$res .= _T('texte_mots_cles_dans_forum');
		$res .= "</td></tr>";

		$res .= "<tr>";
		$res .= "<td align='$spip_lang_left' class='verdana2'>";
		$res .= afficher_choix('mots_cles_forums', $mots_cles_forums,
			array('oui' => _T('item_ajout_mots_cles'),
				'non' => _T('item_non_ajout_mots_cles')));
		$res .= "</td></tr>";
		$res .= "</table>";
		$res .= fin_cadre_relief(true);
	}
	$res .= "</div>";	

	$res = debut_cadre_trait_couleur("mot-cle-24.gif", true, "", _T('info_mots_cles'))
	. ajax_action_post('configurer', 'mots', 'configuration','',$res) 
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-mots', $res);

}
?>
