<?php

include ("inc.php3");
include_ecrire ("inc_documents.php3");
include_ecrire ("inc_barre.php3");

$articles_surtitre = lire_meta("articles_surtitre");
$articles_soustitre = lire_meta("articles_soustitre");
$articles_descriptif = lire_meta("articles_descriptif");
$articles_urlref = lire_meta("articles_urlref");
$articles_chapeau = lire_meta("articles_chapeau");
$articles_ps = lire_meta("articles_ps");
$articles_redac = lire_meta("articles_redac");
$articles_mots = lire_meta("articles_mots");
$articles_modif = lire_meta("articles_modif");

// securite
$id_article = intval($id_article);
unset ($flag_editable);

//
// Creation de l'objet article
//

unset ($row);

if ($id_article) {
	spip_query("UPDATE spip_articles SET date_modif=NOW(), auteur_modif=$connect_id_auteur WHERE id_article=$id_article");
	$id_article_bloque = $id_article;	// message pour inc_presentation

	// Recuperer les donnees de l'article
	$query = "SELECT * FROM spip_articles WHERE id_article=$id_article";
	$result = spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$id_article = $row["id_article"];
		$titre = $row["titre"];

		$query = "SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur";
		$result_auteur = spip_query($query);
		$flag_auteur = (spip_num_rows($result_auteur) > 0);

		$flag_editable = (acces_rubrique($id_rubrique) OR ($flag_auteur > 0 AND ($statut == 'prepa' OR $statut == 'prop' OR $new == 'oui')));
	}
}
else if ($new=='oui') {
	$flag_editable = true;
	$titre = filtrer_entites(_T('info_nouvel_article'));
	$row_rub = spip_fetch_array(spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
	$id_secteur = $row_rub['id_secteur'];

	// creation d'une traduction : recuperer les elements du texte original si on a les droits
	if ($lier_trad = intval($lier_trad)) {
		$row_tmp = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article=$lier_trad"));
		if (($connect_statut == '0minirezo' AND acces_rubrique($row_tmp['id_rubrique']))
		OR ($row_tmp['statut'] == 'publie') OR ($row_tmp['statut'] == 'prop'))
			$row = $row_tmp;
	}
}

if ($row) {
	$surtitre = $row["surtitre"];
	$soustitre = $row["soustitre"];
	$id_rubrique = $row["id_rubrique"];
	$id_secteur = $row['id_secteur'];
	$descriptif = $row["descriptif"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$chapo = $row["chapo"];
	$texte = $row["texte"];
	$ps = $row["ps"];
	$date = $row["date"];
	$statut = $row['statut'];
	$date_redac = $row['date_redac'];
    	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})",$date_redac,$regs)){
	        $mois_redac = $regs[2];
	        $jour_redac = $regs[3];
	        $annee_redac = $regs[1];
	        if ($annee_redac > 4000) $annee_redac -= 9000;
	}
	$extra=$row["extra"];
}

if (!$flag_editable) {
	die ("<H3>"._T('info_acces_interdit')."</H3>");
}

if ($id_article && $id_document) {
	$query_doc = "SELECT * FROM spip_documents_articles WHERE id_document=$id_document AND id_article=$id_article";
	$result_doc = spip_query($query_doc);
	$flag_document_editable = (spip_num_rows($result_doc) > 0);
} else {
	$flag_document_editable = false;
}

$modif_document = $GLOBALS['modif_document'];
if ($modif_document == 'oui' AND $flag_document_editable) {
	$titre_document = addslashes(corriger_caracteres($titre_document));
	$descriptif_document = addslashes(corriger_caracteres($descriptif_document));
	$query = "UPDATE spip_documents SET titre=\"$titre_document\", descriptif=\"$descriptif_document\"";
	if ($largeur_document AND $hauteur_document) $query .= ", largeur='$largeur_document', hauteur='$hauteur_document'";
	$query .= " WHERE id_document=$id_document";
	spip_query($query);
}




//
// Gestion des textes trop longs (limitation brouteurs)
//

