<?php

include ("inc.php3");


// Droits
if ($new=='oui') {
	switch ($type) {
		case 'affich':
			$ok = ($connect_statut == '0minirezo');
			break;
		case 'pb':
		case 'rv':
			$ok = true;
			break;
		default:
			$ok = false;
	}
}

if (!$ok) {
	debut_page(_T('info_acces_refuse'));
	debut_gauche();
	debut_droite();
	echo "<b>"._T('avis_non_acces_message')."</b><p>";
	fin_page();
	exit;
}


function my_sel($num, $tex, $comp) {
	if ($num == $comp) {
		echo "<OPTION VALUE='$num' SELECTED>$tex\n";
	}
	else {
		echo "<OPTION VALUE='$num'>$tex\n";
	}
}

function afficher_mois($mois){
	my_sel("01", _T('date_mois_1'), $mois);
	my_sel("02", _T('date_mois_2'), $mois);
	my_sel("03", _T('date_mois_3'), $mois);
	my_sel("04", _T('date_mois_4'), $mois);
	my_sel("05", _T('date_mois_5'), $mois);
	my_sel("06", _T('date_mois_6'), $mois);
	my_sel("07", _T('date_mois_7'), $mois);
	my_sel("08", _T('date_mois_8'), $mois);
	my_sel("09", _T('date_mois_9'), $mois);
	my_sel("10", _T('date_mois_10'), $mois);
	my_sel("11", _T('date_mois_11'), $mois);
	my_sel("12", _T('date_mois_12'), $mois);
}

function afficher_annee($annee) {
	if ($annee < 1996) {
		echo "<OPTION VALUE='$annee' SELECTED>$annee\n";
	}
	for ($i=date("Y") - 1; $i < date("Y") + 3; $i++) {
		my_sel($i,$i,$annee);
	}
}

function afficher_jour($jour){
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		my_sel($i,$aff,$jour);
	}
}


debut_page(_T('titre_page_message_edit'), "redacteurs", "messagerie");
debut_gauche();
debut_droite();


if ($new == "oui") {
	$mydate = date("YmdHis", time() - 2 * 24 * 3600);
	$query = "DELETE FROM spip_messages WHERE (statut = 'redac') && (date_heure < $mydate)";
	$result = spip_query($query);

	if ($type == 'pb') $statut = 'publie';
	else $statut = 'redac';

	$query = "INSERT INTO spip_messages (titre, date_heure, statut, type, id_auteur) VALUES ('".addslashes(filtrer_entites(_T('texte_nouveau_message')))."', NOW(), '$statut', '$type', $connect_id_auteur)";
	$result = spip_query($query);
	$id_message = spip_insert_id();
	
	if ($rv) {
		spip_query("UPDATE spip_messages SET rv='oui', date_heure='$rv 12:00:00', date_fin= '$rv 13:00:00' WHERE id_message = $id_message");
	}

	if ($type != "affich"){
		spip_query("INSERT INTO spip_auteurs_messages (id_auteur,id_message,vu) VALUES ('$connect_id_auteur','$id_message','oui')");
		if ($dest) {
			spip_query("INSERT INTO spip_auteurs_messages (id_auteur,id_message,vu) VALUES ('$dest','$id_message','non')");
		}
		else if ($type == 'normal') $ajouter_auteur = true;
	}
}


$query = "SELECT * FROM spip_messages WHERE id_message=$id_message";
$result = spip_query($query);

