<?

include ("inc.php3");

debut_page("Forum des administrateurs");
debut_gauche();



debut_boite_info();
echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1>";
echo "<center><font size=2 color='red'><b>FORUM DES ADMINISTRATEURS</b></font></center>";

echo "Cet espace est un forum interne r&eacute;serv&eacute; aux administrateurs. Contrairement au <b>forum interne g&eacute;n&eacute;ral</b>, les r&eacute;dacteurs n'y ont pas acc&egrave;s.";

echo "<p>Pour les discussions qui concernent <b>tous les participants</b>, il faut donc utiliser le <a href='forum.php3'>forum interne g&eacute;n&eacute;ral</a>.";

echo "</font>";
fin_boite_info();


debut_droite();

if ($connect_statut == "0minirezo"){

	echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
	if (!$debut) $debut = 0;

	$query_forum = "SELECT COUNT(*) FROM spip_forum WHERE statut='privadm' AND id_parent=0";
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
				echo "[<A HREF='forum_admin.php3?debut=$i'>$i-$y</A>] ";
		}
		echo "</CENTER>";
	}



	echo "<P align='center'><A HREF='forum_envoi.php3?statut=privadm&adresse_retour=forum_admin.php3&titre_message=Nouveau+message' onMouseOver=\"message.src='IMG2/message-on.gif'\" onMouseOut=\"message.src='IMG2/message-off.gif'\"><img src='IMG2/message-off.gif' alt='Poster un message' width='51' height='52' border='0' name='message'></A>";


	echo "<P align='left'>";


	$query_forum="SELECT * FROM spip_forum WHERE statut='privadm' AND id_parent=0 ORDER BY date_heure DESC LIMIT $debut,10";
	$result_forum=mysql_query($query_forum);

	afficher_forum($result_forum,"forum_admin.php3");
} else {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
}
	
echo "</FONT>";




fin_page();

?>

