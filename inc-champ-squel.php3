<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CHAMP_SQUEL")) return;
define("_INC_CHAMP_SQUEL", "1");


function init_champs_squel() {
	global $champs_valides;
	global $champs_traitement, $champs_pretraitement, $champs_posttraitement;

	global $rows_articles;
	global $rows_signatures;
	global $rows_syndication;
	global $rows_syndic_articles;
	global $rows_documents;
	global $rows_types_documents;
	global $rows_rubriques;
	global $rows_forums;
	global $rows_breves;
	global $rows_auteurs;
	global $rows_hierarchie;
	global $rows_mots;
	global $rows_groupes_mots;

	global $tables_relations;
	global $tables_code_contexte;
	global $tables_doublons;

	//
	// Construire un tableau des tables de relations
	//

	$tables_relations = '';

	$tables_relations['articles']['id_mot'] = 'spip_mots_articles';
	$tables_relations['articles']['id_auteur'] = 'spip_auteurs_articles';
	$tables_relations['articles']['id_document'] = 'spip_documents_articles';

	$tables_relations['auteurs']['id_article'] = 'spip_auteurs_articles';

	$tables_relations['breves']['id_mot'] = 'spip_mots_breves';
	$tables_relations['breves']['id_document'] = 'spip_documents_breves';

	$tables_relations['documents']['id_article'] = 'spip_documents_articles';
	$tables_relations['documents']['id_rubrique'] = 'spip_documents_rubriques';
	$tables_relations['documents']['id_breve'] = 'spip_documents_breves';

	$tables_relations['forums']['id_mot'] = 'spip_mots_forum';

	$tables_relations['mots']['id_article'] = 'spip_mots_articles';
	$tables_relations['mots']['id_breve'] = 'spip_mots_breves';
	$tables_relations['mots']['id_forum'] = 'spip_mots_forum';
	$tables_relations['mots']['id_rubrique'] = 'spip_mots_rubriques';
	$tables_relations['mots']['id_syndic'] = 'spip_mots_syndic';

	$tables_relations['groupes_mots']['id_groupe'] = 'spip_mots';

	$tables_relations['rubriques']['id_mot'] = 'spip_mots_rubriques';
	$tables_relations['rubriques']['id_document'] = 'spip_documents_rubriques';

	$tables_relations['syndication']['id_mot'] = 'spip_mots_syndic';


	//
	// Construire un tableau associatif des codes de champ utilisables
	//

	$c = array('NOM_SITE_SPIP', 'URL_SITE_SPIP', 'EMAIL_WEBMASTER',
		'CHARSET', 'ID_ARTICLE', 'ID_RUBRIQUE', 'ID_BREVE', 'ID_FORUM',
		'ID_PARENT', 'ID_SECTEUR', 'ID_DOCUMENT', 'ID_TYPE', 'ID_AUTEUR',
		'ID_MOT', 'ID_SYNDIC_ARTICLE', 'ID_SYNDIC', 'ID_SIGNATURE',
		'ID_GROUPE', 'TITRE', 'SURTITRE', 'SOUSTITRE', 'DESCRIPTIF',
		'CHAPO', 'TEXTE', 'PS', 'NOTES', 'INTRODUCTION', 'MESSAGE', 'DATE',
		'DATE_REDAC', 'DATE_MODIF', 'DATE_NOUVEAUTES', 'INCLUS', 'LANG',
		'LANG_LEFT', 'LANG_RIGHT', 'LANG_DIR', 'LESAUTEURS', 'EMAIL',
		'NOM_SITE', 'LIEN_TITRE', 'URL_SITE', 'LIEN_URL', 'NOM', 'BIO',
		'TYPE', 'PGP', 'FORMULAIRE_ECRIRE_AUTEUR', 'FORMULAIRE_FORUM',
		'FORMULAIRE_SITE', 'PARAMETRES_FORUM', 'FORMULAIRE_RECHERCHE',
		'RECHERCHE', 'FORMULAIRE_INSCRIPTION', 'FORMULAIRE_SIGNATURE',
		'LOGO_MOT', 'LOGO_RUBRIQUE', 'LOGO_RUBRIQUE_NORMAL',
		'LOGO_RUBRIQUE_SURVOL', 'LOGO_AUTEUR', 'LOGO_AUTEUR_NORMAL',
		'LOGO_AUTEUR_SURVOL', 'LOGO_SITE', 'LOGO_BREVE',
		'LOGO_BREVE_RUBRIQUE', 'LOGO_DOCUMENT', 'LOGO_ARTICLE',
		'LOGO_ARTICLE_RUBRIQUE', 'LOGO_ARTICLE_NORMAL',
		'LOGO_ARTICLE_SURVOL', 'URL_ARTICLE', 'URL_RUBRIQUE', 'URL_BREVE',
		'URL_FORUM', 'URL_SYNDIC', 'URL_MOT', 'URL_DOCUMENT',
		'EMBED_DOCUMENT', 'IP', 'VISITES', 'POPULARITE',
		'POPULARITE_ABSOLUE', 'POPULARITE_MAX', 'POPULARITE_SITE', 'POINTS',
		'COMPTEUR_BOUCLE', 'TOTAL_BOUCLE', 'PETITION', 'LARGEUR', 'HAUTEUR',
		'TAILLE', 'EXTENSION', 'DEBUT_SURLIGNE', 'FIN_SURLIGNE',
		'TYPE_DOCUMENT', 'EXTENSION_DOCUMENT', 'FORMULAIRE_ADMIN',
		'LOGIN_PRIVE', 'LOGIN_PUBLIC', 'URL_LOGOUT', 'PUCE', 'EXTRA'
	);
	reset($c);
	while (list(, $val) = each($c)) {
		unset($champs_traitement[$val]);
		unset($champs_pretraitement[$val]);
		unset($champs_posttraitement[$val]);
		$champs_valides[$val] = $val;
	}


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

	// Textes courts : ajouter le traitement typographique
	$c = array('NOM_SITE_SPIP', 'SURTITRE', 'TITRE', 'SOUSTITRE', 'NOM_SITE', 'LIEN_TITRE', 'NOM');
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

	//
	// Construire un tableau associatif des champs de chaque type
	// avec l'intitule de la colonne mysql correspondante
	//

	$rows_articles = array(
		'ID_ARTICLE' => 'id_article',
		'ID_RUBRIQUE' => 'id_rubrique',
		'ID_SECTEUR' => 'id_secteur',
		'SURTITRE' => 'surtitre',
		'TITRE' => 'titre',
		'SOUSTITRE' => 'soustitre',
		'DESCRIPTIF' => 'descriptif',
		'CHAPO' => 'chapo',
		'TEXTE' => 'texte',
		'PS' => 'ps',
		'LANG' => 'lang',
		'DATE' => 'date',
		'DATE_REDAC' => 'date_redac',
		'DATE_MODIF' => 'date_modif',
		'VISITES' => 'visites',
		'POINTS' => 'points'
	);
	$rows_auteurs = array(
		'ID_AUTEUR' => 'id_auteur',
		'NOM' => 'nom',
		'BIO' => 'bio',
		'EMAIL' => 'email',
		'NOM_SITE' => 'nom_site',
		'URL_SITE' => 'url_site',
		'PGP' => 'pgp',
		'LANG' => 'lang',
		'POINTS' => 'points'
	);
	$rows_breves = array(
		'ID_BREVE' => 'id_breve',
		'ID_RUBRIQUE' => 'id_rubrique',
		'ID_SECTEUR' => 'id_rubrique',
		'DATE' => 'date_heure',
		'TITRE' => 'titre',
		'TEXTE' => 'texte',
		'LANG' => 'lang',
		'NOM_SITE' => 'lien_titre',
		'URL_SITE' => 'lien_url',
		'LIEN_TITRE' => 'lien_titre',
		'LIEN_URL' => 'lien_url',
		'POINTS' => 'points'
	);
	$rows_forums = array(
		'ID_FORUM' => 'id_forum',
		'ID_PARENT' => 'id_parent',
		'ID_BREVE' => 'id_breve',
		'ID_RUBRIQUE' => 'id_rubrique',
		'ID_ARTICLE' => 'id_article',
		'TITRE' => 'titre',
		'TEXTE' => 'texte',
		'DATE' => 'date_heure',
		'NOM' => 'auteur',
		'EMAIL' => 'email_auteur',
		'NOM_SITE' => 'nom_site',
		'URL_SITE' => 'url_site',
		'IP' => 'ip'
	);
	$rows_documents = array(
		'ID_DOCUMENT' => 'id_document',
		'ID_VIGNETTE' => 'id_vignette',
		'ID_TYPE' => 'id_type',
		'TITRE' => 'titre',
		'DESCRIPTIF' => 'descriptif',
		'DATE' => 'date',
		'LARGEUR' => 'largeur',
		'HAUTEUR' => 'hauteur',
		'TAILLE' => 'taille',
		'TYPE_DOCUMENT' => 'type_document',
		'EXTENSION_DOCUMENT' => 'extension_document'
	);
	$rows_types_documents = array(
		'ID_TYPE' => 'id_type',
		'TITRE' => 'titre',
		'DESCRIPTIF' => 'descriptif',
		'EXTENSION' => 'extension'
	);
	$rows_mots = array(
		'ID_MOT' => 'id_mot',
		'TYPE' => 'type',
		'TITRE' => 'titre',
		'DESCRIPTIF' => 'descriptif',
		'TEXTE' => 'texte',
		'POINTS' => 'points',
		'ID_GROUPE' => 'id_groupe'
	);
	$rows_groupes_mots = array(
		'ID_GROUPE' => 'id_groupe',
		'TITRE' => 'titre'
	);
	$rows_rubriques = array(
		'ID_RUBRIQUE' => 'id_rubrique',
		'ID_PARENT' => 'id_parent',
		'ID_SECTEUR' => 'id_secteur',
		'TITRE' => 'titre',
		'DESCRIPTIF' => 'descriptif',
		'TEXTE' => 'texte',
		'LANG' => 'lang',
		'DATE' => 'date',
		'POINTS' => 'points'
	);
	$rows_hierarchie = $rows_rubriques;

	$rows_signatures = array(
		'ID_SIGNATURE' => 'id_signature',
		'ID_ARTICLE' => 'id_article',
		'DATE' => 'date_time',
		'NOM' => 'nom_email',
		'EMAIL' => 'ad_email',
		'NOM_SITE' => 'nom_site',
		'URL_SITE' => 'url_site',
		'MESSAGE' => 'message'
	);

	$rows_syndication = array(
		'ID_SYNDIC' => 'id_syndic',
		'ID_RUBRIQUE' => 'id_rubrique',
		'ID_SECTEUR' => 'id_secteur',
		'NOM_SITE' => 'nom_site',
		'URL_SITE' => 'url_site',
		'URL_SYNDIC' => 'url_syndic',
		'DESCRIPTIF' => 'descriptif',
		'DATE' => 'date',
		'POINTS' => 'points'
	);
	$rows_syndic_articles = array(
		'ID_SYNDIC_ARTICLE' => 'id_syndic_article',
		'ID_SYNDIC' => 'id_syndic',
		'TITRE' => 'titre',
		'URL_ARTICLE' => 'url',
		'DATE' => 'date',
		'LESAUTEURS' => 'lesauteurs',
		'DESCRIPTIF' => 'descriptif',
		'NOM_SITE' => 'nom_site',
		'URL_SITE' => 'url_site'
	);

	$tables_code_contexte = array(
	'articles' => '
		$contexte["id_article"] = $row["id_article"];
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_secteur"] = $row["id_secteur"];
		$contexte["date"] = $row["date"];
		$contexte["date_redac"] = $row["date_redac"];
		$contexte["accepter_forum"] = $row["accepter_forum"];
		$contexte["id_trad"] = $row["id_trad"];
	',
	'breves' => '
		$contexte["id_breve"] = $row["id_breve"];
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_secteur"] = $row["id_rubrique"];
		$contexte["date"] = $row["date_heure"];
	',
	'rubriques' => '
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_parent"] = $row["id_parent"];
		$contexte["id_secteur"] = $row["id_secteur"];
		$contexte["date"] = $row["date"];
	',
	'hierarchie' => '
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_parent"] = $row["id_parent"];
		$contexte["id_secteur"] = $row["id_secteur"];
		$contexte["date"] = $row["date"];
	',
	'documents' => '
		$contexte["id_document"] = $row["id_document"];
		$contexte["id_vignette"] = $row["id_vignette"];
		$contexte["id_type"] = $row["id_type"];
	',
	'types_documents' => '
		$contexte["id_type"] = $row["id_type"];
	',
	'forums' => '
		$contexte["id_forum"] = $row["id_forum"];
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_article"] = $row["id_article"];
		$contexte["id_breve"] = $row["id_breve"];
		$contexte["id_parent"] = $row["id_parent"];
		$contexte["date"] = $row["date_heure"];
	',
	'auteurs' => '
		$contexte["id_auteur"] = $row["id_auteur"];
	',
	'signatures' => '
		$contexte["id_signature"] = $row["id_signature"];
		$contexte["date"] = $row["date_time"];
	',
	'mots' => '
		$contexte["id_mot"] = $row["id_mot"];
		$contexte["type"] = $row["type"];
		$contexte["id_groupe"] = $row["id_groupe"];
	',
	'groupes_mots' => '
		$contexte["id_groupe"] = $row["id_groupe"];
	',
	'syndication' => '
		$contexte["id_syndic"] = $row["id_syndic"];
		$contexte["id_rubrique"] = $row["id_rubrique"];
		$contexte["id_secteur"] = $row["id_secteur"];
		$contexte["url_site"] = $row["url_site"];
		$contexte["date"] = $row["date"];
	',
	'syndic_articles' => '
		$contexte["id_syndic"] = $row["id_syndic"];
		$contexte["id_syndic_article"] = $row["id_syndic_article"];
		$contexte["date"] = $row["date"];
	'
	);

	$tables_doublons = array(
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
		'syndic_articles' => "syndic_articles",
		'types_documents' => "id_document"
	);

}

init_champs_squel();

?>
