<?php

include ("inc.php3");

debut_page("Br&egrave;ves", "documents", "breves");
debut_gauche();

echo "<P align=left>";
	
debut_droite();


if ($statut) {
	$query="UPDATE spip_breves SET date_heure=NOW(), statut=\"$statut\" WHERE id_breve=$id_breve";
	$result=spip_query($query);
	calculer_rubriques();
}


function aff_breves($id_rubrique){
	global $debut;
	global $connect_statut;
	global $couleur_claire;

	if ($connect_statut=="0minirezo") $les_breves="prop,refuse,publie";
	else $les_breves="prop,publie";
	
	if (!$debut[$id_rubrique]) $debut[$id_rubrique]=0;
	$comm=$debut[$id_rubrique];
	$nombre_aff=10;

	$query="SELECT id_breve, date_heure, titre, statut FROM spip_breves WHERE id_rubrique='$id_rubrique' AND FIND_IN_SET(statut,'$les_breves')>0 ORDER BY date_heure DESC";
	
	$result=spip_query($query);
	$nombre=mysql_num_rows($result);
	
	if ($nombre>$nombre_aff){
		
		for ($i = 0; $i < $nombre; $i = $i + $nombre_aff){
			echo "<FONT FACE='arial,helvetica' SIZE=1>";
			$deb=$i+1;
			$fin=$i+$nombre_aff;
			if ($fin>$nombre) $fin=$nombre;
			if ($comm==$i){
				echo "[<B>$deb-$fin</B>] ";
			}else{
				echo "[<A HREF='breves.php3?debut[$id_rubrique]=$i'>$deb-$fin</A>] ";
			}
			echo "</FONT>";
		}

		$query="SELECT id_breve, date_heure, titre, statut FROM spip_breves WHERE id_rubrique='$id_rubrique' AND FIND_IN_SET(statut,'$les_breves')>0 ORDER BY date_heure DESC LIMIT $comm,$nombre_aff";
	
		$result=spip_query($query);
	}

	if (mysql_num_rows($result)>0){
		echo "<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 WIDTH=100% BACKGROUND=''>";
		echo "<TR><TD COLSPAN=2><IMG SRC='img_pack/rien.gif' WIDTH=150 HEIGHT=1 BORDER=0></TD><TD BACKGROUND='img_pack/rien.gif'><IMG SRC='img_pack/rien.gif' WIDTH=150 HEIGHT=1 BORDER=0></TD></TR>";
	
	 	while($row=mysql_fetch_array($result)){
			$id_breve=$row[0];
			$date_heure=$row[1];
			$titre=$row[2];
			$statut=$row[3];

			if ($ifond==0){
				$ifond=1;
				$couleur="#FFFFFF";
			}else{
				$ifond=0;
				$couleur="#EEEEEE";
			}
			echo "<TR WIDTH=\"100%\">";
			
			echo "<TD BGCOLOR='$couleur'>";
			echo "<A HREF='breves_voir.php3?id_breve=$id_breve'>";
				switch($statut){
					case "prop":
						echo "<img src='img_pack/puce-blanche.gif' alt='X' width='8' height='9' border='0'>";
						break;					
					case "refuse":
						echo "<img src='img_pack/puce-rouge.gif' alt='X' width='8' height='9' border='0'>";
						break;					
					case "publie":
						echo "<img src='img_pack/puce-verte.gif' alt='X' width='8' height='9' border='0'>";
						break;					
					default:
						echo "&nbsp;";
				}
			echo "</A>";
			echo "</TD>";
			
			echo "<TD BGCOLOR='$couleur' WIDTH=\"100%\"><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
			if ($statut=="prop") echo "<B>";
			if ($statut=="refuse") echo "<I>";
			echo "<A HREF='breves_voir.php3?id_breve=$id_breve'>";
			echo typo($titre);
			echo "</A>";
			if ($statut=="prop") echo "</B>";
			if ($statut=="refuse") echo "</I>";
			
			echo "</FONT></TD>";
			echo "<TD BGCOLOR='$couleur' align='right'><FONT FACE='arial,helvetica' SIZE=2>";
			
			if ($statut=="prop") echo "<FONT COLOR='red'>&agrave; valider</A>";
			else echo affdate($date_heure);
			
			echo "</FONT></TD>";
			echo "</TR>\n";
		}
		
		echo "</TABLE>";
	}

}



function enfant($leparent){
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

 	while($row=mysql_fetch_array($result)){
		$id_rubrique=$row[0];
		$id_parent=$row[1];
		$titre=$row[2];
		$descriptif=$row[3];
		$texte=$row[4];

		debut_cadre_enfonce();

		echo "<a href='naviguer.php3?coll=$id_rubrique'>";
		if  (acces_restreint_rubrique($id_rubrique))
			echo "<IMG SRC='img_pack/triangle-anim.gif' WIDTH=16 HEIGHT=14 BORDER=0>";
		else
			echo "<IMG SRC='img_pack/secteur-24.png' WIDTH=24 HEIGHT=24 BORDER=0 align='middle'>";
		echo "</a>";
		
		echo " <FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>";
		echo "<B>$titre</B></FONT>\n";
		echo aide ("breves");

		echo "<P ALIGN='left'>";

		if ($GLOBALS['connect_statut'] == "0minirezo") $statuts = "'prop', 'refuse', 'publie'";
		else $statuts = "'prop', 'publie'";

		$query = "SELECT id_breve, date_heure, titre, statut FROM spip_breves ".
			"WHERE id_rubrique='$id_rubrique' AND statut IN ($statuts) ORDER BY date_heure DESC";
		afficher_breves('', $query);
		echo "<div align='right'>";
		icone("&Eacute;crire une nouvelle br&egrave;ve", "breves_edit.php3?new=oui&id_rubrique=$id_rubrique", "breve-24.png", "creer.gif");
		echo "</div>";
	
		fin_cadre_enfonce();	

	}
}



enfant(0);


fin_page();

?>

