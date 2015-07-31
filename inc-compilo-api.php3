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

//ces variabales ne sont pas initialisees par "$var = array()"
// afin de permettre leur extension dans mes_options.php etc

global $tables_des_serveurs_sql, $tables_principales; // (voir inc_serialbase)
global $exceptions_des_tables, $table_des_tables;
global $tables_relations,  $table_primary, $table_date;

$tables_des_serveurs_sql = array('localhost' => &$tables_principales);
	

 // champ principal des tables SQL
$table_primary['articles']="id_article";
$table_primary['auteurs']="id_auteur";
$table_primary['breves']="id_breve";
$table_primary['documents']="id_document";
$table_primary['forums']="id_forum";
$table_primary['groupes_mots']="id_groupe";
$table_primary['hierarchie']="id_rubrique";
$table_primary['mots']="id_mot";
$table_primary['rubriques']="id_rubrique";
$table_primary['signatures']="id_signature";
$table_primary['syndication']="id_syndic";
$table_primary['syndic_articles']="id_syndic_article";
$table_primary['types_documents']="id_type";

 # cf. fonction table_objet dans inc_version
$table_des_tables['articles']='articles';
$table_des_tables['auteurs']='auteurs';
$table_des_tables['breves']='breves';
$table_des_tables['forums']='forum';
$table_des_tables['signatures']='signatures';
$table_des_tables['documents']='documents';
$table_des_tables['types_documents']='types_documents';
$table_des_tables['mots']='mots';
$table_des_tables['groupes_mots']='groupes_mots';
$table_des_tables['rubriques']='rubriques';
$table_des_tables['syndication']='syndic';
$table_des_tables['syndic_articles']='syndic_articles';
$table_des_tables['hierarchie']='rubriques';

$exceptions_des_tables['breves']['id_secteur']='id_rubrique';
$exceptions_des_tables['breves']['date']='date_heure';
$exceptions_des_tables['breves']['nom_site']='lien_titre';
$exceptions_des_tables['breves']['url_site']='lien_url';

$exceptions_des_tables['forums']['date']='date_heure';
$exceptions_des_tables['forums']['nom']='auteur';
$exceptions_des_tables['forums']['email']='email_auteur';

$exceptions_des_tables['signatures']['date']='date_time';
$exceptions_des_tables['signatures']['nom']='nom_email';
$exceptions_des_tables['signatures']['email']='ad_email';

$exceptions_des_tables['documents']['type_document']=array('types_documents'
, 'titre');
$exceptions_des_tables['documents']['extension_document']=array('types_docum
ents', 'extension');

# ne sert plus ? verifier balise_URL_ARTICLE
$exceptions_des_tables['syndic_articles']['url_article']='url';
# ne sert plus ? verifier balise_LESAUTEURS
$exceptions_des_tables['syndic_articles']['lesauteurs']='lesauteurs'; 
$exceptions_des_tables['syndic_articles']['url_site']=array('syndic',
'url_site');
$exceptions_des_tables['syndic_articles']['nom_site']=array('syndic',
'nom_site');

$table_date['articles']='date';
$table_date['auteurs']='date';
$table_date['breves']='date_heure';
$table_date['forums']='date_heure';
$table_date['signatures']='date_time';
$table_date['documents']='date';
$table_date['types_documents']='date';
$table_date['groupes_mots']='date';
$table_date['mots']='date';
$table_date['rubriques']='date';
$table_date['syndication']='date';
$table_date['syndic_articles']='date';

//
// tableau des tables de relations,
// Ex: gestion du critere {id_mot} dans la boucle(ARTICLES)
//
$tables_relations['articles']['id_mot']='mots_articles';
$tables_relations['articles']['id_auteur']='auteurs_articles';
$tables_relations['articles']['id_document']='documents_articles';

$tables_relations['auteurs']['id_article']='auteurs_articles';

$tables_relations['breves']['id_mot']='mots_breves';
$tables_relations['breves']['id_document']='documents_breves';

$tables_relations['documents']['id_article']='documents_articles';
$tables_relations['documents']['id_rubrique']='documents_rubriques';
$tables_relations['documents']['id_breve']='documents_breves';

$tables_relations['forums']['id_mot']='mots_forum';

$tables_relations['mots']['id_article']='mots_articles';
$tables_relations['mots']['id_breve']='mots_breves';
$tables_relations['mots']['id_forum']='mots_forum';
$tables_relations['mots']['id_rubrique']='mots_rubriques';
$tables_relations['mots']['id_syndic']='mots_syndic';

$tables_relations['groupes_mots']['id_groupe']='mots';

$tables_relations['rubriques']['id_mot']='mots_rubriques';
$tables_relations['rubriques']['id_document']='documents_rubriques';

$tables_relations['syndication']['id_mot']='mots_syndic';

?>
