<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// http://doc.spip.org/@action_configurer_moderniseur_html_dist
function action_configurer_moderniseur_html_dist() {
	spip_log("On va enregistrer une version de HTML");
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$v = _request('version_html_max'));
	spip_log("C'est-a-dire: $v");
	if (('html4' == $v) OR ('html5' == $v)) {
		ecrire_meta('version_html_max', $v);
	}
}
?>
