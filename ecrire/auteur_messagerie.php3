<?php

include ("inc.php3");
include_local ("inc_acces.php3");
include_local ("inc_index.php3");
include_local ("inc_logos.php3");


function supp_auteur($id_auteur) {
	$query="UPDATE spip_auteurs SET statut='5poubelle' WHERE id_auteur=$id_auteur";
	$result=spip_query($query);
}


function afficher_auteur_rubriques($leparent){
	global $id_parent;
	global $id_rubrique;
	global $toutes_rubriques;
	global $i;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);
	
		if (!ereg(",$my_rubrique,","$toutes_rubriques")){
			$espace="";
			for ($count=0;$count<$i;$count++){$espace.="&nbsp;&nbsp;";}
			$espace .= "|";
			if ($i==1)
				$espace = "*";

			echo "<OPTION VALUE='$my_rubrique'>$espace $titre\n";
			afficher_auteur_rubriques($my_rubrique);
		}
	}
	$i=$i-1;
}


if ($connect_id_auteur == $id_auteur) {
	if ($perso_activer_messagerie) {
		$query = "UPDATE spip_auteurs SET messagerie='$perso_activer_messagerie', imessage='$perso_activer_imessage' WHERE id_auteur=$id_auteur";
		$result = spip_query($query);
	}
}




$query = "SELECT * FROM spip_auteurs WHERE id_auteur='$id_auteur'";
$result = spip_query($query);


if ($row = mysql_fetch_array($result)) {
	$id_auteur=$row['id_auteur'];
	$nom=$row['nom'];
	$bio=$row['bio'];
	$email=$row['email'];
	$nom_site_auteur=$row['nom_site'];
	$url_site=$row['url_site'];
	$login=$row['login'];
	$pass=$row['pass'];
	$statut=$row['statut'];
	$pgp=$row["pgp"];
	$messagerie=$row["messagerie"];
	$imessage=$row["imessage"];


if ($connect_id_auteur == $id_auteur) debut_page($nom_auteur, "redacteurs", "perso");
else if (ereg("5poubelle",$statut)) debut_page("$nom_auteur","redacteurs","redac-poubelle");
else if (ereg("0minirezo",$statut)) debut_page("$nom_auteur","redacteurs","administrateurs");
else debut_page("$nom_auteur","redacteurs","redacteurs");



echo "<br><br><br>";
gros_titre("$nom");

if (($connect_statut == "0minirezo") OR $connect_id_auteur == $id_auteur) {
	barre_onglets("auteur", "messagerie");
}


debut_gauche();



debut_boite_info();

echo "<CENTER>";

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>AUTEUR NUM&Eacute;RO&nbsp;:</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$id_auteur</B></FONT>";
echo "</CENTER>";

fin_boite_info();


debut_droite();

function mySel($varaut,$variable) {
	$retour = " VALUE=\"$varaut\"";
	if ($variable==$varaut){
		$retour.= " SELECTED";
	}
	return $retour;
}


	//
	// Editer les donnees de l'auteur
	//

	if ($connect_id_auteur == $id_auteur) {
		echo "<FORM ACTION='auteur_messagerie.php3?id_auteur=$id_auteur' METHOD='post'>";
		echo "<INPUT TYPE='Hidden' NAME='id_auteur' VALUE=\"$id_auteur\">";
		echo "<INPUT TYPE='Hidden' NAME='redirect' VALUE=\"$redirect\">";

		//
		// Fonctionnement de la messagerie interne
		//
		$activer_messagerie=lire_meta("activer_messagerie");
		$activer_imessage=lire_meta("activer_imessage");
		
		if ($activer_messagerie!="non"){
			debut_cadre_formulaire();
		
			echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
			echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Messagerie interne</FONT></B>".aide ("messconf")."</TD></TR>";
			echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>Ce site permet l'&eacute;change de messages et la constitution de forums de discussion priv&eacute;s entre les participants du site. Vous pouvez d&eacute;cider de ne pas participer &agrave; ces &eacute;changes.</FONT>";
			echo "</TD></TR>";

			echo "<TR><TD>&nbsp;</TD></TR>";
			echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Messagerie interne</FONT></B></TD></TR>";

			echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Vous pouvez activer ou d&eacute;sactiver votre messagerie personnelle sur ce site.</FONT>";
			echo "</TD></TR>";


			echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
			if ($messagerie=="non"){
				echo "<INPUT TYPE='radio' NAME='perso_activer_messagerie' VALUE='oui' id='perso_activer_messagerie_on'>";
				echo " <label for='perso_activer_messagerie_on'>Activer la messagerie interne</label> ";
				echo "<BR><INPUT TYPE='radio' NAME='perso_activer_messagerie' VALUE='non' CHECKED id='perso_activer_messagerie_off'>";
				echo " <B><label for='perso_activer_messagerie_off'>D&eacute;sactiver la messagerie</label></B> ";
			}else{
				echo "<INPUT TYPE='radio' NAME='perso_activer_messagerie' VALUE='oui' id='perso_activer_messagerie_on' CHECKED>";
				echo " <B><label for='perso_activer_messagerie_on'>Activer la messagerie interne</label></B> ";
				echo "<BR><INPUT TYPE='radio' NAME='perso_activer_messagerie' VALUE='non' id='perso_activer_messagerie_off'>";
				echo " <label for='perso_activer_messagerie_off'>D&eacute;sactiver la messagerie</label> ";
			}

			echo "</FONT>";
			echo "</TD></TR>\n";


			if ($activer_imessage!="non"){
				if ($messagerie!="non"){
					/// Liste des redacteurs connectes
						
					echo "<TR><TD>&nbsp;</TD></TR>";
					echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Liste des r&eacute;dacteurs connect&eacute;s</FONT></B></TD></TR>";

					echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
					echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Ce site peut vous indiquer en permanence la liste des r&eacute;dacteurs connect&eacute;s, ce qui vous permet d'&eacute;changer des messages en direct (lorsque la messagerie est d&eacute;sactiv&eacute;e ci-dessus, la liste des r&eacute;dacteurs est elle-m&ecirc;me d&eacute;sactiv&eacute;e). Vous pouvez d&eacute;cider de ne pas appara&icirc;tre dans cette liste (vous &ecirc;tes &laquo;invisible&raquo; pour les autres utilisateurs).</FONT>";
					echo "</TD></TR>";

					echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
					echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
					if ($imessage=="non"){
						echo "<INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='oui' id='perso_activer_imessage_on'>";
						echo " <label for='perso_activer_imessage_on'>Appara&icirc;tre dans la liste des r&eacute;dacteurs connect&eacute;s</label> ";
						echo "<BR><INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='non' CHECKED id='perso_activer_imessage_off'>";
						echo " <B><label for='perso_activer_imessage_off'>Ne pas appara&icirc;tre dans la liste des r&eacute;dacteurs</label></B> ";
					}else{
						echo "<INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='oui' id='perso_activer_imessage_on' CHECKED>";
						echo " <B><label for='perso_activer_imessage_on'>Appara&icirc;tre dans la liste des r&eacute;dacteurs connect&eacute;s</label></B> ";

						echo "<BR><INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='non' id='perso_activer_imessage_off'>";
						echo " <label for='perso_activer_imessage_off'>Ne pas appara&icirc;tre dans la liste des r&eacute;dacteurs</label> ";
					}
					echo "</FONT>";
					echo "</TD></TR>\n";
				}
			}
			echo "</TABLE>\n";
		}

		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='Valider'></DIV>";

		fin_cadre_formulaire();

	echo "</form>";
	}
}


fin_page();

?>