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

// http://doc.spip.org/@action_desinstaller_plugin_dist
function action_desinstaller_plugin_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	list($dir_plugins,$plugin) = explode("::",$arg);
	$dir_type = "_DIR_PLUGINS";
	if (defined('_DIR_PLUGINS_SUPPL') AND $dir_plugins==_DIR_PLUGINS_SUPPL)
		$dir_type = "_DIR_PLUGINS_SUPPL";
	$installer_plugins = charger_fonction('installer', 'plugins');
	$infos = $installer_plugins($plugin, 'uninstall');
	if ($infos AND !$infos['install_test'][0]) {
		include_spip('inc/plugin');
		ecrire_plugin_actifs(array($plugin),false,'enleve');
		$erreur = '';
	} else 	$erreur = 'erreur_plugin_desinstalation_echouee';
	if ($redirect = _request('redirect')){
		include_spip('inc/headers');
		if ($erreur)
			$redirect = parametre_url($redirect, 'erreur',$erreur);
		$redirect = str_replace('&amp;','&',$redirect);
		redirige_par_entete($redirect);
	}
}
?>
