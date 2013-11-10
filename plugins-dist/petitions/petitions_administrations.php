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
 * Installation/maj des tables petitions et signatures
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function petitions_upgrade($nom_meta_base_version,$version_cible){
	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])){
		$trouver_table = charger_fonction('trouver_table','base');
		if ($desc = $trouver_table('spip_signatures')
		  AND isset($desc['field']['id_article'])){
			ecrire_meta($nom_meta_base_version,'1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}

	$maj = array();
	$maj['create'] = array(
		array('maj_tables',array('spip_petitions','spip_signatures')),
	);

	$maj['1.1.0'] = array(
		array('Sql::alter',"TABLE spip_petitions DROP PRIMARY KEY"),
	);
	$maj['1.1.1'] = array(
		array('Sql::alter',"TABLE spip_petitions ADD UNIQUE id_article (id_article)"),
	);
	$maj['1.1.2'] = array(
		array('Sql::alter',"TABLE spip_petitions ADD id_petition BIGINT(21) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST"),
		array('Sql::alter',"TABLE spip_petitions ADD PRIMARY KEY (id_petition)"),
	);
	$maj['1.1.3'] = array(
		array('Sql::alter',"TABLE spip_petitions ADD statut VARCHAR (10) DEFAULT 'publie' NOT NULL"),
	);
	$maj['1.1.4'] = array(
		array('Sql::alter',"TABLE spip_signatures ADD id_petition bigint(21) DEFAULT '0' NOT NULL"),
		array('Sql::alter',"TABLE spip_signatures ADD INDEX id_petition (id_petition)"),
		array('Sql::updateq','spip_signatures',array('id_petition'=>-1)),
	);
	$maj['1.1.5'] = array(
		array('upgrade_index_signatures'),
	);
	$maj['1.1.6'] = array(
		array('Sql::alter',"TABLE spip_signatures DROP INDEX id_article"),
		array('Sql::alter',"TABLE spip_signatures DROP id_article"),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function upgrade_index_signatures(){
	while ($rows = Sql::allfetsel('DISTINCT id_article','spip_signatures','id_petition=-1','','','0,100')) {
		$rows = array_map('reset',$rows);
		foreach($rows as $id_article){
			$id_petition = Sql::getfetsel('id_petition','spip_petitions','id_article='.intval($id_article));
			if (!$id_petition){
				include_spip('action/editer_petition');
				$id_petition = petition_inserer($id_article);
				Sql::updateq('spip_petitions',array('statut'=>'poubelle'),'id_petition='.$id_petition);
			}
			Sql::updateq('spip_signatures',array('id_petition'=>$id_petition),'id_article='.$id_article);
		}
	}
}

/**
 * Desinstallation/suppression des tables petitions et signatures
 *
 * @param string $nom_meta_base_version
 */
function petitions_vider_tables($nom_meta_base_version) {
	Sql::drop_table("spip_petitions");
	Sql::drop_table("spip_signatures");

	effacer_meta($nom_meta_base_version);
}

?>
