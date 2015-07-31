<?php

include ("inc.php3");
include_local ("inc_acces.php3");


debut_page("Auteurs");
debut_gauche();

if (!$aff_art) $aff_art[] = "0minirezo,1comite,2redac";
$aff_art = join(',', $aff_art);

if (ereg("5poubelle", $aff_art)){ 
    $aff_art .= ",";  
}

if (!$class) $class="auteurs";

if ($liste_lettres) $liste_lettres=ereg_replace(",+", ",", $liste_lettres);

echo "<form action='auteurs.php3' method='get'>";

debut_boite_info();
	echo "<FONT FACE='arial,helvetica,sans-serif'>";

	echo "<B>Afficher les auteurs&nbsp;:</B><BR>";
	echo "<input type='hidden' name='class' value='$class'>";
	echo "<input type='hidden' name='liste_lettres' value='$liste_lettres'>";

	if (ereg("0minirezo",$aff_art)){
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='0minirezo' id='minirezo'>";
	}else{
		echo "<input type='checkbox' name='aff_art[]' value='0minirezo' id='minirezo'>";
	}
	echo " <label for='minirezo'><img src='IMG2/bonhomme-noir.gif' alt='Admin' width='23' height='12' border='0'>";
	echo "  Administrateurs</label><BR>";

	if (ereg("2redac",$aff_art) OR ereg("1comite",$aff_art)){
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='1comite,2redac' id='2redac'>";
	}else{
		echo "<input type='checkbox' name='aff_art[]' value='1comite,2redac' id='2redac'>";
	}
	echo " <label for='2redac'><img src='IMG2/bonhomme-bleu.gif' alt='Admin' width='23' height='12' border='0'>";
	echo "  R&eacute;dacteurs</label><BR>";

	if ($connect_statut=="0minirezo"){
	if (ereg("5poubelle",$aff_art)){
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='5poubelle' id='5poubelle'>";
	}else{
		echo "<input type='checkbox' name='aff_art[]' value='5poubelle' id='5poubelle'>";
	}
	echo " <label for='5poubelle'><img src='IMG2/bonhomme-rouge.gif' alt='Admin' width='23' height='12' border='0'>";
	echo "  A la poubelle</label><BR>";
	}


	if ($publies=="oui"){
		echo "<input type='checkbox' CHECKED name='publies' value='oui' id='publies'>";
	}else{
		echo "<input type='checkbox' name='publies' value='oui' id='publies'>";
	}
	echo " <label for='publies'>";
	echo "  Ayant &eacute;crit des articles</label><BR>";




	echo "<DIV ALIGN='right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer'>";
	echo "</FONT>";
fin_boite_info();
echo "</form>";






debut_droite();


// peut-etre un jour autoriser les redacteurs a modifier leurs
// propres parametres ?
$flag_editable = ($connect_statut == '0minirezo');

function supp_auteur($id_auteur) {
	$query="UPDATE spip_auteurs SET statut='5poubelle' WHERE id_auteur=$id_auteur";
	$result=spip_query($query);
}

if ($supp && $flag_editable) {
	supp_auteur($supp);
}


$retour=urlencode("auteurs.php3?class=$class&aff_art[]=$aff_art&liste_lettres=$liste_lettres&publies=$publies");



function calculer_auteurs($result) {
	global $les_auteurs;
	
	global $k_nom;
	global $k_email;
	global $k_url_site;
	global $k_statut;
	global $k_nombre_articles;
	global $k_messagerie;
	global $nombre_auteurs;
		
	while ($row = mysql_fetch_array($result)) {
		$nombre_auteurs++;
		$id_auteur = $row[0];
		$k_nom[$id_auteur] = ucfirst(trim($row[1]));
		$k_email[$id_auteur] = $row[3];
		$k_url_site[$id_auteur] = $row[5];
		$k_statut[$id_auteur] = $row[8];
		$k_nombre_articles[$id_auteur]=$row["compteur"];
		if (($row["messagerie"] == "non") OR ($row['login'] == ''))
			$k_messagerie[$id_auteur]= "non";
		$les_auteurs.=",$id_auteur";
	}
}


