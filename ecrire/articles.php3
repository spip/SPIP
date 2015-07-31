<?php

include ("inc.php3");

include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_documents.php3");

$articles_surtitre = lire_meta("articles_surtitre");
$articles_soustitre = lire_meta("articles_soustitre");
$articles_descriptif = lire_meta("articles_descriptif");
$articles_chapeau = lire_meta("articles_chapeau");
$articles_ps = lire_meta("articles_ps");
$articles_redac = lire_meta("articles_redac");
$articles_mots = lire_meta("articles_mots");

if ($id_article==0) {
	if ($new=='oui') {
		if ($titre=='') $titre = _T('info_sans_titre');
		$forums_publics = substr(lire_meta('forums_publics'),0,3);
		spip_query("INSERT INTO spip_articles (id_rubrique, statut, date, accepter_forum) VALUES ($id_rubrique, 'prepa', NOW(), '$forums_publics')");
		$id_article = spip_insert_id();
		spip_query("DELETE FROM spip_auteurs_articles WHERE id_article = $id_article");
		spip_query("INSERT INTO spip_auteurs_articles (id_auteur, id_article) VALUES ($connect_id_auteur, $id_article)");
	} else {
		@header("Location: ./index.php3");
		exit;
	}
}

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



/// En double avec articles_edit.php3, mais necessite le flag_editable
$modif_document = $GLOBALS['modif_document'];
if ($modif_document == 'oui' AND $flag_editable) {
	$titre_document = addslashes(corriger_caracteres($titre_document));
	$descriptif_document = addslashes(corriger_caracteres($descriptif_document));
	$query = "UPDATE spip_documents SET titre=\"$titre_document\", descriptif=\"$descriptif_document\"";
	if ($largeur_document AND $hauteur_document) $query .= ", largeur='$largeur_document', hauteur='$hauteur_document'";
	$query .= " WHERE id_document=$id_document";
	spip_query($query);
}


//
// Appliquer les modifications
//

$suivi_edito = lire_meta("suivi_edito");
$reindexer = false;

$ok_nouveau_statut = false;
if ($statut_nouv) {
	if (acces_rubrique($rubrique_article)) $ok_nouveau_statut = true;
	else if ($flag_auteur) {
		if ($statut_nouv == 'prop' AND $statut_article == 'prepa')
			$ok_nouveau_statut = true;
		else if ($statut_nouv == 'prepa' AND $statut_article == 'poubelle')
			$ok_nouveau_statut = true;
	}
	if ($ok_nouveau_statut) {
		$query = "UPDATE spip_articles SET statut='$statut_nouv' WHERE id_article=$id_article";
		$result = spip_query($query);

		if ($statut_nouv == 'publie' AND $statut_nouv != $statut_article)
			spip_query("UPDATE spip_articles SET date=NOW() WHERE id_article=$id_article");

		$statut_ancien = $statut_article;	// message pour les traitements de fond (indexation ; envoi mail)
		$statut_article = $statut_nouv;
		$flag_editable = (acces_rubrique($rubrique_article)
			OR ($flag_auteur AND ($statut_article == 'prepa' OR $statut_article == 'prop')));
	}
}

if ($jour && $flag_editable) {
	if ($annee == "0000") $mois = "00";
	if ($mois == "00") $jour = "00";
	$query = "UPDATE spip_articles SET date='$annee-$mois-$jour' WHERE id_article=$id_article";
	$result = spip_query($query);
	if (lire_meta("post_dates") == 'non')
		calculer_rubriques();
	else
		calculer_dates_rubriques();
}

if ($jour_redac && $flag_editable) {
	if ($annee_redac < 1001) $annee_redac += 9000;

	if ($mois_redac == "00") $jour_redac = "00";

	if ($avec_redac=="non"){
		$annee_redac = '0000';
		$mois_redac = '00';
		$jour_redac = '00';
	}

	$query = "UPDATE spip_articles SET date_redac='$annee_redac-$mois_redac-$jour_redac' WHERE id_article=$id_article";
	$result = spip_query($query);
}


// Passer les images/docs en "inclus=non"
$query = "SELECT docs.id_document FROM spip_documents AS docs, spip_documents_articles AS lien WHERE lien.id_article=$id_article AND lien.id_document=docs.id_document";
$result = spip_query($query);

while($row=spip_fetch_array($result)){
	$ze_doc[]=$row['id_document'];
}

if (count($ze_doc)>0){
	$ze_docs = join($ze_doc,",");
	spip_query("UPDATE spip_documents SET inclus='non' WHERE id_document IN ($ze_docs)");
}


//
// Reunit les textes decoupes parce que trop longs
//

$nb_texte = 0;
while ($nb_texte ++ < 100){		// 100 pour eviter une improbable boucle infinie
	$varname = "texte$nb_texte";
	$texte_plus = $$varname;	// double $ pour obtenir $texte1, $texte2...
	if ($texte_plus){
		$texte_plus = ereg_replace("<!--SPIP-->[\n\r]*","\n\n\n",$texte_plus);
		$texte_ajout .= " ".$texte_plus;
	} else {
		break;
	}
}
$texte = $texte_ajout . $texte;

// preparer le virtuel

if ($changer_virtuel && $flag_editable) {
	if (!ereg("^(https?|ftp|mailto)://.+", trim($virtuel))) $virtuel = "";
	if ($virtuel) $chapo = "=$virtuel";
	else $chapo = "";
	$query = "UPDATE spip_articles SET chapo=\"$chapo\" WHERE id_article=$id_article";
	$result = spip_query($query);
}


