<?php

include ("inc.php3");
include ("inc_statistiques.php3");


debut_page("Statistiques", "administration", "statistiques");


echo "<br><br><br>";
gros_titre("Les referers du jour");
barre_onglets("statistiques", "referers");

debut_gauche();


debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo "<P align=left>".propre("Cette page pr&eacute;sente la liste des {referers}, c'est-&agrave;-dire des sites contenant des liens menant vers votre propre site, uniquement pour aujourd'hui: en effet, cette liste est remise &agrave; z&eacute;ro toutes les 24 heures.");


echo "</FONT>";

fin_boite_info();





debut_droite();

if ($connect_statut != '0minirezo') {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}


//////

	echo "<font face='verdana,arial,helvetica,sans-serif' size=2>";


		echo "<ul>";
		// Recuperer les donnees du log	
		$query = "SELECT date, INET_NTOA(ip) AS ip, type, referer FROM spip_visites_temp";
		$result = spip_query($query);
	
		while ($row = mysql_fetch_array($result)) {
			$ip = $row['ip'];
			$type = $row['type'];
			$referer = $row['referer'];
			
			if (strlen($referer) > 0) {
				$referers[$referer][$type][$ip] = 1;
			}
		}

		while ($referers && (list($key, $value) = each($referers))) {
			$referer = $key;
			$ref_md5 = substr(md5($referer), 0, 15);


			echo "\n<li>";

			$total_ref = 0;
			while (list($key2,$value2) = each ($value)) {
				$value2 = count($value2);
				$total_ref = $total_ref + $value2;
			}
	
			if ($total_ref > 5) echo "<font color='red'>$total_ref liens : </font>";
			else if ($total_ref > 1) echo "$total_ref liens : ";
			else echo "<font color='#999999'>$total_ref lien : </font>";

			echo stats_show_keywords($referer, $referer);


		}



	echo "</ul>";
	echo "</font>";

fin_page();

?>

