<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// chargement des valeurs par defaut des champs du formulaire
function formulaires_oubli_charger_dist(){
	$valeurs = array('oubli'=>'','nobot'=>'');
	return $valeurs;
}

// http://doc.spip.org/@message_oubli
function message_oubli($email, $param)
{
	$r = formulaires_oubli_mail($email);
	if (is_array($r) AND $r[1]) {
		include_spip('inc/texte'); # pour corriger_typo

		include_spip('action/inscrire_auteur');
		$cookie = auteur_attribuer_jeton($r[1]['id_auteur']);

		$msg = recuperer_fond(
			"modeles/mail_oubli",
			array(
				'url_reset'=>generer_url_public('spip_pass',"$param=$cookie", true, false)
			)
		);
		include_spip("inc/notifications");
		notifications_envoyer_mails($email, $msg);
	  return _T('pass_recevoir_mail');
	}
	return  _T('pass_erreur_probleme_technique');
}

// la saisie a ete validee, on peut agir
function formulaires_oubli_traiter_dist(){

	$message = message_oubli(_request('oubli'),'p');
	return array('message_ok'=>$message);
}


// fonction qu'on peut redefinir pour filtrer les adresses mail
// http://doc.spip.org/@test_oubli
function test_oubli_dist($email)
{
	include_spip('inc/filtres'); # pour email_valide()
	if (!email_valide($email) )
		return _T('pass_erreur_non_valide', array('email_oubli' => htmlspecialchars($email)));
	return array('mail' => $email);
}

function formulaires_oubli_verifier_dist(){
	$erreurs = array();

	$email = strval(_request('oubli'));

	$r = formulaires_oubli_mail($email);

	if (!is_array($r))
		$erreurs['oubli'] = $r;
	else {
		if (!$r[1])
			$erreurs['oubli'] = _T('pass_erreur_non_enregistre', array('email_oubli' => htmlspecialchars($email)));

		elseif ($r[1]['statut'] == '5poubelle' OR $r[1]['pass'] == '')
			$erreurs['oubli'] =  _T('pass_erreur_acces_refuse');
	}

	if (_request('nobot'))
		$erreurs['message_erreur'] = _T('pass_rien_a_faire_ici');

	return $erreurs;
}

function formulaires_oubli_mail($email)
{
	if (function_exists('test_oubli'))
		$f = 'test_oubli';
	else
		$f = 'test_oubli_dist';
	$declaration = $f($email);

	if (!is_array($declaration))
		return $declaration;
	else {
		return array($declaration, Sql::fetsel('id_auteur,statut,pass', 'spip_auteurs', 'email =' . Sql::quote($declaration['mail'])));
	}
}
?>
