<?php 

include ("inc.php3");

debut_page("Les articles", "documents", "articles");

debut_gauche();

//
// Afficher le bouton de creation d'article
//

$query = "SELECT id_rubrique FROM spip_rubriques LIMIT 0,1";
$result = spip_query($query);

if (mysql_num_rows($result) > 0) {
	icone_horizontale ("&Eacute;crire un nouvel article", "articles_edit.php3?new=oui", "article-24.gif", "creer.gif");
}
else {
	if ($connect_statut == '0minirezo') {
		echo "Avant de pouvoir &eacute;crire des articles,<BR> vous devez cr&eacute;er une rubrique.";
		icone_horizontale ("Cr&eacute;er une rubrique", "rubriques_edit.php3?new=oui&retour=nav", "rubrique-24.gif", "creer.gif");
	}
}

debut_droite();




//
// Vos articles en cours de redaction
//

echo "<P align=left>";
afficher_articles("Vos articles en cours de r&eacute;daction",
	"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut=\"prepa\" ORDER BY articles.date DESC");




//
// Vos articles soumis au vote
//

echo "<p>";
afficher_articles("Vos articles en attente de validation",
	"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut='prop' ORDER BY articles.date");

if ($options == 'avancees') {

	//
	// Vos articles publies
	//

	echo "<p>";
	afficher_articles("Vos articles publi&eacute;s en ligne",
		"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
		"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
		"WHERE articles.id_article=lien.id_article AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut=\"publie\" ORDER BY articles.date DESC", true);

	//
	//  Vos articles refuses
	//

	echo "<p>";
	afficher_articles("Vos articles refus&eacute;s",
		"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
		"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
		"WHERE articles.id_article=lien.id_article AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut=\"refuse\" ORDER BY articles.date DESC");

}


fin_page();


//
// Si necessaire, recalculer les rubriques
//

if (lire_meta('calculer_rubriques') == 'oui') {
	calculer_rubriques();
	effacer_meta('calculer_rubriques');
	ecrire_metas();
}


//
// Renouvellement de l'alea utilise pour valider certaines operations
// (ajouter une image, etc.)
//

$maj_alea = $meta_maj['alea_ephemere'];
$t_jour = substr($maj_alea, 6, 2);
if ($t_jour != date('d')) {
	ecrire_meta('alea_ephemere_ancien', lire_meta('alea_ephemere'));
	$seed = (double) (microtime() + 1) * time();
	@mt_srand($seed);
	$alea = @mt_rand();
	if (!$alea) {
		srand($seed);
		$alea = rand();
	}
	ecrire_meta('alea_ephemere', $alea);
	ecrire_metas();
}

//
// Optimisation periodique de la base de donnees
//

$date_opt = $meta['date_optimisation'];
$date = time();
if (($date - $date_opt) > 24 * 3600) {
	ecrire_meta("date_optimisation", "$date");
	ecrire_metas();
	include ("optimiser.php3");
}


include_ecrire ("inc_mail.php3");
include_ecrire ("inc_sites.php3");
include_ecrire ("inc_index.php3");

envoyer_mail_quoi_de_neuf();

executer_une_syndication();
executer_une_indexation_syndic();


?>
