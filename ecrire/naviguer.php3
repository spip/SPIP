<?php

include ("inc.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_documents.php3");


$coll = intval($coll);
$flag_mots = lire_meta("articles_mots");

function enfant($collection){
	global $les_enfants, $couleur_foncee, $lang_dir;
	$query2 = "SELECT * FROM spip_rubriques WHERE id_parent=\"$collection\" ORDER BY titre";
	$result2 = spip_query($query2);

	while($row=spip_fetch_array($result2)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=$row['titre'];

		$bouton_layer = bouton_block_invisible("enfants$id_rubrique");
		$les_sous_enfants = sous_enfant($id_rubrique);

		changer_typo($row['lang']);
		$descriptif=propre($row['descriptif']);

		$les_enfants.= "<P>";
		if ($id_parent == "0") $les_enfants .= debut_cadre_relief("secteur-24.gif", true);
		else  $les_enfants .= debut_cadre_relief("rubrique-24.gif", true);
		if (strlen($les_sous_enfants) > 0){
			$les_enfants .= $bouton_layer;
		}
		$les_enfants .= "<FONT FACE=\"Verdana,Arial,Sans,sans-serif\">";

		if (acces_restreint_rubrique($id_rubrique))
			$les_enfants .= "<img src='img_pack/admin-12.gif' alt='' width='12' height='12' title='"._T('image_administrer_rubrique')."'> ";

		$les_enfants.= "<span dir='$lang_dir'><B><A HREF='naviguer.php3?coll=$id_rubrique'><font color='$couleur_foncee'>".typo($titre)."</font></A></B></span>";
		if (strlen($descriptif)>1) {
			$les_enfants .= "<br><FONT SIZE=1><span dir='$lang_dir'>$descriptif</span></FONT>";
		}

		$les_enfants.= "</FONT>";

		$les_enfants .= $les_sous_enfants;
		$les_enfants .= fin_cadre_relief(true);
	}
}

