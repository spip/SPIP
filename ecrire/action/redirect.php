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

// http://doc.spip.org/@action_redirect_dist
function action_redirect_dist()
{
	global $redirect;
	$redirect = _request('redirect');

	$suite ='';
	if ($mode = _request('var_mode')) $suite = "var_mode=$mode";
	if ($connect = _request('connect')) {
		$suite .= ($suite ? '&' : '') . "connect=$connect";
		$GLOBALS['type_urls'] = 'propres';
	}

	charger_generer_url();

	if ($id_article = intval(_request('id_article'))) {
		$r = generer_url_article($id_article,$suite,_request('ancre'));
	}
	else if ($id_breve = intval(_request('id_breve'))) {
		$r = generer_url_breve($id_breve,$suite,_request('ancre'));
	}
	else if ($id_forum = intval(_request('id_forum'))) {
		$r = generer_url_forum($id_forum,$suite,_request('ancre'));
	}
	else if ($id_rubrique = intval(_request('id_rubrique'))) {
		$r = generer_url_rubrique($id_rubrique,$suite,_request('ancre'));
	}
	else if ($id_mot = intval(_request('id_mot'))) {
		$r = generer_url_mot($id_mot,$suite,_request('ancre'));
	}
	else if ($id_auteur = intval(_request('id_auteur'))) {
		$r = generer_url_auteur($id_auteur,$suite,_request('ancre'));
	}
	else if ($id_syndic = intval(_request('id_syndic')) OR $id_syndic = intval(_request('id_site'))) {
		$r = generer_url_site($id_syndic,$suite,_request('ancre'));
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
