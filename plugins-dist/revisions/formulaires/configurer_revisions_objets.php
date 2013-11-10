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

function formulaires_configurer_revisions_objets_charger_dist(){
	if (!$objets = unserialize($GLOBALS['meta']['objets_versions']))
		$objets = array();
	$valeurs = array(
		'objets_versions'=>$objets,
	);

	return $valeurs;
}

function formulaires_configurer_revisions_objets_traiter_dist(){

	include_spip('inc/meta');
	$tables = serialize(_request('objets_versions'));
	ecrire_meta('objets_versions',$tables);

	return array('message_ok'=>_T('config_info_enregistree'));
}

function test_objet_versionable($desc){
	if (!$desc['editable']
		OR !isset($desc['champs_versionnes'])
		OR !count($desc['champs_versionnes'])
	)
		return '';

	// regarder si il y a un vrai champ versionne, pas seulement une jointure
	foreach ($desc['champs_versionnes'] as $c){
		if (strncmp($c,'jointure_',9)!=0)
			return ' ';
	}


	return '';
}
?>