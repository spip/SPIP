<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

// http://doc.spip.org/@exec_menu_navigation_dist
function exec_menu_navigation_dist() {
	global $connect_id_auteur, $spip_lang_left;

	$id_rubrique = intval(_request('id_rubrique'));

	$gadget = '<div style="width: 300px;">';

	$vos_articles = sql_select("A.id_article, A.id_rubrique, A.titre, A.statut", "spip_articles AS A, spip_auteurs_articles AS L", "A.id_article=L.id_article AND L.id_auteur=$connect_id_auteur AND A.statut='prepa'", "A.date", "A.date DESC", "5");
	$vos = '';
	while($row = sql_fetch($vos_articles)) {
		$id_article = $row['id_article'];
		$titre = typo(sinon($row['titre'], _T('ecrire:info_sans_titre')));
		$statut = $row['statut'];
		$h = generer_url_ecrire("articles","id_article=$id_article");
		$vos .= "<a class='$statut spip_xx-small' href='$h'>$titre</a>\n";
	}
	if ($vos) {
		$t = _T('info_en_cours_validation');
		$gadget .= debut_cadre('bandeau-rubriques',"article-24.png",'',afficher_plus(generer_url_ecrire("articles_page")).$t)
		. "\n<div class='plan-articles'>\n"
		. $vos
		. "</div>"
		. fin_cadre('bandeau-rubriques');
	}
	
	$vos_articles = sql_select("id_article, id_rubrique, titre, statut", "spip_articles", "statut='prop'", "date", "date DESC", "5");
	$vos = '';
	while($row = sql_fetch($vos_articles)) {
		$id_article = $row['id_article'];
		$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
		$statut = $row['statut'];
		$h = generer_url_ecrire("articles","id_article=$id_article");
		$vos .= "<a class='$statut' href='$h'>$titre</a>";
	}
	if ($vos) {
		$gadget .= debut_cadre('bandeau-rubriques',"article-24.png",'',afficher_plus(generer_url_ecrire())._T('info_articles_proposes'))
		. "<div class='plan-articles'>"
		. $vos
		. "</div>"
		. fin_cadre('bandeau-rubriques');
	}

	$vos_articles = sql_select("id_breve,titre,statut", "spip_breves", "statut='prop'", "date_heure", "date_heure DESC", "5");
	$vos = '';
	while($row = sql_fetch($vos_articles)) {
		$id_breve = $row['id_breve'];
		$titre = typo(sinon($row['titre'], _T('ecrire:info_sans_titre')));
		$statut = $row['statut'];
		$vos .= "<a class='$statut spip_xx-small' href='" . generer_url_ecrire("breves_voir","id_breve=$id_breve") . "'>$titre</a>";
	}
	if ($vos) {
		$gadget .= debut_cadre('bandeau-rubriques',"breve-24.png",'',afficher_plus(generer_url_ecrire("breves"))._T('info_breves_valider'))
		. "<div class='plan-articles'>"
		. $vos
		. "</div>"
		. fin_cadre('bandeau-rubriques');
	}

	$une_rubrique = sql_getfetsel('id_rubrique', 'spip_rubriques', '','','', 1);
	if ($une_rubrique) {

		$gadget .= "<div>&nbsp;</div>";
		if ($id_rubrique > 0) {
			$dans_rub = "&id_rubrique=$id_rubrique";
			$dans_parent = "&id_parent=$id_rubrique";
		} else $dans_rub = $dans_parent = '';
		if (autoriser('creerrubriquedans', 'rubrique', $id_rubrique)) {	
#			$gadget .= "<div style='width: 140px; float: $spip_lang_left;'>";
			if ($id_rubrique > 0)
				$gadget .= icone_horizontale_display(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui$dans_parent"), "rubrique-24.png", "new", false);
			else 
				$gadget .= icone_horizontale_display(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui"), "rubrique-24.png", "new", false);
#			$gadget .= "</div>";
		}		
#		$gadget .= "<div style='width: 140px; float: $spip_lang_left;'>";
		$gadget .= icone_horizontale_display(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","new=oui$dans_rub"), "article-24.png","new", false);
#		$gadget .= "</div>";
			
		if ($GLOBALS['meta']["activer_breves"] != "non") {
#			$gadget .= "<div style='width: 140px;  float: $spip_lang_left;'>";
			$gadget .= icone_horizontale_display(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui$dans_rub"), "breve-24.png","new", false);
#			$gadget .= "</div>";
		}
			
		if (autoriser('creersitedans', 'rubrique', $une_rubrique)) {
			$gadget .= # "<div style='width: 140px; float: $spip_lang_left;'>" .
			 icone_horizontale_display(_T('info_sites_referencer'), generer_url_ecrire("sites_edit","new=oui$dans_rub"), "site-24.png","new", false)
			#. "</div>"
;
		}
			
	}

	$gadget .="</div>";

	ajax_retour($gadget);
}
?>
