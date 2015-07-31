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

/**
 * Module de compatibilite ascendante : desormais inc/envoyer_mail
 *
 * @deprecated Utiliser inc/envoyer_mail
 * @package SPIP\Core\Mail
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

if (!function_exists('envoyer_mail')) {
	define('_FUNCTION_ENVOYER_MAIL', charger_fonction('envoyer_mail', 'inc'));
	/**
	 * Envoie un mail.
	 * @uses inc_envoyer_mail_dist()
	 * @deprecated Utiliser inc_envoyer_mail_dist() via charger_fonction()
	**/
	function envoyer_mail() {
		$args = func_get_args();
		if (_FUNCTION_ENVOYER_MAIL)
			return call_user_func_array(_FUNCTION_ENVOYER_MAIL, $args);
	}
}


?>