if ($titre && !$ajout_forum && $flag_editable) {
	$surtitre = addslashes(corriger_caracteres($surtitre));
	$titre = addslashes(corriger_caracteres($titre));
	$soustitre = addslashes(corriger_caracteres($soustitre));
	$descriptif = addslashes(corriger_caracteres($descriptif));
	$chapo = addslashes(corriger_caracteres($chapo));
	$texte = addslashes(corriger_caracteres($texte));
	$ps = addslashes(corriger_caracteres($ps));

	// Verifier qu'on envoie bien dans une rubrique autorisee
	if ($flag_auteur OR acces_rubrique($id_rubrique)) {
		$change_rubrique = "id_rubrique=\"$id_rubrique\",";
	} else {
		$change_rubrique = "";
	}

	$query = "UPDATE spip_articles SET surtitre=\"$surtitre\", titre=\"$titre\", soustitre=\"$soustitre\", $change_rubrique descriptif=\"$descriptif\", chapo=\"$chapo\", texte=\"$texte\", ps=\"$ps\" WHERE id_article=$id_article";
	$result = spip_query($query);
	calculer_rubriques();
	if ($statut_article == 'publie') $reindexer = true;

	// afficher le nouveau titre dans la barre de fenetre
	$titre_article = stripslashes($titre);

	// marquer l'article (important pour les articles nouvellement crees)
	spip_query("UPDATE spip_articles SET date_modif=NOW(), auteur_modif=$connect_id_auteur WHERE id_article=$id_article");
	$id_article_bloque = $id_article;   // message pour inc_presentation
}



//
// Suivi forums publics
//

// fonction dupliquee dans inc-forum.php3
function get_forums_publics($id_article=0) {
	$forums_publics = lire_meta("forums_publics");
	if ($id_article) {
		$query = "SELECT accepter_forum FROM spip_articles WHERE id_article=$id_article";
		$res = spip_query($query);
		if ($obj = spip_fetch_object($res))
			$forums_publics = $obj->accepter_forum;
	} else { // dans ce contexte, inutile
		$forums_publics = substr(lire_meta("forums_publics"),0,3);
	}
	return $forums_publics;
}


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
	$chapo = $row["chapo"];
	$texte = $row["texte"];
	$ps = $row["ps"];
	$date = $row["date"];
	$statut_article = $row["statut"];
	$maj = $row["maj"];
	$date_redac = $row["date_redac"];
	$visites = $row["visites"];
	$referers = $row["referers"];
}

