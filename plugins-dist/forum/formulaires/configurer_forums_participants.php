<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaires_configurer_forums_participants_charger_dist(){

	return array(
		'forums_publics' => $GLOBALS['meta']["forums_publics"],
	);
	
}

function formulaires_configurer_forums_participants_traiter_dist(){
	include_spip('inc/config');
	include_spip('inc/meta');

	$purger_skel = false;
	if ($accepter_forum = _request('forums_publics')
	AND ($accepter_forum != $GLOBALS['meta']["forums_publics"])) {
		$purger_skel = true;
		$accepter_forum = substr($accepter_forum,0,3);
	}

	// Appliquer les changements de moderation forum
	// forums_publics_appliquer : futur, saufnon, tous
	if (in_array($appliquer = _request('forums_publics_appliquer'),
		array('tous', 'saufnon')
	)) {
		$sauf = ($appliquer == 'saufnon')
			? "accepter_forum != 'non'"
			: '';

		Sql::updateq('spip_articles', array('accepter_forum'=>$accepter_forum), $sauf);
	}

	if ($accepter_forum == 'abo')
		ecrire_meta('accepter_visiteurs', 'oui');

	appliquer_modifs_config();
	if ($purger_skel) {
		include_spip('inc/invalideur');
		suivre_invalideur("forum/*");
	}

	return array('message_ok'=>_T('config_info_enregistree'));
}

?>