<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


// Ce fichier ne sera execute qu'une fois
if (defined("_INC_COMPILO_API")) return;
define("_INC_COMPILO_API", "1");


// Definition des classes Boucle, Texte, Inclure, Champ

class Texte {
	var $type = 'texte';
	var $texte;
}

class Inclure {
	var $type = 'include';
	var $fichier;
	var $params;
}

//
// encodage d'une boucle SPIP en un objet PHP
//
class Boucle {
	var $type = 'boucle';
	var $id_boucle;
	var $id_parent ='';
	var $cond_avant, $milieu, $cond_apres, $cond_altern;
	var $lang_select;
	var $type_requete;
	var $sql_serveur;
	var $param = array();
	var $separateur = array();
	var $doublons;
	var $partie, $total_parties,$mode_partie;
	var $externe = ''; # appel a partir d'une autre boucle (recursion)
	// champs pour la construction de la requete SQL
	var $tout = false;
	var $plat = false;
	var $select;
	var $from;
	var $where;
	var $limit;
	var $group = '';
	var $order = '';
	var $default_order = '';
	var $date = 'date' ;
	var $hash = false ;
	var $lien = false;
	var $sous_requete = false;
	var $compte_requete = 1;
	var $hierarchie = '';
	// champs pour la construction du corps PHP
	var $id_table;
	var $primary;
	var $return;
	var $numrows = false; 
}

class Champ {
	var $type = 'champ';
	var $nom_champ;
	var $nom_boucle= ''; // seulement si boucle explicite
	var $cond_avant, $cond_apres; // tableaux d'objets
	var $fonctions;  // filtre explicites
	var $etoile;
	// champs pour la production de code
	var $id_boucle;
	var $boucles;
	var $type_requete;
	var $code;	// code du calcul
	var $statut;	// 'numerique, 'h'=texte (html) ou 'p'=script (php) ?
			// -> definira les pre et post-traitements obligatoires
	// champs pour la production de code dependant du contexte
	var $id_mere;    // pour TOTAL_BOUCLE hors du corps
	var $document;   // pour embed et img dans les textes
}


//
// Globales de description de la base

global $tables_des_serveurs_sql, $tables_principales; // (voir inc_serialbase)
global $exceptions_des_tables, $table_des_tables;
global $tables_relations,  $table_primary, $table_date;

$tables_des_serveurs_sql = array('localhost' => &$tables_principales);
	

	// champ principal des tables SQL
	$table_primary = array(
		'articles' => "id_article",
		'auteurs' => "id_auteur",
		'breves' => "id_breve",
		'documents' => "id_document",
		'forums' => "id_forum",
		'groupes_mots' => "id_groupe",
		'hierarchie' => "id_rubrique",
		'mots' => "id_mot",
		'rubriques' => "id_rubrique",
		'signatures' => "id_signature",
		'syndication' => "id_syndic",
		'syndic_articles' => "id_syndic_article",
		'types_documents' => "id_type"
	);
	
	# cf. fonction table_objet dans inc_version
	$table_des_tables = array(
		'articles' => 'articles',
		'auteurs' => 'auteurs',
		'breves' => 'breves',
		'forums' => 'forum',
		'signatures' => 'signatures',
		'documents' => 'documents',
		'types_documents' => 'types_documents',
		'mots' => 'mots',
		'groupes_mots' => 'groupes_mots',
		'rubriques' => 'rubriques',
		'syndication' => 'syndic',
		'syndic_articles' => 'syndic_articles',
		'hierarchie' => 'rubriques'
	);
	
	$exceptions_des_tables = array(
		'breves' => array(
			'id_secteur' => 'id_rubrique',
			'date' => 'date_heure',
			'nom_site' => 'lien_titre',
			'url_site' => 'lien_url'
			),
		'forums' => array(
			'date' => 'date_heure',
			'nom' => 'auteur',
			'email' => 'email_auteur'
			),
		'signatures' => array(
			'date' => 'date_time',
			'nom' => 'nom_email',
			'email' => 'ad_email'
		),
		'documents' => array(
			'type_document' => array('types_documents', 'titre'),
			'extension_document' => array('types_documents', 'extension')
		),
		'syndic_articles' => array(
			'url_article' => 'url',		  # ne sert pas ? cf balise_URL_ARTICLE
			'lesauteurs' => 'lesauteurs', # ne sert pas ? cf balise_LESAUTEURS
			'url_site' => array('syndic', 'url_site'),
			'nom_site' => array('syndic', 'nom_site')
		)
	);
	
	$table_date = array (
		'articles' => 'date',
		'auteurs' =>  'date',
		'breves' =>  'date_heure',
		'forums' =>  'date_heure',
		'signatures' => 'date_time',
		'documents' => 'date',
		'types_documents' => 'date',
		'groupes_mots' => 'date',
		'mots' => 'date',
		'rubriques' => 'date',
		'syndication' => 'date',
		'syndic_articles' => 'date'
	);


//
// tableau des tables de relations,
// Ex: gestion du critere {id_mot} dans la boucle(ARTICLES)
//

$tables_relations = array(
	'articles' => array (
		'id_mot' => 'mots_articles',
		'id_auteur' => 'auteurs_articles',
		'id_document' => 'documents_articles'
		),

	'auteurs' => array (
		'id_article' => 'auteurs_articles'
		),

	'breves' => array (
		'id_mot' => 'mots_breves',
		'id_document' => 'documents_breves'
		),

	'documents' => array (
		'id_article' => 'documents_articles',
		'id_rubrique' => 'documents_rubriques',
		'id_breve' => 'documents_breves',
		'id_mot' => 'mots_documents',
		'id_syndic' => 'documents_syndic',
		'id_syndic_article' => 'documents_syndic'
		),

	'forums' => array (
		'id_mot' => 'mots_forum',
		),

	'mots' => array (
		'id_article' => 'mots_articles',
		'id_breve' => 'mots_breves',
		'id_forum' => 'mots_forum',
		'id_rubrique' => 'mots_rubriques',
		'id_syndic' => 'mots_syndic',
		'id_document' => 'mots_documents'
		),

	'groupes_mots' => array (
		'id_groupe' => 'mots'
		),

	'rubriques' => array (
		'id_mot' => 'mots_rubriques',
		'id_document' => 'documents_rubriques'
		),

	'syndication' => array (
		'id_mot' => 'mots_syndic'
		)
	);
?>
