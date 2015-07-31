<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;


// Cette action permet de confirmer un changement d'email

function action_confirmer_email_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	include_spip('inc/filtres');
	if ($GLOBALS['visiteur_session']['id_auteur'] AND email_valide($arg)){
		include_spip('action/editer_auteur');
		auteur_modifier($GLOBALS['visiteur_session']['id_auteur'],array('email'=>$arg));
	}
	// verifier avant de rediriger pour invalider le message de confirmation
	// si ca n'a pas marche
	if ($redirect = _request('redirect') AND !$arg == sql_getfetsel('email', 'spip_auteurs', 'id_auteur='.intval($GLOBALS['visiteur_session'])))
		$GLOBALS['redirect'] = parametre_url($redirect, 'email_modif','');

}

?>
