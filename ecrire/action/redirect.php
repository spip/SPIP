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

  if ($mode = _request('var_mode')) $mode = "var_mode=$mode";

  if ($id_article = intval(_request('id_article'))) {
	$redirect = generer_url_article($id_article,$mode,_request('ancre'));
}
  else if ($id_breve = intval(_request('id_breve'))) {
	$redirect = generer_url_breve($id_breve,$mode,_request('ancre'));
}
  else if ($id_forum = intval(_request('id_forum'))) {
	$redirect = generer_url_forum($id_forum,$mode,_request('ancre'));
}
  else if ($id_rubrique = intval(_request('id_rubrique'))) {
	$redirect = generer_url_rubrique($id_rubrique,$mode,_request('ancre'));
}
  else if ($id_mot = intval(_request('id_mot'))) {
	$redirect = generer_url_mot($id_mot,$mode,_request('ancre'));
}
  else if ($id_auteur = intval(_request('id_auteur'))) {
	$redirect = generer_url_auteur($id_auteur,$mode,_request('ancre'));
}
  else if ($id_syndic = intval(_request('id_syndic')) OR $id_syndic = intval(_request('id_site'))) {
	$redirect = generer_url_site($id_syndic,$mode,_request('ancre'));
}

// Ne pas masquer cette eventuelle erreur (aide a detecter des lignes vides
// dans inc-urls ou mes_fonctions/mes_options)
  else $redirect = _DIR_RESTREINT_ABS;
}
?>
