<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres'); # pour email_valide()

// fonction qu'on peut redefinir pour filtrer les adresses mail 

// http://doc.spip.org/@test_oubli
function test_oubli($email)
{
	if (!email_valide($email) ) 
		return _T('pass_erreur_non_valide', array('email_oubli' => htmlspecialchars($email)));
	return array('mail' => $email);
}

// http://doc.spip.org/@message_oubli
function message_oubli($email, $param)
{
	include_spip('inc/filtres'); # pour email_valide()

	if (function_exists('test_oubli'))
		$f = 'test_oubli';
	else 
		$f = 'test_oubli_dist';
	$declaration = $f($email);

	if (!is_array($declaration))
		return $declaration;

	include_spip('base/abstract_sql');
	$res = sql_select("id_auteur,statut,pass", "spip_auteurs", "email =" . sql_quote($declaration['mail']));

	if (!$row = sql_fetch($res)) 
		return _T('pass_erreur_non_enregistre', array('email_oubli' => htmlspecialchars($email)));

	if ($row['statut'] == '5poubelle' OR $row['pass'] == '')
		return  _T('pass_erreur_acces_refuse');

	include_spip('inc/acces'); # pour creer_uniqid
	$cookie = creer_uniqid();
	sql_updateq("spip_auteurs", array("cookie_oubli" => $cookie), "id_auteur=" . $row['id_auteur']);

	$nom = $GLOBALS['meta']["nom_site"];
	$envoyer_mail = charger_fonction('envoyer_mail','inc');

	if ($envoyer_mail($email,
			  ("[$nom] " .  _T('pass_oubli_mot')),
			  _T('pass_mail_passcookie',
			     array('nom_site_spip' => $nom,
				   'adresse_site' => url_de_base(),
				   'sendcookie' => generer_url_public('spip_pass', 
				   "$param=$cookie&formulaire_action="._request('formulaire_action')
				   ."&formulaire_action_cle="._request('formulaire_action_cle')
				   ."&formulaire_action_args="._request('formulaire_action_args')
				   , true)))) )
	  return _T('pass_recevoir_mail');
	else
	  return  _T('pass_erreur_probleme_technique');
}

function formulaires_oubli_valider_dist(){
	$erreurs = array();
	$p = _request('p');
	$oubli= _request('oubli');
	// au second passage, afficher le message
	if (!$p && $oubli)
		$erreurs['message_erreur'] = message_oubli($oubli, 'p');
	elseif($p) {
		include_spip('base/abstract_sql');
		$res = sql_select("login", "spip_auteurs", "cookie_oubli=" . sql_quote($p) . " AND statut<>'5poubelle' AND pass<>''");
		if (!$row = sql_fetch($res))
			$erreurs['message_erreur'] = _T('pass_erreur_code_inconnu');
		elseif (!$oubli)
			$erreurs['message_erreur'] = ' '; // activer la saisie a nouveau
	}

	return $erreurs;
}

?>