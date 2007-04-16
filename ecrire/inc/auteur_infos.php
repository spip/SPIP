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


// Affiche la fiche de renseignements d'un auteur
// eventuellement editable
// http://doc.spip.org/@inc_auteur_infos_dist
function inc_auteur_infos_dist($auteur, $redirect) {
	if (!$auteur['id_auteur']) {
		if (_request('new') == 'oui') {
			$new = true;
		} else {
			include_spip('inc/headers');
			redirige_par_entete(generer_url_ecrire('auteurs'));
		}
	}

	if (!$new) {
		$corps = legender_auteur_voir($auteur, $redirect);
	} else
		$corps = '';

	if (_request('echec'))
		$corps .= afficher_erreurs_auteur(_request('echec'));


	// Calculer le bloc de statut (modifiable ou non selon)
	$instituer_auteur = charger_fonction('instituer_auteur', 'inc');
	$bloc_statut = $instituer_auteur($auteur);

	// Calculer le formulaire general
	if (autoriser('modifier', 'auteur', $auteur['id_auteur'])) {
		$corps = legender_auteur_saisir($auteur, $corps, $bloc_statut, $redirect);
	} else {
		// Indiquer le bloc statut (cas non modifiable) ?
		$corps .= $bloc_statut;
	}

	return $corps;

}

// http://doc.spip.org/@afficher_erreurs_auteur
function afficher_erreurs_auteur($echec) {
	foreach (split('@@@',$echec) as $e)
		$corps .= '<p>' . _T($e) . "</p>\n";

	$corps = debut_cadre_relief('', true)
	.  "<span style='color: red; left-margin: 5px'>"
	.  http_img_pack("warning.gif", _T('info_avertissement'), "style='width: 48px; height: 48px; float: left; margin: 5px;'")
	. $corps
	.  _T('info_recommencer')
	.  "</span>\n"
	. fin_cadre_relief(true);

	return $corps;
}


// http://doc.spip.org/@legender_auteur_saisir
function legender_auteur_saisir($auteur, $auteur_infos_voir, $bloc_statut, $redirect) {
	global $options, $connect_statut, $connect_id_auteur, $connect_toutes_rubriques;

	$id_auteur = $auteur['id_auteur'];



	$setmail = ($connect_statut == "0minirezo"
		AND ($connect_toutes_rubriques OR $auteur['statut']<>'0minirezo'));

	$setconnecte = ($connect_id_auteur == $id_auteur);


	// Elaborer le formulaire
	$corps = '';
	$corps .= _T('titre_cadre_signature_obligatoire')
	. "("
	. _T('entree_nom_pseudo')
	. ")<br />\n"
	. "<input type='text' name='nom' class='formo' size='40' value=\""
	. entites_html(sinon($auteur['nom'], _T('ecrire:item_nouvel_auteur')))
	. "\" "
	. (strlen($auteur['nom']) ? '' : ' onfocus="if(!antifocus){this.value=\'\';antifocus=true;}"')
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

	if ($options == "avancees"
	OR strlen($auteur['pgp'])) {
		$corps .= debut_cadre_enfonce("cadenas-24.gif", true, "", _T('entree_cle_pgp'))
		. "<textarea name='pgp' class='forml' rows='4' cols='40'>"
		. entites_html($auteur['pgp'])
		. "</textarea>\n"
		. fin_cadre_enfonce(true);
	}

	$corps .= "\n<br />";

	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		$corps .= extra_saisie($auteur['extra'], 'auteurs', $auteur['statut']);
	}

//
// Login et mot de passe :
// accessibles seulement aux admins non restreints et l'auteur lui-meme
//

	if (($auteur['source'] != 'spip') AND $GLOBALS['ldap_present']) {
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

	$corps .= fin_cadre_relief(true);


	
	//
	// Retour
	//

	$corps = $auteur_infos_voir
		. "<div id='auteur_infos_edit'>\n"
		. '<div>&nbsp;</div>'
		. "\n<div class='serif'>"
		. debut_cadre_relief("fiche-perso-24.gif",
			true, "", _T("icone_informations_personnelles"))
		. $corps
		. fin_cadre_relief(true)
		. (!$setconnecte ? '' : apparait_auteur_infos($id_auteur, $auteur))
		. "</div>\n" # /serif
		. "</div>\n"; # /auteur_infos_edit

	// Installer la fiche "auteur_infos_voir"
	// et masquer le formulaire si on n'en a pas besoin
	$new = ($auteur_infos_voir == '');
	if (!$new
	AND !_request('echec')
	AND !_request('edit')) {
		$corps .= "<script>jQuery('#auteur_infos_edit').hide()</script>\n";
	} else {
		$corps .= "<script>jQuery('#auteur_infos_voir').hide()</script>\n";
	}


	// Formulaire de statut
	$corps .= $bloc_statut;


	// Lier a un article (creation d'un auteur depuis un article)
	if ($id_article = intval(_request('lier_id_article')))
		$corps .= "<input type='hidden' name='lier_id_article' value='$id_article' />\n";

	// Redirection apres enregistrement ?
	if ($redirect)
		$corps .= "<input type='hidden' name='redirect' value=\"".attribut_html($redirect)."\" />\n";

	$corps .= "<div style='text-align: right'><input type='submit' value='"._T('bouton_enregistrer')."' class='fondo' /></div>";


	$arg = intval($id_auteur);
	$ret .= generer_action_auteur('editer_auteur', $arg, $redirect, $corps, ' method="POST"');

	$ret .= fin_cadre_relief(true)
		. '</div>';


	return $ret;
}

