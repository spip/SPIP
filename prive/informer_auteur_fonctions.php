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

// Filtre ad hoc pour le formulaire de login:
// le parametre var_login n'est pas dans le contexte pour optimiser le cache
// il faut aller le chercher a la main
function informer_auteur($bof)
{
	include_spip('inc/json');
	include_spip('formulaires/login');
	include_spip('inc/auth');
	$login = strval(_request('var_login'));
	$row = auth_informer_login($login);
	if ($row AND is_array($row)) {
		unset($row['id_auteur']);
	}
	// permettre d'autoriser l'envoi de password non crypte lorsque
	// l'auteur n'est pas (encore) declare dans SPIP, par exemple pour les cas
	// de premiere authentification via SPIP a une autre application.
	else if (defined('_AUTORISER_AUTH_FAIBLE') and _AUTORISER_AUTH_FAIBLE) {
		$row = array();
	}
	// generer de fausses infos, mais credibles, pour eviter une attaque
	// http://core.spip.org/issues/1758
	else {
		include_spip('inc/securiser_action');
		$fauxalea1 = md5('fauxalea'.secret_du_site().$login.floor(date('U')/86400));
		$fauxalea2 = md5('fauxalea'.secret_du_site().$login.ceil(date('U')/86400));

		$row = array('login' => $login,
		 'cnx' => 0,
		 'logo' => "",
		 'alea_actuel' => substr_replace($fauxalea1,'.',24,0),
		 'alea_futur' => substr_replace($fauxalea2,'.',24,0)
		);
	}

	return json_export($row);
}

?>
