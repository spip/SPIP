<?php

include_local("inc-login_public.php3");

global $balise_LOGIN_PRIVE_collecte;
$balise_LOGIN_PRIVE_collecte = array('url');

# retourner:
# 1. l'url collectee ci-dessus (args0) ou donnee en filtre (filtre0)
# 2. l'eventuel parametre de la balise (args1) fournie par
#    calculer_balise_dynamique, en l'occurence le #LOGIN courant si l'on
#    programme une <boucle(AUTEURS)>[(#LOGIN_PUBLIC{#LOGIN})]

function balise_LOGIN_PRIVE_stat ($args, $filtres) {

	if (!$cible = $filtres[0])
		$cible = $args[0];

	$login = $args[1];

	return array($cible, $login, $simple);
}

function balise_LOGIN_PRIVE_dyn($cible, $login) {
	return login_explicite($login, $cible, 'redac');
}

?>
