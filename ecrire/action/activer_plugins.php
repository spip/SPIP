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

// mise a jour des donnees si envoi via formulaire
// http://doc.spip.org/@enregistre_modif_plugin
function enregistre_modif_plugin(){
	include_spip('inc/plugin');
  // recuperer les plugins dans l'ordre des $_POST
	$test = array();
	foreach(liste_plugin_files() as $file){
	  $test['s'.substr(md5("statusplug_$file"),0,16)] = $file;
	}
	foreach($test as $postvar=>$file){
		if (!isset($test[$alt_postvar]))
	  	$test[$alt_postvar] = $file;
	}
	$plugin=array();
	foreach($_POST as $choix=>$val){
		if (isset($test[$choix])&&$val=='O')
			$plugin[]=$test[$choix];
	}

	spip_log("Changement des plugins actifs par l'auteur " . $GLOBALS['auteur_session']['id_auteur']);
	ecrire_plugin_actifs($plugin);
	ecrire_metas();
}

// http://doc.spip.org/@action_activer_plugins_dist
function action_activer_plugins_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	enregistre_modif_plugin();
}

?>
