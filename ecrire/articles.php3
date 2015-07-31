<?php

include ("inc.php3");

include_ecrire ("inc_logos.php3");
include_ecrire ("inc_index.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_documents.php3");

$articles_surtitre = lire_meta("articles_surtitre");
$articles_soustitre = lire_meta("articles_soustitre");
$articles_descriptif = lire_meta("articles_descriptif");
$articles_chapeau = lire_meta("articles_chapeau");
$articles_ps = lire_meta("articles_ps");
$articles_redac = lire_meta("articles_redac");
$articles_mots = lire_meta("articles_mots");

if ($id_article==0 AND $new=='oui') {
	$forums_publics = substr(lire_meta('forums_publics'),0,3);
	spip_query("INSERT INTO spip_articles (id_rubrique, statut, date, accepter_forum) VALUES ($id_rubrique, 'prepa', NOW(), '$forums_publics')");
	$id_article = mysql_insert_id();
	spip_query("DELETE FROM spip_auteurs_articles WHERE id_article = $id_article");
	spip_query("INSERT INTO spip_auteurs_articles (id_auteur, id_article) VALUES ($connect_id_auteur, $id_article)");
}

$clean_link = new Link("articles.php3?id_article=$id_article");

// Initialiser doublons pour documents (completes par "propre($texte)")
$id_doublons['documents'] = "0";



//////////////////////////////////////////////////////
// Determiner les droits d'edition de l'article
//

$query = "SELECT statut, titre, id_rubrique FROM spip_articles WHERE id_article=$id_article";
$result = spip_query($query);
if ($row = mysql_fetch_array($result)) {
	$statut_article = $row['statut'];
	$titre_article = $row['titre'];
	$rubrique_article = $row['id_rubrique'];
}
else {
	$statut_article = '';
}

$query = "SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur";
$result_auteur = spip_query($query);

$flag_auteur = (mysql_num_rows($result_auteur) > 0);
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

