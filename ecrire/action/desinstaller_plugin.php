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
// http://doc.spip.org/@action_desinstaller_plugin_dist
function action_desinstaller_plugin_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$plug_file = $securiser_action();
	$get_infos = charger_fonction('get_infos','plugins');
	$infos = $get_infos($plug_file);
	$erreur = "";
	if (isset($infos['install'])){
		// desinstaller
		$etat = desinstalle_un_plugin($plug_file,$infos);
		// desactiver si il a bien ete desinstalle
		if (!$etat)
			ecrire_plugin_actifs(array($plug_file),false,'enleve');
		else
			$erreur = 'erreur_plugin_desinstalation_echouee';
	}
	else {
		// en principe on ne passe pas la car pas de bouton sur les plugins non
		// desinstallables
		echo ('Ce plugin ne peut pas etre desinstalle et vous ne devriez pas arriver la !');
	}
	if ($redirect = _request('redirect')){
		include_spip('inc/headers');
		if ($erreur)
			$redirect = parametre_url($redirect, 'erreur',$erreur);
		$redirect = str_replace('&amp;','&',$redirect);
		redirige_par_entete($redirect);
	}
}

?>
