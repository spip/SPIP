<?php 

include ("inc.php3");

debut_page(_T('titre_page_articles_page'), "documents", "articles");

debut_gauche();

//
// Afficher le bouton de creation d'article
//

$query = "SELECT id_rubrique FROM spip_rubriques LIMIT 0,1";
$result = spip_query($query);

if (spip_num_rows($result) > 0) {
	debut_raccourcis();
	icone_horizontale (_T('icone_ecrire_article'), "articles_edit.php3?new=oui", "article-24.gif", "creer.gif");
	fin_raccourcis();
}
else {
	if ($connect_statut == '0minirezo') {
		echo _T('texte_creer_rubrique');
		icone_horizontale (_T('icone_creer_rubrique'), "rubriques_edit.php3?new=oui&retour=nav", "rubrique-24.gif", "creer.gif");
	}
}

debut_droite();




//
// Vos articles en cours de redaction
//

echo "<P align=left>";
afficher_articles(_T('info_en_cours_validation'),
	"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut=\"prepa\" ORDER BY articles.date DESC");




//
// Vos articles soumis au vote
//

echo "<p>";
afficher_articles(_T('info_attente_validation'),
	"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut='prop' ORDER BY articles.date");


//
// Vos articles publies
//

echo "<p>";
afficher_articles(_T('info_publies'),
	"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut=\"publie\" ORDER BY articles.date DESC", true);

//
//  Vos articles refuses
//

echo "<p>";
afficher_articles(_T('info_refuses'),
	"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut=\"refuse\" ORDER BY articles.date DESC");


fin_page();


?>