if ($statut_nouv) {
	$ok = false;
	if (acces_rubrique($rubrique_article)) $ok = true;
	else if ($flag_auteur) {
		if ($statut_nouv == 'prop' AND $statut_article == 'prepa')
			$ok = true;
		else if ($statut_nouv == 'prepa' AND $statut_article == 'poubelle')
			$ok = true;
	}
	if ($ok) {
		$query = "UPDATE spip_articles SET statut='$statut_nouv' WHERE id_article=$id_article";
		$result = spip_query($query);

		if ($statut_nouv == 'publie' AND $statut_nouv != $statut_article) {
			$query = "UPDATE spip_articles SET date=NOW() WHERE id_article=$id_article";
			$result = spip_query($query);
			if (lire_meta('activer_moteur') == 'oui') {
				indexer_article($id_article);
			}
		}
		calculer_rubriques();

		if ($statut_nouv == 'publie' AND $statut_article != $statut_nouv) {
			envoyer_mail_publication($id_article);
		}
	
		if ($statut_nouv == "prop" AND $statut_article != $statut_nouv AND $statut_article != 'publie') {
			envoyer_mail_proposition($id_article);
		}
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

while($row=mysql_fetch_array($result)){
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


if ($changer_virtuel OR $virtuel) {
	if (strlen($virtuel) > 0) $chapo = "=$virtuel";
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
	if ($statut_article == 'publie') {
		if (lire_meta('activer_moteur') == 'oui') {
			indexer_article($id_article);
		}
	}
	
	// afficher le nouveau titre dans la barre de fenetre
	$titre_article = stripslashes($titre);
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
		if ($obj = mysql_fetch_object($res))
			$forums_publics = $obj->accepter_forum;
	} else { // dans ce contexte, inutile
		$forums_publics = substr(lire_meta("forums_publics"),0,3);
	}
	return $forums_publics;
}


//////////////////////////////////////////////////////
// Affichage de la colonne de gauche
//

//
// Lire l'article
//

$query = "SELECT * FROM spip_articles WHERE id_article='$id_article'";
$result = spip_query($query);

if ($row = mysql_fetch_array($result)) {
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
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>RACINE DU SITE</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();



debut_gauche();

debut_boite_info();

echo "<div align='center'>\n";

if ($statut_article == "publie") {
	$post_dates = lire_meta("post_dates");

	$voir_en_ligne = true;

	if ($post_dates == "non") {
		$query = "SELECT id_article FROM spip_articles WHERE id_article=$id_article AND date<=NOW()";
		$result = spip_query($query);
		if (!mysql_num_rows($result)) {
			$voir_en_ligne = false;
		}
	}
}


echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='1'><b>ARTICLE NUM&Eacute;RO&nbsp;:</b></font>\n";
echo "<br><font face='Verdana,Arial,Helvetica,sans-serif' size='6'><b>$id_article</b></font>\n";




if ($voir_en_ligne) {
	icone_horizontale("Voir en ligne", "../spip_redirect.php3?id_article=$id_article&recalcul=oui", "racine-24.gif", "rien.gif");
}



echo "</div>\n";

fin_boite_info();

$activer_statistiques = lire_meta("activer_statistiques");
$activer_statistiques_ref = lire_meta("activer_statistiques_ref");

if ($connect_statut == "0minirezo" AND $statut_article == 'publie' AND $visites > 0 AND $activer_statistiques != "non"){
	echo "<p>";
	icone_horizontale("&Eacute;volution des visites<br>$visites visites$aff_ref", "statistiques_visites.php3?id_article=$id_article", "statistiques-24.gif","rien.gif");
}


//
// Boites de configuration avancee
//

$boite_ouverte = false;


//
// Logos de l'article
//

$arton = "arton$id_article";
$artoff = "artoff$id_article";
$arton_ok = get_image($arton);
if ($arton_ok) $artoff_ok = get_image($artoff);

if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article) AND ($options == 'avancees' OR $arton_ok)) {
		echo "<p>";
		debut_cadre_relief();

	afficher_boite_logo($arton, "LOGO DE L'ARTICLE".aide ("logoart"));
	if (($options == 'avancees' AND $arton_ok) OR $artoff_ok) {
		echo "<p>";
		afficher_boite_logo($artoff, "LOGO POUR SURVOL");
	}
	
	fin_cadre_relief();
	
}


//
// Accepter forums...
//


$forums_publics = get_forums_publics($id_article);

if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article) AND $options == 'avancees') {

	if ($change_accepter_forum) {
		$query_pet="UPDATE spip_articles SET accepter_forum='$change_accepter_forum' WHERE id_article='$id_article'";
		$result_pet=spip_query($query_pet);
		$forums_publics = $change_accepter_forum;
	}

	if (!$boite_ouverte) {
		debut_boite_info();
		$boite_ouverte = true;
	}

	// boite active ?
	if ($change_accepter_forum) $visible = true;

	echo "<center><table width='100%' cellpadding='2' border='1' class='hauteur'>\n";
	echo "<tr><td width='100%' align='left' bgcolor='#FFCC66'>\n";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#333333'><b>\n";
	if ($visible)
		echo bouton_block_visible("forumarticle");
	else
		echo bouton_block_invisible("forumarticle");
	echo "FORUM PUBLIC";
	echo "</b></font></td></tr></table></center>";

	if ($visible)
		echo debut_block_visible("forumarticle");
	else
		echo debut_block_invisible("forumarticle");

	echo "\n<form action='articles.php3' method='get'>";

	echo "\n<input type='hidden' name='id_article' value='$id_article'>";
	echo "<br>Fonctionnement du forum&nbsp;:\n";
	if ($forums_publics == "pos") {
		echo "<br><input type='radio' name='change_accepter_forum' value='pos' id='accepterforumpos' checked>";
		echo "<B><label for='accepterforumpos'> mod&eacute;r&eacute; &agrave; posteriori</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='pos' id='accepterforumpos'>";
		echo "<label for='accepterforumpos'> mod&eacute;r&eacute; &agrave; posteriori</label>";
	}
	if ($forums_publics == "pri") {
		echo "<br><input type='radio' name='change_accepter_forum' value='pri' id='accepterforumpri' checked>";
		echo "<B><label for='accepterforumpri'> mod&eacute;r&eacute; &agrave; priori</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='pri' id='accepterforumpri'>";
		echo "<label for='accepterforumpri'> mod&eacute;r&eacute; &agrave; priori</label>";
	}
	if ($forums_publics == "abo") {
		echo "<br><input type='radio' name='change_accepter_forum' value='abo' id='accepterforumabo' checked>";
		echo "<B><label for='accepterforumabo'> mod&eacute;r&eacute; sur abonnement</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='abo' id='accepterforumabo'>";
		echo "<label for='accepterforumabo'> mod&eacute;r&eacute; sur abonnement</label>";
	}
	if ($forums_publics == "non") {
		echo "<br><input type='radio' name='change_accepter_forum' value='non' id='accepterforumnon' checked>";
		echo "<B><label for='accepterforumnon'> pas de forum</label></B>";
	} else {
		echo "<br><input type='radio' name='change_accepter_forum' value='non' id='accepterforumnon'>";
		echo "<label for='accepterforumnon'> pas de forum</label>";
	}

	echo "<p align='right'><input type='submit' name='Changer' class='fondo' value='Changer' STYLE='font-size:10px'></p>\n";
	echo "</form>";

	echo fin_block();
	
	if ($statut_article == 'publie' AND $connect_statut=='0minirezo' AND acces_rubrique($id_rubrique)) {
		$req = "SELECT count(*) FROM spip_forum WHERE id_article=$id_article AND statut IN ('publie', 'off', 'prop')";
		if ($row = mysql_fetch_row(spip_query($req))) {
			$nb_forums = $row[0];
			if ($nb_forums) {
				icone_horizontale("Suivi du forum public&nbsp;: $nb_forums&nbsp;contribution(s)", "articles_forum.php3?id_article=$id_article", "suivi-forum-24.gif", "");
			}
		}
	}

	echo "<br>\n";

	//
	// Petitions
	//

	//
	// Resultat formulaire
	//
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
	$petition = (mysql_num_rows($result_petition) > 0);

	while ($row = mysql_fetch_array($result_petition)) {
		$id_rubrique=$row["id_article"];
		$email_unique=$row["email_unique"];
		$site_obli=$row["site_obli"];
		$site_unique=$row["site_unique"];
		$message=$row["message"];
		$texte_petition=$row["texte"];
	}

	// boite petition ouverte ? si changement ou si petition activee :
	// du coup pas besoin de changer le titre de la boite petition
	if ($change_petition || $petition)
		$visible = true;
	else
		$visible = false;

	echo "<center><table width='100%' cellpadding='2' border='1' class='hauteur'>\n";
	echo "<tr><td width='100%' align='left' bgcolor='#FFCC66'>\n";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#333333'><b>\n";
	if ($visible)
		echo bouton_block_visible("petition");
	else
		echo bouton_block_invisible("petition");

	echo "P&Eacute;TITION";
	echo "</b></font></td></tr></table></center>";

	if ($visible)
		echo debut_block_visible("petition");
	else
		echo debut_block_invisible("petition");


	echo "\n<FORM ACTION='articles.php3' METHOD='post'>";
	echo "\n<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";

	if ($petition){
		echo "<input type='radio' name='change_petition' value='on' id='petitionon' checked>";
		echo "<B><label for='petitionon'>P&eacute;tition activ&eacute;e</label></B>";
		$query_signatures = "SELECT COUNT(*) AS nb FROM spip_signatures WHERE id_article=$id_article";
		$result = mysql_fetch_array(spip_query($query_signatures));
		if ($result['nb'] > 0) {
			echo "<p><font size=1><a href='controle_petition.php3?id_article=$id_article'>".$result['nb']." signatures</a></font>\n";
		}

		echo "<P><FONT SIZE=1>";
		if ($email_unique=="oui")
			echo "<input type='checkbox' name='email_unique' value='oui' id='emailunique' checked>";
		else
			echo "<input type='checkbox' name='email_unique' value='oui' id='emailunique'>";
		echo " <label for='emailunique'>une seule signature par adresse email</label><BR>";
		if ($site_obli=="oui")
			echo "<input type='checkbox' name='site_obli' value='oui' id='siteobli' checked>";
		else
			echo "<input type='checkbox' name='site_obli' value='oui' id='siteobli'>";
		echo " <label for='siteobli'>indiquer obligatoirement un site Web</label><BR>";
		if ($site_unique=="oui")
			echo "<input type='checkbox' name='site_unique' value='oui' id='siteunique' checked>";
		else
			echo "<input type='checkbox' name='site_unique' value='oui' id='siteunique'>";
		echo " <label for='siteunique'>une seule signature par site Web</label><BR>";
		if ($message=="oui")
			echo "<input type='checkbox' name='message' value='oui' id='message' checked>";
		else
			echo "<input type='checkbox' name='message' value='oui' id='message'>";
		echo " <label for='message'>possibilit&eacute; d'envoyer un message</label>";
		
		echo "<P>Descriptif de la p&eacute;tition&nbsp;:<BR>";
		echo "<TEXTAREA NAME='texte_petition' CLASS='forml' ROWS='4' COLS='10' wrap=soft>";
		echo $texte_petition;
		echo "</TEXTAREA></FONT><P>\n";

	}
	else {
		echo "<input type='radio' name='change_petition' value='on' id='petitionon'>";
		echo "<label for='petitionon'>Activer la p&eacute;tition</label>";
	}
	if (!$petition){
		echo "<br><input type='radio' name='change_petition' value='off' id='petitionoff' checked>";
		echo "<B><label for='petitionoff'>Pas de p&eacute;tition</label></B>";
	}else{
		echo "<br><input type='radio' name='change_petition' value='off' id='petitionoff'>";
		echo "<label for='petitionoff'>Supprimer la p&eacute;tition</label>";
	}
	
	echo "<P align='right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer' STYLE='font-size:10px'>";
	echo "</FORM>";
	echo fin_block();
	echo "<br>\n";
}