function sous_enfant($collection2){
	global $lang_dir, $spip_lang_dir;
	$query3 = "SELECT * FROM spip_rubriques WHERE id_parent=\"$collection2\" ORDER BY titre";
	$result3 = spip_query($query3);

	if (spip_num_rows($result3) > 0){
		$retour = debut_block_invisible("enfants$collection2")."\n<ul style='list-style-image: url(img_pack/rubrique-12.gif)'>\n<FONT SIZE=1 face='arial,helvetica,sans-serif'>";
		while($row=spip_fetch_array($result3)){
			$id_rubrique2=$row['id_rubrique'];
			$id_parent2=$row['id_parent'];
			$titre2=$row['titre'];
			changer_typo($row['lang']);

			$retour.="<LI><A HREF='naviguer.php3?coll=$id_rubrique2'><span dir='$lang_dir'>".typo($titre2)."</span></A>\n";
		}
		$retour .= "</FONT></ul>\n\n".fin_block()."\n\n";
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
	my_sel("00",_T('mois_non_connu'),$mois);
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

function afficher_annee($annee){
	// Cette ligne permettrait de faire des articles sans date de publication
	// my_sel("0000","n.c.",$annee); 

	if($annee<1996 AND $annee <> 0){
		echo "<OPTION VALUE='$annee' SELECTED>$annee\n";
	}
	for($i=1996;$i<date(Y)+2;$i++){
		my_sel($i,$i,$annee);
	}
}

function afficher_jour($jour){
	my_sel("00",_T('jour_non_connu_nc'),$jour);
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		my_sel($i,$aff,$jour);
	}
}


//
// Gerer les modifications...
//

$id_parent = intval($id_parent);
$coll = intval($coll);
$flag_editable = ($connect_statut == '0minirezo' AND (acces_rubrique($id_parent) OR acces_rubrique($coll))); // id_parent necessaire en cas de creation de sous-rubrique

if ($modifier_rubrique == "oui") {
	calculer_rubriques();
}

if ($titre) {
	// creation, le cas echeant
	if ($new == 'oui' AND $flag_editable AND !$coll) {
		$query = "INSERT INTO spip_rubriques (titre, id_parent) VALUES ('"._T('item_nouvelle_rubrique')."', '$id_parent')";
		$result = spip_query($query);
		$coll = spip_insert_id();
		$clean_link->AddVar('coll', $coll);
	}

	// si c'est une rubrique-secteur contenant des breves, ne deplacer
	// que si $confirme_deplace == 'oui'
	$query = "SELECT COUNT(*) AS cnt FROM spip_breves WHERE id_rubrique=\"$coll\"";
	$row = spip_fetch_array(spip_query($query));
	if (($row['cnt'] > 0) and !($confirme_deplace == 'oui')) {
		$id_parent = 0;
	}

	// verifier qu'on envoie bien dans une rubrique autorisee
	if (acces_rubrique($id_parent)) {
		$change_parent = "id_parent=\"$id_parent\",";
	}
	else {
		$change_parent = "";
	}

	$titre = addslashes($titre);
	$descriptif = addslashes($descriptif);
	$texte = addslashes($texte);

	if ($champs_extra) {
		include_ecrire("inc_extra.php3");
		$add_extra = ", extra = '".addslashes(extra_recup_saisie("rubriques"))."'";
	} else
		$add_extra = '';

	if ($flag_editable) {
		$query = "UPDATE spip_rubriques SET $change_parent titre=\"$titre\", descriptif=\"$descriptif\", texte=\"$texte\" $add_extra WHERE id_rubrique=$coll";
		$result = spip_query($query);
	}

	calculer_rubriques();
	calculer_langues_rubriques();

	if (lire_meta('activer_moteur') == 'oui') {
		include_ecrire ("inc_index.php3");
		indexer_rubrique($coll);
	}
}

//
// Appliquer le changement de langue
//
if ($changer_lang AND $coll>0 AND lire_meta('multi_rubriques') == 'oui' AND (lire_meta('multi_secteurs') == 'non' OR $id_parent == 0) AND $flag_editable) {
	if ($changer_lang != "herit")
		spip_query("UPDATE spip_rubriques SET lang='".addslashes($changer_lang)."', langue_choisie='oui' WHERE id_rubrique=$coll");
	else {
		if ($id_parent == 0)
			$langue_parent = lire_meta('langue_site');
		else {
			$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_parent"));
			$langue_parent = $row['lang'];
		}
		spip_query("UPDATE spip_rubriques SET lang='".addslashes($langue_parent)."', langue_choisie='non' WHERE id_rubrique=$coll");
	}
	calculer_langues_rubriques();
}

//
// infos sur cette rubrique
//

$query="SELECT * FROM spip_rubriques WHERE id_rubrique='$coll'";
$result=spip_query($query);

while($row=spip_fetch_array($result)){
	$id_rubrique=$row['id_rubrique'];
	$id_parent=$row['id_parent'];
	$titre=$row['titre'];
	$descriptif=$row['descriptif'];
	$texte=$row['texte'];
	$statut = $row['statut'];
	$extra = $row["extra"];
	$langue_rubrique = $row['lang'];
}

if ($titre)
	$titre_page = "&laquo; ".textebrut($titre)." &raquo;";
else
	$titre_page = _T('titre_naviguer_dans_le_site');


if ($id_document) {
	$query_doc = "SELECT * FROM spip_documents_rubriques WHERE id_document=$id_document AND id_rubrique=$coll";
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

	if ($jour_doc AND $connect_statut == '0minirezo' AND acces_rubrique($coll)) {
		if ($annee_doc == "0000") $mois_doc = "00";
		if ($mois_doc == "00") $jour_doc = "00";
		$query = "UPDATE spip_documents SET date='$annee_doc-$mois_doc-$jour_doc' WHERE id_document=$id_document";
		$result = spip_query($query);
		calculer_rubriques();
	}

}


///// debut de la page
debut_page($titre_page, "documents", "rubriques");


//////// parents


debut_grand_cadre();

if ($coll  > 0) {
	afficher_parents($id_parent);
	$parents="~ <IMG SRC='img_pack/racine-site-24.gif' WIDTH=24 HEIGHT=24 align='middle'> <A HREF='naviguer.php3?coll=0'><b><font color='$couleur_foncee'>"._T('lien_racine_site')."</font></b></A> ".aide ("rubhier")."<BR>".$parents;

	$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
	$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

	echo "$parents";
}

fin_grand_cadre();

changer_typo('', 'rubrique'.$coll);


debut_gauche();

if ($coll > 0) {
	debut_boite_info();
	echo "<CENTER>";
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('titre_numero_rubrique')."</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$coll</B></FONT>";
	echo "</CENTER>";

	if ($coll > 0 AND $statut == 'publie') {
		icone_horizontale(_T('icone_voir_en_ligne'), "../spip_redirect.php3?id_rubrique=$coll&recalcul=oui", "racine-24.gif", "rien.gif");
	}

	fin_boite_info();
}

//
// Logos de la rubrique
//

$rubon = "rubon$coll";
$ruboff = "ruboff$coll";

if ($connect_statut == '0minirezo' AND acces_rubrique($coll)) {
	if ($coll > 0)
		afficher_boite_logo($rubon, $ruboff, _T('logo_rubrique')." ".aide ("rublogo"), _T('logo_survol'));
	else
		afficher_boite_logo($rubon, $ruboff, _T('logo_standard_rubrique')." ".aide ("rublogo"), _T('logo_survol'));
}


//
// Afficher les boutons de creation d'article et de breve
//
debut_raccourcis();

$query = "SELECT id_rubrique FROM spip_rubriques LIMIT 0,1";
$result = spip_query($query);

icone_horizontale(_T('icone_tous_articles'), "articles_page.php3", "article-24.gif");

if (spip_num_rows($result) > 0) {
	if ($coll > 0)
		icone_horizontale(_T('icone_ecrire_article'), "articles_edit.php3?id_rubrique=$coll&new=oui", "article-24.gif","creer.gif");

	$activer_breves = lire_meta("activer_breves");
	if ($activer_breves != "non" AND $id_parent == "0" AND $coll != "0") {
		icone_horizontale(_T('icone_nouvelle_breve'), "breves_edit.php3?id_rubrique=$coll&new=oui", "breve-24.gif","creer.gif");
	}
}
else {
	if ($connect_statut == '0minirezo') {
		echo "<p>"._T('info_creation_rubrique');
	}
}

fin_raccourcis();



debut_droite();


if ($coll == 0) {
	$nom_site = lire_meta("nom_site");
	$titre = _T('info_racine_site').": ".$nom_site;
}

if ($coll ==  0) $ze_logo = "racine-site-24.gif";
else if ($id_parent == 0) $ze_logo = "secteur-24.gif";
else $ze_logo = "rubrique-24.gif";


debut_cadre_relief($ze_logo);

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'><td width='100%' valign='top'>";
if (acces_restreint_rubrique($id_rubrique))
	$fleche = "<img src='img_pack/admin-12.gif' alt='' width='12' height='12' title='"._T('info_administrer_rubrique')."'> ";
gros_titre($fleche.$titre);
echo "</td>";

if ($coll > 0 AND $flag_editable) {
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";
	echo "<td  align='right' valign='top'>";
	icone(_T('icone_modifier_rubrique'), "rubriques_edit.php3?id_rubrique=$id_rubrique&retour=nav", $ze_logo, "edit.gif");
	echo "</td>";
}
echo "</tr>\n";

if (strlen($descriptif) > 1) {
	echo "<tr><td>\n";
	echo "<div align='$spip_lang_left' style='padding: 5px; border: 1px dashed #aaaaaa;'>";
	echo "<font size=2 face='Verdana,Arial,Sans,sans-serif'>";
	echo propre($descriptif."~");
	echo "</font>";
	echo "</div></td></tr>\n";
}

echo "</table>\n";

	if ($champs_extra AND $extra) {
		include_ecrire("inc_extra.php3");
		extra_affichage($extra, "rubriques");
	}


/// Mots-cles
if ($flag_mots!= 'non' AND $flag_editable AND $options == 'avancees' AND $coll > 0) {
	echo "\n<p>";
	formulaire_mots('rubriques', $coll, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}


if (strlen($texte) > 1) {
	echo "\n<p><div align='justify'><font size=3 face='Verdana,Arial,Sans,sans-serif'>";
	echo justifier(propre($texte));
	echo "&nbsp;</font></div>";
}


//
// Langue de la rubrique
//
if ($coll>0 AND lire_meta('multi_rubriques') == 'oui' AND (lire_meta('multi_secteurs') == 'non' OR $id_parent == 0) AND $flag_editable) {

	$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_rubriques WHERE id_rubrique=$coll"));
	$langue_rubrique = $row['lang'];
	$langue_choisie_rubrique = $row['langue_choisie'];
	if ($id_parent) {
		$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_parent"));
		$langue_parent = $row[0];
	}
	else $langue_parent = lire_meta('langue_site');

	debut_cadre_enfonce('langues-24.gif');
	echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC' class='serif2'>";
	echo bouton_block_invisible('languesrubrique');
	echo "<B>";
	echo _T('titre_langue_rubrique');
	echo "&nbsp; (".traduire_nom_langue($langue_rubrique).")";
	echo "</B>";
	echo "</TD></TR></TABLE>";

	echo debut_block_invisible('languesrubrique');
	echo "<div class='verdana2' align='center'>";
	echo menu_langues('changer_lang', $langue_rubrique, '', $langue_parent);
	echo "</div>\n";
	echo fin_block();

	fin_cadre_enfonce();
}


fin_cadre_relief();


//echo "<div align='$spip_lang_left'>";
enfant($coll);


$les_enfants2=substr($les_enfants,round(strlen($les_enfants)/2),strlen($les_enfants));
if (strpos($les_enfants2,"<P>")){
	$les_enfants2=substr($les_enfants2,strpos($les_enfants2,"<P>"),strlen($les_enfants2));
	$les_enfants1=substr($les_enfants,0,strlen($les_enfants)-strlen($les_enfants2));
}else{
	$les_enfants1=$les_enfants;
	$les_enfants2="";
}


// Afficher les sous-rubriques
echo "<p><table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr><td valign='top' width=50% rowspan=2>$les_enfants1</td>";
echo "<td width=20 rowspan=2><img src='img_pack/rien.gif' width=20></td>";
echo "<td valign='top' width=50%>$les_enfants2 &nbsp;";
if (strlen($les_enfants2) > 0) echo "<p>";
echo "</td></tr>";

echo "<tr><td style='text-align: right;' valign='bottom'><div align='right'>";
if ($flag_editable) {
	if ($coll == "0") icone(_T('icone_creer_rubrique'), "rubriques_edit.php3?new=oui&retour=nav", "secteur-24.gif", "creer.gif");
	else  icone(_T('icone_creer_sous_rubrique'), "rubriques_edit.php3?new=oui&retour=nav&id_parent=$coll", "rubrique-24.gif", "creer.gif");
	echo "<p>";
}
echo "</div></td></tr>";
echo "</table>";


//echo "<div align='$spip_lang_left'>";


//////////  Vos articles en cours de redaction
/////////////////////////

echo "<P>";
afficher_articles(_T('info_en_cours_validation'),
	", spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND id_rubrique='$coll' ".
	"AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut=\"prepa\" ORDER BY articles.date DESC");


//////////  Les articles a valider
/////////////////////////

afficher_articles(_T('info_articles_a_valider'),
	"WHERE statut=\"prop\" AND id_rubrique='$coll' ORDER BY date DESC");


//////////  Les articles en cours de redaction
/////////////////////////

if ($connect_statut == "0minirezo" AND $options == 'avancees') {
	afficher_articles(_T('info_tous_articles_en_redaction'),
		"WHERE statut=\"prepa\" AND id_rubrique='$coll' ORDER BY date DESC");
}


//////////  Les articles publies
/////////////////////////

afficher_articles(_T('info_tous_articles_presents'),
	"WHERE statut=\"publie\" AND id_rubrique='$coll' ORDER BY date DESC");

if ($coll > 0){
	echo "<div align='right'>";
	icone(_T('icone_ecrire_article'), "articles_edit.php3?id_rubrique=$coll&new=oui", "article-24.gif", "creer.gif");
	echo "</div><p>";
}

//// Les breves

afficher_breves(_T('icone_ecrire_nouvel_article'), "SELECT * FROM spip_breves WHERE id_rubrique='$coll' ORDER BY date_heure DESC");

$activer_breves=lire_meta("activer_breves");

if ($id_parent == "0" AND $coll != "0" AND $activer_breves!="non"){
	echo "<div align='right'>";
	icone(_T('icone_nouvelle_breve'), "breves_edit.php3?id_rubrique=$coll&new=oui", "breve-24.gif", "creer.gif");
	echo "</div><p>";
}



//// Les sites references

if (lire_meta("activer_sites") == 'oui') {
	include_ecrire("inc_sites.php3");
	afficher_sites(_T('titre_sites_references_rubrique'), "SELECT * FROM spip_syndic WHERE id_rubrique='$coll' AND statut!='refuse' ORDER BY nom_site");

	$proposer_sites=lire_meta("proposer_sites");
	if ($coll > 0 AND ($flag_editable OR $proposer_sites > 0)) {
		$link = new Link('sites_edit.php3');
		$link->addVar('id_rubrique', $coll);
		$link->addVar('target', 'sites.php3');
		$link->addVar('redirect', $clean_link->getUrl());
	
		echo "<div align='right'>";
		icone(_T('info_sites_referencer'), $link->getUrl(), "site-24.gif", "creer.gif");
		echo "</div><p>";
	}
}

/// Documents associes a la rubrique

if ($coll>0)
	 afficher_documents_non_inclus($coll, "rubrique", $flag_editable);




////// Supprimer cette rubrique (si vide)

if (($coll>0) AND tester_rubrique_vide($coll) AND $flag_editable) {
	$link = new Link('naviguer.php3');
	$link->addVar('coll', $id_parent);
	$link->addVar('supp_rubrique', $coll);

	echo "<p><div align='center'>";
	icone(_T('icone_supprimer_rubrique'), $link->getUrl(), "$ze_logo", "supprimer.gif");
	echo "</div><p>";


}



fin_page();

?>
