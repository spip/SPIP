<?php 

include ("inc.php3");

debut_page("Votre espace priv&eacute;");

debut_gauche();



if($options != 'avancees') {
	debut_boite_info();
	echo "<P align=center><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>&Agrave; SUIVRE</B></FONT>";
	echo "<P align=left><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>".propre("Cette page recense l'actualit&eacute; du site et vous permet de suivre vos contributions. Vous y retrouverez vos articles en cours de r&eacute;daction, les articles et les br&egrave;ves pour lesquelles vous &ecirc;tes invit&eacute; &agrave; donner votre avis, puis un rappel de vos pr&eacute;c&eacute;dentes contributions.<p>Quand vous serez familiaris&eacute;(e) avec SPIP, cliquez sur &laquo;interface compl&egrave;te&raquo; pour ouvrir plus de possibilit&eacute;s.")."</FONT>";
	fin_boite_info();
}


//
// Annonces
//
$query = "SELECT * FROM spip_messages WHERE type = 'affich' AND statut = 'publie' ORDER BY date_heure DESC";
$result = spip_query($query);

if (mysql_num_rows($result) > 0){
	echo "<p><table cellpadding=1 cellspacing=0 border=0 width=100%><tr><td width=100% bgcolor='black'>";
	echo "<table cellpadding=5 cellspacing=0 border=0 width=100%><tr><td width=100% bgcolor='yellow'>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='1'>";
	while ($row = mysql_fetch_object($result)) {
		if (ereg("^=([^[:space:]]+)$",$row->texte,$match))
			$url = $match[1];
		else
			$url = "message.php3?id_message=".$row->id_message;
		$titre = typo($row->titre);
		echo "<li> <a href='$url'>$titre</a>\n";
	}
	echo "</font>";
	echo "</div>";
	echo "</td></tr></table>";
	echo "</td></tr></table>";
}



//
// Infos personnelles : nom, utilisation de la messagerie
//

echo "<p align='left'>";

debut_boite_info();
echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2'>";
echo bouton_block_invisible("info_perso");
echo "<font size='1' color='black'><b>".majuscules($connect_nom)."</b></font>";

echo debut_block_invisible("info_perso");
echo "<hr>";

if ($connect_activer_messagerie != "non") {
	echo "Vous utilisez la messagerie interne de ce site. ";
	
	if ($connect_activer_imessage != "non") {
		echo "Votre nom appara&icirc;t dans la liste des utilisateurs connect&eacute;s.";
	}
	else {
		echo "Votre nom n'appara&icirc;t pas dans la liste des utilisateurs connect&eacute;s.";
	}
}
else {
	echo "<br>Vous n'utilisez pas la messagerie interne de ce site.";
}

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1>";
echo "<br><IMG SRC='IMG2/triangle.gif' WIDTH=16 HEIGHT=14 BORDER=0>";
echo " <b><a HREF='auteurs_edit.php3?id_auteur=$connect_id_auteur&redirect=index.php3'>MODIFIER VOS INFORMATIONS PERSONNELLES</a></b>";
echo "</FONT>";


//
// Supprimer le cookie, se deconnecter...
//

if ($connect_statut == "0minirezo" AND $cookie_admin) {
	echo "<hr>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
	echo "<img src='IMG2/triangle.gif' width=16 height=14 border=0>";
	echo " <a href='../spip_cookie.php3?supp_cookie=oui'><B>SUPPRIMER LE COOKIE</B></A>";
		echo aide ("cookie");
	echo "</font>";
}

if ($auth_can_disconnect) {
	echo "<hr>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
	echo "<img src='IMG2/triangle.gif' width=16 height=14 border=0>";
	echo " <a href='?logout=$connect_login'><b>SE D&Eacute;CONNECTER</b></a>";
	echo "</font>";
}

echo fin_block();

fin_boite_info();


//
// Afficher les principales rubriques
//

echo "<P align=left>";

echo "<TABLE CELLPADDING=3 CELLSPACING=0 BORDER=0 WIDTH=\"100%\">";

