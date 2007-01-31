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
include_spip('inc/actions');
include_spip('inc/texte');

// http://doc.spip.org/@inc_petitionner_dist
function inc_petitionner_dist($id_article, $script, $args)
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

	$res = "";
	foreach ($menu as $val => $desc) {
		$res .= "<option" . (($val_menu == $val) ? " selected='selected'" : '') . " value='$val'>".$desc."</option>\n";
	}

	$res = "<select name='change_petition'
		class='fondl spip_xx-small'
		onchange=\"setvisibility('valider_petition', 'visible');\"
		>\n$res</select><br />\n";


	if ($petition) {
		$nb_signatures = spip_fetch_array(spip_query("SELECT COUNT(*) AS count FROM spip_signatures WHERE id_article=$id_article AND statut IN ('publie', 'poubelle')"));
		$nb_signatures = $nb_signatures['count'];
		if ($nb_signatures) {
			$res .= '<!-- visible -->' // message pour l'appelant
			. icone_horizontale(
				$nb_signatures.'&nbsp;'. _T('info_signatures'),
				generer_url_ecrire("controle_petition", "id_article=$id_article",'', false),
				"suivi-petition-24.gif",
				"",
				false
			);
		}

		if ($email_unique=="oui")
			$res .= "<input type='checkbox' name='email_unique' id='emailunique' checked='checked' />";
		else
			$res .="<input type='checkbox' name='email_unique'  id='emailunique' />";
		$res .=" <label for='emailunique'>"._T('bouton_checkbox_signature_unique_email')."</label><br />";
		if ($site_obli=="oui")
			$res .="<input type='checkbox' name='site_obli' id='siteobli' checked='checked' />";
		else
			$res .="<input type='checkbox' name='site_obli'  id='siteobli' />";
		$res .=" <label for='siteobli'>"._T('bouton_checkbox_indiquer_site')."</label><br />";
		if ($site_unique=="oui")
			$res .="<input type='checkbox' name='site_unique' id='siteunique' checked='checked' />";
		else
			$res .="<input type='checkbox' name='site_unique'  id='siteunique' />";
		$res .=" <label for='siteunique'>"._T('bouton_checkbox_signature_unique_site')."</label><br />";
		if ($message=="oui")
			$res .="<input type='checkbox' name='message' id='message' checked='checked' />";
		else
			$res .="<input type='checkbox' name='message'  id='message' />";
		$res .=" <label for='message'>"._T('bouton_checkbox_envoi_message')."</label>";

		$res .= "<br />"._T('texte_descriptif_petition')."&nbsp;:<br />";
		$res .="<textarea name='texte_petition' class='forml' rows='4' cols='10'>";
		$res .=entites_html($texte_petition);
		$res .="</textarea>\n";
		$class = '';
	} else $class =" visible_au_chargement";

	$atts = " class='fondo spip_xx-small$class' style='float: $spip_lang_right;' id='valider_petition'";

	$res = ajax_action_post('petitionner', $id_article, $script, $args, $res,_T('bouton_changer'), $atts);

	return ajax_action_greffe("petitionner-$id_article", $res);
}
?>
