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


// http://doc.spip.org/@formulaire_auteur_infos
function inc_legender_auteur($id_auteur, $auteur, $initial, $ajouter_id_article, $redirect)
{
	global $connect_statut, $connect_toutes_rubriques,$connect_id_auteur, $options, $champs_extra  ;

	$onfocus = $initial ? '' : " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";

	$corps = generer_url_post_ecrire('auteur_infos', (!$id_auteur ? "" : "id_auteur=$id_auteur"));

	if ($ajouter_id_article)
		$corps .= "<input name='ajouter_id_article' value='$ajouter_id_article' type='hidden'>\n"
		. "\n<input name='redirect' value='$redirect' type='hidden' />";
	$corps .= "\n<div class='serif'>"
	. debut_cadre_relief("fiche-perso-24.gif", true, "", _T("icone_informations_personnelles"))
	. _T('titre_cadre_signature_obligatoire')
	. "("._T('entree_nom_pseudo').")<br />\n"
	. "<input type='text' name='nom' class='formo' value=\""
	. entites_html($auteur['nom'])
	. "\" size='40' $onfocus />\n<p>"
	. "<b>"._T('entree_adresse_email')."</b>";

	if ($connect_statut == "0minirezo"
	AND ($connect_toutes_rubriques OR $auteur['statut']<>'0minirezo')) {
		$corps .= "<br /><input type='text' name='email' class='formo' value=\"".entites_html($auteur['email'])."\" size='40' />\n<p>\n";
	} else {
		$corps .= "&nbsp;: <tt>".$auteur['email']."</tt>"
		. "<br>("._T('info_reserve_admin').")\n"
		. "\n<p>";
	}

	$corps .= "<b>"._T('entree_infos_perso')."</b><br />\n"
	. "("._T('entree_biographie').")<br />\n"
	. "<textarea name='bio' class='forml' rows='4' cols='40' wrap=soft>"
	. entites_html($auteur['bio'])
	. "</textarea>\n"
	. debut_cadre_enfonce("site-24.gif", true, "", _T('info_site_web'))
	. "<b>"._T('entree_nom_site')."</b><br />\n"
	. "<input type='text' name='nom_site_auteur' class='forml' value=\""
	. entites_html($auteur['nom_site'])
	. "\" size='40'><P>\n"
	. "<b>"
	. _T('entree_url')
	. "</b><br />\n"
	. "<input type='text' name='url_site' class='forml' value=\""
	. entites_html($auteur['url_site'])
	. "\" size='40'>\n"
	. fin_cadre_enfonce(true)
	. "\n<p>";

	if ($options == "avancees") {
		$corps .= debut_cadre_enfonce("cadenas-24.gif", true, "", _T('entree_cle_pgp'))
		. "<textarea name='pgp' class='forml' rows='4' cols='40' wrap=soft>"
		. entites_html($auteur['pgp'])
		. "</textarea>\n"
		. fin_cadre_enfonce(true)
		. "\n<p>";
	} else {
		$corps .= "<input type='hidden' name='pgp' value=\""
		. entites_html($auteur['pgp'])
		. "\" />";
	}

	$corps .= "\n<p>";

	if ($champs_extra) {
		include_spip('inc/extra');
		$corps .= extra_saisie($auteur['extra'], 'auteurs', $auteur['statut'],'', false);
	}

//
// Login et mot de passe :
// accessibles seulement aux admins non restreints et l'auteur lui-meme
//

if ($auteur['source'] != 'spip') {
	$edit_login = false;
	$edit_pass = false;
}
else if (($connect_statut == "0minirezo") AND $connect_toutes_rubriques) {
	$edit_login = true;
	$edit_pass = true;
}
else if ($connect_id_auteur == $id_auteur) {
	$edit_login = false;
	$edit_pass = true;
}
else {
	$edit_login = false;
	$edit_pass = false;
}

	$corps .= debut_cadre_relief("base-24.gif", true);

// Avertissement en cas de modifs de ses propres donnees
	if (($edit_login OR $edit_pass) AND $connect_id_auteur == $id_auteur) {
		$corps .= debut_cadre_enfonce(true)
		.  http_img_pack("warning.gif", _T('info_avertissement'), "width='48' height='48' align='right'")
		. "<b>"._T('texte_login_precaution')."</b>\n"
		. fin_cadre_enfonce(true)
		. "\n<p>";
	}

// Un redacteur n'a pas le droit de modifier son login !
	if ($edit_login) {
		$corps .= "<b>"._T('item_login')."</b> "
		. "<font color='red'>("._T('texte_plus_trois_car').")</font> :<br />\n"
		. "<input type='text' name='new_login' class='formo' value=\"".entites_html($auteur['login'])."\" size='40' /><p>\n";
	} else {
		$corps .= "<fieldset style='padding:5'><legend><B>"._T('item_login')."</B><br />\n</legend><br><b>".$auteur['login']."</b> "
		. "<i> ("._T('info_non_modifiable').")</i>\n<p>";
	}

// On ne peut modifier le mot de passe en cas de source externe (par exemple LDAP)
	if ($edit_pass) {
		$res = "<b>"._T('entree_nouveau_passe')."</b> "
		. "<font color='red'>("._T('info_plus_cinq_car').")</font> :<br />\n"
		. "<input type='password' name='new_pass' class='formo' value=\"\" size='40' /><br />\n"
		. _T('info_confirmer_passe')."<br />\n"
		. "<input type='password' name='new_pass2' class='formo' value=\"\" size='40'><p>\n";
		$corps .= $res;
	}
	$corps .= fin_cadre_relief(true);

	$res = "<p />";

	if ($GLOBALS['connect_id_auteur'] == $id_auteur)
		$res .= apparait_auteur_infos($id_auteur, $auteur);

	$res .= "\n<div align='right'>"
	. "\n<input type='submit' class='fondo' value='"
	. _T('bouton_enregistrer')
	. "'></div>"

	. pipeline('affiche_milieu',
		array('args' => array(
			'exec'=>'auteur_infos',
			'id_auteur'=>$id_auteur),
			'data'=>''));

	$corps .= $res
	. "</div>"
	. fin_cadre_relief(true);

	return $corps;
}

