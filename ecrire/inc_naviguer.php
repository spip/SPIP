<?php

function infos_naviguer($id_rubrique, $statut)
{

	if ($id_rubrique > 0) {
		debut_boite_info();
		echo "<CENTER>";
		echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('titre_numero_rubrique')."</B></FONT>";
		echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$id_rubrique</B></FONT>";
		echo "</CENTER>";
	
		voir_en_ligne ('rubrique', $id_rubrique, $statut);
	
		fin_boite_info();
	}
}

function logo_naviguer($id_rubrique)
{
	global $connect_statut;
	if ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique)) {
		if ($id_rubrique)
			afficher_boite_logo('rub', 'id_rubrique', $id_rubrique,
			_T('logo_rubrique')." ".aide ("rublogo"), _T('logo_survol'));
		else
			afficher_boite_logo('rub', 'id_rubrique', 0,
			_T('logo_standard_rubrique')." ".aide ("rublogo"),
			_T('logo_survol'));
	}
	
}

function raccourcis_naviguer($id_rubrique, $id_parent)
{
	global $connect_statut;

	debut_raccourcis();
	
	icone_horizontale(_T('icone_tous_articles'), "articles_page.php3", "article-24.gif");
	
	if (spip_num_rows(spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1 OFFSET 0")) > 0) {
		if ($id_rubrique > 0)
			icone_horizontale(_T('icone_ecrire_article'), "articles_edit.php3?id_rubrique=$id_rubrique&new=oui", "article-24.gif","creer.gif");
	
		$activer_breves = lire_meta("activer_breves");
		if ($activer_breves != "non" AND $id_parent == "0" AND $id_rubrique != "0") {
			icone_horizontale(_T('icone_nouvelle_breve'), "breves_edit.php3?id_rubrique=$id_rubrique&new=oui", "breve-24.gif","creer.gif");
		}
	}
	else {
		if ($connect_statut == '0minirezo') {
			echo "<p>"._T('info_creation_rubrique');
		}
	}
	
	fin_raccourcis();
}

function langue_naviguer($id_rubrique, $id_parent, $flag_editable)
{

if ($id_rubrique>0 AND lire_meta('multi_rubriques') == 'oui' AND (lire_meta('multi_secteurs') == 'non' OR $id_parent == 0) AND $flag_editable) {

	$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
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
}

function contenu_naviguer($id_rubrique, $id_parent, $ze_logo,$flag_editable) {

global $clean_link, $connect_statut, $connect_toutes_rubriques, $options, $spip_lang_left, $spip_lang_right;

///// Afficher les rubriques 
afficher_enfant_rub($id_rubrique, $flag_editable);


//echo "<div align='$spip_lang_left'>";


//////////  Vos articles en cours de redaction
/////////////////////////

echo "<P>";


//
// Verifier les boucles a mettre en relief
//

$relief = false;

if (!$relief) {
	$query = "SELECT id_article FROM spip_articles AS articles WHERE id_rubrique='$id_rubrique' AND statut='prop' LIMIT 1 OFFSET 0";
	$result = spip_query($query);
	$relief = (spip_num_rows($result) > 0);
}

if (!$relief) {
	$query = "SELECT id_breve FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='prepa' OR statut='prop') LIMIT 1 OFFSET 0";
	$result = spip_query($query);
	$relief = (spip_num_rows($result) > 0);
}

if (!$relief AND lire_meta('activer_syndic') != 'non') {
	$query = "SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND statut='prop' LIMIT 1 OFFSET 0";
	$result = spip_query($query);
	$relief = (spip_num_rows($result) > 0);
}

if (!$relief AND lire_meta('activer_syndic') != 'non' AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
	$query = "SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (syndication='off' OR syndication='sus') LIMIT 1 OFFSET 0";
	$result = spip_query($query);
	$relief = (spip_num_rows($result) > 0);
}


if ($relief) {
	echo "<p>";
	debut_cadre_couleur();
	echo "<div class='verdana2' style='color: black;'><b>"._T('texte_en_cours_validation')."</b></div><p>";

	//
	// Les articles a valider
	//
	afficher_articles(_T('info_articles_proposes'),
		"WHERE id_rubrique='$id_rubrique' AND statut='prop' ORDER BY date DESC");

	//
	// Les breves a valider
	//
	$query = "SELECT * FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='prepa' OR statut='prop') ORDER BY date_heure DESC";
	afficher_breves(_T('info_breves_valider'), $query, true);

	//
	// Les sites references a valider
	//
	if (lire_meta('activer_syndic') != 'non') {
		include_ecrire("inc_sites.php3");
		afficher_sites(_T('info_site_valider'), "SELECT * FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND statut='prop' ORDER BY nom_site");
	}

	//
	// Les sites a probleme
	//
	if (lire_meta('activer_syndic') != 'non' AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		include_ecrire("inc_sites.php3");
		afficher_sites(_T('avis_sites_syndiques_probleme'),
			"SELECT * FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (syndication='off' OR syndication='sus') AND statut='publie' ORDER BY nom_site");
	}

	// Les articles syndiques en attente de validation
	if ($id_rubrique == 0 AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		$result = spip_query ("SELECT COUNT(*) AS compte FROM spip_syndic_articles AND statut='dispo'");
		if (($row = spip_fetch_array($result)) AND $row['compte'])
			echo "<br><small><a href='sites_tous.php3'>".$row['compte']." "._T('info_liens_syndiques_1')."</a> "._T('info_liens_syndiques_2')."</small>";
	}

	fin_cadre_couleur();
}


//////////  Les articles en cours de redaction
/////////////////////////

if ($connect_statut == "0minirezo" AND $options == 'avancees') {
	afficher_articles(_T('info_tous_articles_en_redaction'),
		"WHERE statut='prepa' AND id_rubrique='$id_rubrique' ORDER BY date DESC");
}


//////////  Les articles publies
/////////////////////////

afficher_articles(_T('info_tous_articles_presents'),
	"WHERE statut='publie' AND id_rubrique='$id_rubrique' ORDER BY date DESC", true);



if ($id_rubrique > 0){
	echo "<div align='$spip_lang_right'>";
	icone(_T('icone_ecrire_article'), "articles_edit.php3?id_rubrique=$id_rubrique&new=oui", "article-24.gif", "creer.gif");
	echo "</div><p>";
}

//// Les breves

afficher_breves(_T('icone_ecrire_nouvel_article'), "SELECT * FROM spip_breves WHERE id_rubrique='$id_rubrique' AND statut != 'prop' AND statut != 'prepa' ORDER BY date_heure DESC");

$activer_breves=lire_meta("activer_breves");

if ($id_parent == "0" AND $id_rubrique != "0" AND $activer_breves!="non"){
	echo "<div align='$spip_lang_right'>";
	icone(_T('icone_nouvelle_breve'), "breves_edit.php3?id_rubrique=$id_rubrique&new=oui", "breve-24.gif", "creer.gif");
	echo "</div><p>";
}



//// Les sites references

if (lire_meta("activer_sites") == 'oui') {
	include_ecrire("inc_sites.php3");
	afficher_sites(_T('titre_sites_references_rubrique'), "SELECT * FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND statut!='refuse' AND statut != 'prop' AND syndication NOT IN ('off','sus') ORDER BY nom_site");

	$proposer_sites=lire_meta("proposer_sites");
	if ($id_rubrique > 0 AND ($flag_editable OR $proposer_sites > 0)) {
		$link = new Link('sites_edit.php3');
		$link->addVar('id_rubrique', $id_rubrique);
		$link->addVar('target', 'sites.php3');
		$link->addVar('redirect', $clean_link->getUrl());
	
		echo "<div align='$spip_lang_right'>";
		icone(_T('info_sites_referencer'), $link->getUrl(), "site-24.gif", "creer.gif");
		echo "</div><p>";
	}
 }
}

?>
