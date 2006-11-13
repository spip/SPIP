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

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_petitionner_dist
function action_petitionner_dist() {

	include_spip('inc/autoriser');

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	$arg = _request('arg');

	$id_article = intval($arg);

	if (!autoriser('modifier', 'article', $id_article))
		return;

	$message = _request('message');
	$site_obli = _request('site_obli');
	$site_unique = _request('site_unique');
	$email_unique = _request('email_unique');
	$texte_petition = _request('texte_petition');
	$change_petition = _request('change_petition');

	if ($change_petition == "on") {
	  	$email_unique = ($email_unique == 'on') ? 'oui' : "non";
		$site_obli = ($site_obli == 'on') ? 'oui' : "non";
		$site_unique = ($site_unique == 'on') ? 'oui' : "non";
		$message =  ($message == 'on') ? 'oui' : "non";

		$result_pet = spip_query("REPLACE spip_petitions (id_article, email_unique, site_obli, site_unique, message, texte) VALUES ($id_article, '$email_unique', '$site_obli', '$site_unique', '$message', " . _q($texte_petition) . ")");
		}
	else if ($change_petition == "off") {
		$result_pet = spip_query("DELETE FROM spip_petitions WHERE id_article=$id_article");
		}
}
?>
