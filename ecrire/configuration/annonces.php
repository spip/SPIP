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
	global $spip_lang_left;

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"
	. "\n<tr><td class='verdana2'>"
	. "<blockquote><p><i>"._T('info_hebergeur_desactiver_envoi_email')."</i></p></blockquote>"
	. "</td></tr></table>";

	//
	// Suivi editorial (articles proposes & publies)
	//

	$suivi_edito=$GLOBALS['meta']["suivi_edito"];
	$adresse_suivi=$GLOBALS['meta']["adresse_suivi"];
	$adresse_suivi_inscription=$GLOBALS['meta']["adresse_suivi_inscription"];

	$res .= "<br />\n"
	. debut_cadre_relief("", true, "", _T('info_suivi_activite'))
	. "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";

	$res .= "\n<tr><td class='verdana2'>"
	. _T('info_facilite_suivi_activite')
	. "</td></tr></table>";


	$res .= "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"
	. "\n<tr><td style='text-align: $spip_lang_left' class='verdana2'>";

	$res .= bouton_radio("suivi_edito", "oui", _T('bouton_radio_envoi_annonces_adresse'), $suivi_edito == "oui", "changeVisible(this.checked, 'config-edito', 'block', 'none');");


	if ($suivi_edito == "oui") $style = "display: block;";
	else $style = "display: none;";			

	$res .= "<div id='config-edito' style='$style'>"
	. "<div style='text-align: center;'><input type='text' name='adresse_suivi' value='$adresse_suivi' size='30' class='fondl' /></div>"
	. "<blockquote class='spip'><p>";

	if (!$adresse_suivi) $adresse_suivi = "mailing@monsite.net";

	$res .= _T('info_config_suivi', array('adresse_suivi' => $adresse_suivi))
	. "<br />\n<input type='text' name='adresse_suivi_inscription' value='$adresse_suivi_inscription' size='50' class='fondl' />"
	. "</p></blockquote>"
	. "</div>"
	. "<br />\n"
	. bouton_radio("suivi_edito", "non", _T('bouton_radio_non_envoi_annonces_editoriales'), $suivi_edito == "non", "changeVisible(this.checked, 'config-edito', 'none', 'block');")
	. "</td></tr></table>\n"
	. fin_cadre_relief(true);

	//
	// Annonce des nouveautes
	//
	$quoi_de_neuf=$GLOBALS['meta']["quoi_de_neuf"];
	$adresse_neuf=$GLOBALS['meta']["adresse_neuf"];
	$jours_neuf=$GLOBALS['meta']["jours_neuf"];

	$res .= "<br />\n"
	. debut_cadre_relief("", true, "", _T('info_annonce_nouveautes'))
	. "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"
	. "\n<tr><td class='verdana2'>"
	. _T('info_non_envoi_annonce_dernieres_nouveautes')
	. "</td></tr>"
	. "\n<tr><td style='text-align: $spip_lang_left' class='verdana2'>"
	. bouton_radio("quoi_de_neuf", "oui", _T('bouton_radio_envoi_liste_nouveautes'), $quoi_de_neuf == "oui", "changeVisible(this.checked, 'config-neuf', 'block', 'none');");
	//	$res .= "<input type='radio' name='quoi_de_neuf' value='oui' id='quoi_de_neuf_on' checked='checked' />";
	//	$res .= " <b><label for='quoi_de_neuf_on'>"._T('bouton_radio_envoi_liste_nouveautes')."</label></b> ";

	if ($quoi_de_neuf == "oui") $style = "display: block;";
	else $style = "display: none;";			

	$res .= "<div id='config-neuf' style='$style'>"
	. "<ul>"
	. "<li>"._T('info_adresse')
	. "\n<input type='text' name='adresse_neuf' value='$adresse_neuf' size='30' class='fondl' />"
	. "</li><li>"._T('info_tous_les')
	. "\n<input type='text' name='jours_neuf' value='$jours_neuf' size='4' class='fondl' />\n"
	. _T('info_jours')
	. " &nbsp;  &nbsp;  &nbsp;\n<input type='submit' name='envoi_now' value='"
	. _T('info_envoyer_maintenant')
	. "' class='fondl' onclick='AjaxNamedSubmit(this)' />"
	. "</li></ul>"
	. "</div>";

	$res .= "<br />\n"
	. bouton_radio("quoi_de_neuf", "non", _T('info_non_envoi_liste_nouveautes'), $quoi_de_neuf == "non", "changeVisible(this.checked, 'config-neuf', 'none', 'block');");
		//$res .= "<br />\n<input type='radio' name='quoi_de_neuf' value='non' id='quoi_de_neuf_off' />";
		//$res .= " <label for='quoi_de_neuf_off'>"._T('info_non_envoi_liste_nouveautes')."</label> ";
	
	$res .= "</td></tr></table>\n"
	. fin_cadre_relief(true);

	$email_envoi = entites_html($GLOBALS['meta']["email_envoi"]);
	$titre =  _T('info_email_envoi');
	if ($email_envoi) $titre .= "&nbsp;:&nbsp;" . $email_envoi;
	$res .= "<br />\n"
	. debut_cadre_relief("", true, "", $titre)
	. "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"
	. "\n<tr><td class='verdana2'>"
	. _T('info_email_envoi_txt')
	. " <input type='text' name='email_envoi' value=\"$email_envoi\" size='20' class='fondl' />"
	. "</td></tr>"
	. "\n<tr><td>&nbsp;</td></tr></table>"
	. fin_cadre_relief(true);

	$res = debut_cadre_trait_couleur("", true, "", _T('info_envoi_email_automatique').aide ("confmails"))
	. ajax_action_post('configurer', 'annonces', 'config_contenu','',$res) 
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-annonces', '', $res);
}
?>