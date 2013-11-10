<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaires_configurer_compteur_charger_dist(){

	$valeurs = array();
	
	$valeurs['activer_statistiques'] = $GLOBALS['meta']['activer_statistiques'];
	$valeurs['activer_captures_referers'] = $GLOBALS['meta']['activer_captures_referers'];
	
	return $valeurs;
	
}

function formulaires_configurer_compteur_verifier_dist(){
	$erreurs = array();
	
	// les checkbox
	foreach(array('activer_statistiques','activer_captures_referers') as $champ)
		if (_request($champ)!='oui')
			set_request($champ,'non');
			
	return $erreurs;
}

function formulaires_configurer_compteur_traiter_dist(){
	include_spip('inc/config');
	appliquer_modifs_config();
		
	return array('message_ok'=>_T('config_info_enregistree'));
}

?>