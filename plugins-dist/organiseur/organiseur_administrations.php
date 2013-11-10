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
 * Installation/maj des tables messagerie
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function organiseur_upgrade($nom_meta_base_version,$version_cible){
	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])){
		$trouver_table = charger_fonction('trouver_table','base');
		if ($desc = $trouver_table('spip_messages')
		  AND isset($desc['exist'])){
			ecrire_meta($nom_meta_base_version,'1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}
	
	$maj = array();
	$maj['create'] = array(
		array('maj_tables',array('spip_messages')),
		array('Sql::alter','TABLE spip_auteurs ADD imessage VARCHAR(3)'),
		array('Sql::alter','TABLE spip_auteurs ADD messagerie VARCHAR(3)'),
	);

	$maj['1.1.0'] = array(
		array('Sql::updateq',"spip_messages",array('statut'=>'prepa'),"statut='redac'"),
		array('Sql::alter',"TABLE spip_messages ADD destinataires text DEFAULT '' NOT NULL"),
	);

	$maj['1.1.1'] = array(
		array('Sql::alter',"TABLE spip_messages CHANGE id_auteur id_auteur bigint(21) DEFAULT 0 NOT NULL"),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Desinstallation/suppression des tables mots et groupes de mots
 *
 * @param string $nom_meta_base_version
 */
function organiseur_vider_tables($nom_meta_base_version) {
	Sql::drop_table("spip_messages");
	Sql::alter("TABLE spip_auteurs DROP imessage");
	Sql::alter("TABLE spip_auteurs DROP messagerie");

	effacer_meta('messagerie_agenda');

	effacer_meta($nom_meta_base_version);
}



?>