if ($connect_statut=="0minirezo" AND $options=="avancees"){
	
	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1, strlen($chapo));
	}
	
	echo "<center><table width='100%' cellpadding='2' border='1' class='hauteur'>\n";
	echo "<tr><td width='100%' align='left' bgcolor='#FFCC66'>\n";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#333333'><b>\n";
	echo bouton_block_invisible("virtuel");
	echo "REDIRECTION";
	echo aide ("artvirt");
	echo "</b></font></td></tr></table></center>";
	echo "<form action='articles.php3' method='post'>";
	echo "\n<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";
	echo "\n<INPUT TYPE='hidden' NAME='changer_virtuel' VALUE='oui'>";

	if (strlen($virtuel) != 0) { 
		echo "<INPUT TYPE='text' NAME='virtuel' CLASS='forml' style='font-size:9px;' VALUE=\"$virtuel\" SIZE='40'><br>";
	}

	echo debut_block_invisible("virtuel");
	if (strlen($virtuel) == 0) { 
		$virtuel = "http://";
		echo "<INPUT TYPE='text' NAME='virtuel' CLASS='forml' style='font-size:9px;' VALUE=\"$virtuel\" SIZE='40'><br>";
	}
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
	echo "(<b>Article virtuel&nbsp;:</b> article r&eacute;f&eacute;renc&eacute; dans votre site SPIP, mais redirig&eacute; vers une autre URL.)";
	echo "</font>";
	echo "<div align='right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer' STYLE='font-size:10px'></div>";
	echo "</form>";
	echo fin_block();
}


