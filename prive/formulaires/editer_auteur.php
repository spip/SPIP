<?php

include_spip('inc/actions');
include_spip('inc/editer');

function instituer_auteur_ici($auteur=array()){
	$instituer_auteur = charger_fonction('instituer_auteur', 'inc');
	return $instituer_auteur($auteur);
}

// http://doc.spip.org/@inc_editer_mot_dist
function formulaires_editer_auteur_charger_dist($id_auteur='new', $retour='', $lier_id_article=0, $config_fonc='auteurs_edit_config', $row=array(), $hidden=''){
	$valeurs = formulaires_editer_objet_charger('auteur',$id_auteur,0,0,$retour,$config_fonc,$row,$hidden);
	if ($lier_id_article) $valeurs['lier_id_article'] = $lier_id_article;
	// forcer la prise en compte du post, sans verifier si c'est bien le meme formulaire,
	// c'est trop hasardeux selon le contenud de $row
	$valeurs['_forcer_request'] = true;
	return $valeurs;
}

// Choix par defaut des options de presentation
// http://doc.spip.org/@articles_edit_config
function auteurs_edit_config($row)
{
	global $spip_ecran, $spip_lang, $spip_display;

	$config = $GLOBALS['meta'];
	$config['lignes'] = ($spip_ecran == "large")? 8 : 5;
	$config['langue'] = $spip_lang;

	// pour instituer_auteur
	$config['auteur'] = $row;
	
	//$config['restreint'] = ($row['statut'] == 'publie');
	$auth_methode = $row['source'];
	include_spip('inc/auth');
	$autoriser = autoriser('modifier','auteur',$row['id_auteur'],null, array('restreintes'=>true));
	$config['edit_login'] =
		(auth_autoriser_modifier_login($auth_methode) AND $autoriser);
	$config['edit_pass'] =
		(auth_autoriser_modifier_pass($auth_methode)
		AND
			($GLOBALS['visiteur_session']['id_auteur'] == $row['id_auteur'] OR $autoriser)
		);

	return $config;
}

function formulaires_editer_auteur_verifier_dist($id_auteur='new', $retour='', $lier_article=0, $config_fonc='auteurs_edit_config', $row=array(), $hidden=''){
	$erreurs = formulaires_editer_objet_verifier('auteur',$id_auteur,array('nom'));

	$auth_methode = sql_getfetsel('source','spip_auteurs','id_auteur='.intval($id_auteur));
	$auth_methode = ($auth_methode ? $auth_methode : 'spip');
	include_spip('inc/auth');

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
function formulaires_editer_auteur_traiter_dist($id_auteur='new', $retour='', $lier_article=0, $config_fonc='auteurs_edit_config', $row=array(), $hidden=''){
	if (_request('saisie_webmestre') OR _request('webmestre'))
		set_request('webmestre',_request('webmestre')?_request('webmestre'):'non');

	return formulaires_editer_objet_traiter('auteur',$id_auteur,0,0,$retour,$config_fonc,$row,$hidden);
	//return $message;
}

?>
