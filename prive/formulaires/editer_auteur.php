<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
include_spip('inc/editer');
include_spip('inc/filtres_ecrire'); // si on utilise le formulaire dans le public

// http://doc.spip.org/@inc_editer_mot_dist
function formulaires_editer_auteur_charger_dist($id_auteur='new', $retour='', $associer_objet='', $config_fonc='auteurs_edit_config', $row=array(), $hidden=''){
	$valeurs = formulaires_editer_objet_charger('auteur',$id_auteur,0,0,$retour,$config_fonc,$row,$hidden);
	$valeurs['new_login'] = $valeurs['login'];

	return $valeurs;
}

/**
 * Identifier le formulaire en faisant abstraction des parametres qui
 * ne representent pas l'objet edite
 */
function formulaires_editer_auteur_identifier_dist($id_auteur='new', $retour='', $associer_objet='', $config_fonc='auteurs_edit_config', $row=array(), $hidden=''){
	return serialize(array(intval($id_auteur),$associer_objet));
}


// Choix par defaut des options de presentation
// http://doc.spip.org/@articles_edit_config
function auteurs_edit_config($row)
{
	global $spip_lang;

	$config = $GLOBALS['meta'];
	$config['lignes'] = 8;
	$config['langue'] = $spip_lang;

	// pour instituer_auteur
	$config['auteur'] = $row;
	
	//$config['restreint'] = ($row['statut'] == 'publie');
	$auth_methode = $row['source'];
	include_spip('inc/auth');
	include_spip('inc/autoriser');
	$autoriser = autoriser('modifier','auteur',$row['id_auteur'],null, array('email'=>true));
	$config['edit_login'] =
		(auth_autoriser_modifier_login($auth_methode) AND $autoriser);
	$config['edit_pass'] =
		(auth_autoriser_modifier_pass($auth_methode)
		AND
			($GLOBALS['visiteur_session']['id_auteur'] == $row['id_auteur'] OR $autoriser)
		);

	return $config;
}

function formulaires_editer_auteur_verifier_dist($id_auteur='new', $retour='', $associer_objet='', $config_fonc='auteurs_edit_config', $row=array(), $hidden=''){
	// auto-renseigner le nom si il n'existe pas, sans couper
	titre_automatique('nom',array('email','login'),255);
	// mais il reste obligatoire si on a rien trouve
	$erreurs = formulaires_editer_objet_verifier('auteur',$id_auteur,array('nom'));

	$auth_methode = sql_getfetsel('source','spip_auteurs','id_auteur='.intval($id_auteur));
	$auth_methode = ($auth_methode ? $auth_methode : 'spip');
	include_spip('inc/auth');
	include_spip('inc/filtres');

	if (!nom_acceptable(_request('nom'))) {
		$erreurs['nom'] = _T("info_nom_pas_conforme");
	}

	if ($email = _request('email')){
		include_spip('inc/autoriser');
		// un redacteur qui modifie son email n'a pas le droit de le vider si il y en avait un
		if (!autoriser('modifier','auteur',$id_auteur,null,array('email'=>'?'))
			AND $GLOBALS['visiteur_session']['id_auteur']==$id_auteur
			AND !strlen(trim($email))
			AND $email!=($email_ancien=sql_getfetsel('email', 'spip_auteurs', 'id_auteur='.intval($id_auteur)))
			){
			$erreurs['email'] = (($id_auteur==$GLOBALS['visiteur_session']['id_auteur'])?_T('form_email_non_valide'):_T('form_prop_indiquer_email'));
		}
		else if (!email_valide($email)){
			$erreurs['email'] = (($id_auteur==$GLOBALS['visiteur_session']['id_auteur'])?_T('form_email_non_valide'):_T('form_prop_indiquer_email'));
		}

		# Ne pas autoriser d'avoir deux auteurs avec le même email
		# cette fonctionalité nécessite que la base soit clean à l'activation : pas de
		# doublon sur la requête select email,count(*) from spip_auteurs group by email ;
		if (defined('_INTERDIRE_AUTEUR_MEME_EMAIL')) {
			#Nouvel auteur
			if (intval($id_auteur)==0) {
				#Un auteur existe deja avec cette adresse ?
				if (sql_countsel("spip_auteurs", "email=".sql_quote($email)) > 0)
					$erreurs['email'] = _T('erreur_email_deja_existant');
			} else {
				#Un auteur existe deja avec cette adresse ? et n'est pas le user courant.
				if ((sql_countsel("spip_auteurs", "email=".sql_quote($email)) > 0) AND ($id_auteur!=($id_auteur_ancien=sql_getfetsel('id_auteur', 'spip_auteurs', "email=".sql_quote($email)))))
					$erreurs['email'] = _T('erreur_email_deja_existant');
			}
		}
	}

	if (preg_match(",^\s*javascript,i", _request('url_site'))) {
		$erreurs['url_site'] = _T('info_url_site_pas_conforme');
	}

	if ($err = auth_verifier_login($auth_methode, _request('new_login'), $id_auteur)){
		$erreurs['new_login'] = $err;
		$erreurs['message_erreur'] .= $err;
	}
	else {
		// pass trop court ou confirmation non identique
		if ($p = _request('new_pass')) {
			if ($p != _request('new_pass2')) {
				$erreurs['new_pass'] = _T('info_passes_identiques');
				$erreurs['message_erreur'] .= _T('info_passes_identiques');
			}
			elseif ($err = auth_verifier_pass($auth_methode, _request('new_login'),$p, $id_auteur)){
				$erreurs['new_pass'] = $err;
				$erreurs['message_erreur'] .= $err;
			}
		}
	}
	return $erreurs;
}