$query = "SELECT id_rubrique, titre FROM spip_rubriques WHERE id_parent=0 ORDER BY titre";
$result = spip_query($query);
while ($row = mysql_fetch_array($result)) {
	$id_rubrique = $row['id_rubrique'];
	$titre_rubrique = typo($row['titre']);
	if  (acces_restreint_rubrique($id_rubrique))
		echo "<TR><TD BACKGROUND='IMG2/rien.gif'><A HREF='naviguer.php3?coll=$id_rubrique'><IMG SRC='IMG2/triangle-anim.gif' WIDTH=16 HEIGHT=14 BORDER=0></A></TD>";
	else
		echo "<TR><TD BACKGROUND='IMG2/rien.gif'><A HREF='naviguer.php3?coll=$id_rubrique'><IMG SRC='IMG2/triangle.gif' WIDTH=16 HEIGHT=14 BORDER=0></A></TD>";

	echo "<TD BACKGROUND='IMG2/rien.gif' WIDTH=\"100%\"><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B>";
	echo "<A HREF='naviguer.php3?coll=$id_rubrique'>$titre_rubrique</A>";
	echo "</B></FONT></TD></TR>";
}

echo "</TABLE>";


debut_droite();


//
// Restauration d'une archive
//

if ($meta["debut_restauration"]) {

	if ($flag_ignore_user_abort) {
		@ignore_user_abort(1);
	}
	include ("inc_import.php3");

	$archive = $meta["fichier_restauration"];
	$my_pos = $meta["status_restauration"];
	$ok = file_exists($archive);

	if ($ok) {
		$pourcent = floor(100 * $my_pos / filesize($archive));
		$texte_boite = "La base est en cours de restauration ($pourcent&nbsp;%).<p>
		Veuillez recharger cette page dans quelques instants.";
	}
	else {
		$texte_boite = "Erreur de restauration : fichier inexistant.";
	}
	debut_boite_alerte();
	echo "<font FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=4 color='black'><B>$texte_boite</B></font>";
	fin_boite_alerte();
	fin_page();
	echo "</HTML><font color='white'>\n<!--";
	@flush();
	$gz = $flag_gz;
	$_fopen = ($gz) ? gzopen : fopen;

	if ($ok) {
		$f = $_fopen($archive, "rb");
		$pos = 0;
		$buf = "";
		if (!import_all($f, $gz)) import_abandon();
	}
	else {
		import_fin();
	}
	exit;
}


//
// Modification du cookie
//

if ($connect_statut == "0minirezo") {
	if (!$cookie_admin) {
		echo "Vous pouvez activer un cookie, ce qui vous permettra d'&eacute;diter directement les articles depuis le site public.";
		echo aide ("cookie");

		bouton("Placer un cookie", "../spip_cookie.php3?ajout_cookie=oui");
		echo "<p><hr><p>";
	}
}


//
// Afficher les boutons de creation d'article et de breve
//

$query = "SELECT id_rubrique FROM spip_rubriques LIMIT 0,1";
$result = spip_query($query);

