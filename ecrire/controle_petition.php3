<?

include ("inc.php3");



debut_page("Suivi des p&eacute;titions");
debut_gauche();

$query_petition = "SELECT COUNT(*) FROM spip_forum WHERE date_heure > DATE_SUB(NOW(),INTERVAL 30 DAY)";
$result_petition = mysql_query($query_petition);

if ($row = mysql_fetch_array($result_petition)) {
	$nombre_petition = $row[0];
}

if ($nombre_petition > 0) {
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
	echo "<A HREF='controle_forum.php3'><IMG SRC='puce.gif' BORDER=0> $nombre_petition messages de forums</A>";
	echo "</FONT><P>";
}



debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo "<P align=left>".propre("La page de {suivi des p&eacute;titions} vous permet de suivre les signatures de vos p&eacute;titions.");

echo aide ("suiviforum");
echo "</FONT>";

fin_boite_info();




debut_droite();


function controle_forum($request,$adresse_retour) {
	global $debut;
	global $couleur_foncee;
	
	$nb_forum[$compteur_forum] = mysql_num_rows($request);
	$i[$compteur_forum] = 1;
 	while($row=mysql_fetch_array($request)){
		$id_signature = $row[0];
		$id_article = $row[1];
		$date_time = $row[2];
		$nom_email= typo($row[3]);
		$ad_email = $row[4];
		$nom_site = typo($row[5]);
		$url_site = $row[6];
		$message = propre($row[7]);
		$statut = $row[8];
		
		
		
		echo "<P>";
		
		
	if ($statut=="poubelle"){
		echo "<TABLE WIDTH=100% CELLPADDING=2 CELLSPACING=0 BORDER=0><TR><TD BGCOLOR='#FF0000'>";
	}
		echo "<TABLE WIDTH=100% CELLPADDING=3 CELLSPACING=0><TR><TD BGCOLOR='$couleur_foncee'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#FFFFFF'><B>$nom_site / $nom_email</B></FONT></TD></TR>";
		echo "<TR><TD BGCOLOR='#FFFFFF'>";
		echo "<FONT SIZE=3 FACE='Georgia,Garamond,Times,serif'>";
				
		if ($statut=="publie"){
			echo "<A HREF='controle_petition.php3?supp_petition=$id_signature&debut=$debut' onMouseOver=\"message$id_signature.src='IMG2/supprimer-message-on.gif'\" onMouseOut=\"message$id_signature.src='IMG2/supprimer-message-off.gif'\"><IMG SRC='IMG2/supprimer-message-off.gif' WIDTH=64 HEIGHT=52 NAME='message$id_signature' ALIGN='right' BORDER=0></A>";
		
		
		}
		if ($statut=="poubelle"){
			echo "<A HREF='controle_petition.php3?add_petition=$id_signature&debut=$debut' onMouseOver=\"message$id_signature.src='IMG2/valider-message-on.gif'\" onMouseOut=\"message$id_signature.src='IMG2/valider-message-off.gif'\"><IMG SRC='IMG2/valider-message-off.gif' WIDTH=60 HEIGHT=52 NAME='message$id_signature' ALIGN='right' BORDER=0></A>";
		
		
		}
		
		
		echo "<FONT SIZE=2>".affdate($date_time)."</FONT><BR>";
		if ($statut=="poubelle"){
			echo "<FONT SIZE=1 COLOR='red'>MESSAGE EFFAC&Eacute;</FONT><BR>";
		}
		if (strlen($url_site)>6 AND strlen($nom_site)>0){
			echo "<FONT SIZE=1>SITE WEB :</FONT> <A HREF='$url_site'>$nom_site</A><BR>";
		}
		if (strlen($ad_email)>0){
			echo "<FONT SIZE=1>ADRESSE EMAIL :</FONT> <A HREF='mailto:$ad_email'>$ad_email</A><BR>";
		}
		if (strlen($message)>0) echo "<P>$message";
		
		$query_article="SELECT * FROM spip_articles WHERE id_article=$id_article";
		$result_article=mysql_query($query_article);
 		while($row=mysql_fetch_array($result_article)){
			$id_article = $row[0];
			$titre = typo($row["titre"]);
		}
		echo "<P align='right'><A HREF='../article.php3?id_article=$id_article'>$titre</A>";
		

		echo "</FONT></TD></TR></TABLE>";
	if ($statut=="poubelle"){
		echo "</TD></TR></TABLE>";
	}

	}
}

  
echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
 
if ($connect_statut == "0minirezo") {

	if ($supp_petition){
		$query_forum = "UPDATE spip_signatures SET statut='poubelle' WHERE id_signature=$supp_petition";
 		$result_forum = mysql_query($query_forum);
	}

	if ($add_petition){
		$query_forum = "UPDATE spip_signatures SET statut='publie' WHERE id_signature=$add_petition";
 		$result_forum = mysql_query($query_forum);
	}

	if (!$debut) $debut = 0;

	$query_forum = "SELECT COUNT(*) FROM spip_signatures WHERE (statut='publie' OR statut='poubelle') AND date_time>DATE_SUB(NOW(),INTERVAL 30 DAY)";
 	$result_forum = mysql_query($query_forum);
 	$total = 0;
 	if ($row = mysql_fetch_array($result_forum)) $total = $row[0];

	if ($total > 10) {
		echo "<CENTER>";
		for ($i = 0; $i < $total; $i = $i + 10){
			$y = $i + 9;
			if ($i == $debut)
				echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
			else
				echo "[<A HREF='controle_petition.php3?debut=$i'>$i-$y</A>] ";
		}
		echo "</CENTER>";
	}

	$query_forum = "DELETE FROM spip_signatures WHERE NOT (statut='publie' OR statut='poubelle') AND date_time<DATE_SUB(NOW(),INTERVAL 10 DAY)";
 	$result_forum = mysql_query($query_forum);

	$query_forum = "SELECT * FROM spip_signatures WHERE (statut='publie' OR statut='poubelle') ORDER BY date_time DESC LIMIT $debut,10";
 	$result_forum = mysql_query($query_forum);
	controle_forum($result_forum, "forum.php3");
}
else {
	echo "<B>Vous n'avez pas acc&egrave;s &agrave; cette page.</B>";
}	
		

echo "</FONT>";


fin_page();


?>

