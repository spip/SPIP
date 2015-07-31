<?php

include ("inc.php3");

debut_page("Forum des administrateurs", "messagerie", "forum-admin");
debut_gauche();




//
// Afficher les boutons de creation d'article et de breve
//
if ($connect_statut == '0minirezo') {
	debut_raccourcis();
	
	icone_horizontale("Forum interne", "forum.php3", "forum-interne-24.gif", "rien.gif");
	//icone_horizontale("Forum des administrateurs", "forum_admin.php3", "forum-admin-24.gif", "rien.gif");

	$query_petition = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE date_heure > DATE_SUB(NOW(),INTERVAL 30 DAY)";
	$result_petition = spip_query($query_petition);
	if ($row = mysql_fetch_array($result_petition)) {
		$nombre_petition = $row['cnt'];
	}
	if ($nombre_petition > 0) {
		echo "<p>";
		icone_horizontale("$nombre_petition messages de forums", "controle_forum.php3", "suivi-forum-24.gif", "rien.gif");
	}

	$query_petition = "SELECT COUNT(*) AS cnt FROM spip_signatures WHERE (statut='publie' OR statut='poubelle')";
	$result_petition = spip_query($query_petition);
	if ($row = mysql_fetch_array($result_petition)){
		$nombre_petition = $row['cnt'];
	}
	if ($nombre_petition > 0) {
		echo "<p>";
		icone_horizontale("$nombre_petition signatures de p&eacute;titions", "controle_petition.php3", "suivi-forum-24.gif", "rien.gif");
	}
	
	
	fin_raccourcis();
}



debut_droite();
gros_titre("Forum priv&eacute; des administrateurs");


if ($connect_statut == "0minirezo"){

	echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
	if (!$debut) $debut = 0;

	$query_forum = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='privadm' AND id_parent=0";
 	$result_forum = spip_query($query_forum);
 	$total = 0;
 	if ($row = mysql_fetch_array($result_forum)) $total = $row['cnt'];


	if ($total > 10) {
		echo "<p>";
		//echo "<CENTER>";
		for ($i = 0; $i < $total; $i = $i + 10){
			if ($i > 0) echo " | ";
			if ($i == $debut)
				echo "<FONT SIZE=3><B>$i</B></FONT>";
			else
				echo "<A HREF='forum_admin.php3?debut=$i'>$i</A>";
		}
		//echo "</CENTER>";
	}


	echo "<p><div align='center'>";
	icone ("Poster un message", "forum_envoi.php3?statut=privadm&adresse_retour=forum_admin.php3&titre_message=Nouveau+message", "forum-interne-24.gif", "creer.gif");
	echo "</div>";

	echo "<P align='left'>";


	$query_forum="SELECT * FROM spip_forum WHERE statut='privadm' AND id_parent=0 ORDER BY date_heure DESC LIMIT $debut,10";
	$result_forum=spip_query($query_forum);

	afficher_forum($result_forum,"forum_admin.php3");
} else {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
}
	
echo "</FONT>";




fin_page();

?>

