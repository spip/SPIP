<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
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
# 1. l'url collectee ci-dessus (args0) ou donnee en premier parametre (args1) 
#    #LOGIN_PRIVE{#URL_ECRIRE}
# 2. un eventuel parametre (args2) indiquant le login et permettant une ecriture
#    <boucle(AUTEURS)>[(#LOGIN_PRIVE{#URL_ECRIRE, #LOGIN})]

// http://doc.spip.org/@balise_LOGIN_PRIVE_stat
function balise_LOGIN_PRIVE_stat ($args, $context_compil) {
	return array(isset($args[1]) ? $args[1] : $args[0], (isset($args[2]) ? $args[2] : ''));
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
