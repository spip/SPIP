<?php

global $balise_URL_LOGOUT_collecte;
$balise_URL_LOGOUT_collecte = array();

function balise_URL_LOGOUT_stat ($args, $filtres)
{
  return array($filtres[0]);
}

function balise_URL_LOGOUT_dyn($cible)
{
	if (!$login = $GLOBALS['auteur_session']['login'])
	  return '';
	if (!$cible) $cible = $GLOBALS['clean_link']->getUrl();
	return 'spip_cookie.php3?logout_public=' . $login . '&amp;url=' . urlencode($cible);
}
?>
