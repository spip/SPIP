<?php

include ("inc.php3");



debut_page("Suivi des p&eacute;titions", "messagerie", "suivi-petition");
debut_gauche();

debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo propre("La page de {suivi des p&eacute;titions} vous permet de suivre les signatures de vos p&eacute;titions.");

echo aide ("suiviforum");
echo "</FONT>";

fin_boite_info();



//
// Afficher les boutons de creation d'article et de breve
//
if ($connect_statut == '0minirezo') {
	debut_cadre_enfonce();
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
	echo "<b>RACCOURCIS :</b><p>";
	
	
	icone_horizontale("Forum interne", "forum.php3", "forum-interne-24.png", "rien.gif");
	icone_horizontale("Forum des administrateurs", "forum_admin.php3", "forum-admin-24.png", "rien.gif");
		

	$query_petition = "SELECT COUNT(*) FROM spip_forum WHERE date_heure > DATE_SUB(NOW(),INTERVAL 30 DAY)";
	$result_petition = spip_query($query_petition);
	if ($row = mysql_fetch_array($result_petition)) {
		$nombre_petition = $row[0];
	}
	if ($nombre_petition > 0) {
		echo "<p>";
		icone_horizontale("$nombre_petition messages de forums", "controle_forum.php3", "suivi-forum-24.png", "rien.gif");
	}


	/*
	$query_petition = "SELECT COUNT(*) FROM spip_signatures WHERE (statut='publie' OR statut='poubelle')";
	$result_petition = spip_query($query_petition);
	if ($row = mysql_fetch_array($result_petition)){
		$nombre_petition = $row[0];
	}
	if ($nombre_petition > 0) {
		echo "<p>";
		icone_horizontale("$nombre_petition signatures de p&eacute;titions", "controle_petition.php3", "suivi-forum-24.png", "rien.gif");
	}
	*/
	
	
	
	echo "</font>";
	fin_cadre_enfonce();
}



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
			icone ("Supprimer cette signature", "controle_petition.php3?supp_petition=$id_signature&debut=$debut", "forum-interne-24.png", "supprimer.gif", "right");
		}
		if ($statut=="poubelle"){
			icone ("Valider cette signature", "controle_petition.php3?add_petition=$id_signature&debut=$debut", "forum-interne-24.png", "creer.gif", "right");
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
		$result_article=spip_query($query_article);
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
	gros_titre("Suivi des p&eacute;titions");

	if ($supp_petition){
		$query_forum = "UPDATE spip_signatures SET statut='poubelle' WHERE id_signature=$supp_petition";
 		$result_forum = spip_query($query_forum);
	}

	if ($add_petition){
		$query_forum = "UPDATE spip_signatures SET statut='publie' WHERE id_signature=$add_petition";
 		$result_forum = spip_query($query_forum);
	}

	if (!$debut) $debut = 0;

	$query_forum = "SELECT COUNT(*) FROM spip_signatures WHERE (statut='publie' OR statut='poubelle') AND date_time>DATE_SUB(NOW(),INTERVAL 180 DAY)";
 	$result_forum = spip_query($query_forum);
 	$total = 0;
 	if ($row = mysql_fetch_array($result_forum)) $total = $row[0];

	if ($total > 10) {
		echo "<p>";
		for ($i = 0; $i < $total; $i = $i + 10){
			$y = $i + 9;
			if ($i == $debut)
				echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
			else
				echo "[<A HREF='controle_petition.php3?debut=$i'>$i-$y</A>] ";
		}
	}

	$query_forum = "DELETE FROM spip_signatures WHERE NOT (statut='publie' OR statut='poubelle') AND date_time<DATE_SUB(NOW(),INTERVAL 10 DAY)";
 	$result_forum = spip_query($query_forum);

	$query_forum = "SELECT * FROM spip_signatures WHERE (statut='publie' OR statut='poubelle') ORDER BY date_time DESC LIMIT $debut,10";
 	$result_forum = spip_query($query_forum);
	controle_forum($result_forum, "forum.php3");
}
else {
	echo "<B>Vous n'avez pas acc&egrave;s &agrave; cette page.</B>";
}	
		

echo "</FONT>";


fin_page();


?>

