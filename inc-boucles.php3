<?php

//
// Ce fichier definit les boucles standard de SPIP
//


// Ce fichier ne sera execute qu'une fois
if (defined("_INC_BOUCLES")) return;
define("_INC_BOUCLES", "1");


//
// Globales de description de la base
//
# le bloc qui suit est un peu sale, peut-etre faudrait-il definir
# ces choses-la au meme endroit qu'on definit le contenu des tables
# de la base de donnees, ie ecrire/inc_serial_base et ecrire/inc_auxbase !
# et sous forme de fonctions
{

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

}


//
// Boucle sur une table hors SPIP, pourquoi pas
//
function boucle_DEFAUT(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "$type AS $type";
	$id_field = '*'; // utile a TOTAL_BOUCLE seulement
}


//
// <BOUCLE(ARTICLES)>
//
function boucle_ARTICLES_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "articles AS $id_table";
	if (!$GLOBALS['var_preview']) {
		$boucle->where[] = "$id_table.statut='publie'";
		if (lire_meta("post_dates") == 'non')
			$boucle->where[] = "$id_table.date < NOW()";
	} else
		$boucle->where[] = "$id_table.statut IN ('publie','prop')";
}

//
// <BOUCLE(AUTEURS)>
//
function boucle_AUTEURS_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "auteurs AS $id_table";
	// Si pas de lien avec un article, selectionner
	// uniquement les auteurs d'un article publie
	if (!$GLOBALS['var_preview'])
	if (!$boucle->lien AND !$boucle->tout) {
		$boucle->from[] =  "auteurs_articles AS lien";
		$boucle->from[] =  "articles AS articles";
		$boucle->where[] = "lien.id_auteur=$id_table.id_auteur";
		$boucle->where[] = 'lien.id_article=articles.id_article';
		$boucle->where[] = "articles.statut='publie'";
		$boucle->group =  "$id_field";
	}
	// pas d'auteurs poubellises
	$boucle->where[] = "NOT($id_table.statut='5poubelle')";
}

//
// <BOUCLE(BREVES)>
//
function boucle_BREVES_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "breves AS $id_table";
	if (!$GLOBALS['var_preview'])
		$boucle->where[] = "$id_table.statut='publie'";
	else
		$boucle->where[] = "$id_table.statut IN ('publie','prop')";
}


//
// <BOUCLE(FORUMS)>
//
function boucle_FORUMS_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "forum AS $id_table";
	// Par defaut, selectionner uniquement les forums sans pere
	if (!$boucle->tout AND !$boucle->plat) 
		{
	$boucle->where[] = "$id_table.id_parent=0";
		}
	$boucle->where[] = "$id_table.statut='publie'";
}


//
// <BOUCLE(SIGNATURES)>
//
function boucle_SIGNATURES_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "signatures AS $id_table";
	$boucle->from[] =  "petitions AS petitions";
	$boucle->from[] =  "articles articles";
	$boucle->where[] = "petitions.id_article=articles.id_article";
	$boucle->where[] = "petitions.id_article=$id_table.id_article";
	$boucle->where[] = "$id_table.statut='publie'";
	$boucle->group = "$id_field";
}


//
// <BOUCLE(DOCUMENTS)>
//
function boucle_DOCUMENTS_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "documents AS $id_table";
	$boucle->from[] =  "types_documents AS types_documents";
	$boucle->where[] = "$id_table.id_type=types_documents.id_type";
	$boucle->where[] = "$id_table.taille > 0";
}


//
// <BOUCLE(TYPES_DOCUMENTS)>
//
function boucle_TYPES_DOCUMENTS_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "types_documents AS $id_table";
}


//
// <BOUCLE(GROUPES_MOTS)>
//
function boucle_GROUPES_MOTS_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "groupes_mots AS $id_table";
}


//
// <BOUCLE(MOTS)>
//
function boucle_MOTS_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "mots AS $id_table";
}


//
// <BOUCLE(RUBRIQUES)>
//
function boucle_RUBRIQUES_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "rubriques AS $id_table";
	if (!$GLOBALS['var_preview'])
	if (!$boucle->tout) $boucle->where[] = "$id_table.statut='publie'";
}


//
// <BOUCLE(HIERARCHIE)>
//
function boucle_HIERARCHIE_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "rubriques AS $id_table";

	// $hierarchie sera calculee par une fonction de inc-calcul-mysql
	// inc-criteres supprimera le parametre {id_article/id_rubrique/id_syndic}
	$boucle->where[] = 'id_rubrique IN ($hierarchie)';
	$boucle->select[] = 'FIND_IN_SET(id_rubrique, \'$hierarchie\')-1 AS rang';
	$boucle->order = 'rang';
	$boucle->hierarchie = '$hierarchie = calculer_hierarchie('
	. calculer_argument_precedent($boucle->id_boucle, 'id_rubrique', $boucles)
	. ', false);';
}


//
// <BOUCLE(SYNDICATION)>
//
function boucle_SYNDICATION_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "syndic AS $id_table";
	$boucle->where[] = "$id_table.statut='publie'";
}


//
// <BOUCLE(SYNDIC_ARTICLES)>
//
function boucle_SYNDIC_ARTICLES_dist(&$boucle, &$boucles, $type, $id_table, $id_field) {
	$boucle->from[] =  "syndic_articles  AS $id_table";
	$boucle->from[] =  "syndic AS syndic";
	$boucle->where[] = "$id_table.id_syndic=syndic.id_syndic";
	$boucle->where[] = "$id_table.statut='publie'";
	$boucle->where[] = "syndic.statut='publie'";
	$boucle->select[]='syndic.nom_site AS nom_site'; # derogation zarbi
	$boucle->select[]='syndic.url_site AS url_site'; # idem
}


?>