// http://doc.spip.org/@inc_editer_mot_dist
function formulaires_editer_auteur_traiter_dist($id_auteur='new', $retour='', $associer_objet='', $config_fonc='auteurs_edit_config', $row=array(), $hidden=''){
	if (_request('saisie_webmestre') OR _request('webmestre'))
		set_request('webmestre',_request('webmestre')?_request('webmestre'):'non');
	$retour = parametre_url($retour, 'email_confirm','');

	include_spip('inc/autoriser');
	if (!autoriser('modifier','auteur',$id_auteur,null,array('email'=>'?'))){
		$email_nouveau = _request('email');
		set_request('email'); // vider la saisie car l'auteur n'a pas le droit de modifier cet email
		// mais si c'est son propre profil on lui envoie un email à l'adresse qu'il a indique
		// pour qu'il confirme qu'il possede bien cette adresse
		// son clic sur l'url du message permettre de confirmer le changement
		// et de revenir sur son profil
		if ($GLOBALS['visiteur_session']['id_auteur']==$id_auteur
			AND $email_nouveau!=($email_ancien=sql_getfetsel('email', 'spip_auteurs', 'id_auteur='.intval($id_auteur)))){
			$envoyer_mail = charger_fonction('envoyer_mail','inc');
			$texte = _T('form_auteur_mail_confirmation',
											array('url'=>generer_action_auteur('confirmer_email', $email_nouveau,parametre_url($retour, 'email_modif','ok'))));
			$envoyer_mail($email_nouveau,_T('form_auteur_confirmation'),$texte);
			set_request('email_confirm',$email_nouveau);
			if ($email_ancien)
				$envoyer_mail($email_ancien,_T('form_auteur_confirmation'),_T('form_auteur_envoi_mail_confirmation',array('email'=>$email_nouveau)));
			$retour = parametre_url($retour, 'email_confirm',$email_nouveau);
		}
	}

	$res = formulaires_editer_objet_traiter('auteur',$id_auteur,0,0,$retour,$config_fonc,$row,$hidden);

	// Un lien auteur a prendre en compte ?
	if ($associer_objet AND $id_auteur=$res['id_auteur']){
		$objet = '';
		if (intval($associer_objet)){
			$objet='article';
			$id_objet = intval($associer_objet);
		}
		elseif(preg_match(',^\w+\|[0-9]+$,',$associer_objet)){
			list($objet,$id_objet) = explode('|',$associer_objet);
		}
		if ($objet AND $id_objet AND autoriser('modifier',$objet,$id_objet)){
			include_spip('action/editer_auteur');
			auteur_associer($id_auteur, array($objet => $id_objet));
			if (isset($res['redirect']))
				$res['redirect'] = parametre_url ($res['redirect'], "id_lien_ajoute", $id_auteur, '&');
		}
	}

	return $res;
}

?>
