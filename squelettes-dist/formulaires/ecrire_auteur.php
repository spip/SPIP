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


function formulaires_ecrire_auteur_charger_dist($id_auteur, $id_article, $mail){
	include_spip('inc/texte');
	$puce = definir_puce();
	$valeurs = array(
		'sujet_message_auteur'=>'',
		'texte_message_auteur'=>'',
		'email_message_auteur'=>$GLOBALS['visiteur_session']['email']
	);
	
	// id du formulaire (pour en avoir plusieurs sur une meme page)
	$valeurs['id'] = ($id_auteur ? '_'.$id_auteur : '_ar'.$id_article);
	// passer l'id_auteur au squelette
	$valeurs['id_auteur'] = $id_auteur;
	$valeurs['id_article'] = $id_article;
	
	return $valeurs;
}

function formulaires_ecrire_auteur_verifier_dist($id_auteur, $id_article, $mail){
	$erreurs = array();
	include_spip('inc/filtres');
	
	if (!$adres = _request('email_message_auteur'))
		$erreurs['email_message_auteur'] = _T("info_obligatoire");
	elseif(!email_valide($adres))
		$erreurs['email_message_auteur'] = _T('form_prop_indiquer_email');
	else {
		include_spip('inc/session');
		session_set('email', $adres);
	}

	if (!$sujet=_request('sujet_message_auteur'))
		$erreurs['sujet_message_auteur'] = _T("info_obligatoire");
	elseif(!(strlen($sujet)>3))
		$erreurs['sujet_message_auteur'] = _T('forum_attention_trois_caracteres');

	if (!$texte=_request('texte_message_auteur'))
		$erreurs['texte_message_auteur'] = _T("info_obligatoire");
	elseif(!(strlen($texte)>10))
		$erreurs['texte_message_auteur'] = _T('forum_attention_dix_caracteres');

	if (!_request('confirmer') AND !count($erreurs))
		$erreurs['previsu']=' ';
	return $erreurs;
}

function formulaires_ecrire_auteur_traiter_dist($id_auteur, $id_article, $mail){
	
	$adres = _request('email_message_auteur');
	$sujet=_request('sujet_message_auteur');
	$texte=_request('texte_message_auteur');
	
	$texte .= "\n\n-- "._T('envoi_via_le_site')." ".supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site']))." (".$GLOBALS['meta']['adresse_site']."/) --\n";
	$envoyer_mail = charger_fonction('envoyer_mail','inc');

	if ($envoyer_mail($mail, $sujet, $texte, $adres,
	"X-Originating-IP: ".$GLOBALS['ip']))
		$message = _T('form_prop_message_envoye');
	else
		$message = _T('pass_erreur_probleme_technique');

	return array('message_ok'=>$message);
}

?>
