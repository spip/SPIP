<?

include ("inc.php3");


debut_page("R&eacute;diger un message");
debut_gauche();
debut_droite();


if ($new == "oui") {
	$mydate = date("YmdHis", time() - 2 * 24 * 3600);
	$query = "DELETE FROM spip_messages WHERE (statut = 'redac') && (date_heure < $mydate)";
	$result = mysql_query($query);

	if ($type == 'pb') $statut = 'publie';
	else $statut = 'redac';

	$query = "INSERT INTO spip_messages (titre, date_heure, statut, type, id_auteur) VALUES ('Nouveau message', NOW(), '$statut', '$type', $connect_id_auteur)";
	$result = mysql_query($query);
	$id_message = mysql_insert_id();
	
	if ($rv) {
		mysql_query("UPDATE spip_messages SET rv='oui', date_heure='$rv 12:00:00' WHERE id_message = $id_message");
	}

	if ($type != "affich"){
		mysql_query("INSERT INTO spip_auteurs_messages (id_auteur,id_message,vu) VALUES ('$connect_id_auteur','$id_message','oui')");
		if ($dest) {
			mysql_query("INSERT INTO spip_auteurs_messages (id_auteur,id_message,vu) VALUES ('$dest','$id_message','non')");
		}
		else if ($type == 'normal') $ajouter_auteur = true;
	}
}


$query = "SELECT * FROM spip_messages WHERE id_message=$id_message";
$result = mysql_query($query);

if ($row = mysql_fetch_array($result)) {
	$id_message = $row[0];
	$date_heure = $row["date_heure"];
	$titre = htmlspecialchars($row["titre"]);
	$texte = htmlspecialchars($row["texte"]);
	$type = $row["type"];
	$statut = $row["statut"];
	$page = $row["page"];
	$rv = $row["rv"];
	$id_auteur = $row["id_auteur"];
	if (!($id_auteur == $connect_id_auteur OR ($type == "affich" AND $connect_statut == "0minirezo"))) break;


	echo "<FORM ACTION='message.php3?id_message=$id_message' METHOD='post'>";
	if ($type == 'normal') $le_type = "ENVOYER UN MESSAGE";
	if ($type == 'pb') $le_type = "PENSE-B&Ecirc;TE &Agrave; USAGE PERSONNEL";
	if ($type == 'affich') $le_type = "MESSAGE D'ACCUEIL";

	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='green'><b>$le_type</b></font><p>";
	
	if ($type == "affich")
		echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1 color='red'>Attention&nbsp;: ce message peut &ecirc;tre modifi&eacute; par tous les administrateurs du site, et est visible par tous les r&eacute;dacteurs. N'utilisez ce type de message que pour exposer des &eacute;v&eacute;nements importants de la vie du site.</font><p>";
	

	echo "<INPUT TYPE='Hidden' NAME='modifier_message' VALUE=\"oui\">";
	echo "<INPUT TYPE='Hidden' NAME='id_message' VALUE=\"$id_message\">";

	echo "<B>Titre</B> [Obligatoire]<BR>";
	echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40'><P>";

	if ($ajouter_auteur) {
		echo "<B>Nom du destinataire</B><BR>";
		echo "<INPUT TYPE='text' CLASS='formo' NAME='cherche_auteur' VALUE='' SIZE='40'><P>";
	}

	echo "<B>Texte du message</B><BR>";
	echo "<TEXTAREA NAME='texte' ROWS='20' CLASS='formo' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA><P>\n";

	echo "<P ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</FORM>";
}

fin_page();

?>
