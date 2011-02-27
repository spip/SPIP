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

// http://doc.spip.org/@action_desinstaller_plugin_dist
function action_desinstaller_plugin_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$plugin = $securiser_action();
	include_spip('plugins/installer');
	$erreur = desinstalle_un_plugin($plugin);
	if ($redirect = _request('redirect')){
		include_spip('inc/headers');
		if ($erreur)
			$redirect = parametre_url($redirect, 'erreur',$erreur);
		$redirect = str_replace('&amp;','&',$redirect);
		redirige_par_entete($redirect);
	}
}
?>
