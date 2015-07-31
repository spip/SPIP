<?php

include ("inc.php3");


debut_page(_T('titre_page_message_edit'), "asuivre", "messagerie");
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
	$titre = entites_html($row["titre"]);
	$texte = entites_html($row["texte"]);
	$type = $row["type"];
	$statut = $row["statut"];
	$page = $row["page"];
	$rv = $row["rv"];
	$id_auteur = $row["id_auteur"];
	if (!($id_auteur == $connect_id_auteur OR ($type == "affich" AND $connect_statut == "0minirezo"))) break;


	echo "<FORM ACTION='message.php3?id_message=$id_message' METHOD='post'>";
	if ($type == 'normal') $le_type = _T('bouton_envoi_message_02');
	if ($type == 'pb') $le_type = _T('bouton_pense_bete');
	if ($type == 'affich') $le_type = _T('bouton_annonce');

	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='green'><b>$le_type</b></font><p>";
	
	if ($type == "affich")
		echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1 color='red'>"._T('texte_message_edit')."</font><p>";
	

	echo "<INPUT TYPE='Hidden' NAME='modifier_message' VALUE=\"oui\">";
	echo "<INPUT TYPE='Hidden' NAME='id_message' VALUE=\"$id_message\">";

	echo _T('texte_titre_obligatoire')."<BR>";
	echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40'><P>";

	if ($ajouter_auteur) {
		echo "<B>"._T('info_nom_destinataire')."</B><BR>";
		echo "<INPUT TYPE='text' CLASS='formo' NAME='cherche_auteur' VALUE='' SIZE='40'><P>";
	}

	echo "<B>"._T('info_texte_message_02')."</B><BR>";
	echo "<TEXTAREA NAME='texte' ROWS='20' CLASS='formo' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA><P>\n";

	echo "<P ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</FORM>";
}

fin_page();

?>
