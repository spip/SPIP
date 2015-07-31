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

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_petitionner_dist
function action_petitionner_dist() {

	include_spip('inc/autoriser');

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$id_article = intval($arg);

	if (!autoriser('modererpetition', 'article', $id_article))
		return;

	switch(_request('change_petition')) {
	case 'on':
		$email_unique = (_request('email_unique') == 'on') ? 'oui' : 'non';
		$site_obli = (_request('site_obli') == 'on') ? 'oui' : 'non';
		$site_unique = (_request('site_unique') == 'on') ? 'oui' : 'non';
		$message =  (_request('message') == 'on') ? 'oui' : 'non';

		include_spip('base/auxiliaires');
		spip_abstract_replace('spip_petitions',
				      array('id_article' => $id_article,
					    'email_unique' => $email_unique,
					    'site_obli' => $site_obli,
					    'site_unique' => $site_unique,
					    'message' => $message),
				      $GLOBALS['tables_auxiliaires']['spip_petitions']);
		include_spip('inc/modifier');
		revision_petition($id_article,
			array('texte' => _request('texte_petition'))
		);
		break;
	case 'off':
		spip_query("DELETE FROM spip_petitions WHERE id_article=$id_article");
		break;
	}

}

?>
