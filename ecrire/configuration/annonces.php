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
include_spip('inc/mail');
include_spip('inc/config');

function configuration_annonces_dist()
{
  global $connect_statut, $connect_toutes_rubriques, $options, $spip_lang_right, $spip_lang_left,$changer_config, $envoi_now ;

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "\n<tr><td class='verdana2'>";
	$res .= "<blockquote><p><i>"._T('info_hebergeur_desactiver_envoi_email')."</i></p></blockquote>";
	$res .= "</td></tr></table>";

	$res .= debut_cadre_relief("", true, "", _T('info_envoi_forum'));
	$res .= "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "\n<tr><td class='verdana2'>";
	$res .= _T('info_option_email');
	$res .= "</td></tr>";

	$res .= "\n<tr><td style='text-align: $spip_lang_left' class='verdana2'>";
	$res .= afficher_choix('prevenir_auteurs', $GLOBALS['meta']["prevenir_auteurs"],
		array('oui' => _T('info_option_faire_suivre'),
			'non' => _T('info_option_ne_pas_faire_suivre')));
	$res .= "</td></tr></table>\n";
	$res .= fin_cadre_relief(true);

	//
	// Suivi editorial (articles proposes & publies)
	//

	$suivi_edito=$GLOBALS['meta']["suivi_edito"];
	$adresse_suivi=$GLOBALS['meta']["adresse_suivi"];
	$adresse_suivi_inscription=$GLOBALS['meta']["adresse_suivi_inscription"];

	$res .= "<br />\n";
	$res .= debut_cadre_relief("", true, "", _T('info_suivi_activite'));
	$res .= "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";

	$res .= "\n<tr><td class='verdana2'>";
	$res .= _T('info_facilite_suivi_activite');
	$res .= "</td></tr></table>";


	$res .= "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "\n<tr><td style='text-align: $spip_lang_left' class='verdana2'>";

	$res .= bouton_radio("suivi_edito", "oui", _T('bouton_radio_envoi_annonces_adresse'), $suivi_edito == "oui", "changeVisible(this.checked, 'config-edito', 'block', 'none');");


	if ($suivi_edito == "oui") $style = "display: block;";
	else $style = "display: none;";			
	$res .= "<div id='config-edito' style='$style'>";
	$res .= "<div style='text-align: center;'><input type='text' name='adresse_suivi' value='$adresse_suivi' size='30' class='fondl' /></div>";
	$res .= "<blockquote class='spip'><p>";
	if (!$adresse_suivi) $adresse_suivi = "mailing@monsite.net";
	$res .= _T('info_config_suivi', array('adresse_suivi' => $adresse_suivi));
	$res .= "<br />\n<input type='text' name='adresse_suivi_inscription' value='$adresse_suivi_inscription' size='50' class='fondl' />";
	$res .= "</p></blockquote>";
	$res .= "</div>";

	$res .= "<br />\n";
	$res .= bouton_radio("suivi_edito", "non", _T('bouton_radio_non_envoi_annonces_editoriales'), $suivi_edito == "non", "changeVisible(this.checked, 'config-edito', 'none', 'block');");

	$res .= "</td></tr></table>\n";
	$res .= fin_cadre_relief(true);

	//
	// Annonce des nouveautes
	//
	$quoi_de_neuf=$GLOBALS['meta']["quoi_de_neuf"];
	$adresse_neuf=$GLOBALS['meta']["adresse_neuf"];
	$jours_neuf=$GLOBALS['meta']["jours_neuf"];

	$res .= "<br />\n";
	$res .= debut_cadre_relief("", true, "", _T('info_annonce_nouveautes'));
	$res .= "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";

	$res .= "\n<tr><td class='verdana2'>";
	$res .= _T('info_non_envoi_annonce_dernieres_nouveautes');
	$res .= "</td></tr>";

	$res .= "\n<tr><td style='text-align: $spip_lang_left' class='verdana2'>";

	$res .= bouton_radio("quoi_de_neuf", "oui", _T('bouton_radio_envoi_liste_nouveautes'), $quoi_de_neuf == "oui", "changeVisible(this.checked, 'config-neuf', 'block', 'none');");
	//	$res .= "<input type='radio' name='quoi_de_neuf' value='oui' id='quoi_de_neuf_on' checked='checked' />";
	//	$res .= " <b><label for='quoi_de_neuf_on'>"._T('bouton_radio_envoi_liste_nouveautes')."</label></b> ";

	if ($quoi_de_neuf == "oui") $style = "display: block;";
	else $style = "display: none;";			
	$res .= "<div id='config-neuf' style='$style'>";
	$res .= "<ul>";
	$res .= "<li>"._T('info_adresse');
	$res .= "\n<input type='text' name='adresse_neuf' value='$adresse_neuf' size='30' class='fondl' />";
	$res .= "</li><li>"._T('info_tous_les');
	$res .= "\n<input type='text' name='jours_neuf' value='$jours_neuf' size='4' class='fondl' />\n";
	$res .= _T('info_jours');
	$res .= " &nbsp;  &nbsp;  &nbsp;\n<input type='submit' name='envoi_now' value='";
	$res .= _T('info_envoyer_maintenant');
	$res .= "' class='fondl' onclick='AjaxNamedSubmit(this)' />";
	$res .= "</li></ul>";
	$res .= "</div>";

	$res .= "<br />\n";
	$res .= bouton_radio("quoi_de_neuf", "non", _T('info_non_envoi_liste_nouveautes'), $quoi_de_neuf == "non", "changeVisible(this.checked, 'config-neuf', 'none', 'block');");
		//$res .= "<br />\n<input type='radio' name='quoi_de_neuf' value='non' id='quoi_de_neuf_off' />";
		//$res .= " <label for='quoi_de_neuf_off'>"._T('info_non_envoi_liste_nouveautes')."</label> ";
	
	$res .= "</td></tr></table>\n";
	$res .= fin_cadre_relief(true);

	$email_envoi = entites_html($GLOBALS['meta']["email_envoi"]);
	$titre =  _T('info_email_envoi');
	if ($email_envoi) $titre .= "&nbsp;:&nbsp;" . $email_envoi;
	$res .= "<br />\n";
	$res .= debut_cadre_relief("", true, "", $titre);
	$res .= "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	$res .= "\n<tr><td class='verdana2'>";
	$res .= _T('info_email_envoi_txt');
	$res .= " <input type='text' name='email_envoi' value=\"$email_envoi\" size='20' class='fondl' />";
	$res .= "</td></tr>";
	$res .= "\n<tr><td>&nbsp;</td></tr></table>";
	$res .= fin_cadre_relief(true);

	$res = debut_cadre_trait_couleur("", true, "", _T('info_envoi_email_automatique').aide ("confmails"))
	. ajax_action_post('configurer', 'annonces', 'config_contenu','',$res) 
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-annonces', $res);
}
?>