if ($boite_ouverte) {
	fin_boite_info();
}



//
// Afficher les boutons de creation d'article et de breve
//
debut_raccourcis();

icone_horizontale("Tous vos articles", "articles_page.php3", "article-24.gif");

if ($connect_statut == '0minirezo') {
	$retour = urlencode($clean_link->getUrl());

	icone_horizontale("Cr&eacute;er un nouvel auteur et l'associer &agrave; cet article", "auteur_infos.php3?new=oui&ajouter_id_article=$id_article&redirect=$retour", "redacteurs-24.gif", "creer.gif");

	$articles_mots = lire_meta('articles_mots');
	if ($articles_mots != "non")
		icone_horizontale("Cr&eacute;er un nouveau mot-cl&eacute; et le lier &agrave; cet article", "mots_edit.php3?new=oui&ajouter_id_article=$id_article&redirect=$retour", "mot-cle-24.gif", "creer.gif");
}


fin_raccourcis();




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
	my_sel("00","non connu",$mois);
	my_sel("01","janvier",$mois);
	my_sel("02","f&eacute;vrier",$mois);
	my_sel("03","mars",$mois);
	my_sel("04","avril",$mois);
	my_sel("05","mai",$mois);
	my_sel("06","juin",$mois);
	my_sel("07","juillet",$mois);
	my_sel("08","ao&ucirc;t",$mois);
	my_sel("09","septembre",$mois);
	my_sel("10","octobre",$mois);
	my_sel("11","novembre",$mois);
	my_sel("12","d&eacute;cembre",$mois);
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