//
// Apparaitre dans la liste des redacteurs connectes
//

// http://doc.spip.org/@apparait_auteur_infos
function apparait_auteur_infos($id_auteur, $auteur)
{

	if ($auteur['imessage']=="non"){
		$res = "<input type='radio' name='perso_activer_imessage' value='oui' id='perso_activer_imessage_on'>"
		. " <label for='perso_activer_imessage_on'>"._T('bouton_radio_apparaitre_liste_redacteurs_connectes')."</label> "
		. "<br />\n<input type='radio' name='perso_activer_imessage' value='non' checked id='perso_activer_imessage_off'>"
		. " <b><label for='perso_activer_imessage_off'>"._T('bouton_radio_non_apparaitre_liste_redacteurs_connectes')."</label></b> ";
	} else {
		$res = "<input type='radio' name='perso_activer_imessage' value='oui' id='perso_activer_imessage_on' checked>"
		. " <b><label for='perso_activer_imessage_on'>"
		. _T('bouton_radio_apparaitre_liste_redacteurs_connectes')
		. "</label></b> "
		. "<br />\n<input type='radio' name='perso_activer_imessage' value='non' id='perso_activer_imessage_off'>"
		. " <label for='perso_activer_imessage_off'>"
		. _T('bouton_radio_non_apparaitre_liste_redacteurs_connectes')
		. "</label> ";
	}

	return debut_cadre_relief("messagerie-24.gif", true, "", _T('info_liste_redacteurs_connectes'))
	. "\n<div>"
	. _T('texte_auteur_messagerie')
	. "</div>"
	. $res
	. fin_cadre_relief(true)
	. "<p />";
}
?>
