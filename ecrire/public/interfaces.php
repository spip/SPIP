<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;


// Definition des noeuds de l'arbre de syntaxe abstraite

// http://doc.spip.org/@Texte
class Texte {
	var $type = 'texte';
	var $texte;
	var $avant, $apres = ""; // s'il y avait des guillemets autour
	var $ligne = 0; 
}

// http://doc.spip.org/@Inclure
class Inclure {
	var $type = 'include';
	var $texte;
	var $avant, $apres; // inutilises mais generiques
	var $ligne = 0; 
	var $param = array();  //  valeurs des params
}

//
// encodage d'une boucle SPIP en un objet PHP
//
// http://doc.spip.org/@Boucle
class Boucle {
	var $type = 'boucle';
	var $id_boucle;
	var $id_parent ='';
	var $avant, $milieu, $apres, $altern;
	var $lang_select;
	var $type_requete;
	var $sql_serveur = '';
	var $param = array();
	var $criteres = array();
	var $separateur = array();
	var $jointures = array();
	var $jointures_explicites = false;
	var $doublons;
	var $partie, $total_parties,$mode_partie;
	var $externe = ''; # appel a partir d'une autre boucle (recursion)
	// champs pour la construction de la requete SQL
	var $select = array();
	var $from = array();
	var $where = array();
	var $join = array();
	var $having = array();
	var $limit;
	var $group = array();
	var $order = array();
	var $default_order = array();
	var $date = 'date' ;
	var $hash = "" ;
	var $in = "" ;
	var $sous_requete = false;
	var $hierarchie = '';
	var $statut = false; # definition/surcharge du statut des elements retournes
	// champs pour la construction du corps PHP
	var $id_table;
	var $primary;
	var $return;
	var $numrows = false;
	var $cptrows = false;
	var $ligne = 0;
	var $descr =  array(); # noms des fichiers source et but etc
	
	var $modificateur = array(); // table pour stocker les modificateurs de boucle tels que tout, plat, fragment ..., utilisable par les plugins egalement
	
	// obsoletes, conserves provisoirement pour compatibilite
	var $fragment; # definir un fragment de page
	var $tout = false;
	var $plat = false;
	var $lien = false;
}

// sous-noeud du precedent

// http://doc.spip.org/@Critere
class Critere {
	var $op;
	var $not;	
	var $param = array();
	var $ligne = 0; 
}

// http://doc.spip.org/@Champ
class Champ {
	var $type = 'champ';
	var $nom_champ;
	var $nom_boucle= ''; // seulement si boucle explicite
	var $avant, $apres; // tableaux d'objets
	var $etoile;
	var $param = array();  // filtre explicites
	var $fonctions = array();  // source des filtres (compatibilite)
	// champs pour la production de code
	var $id_boucle;
	var $boucles;
	var $type_requete;
	var $code;	// code du calcul
	var $interdire_scripts = true; // false si on est sur de cette balise
	// tableau pour la production de code dependant du contexte
	// id_mere;  pour TOTAL_BOUCLE hors du corps
	// document; pour embed et img dans les textes
	// sourcefile; pour DOSSIER_SQUELETTE
	var $descr = array();
	// pour localiser les erreurs
	var $ligne = 0; 
}


// http://doc.spip.org/@Idiome
class Idiome {
	var $type = 'idiome';
	var $nom_champ = ""; // la chaine a traduire
	var $module = ""; // son module de definition
	var $param = array(); // les filtres a appliquer au resultat
	var $fonctions = array(); // source des filtres  (compatibilite)
	var $avant, $apres; // inutilises mais faut = ci-dessus
	// champs pour la production de code, cf ci-dessus
	var $id_boucle;
	var $boucles;
	var $type_requete;
	var $code;
	var $interdire_scripts = false;
	var $descr = array();
	var $ligne = 0; 
}

// http://doc.spip.org/@Polyglotte
class Polyglotte {
	var $type = 'polyglotte';
	var $traductions = array(); // les textes ou choisir
	var $ligne = 0; 
}
//
// Globales de description de la base

//ces variables ne sont pas initialisees par "$var = array()"
// afin de permettre leur extension dans mes_options.php etc

global $tables_des_serveurs_sql, $tables_principales; // (voir inc_serialbase)
global $exceptions_des_tables, $table_des_tables;
global $table_date;

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
$table_des_tables['index']='index';

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

$exceptions_des_tables['documents']['type_document']=array('spip_types_documents'
, 'titre');
$exceptions_des_tables['documents']['extension_document']=array('spip_types_documents', 'extension');
$exceptions_des_tables['documents']['mime_type']=array('spip_types_documents'
, 'mime_type');

