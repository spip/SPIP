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
include_spip('inc/actions');
include_spip('inc/texte');

// http://doc.spip.org/@inc_petitionner_dist
function inc_petitionner_dist($id_article, $script, $args, $ajax=false)
{
	global $spip_lang_right;

	$petition = sql_fetsel("*", "spip_petitions", "id_article=$id_article");

	if (!autoriser('modererpetition', 'article', $id_article))
		return petitionner_decompte($id_article, $petition);

	$choix = petitionner_choisir($petition);

	if ($petition) {
			$res = $choix
			. petitionner_decompte($id_article, $petition)
			. petitionner_params($petition)
			. petitionner_message($petition);
			$class = '';
	} else {
			$res = $choix;
			$class = $ajax ? '' : ' visible_au_chargement';
	}

	$atts = " class='$class' style='float: $spip_lang_right;' id='valider_petition'";

	$res = ajax_action_post('petitionner', $id_article, $script, $args, $res,_T('bouton_changer'), $atts);

	return ajax_action_greffe("petitionner", $id_article, $res);
}

// http://doc.spip.org/@petitionner_choisir
function petitionner_choisir($petition)
{
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

	return "<select name='change_petition' id='change_petition'
		class='spip_xx-small'
		onchange=\"\$('#valider_petition').css('visibility','visible');\"
		>\n$res</select><br />\n";
}

// http://doc.spip.org/@petitionner_decompte
function petitionner_decompte($id_article, $petition)
{
	$signatures = sql_countsel("spip_signatures", "id_article=$id_article");

	if (!$signatures) return '';

	return '<!-- visible -->' // message pour l'appelant
		. icone_horizontale(
			$signatures.'&nbsp;'. _T('info_signatures'),
			generer_url_ecrire("controle_petition", "id_article=$id_article",'', false),
			"suivi-petition-24.gif",
			"",
			false
		);
}

// http://doc.spip.org/@petitionner_message
function petitionner_message($petition)
{
	return "<br /><label for='texte_petition'>"._T('texte_descriptif_petition')."</label>&nbsp;:<br />"
	. "<textarea name='texte_petition' id='texte_petition' class='forml' rows='4' cols='10'>"
	. entites_html($petition["texte"])
	. "</textarea>\n";
}

// http://doc.spip.org/@petitionner_params
function petitionner_params($petition)
{
	$email_unique=$petition["email_unique"];
	$site_obli=$petition["site_obli"];
	$site_unique=$petition["site_unique"];
	$message=$petition["message"];

	$res = "<input type='checkbox' name='email_unique' id='emailunique' "
	  . (($email_unique=="oui")?"checked='checked' ":"")
	  . "/>"
	. " <label for='emailunique'>"._T('bouton_checkbox_signature_unique_email')."</label><br />";

	$res .= "<input type='checkbox' name='site_obli' id='siteobli' "
	  . ($site_obli=="oui"?"checked='checked'":"")
	  . " />";

	return $res
	. " <label for='siteobli'>"._T('bouton_checkbox_indiquer_site')."</label><br />"
	. "<input type='checkbox' name='site_unique' id='siteunique' "
	  . ($site_unique=="oui"?"checked='checked'":"")
	  . " />"
	. " <label for='siteunique'>"._T('bouton_checkbox_signature_unique_site')."</label><br />"
	. "<input type='checkbox' name='message' id='message' "
	  . ($message=="oui"?"checked='checked'":"")
	  . " />"
	. " <label for='message'>"._T('bouton_checkbox_envoi_message')."</label>";

}
?>
