<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaire_petitionner($id_article, $script, $args, $ajax=false)
{
	global $spip_lang_right;

	$petition = spip_fetch_array(spip_query("SELECT * FROM spip_petitions WHERE id_article=$id_article"));

	$email_unique=$petition["email_unique"];
	$site_obli=$petition["site_obli"];
	$site_unique=$petition["site_unique"];
	$message=$petition["message"];
	$texte_petition=$petition["texte"];

	if ($petition) {
		$menu = array(
			'on' => _T('bouton_radio_petition_activee'),
			'off'=> _T('bouton_radio_supprimer_petition')
		);
		$val_menu = 'on';
	} else {
		$menu = array(
			'off'=> _T('bouton_radio_pas_petition'),
			'on' => _T('bouton_radio_activer_petition')
		);
		$val_menu = 'off';
	}

	$res = '';
	foreach ($menu as $val => $desc) {
		$res .= "<option" . (($val_menu == $val) ? " selected" : '') . " value='$val'>".$desc."</option>\n";
	}

	$res = "<select name='change_petition'
		class='fondl' style='font-size:10px;'
		onChange=\"setvisibility('valider_petition', 'visible');\"
		>\n$res</select>\n";


	if ($petition) {
		$nb_signatures = spip_fetch_array(spip_query("SELECT COUNT(*) AS count FROM spip_signatures WHERE id_article=$id_article AND statut IN ('publie', 'poubelle')"));
		$nb_signatures = $nb_signatures['count'];
		if ($nb_signatures) {
			$res .= "<br />\n" .
			  icone_horizontale($nb_signatures.'&nbsp;'. _T('info_signatures'), generer_url_ecrire("controle_petition","id_article=$id_article",'', false), "suivi-petition-24.gif", "", false);
		}

		$res .= "<br />\n";

		if ($email_unique=="oui")
			$res .= "<input type='checkbox' name='email_unique' id='emailunique' checked='checked'>";
		else
			$res .="<input type='checkbox' name='email_unique'  id='emailunique'>";
		$res .=" <label for='emailunique'>"._T('bouton_checkbox_signature_unique_email')."</label><BR>";
		if ($site_obli=="oui")
			$res .="<input type='checkbox' name='site_obli' id='siteobli' checked='checked'>";
		else
			$res .="<input type='checkbox' name='site_obli'  id='siteobli'>";
		$res .=" <label for='siteobli'>"._T('bouton_checkbox_indiquer_site')."</label><BR>";
		if ($site_unique=="oui")
			$res .="<input type='checkbox' name='site_unique' id='siteunique' checked='checked'>";
		else
			$res .="<input type='checkbox' name='site_unique'  id='siteunique'>";
		$res .=" <label for='siteunique'>"._T('bouton_checkbox_signature_unique_site')."</label><BR>";
		if ($message=="oui")
			$res .="<input type='checkbox' name='message' id='message' checked='checked'>";
		else
			$res .="<input type='checkbox' name='message'  id='message' />";
		$res .=" <label for='message'>"._T('bouton_checkbox_envoi_message')."</label>";

		$res .=_T('texte_descriptif_petition')."&nbsp;:<BR />";
		$res .="<TEXTAREA NAME='texte_petition' CLASS='forml' ROWS='4' COLS='10' wrap=soft>";
		$res .=entites_html($texte_petition);
		$res .="</TEXTAREA>\n";

		$res .="<span align='$spip_lang_right'>";
	} else $res .="<span class='visible_au_chargement' id='valider_petition'>";
	$res .="<input type='submit' CLASS='fondo' VALUE='"._T('bouton_changer')."' STYLE='font-size:10px' />";
	$res .="</span>";

	return ajax_action_auteur('petitionner', $id_article, $res, $script, $args, $args);
}
?>