if (mysql_num_rows($result) > 0) {

	echo "<P align=right>";
	echo "<A HREF='./articles_edit.php3?new=oui' onMouseOver=\"ecrire_article.src='IMG2/ecrire-article-on.gif'\" onMouseOut=\"ecrire_article.src='IMG2/ecrire-article-off.gif'\"><img src='IMG2/ecrire-article-off.gif' alt='Ecrire un nouvel article' width='69' height='53' border='0' name='ecrire_article'></A>";

	$activer_breves = lire_meta("activer_breves");
	if ($activer_breves != "non") {
		echo " &nbsp; ";
		echo "<A HREF='./breves_edit.php3?new=oui' onMouseOver=\"ecrire_breve.src='IMG2/ecrire-breve-on.gif'\" onMouseOut=\"ecrire_breve.src='IMG2/ecrire-breve-off.gif'\"><img src='IMG2/ecrire-breve-off.gif' alt='Ecrire une nouvelle breve' width='75' height='53' border='0' name='ecrire_breve'></A>";
	}
}
else {
	if ($connect_statut == '0minirezo') {
		echo "<P align=right>";
		echo "Avant de pouvoir &eacute;crire des articles,<BR> vous devez <A HREF='rubriques_edit.php3?new=oui&retour=nav' onMouseOver=\"creer_rubrique.src='IMG2/creer-rubrique-on.gif'\" onMouseOut=\"creer_rubrique.src='IMG2/creer-rubrique-off.gif'\">cr&eacute;er au moins une rubrique</A>.<BR>";
		echo "<A HREF='rubriques_edit.php3?new=oui&retour=nav' onMouseOver=\"creer_rubrique.src='IMG2/creer-rubrique-on.gif'\" onMouseOut=\"creer_rubrique.src='IMG2/creer-rubrique-off.gif'\"><img src='IMG2/creer-rubrique-off.gif' alt='Creer une nouvelle sous-rubrique' width='95' height='56' border='0' name='creer_rubrique' ALIGN='top'></A>";
	}
}


//
// Articles post-dates en attente de publication
//

$post_dates = lire_meta("post_dates");

if ($post_dates == "non" AND $connect_statut == '0minirezo' AND $options == 'avancees') {
	echo "<P align=left>";
	afficher_articles("Les articles post-dat&eacute;s &agrave; para&icirc;tre",
		"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
		"FROM spip_articles WHERE statut='publie' AND date>NOW() ORDER BY date");
}


//
// Vos articles en cours de redaction
//

echo "<P align=left>";
afficher_articles("Vos articles en cours de r&eacute;daction",
	"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut=\"prepa\" ORDER BY articles.date DESC");


//
// Verifier les boucles a mettre en relief
//

$relief = false;

if (!$relief) {
	$query = "SELECT id_article FROM spip_articles WHERE statut='prop' LIMIT 0,1";
	$result = spip_query($query);
	$relief = (mysql_num_rows($result) > 0);
}

if (!$relief) {
	$query = "SELECT id_breve FROM spip_breves WHERE statut='prop' LIMIT 0,1";
	$result = spip_query($query);
	$relief = (mysql_num_rows($result) > 0);
}

if (!$relief) {
	$query = "SELECT id_syndic FROM spip_syndic WHERE statut='prop' LIMIT 0,1";
	$result = spip_query($query);
	$relief = (mysql_num_rows($result) > 0);
}

if (!$relief AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
	$query = "SELECT id_syndic FROM spip_syndic WHERE syndication='off' LIMIT 0,1";
	$result = spip_query($query);
	$relief = (mysql_num_rows($result) > 0);
}


if ($relief) {
	echo "<p>";
	debut_cadre_relief();

	//
	// Les articles a valider
	//
	afficher_articles("Les articles propos&eacute;s &agrave; la publication",
		"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
		"FROM spip_articles WHERE statut='prop' ORDER BY date DESC");


	//
	// Les breves a valider
	//
	$query = "SELECT * FROM spip_breves WHERE statut='prepa' OR statut='prop' ORDER BY date_heure DESC";
	afficher_breves("Les br&egrave;ves &agrave; valider", $query);

	//
	// Les sites references a valider
	//
	afficher_sites("Les sites &agrave; valider", "SELECT * FROM spip_syndic WHERE statut='prop' ORDER BY nom_site");

	//
	// Les sites a probleme
	//
	if ($connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		afficher_sites("Ces sites syndiqu&eacute;s ont pos&eacute; un probl&egrave;me",
			"SELECT * FROM spip_syndic WHERE syndication='off' ORDER BY nom_site");
	}
	
	fin_cadre_relief();	
}	


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
	afficher_articles("Vos derniers articles publi&eacute;s en ligne",
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


include_local ("inc_mail.php3");
include_local ("inc_sites.php3");
include_local ("inc_index.php3");

envoyer_mail_quoi_de_neuf();

executer_une_syndication();
executer_une_indexation_syndic();


?>
