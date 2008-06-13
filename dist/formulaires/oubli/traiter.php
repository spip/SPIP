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

// http://doc.spip.org/@message_oubli
function message_oubli($email, $param)
{
	if (function_exists('test_oubli'))
		$f = 'test_oubli';
	else 
		$f = 'test_oubli_dist';
	$declaration = $f($email);

	if (is_array($declaration)
	  AND $res = sql_select("id_auteur,statut,pass", "spip_auteurs", "email =" . sql_quote($declaration['mail']))
	  AND $row = sql_fetch($res)
	  ) {
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
					   "$param=$cookie", true)))) )
		  return _T('pass_recevoir_mail');
		else
		  return  _T('pass_erreur_probleme_technique');
	}
  return  _T('pass_erreur_probleme_technique');
}

// la saisie a ete validee, on peut agir
function formulaires_oubli_traiter_dist(){

	$message = message_oubli(_request('oubli'),'p');
	return $message;
}

?>