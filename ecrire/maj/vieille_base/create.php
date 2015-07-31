<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

$serveur_vieille_base =0;

function	spip_create_table($table,$fields,$keys,$autoinc){
	static $fcreate = null;
	$serveur = $GLOBALS['serveur_vieille_base'];
	if (!$fcreate) $fcreate = sql_serveur('create', $serveur);
	$fcreate($table,$fields,$keys,$autoinc,false,$serveur);
}

function maj_vieille_base_create_dist($version_cible){

	$charger = charger_fonction('charger','maj/vieille_base');
	$version = $charger($version_cible);

	// choisir un nouveau prefixe de table, le noter, et switcher en redefinissant le serveur
	$new_prefixe = "XXspip$version";
	// ici on ecrit la meta dans la table 'officielle'
	ecrire_meta('restauration_table_prefix',$new_prefixe,'non');
	ecrire_meta('vieille_version_installee',$version,'non');
	
	//$GLOBALS['table_prefix'] = $new_prefixe;
	//lire_metas();
	$prefixe_source = $GLOBALS['connexions'][0]['prefixe'];
	$GLOBALS['serveur_vieille_base'] = 0;
	$GLOBALS['connexions'][$GLOBALS['serveur_vieille_base']] = $GLOBALS['connexions'][0];
	$GLOBALS['connexions'][$GLOBALS['serveur_vieille_base']]['prefixe'] = $new_prefixe;

	$create = charger_fonction('create',"maj/vieille_base/$version");
	$create();
	
	// reecrire les metas dans la table provisoire
	foreach($GLOBALS['meta'] as $k=>$v)
		ecrire_meta($k,$v);
	ecrire_meta('restauration_table_prefix_source',$prefixe_source,'non');
	
	// noter le numero de version installee
	ecrire_meta('version_installee',$version_cible,'non');

}


?>