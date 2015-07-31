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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('balise/login_public'); 


function balise_LOGIN_PRIVE ($p) {

	return balise_login_public($p,'LOGIN_PRIVE');
}

# retourner:
# 1. l'url collectee ci-dessus (args0) ou donnee en filtre (filtre0)
# 2. l'eventuel parametre de la balise (args1) fournie par
#    calculer_balise_dynamique, en l'occurrence le #LOGIN courant si l'on
#    programme une <boucle(AUTEURS)>[(#LOGIN_PRIVE{#LOGIN})]

function balise_LOGIN_PRIVE_stat ($args, $filtres) {
	 return array(isset($args[1]) ? $args[1] : '',
		      ($filtres[0] ? $filtres[0] : $args[0]));
}

function balise_LOGIN_PRIVE_dyn($login, $cible) {
	return login_explicite($login, $cible);
}
?>
