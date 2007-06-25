<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// pour rediriger vers l'URL canonique de l'article,
// en indiquant recalcul et previsu

if (!defined("_ECRIRE_INC_VERSION")) return;

charger_generer_url();

// http://doc.spip.org/@action_redirect_dist
function action_redirect_dist()
{
  global $redirect;
  $redirect = _request('redirect');

  if ($mode = _request('var_mode')) $mode = "var_mode=$mode";

  if ($id_article = intval(_request('id_article'))) {
	$r = generer_url_article($id_article,$mode,_request('ancre'));
}
  else if ($id_breve = intval(_request('id_breve'))) {
	$r = generer_url_breve($id_breve,$mode,_request('ancre'));
}
  else if ($id_forum = intval(_request('id_forum'))) {
	$r = generer_url_forum($id_forum,$mode,_request('ancre'));
}
  else if ($id_rubrique = intval(_request('id_rubrique'))) {
	$r = generer_url_rubrique($id_rubrique,$mode,_request('ancre'));
}
  else if ($id_mot = intval(_request('id_mot'))) {
	$r = generer_url_mot($id_mot,$mode,_request('ancre'));
}
  else if ($id_auteur = intval(_request('id_auteur'))) {
	$r = generer_url_auteur($id_auteur,$mode,_request('ancre'));
}
  else if ($id_syndic = intval(_request('id_syndic')) OR $id_syndic = intval(_request('id_site'))) {
	$r = generer_url_site($id_syndic,$mode,_request('ancre'));
}

// Ne pas masquer cette eventuelle erreur (aide a detecter des lignes vides
// dans inc-urls ou mes_fonctions/mes_options)
  else $redirect = _DIR_RESTREINT_ABS;

	// si c'est un url calcule, on l'encode car spip va ensuite le decoder
	// avant de faire le header(location)
	if (isset($r))
		$redirect = rawurlencode($r);

// Compatibilite avec l'ancienne interface a un seul argument des generer_url_
  if ($mode AND !strpos($redirect, 'var_mode')) {
	$sep =  (strpos($redirect,'?') !== false) ? '&' : '?';
	if (strpos($redirect,'#'))
		$redirect = str_replace('#', "$sep$mode#", $redirect);
	else $redirect .= "$sep$mode";
  }
}
?>