function afficher_auteurs($classement) {
	global $connect_statut;
	global $connect_id_auteur;
	global $connect_activer_messagerie;
	global $ifond;
	global $les_auteurs;
	global $k_nom;
	global $k_email;
	global $k_url_site;
	global $k_statut;
	global $k_messagerie;
	global $k_nombre_articles;
	global $retour;
	global $liste_lettres;
	global $aff_art;
	global $publies;
	global $couleur_claire;
	global $couleur_foncee;

	$activer_messagerie=lire_meta("activer_messagerie");
	
	if ($classement=="auteurs") $auteurs=$k_nom;
	elseif ($classement=="statut") $auteurs=$k_statut;
	elseif ($classement=="articles") $auteurs=$k_nombre_articles;
	elseif ($classement=="email") $auteurs=$k_email;
	elseif ($classement=="url_site") $auteurs=$k_url_site;
	else $auteurs=$k_nom;
	
	if ($classement=="articles") arsort($auteurs);
	else  asort($auteurs);
	
	
	for(reset($auteurs);$index=key($auteurs);next($auteurs)){
	
	
		$id_auteur = $index;
		$nom = $k_nom[$index];
		$email = $k_email[$index];
		$url_site = $k_url_site[$index];
		$statut = $k_statut[$index];
		$nombre_articles=$k_nombre_articles[$index];
		$afficher_lettre=true;
		$messagerie=$k_messagerie[$index];

		$premiere_lettre=strtoupper(substr($nom,0,1));
		
		if ($classement=="auteurs" AND $premiere_lettre!=$ancienne_lettre){
			if ($ifond==0){
				$ifond=1;
				$couleur="#FFFFFF";
			}else{
				$ifond=0;
				$couleur="$couleur_claire";
			}
			

			if (eregi(",".quotemeta($premiere_lettre).",",$liste_lettres) OR $liste_lettres=="tout"){
				$new_lettres=eregi_replace(quotemeta($premiere_lettre),"",$liste_lettres);
				echo "<TR><TD BGCOLOR='$couleur' COLSPAN=5><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3><B><A HREF='auteurs.php3?class=$classement&aff_art[]=$aff_art&liste_lettres=$new_lettres&publies=$publies'><img src='IMG2/triangle-bas.gif' alt='&gt;' width='16' height='14' border='0'></A> $premiere_lettre</B></FONT></TD></TR>";
			}else{
				$new_lettres=urlencode("$liste_lettres,$premiere_lettre,");
				echo "<TR><TD BGCOLOR='$couleur' COLSPAN=5><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3><B><A HREF='auteurs.php3?class=$classement&aff_art[]=$aff_art&liste_lettres=$new_lettres&publies=$publies'><img src='IMG2/triangle.gif' alt='&gt;' width='16' height='14' border='0'></A> $premiere_lettre</B></FONT></TD></TR>";
			
			}
		}
		
		$ancienne_lettre=$premiere_lettre;
		$les_lettres.=",$premiere_lettre";
		
		
		if (eregi(",".quotemeta($premiere_lettre).",",$liste_lettres) OR $liste_lettres=="tout"){
	
			if ($ifond==0){
				$ifond=1;
				$couleur="#FFFFFF";
			}else{
				$ifond=0;
				$couleur="$couleur_claire";
			}				

			echo "<TR>";

			echo "<TD BGCOLOR='$couleur' WIDTH=50>";
				if ($classement=="auteurs") echo "<IMG SRC='IMG2/rien.gif' WIDTH=24 HEIGHT=12 BORDER=0>";

		
				if ($nombre_articles>0 OR $connect_statut=="0minirezo"){
					echo "<A HREF='auteurs_edit.php3?id_auteur=$id_auteur&redirect=$retour'>";
				}
				switch($statut){
					case "0minirezo":
						echo "<img src='IMG2/bonhomme-noir.gif' alt='Admin' width='23' height='12' border='0'>";
						break;					
					case "2redac":
						echo "<img src='IMG2/bonhomme-bleu.gif' alt='Admin' width='23' height='12' border='0'>";
						break;					
					case "1comite":
						echo "<img src='IMG2/bonhomme-bleu.gif' alt='Admin' width='23' height='12' border='0'>";
						break;					
					case "5poubelle":
						echo "<img src='IMG2/bonhomme-rouge.gif' alt='Admin' width='23' height='12' border='0'>";
						break;					
					case "nouveau":
						echo "&nbsp;";
						break;
					default:
						echo "&nbsp;";
											
				}
				if ($nombre_articles>0 OR $connect_statut=="0minirezo"){
					echo "</A>";
				}
			
			
				//echo $statut;
			
			echo "</TD>";


			echo "<TD BGCOLOR='$couleur'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
			if ($nombre_articles>0 OR $connect_statut=="0minirezo"){
				echo "<A HREF='auteurs_edit.php3?id_auteur=$id_auteur&redirect=$retour'>$nom</A>";
			}else echo "$nom";
			echo "</FONT>";
			echo "</TD>";

			echo "<TD BGCOLOR='$couleur' align='left'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
			if($activer_messagerie<>"non" AND $connect_activer_messagerie<>"non" AND $messagerie<>"non"){
				echo bouton_imessage($id_auteur,"force")."&nbsp;";
			}

			if ($connect_statut=="0minirezo"){
				if (strlen($email)>3) echo "<A HREF='mailto:$email'>email</A>";
			}else{
				echo "&nbsp;";
			}
			echo "</FONT>";
			echo "</TD>";


			echo "<TD BGCOLOR='$couleur'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
			if (strlen($url_site)>3) echo "<A HREF='$url_site'>site</A>";
			else echo "&nbsp;";
			echo "</FONT>";
			echo "</TD>";


			echo "<TD BGCOLOR='$couleur'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
			if ($nombre_articles>1)	echo "$nombre_articles&nbsp;articles";
			elseif($nombre_articles==1)	echo "$nombre_articles&nbsp;article";
			else echo "&nbsp;";
			echo "</FONT>";
			echo "</TD>";

			echo "</TR>\n";
		}		

	}
}