debut_cadre_relief("article-24.gif");
echo "<CENTER>";
/*echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0>";
echo "<TR><td width='100%'>";*/


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
if (strlen($surtitre) > 1) {
	echo "<font face='arial,helvetica' size=3><b>";
	echo typo($surtitre);
	echo "</b></font>\n";
}
	gros_titre($titre, $logo_statut);
if (strlen($soustitre) > 1) {
	echo "<font face='arial,helvetica' size=3><b>";
	echo typo($soustitre);
	echo "</b></font>\n";
}


if (strlen($descriptif) > 1) {
	echo "<p><div align='left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;'>";
	echo "<font size=2 face='Verdana,Arial,Helvetica,sans-serif'>";
	echo "<b>Descriptif :</b> ";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</font>";
	echo "</div>";
}

if ($statut_article == 'prop') {
	echo "<P><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='red'><B>Article propos&eacute; pour la publication. N'h&eacute;sitez pas &agrave; donner votre avis gr&acirc;ce au forum attach&eacute; &agrave; cet article (en bas de page).</B></FONT></P>";
}


echo "</td>";


if ($flag_editable) {
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";
	echo "<td  align='center'>";
	// Recuperer les donnees de l'article
	$query = "SELECT auteur_modif, UNIX_TIMESTAMP(date_modif) AS modification, UNIX_TIMESTAMP(NOW()) AS maintenant FROM spip_articles WHERE id_article='$id_article'";
	$result = spip_query($query);
	
	while ($row = mysql_fetch_array($result)) {
		$auteur_modif = $row["auteur_modif"];
		$modification = $row["modification"];
		$maintenant = $row["maintenant"];
		
		$date_diff = floor(($maintenant - $modification)/60);
		
		if ($date_diff >= 0 AND $date_diff < 60 AND $auteur_modif > 0 AND $auteur_modif != $connect_id_auteur) {
			$query_auteur = "SELECT * FROM spip_auteurs WHERE id_auteur='$auteur_modif'";
			$result_auteur = spip_query($query_auteur);
			while ($row_auteur = mysql_fetch_array($result_auteur)) {
				$nom_auteur_modif = $row_auteur["nom"];
			}
			icone("Modifier cet article", "articles_edit.php3?id_article=$id_article", "warning-24.gif", "");
			echo "<font face='arial,helvetica,sans-serif' size=1>$nom_auteur_modif a travaill&eacute; sur cet article il y a $date_diff minutes</font>";
			echo aide("artmodif");
		}
		else {
			icone("Modifier cet article", "articles_edit.php3?id_article=$id_article", "article-24.gif", "edit.gif");
		}
	}
	

	echo "</td>";
}
echo "</tr></table>\n";



echo "<P align=left>";
echo "<FONT FACE='Georgia,Garamond,Times,serif'>";


//
// Affichage date redac et date publi
//

