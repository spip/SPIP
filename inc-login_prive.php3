<?php

include_local("inc-login_public.php3");

global $balise_LOGIN_PRIVE_collecte;
$balise_LOGIN_PRIVE_collecte = array('url');

# retourner:
# 1. l'url collectee ci-dessus (args0) ou donnee en filtre (filtre0)
# 2. l'eventuel parametre de la balise (args1) fournie par calculer_balise_dynamique

function balise_LOGIN_PRIVE_stat ($args, $filtres)
{
	return array($filtres[0] ? $filtres[0] : $args[0], $args[1]);
}

function balise_LOGIN_PRIVE_dyn($cible, $login)
{
	return login_explicite($login, $cible,  'redac');
}
?>
