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
	  $test["statusplug_$file"] = $file;
	}
	// gerer les noms de repertoires qui ont un espace
	// sachant qu'ils vont arriver dans le $_POST avec un _ a la place
	// mais qu'il faut pas se melanger si jamais deux repertoire existent et ne different
	// que par un espace et un underscore
	foreach($test as $postvar=>$file){
		$alt_postvar = str_replace(" ","_",$postvar); // les espaces deviennent des _
		$alt_postvar = str_replace(".","_",$postvar); // les points deviennent des _
		if (!isset($test[$alt_postvar]))
	  	$test[$alt_postvar] = $file;
	}
	$plugin=array();
	foreach($_POST as $choix=>$val){
		if (isset($test[$choix])&&$val=='O')
			$plugin[]=$test[$choix];
	}
	global $connect_id_auteur, $connect_login;
	spip_log("Changement des plugins actifs par auteur id=$connect_id_auteur :".implode(',',$plugin));
	ecrire_plugin_actifs($plugin);
	ecrire_metas();
}

// http://doc.spip.org/@action_activer_plugins_dist
function action_activer_plugins_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	enregistre_modif_plugin();
	
	if ($redirect = _request('redirect')){
		include_spip('inc/headers');
		$redirect = str_replace('&amp;','&',$redirect);
		redirige_par_entete($redirect);
	}
}

?>