// pour l'affichage du virtuel
unset($virtuel);
if (substr($chapo, 0, 1) == '=') {
	$virtuel = substr($chapo, 1);
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

debut_boite_info();

echo "<div align='center'>\n";

if ($statut_article == "publie") {
	$post_dates = lire_meta("post_dates");
	$voir_en_ligne = true;
	if ($post_dates == "non") {
		$query = "SELECT id_article FROM spip_articles WHERE id_article=$id_article AND date<=NOW()";
		$result = spip_query($query);
		if (!spip_num_rows($result)) {
			$voir_en_ligne = false;
		}
	}
}

echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='1'><b>"._T('info_numero_article')."&nbsp;:</b></font>\n";
echo "<br><font face='Verdana,Arial,Helvetica,sans-serif' size='6'><b>$id_article</b></font>\n";

if ($voir_en_ligne) {
	icone_horizontale(_T('icone_voir_en_ligne'), "../spip_redirect.php3?id_article=$id_article&recalcul=oui", "racine-24.gif", "rien.gif");
}

if ($connect_statut=='0minirezo' AND acces_rubrique($id_rubrique)) {
	$query = "SELECT count(*) AS count FROM spip_forum WHERE id_article=$id_article AND statut IN ('publie', 'off', 'prop')";
	if ($row = spip_fetch_array(spip_query($query))) {
		$nb_forums = $row['count'];
		if ($nb_forums) {
			icone_horizontale(_T('icone_suivi_forum', array('nb_forums' => $nb_forums)),
				"articles_forum.php3?id_article=$id_article", "suivi-forum-24.gif", "");
		}
	}
}


$activer_statistiques = lire_meta("activer_statistiques");
$activer_statistiques_ref = lire_meta("activer_statistiques_ref");

if ($connect_statut == "0minirezo" AND $statut_article == 'publie' AND $visites > 0 AND $activer_statistiques != "non" AND $options == "avancees"){
	icone_horizontale(_T('icone_evolution_visites', array('visites' => $visites, 'aff_ref' => $aff_ref)), "statistiques_visites.php3?id_article=$id_article", "statistiques-24.gif","rien.gif");
}

echo "</div>\n";

fin_boite_info();


// Logos de l'article

$arton = "arton$id_article";
$artoff = "artoff$id_article";

if ($id_article>0 AND $flag_editable)
	afficher_boite_logo($arton, $artoff, _T('logo_article').aide ("logoart"), _T('logo_survol'));


//
// Boites de configuration avancee
//

if ($options == "avancees" && $connect_statut=='0minirezo' && $flag_editable) {
	echo "<p>";
	debut_cadre_relief("forum-interne-24.gif");
	$visible = $change_accepter_forum || $change_petition;

	echo "<font size='2' FACE='Verdana,Arial,Helvetica,sans-serif'><center><b>";
	if ($visible)
		echo bouton_block_visible("forumpetition");
	else
		echo bouton_block_invisible("forumpetition");
	echo _T('bouton_forum_petition');
	echo "</b></center></font>";
	if ($visible)
		echo debut_block_visible("forumpetition");
	else
		echo debut_block_invisible("forumpetition");


	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='1'>\n";

	// Forums et petitions

	$forums_publics = get_forums_publics($id_article);

	if ($change_accepter_forum) {
		$query_forum = "UPDATE spip_articles SET accepter_forum='$change_accepter_forum' WHERE id_article='$id_article'";
		$result_forum = spip_query($query_forum);
		$forums_publics = $change_accepter_forum;
		if ($change_accepter_forum == 'abo') {
			ecrire_meta('accepter_visiteurs', 'oui');
			ecrire_metas();
		}
	}

	echo "\n<form action='articles.php3' method='get'>";

	echo "\n<input type='hidden' name='id_article' value='$id_article'>";
	echo "<br>"._T('info_fonctionnement_forum')."\n";
	if ($forums_publics == "pos") {
		echo "<br><input type='radio' name='change_accepter_forum' value='pos' id='accepterforumpos' checked>";
		echo "<B><label for='accepterforumpos'> "._T('bouton_radio_modere_posteriori')."</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='pos' id='accepterforumpos'>";
		echo "<label for='accepterforumpos'> "._T('bouton_radio_modere_posteriori')."</label>";
	}
	if ($forums_publics == "pri") {
		echo "<br><input type='radio' name='change_accepter_forum' value='pri' id='accepterforumpri' checked>";
		echo "<B><label for='accepterforumpri'> "._T('bouton_radio_modere_priori')."</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='pri' id='accepterforumpri'>";
		echo "<label for='accepterforumpri'> "._T('bouton_radio_modere_priori')."</label>";
	}
	if ($forums_publics == "abo") {
		echo "<br><input type='radio' name='change_accepter_forum' value='abo' id='accepterforumabo' checked>";
		echo "<B><label for='accepterforumabo'> "._T('bouton_radio_modere_abonnement')."</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='abo' id='accepterforumabo'>";
		echo "<label for='accepterforumabo'> "._T('bouton_radio_modere_abonnement')."</label>";
	}
	if ($forums_publics == "non") {
		echo "<br><input type='radio' name='change_accepter_forum' value='non' id='accepterforumnon' checked>";
		echo "<B><label for='accepterforumnon'> "._T('info_pas_de_forum')."</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='non' id='accepterforumnon'>";
		echo "<label for='accepterforumnon'> "._T('info_pas_de_forum')."</label>";
	}

	echo "<div align='right'><input type='submit' name='Changer' class='fondo' value='"._T('bouton_changer')."' STYLE='font-size:10px'></div>\n";
	echo "</form>";

	echo "<br>";

	
	// Petitions

	if ($change_petition) {
		if ($change_petition == "on") {
			if (!$email_unique) $email_unique = "non";
			if (!$site_obli) $site_obli = "non";
			if (!$site_unique) $site_unique = "non";
			if (!$message) $message = "non";

			$texte_petition = addslashes($texte_petition);

			$query_pet = "REPLACE spip_petitions (id_article, email_unique, site_obli, site_unique, message, texte) ".
				"VALUES ($id_article, '$email_unique', '$site_obli', '$site_unique', '$message', '$texte_petition')";
			$result_pet = spip_query($query_pet);
		}
		else if ($change_petition == "off") {
			$query_pet = "DELETE FROM spip_petitions WHERE id_article=$id_article";
			$result_pet = spip_query($query_pet);
		}
	}

	$query_petition = "SELECT * FROM spip_petitions WHERE id_article=$id_article";
	$result_petition = spip_query($query_petition);
	$petition = (spip_num_rows($result_petition) > 0);

	while ($row = spip_fetch_array($result_petition)) {
		$id_rubrique=$row["id_article"];
		$email_unique=$row["email_unique"];
		$site_obli=$row["site_obli"];
		$site_unique=$row["site_unique"];
		$message=$row["message"];
		$texte_petition=$row["texte"];
	}

	echo "\n<FORM ACTION='articles.php3' METHOD='post'>";
	echo "\n<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";

	if ($petition){
		echo "<input type='radio' name='change_petition' value='on' id='petitionon' checked>";
		echo "<B><label for='petitionon'>"._T('bouton_radio_petition_activee')."</label></B>";
		$query_signatures = "SELECT COUNT(*) AS nb FROM spip_signatures WHERE id_article=$id_article";
		$result = spip_fetch_array(spip_query($query_signatures));
		if ($result['nb'] > 0) {
			echo "<p><font size=1><a href='controle_petition.php3?id_article=$id_article'>".$result['nb']." "._T('info_signatures')."</a></font>\n";
		}

		echo "<p>";
		if ($email_unique=="oui")
			echo "<input type='checkbox' name='email_unique' value='oui' id='emailunique' checked>";
		else
			echo "<input type='checkbox' name='email_unique' value='oui' id='emailunique'>";
		echo " <label for='emailunique'>"._T('bouton_checkbox_signature_unique_email')."</label><BR>";
		if ($site_obli=="oui")
			echo "<input type='checkbox' name='site_obli' value='oui' id='siteobli' checked>";
		else
			echo "<input type='checkbox' name='site_obli' value='oui' id='siteobli'>";
		echo " <label for='siteobli'>"._T('bouton_checkbox_indiquer_site')."</label><BR>";
		if ($site_unique=="oui")
			echo "<input type='checkbox' name='site_unique' value='oui' id='siteunique' checked>";
		else
			echo "<input type='checkbox' name='site_unique' value='oui' id='siteunique'>";
		echo " <label for='siteunique'>"._T('bouton_checkbox_signature_unique_site')."</label><BR>";
		if ($message=="oui")
			echo "<input type='checkbox' name='message' value='oui' id='message' checked>";
		else
			echo "<input type='checkbox' name='message' value='oui' id='message'>";
		echo " <label for='message'>"._T('bouton_checkbox_envoi_message')."</label>";

		echo "<P>"._T('texte_descriptif_petition')."&nbsp;:<BR>";
		echo "<TEXTAREA NAME='texte_petition' CLASS='forml' ROWS='4' COLS='10' wrap=soft>";
		echo $texte_petition;
		echo "</TEXTAREA><P>\n";

	}
	else {
		echo "<input type='radio' name='change_petition' value='on' id='petitionon'>";
		echo "<label for='petitionon'>"._T('bouton_radio_activer_petition')."</label>";
	}
	if (!$petition){
		echo "<br><input type='radio' name='change_petition' value='off' id='petitionoff' checked>";
		echo "<B><label for='petitionoff'>"._T('bouton_radio_pas_petition')."</label></B>";
	}else{
		echo "<br><input type='radio' name='change_petition' value='off' id='petitionoff'>";
		echo "<label for='petitionoff'>"._T('bouton_radio_supprimer_petition')."</label>";
	}

	echo "<P align='right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."' STYLE='font-size:10px'>";
	echo "</FORM>";

	echo "</font>";
	echo fin_block();

	fin_cadre_relief();

	echo "<br>";


	// Redirection (article virtuel)
	debut_cadre_relief("site-24.gif");
	$visible = ($changer_virtuel || $virtuel);

	echo "<font size='2' FACE='Verdana,Arial,Helvetica,sans-serif'><center><b>";
	if ($visible)
		echo bouton_block_visible("redirection");
	else
		echo bouton_block_invisible("redirection");
	echo _T('bouton_redirection');
	echo aide ("artvirt");
	echo "</b></center></font>";
	if ($visible)
		echo debut_block_visible("redirection");
	else
		echo debut_block_invisible("redirection");

	echo "<form action='articles.php3?id_article=$id_article' method='post'>";
	echo "\n<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";
	echo "\n<INPUT TYPE='hidden' NAME='changer_virtuel' VALUE='oui'>";
	$virtuelhttp = ($virtuel ? "" : "http://");

	echo "<INPUT TYPE='text' NAME='virtuel' CLASS='formo' style='font-size:9px;' VALUE=\"$virtuelhttp$virtuel\" SIZE='40'><br>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
	echo "(<b>"._T('texte_article_virtuel')."&nbsp;:</b> "._T('texte_reference_mais_redirige').")";
	echo "</font>";
	echo "<div align='right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."' STYLE='font-size:10px'></div>";
	echo "</form>";
	echo fin_block();

	fin_cadre_relief();
}


//
// Afficher les raccourcis
//

debut_raccourcis();

icone_horizontale(_T('icone_tous_articles'), "articles_page.php3", "article-24.gif");
if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article) AND $options == "avancees") {
	$retour = urlencode($clean_link->getUrl());
	icone_horizontale(_T('icone_creer_auteur'), "auteur_infos.php3?new=oui&ajouter_id_article=$id_article&redirect=$retour", "redacteurs-24.gif", "creer.gif");
	$articles_mots = lire_meta('articles_mots');
	if ($articles_mots != "non")
		icone_horizontale(_T('icone_creer_mot_cle'), "mots_edit.php3?new=oui&ajouter_id_article=$id_article&redirect=$retour", "mot-cle-24.gif", "creer.gif");
}

