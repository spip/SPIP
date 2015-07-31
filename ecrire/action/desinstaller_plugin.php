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

include_spip('inc/plugin');
function action_desinstaller_plugin_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$plug_file = $securiser_action();
	$infos = plugin_get_infos($plug_file);
	if (isset($infos['install'])){
		// desinstaller
		$etat = desinstalle_un_plugin($plug_file,$infos['prefix'],$infos['install']);
		// desactiver si il a bien ete desinstalle
		if (!$etat)
			ecrire_plugin_actifs($plug_file,false,'enleve');
		ecrire_metas();
	}
	if ($redirect = _request('redirect')){
		include_spip('inc/headers');
		$redirect = str_replace('&amp;','&',$redirect);
		redirige_par_entete($redirect);
	}
}

?>
