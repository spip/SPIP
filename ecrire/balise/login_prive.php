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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite


// http://doc.spip.org/@balise_LOGIN_PRIVE
function balise_LOGIN_PRIVE ($p) {
	return calculer_balise_dynamique($p, 'LOGIN_PRIVE', array('url'));
}

# retourner:
# 1. l'url collectee ci-dessus (args0) ou donnee en filtre (filtre0)
# 2. l'eventuel parametre de la balise (args1) fournie par
#    calculer_balise_dynamique, en l'occurrence le #LOGIN courant si l'on
#    programme une <boucle(AUTEURS)>[(#LOGIN_PRIVE{#LOGIN})]

// http://doc.spip.org/@balise_LOGIN_PRIVE_stat
function balise_LOGIN_PRIVE_stat ($args, $filtres) {
	return array($filtres[0] ? $filtres[0] : $args[0], (isset($args[1]) ? $args[1] : ''));
}

// http://doc.spip.org/@balise_LOGIN_PRIVE_dyn
function balise_LOGIN_PRIVE_dyn($url, $login) {
	include_spip('balise/formulaire_');
	if (!$url 		# pas d'url passee en filtre ou dans le contexte
	AND !$url = _request('url') # ni d'url passee par l'utilisateur
	)
		$url = generer_url_ecrire('accueil','',true);
	return balise_FORMULAIRE__dyn('login',$url,$login,true);
}
?>
