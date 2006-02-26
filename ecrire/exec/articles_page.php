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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire("inc_presentation");
include_ecrire("inc_texte");
charger_generer_url();
include_ecrire("inc_rubriques");

function exec_articles_page_dist()
{
  global $connect_statut, $connect_id_auteur;

	debut_page(_T('titre_page_articles_page'), "documents", "articles");

	debut_gauche();

//
// Afficher le bouton de creation d'article
//

	$result = spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1");

	if (spip_num_rows($result) > 0) {
		debut_raccourcis();
		icone_horizontale (_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","new=oui"), "article-24.gif", "creer.gif");
		fin_raccourcis();
	} else {
		if ($connect_statut == '0minirezo') {
		echo _T('texte_creer_rubrique');
		icone_horizontale (_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav"), "rubrique-24.gif", "creer.gif");
	}
}

debut_droite();

//
// Vos articles en cours de redaction
//

echo "<P align=left>";
afficher_articles(_T('info_en_cours_validation'),
	", spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut='prepa' ORDER BY articles.date DESC");
echo "</p>";



//
// Vos articles soumis au vote
//

echo "<p>";
afficher_articles(_T('info_attente_validation'),
	", spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut='prop' ORDER BY articles.date");
echo "</p>";

//
// Vos articles publies
//

echo "<p>";
afficher_articles(_T('info_publies'),
	", spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut='publie' ORDER BY articles.date DESC", true);
echo "</p>";

//
//  Vos articles refuses
//

echo "<p>";
afficher_articles(_T('info_refuses'),
	", spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut='refuse' ORDER BY articles.date DESC");
echo "</p>";

fin_page();
}

// Debloquer articles

function changer_statut_articles_page($debloquer_article)
{
	global $connect_id_auteur;
	if ($debloquer_article) {
		if ($debloquer_article <> 'tous')
			$where_id = "AND id_article=".intval($debloquer_article);
		$query = "UPDATE spip_articles SET auteur_modif='0' WHERE auteur_modif=$connect_id_auteur $where_id";
		spip_query ($query);
	}
}

?>
