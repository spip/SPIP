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

/**
 * Installation/maj des tables forum
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function forum_upgrade($nom_meta_base_version,$version_cible){

	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])){
		$trouver_table = charger_fonction('trouver_table','base');
		$trouver_table(''); // vider le cache des descriptions !
		if ($desc = $trouver_table('spip_forum')
		  AND isset($desc['field']['id_article'])){
			ecrire_meta($nom_meta_base_version,'1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}

	$maj = array();
	$maj['create'] = array(
		array('maj_tables',array('spip_forum')),
	);
	$maj['1.1.0'] = array(
		array('Sql::alter',"TABLE spip_forum ADD id_objet bigint(21) DEFAULT 0 NOT NULL AFTER id_forum"),
		array('Sql::alter',"TABLE spip_forum ADD objet VARCHAR (25) DEFAULT '' NOT NULL AFTER id_objet"),
		#array('Sql::alter',"TABLE spip_forum DROP INDEX optimal"),
		#array('Sql::alter',"TABLE spip_forum ADD INDEX optimal (statut,id_parent,id_objet,objet,date_heure)"),
	);
	$maj['1.1.1'] = array(
		array('Sql::update',"spip_forum", array('objet' => "'breve'",'id_objet' => 'id_breve'), 'id_breve> 0'),
		#array('Sql::alter',"TABLE spip_forum DROP id_breve"),
		array('Sql::update',"spip_forum", array('objet' => "'article'",'id_objet' => 'id_article'), 'id_article>0'),
		#array('Sql::alter',"TABLE spip_forum DROP id_article"),
		array('Sql::update',"spip_forum", array('objet' => "'site'",'id_objet' => 'id_syndic'), 'id_syndic>0'),
		#array('Sql::alter',"TABLE spip_forum DROP id_syndic"),
		array('Sql::update',"spip_forum", array('objet' => "'message'",'id_objet' => 'id_message'), 'id_message>0'),
		#array('Sql::alter',"TABLE spip_forum DROP id_message"),
		array('Sql::update',"spip_forum", array('objet' => "'rubrique'",'id_objet' => 'id_rubrique'), 'id_rubrique>0'),
		#array('Sql::alter',"TABLE spip_forum DROP id_rubrique"),
	);

	# champ ip sur 40 car (compat IPv6)
	$maj['1.2.0'] = array(
		array('Sql::alter',"TABLE spip_forum CHANGE ip ip VARCHAR(40) DEFAULT '' NOT NULL"),
	);
	# rejouer la suppression/creation de l'index optimal
	# et la suppression des vieux champs, car la premiere sequence avait echoue
	# en raison d'un DROP KEY au lieu de DROP INDEX
	$maj['1.2.1'] = array(
		array('Sql::alter',"TABLE spip_forum DROP INDEX optimal"),
		array('Sql::alter',"TABLE spip_forum ADD INDEX optimal (statut,id_parent,id_objet,objet,date_heure)"),
		array('Sql::alter',"TABLE spip_forum DROP id_breve"),
		array('Sql::alter',"TABLE spip_forum DROP id_article"),
		array('Sql::alter',"TABLE spip_forum DROP id_syndic"),
		array('Sql::alter',"TABLE spip_forum DROP id_message"),
		array('Sql::alter',"TABLE spip_forum DROP id_rubrique"),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Desinstallation/suppression des tables forum
 *
 * @param string $nom_meta_base_version
 */
function forum_vider_tables($nom_meta_base_version) {
	Sql::drop_table("spip_forum");

	effacer_meta("mots_cles_forums");
	effacer_meta("forums_titre");
	effacer_meta("forums_texte");
	effacer_meta("forums_urlref");
	effacer_meta("forums_afficher_barre");
	effacer_meta("formats_documents_forum");
	effacer_meta("forums_publics");
	effacer_meta("forum_prive");
	effacer_meta("forum_prive_objets");
	effacer_meta("forum_prive_admin");

	effacer_meta($nom_meta_base_version);
}

?>
