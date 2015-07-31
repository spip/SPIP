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

// Affiche la fiche de renseignements d'un auteur
// eventuellement editable
// $quoi introduit pour pouvoir demander simplement les infos ou la partie editable
// ""=>tout, "infos"=>infos simplement, "edit"=>formulaire d'edition simplement
// http://doc.spip.org/@inc_auteur_infos_dist
function inc_auteur_infos_dist($auteur, $new, $echec, $edit, $id_article, $redirect, $quoi="") {

	if (!$new AND $quoi!="edit") {
		$infos = legender_auteur_voir($auteur);
	} else
		$infos = '';

	$id_auteur = intval($auteur['id_auteur']);

	if (
	  	(!$auth = autoriser('modifier', 'auteur', $id_auteur,null))
		OR $quoi=='infos') {
		if ($quoi!='edit' AND $auth){
			// Formulaire de statut
			// Calculer le bloc de statut (non modifiable)
			// il n'est pas possible d'avoir 2 blocs de statut modifiables dans la meme page
			// car le plonguer de rubrique repose sur son unicite -> a reecrire
			$instituer_auteur = charger_fonction('instituer_auteur', 'inc');
			$bloc_statut = $instituer_auteur($auteur, false);
			$bloc_statut = $bloc_statut? "<div class='statut'>$bloc_statut</div>":$bloc_statut.' ';
		} else {
			$bloc_statut = "";
		}
		return $infos . $bloc_statut;
	}

	
	// Elaborer le formulaire
	$corps = "<div id='auteur_infos_edit'>\n";

	$editer = ($new=='oui');
	if ($editer&$redirect) {
		$retour = rawurldecode($redirect);
	} elseif ($id_auteur){
		$retour = generer_url_ecrire('auteur_infos','id_auteur='.$id_auteur, false, true);
	} else {
		$retour = "";
	}

	$contexte = array(
		'icone_retour'=>($retour)?icone_inline(_T('icone_retour'),$retour,"auteur-24.gif","rien.gif",$GLOBALS['spip_lang_left'],false,($editer&$redirect)?"":" onclick=\"jQuery('#auteur_infos_edit').hide();jQuery('#auteur-voir').show();return false;\""):"",
		'redirect'=>$redirect?rawurldecode($redirect):generer_url_ecrire('auteur_infos','id_auteur='.$id_auteur, '&',true),
		'titre'=>($auteur['nom']?$auteur['nom']:_T('nouvel_auteur')),
		'new'=>$new == "oui"?$new:$id_auteur,
		'config_fonc'=>'auteurs_edit_config',
		'lier_id_article' => $id_article,
		'auteur' => $auteur
	);

	$corps .= recuperer_fond("prive/editer/auteur", $contexte);
	$corps .= '</div>';

	// ajouter les infos, si l'on ne demande pas simplement le formulaire d'edition
	if ($quoi!="edit") {
		$corps =  $infos . $corps;
	}

	// Installer la fiche "auteur_infos_voir"
	// et masquer le formulaire si on n'en a pas besoin

	if (!$new AND !$echec AND !$edit) {
	  $corps .= http_script("if (jQuery('#auteur_infos_edit span.erreur_message,#auteur_infos_edit .reponse_formulaire_erreur').length){jQuery('#auteur-voir').hide();}else{jQuery('#auteur_infos_edit').hide();}");
	} else {
	  $corps .= http_script("jQuery('#auteur-voir').hide();");
	}

	return $corps;
}

// http://doc.spip.org/@afficher_erreurs_auteur
function afficher_erreurs_auteur($echec) {
	foreach (explode('@@@',$echec) as $e)
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
	global $spip_lang_right;
	$res = "";

	$id_auteur = $auteur['id_auteur'];

	// Bouton "modifier" ?
	if (autoriser('modifier', 'auteur', $id_auteur)) {
		$res .= "<span id='bouton_modifier_auteur'>";

		if (_request('edit') == 'oui') {
			$clic = _T('icone_retour');
			$retour = _T('admin_modifier_auteur');
		} else {
			$clic = _T('admin_modifier_auteur');
			$retour = _T('icone_retour');
		}

		$h = generer_url_ecrire("auteur_infos","id_auteur=$id_auteur&edit=oui");
		$h = "<a\nhref='$h'>$clic</a>";
		$res .= icone_inline($clic, $h, "redacteurs-24.gif", "edit.gif", $spip_lang_right);

		$res .= http_script("
		var intitule_bouton = "._q($retour).";
		jQuery('#bouton_modifier_auteur a')
		.click(function() {
			jQuery('#auteur_infos_edit')
			.toggle();
			jQuery('#auteur-voir')
			.toggle();
			return false;
		});");
		$res .= "</span>\n";
	}
	
	$res .= gros_titre(
		sinon($auteur['nom'],_T('item_nouvel_auteur')),
		'',false);

	$res .= "<div class='nettoyeur'></div>";

	$contenu_auteur = "";
	if (strlen($auteur['email']))
		$contenu_auteur .= "<div>"._T('email_2')
			." <b><a href='mailto:".htmlspecialchars($auteur['email'])."'>"
			.$auteur['email']."</a></b></div>";
	// message d'information d'envoi d'email pour modif et de confirmation
	// on ne fait ici qu'informer, sans aucune action
	if ($email = _request('email_confirm')){
		$contenu_auteur .= "<p><strong>"._T('form_auteur_envoi_mail_confirmation',array('email'=>$email))."</strong></p>";
	}
	elseif (_request('email_modif')==='ok'){
		$contenu_auteur .= "<p><strong>"._T('form_auteur_email_modifie')."</strong></p>";
	}

	if ($auteur['url_site']) {
		if (!$auteur['nom_site'])
			$auteur['nom_site'] = _T('info_site');
		$contenu_auteur .= propre(_T('info_site_2')." [{{".$auteur['nom_site']."}}->".$auteur['url_site']."]");
	}

	if (strlen($auteur['bio'])) {
		$contenu_auteur .= propre("<quote>".$auteur['bio']."</quote>");
	}

	if (strlen($auteur['pgp'])) {
		$contenu_auteur .= propre("PGP: <cadre>".$auteur['pgp']."</cadre>");
	}

	$contexte = array('id'=>$id_auteur);
	// permettre aux plugin de faire des modifs ou des ajouts
	$contenu_auteur = pipeline('afficher_contenu_objet',
		array(
			'args'=>array(
				'type'=>'auteur',
				'id_objet'=>$id_auteur,
				'contexte'=>$contexte
			),
			'data'=> $contenu_auteur
		)
	);

	$res .= "<div id='auteur_infos_voir'>$contenu_auteur</div>\n";

	return $res;

}

?>
