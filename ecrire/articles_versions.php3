<?php

include ("inc.php3");

include_ecrire ("inc_diff.php3");

$articles_surtitre = lire_meta("articles_surtitre");
$articles_soustitre = lire_meta("articles_soustitre");
$articles_descriptif = lire_meta("articles_descriptif");
$articles_urlref = lire_meta("articles_urlref");
$articles_chapeau = lire_meta("articles_chapeau");
$articles_ps = lire_meta("articles_ps");
$articles_redac = lire_meta("articles_redac");
$articles_mots = lire_meta("articles_mots");
$articles_versions = lire_meta("articles_versions");

$clean_link = new Link("articles.php3?id_article=$id_article");

// Initialiser doublons pour documents (completes par "propre($texte)")
$id_doublons['documents'] = "0";



//////////////////////////////////////////////////////
// Determiner les droits d'edition de l'article
//

$query = "SELECT statut, titre, id_rubrique FROM spip_articles WHERE id_article=$id_article";
$result = spip_query($query);
if ($row = spip_fetch_array($result)) {
	$statut_article = $row['statut'];
	$titre_article = $row['titre'];
	$rubrique_article = $row['id_rubrique'];
}
else {
	$statut_article = '';
}

$query = "SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur";
$result_auteur = spip_query($query);

$flag_auteur = (spip_num_rows($result_auteur) > 0);
$flag_editable = (acces_rubrique($rubrique_article)
	OR ($flag_auteur AND ($statut_article == 'prepa' OR $statut_article == 'prop' OR $statut_article == 'poubelle')));


//
// Lire l'article
//

$query = "SELECT * FROM spip_articles WHERE id_article='$id_article'";
$result = spip_query($query);

if ($row = spip_fetch_array($result)) {
	$id_article = $row["id_article"];
	$surtitre = $row["surtitre"];
	$titre = $row["titre"];
	$soustitre = $row["soustitre"];
	$id_rubrique = $row["id_rubrique"];
	$descriptif = $row["descriptif"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$chapo = $row["chapo"];
	$texte = $row["texte"];
	$ps = $row["ps"];
	$date = $row["date"];
	$statut_article = $row["statut"];
	$maj = $row["maj"];
	$date_redac = $row["date_redac"];
	$visites = $row["visites"];
	$referers = $row["referers"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];
}

if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date_redac, $regs)) {
        $mois_redac = $regs[2];
        $jour_redac = $regs[3];
        $annee_redac = $regs[1];
        if ($annee_redac > 4000) $annee_redac -= 9000;
}

if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date, $regs)) {
        $mois = $regs[2];
        $jour = $regs[3];
        $annee = $regs[1];
}



debut_page("&laquo; $titre_article &raquo;", "documents", "articles");

debut_grand_cadre();

afficher_parents($id_rubrique);
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>"._T('lien_racine_site')."</B></A> ".aide ("rubhier")."<BR>".$parents;
$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);
echo "$parents";

fin_grand_cadre();



//////////////////////////////////////////////////////
// Affichage de la colonne de gauche
//

debut_gauche();



//////////////////////////////////////////////////////
// Affichage de la colonne de droite
//

debut_droite();


changer_typo('','article'.$id_article);



debut_cadre_relief();

//
// Titre, surtitre, sous-titre
//

if ($statut_article=='publie') {
	$logo_statut = "puce-verte.gif";
}
else if ($statut_article=='prepa') {
	$logo_statut = "puce-blanche.gif";
}
else if ($statut_article=='prop') {
	$logo_statut = "puce-orange.gif";
}
else if ($statut_article == 'refuse') {
	$logo_statut = "puce-rouge.gif";
}
else if ($statut_article == 'poubelle') {
	$logo_statut = "puce-poubelle.gif";
}


echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'><td width='100%' valign='top'>";
if ($surtitre) {
	echo "<span $dir_lang><font face='arial,helvetica' size=3><b>";
	echo typo($surtitre);
	echo "</b></font></span>\n";
}
	gros_titre($titre, $logo_statut);

if ($soustitre) {
	echo "<span $dir_lang><font face='arial,helvetica' size=3><b>";
	echo typo($soustitre);
	echo "</b></font></span>\n";
}


if ($descriptif OR $url_site OR $nom_site) {
	echo "<p><div align='left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;' $dir_lang>";
	echo "<font size=2 face='Verdana,Arial,Sans,sans-serif'>";
	$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';
	$texte_case .= ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';
	echo propre($texte_case);
	echo "</font>";
	echo "</div>";
}
echo "</td></tr></table>";


//////////////////////////////////////////////////////
// Affichage des versions
//

debut_cadre_relief();

$query = "SELECT id_version, date, v.id_auteur, a.nom FROM spip_versions AS v, spip_auteurs AS a ".
	"WHERE id_article=$id_article AND v.id_auteur=a.id_auteur ORDER BY date";
$result = spip_query($query);

echo "<ul>";
while ($row = spip_fetch_array($result)) {
	echo "<li>\n";
	$date = affdate_heure($row['date']);
	if ($row['id_version'] != $id_version) {
		$link = new Link();
		$link->addVar('id_version', $row['id_version']);
		echo "<a href='".$link->getUrl()."'>$date</a>";
	}
	else {
		echo "<b>$date</b>";
	}
	echo " (".$row['nom'].")";
	echo " <span style='color:#989898; font-size: 80%; font-weight: bold;'><i>#".$row['id_version']."</i></span>";
	echo "</li>\n";
}
echo "</ul>\n";

fin_cadre_relief();


//////////////////////////////////////////////////////
// Corps de la version affichee
//

if ($id_version) {
	echo "\n\n<div align='justify'>";

	$champs = recuperer_version($id_article, $id_version);
	$chapo = $champs['chapo'];
	$texte = $champs['texte'];
	$ps = $champs['ps'];

	// pour l'affichage du virtuel
	unset($virtuel);
	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1);
	}
	
	if ($virtuel) {
		debut_boite_info();
		echo _T('info_renvoi_article')." ".propre("<center>[->$virtuel]</center>");
		fin_boite_info();
	}
	else {
		echo "<div $dir_lang><b>";
		$revision_nbsp = ($options == "avancees");	// a regler pour relecture des nbsp dans les articles
		echo justifier(propre($chapo));
		echo "</b></div>\n\n";
	
		echo "<div $dir_lang>";
		echo justifier(propre($texte));
		echo "</div>";
	
		if ($ps) {
			echo debut_cadre_enfonce();
			echo "<div $dir_lang><font size=2 face='Verdana,Arial,Sans,sans-serif'>";
			echo justifier("<b>"._T('info_ps')."</b> ".propre($ps));
			echo "</font></div>";
			echo fin_cadre_enfonce();
		}
		$revision_nbsp = false;
	
		if ($les_notes) {
			echo debut_cadre_relief();
			echo "<div $dir_lang><font size=2>";
			echo justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
			echo "</font></div>";
			echo fin_cadre_relief();
		}
	
		if ($champs_extra AND $extra) {
			include_ecrire("inc_extra.php3");
			extra_affichage($extra, "articles");
		}
	}
}

fin_cadre_relief();


fin_page();

?>

