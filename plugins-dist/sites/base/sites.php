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
 * Interfaces des tables syndic et syndic article
 *
 * @param array $interfaces
 * @return array
 */
function sites_declarer_tables_interfaces($interfaces){

	$interfaces['table_des_tables']['sites']='syndic'; // compat pour les boucles (SITES)
	$interfaces['table_des_tables']['syndication']='syndic';
	$interfaces['table_des_tables']['syndic']='syndic';
	$interfaces['table_des_tables']['syndic_articles']='syndic_articles';

	# ne sert plus ? verifier balise_URL_ARTICLE
	$interfaces['exceptions_des_tables']['syndic_articles']['url_article']='url';
	# ne sert plus ? verifier balise_LESAUTEURS
	$interfaces['exceptions_des_tables']['syndic_articles']['lesauteurs']='lesauteurs';
	$interfaces['exceptions_des_tables']['syndic_articles']['url_site']=array('syndic', 'url_site');
	$interfaces['exceptions_des_tables']['syndic_articles']['nom_site']=array('syndic', 'nom_site');

	$interfaces['table_date']['syndication'] = 'date';

	$interfaces['tables_jointures']['spip_syndic_articles'][]= 'syndic';

	$interfaces['table_des_traitements']['NOM_SITE'][]=  _TRAITEMENT_TYPO;

	// Articles syndiques : passage des donnees telles quelles, sans traitement typo
	// la securite et conformite XHTML de ces champs est assuree par safehtml()
	foreach(array('DESCRIPTIF','SOURCE','URL','URL_SOURCE','LESAUTEURS','TAGS') as $balise)
		if (!isset($interfaces['table_des_traitements'][$balise]['syndic_articles']))
			$interfaces['table_des_traitements'][$balise]['syndic_articles'] = 'safehtml(%s)';
		else
			if (strpos($interfaces['table_des_traitements'][$balise]['syndic_articles'],'safehtml')==false)
				$interfaces['table_des_traitements'][$balise]['syndic_articles'] = 'safehtml('.$interfaces['table_des_traitements'][$balise]['syndic_articles'].')';

	return $interfaces;
}


function sites_declarer_tables_objets_sql($tables){
	$tables['spip_syndic'] = array(
		'table_objet_surnoms'=>array('site'),
		'type'=>'site',
	  'type_surnoms' => array('syndic'),
		
		'texte_retour' => 'icone_retour',
		'texte_objets' => 'icone_sites_references',
		'texte_objet' => 'sites:icone_site_reference',
		'texte_modifier' => 'sites:icone_modifier_site',
		'texte_creer' => 'sites:icone_referencer_nouveau_site',
		'info_aucun_objet'=> 'info_aucun_site',
		'info_1_objet' => 'info_1_site',
		'info_nb_objets' => 'info_nb_sites',
		'titre' => "nom_site AS titre, '' AS lang",
		'date' => 'date',
		'principale' => 'oui',
		'field'=> array(
			"id_syndic"	=> "bigint(21) NOT NULL",
			"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
			"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
			"nom_site"	=> "text DEFAULT '' NOT NULL",
			"url_site"	=> "text DEFAULT '' NOT NULL",
			"url_syndic"	=> "text DEFAULT '' NOT NULL",
			"descriptif"	=> "text DEFAULT '' NOT NULL",
			"maj"	=> "TIMESTAMP",
			"syndication"	=> "VARCHAR(3) DEFAULT '' NOT NULL",
			"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
			"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"date_syndic"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"date_index"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"moderation"	=> "VARCHAR(3) DEFAULT 'non'",
			"miroir"	=> "VARCHAR(3) DEFAULT 'non'",
			"oubli"	=> "VARCHAR(3) DEFAULT 'non'",
			"resume"	=> "VARCHAR(3) DEFAULT 'oui'"
		),
		'key' => array(
			"PRIMARY KEY"	=> "id_syndic",
			"KEY id_rubrique"	=> "id_rubrique",
			"KEY id_secteur"	=> "id_secteur",
			"KEY statut"	=> "statut, date_syndic",
		),
		'join' => array(
			"id_syndic"=>"id_syndic",
			"id_rubrique"=>"id_rubrique"
		),
		'statut' => array(
			 array('champ'=>'statut','publie'=>'publie','previsu'=>'publie,prop','exception'=>'statut')
		),
		'texte_changer_statut' => 'sites:info_statut_site_1',
		'statut_textes_instituer' => 	array(
			'prop' => 'texte_statut_propose_evaluation',
			'publie' => 'texte_statut_publie',
			'refuse' => 'texte_statut_poubelle',
		),

		'rechercher_champs' => array(
			'nom_site' => 5, 'url_site' => 1, 'descriptif' => 3
		),
		'champs_versionnes' => array('id_rubrique', 'id_secteur', 'nom_site', 'url_site', 'url_syndic','descriptif'),
	);

	$tables['spip_syndic_articles'] = array(
		'table_objet_surnoms'=>array('syndic_article'),

		'texte_retour' => 'icone_retour',
		'texte_objets' => 'sites:icone_articles_syndic',
		'texte_objet' => 'sites:icone_article_syndic',
		'texte_modifier' => 'icone_modifier_article', # inutile en vrai
		'info_aucun_objet'=> 'info_aucun_article_syndique',
		'info_1_objet' => 'info_1_article_syndique',
		'info_nb_objets' => 'info_nb_articles_syndiques',
		'icone_objet' => 'site',

		// pas de page propre ni dans ecrire ni dans le site public
		'url_voir'=>'',
		'url_edit'=>'',
		'page'=>'',
		
		'date' => 'date',
		'editable' => 'non',
		'principale' => 'oui',
		'field'=> array(
			"id_syndic_article"	=> "bigint(21) NOT NULL",
			"id_syndic"	=> "bigint(21) DEFAULT '0' NOT NULL",
			"titre"	=> "text DEFAULT '' NOT NULL",
			"url"	=> "VARCHAR(255) DEFAULT '' NOT NULL",
			"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"lesauteurs"	=> "text DEFAULT '' NOT NULL",
			"maj"	=> "TIMESTAMP",
			"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
			"descriptif"	=> "text DEFAULT '' NOT NULL",
			"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
			"url_source" => "TINYTEXT DEFAULT '' NOT NULL",
			"source" => "TINYTEXT DEFAULT '' NOT NULL",
			"tags" => "TEXT DEFAULT '' NOT NULL"
		),
		'key' => array(
			"PRIMARY KEY"	=> "id_syndic_article",
			"KEY id_syndic"	=> "id_syndic",
			"KEY statut"	=> "statut",
			"KEY url"	=> "url"
		),
		'join' => array(
			"id_syndic_article"=>"id_syndic_article",
			"id_syndic"=>"id_syndic"
		),
		'statut' => array(
			array('champ'=>'statut','publie'=>'publie','previsu'=>'publie,prop','exception'=>'statut'),
			array('champ'=>array(array('spip_syndic','id_syndic'),'statut'),'publie'=>'publie','previsu'=>'publie,prop','exception'=>'statut'),
		),
		'statut_images' => array(
			'puce-rouge-anim.gif','publie'=>'puce-publier-8.png','refuse'=>'puce-supprimer-8.png','dispo'=>'puce-proposer-8.png','off'=>'puce-refuser-8.png',
		),
		'rechercher_champs' => array(
				'titre' => 5, 'descriptif' => 1
		)
	);

	return $tables;
}

?>
