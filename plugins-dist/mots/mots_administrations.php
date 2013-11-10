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
 * Installation/maj des tables mots et groupes de mots...
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function mots_upgrade($nom_meta_base_version,$version_cible){

	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])){
		$trouver_table = charger_fonction('trouver_table','base');
		if ($desc = $trouver_table('spip_mots')
		  AND isset($desc['exist'])
		  AND $desc = $trouver_table('spip_mots_articles')
			AND isset($desc['exist'])){
			ecrire_meta($nom_meta_base_version,'1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}

	$maj = array();
	$maj['create'] = array(
		array('maj_tables',array('spip_groupes_mots','spip_mots','spip_mots_liens')),
	);
	$maj['1.0.0'] = array(
		array('maj_tables',array('spip_groupes_mots','spip_mots','spip_mots_liens')),
	);
	include_spip('maj/svn10000');
	$maj['2.0.0'] = array(
		array('maj_liens','mot'), // creer la table liens
		array('maj_liens','mot','breve'),
		array('Sql::drop_table',"spip_mots_breves"),
		array('maj_liens','mot','rubrique'),
		array('Sql::drop_table',"spip_mots_rubriques"),
		array('maj_liens','mot','syndic'),
		array('Sql::drop_table',"spip_mots_syndic"),
		array('maj_liens','mot','forum'),
		array('Sql::drop_table',"spip_mots_forum"),
		array('maj_liens','mot','auteur'),
		array('Sql::drop_table',"spip_mots_auteurs"),
		array('maj_liens','mot','document'),
		array('Sql::drop_table',"spip_mots_documents"),
		array('maj_liens','mot','article'),
		array('Sql::drop_table',"spip_mots_articles"),
	);
	$maj['2.0.1'] = array(
		array('Sql::updateq',"spip_mots_liens",array('objet'=>'site'),"objet='syndic'"),
	);
	$maj['2.1.0'] = array(
		array('Sql::alter',"TABLE spip_mots_liens ADD INDEX id_objet (id_objet)"),
		array('Sql::alter',"TABLE spip_mots_liens ADD INDEX objet (objet)"),
	);
	$maj['2.1.1'] = array(
		array('Sql::alter',"TABLE spip_mots ADD INDEX id_groupe (id_groupe)")
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Desinstallation/suppression des tables mots et groupes de mots
 *
 * @param string $nom_meta_base_version
 */
function mots_vider_tables($nom_meta_base_version) {
	Sql::drop_table("spip_mots");
	Sql::drop_table("spip_groupes_mots");
	Sql::drop_table("spip_mots_liens");
	
	effacer_meta('articles_mots');
	effacer_meta('config_precise_groupes');
	
	effacer_meta($nom_meta_base_version);
}

?>
