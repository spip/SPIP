<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CHAMP_SQUEL")) return;
define("_INC_CHAMP_SQUEL", "1");

global $exceptions_des_tables, $table_des_tables;
global $tables_relations,  $table_primary, $table_date;





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
	'types_documents' => "id_document"
);


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
		'url_article' => 'url',			# ne sert pas ? cf balise_URL_ARTICLE
		'lesauteurs' => 'lesauteurs',	# ne sert pas ? cf balise_LESAUTEURS
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

?>
