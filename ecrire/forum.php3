<?

include ("inc.php3");

debut_page("Forum interne");
debut_gauche();


debut_boite_info();
echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1>";
echo "<center><font size=2 color='black'><b>FORUM INTERNE</b></font></center>";

echo "Ce forum interne est accessible &agrave; tous les r&eacute;dacteurs du site.";

if ($connect_statut=="0minirezo"){

	echo "<p><font color='red'>Il existe &eacute;galement un <a href='forum_admin.php3'>forum des administrateurs</a>, r&eacute;serv&eacute; aux administrateurs.</font>";
}


echo "</font>";
fin_boite_info();




debut_droite();


echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
	if (!$debut) $debut = 0;

	$query_forum = "SELECT COUNT(*) FROM spip_forum WHERE statut='privrac' AND id_parent=0";
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
				echo "[<A HREF='forum.php3?debut=$i'>$i-$y</A>] ";
		}
		echo "</CENTER>";
	}



	echo "<P align='center'><A HREF='forum_envoi.php3?statut=privrac&adresse_retour=forum.php3&titre_message=Nouveau+message' onMouseOver=\"message.src='IMG2/message-on.gif'\" onMouseOut=\"message.src='IMG2/message-off.gif'\"><img src='IMG2/message-off.gif' alt='Poster un message' width='51' height='52' border='0' name='message'></A>";


echo "<P align='left'>";


$query_forum="SELECT * FROM spip_forum WHERE statut='privrac' AND id_parent=0 ORDER BY date_heure DESC LIMIT $debut,10";
$result_forum=mysql_query($query_forum);

afficher_forum($result_forum,"forum.php3");
	
echo "</FONT>";




fin_page();

?>