if ($flag_editable AND ($options == 'avancees' OR $statut_article == 'publie')) {
	debut_cadre_enfonce();

	echo "<FORM ACTION='articles.php3' METHOD='GET'>";
	echo "<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";

	if ($statut_article == 'publie') {	
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND=''>";
		echo "<TR><TD BGCOLOR='$couleur_foncee' COLSPAN=2><FONT SIZE=1 COLOR='#FFFFFF'><B>DATE DE PUBLICATION EN LIGNE :";
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
		echo "<INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer'>";
		echo "</TD></TR></TABLE>";
	}
	else {
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND=''>";
		echo "<TR><TD BGCOLOR='$couleur_foncee'><FONT SIZE=1 COLOR='#FFFFFF' face='Verdana,Arial,Helvetica,sans-serif'><b>DATE DE CREATION DE L'ARTICLE :</b> ";
		echo "<B><font color='black'>".majuscules(affdate($date))."</font></B></FONT>".aide('artdate')."</TD></TR>";
		echo "</TABLE>";
	}
	
	if (($options == 'avancees' AND $articles_redac != "non") OR ("$annee_redac-$mois_redac-$jour_redac" != "0000-00-00")) {
		echo "<P><TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND=''>";
		echo "<TR><TD BGCOLOR='#cccccc' COLSPAN=2><FONT SIZE=2 COLOR='#000000'>";
		if ("$annee_redac-$mois_redac-$jour_redac" != "0000-00-00") $date_affichee = " : ".majuscules(affdate($date_redac));
		echo bouton_block_invisible('dateredac');
		echo "<B>DATE DE PUBLICATION ANT&Eacute;RIEURE$date_affichee</B></FONT></TD></TR></table>";
		echo debut_block_invisible('dateredac');
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND=''>";
		echo "<TR><TD ALIGN='left'>";
		if ("$annee_redac-$mois_redac-$jour_redac" == "0000-00-00") {
			echo "<INPUT TYPE='radio' NAME='avec_redac' VALUE='non' id='on' checked>  <B><label for='on'>Ne pas afficher de date de publication ant&eacute;rieure.</label></B>";
			echo "<BR><INPUT TYPE='radio' NAME='avec_redac' VALUE='oui' id='off'>";
			echo " <label for='off'>Afficher la date de publication ant&eacute;rieure.</label> ";
			
			echo "<INPUT TYPE='hidden' NAME='jour_redac' VALUE=\"1\">";
			echo "<INPUT TYPE='hidden' NAME='mois_redac' VALUE=\"1\">";
			echo "<INPUT TYPE='hidden' NAME='annee_redac' VALUE=\"0\">";
		}
		else{
			echo "<INPUT TYPE='radio' NAME='avec_redac' VALUE='non' id='on'>  <label for='on'>Ne pas afficher de date de publication ant&eacute;rieure.</label>";
			echo "<BR><INPUT TYPE='radio' NAME='avec_redac' VALUE='oui' id='off' checked>";
			echo " <B><label for='off'>Afficher :</label></B> ";
			
			echo "<SELECT NAME='jour_redac' SIZE=1 CLASS='fondl'>";
			afficher_jour($jour_redac);
			echo "</SELECT> &nbsp;";
			echo "<SELECT NAME='mois_redac' SIZE=1 CLASS='fondl'>";
			afficher_mois($mois_redac);
			echo "</SELECT> &nbsp;";
			echo "<INPUT TYPE='text' NAME='annee_redac' CLASS='fondl' VALUE=\"$annee_redac\" SIZE='5'>";
		}
		echo "</TD><TD ALIGN='right'>";
		echo "<INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer'>";
		echo aide ("artdate-redac");
		echo "</TD></TR>";
		echo fin_block();
		echo "</TABLE>";
	
	}

	echo "</FORM>";
	fin_cadre_enfonce();
}

