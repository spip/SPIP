<?php

include ("inc.php3");

debut_page("Forum interne", "messagerie", "forum-interne");
debut_gauche();



//
// Raccourcis
//
/*
	debut_raccourcis();
	// rien
	fin_raccourcis();
*/


debut_droite();

gros_titre("Forum interne");


echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
	if (!$debut) $debut = 0;

	$query_forum = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='privrac' AND id_parent=0";
 	$result_forum = spip_query($query_forum);
 	$total = 0;
 	if ($row = spip_fetch_array($result_forum)) $total = $row['cnt'];

	if ($total > 10) {
		echo "<p>";
		//echo "<CENTER>";
		for ($i = 0; $i < $total; $i = $i + 10){
			if ($i > 0) echo " | ";
			if ($i == $debut)
				echo "<FONT SIZE=3><B>$i</B></FONT>";
			else
				echo "<A HREF='forum.php3?debut=$i'>$i</A>";
		}
		//echo "</CENTER>";
	}



	echo "<p><div align='center'>";
	icone ("Poster un message", "forum_envoi.php3?statut=privrac&adresse_retour=forum.php3&titre_message=Nouveau+message", "forum-interne-24.gif", "creer.gif");
	echo "</div>";


echo "<P align='left'>";


$query_forum="SELECT * FROM spip_forum WHERE statut='privrac' AND id_parent=0 ORDER BY date_heure DESC LIMIT $debut,10";
$result_forum=spip_query($query_forum);

afficher_forum($result_forum,"forum.php3");
	
echo "</FONT>";




fin_page();

?>