function coupe_trop_long($texte){	// utile pour les textes > 32ko
	if (strlen($texte) > 28*1024) {
		$texte = str_replace("\r\n","\n",$texte);
		$pos = strpos($texte, "\n\n\n", 28*1024);	// coupe para > 28 ko
		if ($pos > 0 and $pos < 32 * 1024) {
			$debut = substr($texte, 0, $pos)."\n\n\n<!--SPIP-->\n";
			$suite = substr($texte, $pos + 3);
		} else {
			$pos = strpos($texte, " ", 28*1024);	// sinon coupe espace
			if (!($pos > 0 and $pos < 32 * 1024))
				$pos = 28*1024;	// au pire
			$debut = substr($texte,0,$pos);
			$suite = substr($texte,$pos + 1);
		}
		return (array($debut,$suite));
	}
	else
		return (array($texte,''));
}


debut_page(_T('titre_page_articles_edit', array('titre' => $titre)), "documents", "articles");


debut_grand_cadre();

afficher_parents($id_rubrique);
	echo "<INPUT TYPE='hidden' NAME='id_rubrique_old' VALUE=\"$id_rubrique\" >";
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>"._T('lien_racine_site')."</B></A> ".aide ("rubhier")."<BR>".$parents;
$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();



debut_gauche();



//
// Pave "documents associes a l'article"
//

if ($new != 'oui'){
	afficher_documents_colonne($id_article, 'article', $flag_editable);
}

debut_droite();
debut_cadre_formulaire();


function mySel($varaut,$variable) {
	$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut) {
		$retour.= " SELECTED";
	}

	return $retour;
}



function my_sel($num,$tex,$comp){
	if ($num==$comp){
		echo "<OPTION VALUE='$num' SELECTED>$tex\n";
	}else{
		echo "<OPTION VALUE='$num'>$tex\n";
	}

}

function afficher_mois($mois){
	my_sel("01",_T('date_mois_1'),$mois);
	my_sel("02",_T('date_mois_2'),$mois);
	my_sel("03",_T('date_mois_3'),$mois);
	my_sel("04",_T('date_mois_4'),$mois);
	my_sel("05",_T('date_mois_5'),$mois);
	my_sel("06",_T('date_mois_6'),$mois);
	my_sel("07",_T('date_mois_7'),$mois);
	my_sel("08",_T('date_mois_8'),$mois);
	my_sel("09",_T('date_mois_9'),$mois);
	my_sel("10",_T('date_mois_10'),$mois);
	my_sel("11",_T('date_mois_11'),$mois);
	my_sel("12",_T('date_mois_12'),$mois);
}

function afficher_jour($jour){
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		my_sel($i,$aff,$jour);
	}
}


function enfant($leparent){
	global $id_parent;
	global $id_rubrique;
	static $i = 0, $premier = 1;
	global $statut;
	global $connect_toutes_rubriques;
	global $connect_id_rubriques;
	global $couleur_claire;

	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=spip_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);
		$statut_rubrique=$row['statut'];
		$lang_rub = $row['lang'];
		$langue_choisie_rub = $row['langue_choisie'];
		$style = "";

		// si l'article est publie il faut etre admin pour avoir le menu
		// sinon le menu est present en entier (proposer un article)
		if ($statut != "publie" OR acces_rubrique($my_rubrique)) {
			$rubrique_acceptable = true;
		} else {
			$rubrique_acceptable = false;
		}

		$espace="";
		for ($count=1;$count<$i;$count++){
			$espace.="&nbsp;&nbsp;&nbsp; ";
		}

		switch ($i) {
		case 1:
			$espace= "";
			$style .= "font-weight: bold;";
			break;
		case 2:
			$style .= "color: #202020;";
			break;
		case 3:
			$style .= "color: #404040;";
			break;
		case 4:
			$style .= "color: #606060;";
			break;
		case 5:
			$style .= "color: #808080;";
			break;
		default;
			$style .= "color: #A0A0A0;";
			break;
		}

		if ($rubrique_acceptable) {
			if ($i == 1 && !$premier) echo "<OPTION VALUE='$my_rubrique'>\n"; // sert a separer les secteurs
			$titre = couper($titre." ", 50); // largeur maxi
			if (lire_meta('multi_rubriques') == 'oui' AND ($langue_choisie_rub == "oui" OR $leparent == 0)) $titre = $titre." [".traduire_nom_langue($lang_rub)."]";
			echo "<OPTION".mySel($my_rubrique,$id_rubrique)." style=\"$style\">$espace$titre\n";
		}
		$premier = 0;
		enfant($my_rubrique);
	}
	$i=$i-1;
}


echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td>";
	if ($lier_trad) icone(_T('icone_retour'), "articles.php3?id_article=$lier_trad", "article-24.gif", "rien.gif");
	else icone(_T('icone_retour'), "articles.php3?id_article=$id_article", "article-24.gif", "rien.gif");

echo "</td>";
	echo "<td><img src='img_pack/rien.gif' width=10></td>\n";
echo "<td width='100%'>";
echo _T('texte_modifier_article');
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";

echo "<P><HR><P>";

	$titre = entites_html($titre);
	$soustitre = entites_html($soustitre);
	$surtitre = entites_html($surtitre);

	$descriptif = entites_html($descriptif);
	$nom_site = entites_html($nom_site);
	$url_site = entites_html($url_site);
	$chapo = entites_html($chapo);
	$texte = entites_html($texte);
	$ps = entites_html($ps);

	$lien = 'articles.php3';
	if ($id_article) $lien .= "?id_article=$id_article";
	echo "<FORM ACTION='$lien' METHOD='post' name='formulaire'>\n";

	if ($id_article)
		echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE='$id_article'>";
	else if ($new == 'oui')
		echo "<INPUT TYPE='Hidden' NAME='new' VALUE='oui'>";

	if ($lier_trad) echo "<INPUT TYPE='Hidden' NAME='lier_trad' VALUE='$lier_trad'>";

	if (($options == "avancees" AND $articles_surtitre != "non") OR $surtitre) {
		echo "<B>"._T('texte_sur_titre')."</B>";
		echo aide ("arttitre");
		echo "<BR><INPUT TYPE='text' NAME='surtitre' CLASS='forml' VALUE=\"$surtitre\" SIZE='40'><P>";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='surtitre' VALUE=\"$surtitre\" >";
	}

	echo _T('texte_titre_obligatoire');
	echo aide ("arttitre");
	echo "<BR><INPUT TYPE='text' NAME='titre' style='font-weight: bold;' CLASS='formo' VALUE=\"$titre\" SIZE='40'><P>";

	if (($articles_soustitre != "non") OR $soustitre) {
		echo "<B>"._T('texte_sous_titre')."</B>";
		echo aide ("arttitre");
		echo "<BR><INPUT TYPE='text' NAME='soustitre' CLASS='forml' VALUE=\"$soustitre\" SIZE='40'><br><br>";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='soustitre' VALUE=\"$soustitre\">";
	}


	/// Dans la rubrique....

	if ($id_rubrique == 0) $logo_parent = "racine-site-24.gif";
	else {
		$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'";
		$result=spip_query($query);
		while($row=spip_fetch_array($result)){
			$parent_parent=$row['id_parent'];
		}
		if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
		else $logo_parent = "rubrique-24.gif";
	}

	debut_cadre_relief("$logo_parent");
	echo "<B>"._T('titre_cadre_interieur_rubrique')."&nbsp;:</B>\n";
	echo aide ("artrub");
	echo "<BR><SELECT NAME='id_rubrique' style='background-color: $couleur_claire; font-size: 90%; width:100%; font-face:verdana,arial,helvetica,sans-serif;' SIZE=1>\n";
	enfant(0);
	echo "</SELECT><BR>\n";
	echo _T('texte_rappel_selection_champs');
	fin_cadre_relief();

	if (($options == "avancees" AND $articles_descriptif != "non") OR $descriptif) {
		echo "<P><B>"._T('texte_descriptif_rapide')."</B>";
		echo aide ("artdesc");
		echo "<BR>"._T('texte_contenu_article')."<BR>";
		echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='2' COLS='40' wrap=soft>";
		echo $descriptif;
		echo "</TEXTAREA><P>\n";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='descriptif' VALUE=\"$descriptif\">";
	}

	if (($options == "avancees" AND $articles_urlref != "non") OR $nom_site OR $url_site) {
		echo _T('entree_liens_sites')."<br />\n";
		echo _T('info_titre')." ";
		echo "<input type='text' name='nom_site' class='forml' width='40' value=\"$nom_site\"/><br />\n";
		echo _T('info_url')." ";
		echo "<input type='text' name='url_site' class='forml' width='40' value=\"$url_site\"/>";
	}

	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1);
		$chapo = "";
	}

	if ($connect_statut=="0minirezo" AND $virtuel){
		echo "<p><div style='border: 1px dashed #666666; background-color: #f0f0f0; padding: 5px;'>";
		echo "<table width=100% cellspacing=0 cellpadding=0 border=0>";
		echo "<tr><td valign='top'>";
		echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
		echo "<B><label for='confirme-virtuel'>"._T('info_redirection')."&nbsp;:</label></B>";
		echo aide ("artvirt");
		echo "</font>";
		echo "</td>";
		echo "<td width=10>&nbsp;</td>";
		echo "<td valign='top' width='50%'>";
		if (!$virtuel) $virtuel = "http://";
		echo "<INPUT TYPE='text' NAME='virtuel' CLASS='forml' style='font-size:9px;' VALUE=\"$virtuel\" SIZE='40'>";
		echo "<input type='hidden' name='changer_virtuel' value='oui'>";
		echo "</td></tr></table>\n";
		echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
		echo _T('texte_article_virtuel_reference');
		echo "</font>";
		echo "</div><p>\n";
	}

	else {
		echo "<HR>";

		if (($articles_chapeau != "non") OR $chapo) {
			if ($spip_ecran == "large") $rows = 8;
			else $rows = 5;
			echo "<B>"._T('info_chapeau')."</B>";
			echo aide ("artchap");
			echo "<BR>"._T('texte_introductif_article')."<BR>";
			echo "<TEXTAREA NAME='chapo' CLASS='forml' ROWS='$rows' COLS='40' wrap=soft>";
			echo $chapo;
			echo "</TEXTAREA><P>\n";
		}
		else {
			echo "<INPUT TYPE='hidden' NAME='chapo' VALUE=\"$chapo\">";
		}

	}

	if ($spip_ecran == "large") $rows = 28;
	else $rows = 20;

	if (strlen($texte)>29*1024) // texte > 32 ko -> decouper en morceaux
	{
		$textes_supplement = "<br><font color='red'>"._T('info_texte_long')."</font>\n";
		while (strlen($texte)>29*1024)
		{
			$nombre_textes ++;
			list($texte1,$texte) = coupe_trop_long($texte);

			$textes_supplement .= "<BR>";
			$textes_supplement .= afficher_barre('formulaire', 'texte'.$nombre_textes);
			$textes_supplement .= "<TEXTAREA NAME='texte$nombre_textes'".
				" CLASS='formo' ".afficher_claret()." ROWS='$rows' COLS='40' wrap=soft>" .
				$texte1 . "</TEXTAREA><P>\n";
		}
	}
	echo "<B>"._T('info_texte')."</B>";
	echo aide ("arttexte");
	echo "<br>"._T('texte_enrichir_mise_a_jour');
	echo aide("raccourcis");

	echo $textes_supplement;

	echo "<BR>";
	echo afficher_barre('formulaire', 'texte');
	echo "<TEXTAREA NAME='texte' ".afficher_claret()." CLASS='formo' ROWS='$rows' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA><P>\n";

	if (($articles_ps != "non" AND $options == "avancees") OR $ps) {
		echo "<B>"._T('info_post_scriptum')."</B><BR>";
		echo "<TEXTAREA NAME='ps' CLASS='forml' ROWS='5' COLS='40' wrap=soft>";
		echo $ps;
		echo "</TEXTAREA><P>\n";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='ps' VALUE=\"$ps\">";
	}

	if ($champs_extra) {
		include_ecrire("inc_extra.php3");
		extra_saisie($extra, 'articles', $id_secteur);
	}

	if ($date)
		echo "<INPUT TYPE='Hidden' NAME='date' VALUE=\"$date\" SIZE='40'><P>";

	if ($new == "oui")
		echo "<INPUT TYPE='Hidden' NAME='statut_nouv' VALUE=\"prepa\" SIZE='40'><P>";

	echo "<DIV ALIGN='right'>";
	echo "<INPUT CLASS='fondo' TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."'>";
	echo "</DIV></FORM>";

fin_cadre_formulaire();

fin_page();

?>
