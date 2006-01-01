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

include_local(find_in_path("inc-login_public.php3"));

global $balise_LOGIN_PRIVE_collecte;
$balise_LOGIN_PRIVE_collecte = array('url');

# retourner:
# 1. l'url collectee ci-dessus (args0) ou donnee en filtre (filtre0)
# 2. l'eventuel parametre de la balise (args1) fournie par
#    calculer_balise_dynamique, en l'occurence le #LOGIN courant si l'on
#    programme une <boucle(AUTEURS)>[(#LOGIN_PRIVE{#LOGIN})]

function balise_LOGIN_PRIVE_stat ($args, $filtres) {

	if (!$cible = $filtres[0])
		$cible = $args[0];

	$login = $args[1];

	return array($cible, $login);
}

function balise_LOGIN_PRIVE_dyn($cible, $login) {
	return
		login_explicite($login, $cible);
}

?>
