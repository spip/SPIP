<?php

#include_local("inc-login_public.php3");

global $balise_LOGIN_LIENS_collecte;
$balise_LOGIN_LIENS_collecte = array();


function balise_LOGIN_LIENS_stat ($args, $filtres) {
	return array();
}

function balise_LOGIN_LIENS_dyn() {
	# return array ('fond', $delais, $contexte_inclus)
	return array('formulaire_login_liens', 0, array());
}

?>
