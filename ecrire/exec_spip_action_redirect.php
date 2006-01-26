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

// pour rediriger vers l'URL canonique de l'article,
// en indiquant recalcul et previsu

if (!defined("_ECRIRE_INC_VERSION")) return;

charger_generer_url();

function spip_action_redirect_dist()
{
  global $id_article, $id_auteur, $id_breve, $id_forum, $id_mot, $id_rubrique, $id_site, $id_syndic, $var_mode;


  if ($id_article = intval($id_article)) {
	$url = generer_url_article($id_article);
}
  else if ($id_breve = intval($id_breve)) {
	$url = generer_url_breve($id_breve);
}
  else if ($id_forum = intval($id_forum)) {
	$url = generer_url_forum($id_forum);
}
  else if ($id_rubrique = intval($id_rubrique)) {
	$url = generer_url_rubrique($id_rubrique);
}
  else if ($id_mot = intval($id_mot)) {
	$url = generer_url_mot($id_mot);
}
  else if ($id_auteur = intval($id_auteur)) {
	$url = generer_url_auteur($id_auteur);
}
  else if ($id_syndic = intval($id_syndic) OR $id_syndic = intval($id_site)) {
	$url = generer_url_site($id_syndic);
}
else {
	$url = _DIR_RESTREINT_ABS;
}

// Ne pas masquer cette eventuelle erreur (aide a detecter des lignes vides
// dans inc-urls ou mes_fonctions/mes_options)
 header("Location: " . (!$var_mode ?  $url : ($url . (strpos($url,'?') ? '&' : '?') ."var_mode=" . $var_mode)));
}


?>