# ne sert plus ? verifier balise_URL_ARTICLE
$exceptions_des_tables['syndic_articles']['url_article']='url';
# ne sert plus ? verifier balise_LESAUTEURS
$exceptions_des_tables['syndic_articles']['lesauteurs']='lesauteurs'; 
$exceptions_des_tables['syndic_articles']['url_site']=array('spip_syndic',
'url_site');
$exceptions_des_tables['syndic_articles']['nom_site']=array('spip_syndic',
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
// tableau des tables de jointures
// Ex: gestion du critere {id_mot} dans la boucle(ARTICLES)

global $tables_jointures;

$tables_jointures['spip_articles'][]= 'mots_articles';
$tables_jointures['spip_articles'][]= 'auteurs_articles';
$tables_jointures['spip_articles'][]= 'documents_articles';
$tables_jointures['spip_articles'][]= 'mots';
$tables_jointures['spip_articles'][]= 'signatures';

$tables_jointures['spip_auteurs'][]= 'auteurs_articles';
$tables_jointures['spip_auteurs'][]= 'mots';

$tables_jointures['spip_breves'][]= 'mots_breves';
$tables_jointures['spip_breves'][]= 'documents_breves';
$tables_jointures['spip_breves'][]= 'mots';

$tables_jointures['spip_documents'][]= 'documents_articles';
$tables_jointures['spip_documents'][]= 'documents_rubriques';
$tables_jointures['spip_documents'][]= 'documents_breves';
$tables_jointures['spip_documents'][]= 'mots_documents';
$tables_jointures['spip_documents'][]= 'types_documents';
$tables_jointures['spip_documents'][]= 'mots';

$tables_jointures['spip_forum'][]= 'mots_forum';
$tables_jointures['spip_forum'][]= 'mots';

$tables_jointures['spip_rubriques'][]= 'mots_rubriques';
$tables_jointures['spip_rubriques'][]= 'documents_rubriques';
$tables_jointures['spip_rubriques'][]= 'mots';

$tables_jointures['spip_syndic'][]= 'mots_syndic';
$tables_jointures['spip_syndic'][]= 'mots';

$tables_jointures['spip_syndic_articles'][]= 'syndic';
$tables_jointures['spip_syndic_articles'][]= 'mots';

$tables_jointures['spip_mots'][]= 'mots_articles';
$tables_jointures['spip_mots'][]= 'mots_breves';
$tables_jointures['spip_mots'][]= 'mots_forum';
$tables_jointures['spip_mots'][]= 'mots_rubriques';
$tables_jointures['spip_mots'][]= 'mots_syndic';
$tables_jointures['spip_mots'][]= 'mots_documents';

$tables_jointures['spip_groupes_mots'][]= 'mots';


global  $exceptions_des_jointures;
$exceptions_des_jointures['titre_mot'] = array('spip_mots', 'titre');
$exceptions_des_jointures['type_mot'] = array('spip_mots', 'type');
$exceptions_des_jointures['id_signature']= array('spip_signatures', 'id_signature');

global  $table_des_traitements;
$table_des_traitements['BIO'][]= 'propre(%s)';
$table_des_traitements['CHAPO'][]= 'propre(nettoyer_chapo(%s))';
$table_des_traitements['DATE'][]= 'vider_date(%s)';
$table_des_traitements['DATE_MODIF'][]= 'vider_date(%s)';
$table_des_traitements['DATE_NOUVEAUTES'][]= 'vider_date(%s)';
$table_des_traitements['DATE_REDAC'][]= 'vider_date(%s)';
$table_des_traitements['DESCRIPTIF'][]= 'propre(%s)';
$table_des_traitements['FICHIER']['documents']= 'get_spip_doc(%s)';
$table_des_traitements['LIEN_TITRE'][]= 'typo(%s)';
$table_des_traitements['LIEN_URL'][]= 'vider_url(%s)';
$table_des_traitements['MESSAGE'][]= 'propre(%s)';
$table_des_traitements['NOM_SITE_SPIP'][]= 'typo(%s)';
$table_des_traitements['NOM_SITE'][]= '%s'; # construire_titre_lien -> typo
$table_des_traitements['NOM'][]= 'typo(%s)';
$table_des_traitements['PARAMETRES_FORUM'][]= 'htmlspecialchars(lang_parametres_forum(%s))';
$table_des_traitements['PS'][]= 'propre(%s)';
$table_des_traitements['SOURCE'][]= 'typo(%s)';
$table_des_traitements['SOUSTITRE'][]= 'typo(%s)';
$table_des_traitements['SURTITRE'][]= 'typo(%s)';
$table_des_traitements['TAGS'][]= '%s';
$table_des_traitements['TEXTE'][]= 'propre(%s)';
$table_des_traitements['TITRE'][]= 'typo(%s)';
$table_des_traitements['TYPE'][]= 'typo(%s)';
$table_des_traitements['URL_ARTICLE'][]= 'vider_url(%s)';
$table_des_traitements['URL_BREVE'][]= 'vider_url(%s)';
$table_des_traitements['URL_DOCUMENT'][]= 'vider_url(%s)';
$table_des_traitements['URL_FORUM'][]= 'vider_url(%s)';
$table_des_traitements['URL_MOT'][]= 'vider_url(%s)';
$table_des_traitements['URL_RUBRIQUE'][]= 'vider_url(%s)';
$table_des_traitements['DESCRIPTIF_SITE_SPIP'][]= 'propre(%s)';
$table_des_traitements['URL_SITE'][]= 'calculer_url(%s)';
$table_des_traitements['URL_SOURCE'][]= 'vider_url(%s)';
$table_des_traitements['URL_SYNDIC'][]= 'vider_url(%s)';
$table_des_traitements['ENV'][]= 'entites_html(%s,true)';


// Articles syndiques : passage des donnees telles quelles, sans traitement typo
// A noter, dans applique_filtres la securite et compliance XHTML de ces champs
// est assuree par safehtml()
foreach(array('TITRE','DESCRIPTIF','SOURCE') as $balise)
	if (!isset($table_des_traitements[$balise]['syndic_articles']))
		$table_des_traitements[$balise]['syndic_articles'] = '%s';

?>
