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

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/plugin');

include_spip('inc/actions');
// http://doc.spip.org/@exec_info_plugin_dist
function exec_info_plugin_distant_dist() {
	if (!autoriser('configurer', 'plugins')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		$plug = _request('plugin');
		include_spip('inc/charger_plugin');
		include_spip('inc/texte');
		$liste = liste_plugins_distants($plug);
		$item = $liste[$plug][2];
		$afficher_plugin_distant = charger_fonction("afficher_plugin_distant","plugins");
		ajax_retour(affiche_bloc_plugin_distant($plug, $liste[$plug][2]));
	}
}

?>