if ($row = spip_fetch_array($result)) {
	$id_message = $row['id_message'];
	$date_heure = $row["date_heure"];
	$date_fin = $row["date_fin"];
	$titre = entites_html($row["titre"]);
	$texte = entites_html($row["texte"]);
	$type = $row["type"];
	$statut = $row["statut"];
	$page = $row["page"];
	$rv = $row["rv"];
	$expediteur = $row["id_auteur"];
		if (!($expediteur == $connect_id_auteur OR ($type == 'affich' AND $connect_statut == '0minirezo'))) die();

	echo "<div class='arial2'>";

	echo "<FORM ACTION='message.php3?id_message=$id_message' METHOD='post'>";
	if ($type == 'normal') {
		$le_type = _T('bouton_envoi_message_02');
		$logo = "message";
	}
	if ($type == 'pb') {
		$le_type = _T('bouton_pense_bete');
		$logo = "pense-bete";
	}
	if ($type == 'affich') {
		$le_type = _T('bouton_annonce');
		$logo = "annonce";
	}

	echo "<font face='Verdana,Arial,Sans,sans-serif' size=2 color='green'><b>$le_type</b></font><p>";
	
	if ($type == "affich")
		echo "<font face='Verdana,Arial,Sans,sans-serif' size=1 color='red'>"._T('texte_message_edit')."</font><p>";
	

	echo "<INPUT TYPE='Hidden' NAME='modifier_message' VALUE=\"oui\">";
	echo "<INPUT TYPE='Hidden' NAME='id_message' VALUE=\"$id_message\">";

	echo _T('texte_titre_obligatoire')."<BR>";
	echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40'>";

	if ($ajouter_auteur) {
		echo "<P><B>"._T('info_nom_destinataire')."</B><BR>";
		echo "<INPUT TYPE='text' CLASS='formo' NAME='cherche_auteur' VALUE='' SIZE='40'>";
	}

	//////////////////////////////////////////////////////
	// Fixer rendez-vous?
	//

		
		echo "<p />";
		
		if ($rv == "oui") $fonction = "rv.gif";
		else $fonction = "";

		debut_cadre_trait_couleur("$logo.gif", false, $fonction, _T('titre_rendez_vous'));
		echo "<input type='hidden' name='id_message' value='$id_message'>";
		echo "<input type='hidden' name='changer_rv' value='$id_message'>";
		if ($rv != "oui") {
			echo "<INPUT TYPE='radio' NAME='rv' VALUE='oui' id='rv_on' onClick=\"changeVisible(this.checked, 'heure-rv', 'block', 'none');\">";
			echo " <label for='rv_on'>"._T('item_afficher_calendrier')."</label> ";


			echo "<div id='heure-rv' style='display: block; padding-top: 4px; padding-left: 24px;'>";
			echo "<SELECT NAME='jour' SIZE=1 CLASS='fondl'>";
			afficher_jour(jour($date_heure));
			echo "</SELECT> ";
			echo "<SELECT NAME='mois' SIZE=1 CLASS='fondl'>";
			afficher_mois(mois($date_heure));
			echo "</SELECT> ";
			echo "<SELECT NAME='annee' SIZE=1 CLASS='fondl'>";
			afficher_annee(annee($date_heure));
			echo "</SELECT>\n";
			
			$heures_debut = heures($date_heure);
			$minutes_debut = minutes($date_heure);
			echo " &nbsp; <INPUT TYPE='text' CLASS='fondl' NAME='heures' VALUE=\"".$heures_debut."\" SIZE='3'>&nbsp;".majuscules(_T('date_mot_heures'))."&nbsp;";
			echo "<INPUT TYPE='text' CLASS='fondl' NAME='minutes' VALUE=\"$minutes_debut\" SIZE='3'> ";
			
			
			$heures_fin = heures($date_fin);
			$minutes_fin = minutes($date_fin);

			if ($date_fin == "0000-00-00 00:00:00") {
				$date_fin = $date_heure;
				$heures_fin = $heures_debut + 1;
			}
			
			if ($heures_fin >=24){
				$heures_fin = 23;
				$minutes_fin = 59;	
			}


			
			echo " <br> <img src='puce$spip_lang_rtl.gif' border='0'> &nbsp; ";
			
			echo "<SELECT NAME='jour_fin' SIZE=1 CLASS='fondl'>";
			afficher_jour(jour($date_fin));
			echo "</SELECT> ";
			echo "<SELECT NAME='mois_fin' SIZE=1 CLASS='fondl'>";
			afficher_mois(mois($date_fin));
			echo "</SELECT> ";
			echo "<SELECT NAME='annee_fin' SIZE=1 CLASS='fondl'>";
			afficher_annee(annee($date_fin));
			echo "</SELECT>\n";
			
			echo " &nbsp; <INPUT TYPE='text' CLASS='fondl' NAME='heures_fin' VALUE=\"".$heures_fin."\" SIZE='3'>&nbsp;".majuscules(_T('date_mot_heures'))."&nbsp;";
			echo "<INPUT TYPE='text' CLASS='fondl' NAME='minutes_fin' VALUE=\"".$minutes_fin."\" SIZE='3'> ";

			echo "</div>";


			echo " <br><INPUT TYPE='radio' NAME='rv' VALUE='non' CHECKED id='rv_off' onClick=\"changeVisible(this.checked, 'heure-rv', 'none', 'block');\">";
			echo " <B><label for='rv_off'>"._T('item_non_afficher_calendrier')."</label></B> ";
		}
		else {
			echo "<INPUT TYPE='radio' NAME='rv' VALUE='oui' CHECKED id='rv_on' onClick=\"changeVisible(this.checked, 'heure-rv', 'block', 'none');\">";
			echo " <b><label for='rv_on'>"._T('item_afficher_calendrier')."</label></b> ";

			echo "<div id='heure-rv' style='display: block; padding-top: 4px; padding-left: 24px;'>";
			echo "<SELECT NAME='jour' SIZE=1 CLASS='fondl'>";
			afficher_jour(jour($date_heure));
			echo "</SELECT> ";
			echo "<SELECT NAME='mois' SIZE=1 CLASS='fondl'>";
			afficher_mois(mois($date_heure));
			echo "</SELECT> ";
			echo "<SELECT NAME='annee' SIZE=1 CLASS='fondl'>";
			afficher_annee(annee($date_heure));
			echo "</SELECT>\n";
			
			$heures_debut = heures($date_heure);
			$minutes_debut = minutes($date_heure);
			echo " &nbsp; <INPUT TYPE='text' CLASS='fondl' NAME='heures' VALUE=\"".$heures_debut."\" SIZE='3'>&nbsp;".majuscules(_T('date_mot_heures'))."&nbsp;";
			echo "<INPUT TYPE='text' CLASS='fondl' NAME='minutes' VALUE=\"$minutes_debut\" SIZE='3'> ";
			
			$heures_fin = heures($date_fin);
			$minutes_fin = minutes($date_fin);
			
			if ($heures_fin >=24){
				$heures_fin = 23;
				$minutes_fin = 59;	
			}
			
			echo " <br> <img src='puce$spip_lang_rtl.gif' border='0'> &nbsp; ";
			
			echo "<SELECT NAME='jour_fin' SIZE=1 CLASS='fondl'>";
			afficher_jour(jour($date_fin));
			echo "</SELECT> ";
			echo "<SELECT NAME='mois_fin' SIZE=1 CLASS='fondl'>";
			afficher_mois(mois($date_fin));
			echo "</SELECT> ";
			echo "<SELECT NAME='annee_fin' SIZE=1 CLASS='fondl'>";
			afficher_annee(annee($date_fin));
			echo "</SELECT>\n";
			
			echo " &nbsp; <INPUT TYPE='text' CLASS='fondl' NAME='heures_fin' VALUE=\"".$heures_fin."\" SIZE='3'>&nbsp;".majuscules(_T('date_mot_heures'))."&nbsp;";
			echo "<INPUT TYPE='text' CLASS='fondl' NAME='minutes_fin' VALUE=\"".$minutes_fin."\" SIZE='3'> ";

			echo "</div>";

			echo " <p><INPUT TYPE='radio' NAME='rv' VALUE='non' id='rv_off' onClick=\"changeVisible(this.checked, 'heure-rv', 'none', 'block');\">";
			echo " <label for='rv_off'>"._T('item_non_afficher_calendrier')."</label> ";
		}

		fin_cadre_trait_couleur();

	echo "<p><B>"._T('info_texte_message_02')."</B><BR>";
	echo "<TEXTAREA NAME='texte' ROWS='20' CLASS='formo' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA><P>\n";

	echo "<P ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</FORM>";
	echo "</div>";
}

fin_page();

?>