fin_raccourcis();


//////////////////////////////////////////////////////
// Affichage de la colonne de droite
//

debut_droite();


// qu'est-ce que c'est que ces choses ??

function mySel($varaut,$variable){
	$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
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
	my_sel("00","n.c.",$jour);
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		my_sel($i,$aff,$jour);
	}
}



debut_cadre_relief();
echo "<CENTER>";

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
	echo "<font face='arial,helvetica' size=3><b>";
	echo typo($surtitre);
	echo "</b></font>\n";
}
	gros_titre($titre, $logo_statut);
if ($soustitre) {
	echo "<font face='arial,helvetica' size=3><b>";
	echo typo($soustitre);
	echo "</b></font>\n";
}


if ($descriptif) {
	echo "<p><div align='left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;'>";
	echo "<font size=2 face='Verdana,Arial,Helvetica,sans-serif'>";
	echo "<b>"._T('info_descriptif')."</b> ";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</font>";
	echo "</div>";
}

if ($statut_article == 'prop') {
	echo "<P><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='red'><B>"._T('text_article_propose_publication')."</B></FONT></P>";
}


echo "</td>";


if ($flag_editable) {
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";
	echo "<td align='center'>";
	$flag_modif = false;

	// Recuperer les donnees de l'article
	if (lire_meta('articles_modif') != 'non') {
		$query = "SELECT auteur_modif, UNIX_TIMESTAMP(date_modif) AS modification, UNIX_TIMESTAMP(NOW()) AS maintenant FROM spip_articles WHERE id_article='$id_article'";
		$result = spip_query($query);

		if ($row = spip_fetch_array($result)) {
			$auteur_modif = $row["auteur_modif"];
			$modification = $row["modification"];
			$maintenant = $row["maintenant"];

			$date_diff = floor(($maintenant - $modification)/60);

			if ($date_diff >= 0 AND $date_diff < 60 AND $auteur_modif > 0 AND $auteur_modif != $connect_id_auteur) {
				$flag_modif = true;
				$query_auteur = "SELECT nom FROM spip_auteurs WHERE id_auteur='$auteur_modif'";
				$result_auteur = spip_query($query_auteur);
				if ($row_auteur = spip_fetch_array($result_auteur)) {
					$nom_auteur_modif = $row_auteur["nom"];
				}
			}
		}
	}
	if ($flag_modif) {
		icone(_T('icone_modifier_article'), "articles_edit.php3?id_article=$id_article", "article-24.gif", "edit.gif");
		echo "<font face='arial,helvetica,sans-serif' size='2'>"._T('avis_article_modifie', array('nom_auteur_modif' => $nom_auteur_modif, 'date_diff' => $date_diff))."</font>";
		echo aide("artmodif");
	}
	else {
		icone(_T('icone_modifier_article'), "articles_edit.php3?id_article=$id_article", "article-24.gif", "edit.gif");
	}

	echo "</td>";
}
echo "</tr></table>\n";



