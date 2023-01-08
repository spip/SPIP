<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_rediriger_article_charger_dist($id_article, $retour = '') {

	include_spip('inc/autoriser');
	if (!autoriser('modifier', 'article', $id_article)) {
		return false;
	}

	$row = sql_fetsel('id_article,virtuel', 'spip_articles', 'id_article=' . intval($id_article));
	if (!$row['id_article']) {
		return false;
	}
	include_spip('inc/lien');
	$redirection = virtuel_redirige($row['virtuel']);

	if (
		!$redirection
		and $GLOBALS['meta']['articles_redirection'] != 'oui'
	) {
		return false;
	}


	include_spip('inc/texte');
	$valeurs = [
		'redirection' => $redirection,
		'id' => $id_article,
		'_afficher_url' => ($redirection ? propre("[->$redirection]") : ''),
	];

	return $valeurs;
}

function formulaires_rediriger_article_verifier_dist($id_article, $retour = '') {
	$erreurs = [];

	if (($redirection = _request('redirection')) == $id_article || $redirection == 'art' . $id_article) {
		$erreurs['redirection'] = _T('info_redirection_boucle');
	}

	return $erreurs;
}

function formulaires_rediriger_article_traiter_dist($id_article, $retour = '') {

	$url = preg_replace(',^\s*https?://$,i', '', rtrim(_request('redirection')));
	if ($url) {
		$url = corriger_caracteres($url);
	}

	include_spip('action/editer_article');
	article_modifier($id_article, ['virtuel' => $url]);

	$js = _AJAX ? '<script type="text/javascript">if (window.ajaxReload) ajaxReload("wysiwyg");</script>' : '';

	return [
		'message_ok' => ($url ? _T('info_redirection_activee') : _T('info_redirection_desactivee')) . $js,
		'editable' => true
	];
}
