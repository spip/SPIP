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


// http://doc.spip.org/@inc_legender_auteur_dist
function inc_legender_auteur_dist($id_auteur, $auteur, $mode, $echec='', $redirect='')
{
	$corps = (($mode < 0) OR !statut_modifiable_auteur($id_auteur, $auteur))
	? legender_auteur_voir($auteur, $redirect)
	: legender_auteur_saisir($id_auteur, $auteur, $mode, $echec, $redirect);
	
	return  $redirect ? $corps :
	  ajax_action_greffe("legender_auteur-$id_auteur", $corps);

}

// http://doc.spip.org/@legender_auteur_saisir
function legender_auteur_saisir($id_auteur, $auteur, $mode, $echec='', $redirect='')
{
	global $options, $connect_statut, $connect_id_auteur, $connect_toutes_rubriques;
	$corps = '';

	if ($echec){

		foreach (split('@@@',$echec) as $e)
			$corps .= '<p>' . _T($e) . "</p>\n";
		
		$corps = debut_cadre_relief('', true)
		.  "<span style='color: red; left-margin: 5px'>"
		.  http_img_pack("warning.gif", _T('info_avertissement'), "style='width: 48px; height: 48px; float: left; margin: 5px;'")
		. $corps
		.  _T('info_recommencer')
		.  "</span>\n"
		. fin_cadre_relief(true);
	}

	$setmail = ($connect_statut == "0minirezo"
		AND ($connect_toutes_rubriques OR $auteur['statut']<>'0minirezo'));

	$setconnecte = ($connect_id_auteur == $id_auteur);

	$corps .= _T('titre_cadre_signature_obligatoire')
	. "("
	. _T('entree_nom_pseudo')
	. ")<br />\n"
	. "<input type='text' name='nom' class='formo' size='40' value=\""
	. entites_html($auteur['nom'])
	. "\" "
	. (!$mode ? '' : ' onfocus="if(!antifocus){this.value=\'\';antifocus=true;}"')
	. " />\n<br />"
	. "<b>"._T('entree_adresse_email')."</b>";

	if ($setmail) {
		$corps .= "<br /><input type='text' name='email' class='formo' size='40' value=\""
		. entites_html($auteur['email'])
		. "\"  />\n<br />\n";
	} else {
		$corps .= "&nbsp;: <tt>".$auteur['email']."</tt>"
		. "<br />("._T('info_reserve_admin').")\n"
		. "\n<br />";
	}

	$corps .= "<b>"._T('entree_infos_perso')."</b><br />\n"
	. "("._T('entree_biographie')
	. ")<br />\n"
	. "<textarea name='bio' class='forml' rows='4' cols='40'>"
	. entites_html($auteur['bio'])
	. "</textarea><br />\n"
	. debut_cadre_enfonce("site-24.gif", true, "", _T('info_site_web'))
	. "<b>"._T('entree_nom_site')."</b><br />\n"
	. "<input type='text' name='nom_site_auteur' class='forml' value=\""
	. entites_html($auteur['nom_site'])
	. "\" size='40' /><br />\n"
	. "<b>"
	. _T('entree_url')
	. "</b><br />\n"
	. "<input type='text' name='url_site' class='forml' value=\""
	. entites_html($auteur['url_site'])
	. "\" size='40' />\n"
	. fin_cadre_enfonce(true)
	. "\n<br />";

	if ($options == "avancees") {
		$corps .= debut_cadre_enfonce("cadenas-24.gif", true, "", _T('entree_cle_pgp'))
		. "<textarea name='pgp' class='forml' rows='4' cols='40'>"
		. entites_html($auteur['pgp'])
		. "</textarea>\n"
		. fin_cadre_enfonce(true);
	} else {
		$corps .= "<input type='hidden' name='pgp' value=\""
		. entites_html($auteur['pgp'])
		. "\" />";
	}

	$corps .= "\n<br />";

	if ($champs_extra) {
		include_spip('inc/extra');
		$corps .= extra_saisie($auteur['extra'], 'auteurs', $auteur['statut']);
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
		$corps .= debut_cadre_enfonce('', true)
		.  http_img_pack("warning.gif", _T('info_avertissement'), 
				 "style='width: 48px; height: 48px; float: right;margin: 5px;'")
		. "<b>"._T('texte_login_precaution')."</b>\n"
		. fin_cadre_enfonce(true)
		. "\n<br />";
	}

// Un redacteur n'a pas le droit de modifier son login !
	if ($edit_login) {
		$corps .= "<b>"._T('item_login')."</b> "
		. "<span style='color: red'>("._T('texte_plus_trois_car').")</span> :<br />\n"
		. "<input type='text' name='new_login' class='formo' value=\"".entites_html($auteur['login'])."\" size='40' /><br />\n";
	} else {
		$corps .= "<fieldset style='padding:5'><legend><b>"._T('item_login')."</b><br />\n</legend><br /><b>".$auteur['login']."</b> "
		. "<i> ("._T('info_non_modifiable').")</i>\n<br />";
	}

// On ne peut modifier le mot de passe en cas de source externe (par exemple LDAP)
	if ($edit_pass) {
		$res = "<b>"._T('entree_nouveau_passe')."</b> "
		. "<span style='color: red'>("._T('info_plus_cinq_car').")</span> :<br />\n"
		. "<input type='password' name='new_pass' class='formo' value=\"\" size='40' /><br />\n"
		. _T('info_confirmer_passe')."<br />\n"
		. "<input type='password' name='new_pass2' class='formo' value=\"\" size='40' /><br />\n";
		$corps .= $res;
	}

	$corps .= fin_cadre_relief(true)
	. "<br />"
	. (!$setconnecte ? '' : apparait_auteur_infos($id_auteur, $auteur))
	. "\n<div align='right'>"
	. "\n<input type='submit' class='fondo' value='"
	. _T('bouton_enregistrer')
	. "' /></div>";

	$arg = intval($id_auteur) . '/';

	return '<div>&nbsp;</div>'
	. "\n<div class='serif'>"
	. debut_cadre_relief("fiche-perso-24.gif", true, "", _T("icone_informations_personnelles"))
	. ($redirect
	     ? generer_action_auteur('legender_auteur', $arg, $redirect, $corps)
	   : ajax_action_auteur('legender_auteur', $arg, 'auteur_infos', "id_auteur=$id_auteur&initial=-1&retour=$redirect", $corps))
	. fin_cadre_relief(true)
	. '</div>';
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
		. "<br />\n<input type='radio' name='perso_activer_imessage' value='non' checked='checked' id='perso_activer_imessage_off'>"
		. " <b><label for='perso_activer_imessage_off'>"._T('bouton_radio_non_apparaitre_liste_redacteurs_connectes')."</label></b> ";
	} else {
		$res = "<input type='radio' name='perso_activer_imessage' value='oui' id='perso_activer_imessage_on' checked='checked'>"
		. " <b><label for='perso_activer_imessage_on'>"
		. _T('bouton_radio_apparaitre_liste_redacteurs_connectes')
		. "</label></b> "
		. "<br />\n<input type='radio' name='perso_activer_imessage' value='non' id='perso_activer_imessage_off'>"
		. " <label for='perso_activer_imessage_off'>"
		. _T('bouton_radio_non_apparaitre_liste_redacteurs_connectes')
		. "</label> ";
	}

	return 	debut_cadre_formulaire('', true)
	. debut_cadre_relief("messagerie-24.gif", true, "", _T('info_liste_redacteurs_connectes'))
	. "\n<div>"
	. _T('texte_auteur_messagerie')
	. "</div>"
	. $res
	. fin_cadre_relief(true)
	. "<p />"
	. fin_cadre_formulaire(true);
}


