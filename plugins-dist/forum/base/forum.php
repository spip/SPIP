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
 * Interfaces de la table forum pour le compilateur
 *
 * @param array $interfaces
 * @return array $interfaces
 */
function forum_declarer_tables_interfaces($interfaces){

	$interfaces['table_des_tables']['forums']='forum';

	$interfaces['exceptions_des_tables']['forums']['date']='date_heure';
	$interfaces['exceptions_des_tables']['forums']['nom']='auteur';
	$interfaces['exceptions_des_tables']['forums']['email']='email_auteur';

	// il ne faut pas essayer de chercher le forum du mot cle, mais bien le mot cle associe au forum
	$interfaces['exceptions_des_jointures']['spip_forum']['id_mot'] = array('spip_mots','id_mot');
	$interfaces['exceptions_des_jointures']['spip_forum']['titre_mot'] = array('spip_mots','titre');
	$interfaces['exceptions_des_jointures']['spip_forum']['type_mot'] = array('spip_mots','type');
	$interfaces['exceptions_des_jointures']['spip_forum']['id_groupe'] = array('spip_mots','id_groupe');

	
	#$interfaces['table_titre']['forums']= "titre, '' AS lang";
	#$interfaces['table_date']['forums']='date_heure';

	$interfaces['table_statut']['spip_forum'][] = array('champ'=>'statut','publie'=>'publie','previsu'=>'publie,prop','exception'=>'statut');

	$interfaces['table_des_traitements']['PARAMETRES_FORUM'][]= 'htmlspecialchars(%s)';
	$interfaces['table_des_traitements']['TEXTE']['forums']= "liens_nofollow(safehtml(".str_replace("%s","interdit_html(%s)",_TRAITEMENT_RACCOURCIS)."))";
	$interfaces['table_des_traitements']['TITRE']['forums']= "liens_nofollow(safehtml(".str_replace("%s","interdit_html(%s)",_TRAITEMENT_TYPO)."))";
	$interfaces['table_des_traitements']['NOTES']['forums']= "liens_nofollow(safehtml(".str_replace("%s","interdit_html(%s)",_TRAITEMENT_RACCOURCIS)."))";
	$interfaces['table_des_traitements']['NOM_SITE']['forums']=  "liens_nofollow(safehtml(".str_replace("%s","interdit_html(%s)",_TRAITEMENT_TYPO)."))";
	$interfaces['table_des_traitements']['URL_SITE']['forums']= 'safehtml(vider_url(%s))';
	$interfaces['table_des_traitements']['AUTEUR']['forums']= 'liens_nofollow(safehtml(vider_url(%s)))';
	$interfaces['table_des_traitements']['EMAIL_AUTEUR']['forums']= 'safehtml(vider_url(%s))';

	return $interfaces;
}

/**
 * Déclaration de la table spip_forum et de l'objet forum
 *
 * @param array $tables Tableau des objets déclarés
 * @return array $tables Tableau des objets complété
 */
function forum_declarer_tables_objets_sql($tables){
	$tables['spip_forum'] = array(
		'table_objet'=>'forums', # ??? hum hum redevient spip_forum par table_objet_sql mais casse par un bete "spip_".table_objet()
		'type'=>'forum',
		'url_voir'=>'controler_forum',
		'url_edit'=>'controler_forum',
		'editable'=>'non',
		'principale' => 'oui',
		'page'=>'', // pas de page editoriale pour un forum

		'texte_retour' => 'icone_retour',
		'texte_objets' => 'forum:forum',
		'texte_objet' => 'forum:forum',
		'info_aucun_objet'=> 'forum:aucun_message_forum',
		'info_1_objet' => 'forum:info_1_message_forum',
		'info_nb_objets' => 'forum:info_nb_messages_forum',
		'titre' => "titre, '' AS lang",
		'date' => 'date_heure',

		'champs_editables' => array('titre', 'texte', 'nom_site', 'url_site'),

		'field'=> array(
			"id_forum"	=> "bigint(21) NOT NULL",
			"id_objet"	=> "bigint(21) DEFAULT '0' NOT NULL",
			"objet"		=> "VARCHAR (25) DEFAULT '' NOT NULL",
			"id_parent"	=> "bigint(21) DEFAULT '0' NOT NULL",
			"id_thread"	=> "bigint(21) DEFAULT '0' NOT NULL",
			"date_heure"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"date_thread"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"titre"	=> "text DEFAULT '' NOT NULL",
			"texte"	=> "mediumtext DEFAULT '' NOT NULL",
			"auteur"	=> "text DEFAULT '' NOT NULL",
			"email_auteur"	=> "text DEFAULT '' NOT NULL",
			"nom_site"	=> "text DEFAULT '' NOT NULL",
			"url_site"	=> "text DEFAULT '' NOT NULL",
			"statut"	=> "varchar(8) DEFAULT '0' NOT NULL",
			"ip"	=> "varchar(40) DEFAULT '' NOT NULL",
			"maj"	=> "TIMESTAMP",
			"id_auteur"	=> "bigint DEFAULT '0' NOT NULL"
		),
		'key' => array(
			"PRIMARY KEY"	=> "id_forum",
			"KEY id_auteur"	=> "id_auteur",
			"KEY id_parent"	=> "id_parent",
			"KEY id_thread"	=> "id_thread",
			"KEY optimal" => "statut,id_parent,id_objet,objet,date_heure"
		),
		'join' => array(
			"id_forum"=>"id_forum",
			"id_parent"=>"id_parent",
			"id_objet"=>"id_objet",
			"objet"=>"objet",
			"id_auteur"=>"id_auteur",
		),
		'rechercher_champs' => array(
	    	'titre' => 3, 'texte' => 1, 'auteur' => 2, 'email_auteur' => 2, 'nom_site' => 1, 'url_site' => 1
		),
	);

	// jointures sur les forum pour tous les objets
	$tables[]['tables_jointures'][]= 'forums';

	return $tables;
}

?>
