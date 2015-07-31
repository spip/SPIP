<?php

//
// Ce fichier definit les boucles standard de SPIP
//

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_BOUCLES")) return;
define("_INC_BOUCLES", "1");


//
// Boucle sur une table hors SPIP
//
function boucle_DEFAUT($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$boucle->from[] =  $boucle->type_requete . " AS " . $boucle->type_requete;
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(BOUCLE)> boucle dite recursive
//
function boucle_BOUCLE_dist($id_boucle, &$boucles) {

	    return "\n	\$t0 = " . $boucles[$id_boucle]->return . ";";
}

//
// <BOUCLE(ARTICLES)>
//
function boucle_ARTICLES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_articles AS $id_table";
	if (!$GLOBALS['var_preview']) {
		$boucle->where[] = "$id_table.statut='publie'";
		if (lire_meta("post_dates") == 'non')
			$boucle->where[] = "$id_table.date < NOW()";
	} else
		$boucle->where[] = "$id_table.statut IN ('publie','prop')";
	return calculer_boucle($id_boucle, $boucles); 
}

//
// <BOUCLE(AUTEURS)>
//
function boucle_AUTEURS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_auteurs AS $id_table";
	// Si pas de lien avec un article, selectionner
	// uniquement les auteurs d'un article publie
	if (!$GLOBALS['var_preview'])
	if (!$boucle->lien AND !$boucle->tout) {
		$boucle->from[] =  "spip_auteurs_articles AS lien";
		$boucle->from[] =  "spip_articles AS articles";
		$boucle->where[] = "lien.id_auteur=$id_table.id_auteur";
		$boucle->where[] = 'lien.id_article=articles.id_article';
		$boucle->where[] = "articles.statut='publie'";
		$boucle->group =  $boucle->id_table . '.' . $boucle->primary;
	}
	// pas d'auteurs poubellises
	$boucle->where[] = "NOT($id_table.statut='5poubelle')";
	return calculer_boucle($id_boucle, $boucles); 
}

//
// <BOUCLE(BREVES)>
//
function boucle_BREVES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_breves AS $id_table";
	if (!$GLOBALS['var_preview'])
		$boucle->where[] = "$id_table.statut='publie'";
	else
		$boucle->where[] = "$id_table.statut IN ('publie','prop')";
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(FORUMS)>
//
function boucle_FORUMS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_forum AS $id_table";
	// Par defaut, selectionner uniquement les forums sans pere
	if (!$boucle->tout AND !$boucle->plat) 
		{
	$boucle->where[] = "$id_table.id_parent=0";
		}
	$boucle->where[] = "$id_table.statut='publie'";
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(SIGNATURES)>
//
function boucle_SIGNATURES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_signatures AS $id_table";
	$boucle->from[] =  "spip_petitions AS petitions";
	$boucle->from[] =  "spip_articles articles";
	$boucle->where[] = "petitions.id_article=articles.id_article";
	$boucle->where[] = "petitions.id_article=$id_table.id_article";
	$boucle->where[] = "$id_table.statut='publie'";
	$boucle->group =  $boucle->id_table . '.' . $boucle->primary;
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(DOCUMENTS)>
//
function boucle_DOCUMENTS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_documents AS $id_table";
	$boucle->from[] =  "spip_types_documents AS types_documents";
	$boucle->where[] = "$id_table.id_type=types_documents.id_type";
	$boucle->where[] = "$id_table.taille > 0";
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(TYPES_DOCUMENTS)>
//
function boucle_TYPES_DOCUMENTS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_types_documents AS $id_table";
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(GROUPES_MOTS)>
//
function boucle_GROUPES_MOTS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_groupes_mots AS $id_table";
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(MOTS)>
//
function boucle_MOTS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_mots AS $id_table";
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(RUBRIQUES)>
//
function boucle_RUBRIQUES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_rubriques AS $id_table";
	if (!$GLOBALS['var_preview'])
		if (!$boucle->tout) $boucle->where[] = "$id_table.statut='publie'";
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(HIERARCHIE)>
//
function boucle_HIERARCHIE_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_rubriques AS $id_table";

	// Si la boucle mere est une boucle RUBRIQUES il faut ignorer la feuille
	// sauf si le critere {tout} est present (cf. inc-html-squel)
	$exclure_feuille = ($boucle->tout ? 'false' : 'true');

	// $hierarchie sera calculee par une fonction de inc-calcul-outils
	$boucle->where[] = 'id_rubrique IN ($hierarchie)';
	$boucle->select[] = 'FIND_IN_SET(id_rubrique, \'$hierarchie\')-1 AS rang';
	$boucle->order = 'rang';
	$boucle->hierarchie = '$hierarchie = calculer_hierarchie('
	. calculer_argument_precedent($boucle->id_boucle, 'id_rubrique', $boucles)
	. ', '
	. $exclure_feuille
	. ');';
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(SYNDICATION)>
//
function boucle_SYNDICATION_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_syndic AS $id_table";
	$boucle->where[] = "$id_table.statut='publie'";
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(SYNDIC_ARTICLES)>
//
function boucle_SYNDIC_ARTICLES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_syndic_articles  AS $id_table";
	$boucle->from[] =  "spip_syndic AS syndic";
	$boucle->where[] = "$id_table.id_syndic=syndic.id_syndic";
	$boucle->where[] = "$id_table.statut='publie'";
	$boucle->where[] = "syndic.statut='publie'";
	$boucle->select[]='syndic.nom_site AS nom_site'; # derogation zarbi
	$boucle->select[]='syndic.url_site AS url_site'; # idem
	return calculer_boucle($id_boucle, $boucles); 
}


?>
