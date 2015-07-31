<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CHAMP_SQUEL")) return;
define("_INC_CHAMP_SQUEL", "1");

global $exceptions_des_tables, $table_des_tables;
global $champs_traitement, $champs_pretraitement, $champs_posttraitement;
global $tables_relations,  $table_primary, $table_date;


//
// Construire un tableau des tables de relations
//

$tables_relations = '';

$tables_relations['articles']['id_mot'] = 'mots_articles';
$tables_relations['articles']['id_auteur'] = 'auteurs_articles';
$tables_relations['articles']['id_document'] = 'documents_articles';

$tables_relations['auteurs']['id_article'] = 'auteurs_articles';

$tables_relations['breves']['id_mot'] = 'mots_breves';
$tables_relations['breves']['id_document'] = 'documents_breves';

$tables_relations['documents']['id_article'] = 'documents_articles';
$tables_relations['documents']['id_rubrique'] = 'documents_rubriques';
$tables_relations['documents']['id_breve'] = 'documents_breves';

$tables_relations['forums']['id_mot'] = 'mots_forum';

$tables_relations['mots']['id_article'] = 'mots_articles';
$tables_relations['mots']['id_breve'] = 'mots_breves';
$tables_relations['mots']['id_forum'] = 'mots_forum';
$tables_relations['mots']['id_rubrique'] = 'mots_rubriques';
$tables_relations['mots']['id_syndic'] = 'mots_syndic';

$tables_relations['groupes_mots']['id_groupe'] = 'mots';

$tables_relations['rubriques']['id_mot'] = 'mots_rubriques';
$tables_relations['rubriques']['id_document'] = 'documents_rubriques';

$tables_relations['syndication']['id_mot'] = 'mots_syndic';

//
// Construire un tableau associatif des pre-traitements de champs
//

// Textes utilisateur : ajouter la securite anti-script
$c = array('NOM_SITE_SPIP', 'URL_SITE_SPIP', 'EMAIL_WEBMASTER', 'CHARSET',
	'TITRE', 'SURTITRE', 'SOUSTITRE', 'DESCRIPTIF', 'CHAPO', 'TEXTE', 'PS', 'NOTES', 'INTRODUCTION', 'MESSAGE',
	'LESAUTEURS', 'EMAIL', 'NOM_SITE', 'LIEN_TITRE', 'URL_SITE', 'LIEN_URL', 'NOM', 'IP', 'BIO', 'TYPE', 'PGP',
	'RECHERCHE'
);
reset($c);
while (list(, $val) = each($c)) {
	$champs_pretraitement[$val][] = 'trim';
	$champs_posttraitement[$val][] = 'interdire_scripts';
}
$c = array('EXTRA');
reset($c);
while (list(, $val) = each($c)) {
  $champs_posttraitement[$val][] = 'interdire_scripts';
}
   
// Textes courts : ajouter le traitement typographique
$c = array('NOM_SITE_SPIP', 'SURTITRE', 'TITRE', 'SOUSTITRE', 'NOM_SITE', 'LIEN_TITRE', 'NOM', 'TYPE');
reset($c);
while (list(, $val) = each($c)) {
	$champs_traitement[$val][] = 'typo';
}

// Chapo : ne pas l'afficher si article virtuel
$c = array('CHAPO');
reset($c);
while (list(, $val) = each($c)) {
	$champs_traitement[$val][] = 'nettoyer_chapo';
}

// Textes longs : ajouter le traitement typographique + mise en forme
$c = array('DESCRIPTIF', 'CHAPO', 'TEXTE', 'PS', 'BIO', 'MESSAGE');
reset($c);
while (list(, $val) = each($c)) {
	$champs_traitement[$val][] = 'traiter_raccourcis';
}

// Dates : ajouter le vidage des dates egales a 00-00-0000
$c = array('DATE', 'DATE_REDAC', 'DATE_MODIF', 'DATE_NOUVEAUTES');
reset($c);
while (list(, $val) = each($c)) {
	$champs_traitement[$val][] = 'vider_date';
}

// URL_SITE : vider les url == 'http://'
$c = array('URL_SITE_SPIP', 'URL_SITE', 'LIEN_URL');
reset($c);
while (list(, $val) = each($c)) {
	$champs_traitement[$val][] = 'vider_url';
}

// URLs : remplacer les & par &amp;
$c = array('URL_SITE_SPIP', 'URL_SITE', 'LIEN_URL', 'PARAMETRES_FORUM',
	'URL_ARTICLE', 'URL_RUBRIQUE', 'URL_BREVE', 'URL_FORUM', 'URL_SYNDIC', 'URL_MOT', 'URL_DOCUMENT');
reset($c);
while (list(, $val) = each($c)) {
	$champs_traitement[$val][] = 'htmlspecialchars';
}

// champ principal des tables SQL

$table_primary = array(
	'articles' => "id_article",
	'auteurs' => "id_auteur",
	'breves' => "id_breve",
	'documents' => "id_document",
	'forums' => "id_forum",
	'groupes_mots'	=> "id_groupe",
	'hierarchie' => "id_rubrique",
	'mots'	=> "id_mot",
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
	'syndic_articles' => 'articles',
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
		'url_article' => 'url',
		'lesauteurs' => 'lesauteurs',
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
