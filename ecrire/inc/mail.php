<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

##
## Module de compatibilite ascendante : desormais inc/envoyer_mail
##

if (!defined('_ECRIRE_INC_VERSION')) return;

if (!function_exists('envoyer_mail')) {
	define('_FUNCTION_ENVOYER_MAIL', charger_fonction('envoyer_mail', 'inc'));
// http://doc.spip.org/@envoyer_mail
	function envoyer_mail() {
		$args = func_get_args();
		if (_FUNCTION_ENVOYER_MAIL)
			return call_user_func_array(_FUNCTION_ENVOYER_MAIL, $args);
	}
}


?>
