<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CHAMP_SQUEL")) return;
define("_INC_CHAMP_SQUEL", "1");

global $exceptions_des_tables, $table_des_tables;
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

function champs_traitements ($nom_champ) {
	static $traitements = array (
		'BIO' => 'traiter_raccourcis(%s)',
		'CHAPO' => 'traiter_raccourcis(nettoyer_chapo(%s))',
		'DATE' => 'vider_date(%s)',
		'DATE_MODIF' => 'vider_date(%s)',
		'DATE_NOUVEAUTES' => 'vider_date(%s)',
		'DATE_REDAC' => 'vider_date(%s)',
		'DESCRIPTIF' => 'traiter_raccourcis(%s)',
		'LIEN_TITRE' => 'typo(%s)',
		'LIEN_URL' => 'htmlspecialchars(vider_url(%s))',
		'MESSAGE' => 'traiter_raccourcis(%s)',
		'NOM_SITE_SPIP' => 'typo(%s)',
		'NOM' => 'typo(%s)',
		'PARAMETRES_FORUM' => 'htmlspecialchars(%s)',
		'PS' => 'traiter_raccourcis(%s)',
		'SOUSTITRE' => 'typo(%s)',
		'SURTITRE' => 'typo(%s)',
		'TEXTE' => 'traiter_raccourcis(%s)',
		'TITRE' => 'typo(%s)',
		'TYPE' => 'typo(%s)',
		'URL_ARTICLE' => 'htmlspecialchars(vider_url(%s))',
		'URL_BREVE' => 'htmlspecialchars(vider_url(%s))',
		'URL_DOCUMENT' => 'htmlspecialchars(vider_url(%s))',
		'URL_FORUM' => 'htmlspecialchars(vider_url(%s))',
		'URL_MOT' => 'htmlspecialchars(vider_url(%s))',
		'URL_RUBRIQUE' => 'htmlspecialchars(vider_url(%s))',
		'URL_SITE_SPIP' => 'htmlspecialchars(vider_url(%s))',
		'URL_SITE' => 'htmlspecialchars(vider_url(%s))',
		'URL_SYNDIC' => 'htmlspecialchars(vider_url(%s))'
	);

	return $traitements[$nom_champ];
}



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
