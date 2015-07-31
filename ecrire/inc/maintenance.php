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

// Diverses taches de maintenance
// http://doc.spip.org/@cron_maintenance
function cron_maintenance ($t) {

	// (re)mettre .htaccess avec deny from all 
	// dans les deux repertoires dits inaccessibles par http

	include_spip('inc/acces');
	verifier_htaccess(_DIR_ETC);
	verifier_htaccess(_DIR_TMP);

	// Supprimer les vieilles fonctions ajax enregistrees
	spip_query("DELETE FROM spip_ajax_fonc WHERE date < DATE_SUB(NOW(), INTERVAL 2 HOUR)");
	return 1;
}

?>
