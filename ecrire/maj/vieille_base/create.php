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

$serveur_vieille_base =0;

function	spip_create_vieille_table($table,$fields,$keys,$autoinc){
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
	ecrire_meta('vieille_version_installee',$version_cible,'non');
	$metas = $GLOBALS['meta'];
	
	$prefixe_source = $GLOBALS['connexions'][0]['prefixe'];
	$GLOBALS['serveur_vieille_base'] = 0;
	$GLOBALS['connexions'][$GLOBALS['serveur_vieille_base']] = $GLOBALS['connexions'][0];
	$GLOBALS['connexions'][$GLOBALS['serveur_vieille_base']]['prefixe'] = $new_prefixe;
	lire_metas();
	
	if (!isset($GLOBALS['meta']['restauration_table_prefix_source'])) {

		$create = charger_fonction('create',"maj/vieille_base/$version");
		$create();
	
		// reecrire les metas dans la table provisoire
		foreach($metas as $k=>$v)
			ecrire_meta($k,$v);
		ecrire_meta('restauration_table_prefix_source',$prefixe_source,'non');
	
		// noter le numero de version installee
		//
		// Exception lorsque la version est entre 10000 et 12000
		// car ce qui est utilise est la base au moment du SVN 10000
		// qui avait url_propre dans les champs de tous les objets
		// mais un spip_version type 1.945 (et non 10000). 
		// si on laisse 10000, les mise a jour de url_propre ne se font pas.
		if ($version == 10000) $version = 1.945;
		ecrire_meta('version_installee',$version,'non');
	}
	
	if ($version_cible!=$GLOBALS['meta']['version_installee']) {
		// upgrader jusqu'a la cible
		include_spip('base/upgrade');
		maj_base($version_cible);
	}

}


?>
