<?php

include ("inc.php3");


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
		spip_query("UPDATE spip_messages SET rv='oui', date_heure='$rv 12:00:00' WHERE id_message = $id_message");
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


	echo "<FORM ACTION='message.php3?id_message=$id_message' METHOD='post'>";
	if ($type == 'normal') $le_type = _T('bouton_envoi_message_02');
	if ($type == 'pb') $le_type = _T('bouton_pense_bete');
	if ($type == 'affich') $le_type = _T('bouton_annonce');

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

		debut_boite_info();
		echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='$couleur_foncee' colspan=2>";
		echo "<span class='serif2' color='#FFFFFF'><B>"._T('titre_rendez_vous')."</B></span>";
		echo "</td></tr></table>";
		echo "<input type='hidden' name='id_message' value='$id_message'>";
		echo "<input type='hidden' name='changer_rv' value='$id_message'>";
		if ($rv != "oui") {
			echo "<INPUT TYPE='radio' NAME='rv' VALUE='oui' id='rv_on'>";
			echo " <label for='rv_on'>"._T('item_afficher_calendrier')."</label> ";
			echo " <br><INPUT TYPE='radio' NAME='rv' VALUE='non' CHECKED id='rv_off'>";
			echo " <B><label for='rv_off'>"._T('item_non_afficher_calendrier')."</label></B> ";
		}
		else {
			echo "<INPUT TYPE='radio' NAME='rv' VALUE='oui' CHECKED id='rv_on'>";
			echo " <b><label for='rv_on'>"._T('item_afficher_calendrier')."</label></b> ";

			echo "<div style='text-align: center; padding: 4px;'><SELECT NAME='jour' SIZE=1 CLASS='fondl'>";
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
			echo "<br><INPUT TYPE='text' CLASS='fondl' NAME='heures' VALUE=\"".$heures_debut."\" SIZE='3'>&nbsp;".majuscules(_T('date_mot_heures'))."&nbsp;";
			echo "<INPUT TYPE='text' CLASS='fondl' NAME='minutes' VALUE=\"$minutes_debut\" SIZE='3'> ";
			
			$heures_fin = heures($date_fin);
			$minutes_fin = minutes($date_fin);
			
			if (($heures_fin * 60) + $minutes_fin < ($heures_debut * 60) + $minutes_debut) {
				$minutes_fin = $minutes_debut;
				$heures_fin = $heures_debut + 1;
			}
			if ($heures_fin >=24){
				$heures_fin = 23;
				$minutes_fin = 59;	
			}
			
			echo " &nbsp; <img src='puce$spip_lang_rtl.gif' border='0'> &nbsp; <INPUT TYPE='text' CLASS='fondl' NAME='heures_fin' VALUE=\"".$heures_fin."\" SIZE='3'>&nbsp;".majuscules(_T('date_mot_heures'))."&nbsp;";
			echo "<INPUT TYPE='text' CLASS='fondl' NAME='minutes_fin' VALUE=\"".$minutes_fin."\" SIZE='3'> ";

			echo "</div>";

			echo " <p><INPUT TYPE='radio' NAME='rv' VALUE='non' id='rv_off'>";
			echo " <label for='rv_off'>"._T('item_non_afficher_calendrier')."</label> ";
		}

		fin_boite_info();

	echo "<p><B>"._T('info_texte_message_02')."</B><BR>";
	echo "<TEXTAREA NAME='texte' ROWS='20' CLASS='formo' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA><P>\n";

	echo "<P ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</FORM>";
}

fin_page();

?>
