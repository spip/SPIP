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

/**
 * Installation du plugin révisions
 *
 * @package Revisions\Installation
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Installation/maj des tables révisions
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function revisions_upgrade($nom_meta_base_version,$version_cible){
	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])){
		$trouver_table = charger_fonction('trouver_table','base');
		if ($desc = $trouver_table('spip_versions')
		  AND isset($desc['exist'])){
			ecrire_meta($nom_meta_base_version,'1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}

	$maj = array();
	$maj['create'] = array(
		array('maj_tables',array('spip_versions','spip_versions_fragments')),
		array('revisions_upate_meta'),
	);

	$maj['1.1.0'] = array(
		// Ajout du champs objet et modification du champs id_article en id_objet
		// sur les 2 tables spip_versions et spip_versions_fragments
		array('Sql::alter',"TABLE spip_versions CHANGE id_article id_objet bigint(21) DEFAULT 0 NOT NULL"),
		array('Sql::alter',"TABLE spip_versions ADD objet VARCHAR (25) DEFAULT '' NOT NULL AFTER id_objet"),
		// Les id_objet restent les id_articles puisque les révisions n'étaient possibles que sur les articles
		array('Sql::updateq',"spip_versions",array('objet'=>'article'),"objet=''"),
		// Changement des clefs primaires également
		array('Sql::alter',"TABLE spip_versions DROP PRIMARY KEY"),
		array('Sql::alter',"TABLE spip_versions ADD PRIMARY KEY (id_version, id_objet, objet)"),

		array('Sql::alter',"TABLE spip_versions_fragments CHANGE id_article id_objet bigint(21) DEFAULT 0 NOT NULL"),
		array('Sql::alter',"TABLE spip_versions_fragments ADD objet VARCHAR (25) DEFAULT '' NOT NULL AFTER id_objet"),
		// Les id_objet restent les id_articles puisque les révisions n'étaient possibles que sur les articles
		array('Sql::updateq',"spip_versions_fragments",array('objet'=>'article'),"objet=''"),
		// Changement des clefs primaires également
		array('Sql::alter',"TABLE spip_versions_fragments DROP PRIMARY KEY"),
		array('Sql::alter',"TABLE spip_versions_fragments ADD PRIMARY KEY (id_objet, objet, id_fragment, version_min)"),
		array('revisions_upate_meta'),
	);
	$maj['1.1.2'] = array(
		array('revisions_upate_meta'),
		array('Sql::updateq',"spip_versions",array('objet'=>'article'),"objet=''"),
		array('Sql::updateq',"spip_versions_fragments",array('objet'=>'article'),"objet=''"),
	);
	$maj['1.1.3'] = array(
		array('Sql::alter',"TABLE spip_versions DROP KEY id_objet"),
		array('Sql::alter',"TABLE spip_versions ADD INDEX id_version (id_version)"),
		array('Sql::alter',"TABLE spip_versions ADD INDEX id_objet (id_objet)"),
		array('Sql::alter',"TABLE spip_versions ADD INDEX objet (objet)")
	);
	$maj['1.1.4'] = array(
		array('Sql::alter',"TABLE spip_versions CHANGE permanent permanent char(3) DEFAULT '' NOT NULL"),
		array('Sql::alter',"TABLE spip_versions CHANGE champs champs text DEFAULT '' NOT NULL"),
	);


	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);

}

/**
 * Desinstallation/suppression des tables revisions
 *
 * @param string $nom_meta_base_version
 */
function revisions_vider_tables($nom_meta_base_version) {
	Sql::drop_table("spip_versions");
	Sql::drop_table("spip_versions_fragments");

	effacer_meta($nom_meta_base_version);
}

/**
 * Mettre a jour la meta des versions
 * @return void
 */
function revisions_upate_meta(){
	// Si dans une installation antérieure ou un upgrade, les articles étaient versionnés
	// On crée la meta correspondante
	// mettre les metas par defaut
	$config = charger_fonction('config','inc');
	$config();
	if($GLOBALS['meta']['articles_versions'] == 'oui'){
		ecrire_meta('objets_versions',serialize(array('articles')));
	}
	effacer_meta('articles_versions');
	if (!$versions = unserialize($GLOBALS['meta']['objets_versions']))
		$versions = array();
	$versions = array_map('table_objet_sql',$versions);
	ecrire_meta('objets_versions',serialize($versions));
}


?>
