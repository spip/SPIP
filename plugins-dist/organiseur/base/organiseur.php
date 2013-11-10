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
 * Declaration des champs complementaires sur la table auteurs, pour les clients
 *
 * @param array $tables
 * @return array
 */
function organiseur_declarer_tables_objets_sql($tables){

	$tables['spip_auteurs']['field']["imessage"] = "VARCHAR(3)";
	$tables['spip_auteurs']['field']["messagerie"] = "VARCHAR(3)";

	$tables['spip_messages'] = array(
		'page'=> false,
		'texte_modifier' => 'icone_modifier_message',
		'texte_creer' => 'icone_ecrire_nouveau_message',
		'texte_objets' => 'organiseur:messages',
		'texte_objet' => 'organiseur:message',
		'info_aucun_objet'=> 'info_aucun_message',
		'info_1_objet' => 'info_1_message',
		'info_nb_objets' => 'info_nb_messages',

		'principale' => 'oui',
		'champs_editables' => array('titre', 'texte', 'type','date_heure', 'date_fin', 'rv', 'id_auteur', 'destinataires'),
		'field' => array(
			"id_message"	=> "bigint(21) NOT NULL",
			"titre"	=> "text DEFAULT '' NOT NULL",
			"texte"	=> "longtext DEFAULT '' NOT NULL",
			// normal,
			// pb (pense bete)
			// affich (annonce publique)
			"type"	=> "varchar(6) DEFAULT '' NOT NULL",
			"date_heure"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"date_fin"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"rv"	=> "varchar(3) DEFAULT '' NOT NULL",
			"statut"	=> "varchar(6)  DEFAULT '0' NOT NULL",
			"id_auteur"	=> "bigint(21) DEFAULT 0 NOT NULL",
			"destinataires" => "text DEFAULT '' NOT NULL",
			"maj"	=> "TIMESTAMP"
		),
		'key' => array(
			"PRIMARY KEY"	=> "id_message",
			"KEY id_auteur"	=> "id_auteur"
		),
		'titre' => "titre, '' AS lang",
		'date' => 'date_heure',
		'statut' => array(
			array(
				'champ' => 'statut',
				'publie' => 'publie',
				'previsu' => '!',
				'exception' => array('statut','tout')
			),
		),
		'rechercher_champs' => array(
			'titre' => 8, 'texte' => 1
		),

	);

	return $tables;
	
}

/**
 * Interfaces des tables agenda et messagerie
 *
 * @param array $interfaces
 * @return array
 */
function organiseur_declarer_tables_interfaces($interfaces){
	$interfaces['table_des_tables']['messages']='messages';

	return $interfaces;
}

?>