//
// Apparaitre dans la liste des redacteurs connectes
//

// http://doc.spip.org/@apparait_auteur_infos
function apparait_auteur_infos($id_auteur, $auteur) {

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

	return 
		debut_cadre_enfonce("messagerie-24.gif", true, "", _T('info_liste_redacteurs_connectes'))
		. "\n<div>"
		. _T('texte_auteur_messagerie')
		. "</div>"
		. $res
		. fin_cadre_enfonce(true)
		. "<br />\n";
}


// http://doc.spip.org/@legender_auteur_voir
function legender_auteur_voir($auteur) {
	global $connect_toutes_rubriques, $connect_statut, $connect_id_auteur, $champs_extra, $options, $spip_lang_right;

	if (!$id_auteur = $auteur['id_auteur']) {
		$new = true;
	}

	$res .= "<div id='auteur_infos_voir'>";

	$res = "<table width='100%' cellpadding='0' border='0' cellspacing='0'>"
	. "<tr>"
	. "<td  style='width: 100%' valign='top'>"
	. gros_titre(
		sinon($auteur['nom'],_T('item_nouvel_auteur')),
		'',false);


	$res .= "<div>&nbsp;</div>";

	if (strlen($auteur['email']))
		$res .= "<div>"._T('email_2')
			." <b><a href='mailto:".htmlspecialchars($auteur['email'])."'>"
			.$auteur['email']."</a></b></div>";

	if ($auteur['url_site']) {
		if (!$auteur['nom_site'])
			$auteur['nom_site'] = _T('info_site');
		$res .= propre(_T('info_site_2')." [{{".$auteur['nom_site']."}}->".$auteur['url_site']."]");
	}

	// Bouton "modifier" ?
	if (autoriser('modifier', 'auteur', $id_auteur)) {
		$res .= "</td>\n<td id='bouton_modifier_auteur'>";

		if (_request('edit') == 'oui') {
			$clic = _T('icone_retour');
			$retour = _T('admin_modifier_auteur');
		} else {
			$clic = _T('admin_modifier_auteur');
			$retour = _T('icone_retour');
		}

		$h = generer_url_ecrire("auteur_infos","id_auteur=$id_auteur&edit=oui");
		$h = "<a\nhref='$h'>$clic</a>";
		$res .= icone($clic, $h, "redacteurs-24.gif", "edit.gif", '', '',true);

		$res .= "<script type='text/javascript'><!--
		var intitule_bouton = "._q($retour).";
		jQuery('#bouton_modifier_auteur a')
		.click(function() {
			jQuery('#auteur_infos_edit')
			.toggle();
			jQuery('#auteur_infos_voir')
			.toggle();
			jQuery('#bouton_modifier_auteur a span')
			.each(function(){
				var tmp = jQuery(this).html();
				jQuery(this).html(intitule_bouton);
				intitule_bouton = tmp;
			});
			return false;
		});
		// --></script>\n";
		$res .= "</td><tr><td colspan='2'>\n";
	}

	if (strlen($auteur['bio'])) {
		$res .= propre("<quote>".$auteur['bio']."</quote>");
	}

	if (strlen($auteur['pgp'])) {
		$res .= propre("PGP: <cadre>".$auteur['pgp']."</cadre>");
	}

	if ($GLOBALS['champs_extra'] AND $auteur['extra']) {
		include_spip('inc/extra');
		$res .= extra_affichage($auteur['extra'], 'auteurs');
	}

	$res .= "</td></tr></table>";

	$res .= "</div>\n";

	return $res;

}

?>