if (!$flag_editable AND $statut_article == 'publie') {
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
echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'><B>LES AUTEURS</B></FONT>";
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
	
	$nouv_auteur = mysql_insert_id();
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
	while ($row = mysql_fetch_array($result)) {
		$table_auteurs[] = $row["nom"];
		$table_ids[] = $row["id_auteur"];
	}
	$resultat = mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
	debut_boite_info();
	if (!$resultat) {
		echo "<B>Aucun r&eacute;sultat pour \"$cherche_auteur\".</B><BR>";
	}
	if (count($resultat) == 1) {
		$ajout_auteur = 'oui';
		list(, $nouv_auteur) = each($resultat);
		echo "<B>L'auteur suivant a &eacute;t&eacute; ajout&eacute; &agrave; l'article :</B><BR>";
		$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$nouv_auteur";
		$result = spip_query($query);
		echo "<UL>";
		while ($row = mysql_fetch_array($result)) {
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
			echo "<B>Plusieurs auteurs trouv&eacute;s pour \"$cherche_auteur\":</B><BR>";
			$query = "SELECT * FROM spip_auteurs WHERE id_auteur IN ($les_auteurs) ORDER BY nom";
			$result = spip_query($query);
			echo "<UL>";
			while ($row = mysql_fetch_array($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];
	
				echo "<LI><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B><FONT SIZE=3>$nom_auteur</FONT></B>";
			
				if ($email_auteur) echo " ($email_auteur)";
				echo " | <A HREF=\"articles.php3?id_article=$id_article&ajout_auteur=oui&nouv_auteur=$id_auteur\">Ajouter cet auteur</A>";
			
				if (trim($bio_auteur)) {
					echo "<BR><FONT SIZE=1>".propre(couper($bio_auteur, 100))."</FONT>\n";
				}
				echo "</FONT><p>\n";
			}
			echo "</UL>";
		}
	}
	else {
		echo "<B>Trop de r&eacute;sultats pour \"$cherche_auteur\" ; veuillez affiner la recherche.</B><BR>";
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

if (mysql_num_rows($result)) {
	$ifond = 0;

	echo "\n<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''>\n";
	while ($row = mysql_fetch_array($result)) {
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
		if ($result2) list($nombre_articles) = mysql_fetch_row($result2);
		else $nombre_articles = 0;

		$ifond = $ifond ^ 1;
		$couleur = ($ifond) ? '#FFFFFF' : $couleur_claire;

		$url_auteur = "auteurs_edit.php3?id_auteur=$id_auteur";

		echo "<TR BGCOLOR='$couleur' WIDTH=\"100%\">";
		echo "<TD WIDTH=23>";
		echo "<A HREF=\"$url_auteur\">";
		switch ($statut_auteur) {
		case "0minirezo":
			echo "<img src='img_pack/bonhomme-noir.gif' alt='Admin' width='23' height='12' border='0'>";
			break;					
		case "2redac":
		case "1comite":
			echo "<img src='img_pack/bonhomme-bleu.gif' alt='Admin' width='23' height='12' border='0'>";
			break;					
		case "5poubelle":
			echo "<img src='img_pack/bonhomme-rouge.gif' alt='Admin' width='23' height='12' border='0'>";
			break;					
		case "nouveau":
			echo "&nbsp;";
			break;
		default:
			echo "&nbsp;";
		}
		echo "</A>";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		echo "<A HREF=\"$url_auteur\"$bio_auteur>$nom_auteur</A>";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		echo bouton_imessage($id_auteur)."&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		if ($email_auteur) echo "<A HREF='mailto:$email_auteur'>email</A>";
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial2'>";
		if ($url_site_auteur) echo "<A HREF='$url_site_auteur'>site</A>";
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial2' ALIGN='right'>";
		if ($nombre_articles > 1) echo "$nombre_articles articles";
		else if ($nombre_articles == 1) echo "1 article";
		else echo "&nbsp;";
		echo "</TD>\n";

		echo "<TD CLASS='arial1' align='right'>";
		if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo') AND $options == 'avancees') {
			echo "<A HREF='articles.php3?id_article=$id_article&supp_auteur=$id_auteur'>Retirer l'auteur</A>";
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

	if (mysql_num_rows($result) > 0) {

		echo "<FORM ACTION='articles.php3' METHOD='post'>";
		echo "<DIV align=right><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B>AJOUTER UN AUTEUR : &nbsp; </B></FONT>\n";
		echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";

		if (mysql_num_rows($result) > 80 AND $flag_mots_ressemblants) {
			echo "<INPUT TYPE='text' NAME='cherche_auteur' CLASS='fondl' VALUE='' SIZE='20'>";
			echo " <INPUT TYPE='submit' NAME='Chercher' VALUE='Chercher' CLASS='fondo'>";
		}
		else {
			echo "<INPUT TYPE='Hidden' NAME='ajout_auteur' VALUE='oui'>";
			echo "<SELECT NAME='nouv_auteur' SIZE='1' STYLE='WIDTH=150' CLASS='fondl'>";
			$group = false;
			$group2 = false;
	
			while($row=mysql_fetch_array($result)) {
				$id_auteur = $row["id_auteur"];
				$nom = $row["nom"];
				$email = $row["email"];
				$statut = $row["statut"];
	
				$statut=ereg_replace("0minirezo", "Administrateur", $statut);
				$statut=ereg_replace("1comite", "R&eacute;dacteur", $statut);
				$statut=ereg_replace("2redac", "R&eacute;dacteur", $statut);
				$statut=ereg_replace("5poubelle", "Effac&eacute;", $statut);
	
				$premiere = strtoupper(substr(trim($nom), 0, 1));
	
				if ($connect_statut != '0minirezo') {
					if ($p = strpos($email, '@')) $email = substr($email, 0, $p).'@...';
				}
	
				if ($statut != $statut_old) {
					echo "\n<OPTION VALUE=\"x\">";
					echo "\n<OPTION VALUE=\"x\"> $statut".'s';
				}
			
				if ($premiere != $premiere_old AND ($statut != 'Administrateur' OR !$premiere_old)) {
					echo "\n<OPTION VALUE=\"x\">";
				}
	
				$texte_option = couper("$nom ($email) ", 40);
				echo "\n<OPTION VALUE=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;$texte_option";
				$statut_old = $statut;
				$premiere_old = $premiere;
			}
			
			echo "</SELECT>";
			echo " <INPUT TYPE='submit' NAME='Ajouter' VALUE='Ajouter' CLASS='fondo'>";
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

	echo "<B>Cet article est :</B> ";

	echo "<SELECT NAME='statut_nouv' SIZE='1' CLASS='fondl' onChange='change_bouton(this)'>";

	echo "<OPTION" . mySel("prepa", $statut_article) .">en cours de r&eacute;daction\n";
	echo "<OPTION" . mySel("prop", $statut_article) . ">propos&eacute; &agrave; l'&eacute;valuation\n";
	echo "<OPTION" . mySel("publie", $statut_article) . ">publi&eacute; en ligne\n";
	echo "<OPTION" . mySel("poubelle", $statut_article) . ">&agrave; la poubelle\n";
	echo "<OPTION" . mySel("refuse", $statut_article) . ">refus&eacute;\n";

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

	echo "<INPUT TYPE='submit' NAME='Modifier' VALUE='Modifier' CLASS='fondo'>";
	echo aide ("artstatut");
	echo "</CENTER>";
	fin_cadre_relief();
	echo "</FORM>";
}



//////////////////////////////////////////////////////
// Corps de l'article
//

echo "\n\n<DIV align=justify>";

if (substr($chapo, 0, 1) == '=') {
	$chapo = substr($chapo, 1, strlen($chapo));
	debut_boite_info();
	echo "<B>Redirection.</b> ";
	echo "Cet article correspond &agrave; l'adresse&nbsp;:";
	echo "<center>$chapo</center>";
	fin_boite_info();
}
else {
	echo "<B>";
	echo justifier(propre($chapo));
	echo "</B>\n\n";
}

echo justifier(propre($texte));

if ($ps) {
	echo "\n\n<FONT SIZE=2><P align=justify><B>P.S.</B> ";
	echo justifier(propre($ps));
	echo "</FONT>";
}


if ($les_notes) {
	echo "\n\n<FONT SIZE=2>";
	echo justifier($les_notes);
	echo "</FONT>";
}


//
// Bouton "modifier cet article"
//

if ($flag_editable) {
echo "\n\n<div align=right>";
//	icone("Modifier cet article", "articles_edit.php3?id_article=$id_article", "article-24.gif", "edit.gif");

if ($date_diff >= 0 AND $date_diff < 60 AND $auteur_modif > 0 AND $auteur_modif != $connect_id_auteur) {
	$query_auteur = "SELECT * FROM spip_auteurs WHERE id_auteur='$auteur_modif'";
	$result_auteur = spip_query($query_auteur);
	while ($row_auteur = mysql_fetch_array($result_auteur)) {
		$nom_auteur_modif = $row_auteur["nom"];
	}
	icone("Modifier cet article", "articles_edit.php3?id_article=$id_article", "warning-24.gif", "");
	echo "<font face='arial,helvetica,sans-serif' size=1>$nom_auteur_modif a travaill&eacute; sur cet article il y a $date_diff minutes</font>";
	echo aide("artmodif");
}
else {
	icone("Modifier cet article", "articles_edit.php3?id_article=$id_article", "article-24.gif", "edit.gif");
}

echo "</div>";
}

/// Documents associes a l'article

afficher_documents_non_inclus($id_article, "article", $flag_editable);

//
// "Demander la publication"
//

if ($flag_auteur AND $statut_article == 'prepa') {
	echo "<P>";
	debut_cadre_relief();
	echo "<center>";
	echo "<B>Lorsque votre article est termin&eacute;,<br> vous pouvez proposer sa publication.</B>";
	echo aide ("artprop");
	bouton("Demander la publication de cet article", "articles.php3?id_article=$id_article&statut_nouv=prop");
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
	icone("Poster un message", "forum_envoi.php3?statut=prive&adresse_retour=".$forum_retour."&id_article=$id_article&titre_message=".urlencode($titre), "forum-interne-24.gif", "creer.gif");
echo "</div>";

echo "<P align='left'>";


$query_forum = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0";
$result_forum = spip_query($query_forum);
$total = 0;
if ($row = mysql_fetch_array($result_forum)) $total = $row["cnt"];

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

?>

