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

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Installation/maj des tables urls
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function urls_upgrade($nom_meta_base_version,$version_cible){
	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])){
		$trouver_table = charger_fonction('trouver_table','base');
		if ($desc = $trouver_table('spip_urls')
		  AND isset($desc['exist'])){
			ecrire_meta($nom_meta_base_version,'1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}

	$maj = array();
	$maj['create'] = array(
		array('maj_tables',array('spip_urls')),
	);
	$maj['1.1.0'] = array(
		array('Sql::alter',"table spip_urls ADD id_parent bigint(21) DEFAULT '0' NOT NULL"),
		array('Sql::alter',"table spip_urls DROP PRIMARY KEY"),
		array('Sql::alter',"table spip_urls ADD PRIMARY KEY (id_parent, url)"),
	);
	$maj['1.1.1'] = array(
		array('urls_migre_arbo_prefixes'),
	);

	$maj['1.1.2'] = array(
		array('Sql::alter',"table spip_urls ADD segments SMALLINT(3) DEFAULT '1' NOT NULL"),
		array('urls_migre_urls_segments'),
	);
	$maj['1.1.3'] = array(
		array('Sql::alter',"table spip_urls ADD perma TINYINT(1) DEFAULT '0' NOT NULL"),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function urls_migre_arbo_prefixes(){
	$res = Sql::select('*','spip_urls',"url REGEXP '\d+:\/\/'");
	while($row = Sql::fetch($res)){
		$url = explode("://",$row['url']);
		$set = array('id_parent'=>intval(reset($url)),'url'=>end($url));
		if (!Sql::updateq('spip_urls',$set,"id_parent=".intval($row['id_parent'])." AND url=".Sql::quote($row['url']))){
			if ($set['id_parent']==0
			  AND Sql::countsel('spip_urls',"id_parent=".intval($set['id_parent'])." AND url=".Sql::quote($set['url'])." AND type=".Sql::quote($row['type'])." AND id_objet=".Sql::quote($row['id_objet']))){
				spip_log('suppression url doublon '.var_export($row,1),'urls.'._LOG_INFO_IMPORTANTE);
				Sql::delete('spip_urls',"id_parent=".intval($row['id_parent'])." AND url=".Sql::quote($row['url']));
			}
			else {
				spip_log('Impossible de convertir url doublon '.var_export($row,1),'urls.'._LOG_ERREUR);
				echo "Impossible de convertir l'url ".$row['url'].". Verifiez manuellement dans spip_urls";
			}
		}
		if (time() >= _TIME_OUT){
			Sql::free($res);
			return;
		}
	}
}

function urls_migre_urls_segments(){
	Sql::updateq('spip_urls',array('segments'=>1),"segments<1 OR NOT(url REGEXP '\/')");
	$res = Sql::select('DISTINCT url','spip_urls',"url REGEXP '\/' AND segments=1");
	while($row = Sql::fetch($res)){
		$segments = count(explode('/',$row['url']));
		Sql::updateq('spip_urls',array('segments'=>$segments),"url=".Sql::quote($row['url']));
		if (time() >= _TIME_OUT){
			Sql::free($res);
			return;
		}
	}
}

/**
 * Desinstallation/suppression des tables urls
 *
 * @param string $nom_meta_base_version
 */
function urls_vider_tables($nom_meta_base_version) {
	// repasser dans les urls par defaut
	ecrire_meta('type_urls','page');
	Sql::drop_table("spip_urls");
	effacer_meta($nom_meta_base_version);
}

?>