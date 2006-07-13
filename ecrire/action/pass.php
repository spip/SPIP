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

include_spip('inc/session'); # pour creer_uniq_id
include_spip('inc/minipres'); # charge lang et execute utiliser_lang
include_spip('inc/mail'); # pour envoyer_mail
include_spip('inc/acces'); # pour generer_htpass
include_spip('public/assembler'); # pour calculer la page
include_spip('inc/filtres'); # pour email_valide()

// Ce fichier est celui d'une balise dynamique qui s'ignore.


// fonction qu'on peut redefinir pour filtrer les adresses mail 

function test_oubli($email)
{
	if (!email_valide($email) ) 
		return _T('pass_erreur_non_valide', array('email_oubli' => htmlspecialchars($email)));
	return array('mail' => $email);
}

function message_oubli($email, $param)
{
	if (function_exists('test_oubli'))
		$f = 'test_oubli';
	else 
		$f = 'test_oubli_dist';
	$declaration = $f($email);

	if (!is_array($declaration))
		return $declaration;

	$res = spip_query("SELECT id_auteur,statut,pass FROM spip_auteurs WHERE email =" . spip_abstract_quote($declaration['mail']));

	if (!$row = spip_fetch_array($res)) 
		return _T('pass_erreur_non_enregistre', array('email_oubli' => htmlspecialchars($email)));

	if ($row['statut'] == '5poubelle' OR $row['pass'] == '')
		return  _T('pass_erreur_acces_refuse');

	$cookie = creer_uniqid();
	spip_query("UPDATE spip_auteurs SET cookie_oubli = '$cookie' WHERE id_auteur=" . $row['id_auteur']);

	$nom = $GLOBALS['meta']["nom_site"];
	$url = $GLOBALS['meta']["adresse_site"];
	if ( envoyer_mail($email,
			  ("[$nom] " .  _T('pass_oubli_mot')),
			  _T('pass_mail_passcookie',
			     array('nom_site_spip' => $nom,
				   'adresse_site' => $url, 
				   'sendcookie' => generer_url_action('pass', "$param=$cookie", true)))) )
	  return _T('pass_recevoir_mail');
	else
	  return  _T('pass_erreur_probleme_technique');
}


function formulaire_oubli_dyn($p, $oubli)
{

$message = '';

// au 3e appel la variable P est positionnee et oubli = mot passe.
// au 2e appel, P est vide et oubli vaut le mail a qui envoyer le cookie
// au 1er appel, P et oubli sont vides

 if (!$p) {
	  if ($oubli) $message = message_oubli($oubli, 'p');
 } else {
	$res = spip_query("SELECT login FROM spip_auteurs WHERE cookie_oubli=" . spip_abstract_quote($p) . " AND statut<>'5poubelle' AND pass<>''");
	if (!$row = spip_fetch_array($res)) 
		$message = _T('pass_erreur_code_inconnu');
	else {
		if ($oubli) {
			$mdpass = md5($oubli);
			$htpass = generer_htpass($oubli);
			spip_query("UPDATE spip_auteurs SET htpass='$htpass', pass='$mdpass', alea_actuel='',	cookie_oubli='' WHERE cookie_oubli=" . spip_abstract_quote($p));

			$login = $row['login'];
			$message = "<b>" . _T('pass_nouveau_enregistre') . "</b>".
			"<p>" . _T('pass_rappel_login', array('login' => $login));
		}
	}
 }
 return array('formulaires/formulaire_oubli', 0, 
	      array('p' => $p,
		    'message' => $message,
		    'action' => generer_url_action('pass')));
}

function action_pass_dist()
{
	install_debut_html( _T('pass_mot_oublie'));
	inclure_balise_dynamique(formulaire_oubli_dyn(_request('p'), _request('oubli')));
	install_fin_html();
}
?>
