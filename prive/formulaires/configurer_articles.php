<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_articles_charger_dist() {
	$valeurs = [];
	foreach (
		[
			'articles_surtitre',
			'articles_soustitre',
			'articles_descriptif',
			'articles_chapeau',
			'articles_texte',
			'articles_ps',
			'articles_redac',
			'articles_urlref',
			'post_dates',
			'articles_redirection',
		] as $m
	) {
		$valeurs[$m] = $GLOBALS['meta'][$m];
	}

	return $valeurs;
}


function formulaires_configurer_articles_traiter_dist() {
	$res = ['editable' => true];
	$purger_skel = false;
	// Purger les squelettes si un changement de meta les affecte
	if ($i = _request('post_dates') and ($i != $GLOBALS['meta']['post_dates'])) {
		$purger_skel = true;
	}

	foreach (
		[
			'articles_surtitre',
			'articles_soustitre',
			'articles_descriptif',
			'articles_chapeau',
			'articles_texte',
			'articles_ps',
			'articles_redac',
			'articles_urlref',
			'post_dates',
			'articles_redirection',
		] as $m
	) {
		if (!is_null($v = _request($m))) {
			ecrire_meta($m, $v == 'oui' ? 'oui' : 'non');
		}
	}

	if ($purger_skel) {
		include_spip('inc/invalideur');
		purger_repertoire(_DIR_SKELS);
	}

	$res['message_ok'] = _T('config_info_enregistree');

	return $res;
}
