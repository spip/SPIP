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

function action_petitionner_dist() {
	global $action, $arg, $hash, $id_auteur;
	include_spip('inc/actions');
	if (!verifier_action_auteur("$action-$arg", $hash, $id_auteur)) {
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	$id_article = intval($arg);

	$message = _request('message');
	spip_log("	message	$message");
	$site_obli = _request('site_obli');
	spip_log("	site_obli 	$site_obli ");
	$site_unique = _request('site_unique');
	spip_log("	site_unique 	$site_unique ");
	$email_unique = _request('email_unique');
	spip_log("	email_unique 	$email_unique ");
	$texte_petition = _request('texte_petition');
	spip_log("	texte_petition 	$texte_petition ");
	$change_petition = _request('change_petition');

	spip_log("action $action $arg $change_petition");

	if ($change_petition == "on") {
	  	$email_unique = ($email_unique == 'on') ? 'oui' : "non";
		$site_obli = ($site_obli == 'on') ? 'oui' : "non";
		$site_unique = ($site_unique == 'on') ? 'oui' : "non";
		$message =  ($message == 'on') ? 'oui' : "non";

		$result_pet = spip_query("REPLACE spip_petitions (id_article, email_unique, site_obli, site_unique, message, texte) VALUES ($id_article, '$email_unique', '$site_obli', '$site_unique', '$message', " . spip_abstract_quote($texte_petition) . ")");
		}
	else if ($change_petition == "off") {
		$result_pet = spip_query("DELETE FROM spip_petitions WHERE id_article=$id_article");
		}
}
?>
