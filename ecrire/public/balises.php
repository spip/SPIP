<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier regroupe la quasi totalite des definitions de #BALISES de spip
// Pour chaque balise, il est possible de surcharger, dans mes_fonctions,
// la fonction balise_TOTO_dist par une fonction balise_TOTO() respectant la
// meme API : 
// elle recoit en entree un objet de classe CHAMP, le modifie et le retourne.
// Cette classe est definie dans inc-compilo-index
//

if (!defined("_ECRIRE_INC_VERSION")) return;


//
// Definition des balises
//
// http://doc.spip.org/@balise_NOM_SITE_SPIP_dist
function balise_NOM_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['nom_site']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_EMAIL_WEBMASTER_dist
function balise_EMAIL_WEBMASTER_dist($p) {
	$p->code = "\$GLOBALS['meta']['email_webmaster']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_DESCRIPTIF_SITE_SPIP_dist
function balise_DESCRIPTIF_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['descriptif_site']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_CHARSET_dist
function balise_CHARSET_dist($p) {
	$p->code = "\$GLOBALS['meta']['charset']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_LANG_LEFT_dist
function balise_LANG_LEFT_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir(($_lang ? $_lang : \$GLOBALS['spip_lang']),'left','right')";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_LANG_RIGHT_dist
function balise_LANG_RIGHT_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir(($_lang ? $_lang : \$GLOBALS['spip_lang']),'right','left')";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_LANG_DIR_dist
function balise_LANG_DIR_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir(($_lang ? $_lang : \$GLOBALS['spip_lang']),'ltr','rtl')";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_PUCE_dist
function balise_PUCE_dist($p) {
	$p->code = "definir_puce()";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE
// Cette fonction sait aller chercher dans le contexte general
// quand #DATE est en dehors des boucles
// http://www.spip.net/fr_article1971.html
// http://doc.spip.org/@balise_DATE_dist
function balise_DATE_dist ($p) {
	$_date = champ_sql('date', $p);
	$p->code = "$_date";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE_REDAC
// http://www.spip.net/fr_article1971.html
// http://doc.spip.org/@balise_DATE_REDAC_dist
function balise_DATE_REDAC_dist ($p) {
	$_date = champ_sql('date_redac', $p);
	$p->code = "$_date";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE_MODIF
// http://www.spip.net/fr_article1971.html
// http://doc.spip.org/@balise_DATE_MODIF_dist
function balise_DATE_MODIF_dist ($p) {
	$_date = champ_sql('date_modif', $p);
	$p->code = "$_date";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE_NOUVEAUTES
// http://www.spip.net/fr_article1971.html
// http://doc.spip.org/@balise_DATE_NOUVEAUTES_dist
function balise_DATE_NOUVEAUTES_dist($p) {
	$p->code = "((\$GLOBALS['meta']['quoi_de_neuf'] == 'oui'
	AND @file_exists(_DIR_TMP . 'mail.lock')) ?
	normaliser_date(@filemtime(_DIR_TMP . 'mail.lock')) :
	\"'0000-00-00'\")";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_DOSSIER_SQUELETTE_dist
function balise_DOSSIER_SQUELETTE_dist($p) {
	$code = addslashes(dirname($p->descr['sourcefile']));
	$p->code = "'$code'" . 
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_SITE_SPIP_dist
function balise_URL_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['adresse_site']";
	#$p->interdire_scripts = true;
	return $p;
}


// http://doc.spip.org/@balise_URL_ARTICLE_dist
function balise_URL_ARTICLE_dist($p) {
	$_type = $p->type_requete;

	// Cas particulier des boucles (SYNDIC_ARTICLES)
	if ($_type == 'syndic_articles') {
		$p->code = champ_sql('url', $p);
	}

	// Cas general : chercher un id_article dans la pile
	else {
		$_id_article = '';
		if ($p->param && !$p->param[0][0]){
			$_id_article = calculer_liste($p->param[0][1],
								$p->descr,
								$p->boucles,
								$p->id_boucle);
		}
		if (!$_id_article)
			$_id_article = champ_sql('id_article', $p);
		$p->code = "generer_url_article($_id_article)";

		if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
			$p->code = "url_var_recherche(" . $p->code . ")";
	}

	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_RUBRIQUE_dist
function balise_URL_RUBRIQUE_dist($p) {
	$_id_rubrique = '';
	if ($p->param && !$p->param[0][0]){
		$_id_rubrique =  calculer_liste($p->param[0][1],
							$p->descr,
							$p->boucles,
							$p->id_boucle);
	}
	if (!$_id_rubrique)
		$_id_rubrique = champ_sql('id_rubrique',$p);
	$p->code = "generer_url_rubrique($_id_rubrique)" ;

	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_BREVE_dist
function balise_URL_BREVE_dist($p) {
	$_id_breve = '';
	if ($p->param && !$p->param[0][0]){
		$_id_breve =  calculer_liste($p->param[0][1],
							$p->descr,
							$p->boucles,
							$p->id_boucle);
	}
	if (!$_id_breve)
		$_id_breve = champ_sql('id_breve',$p);
	$p->code = "generer_url_breve($_id_breve)";
	
	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_MOT_dist
function balise_URL_MOT_dist($p) {
	$_id_mot = '';
	if ($p->param && !$p->param[0][0]){
		$_id_mot =  calculer_liste($p->param[0][1],
							$p->descr,
							$p->boucles,
							$p->id_boucle);
	}
	if (!$_id_mot)
		$_id_mot = champ_sql('id_mot',$p);
	$p->code = "generer_url_mot($_id_mot)";

	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->interdire_scripts = false;
	return $p;
}

// #NOM_SITE affiche le nom du site, ou sinon l'URL ou le titre de l'objet
// http://doc.spip.org/@balise_NOM_SITE_dist
function balise_NOM_SITE_dist($p) {
	if (!$p->etoile) {
		$p->code = "construire_titre_lien(" .
		champ_sql('nom_site',$p) ."," .
		champ_sql('url_site',$p) . 
		")";
	} else
		$p->code = champ_sql('nom_site',$p);

	$p->interdire_scripts = true;
	return $p;
}

# URL_SITE est une donnee "brute" tiree de la base de donnees
# URL_SYNDIC correspond a l'adresse de son backend.
# Il n'existe pas de balise pour afficher generer_url_site($id_syndic),
# a part [(#ID_SYNDIC|generer_url_site)]

// http://doc.spip.org/@balise_URL_FORUM_dist
function balise_URL_FORUM_dist($p, $show_thread = 'false') {
	$_id_forum = '';
	if ($p->param && !$p->param[0][0]){
		$_id_forum =  calculer_liste($p->param[0][1],
							$p->descr,
							$p->boucles,
							$p->id_boucle);
	}
	if (!$_id_forum)
		$_id_forum = champ_sql('id_forum',$p);
	$p->code = "generer_url_forum($_id_forum, $show_thread)";

	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_DOCUMENT_dist
function balise_URL_DOCUMENT_dist($p) {
	$_id_document = '';
	if ($p->param && !$p->param[0][0]){
		$_id_document =  calculer_liste($p->param[0][1],
							$p->descr,
							$p->boucles,
							$p->id_boucle);
	}
	if (!$_id_document)
		$_id_document = champ_sql('id_document',$p);
	$p->code = "generer_url_document($_id_document)";

	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_URL_AUTEUR_dist
function balise_URL_AUTEUR_dist($p) {
	$_id_auteur = '';
	if ($p->param && !$p->param[0][0]){
		$_id_auteur =  calculer_liste($p->param[0][1],
							$p->descr,
							$p->boucles,
							$p->id_boucle);
	}
	if (!$_id_auteur)
		$_id_auteur = champ_sql('id_auteur',$p);
	$p->code = "generer_url_auteur($_id_auteur)";

	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_NOTES_dist
function balise_NOTES_dist($p) {
	// Recuperer les notes
	$p->code = 'calculer_notes()';
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_RECHERCHE_dist
function balise_RECHERCHE_dist($p) {
	$p->code = 'entites_html(_request("recherche"))';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_COMPTEUR_BOUCLE_dist
function balise_COMPTEUR_BOUCLE_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];
	if ($b === '') {
		erreur_squelette(
			_T('zbug_champ_hors_boucle',
				array('champ' => '#COMPTEUR_BOUCLE')
			), $p->id_boucle);
		$p->code = "''";
	} else {
		$p->code = "\$Numrows['$b']['compteur_boucle']";
		$p->boucles[$b]->cptrows = true;
		$p->interdire_scripts = false;
		return $p;
	}
}

// http://doc.spip.org/@balise_TOTAL_BOUCLE_dist
function balise_TOTAL_BOUCLE_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];
	if ($b === '' || !isset($p->boucles[$b])) {
		erreur_squelette(
			_T('zbug_champ_hors_boucle',
				array('champ' => "#$b" . 'TOTAL_BOUCLE')
			), $p->id_boucle);
		$p->code = "''";
	} else {
		$p->code = "\$Numrows['$b']['total']";
		$p->boucles[$b]->numrows = true;
		$p->interdire_scripts = false;
	}
	return $p;
}

// Si on est hors d'une boucle {recherche}, ne pas "prendre" cette balise
// http://doc.spip.org/@balise_POINTS_dist
function balise_POINTS_dist($p) {
	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
		return rindex_pile($p, 'points', 'recherche');
	else
		return NULL;
}

// http://doc.spip.org/@balise_POPULARITE_ABSOLUE_dist
function balise_POPULARITE_ABSOLUE_dist($p) {
	$p->code = 'ceil(' .
	champ_sql('popularite', $p) .
	')';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_POPULARITE_SITE_dist
function balise_POPULARITE_SITE_dist($p) {
	$p->code = 'ceil($GLOBALS["meta"][\'popularite_total\'])';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_POPULARITE_MAX_dist
function balise_POPULARITE_MAX_dist($p) {
	$p->code = 'ceil($GLOBALS["meta"][\'popularite_max\'])';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_EXPOSE_dist
function balise_EXPOSE_dist($p) {
	$on = "'on'";
	$off= "''";

	if ($p->param && !$p->param[0][0]) {
		$on =  calculer_liste($p->param[0][1],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		$off =  calculer_liste($p->param[0][2],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		// autres filtres
		array_shift($p->param);
	}
	return calculer_balise_expose($p, $on, $off);
}

// obsolete. utiliser la precedente

// http://doc.spip.org/@balise_EXPOSER_dist
function balise_EXPOSER_dist($p)
{
	$on = "'on'";
	$off= "''";
	if ($a = ($p->fonctions)) {
		// Gerer la notation [(#EXPOSER|on,off)]
		$onoff = array_shift($a);
		ereg("([^,]*)(,(.*))?", $onoff[0], $regs);
		$on = "" . spip_abstract_quote($regs[1]);
		$off = "" . spip_abstract_quote($regs[3]) ;
		// autres filtres
		array_shift($p->param);
	}
	return calculer_balise_expose($p, $on, $off);
}

// http://doc.spip.org/@calculer_balise_expose
function calculer_balise_expose($p, $on, $off)
{
	$primary_key = $p->boucles[$p->id_boucle]->primary;
	if (!$primary_key) {
		erreur_squelette(_T('zbug_champ_hors_boucle',
				array('champ' => '#EXPOSER')
			), $p->id_boucle);

	}

	$p->code = '(calcul_exposer('
	.champ_sql($primary_key, $p)
	.", '$primary_key', \$Pile[0]) ? $on : $off)";
	$p->interdire_scripts = false;
	return $p;
}

//
// Inserer directement un document dans le squelette
//
// http://doc.spip.org/@balise_EMBED_DOCUMENT_dist
function balise_EMBED_DOCUMENT_dist($p) {
	balise_distante_interdite($p);
	$_id_document = champ_sql('id_document',$p);
	$p->code = "calcule_embed_document(intval($_id_document), " .
	  argumenter_balise($p->fonctions, "|") .
	  ", \$doublons, '" . $p->descr['documents'] . "')";
	$p->param = array();
	#$p->interdire_scripts = true;
	return $p;
}

// Debut et fin de surlignage auto des mots de la recherche
// on insere une balise Span avec une classe sans spec:
// c'est transparent s'il n'y a pas de recherche,
// sinon elles seront remplacees par les fontions de inc_surligne

// http://doc.spip.org/@balise_DEBUT_SURLIGNE_dist
function balise_DEBUT_SURLIGNE_dist($p) {
	include_spip('inc/surligne');
	$p->code = "'<" . MARQUEUR_SURLIGNE . "'";
	return $p;
}
// http://doc.spip.org/@balise_FIN_SURLIGNE_dist
function balise_FIN_SURLIGNE_dist($p) {
	include_spip('inc/surligne');
	$p->code = "'<" . MARQUEUR_FSURLIGNE . "'";
	return $p;
}


// #SPIP_CRON
// a documenter
// insere un <div> avec un lien background-image vers les taches de fond.
// Si cette balise est presente sur la page de sommaire, le site ne devrait
// quasiment jamais se trouver ralenti par des taches de fond un peu lentes
// ATTENTION: cette balise efface parfois les boutons admin implicites
// http://doc.spip.org/@balise_SPIP_CRON_dist
function balise_SPIP_CRON_dist ($p) {
	$p->code = '"' . str_replace('"', '\"', (generer_spip_cron())) . '"';
	$p->interdire_scripts = false;
	return $p;
}


// #INTRODUCTION
// http://www.spip.net/@introduction
// http://doc.spip.org/@balise_INTRODUCTION_dist
function balise_INTRODUCTION_dist ($p) {
	$_type = $p->type_requete;
	$_texte = champ_sql('texte', $p);
	$_chapo = champ_sql('chapo', $p);
	$_descriptif = champ_sql('descriptif', $p);
	$p->code = "calcul_introduction('$_type', $_texte, $_chapo, $_descriptif)";

	#$p->interdire_scripts = true;
	return $p;
}


// #LANG
// affiche la langue de l'objet (ou superieure), et a defaut la langue courante
// (celle du site ou celle qui a ete passee dans l'URL par le visiteur)
// #LANG* n'affiche rien si aucune langue n'est trouvee dans le sql/le contexte
// http://doc.spip.org/@balise_LANG_dist
function balise_LANG_dist ($p) {
	$_lang = champ_sql('lang', $p);
	if (!$p->etoile)
		$p->code = "htmlentities($_lang ? $_lang : \$GLOBALS['spip_lang'])";
	else
		$p->code = "htmlentities($_lang)";
	$p->interdire_scripts = false;
	return $p;
}

// #RANG
// affiche le "numero de l'article" quand on l'a titre '1. Premier article';
// ceci est transitoire afin de preparer une migration vers un vrai systeme de
// tri des articles dans une rubrique (et plus si affinites)
// http://doc.spip.org/@balise_RANG_dist
function balise_RANG_dist ($p) {
	$_titre = champ_sql('titre', $p);
	$p->code = "recuperer_numero($_titre)";
	$p->interdire_scripts = false;
	return $p;
}


// #PETITION 
// retourne '' si l'article courant n'a pas de petition 
// le texte de celle-ci sinon (et ' ' si il est vide)
// cf FORMULAIRE_PETITION

// http://doc.spip.org/@balise_PETITION_dist
function balise_PETITION_dist ($p) {
	$nom = $p->id_boucle;
	$p->code = "sql_petitions(" .
			champ_sql('id_article', $p) .
			",'" .
			$p->boucles[$nom]->type_requete .
			"','" .
			$nom .
			"','" .
			$p->boucles[$nom]->sql_serveur .
			"', \$Cache)";
	$p->interdire_scripts = false;
	return $p;
}


// #POPULARITE
// http://www.spip.net/fr_article1846.html
// http://doc.spip.org/@balise_POPULARITE_dist
function balise_POPULARITE_dist ($p) {
	$_popularite = champ_sql('popularite', $p);
	$p->code = "(ceil(min(100, 100 * $_popularite
	/ max(1 , 0 + \$GLOBALS['meta']['popularite_max']))))";
	$p->interdire_scripts = false;
	return $p;
}

// #PAGINATION
// http://www.spip.net/fr_articleXXXX.html
// http://doc.spip.org/@balise_PAGINATION_dist
function balise_PAGINATION_dist($p, $liste='true') {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];

	// s'il n'y a pas de nom de boucle, on ne peut pas paginer
	if ($b === '') {
		erreur_squelette(
			_T('zbug_champ_hors_boucle',
				array('champ' => '#PAGINATION')
			), $p->id_boucle);
		$p->code = "''";
		return $p;
	}

	// s'il n'y a pas de total_parties, c'est qu'on se trouve
	// dans un boucle recurive ou qu'on a oublie le critere {pagination}
	if (!$p->boucles[$b]->total_parties) {
		erreur_squelette(
			_L('zbug_xx: #PAGINATION sans critere {pagination}
				ou employe dans une boucle recursive',
				array('champ' => '#PAGINATION')
			), $p->id_boucle);
		$p->code = "''";
		return $p;
	}
	$__modele = "";
	if ($p->param && !$p->param[0][0]) {
		$__modele = ",". calculer_liste($p->param[0][1],
					$p->descr,
					$p->boucles,
					$p->id_boucle);
	}
	

	$p->boucles[$b]->numrows = true;

	$p->code = "calcul_pagination(
	(isset(\$Numrows['$b']['grand_total']) ?
		\$Numrows['$b']['grand_total'] : \$Numrows['$b']['total']
	), '$b', "
	. $p->boucles[$b]->total_parties
	. ", $liste $__modele)";

	$p->interdire_scripts = false;
	return $p;
}

// N'afficher que l'ancre de la pagination (au-dessus, par exemple, alors
// qu'on mettra les liens en-dessous de la liste paginee)
// http://doc.spip.org/@balise_ANCRE_PAGINATION_dist
function balise_ANCRE_PAGINATION_dist($p) {
	$p = balise_PAGINATION_dist($p, $liste='false');
	return $p;
}

// equivalent a #TOTAL_BOUCLE sauf pour les boucles paginees, ou elle
// indique le nombre total d'articles repondant aux criteres hors pagination
// http://doc.spip.org/@balise_GRAND_TOTAL_dist
function balise_GRAND_TOTAL_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];
	if ($b === '' || !isset($p->boucles[$b])) {
		erreur_squelette(
			_T('zbug_champ_hors_boucle',
				array('champ' => "#$b" . 'TOTAL_BOUCLE')
			), $p->id_boucle);
		$p->code = "''";
	} else {
		$p->code = "(isset(\$Numrows['$b']['grand_total'])
			? \$Numrows['$b']['grand_total'] : \$Numrows['$b']['total'])";
		$p->boucles[$b]->numrows = true;
		$p->interdire_scripts = false;
	}
	return $p;
}



//
// Fonction commune aux balises #LOGO_XXXX
// (les balises portant ce type de nom sont traitees en bloc ici)
//
// http://doc.spip.org/@calculer_balise_logo_dist
function calculer_balise_logo_dist ($p) {

	eregi("^LOGO_([A-Z]+)(_.*)?$", $p->nom_champ, $regs);
	$type_objet = $regs[1];
	$suite_logo = $regs[2];	

	// cas de #LOGO_SITE_SPIP
	if (ereg("^_SPIP(.*)$", $suite_logo, $regs)) {
		$type_objet = 'SITE';
		$suite_logo = $regs[1];
		$_id_objet = "\"'0'\"";
		$id_objet = 'id_syndic'; # parait faux mais donne bien "siteNN"
	} else {
		if ($type_objet == 'SITE')
			$id_objet = "id_syndic";
		else
			$id_objet = "id_".strtolower($type_objet);
		$_id_objet = champ_sql($id_objet, $p);
	}

	// analyser les faux filtres
	$flag_fichier = $flag_stop = $flag_lien_auto = $code_lien = $filtres = $align = $lien = $params = '';

	if (is_array($p->fonctions)) {
		foreach($p->fonctions as $couple) {
			if (!$flag_stop) {
				$nom = trim($couple[0]);

				// double || signifie "on passe aux vrais filtres"
				if ($nom == '') {
					if ($couple[1]) {
						$params = $couple[1]; // recuperer #LOGO_DOCUMENT{20,30}
						array_shift($p->param);
					}
					else
						$flag_stop = true;
				} else {
					// faux filtres
					array_shift($p->param);
					switch($nom) {
						case 'left':
						case 'right':
						case 'center':
						case 'top':
						case 'bottom':
							$align = $nom;
							break;
						
						case 'lien':
							$flag_lien_auto = 'oui';
							$flag_stop = true; # apres |lien : vrais filtres
							break;

						case 'fichier':
							$flag_fichier = 1;
							$flag_stop = true; # apres |fichier : vrais filtres
							break;

						default:
							$lien = $nom;
							$flag_stop = true; # apres |#URL... : vrais filtres
							break;
					}
				}
			}
		}
	}

	//
	// Preparer le code du lien
	//
	// 1. filtre |lien
	if ($flag_lien_auto AND !$lien)
		$code_lien = '($lien = generer_url_'.$type_objet.'('.$_id_objet.')) ? $lien : ""';
	// 2. lien indique en clair (avec des balises : imprimer#ID_ARTICLE.html)
	else if ($lien) {
		$code_lien = "'".texte_script(trim($lien))."'";
		while (ereg("^([^#]*)#([A-Za-z_]+)(.*)$", $code_lien, $match)) {
			$c = new Champ();
			$c->nom_champ = $match[2];
			$c->id_boucle = $p->id_boucle;
			$c->boucles = &$p->boucles;
			$c->descr = $p->descr;
			$c = calculer_champ($c);
			$code_lien = str_replace('#'.$match[2], "'.".$c.".'", $code_lien);
		}
		// supprimer les '' disgracieux
		$code_lien = ereg_replace("^''\.|\.''$", "", $code_lien);
	}

	if ($flag_fichier)
		$code_lien = "'',''" ; 
	else {
		if (!$code_lien)
			$code_lien = "''";
		$code_lien .= ", '". $align . "'";
	}

	// cas des documents
	if ($type_objet == 'DOCUMENT') {
		$p->code = "calcule_logo_document($_id_objet, '" .
			$p->descr['documents'] .
			'\', $doublons, '. intval($flag_fichier).", $code_lien, '".
			// #LOGO_DOCUMENT{x,y} donne la taille maxi
			texte_script($params)
			."')";
	}
	else {
		$p->code = "affiche_logos(calcule_logo('$id_objet', '" .
			(($suite_logo == '_SURVOL') ? 'off' : 
			(($suite_logo == '_NORMAL') ? 'on' : 'ON')) .
			"', $_id_objet," .
			(($suite_logo == '_RUBRIQUE') ? 
			champ_sql("id_rubrique", $p) :
			(($type_objet == 'RUBRIQUE') ? "sql_parent($_id_objet)" : "''")) .
			",  '$flag_fichier'), $code_lien)";
	}

	$p->interdire_scripts = false;
	return $p;
}

// #EXTRA
// [(#EXTRA|extra{isbn})]
// ou [(#EXTRA|isbn)] (ce dernier applique les filtres definis dans mes_options)
// Champs extra
// Non documentes, en voie d'obsolescence, cf. ecrire/inc/extra
// http://doc.spip.org/@balise_EXTRA_dist
function balise_EXTRA_dist ($p) {
	$_extra = champ_sql('extra', $p);
	$p->code = $_extra;

	// Gerer la notation [(#EXTRA|isbn)]
	if ($p->fonctions) {
		list($champ,) = $p->fonctions[0];
		include_spip('inc/extra');
		$type_extra = $p->type_requete;

		// ci-dessus est sans doute un peu buggue : si on invoque #EXTRA
		// depuis un sous-objet sans champ extra d'un objet a champ extra,
		// on aura le type_extra du sous-objet (!)
		if (extra_champ_valide($type_extra, $champ)) {
			array_shift($p->fonctions);
			array_shift($p->param);
			// Appliquer les filtres definis par le webmestre
			$p->code = 'extra('.$p->code.', "'.$champ.'")';

			$filtres = extra_filtres($type_extra, $champ);
			if ($filtres) foreach ($filtres as $f)
				$p->code = "$f($p->code)";
		} else {
			if (!function_exists($champ)) {
				spip_log("erreur champ extra |$champ");
				array_shift($p->fonctions);
				array_shift($p->param);
			}
		}
	}

	#$p->interdire_scripts = true;
	return $p;
}

//
// Parametres de reponse a un forum
//

// http://doc.spip.org/@balise_PARAMETRES_FORUM_dist
function balise_PARAMETRES_FORUM_dist($p) {
	$_id_article = champ_sql('id_article', $p);
	$p->code = '
		// refus des forums ?
		(sql_accepter_forum('.$_id_article.')=="non" OR
		($GLOBALS["meta"]["forums_publics"] == "non"
		AND sql_accepter_forum('.$_id_article.') == ""))
		? "" : // sinon:
		';

	switch ($p->type_requete) {
		case 'articles':
			$c = '"id_article=".' . champ_sql('id_article', $p);
			break;
		case 'breves':
			$c = '"id_breve=".' . champ_sql('id_breve', $p);
			break;
		case 'rubriques':
			$c = '"id_rubrique=".' . champ_sql('id_rubrique', $p);
			break;
		case 'syndication':
			$c = '"id_syndic=".' . champ_sql('id_syndic', $p);
			break;
		case 'forums':
		default:
			$liste_champs = array ("id_article","id_breve","id_rubrique","id_syndic","id_forum");
			$c = '';
			foreach ($liste_champs as $champ) {
				$x = champ_sql( $champ, $p);
				$c .= (($c) ? ".\n" : "") . "((!$x) ? '' : ('&$champ='.$x))";
			}
			$c = "substr($c,1)";
			break;
	}

	// Syntaxe [(#PARAMETRES_FORUM{#SELF})] pour fixer le retour du forum
	# note : ce bloc qui sert a recuperer des arguments calcules pourrait
	# porter un nom et faire partie de l'API.
	if ($p->param && !$p->param[0][0]) {
		  $retour = array_shift( $p->param );
		  array_shift($retour);
		  $retour = calculer_liste($retour[0],
					   $p->descr,
					   $p->boucles,
					   $p->id_boucle);
	}
	else
		$retour = "''";

	// Attention un eventuel &retour=xxx dans l'URL est prioritaire
	$c .= '.
	(($lien = (_request("retour") ? _request("retour") : str_replace("&amp;", "&", '.$retour.'))) ? "&retour=".rawurlencode($lien) : "")';

	$p->code .= code_invalideur_forums($p, "(".$c.")");

	$p->interdire_scripts = false;
	return $p;
}


// Noter l'invalideur de la page contenant ces parametres,
// en cas de premier post sur le forum
// http://doc.spip.org/@code_invalideur_forums
function code_invalideur_forums($p, $code) {
	$type = 'id_forum';
	$valeur = "\n\t\tcalcul_index_forum("
		// Retournera 4 [$SP] mais force la demande du champ SQL
		. champ_sql('id_article', $p) . ','
		. champ_sql('id_breve', $p) .  ','
		. champ_sql('id_rubrique', $p) .','
		. champ_sql('id_syndic', $p) .  ")\n\t";

	return '
	// invalideur '.$type.'
	(!($Cache[\''.$type.'\']['.$valeur."]=1) ? '':\n\t" . $code .")\n";
}

// Reference a l'URL de la page courante
// Attention dans un INCLURE() ou une balise dynamique on n'a pas le droit de
// mettre en cache #SELF car il peut correspondre a une autre page (attaque XSS)
// (Dans ce cas faire <INCLURE{self=#SELF}> pour differencier les caches.)
// http://www.spip.net/@self
// http://doc.spip.org/@balise_SELF_dist
function balise_SELF_dist($p) {
	$p->code = 'quote_amp(self())';
	$p->interdire_scripts = false;
	return $p;
}


//
// #URL_PAGE{backend} -> backend.php3 ou ?page=backend selon les cas
// Pour les pages qui commencent par "spip_", il faut eventuellement
// aller chercher spip_action.php?action=xxxx
//
// http://doc.spip.org/@balise_URL_PAGE_dist
function balise_URL_PAGE_dist($p) {

	if ($p->param && !$p->param[0][0]) {
		$p->code =  calculer_liste($p->param[0][1],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		$args =  calculer_liste($p->param[0][2],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		if ($args != "''")
			$p->code .= ','.$args;

		// autres filtres (???)
		array_shift($p->param);
	}

	$p->code = 'generer_url_public(' . $p->code .')';

	#$p->interdire_scripts = true;
	return $p;
}

//
// #URL_ECRIRE{naviguer} -> ecrire/?exec=naviguer
//
// http://doc.spip.org/@balise_URL_ECRIRE_dist
function balise_URL_ECRIRE_dist($p) {

	if ($p->param && !$p->param[0][0]) {
		$p->code =  calculer_liste($p->param[0][1],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		$args =  calculer_liste($p->param[0][2],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		if ($args != "''")
			$p->code .= ','.$args;

		// autres filtres (???)
		array_shift($p->param);
	}

	$p->code = 'generer_url_ecrire(' . $p->code .')';

	#$p->interdire_scripts = true;
	return $p;
}

//
// #CHEMIN{fichier} -> find_in_path(fichier)
//
// http://doc.spip.org/@balise_CHEMIN_dist
function balise_CHEMIN_dist($p) {
	if ($p->param && !$p->param[0][0]) {
		$p->code =  calculer_liste($p->param[0][1],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		// autres filtres (???)
		array_shift($p->param);
	}

	$p->code = 'find_in_path(' . $p->code .')';

	#$p->interdire_scripts = true;
	return $p;
}

//
// #ENV
// l'"environnement", id est le $contexte (ou $contexte_inclus)
//
// en standard on applique |entites_html, mais si vous utilisez
// [(#ENV*{toto})] il *faut* vous assurer vous-memes de la securite
// anti-javascript (par exemple en filtrant avec |safehtml)
//
// La syntaxe #ENV{toto, rempl} renverra 'rempl' si $toto est vide
//
// http://doc.spip.org/@balise_ENV_dist
function balise_ENV_dist($p, $src = NULL) {
	// le tableau de base de la balise (cf #META ci-dessous)
	if (!$src) $src = '$Pile[0]';

	if ($a = $p->param) {
		$sinon = array_shift($a);
		if  (!array_shift($sinon)) {
			$p->fonctions = $a;
			array_shift( $p->param );
			$nom = array_shift($sinon);
			$nom = ($nom[0]->type=='texte') ? $nom[0]->texte : "";
		}
	}

	if (!$nom) {
		// cas de #ENV sans argument : on retourne le serialize() du tableau
		// une belle fonction [(#ENV|affiche_env)] serait pratique
		$p->code = 'serialize('.$src.')';
	} else {
		// admet deux arguments : nom de variable, valeur par defaut si vide
		$p->code = $src.'[\'' . addslashes($nom) . '\']';
		if ($sinon)
			$p->code = 'sinon('. 
				$p->code
				. compose_filtres_args($p, $sinon, ',')
				. ')';
	}
	#$p->interdire_scripts = true;

	return $p;
}

//
// #CONFIG
// les reglages du site
//
// Par exemple #CONFIG{gerer_trad} donne 'oui' ou 'non' selon le reglage
// Attention c'est brut de decoffrage de la table spip_meta
//
// La balise fonctionne exactement comme #ENV (ci-dessus)
//
// http://doc.spip.org/@balise_CONFIG_dist
function balise_CONFIG_dist($p) {
	if(function_exists('balise_ENV'))
		return balise_ENV($p, '$GLOBALS["meta"]');
	else
		return balise_ENV_dist($p, '$GLOBALS["meta"]');
}


//
// #EVAL{...}
// evalue un code php ; a utiliser avec precaution :-)
//
// rq: #EVAL{code} produit eval('return code;')
// mais si le code est une expression sans balise, on se dispense
// de passer par une construction si compliquee, et le code est
// passe tel quel (entre parentheses, et protege par interdire_scripts)
// Exemples : #EVAL**{6+9} #EVAL**{_DIR_IMG_PACK} #EVAL{'date("Y-m-d")'}
// #EVAL{'str_replace("r","z", "roger")'}  (attention les "'" sont interdits)
// http://doc.spip.org/@balise_EVAL_dist
function balise_EVAL_dist($p) {
	if ($p->param && !$p->param[0][0]) {
		$php = array_shift( $p->param );
		array_shift($php);
		$php = calculer_liste($php[0],
					$p->descr,
					$p->boucles,
					$p->id_boucle);
	}

	if ($php) {
		# optimisation sur les #EVAL{une expression sans #BALISE}
		# attention au commentaire "// x signes" qui precede
		if (preg_match(",^([[:space:]]*//[^\n]*\n)'([^']+)'$,ms",
		$php,$r))
			$p->code = /* $r[1]. */'('.$r[2].')';
		else
			$p->code = "eval('return '.$php.';')";
	} else
		$p->code = '';

	#$p->interdire_scripts = true;

	return $p;
}

//
// #REM
// pour les remarques : renvoie toujours ''
//
// http://doc.spip.org/@balise_REM_dist
function balise_REM_dist($p) {
	$p->code="''";
	$p->interdire_scripts = false;
	return $p;
}


//
// #HTTP_HEADER
// pour les entetes de retour http
// Ne fonctionne pas sur les INCLURE !
// #HTTP_HEADER{Content-Type: text/css}
//
// http://doc.spip.org/@balise_HTTP_HEADER_dist
function balise_HTTP_HEADER_dist($p) {

	$header = calculer_liste($p->param[0][1],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

	$p->code = "'<'.'?php header(\"' . "
		. $header
		. " . '\"); ?'.'>'";
	$p->interdire_scripts = false;
	return $p;
}

//
// #CACHE
// definit la duree de vie ($delais) du squelette
// #CACHE{24*3600}
// http://doc.spip.org/@balise_CACHE_dist
function balise_CACHE_dist($p) {
	$duree = valeur_numerique($p->param[0][1][0]->texte);

	// noter la duree du cache dans un entete proprietaire
	$p->code .= '\'<'.'?php header("X-Spip-Cache: '
		. $duree
		. '"); ?'.'>\'';

	// remplir le header Cache-Control
	if ($duree > 0)
		$p->code .= '.\'<'.'?php header("Cache-Control: max-age='
			. $duree
			. '"); ?'.'>\'';
	else
		$p->code .= '.\'<'
		.'?php header("Cache-Control: no-store, no-cache, must-revalidate"); ?'
		.'><'
		.'?php header("Pragma: no-cache"); ?'
		.'>\'';

	$p->interdire_scripts = false;
	return $p;
}

//
// #INSERT_HEAD
// pour permettre aux plugins d'inserer des styles, js ou autre
// dans l'entete sans modification du squelette
// #INSERT_HEAD
//
// http://doc.spip.org/@balise_INSERT_HEAD_dist
function balise_INSERT_HEAD_dist($p) {
	$p->code = "pipeline('insert_head','')";
	$p->interdire_scripts = false;
	return $p;
}

//
// #INCLURE statique
// l'inclusion est realisee au calcul du squelette, pas au service
// corrolairement, le produit du squelette peut etre utilise en entree de filtres a suivre
//
// http://doc.spip.org/@balise_INCLUDE_dist
function balise_INCLUDE_dist($p) {
	if(function_exists('balise_INCLURE'))
		return balise_INCLURE($p);
	else
		return balise_INCLURE_dist($p);
}
// http://doc.spip.org/@balise_INCLURE_dist
function balise_INCLURE_dist($p) {
	$champ = phraser_arguments_inclure($p, true);
	$l = argumenter_inclure($champ, $p->descr, $p->boucles, $p->id_boucle, false);
	
	$code = "recuperer_fond('',array(".implode(',',$l)."))";

	$commentaire = '#INCLURE ' . str_replace("\n", ' ', $code);

	$p->code = "\n//$commentaire.\n$code";
	$p->interdire_scripts = false;
	return $p;
}

//
// #SET
// Affecte une variable locale au squelette
// #SET{nom,valeur}
// la balise renvoie la valeur
// http://doc.spip.org/@balise_SET_dist
function balise_SET_dist($p){
	if ($p->param && !$p->param[0][0]) {
		$_nom =  calculer_liste($p->param[0][1],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		$_valeur =  calculer_liste($p->param[0][2],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		if ($args != "''")
			$p->code .= ','.$args;

		// autres filtres (???)
		array_shift($p->param);
	}

	$p->code = "vide(\$Pile['vars'][$_nom] = $_valeur)";

	#$p->interdire_scripts = true;
	return $p;
}

//
// #GET
// Recupere une variable locale au squelette
// #GET{nom,defaut} renvoie defaut si la variable nom n'a pas ete affectee
//
// http://doc.spip.org/@balise_GET_dist
function balise_GET_dist($p) {
	if (function_exists('balise_ENV'))
		return balise_ENV($p, '$Pile["vars"]');
	else
		return balise_ENV_dist($p, '$Pile["vars"]');
}
?>