echo "<P align=left>";
echo "<FONT FACE='Georgia,Garamond,Times,serif'>";


//
// Affichage date redac et date publi
//

if ($flag_editable AND $options == 'avancees') {
	debut_cadre_enfonce();

	echo "<FORM ACTION='articles.php3' METHOD='GET'>";
	echo "<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";

	if ($statut_article == 'publie') {
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND=''>";
		echo "<TR><TD BGCOLOR='$couleur_foncee' COLSPAN=2><FONT SIZE=1 COLOR='#FFFFFF'><B>"._T('texte_date_publication_article');
		echo aide ("artdate");
		echo "</B></FONT></TD></TR>";
		echo "<TR><TD ALIGN='center'>";
		echo "<SELECT NAME='jour' SIZE=1 CLASS='fondl'>";
		afficher_jour($jour);
		echo "</SELECT> ";
		echo "<SELECT NAME='mois' SIZE=1 CLASS='fondl'>";
		afficher_mois($mois);
		echo "</SELECT> ";
		echo "<SELECT NAME='annee' SIZE=1 CLASS='fondl'>";
		afficher_annee($annee);
		echo "</SELECT>";

		echo "</TD><TD ALIGN='right'>";
		echo "<INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."'>";
		echo "</TD></TR></TABLE>";
	}
	else {
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND=''>";
		echo "<TR><TD BGCOLOR='$couleur_foncee'><FONT SIZE=1 COLOR='#FFFFFF' face='Verdana,Arial,Helvetica,sans-serif'><b>"._T('texte_date_creation_article')." : ";
		echo majuscules(affdate($date))."</font></B></FONT>".aide('artdate')."</TD></TR>";
		echo "</TABLE>";
	}

	if (($options == 'avancees' AND $articles_redac != 'non') OR ($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00')) {
		echo '<p><table cellpadding="5" cellspacing="0" border="0" width="100%">';
		echo '<tr><td bgcolor="#cccccc" colspan="2"><font size="1" color="#000000" face="Verdana,Arial,Helvetica,sans-serif">';
		if ($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00') $date_affichee = ' : '.majuscules(affdate($date_redac));
		echo bouton_block_invisible('dateredac');
		echo "<b>"._T('texte_date_publication_anterieure').$date_affichee."</b></font></td></tr></table>";
		echo debut_block_invisible('dateredac');
		echo '<table cellpadding="5" cellspacing="0" border="0" width="100%">';
		echo '<tr><td align="left">';
		echo '<input type="radio" name="avec_redac" value="non" id="avec_redac_on"';
		if ($annee_redac.'-'.$mois_redac.'-'.$jour_redac == '0000-00-00') echo ' checked="checked"';
		echo ' /> <label for="avec_redac_on">'._T('texte_date_publication_anterieure_nonaffichee').'</label>';
		echo '<br /><input type="radio" name="avec_redac" value="oui" id="avec_redac_off"';
		if ($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00') echo ' checked="checked"';
		echo ' /> <label for="avec_redac_off">'._T('bouton_radio_afficher').' :</label> ';

		echo '<select name="jour_redac" class="fondl">';
		afficher_jour($jour_redac);
		echo '</select> &nbsp;';
		echo '<select name="mois_redac" class="fondl">';
		afficher_mois($mois_redac);
		echo '</select> &nbsp;';
		echo '<input type="text" name="annee_redac" class="fondl" value="'.$annee_redac.'" size="5" maxlength="4" />';

		echo '</td><td align="right">';
		echo '<input type="submit" name="Changer" class="fondo" value="'._T('bouton_changer').'" />';
		echo aide('artdate_redac');
		echo '</td></tr>';
		echo fin_block();
		echo '</table>';
	}

	echo "</FORM>";
	fin_cadre_enfonce();
}

else if ($statut_article == 'publie') {
	echo "<CENTER>".affdate($date)."</CENTER><P>";
}



//
// Liste des auteurs de l'article
//

debut_cadre_enfonce("redacteurs-24.gif");

echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC'>";
if ($flag_editable AND $options == 'avancees') {
	echo bouton_block_invisible("auteursarticle");
}
echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'><B>"._T('texte_auteurs')."</B></FONT>";
echo aide ("artauteurs");
echo "</TD></TR></TABLE>";


////////////////////////////////////////////////////
// Gestion des auteurs
//

// Creer un nouvel auteur et l'ajouter

if ($creer_auteur AND $connect_statut=='0minirezo'){
	$creer_auteur = addslashes($creer_auteur);
	$query_creer = "INSERT INTO spip_auteurs (nom, statut) VALUES (\"$creer_auteur\", '1comite')";
	$result_creer = spip_query($query_creer);
	
	$nouv_auteur = spip_insert_id();
	$ajout_auteur = true;
}


//
// Recherche d'auteur
//

if ($cherche_auteur) {
	echo "<P ALIGN='left'>";
	$query = "SELECT id_auteur, nom FROM spip_auteurs";
	$result = spip_query($query);
	unset($table_auteurs);
	unset($table_ids);
	while ($row = spip_fetch_array($result)) {
		$table_auteurs[] = $row["nom"];
		$table_ids[] = $row["id_auteur"];
	}
	$resultat = mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
	debut_boite_info();
	if (!$resultat) {
		echo "<B>"._T('texte_aucun_resultat_auteur', array('cherche_auteur' => $cherche_auteur)).".</B><BR>";
	}
	else if (count($resultat) == 1) {
		$ajout_auteur = 'oui';
		list(, $nouv_auteur) = each($resultat);
		echo "<B>"._T('texte_ajout_auteur')."</B><BR>";
		$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$nouv_auteur";
		$result = spip_query($query);
		echo "<UL>";
		while ($row = spip_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$nom_auteur = $row['nom'];
			$email_auteur = $row['email'];
			$bio_auteur = $row['bio'];

			echo "<LI><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B><FONT SIZE=3>$nom_auteur</FONT></B>";
			echo "</FONT>\n";
		}
		echo "</UL>";
	}
	else if (count($resultat) < 16) {
		reset($resultat);
		unset($les_auteurs);
		while (list(, $id_auteur) = each($resultat)) $les_auteurs[] = $id_auteur;
		if ($les_auteurs) {
			$les_auteurs = join(',', $les_auteurs);
			echo "<B>"._T('texte_plusieurs_articles', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
			$query = "SELECT * FROM spip_auteurs WHERE id_auteur IN ($les_auteurs) ORDER BY nom";
			$result = spip_query($query);
			echo "<UL>";
			while ($row = spip_fetch_array($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];
	
				echo "<LI><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B><FONT SIZE=3>$nom_auteur</FONT></B>";
			
				if ($email_auteur) echo " ($email_auteur)";
				echo " | <A HREF=\"articles.php3?id_article=$id_article&ajout_auteur=oui&nouv_auteur=$id_auteur\">"._T('lien_ajouter_auteur')."</A>";
			
				if (trim($bio_auteur)) {
					echo "<BR><FONT SIZE=1>".propre(couper($bio_auteur, 100))."</FONT>\n";
				}
				echo "</FONT><p>\n";
			}
			echo "</UL>";
		}
	}
	else {
		echo "<B>"._T('texte_trop_resultats_auteurs', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
	}
	fin_boite_info();
	echo "<P>";

}



//
// Appliquer les modifications sur les auteurs 
//

if ($ajout_auteur && $flag_editable) {
	if ($nouv_auteur > 0) {
		$query="DELETE FROM spip_auteurs_articles WHERE id_auteur='$nouv_auteur' AND id_article='$id_article'";
		$result=spip_query($query);
		$query="INSERT INTO spip_auteurs_articles (id_auteur,id_article) VALUES ('$nouv_auteur','$id_article')";
		$result=spip_query($query);
	}
}

if ($supp_auteur && $flag_editable) {
	$query="DELETE FROM spip_auteurs_articles WHERE id_auteur='$supp_auteur' AND id_article='$id_article'";
	$result=spip_query($query);

}


//
// Afficher les auteurs 
//

unset($les_auteurs);

$query = "SELECT * FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien ".
	"WHERE auteurs.id_auteur=lien.id_auteur AND lien.id_article=$id_article ".
	"GROUP BY auteurs.id_auteur ORDER BY auteurs.nom";
$result = spip_query($query);

if (spip_num_rows($result)) {
	$ifond = 0;

	echo "\n<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''>\n";
	while ($row = spip_fetch_array($result)) {
		$id_auteur = $row["id_auteur"];
		$nom_auteur = $row["nom"];
		$email_auteur = $row["email"];
		if ($bio_auteur = attribut_html(propre(couper($row["bio"], 100))))
			$bio_auteur = " TITLE=\"$bio_auteur\"";
		$url_site_auteur = $row["url_site"];
		$statut_auteur = $row["statut"];
		if ($row['messagerie'] == 'non' OR $row['login'] == '') $messagerie = 'non';

		$les_auteurs[] = $id_auteur;

		if ($connect_statut == "0minirezo") $aff_articles = "('prepa', 'prop', 'publie', 'refuse')";
		else $aff_articles = "('prop', 'publie')";

		$query2 = "SELECT COUNT(articles.id_article) AS compteur ".
			"FROM spip_auteurs_articles AS lien, spip_articles AS articles ".
			"WHERE lien.id_auteur=$id_auteur AND articles.id_article=lien.id_article ".
			"AND articles.statut IN $aff_articles GROUP BY lien.id_auteur";
		$result2 = spip_query($query2);
		if ($result2) list($nombre_articles) = spip_fetch_row($result2);
		else $nombre_articles = 0;

		$ifond = $ifond ^ 1;
		$couleur = ($ifond) ? '#FFFFFF' : $couleur_claire;

		$url_auteur = "auteurs_edit.php3?id_auteur=$id_auteur";

		echo "<TR BGCOLOR='$couleur' WIDTH=\"100%\">";
		echo "<TD WIDTH='20'>";
		echo bonhomme_statut($row);
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		echo "<A HREF=\"$url_auteur\"$bio_auteur>$nom_auteur</A>";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		echo bouton_imessage($id_auteur)."&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		if ($email_auteur) echo "<A HREF='mailto:$email_auteur'>"._T('email')."</A>";
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		if ($url_site_auteur) echo "<A HREF='$url_site_auteur'>"._T('info_site_min')."</A>";
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial2' ALIGN='right'>";
		if ($nombre_articles > 1) echo "$nombre_articles articles";
		else if ($nombre_articles == 1) echo _T('info_1_article');
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial1' align='right'>";
		if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo') AND $options == 'avancees') {
			echo "<A HREF='articles.php3?id_article=$id_article&supp_auteur=$id_auteur'>"._T('lien_retirer_auteur')."</A>";
		}
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "</TR>\n";
	}
	echo "</TABLE>\n";

	$les_auteurs = join(',', $les_auteurs);
}


//
// Ajouter un auteur
//

if ($flag_editable AND $options == 'avancees') {
	echo debut_block_invisible("auteursarticle");
	
	$query = "SELECT * FROM spip_auteurs WHERE ";
	if ($les_auteurs) $query .= "id_auteur NOT IN ($les_auteurs) AND ";
	$query .= "statut<>'5poubelle' AND statut<>'nouveau' ORDER BY statut, nom";
	$result = spip_query($query);

	if (spip_num_rows($result) > 0) {

		echo "<FORM ACTION='articles.php3' METHOD='post'>";
		echo "<DIV align=right><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B>"._T('titre_cadre_ajouter_auteur')."&nbsp; </B></FONT>\n";
		echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";

		if (spip_num_rows($result) > 80 AND $flag_mots_ressemblants) {
			echo "<INPUT TYPE='text' NAME='cherche_auteur' CLASS='fondl' VALUE='' SIZE='20'>";
			echo " <INPUT TYPE='submit' NAME='Chercher' VALUE='"._T('bouton_chercher')."' CLASS='fondo'>";
		}
		else {
			echo "<INPUT TYPE='Hidden' NAME='ajout_auteur' VALUE='oui'>";
			echo "<SELECT NAME='nouv_auteur' SIZE='1' STYLE='WIDTH=150' CLASS='fondl'>";
			$group = false;
			$group2 = false;
	
			while($row=spip_fetch_array($result)) {
				$id_auteur = $row["id_auteur"];
				$nom = $row["nom"];
				$email = $row["email"];
				$statut = $row["statut"];
	
				$statut=ereg_replace("0minirezo", _T('item_administrateur'), $statut);
				$statut=ereg_replace("1comite", _T('item_redacteur'), $statut);
				$statut=ereg_replace("2redac", _T('item_redacteur'), $statut);
				$statut=ereg_replace("5poubelle", _T('item_efface'), $statut);
	
				$premiere = strtoupper(substr(trim($nom), 0, 1));
	
				if ($connect_statut != '0minirezo')
					if ($p = strpos($email, '@'))
						$email = substr($email, 0, $p).'@...';
				if ($email)
					$email = " ($email)";
	
				if ($statut != $statut_old) {
					echo "\n<OPTION VALUE=\"x\">";
					echo "\n<OPTION VALUE=\"x\"> $statut".'s';
				}
			
				if ($premiere != $premiere_old AND ($statut != _T('item_administrateur') OR !$premiere_old)) {
					echo "\n<OPTION VALUE=\"x\">";
				}
	
				$texte_option = couper("$nom$email", 40);
				echo "\n<OPTION VALUE=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;$texte_option";
				$statut_old = $statut;
				$premiere_old = $premiere;
			}
			
			echo "</SELECT>";
			echo " <INPUT TYPE='submit' NAME='Ajouter' VALUE="._T('bouton_ajouter')." CLASS='fondo'>";
		}
		echo "</div></FORM>";
	}
	
		
	echo fin_block();
}

fin_cadre_enfonce(false);



//////////////////////////////////////////////////////
// Liste des mots-cles de l'article
//

if ($options == 'avancees' AND $articles_mots != 'non') {
	formulaire_mots('articles', $id_article, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}


//////////////////////////////////////////////////////
// Modifier le statut de l'article
//


?>
<SCRIPT LANGUAGE="JavaScript">
<!-- Beginning of JavaScript -
function change_bouton(selObj){

	var selection=selObj.options[selObj.selectedIndex].value;

	if (selection=="publie"){
		document.statut.src="img_pack/puce-verte.gif";
	}
	if (selection=="prepa"){
		document.statut.src="img_pack/puce-blanche.gif";
	}
	if (selection=="prop"){
		document.statut.src="img_pack/puce-orange.gif";
	}
	if (selection=="refuse"){
		document.statut.src="img_pack/puce-rouge.gif";
	}
	if (selection=="poubelle"){
		document.statut.src="img_pack/puce-poubelle.gif";
	}
}

// - End of JavaScript - -->
</SCRIPT>
<?php

if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article)) {
	echo "<FORM ACTION='articles.php3' METHOD='get'>";
	debut_cadre_relief("racine-site-24.gif");
	echo "<CENTER>";
	
	echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";

	echo "<B>"._T('texte_article_statut')."</B> ";

	echo "<SELECT NAME='statut_nouv' SIZE='1' CLASS='fondl' onChange='change_bouton(this)'>";

	echo "<OPTION" . mySel("prepa", $statut_article) .">"._T('texte_statut_en_cours_redaction')."\n";
	echo "<OPTION" . mySel("prop", $statut_article) . ">"._T('texte_statut_propose_evaluation')."\n";
	echo "<OPTION" . mySel("publie", $statut_article) . ">"._T('texte_statut_publie')."\n";
	echo "<OPTION" . mySel("poubelle", $statut_article) . ">"._T('texte_statut_poubelle')."\n";
	echo "<OPTION" . mySel("refuse", $statut_article) . ">"._T('texte_statut_refuse')."\n";

	echo "</SELECT>";

	echo " \n";

	if ($statut_article=='publie') {
		echo "<img src='img_pack/puce-verte.gif' alt='' width='13' height='14' border='0' NAME='statut'>";
	}
	else if ($statut_article=='prepa') {
		echo "<img src='img_pack/puce-blanche.gif' alt='' width='13' height='14' border='0' NAME='statut'>";
	}
	else if ($statut_article=='prop') {
		echo "<img src='img_pack/puce-orange.gif' alt='' width='13' height='14' border='0' NAME='statut'>";
	}
	else if ($statut_article == 'refuse') {
		echo "<img src='img_pack/puce-rouge.gif' alt='' width='13' height='14' border='0' NAME='statut'>";
	}
	else if ($statut_article == 'poubelle') {
		echo "<img src='img_pack/puce-poubelle.gif' alt='' width='13' height='14' border='0' NAME='statut'>";
	}
	echo " \n";

	echo "<INPUT TYPE='submit' NAME='Modifier' VALUE='"._T('bouton_modifier')."' CLASS='fondo'>";
	echo aide ("artstatut");
	echo "</CENTER>";
	fin_cadre_relief();
	echo "</FORM>";
}



//////////////////////////////////////////////////////
// Corps de l'article
//

echo "\n\n<DIV align=justify>";

if ($virtuel) {
	debut_boite_info();
	echo _T('info_renvoi_article')." ".propre("<center>[->$virtuel]</center>");
	fin_boite_info();
}
else {
	echo "<div><b>";
	echo justifier(propre($chapo));
	echo "</b></div>\n\n";

	echo justifier(propre($texte));

	if ($ps) {
		echo debut_cadre_enfonce();
		echo "<font size=2 face='Verdana,Arial,Helvetica,sans-serif'>";
		echo justifier("<b>"._T('info_ps')."</b> ".propre($ps));
		echo "</font>";
		echo fin_cadre_enfonce();
	}

	if ($les_notes) {
		echo debut_cadre_relief();
		echo "<font size=2>";
		echo justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
		echo "</font>";
		echo fin_cadre_relief();
	}
}


//
// Bouton "modifier cet article"
//

if ($flag_editable) {
echo "\n\n<div align=right><br>";

if ($date_diff >= 0 AND $date_diff < 60 AND $auteur_modif > 0 AND $auteur_modif != $connect_id_auteur) {
	$query_auteur = "SELECT * FROM spip_auteurs WHERE id_auteur='$auteur_modif'";
	$result_auteur = spip_query($query_auteur);
	while ($row_auteur = spip_fetch_array($result_auteur)) {
		$nom_auteur_modif = $row_auteur["nom"];
	}
	icone(_T('icone_modifier_article'), "articles_edit.php3?id_article=$id_article", "warning-24.gif", "");
	echo "<font face='arial,helvetica,sans-serif' size=1>"._T('texte_travail_article', array('nom_auteur_modif' => $nom_auteur_modif, 'date_diff' => $date_diff))."</font>";
	echo aide("artmodif");
}
else {
	icone(_T('icone_modifier_article'), "articles_edit.php3?id_article=$id_article", "article-24.gif", "edit.gif");
}

echo "</div>";
}


//
// Documents associes a l'article
//
afficher_documents_non_inclus($id_article, "article", $flag_editable);


//
// "Demander la publication"
//

if ($flag_auteur AND $statut_article == 'prepa') {
	echo "<P>";
	debut_cadre_relief();
	echo "<center>";
	echo "<B>"._T('texte_proposer_publication')."</B>";
	echo aide ("artprop");
	bouton(_T('bouton_demande_publication'), "articles.php3?id_article=$id_article&statut_nouv=prop");
	echo "</center>";
	fin_cadre_relief();
}

echo "</DIV>";


fin_cadre_relief();

//
// Forums
//

echo "<BR><BR>";

$forum_retour = urlencode("articles.php3?id_article=$id_article");


echo "\n<div align='center'>";
	icone(_T('icone_poster_message'), "forum_envoi.php3?statut=prive&adresse_retour=".$forum_retour."&id_article=$id_article&titre_message=".urlencode($titre), "forum-interne-24.gif", "creer.gif");
echo "</div>";

echo "<P align='left'>";


$query_forum = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0";
$result_forum = spip_query($query_forum);
$total = 0;
if ($row = spip_fetch_array($result_forum)) $total = $row["cnt"];

if (!$debut) $debut = 0;
$total_afficher = 8;
if ($total > $total_afficher) {
	echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
	echo "<CENTER>";
	for ($i = 0; $i < $total; $i = $i + $total_afficher){
		$y = $i + $total_afficher - 1;
		if ($i == $debut)
			echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
		else
			echo "[<A HREF='articles.php3?id_article=$id_article&debut=$i'>$i-$y</A>] ";
	}
	echo "</CENTER>";
	echo "</font>";
}



$query_forum = "SELECT * FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0 ORDER BY date_heure DESC LIMIT $debut,$total_afficher";
$result_forum = spip_query($query_forum);
afficher_forum($result_forum, $forum_retour);


if (!$debut) $debut = 0;
$total_afficher = 8;
if ($total > $total_afficher) {
	echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
	echo "<CENTER>";
	for ($i = 0; $i < $total; $i = $i + $total_afficher){
		$y = $i + $total_afficher - 1;
		if ($i == $debut)
			echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
		else
			echo "[<A HREF='articles.php3?id_article=$id_article&debut=$i'>$i-$y</A>] ";
	}
	echo "</CENTER>";
	echo "</font>";
}


fin_page();


// choses lentes reportees en fin de page
@flush();

if ($ok_nouveau_statut) {
	calculer_rubriques();
	if ($statut_nouv == 'publie' AND $statut_ancien != $statut_nouv) {
		include_ecrire("inc_mail.php3");
		envoyer_mail_publication($id_article);
	}
	if ($statut_nouv == "prop" AND $statut_ancien != $statut_nouv AND $statut_ancien != 'publie') {
		include_ecrire("inc_mail.php3");
		envoyer_mail_proposition($id_article);
	}
	if ($statut_nouv == 'publie' AND $statut_nouv != $statut_ancien) $reindexer = true;
}

if ($reindexer AND (lire_meta('activer_moteur') == 'oui')) {
	include_ecrire ("inc_index.php3");
	indexer_article($id_article);
}

?>

