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
function test_oubli_dist($email)
{
	include_spip('inc/filtres'); # pour email_valide()
	if (!email_valide($email) ) 
		return _T('pass_erreur_non_valide', array('email_oubli' => htmlspecialchars($email)));
	return array('mail' => $email);
}

function formulaires_oubli_verifier_dist(){
	$erreurs = array();

	$email = _request('oubli');
	if (function_exists('test_oubli'))
		$f = 'test_oubli';
	else 
		$f = 'test_oubli_dist';
	$declaration = $f($email);

	if (!is_array($declaration))
		$erreurs['oubli'] = $declaration;
	else {

		include_spip('base/abstract_sql');
		$res = sql_select("id_auteur,statut,pass", "spip_auteurs", "email =" . sql_quote($declaration['mail']));
	
		if (!$row = sql_fetch($res)) 
			$erreurs['oubli'] = _T('pass_erreur_non_enregistre', array('email_oubli' => htmlspecialchars($email)));
	
		if ($row['statut'] == '5poubelle' OR $row['pass'] == '')
			$erreurs['oubli'] =  _T('pass_erreur_acces_refuse');
	}

	return $erreurs;
}

?>