// http://doc.spip.org/@legender_auteur_voir
function legender_auteur_voir($auteur, $redirect)
{
	global $connect_toutes_rubriques, $connect_statut, $connect_id_auteur, $champs_extra, $options,$spip_lang_right ;

	$id_auteur=$auteur['id_auteur'];
	$nom=$auteur['nom'];
	$bio=$auteur['bio'];
	$email=$auteur['email'];
	$nom_site_auteur=$auteur['nom_site'];
	$url_site=$auteur['url_site'];
	$statut=$auteur['statut'];
	$pgp=$auteur["pgp"];
	$extra = $auteur["extra"];

	$res = "<table width='100%' cellpadding='0' border='0' cellspacing='0'>"
	. "<tr>"
	. "<td  style='width: 100%' valign='top'>"
	. gros_titre($nom,'',false)
	. "<div>&nbsp;</div>";

	if (strlen($email) > 2)
		$res .= "<div>"._T('email_2')." <b><a href='mailto:$email'>$email</a></b></div>";

	if ($url_site) {
		if (!$nom_site_auteur) $nom_site_auteur = _T('info_site');
		$res .= propre(_T('info_site_2')." [{{".$nom_site_auteur."}}->".$url_site."]");
	}
		
	$res .= "</td>"
	.  "<td>";
	
	if (statut_modifiable_auteur($id_auteur, $auteur)) {
		$ancre = "legender_auteur-$id_auteur";
		$clic = _T("admin_modifier_auteur");
		$h = generer_url_ecrire("auteur_infos","id_auteur=$id_auteur&initial=0");
		if ((_SPIP_AJAX === 1 ) AND !$redirect) {
		  $evt = "\nonclick=" . ajax_action_declencheur($h,$ancre);
		  $h = "<a\nhref='$h#$ancre'$evt>$clic</a>";
		}
	  $res .= icone($clic, $h, "redacteurs-24.gif", "edit.gif", '', '',true);
	}
	$res .= "</td></tr></table>";

	if (strlen($bio) > 0) { $res .= "<div>".propre("<quote>".$bio."</quote>")."</div>"; }
	if (strlen($pgp) > 0) { $res .= "<div>".propre("PGP:<cadre>".$pgp."</cadre>")."</div>"; }

	if ($champs_extra AND $extra) {
		include_spip('inc/extra');
		$res .= extra_affichage($extra, "auteurs");
	}

	return $res;

}

// http://doc.spip.org/@statut_modifiable_auteur
function statut_modifiable_auteur($id_auteur, $auteur)
{
	global $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;

// on peut se changer soi-meme
	  return  (($connect_id_auteur == $id_auteur) ||
  // sinon on doit etre admin
  // et pas admin restreint pour changer un autre admin ou creer qq
		(($connect_statut == "0minirezo") &&
		 ($connect_toutes_rubriques OR 
		  ($id_auteur AND ($auteur['statut'] != "0minirezo")))));
}

?>