if ($connect_statut=="0minirezo") $aff_articles="prepa,prop,publie,refuse";
else $aff_articles="prop,publie";


	 	$query="SELECT auteurs.*, COUNT(articles.id_article) AS compteur FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien, spip_articles AS articles WHERE auteurs.id_auteur=lien.id_auteur AND lien.id_article=articles.id_article AND FIND_IN_SET(auteurs.statut,'$aff_art')>0 AND FIND_IN_SET(articles.statut,'$aff_articles') GROUP BY auteurs.id_auteur";
		calculer_auteurs(spip_query($query));


	if($nombre_auteurs<30) $liste_lettres="tout";

		if ($publies!="oui"){

			$ze_auteurs=substr($les_auteurs,1,strlen($les_auteurs));

			$query="SELECT *, 0 AS compteur FROM spip_auteurs WHERE FIND_IN_SET(id_auteur,'$ze_auteurs')=0 AND FIND_IN_SET(statut,'$aff_art')>0";
			calculer_auteurs(spip_query($query));
		}



if ($connect_statut =="0minirezo"){
	echo "<P align='right'><A HREF='auteurs_edit.php3?new=oui&redirect=$retour' onMouseOver=\"creer_auteur.src='IMG2/creer-auteur-on.gif'\" onMouseOut=\"creer_auteur.src='IMG2/creer-auteur-off.gif'\"><img src='IMG2/creer-auteur-off.gif' alt='Creer un nouvel auteur' width='71' height='56' border='0' name='creer_auteur'></A>";

}








	echo "<P align='left'><A HREF='auteurs.php3?aff_art[]=$aff_art&class=auteurs&liste_lettres=tout&publies=$publies'>Tout d&eacute;plier</A>";
	echo " | <A HREF='auteurs.php3?aff_art[]=$aff_art&class=auteurs&publies=$publies'>Tout replier</A><P>";

$ifond=0;


echo "<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 WIDTH=\"100%\">";
echo "<TR>";
if ($class=="statut"){
	echo "<TD BGCOLOR='$couleur_foncee'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><IMG SRC='IMG2/rien.gif' BORDER=0 WIDTH=50 HEIGHT=2><BR><img src='IMG2/triangle-bleu-bas.gif' alt='X' width='14' height='14' border='0'></FONT></TD>";
}else{
	echo "<TD BGCOLOR='#DBE1C5'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><IMG SRC='IMG2/rien.gif' BORDER=0 WIDTH=50 HEIGHT=2><BR><A HREF='auteurs.php3?aff_art[]=$aff_art&class=statut&liste_lettres=$liste_lettres&publies=$publies'><img src='IMG2/bonhomme-noir.gif' alt='Admin' width='23' height='12' border='0'></A></FONT></TD>";
}

if ($class=="auteurs"){
	echo "<TD BGCOLOR='$couleur_foncee'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#FFFFFF'><img src='IMG2/triangle-bleu-bas.gif' alt='X' width='14' height='14' border='0'> <B>Nom</B></FONT></TD>";
}else{
	echo "<TD BGCOLOR='#DBE1C5'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><A HREF='auteurs.php3?aff_art[]=$aff_art&class=auteurs&liste_lettres=$liste_lettres&publies=$publies'>Nom</A></FONT></TD>";

}
if ($connect_statut=="0minirezo"){
	echo "<TD BGCOLOR='#DBE1C5'><IMG SRC='IMG2/rien.gif' BORDER=0 WIDTH=50 HEIGHT=2></TD>";
}
echo "<TD BGCOLOR='#DBE1C5'><IMG SRC='IMG2/rien.gif' BORDER=0 WIDTH=40 HEIGHT=2></TD>";
if ($class=="articles"){
	echo "<TD BGCOLOR='$couleur_foncee'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#FFFFFF'><img src='IMG2/triangle-bleu-bas.gif' alt='X' width='14' height='14' border='0'>&nbsp;<B>Articles</B></FONT></TD>";
}else{
	echo "<TD BGCOLOR='#DBE1C5'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><A HREF='auteurs.php3?aff_art[]=$aff_art&class=articles&liste_lettres=$liste_lettres&publies=$publies'>Articles</A></FONT></TD>";

}
echo "</TR>";

		
		if (count($les_auteurs)>0){
			afficher_auteurs("$class");
		}


echo "</TABLE>";


fin_page();

?>

