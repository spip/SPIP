<?php

include ("inc.php3");
include_ecrire ("inc_acces.php3");
include_ecrire ("inc_index.php3");
include_ecrire ("inc_logos.php3");

function afficher_auteur_rubriques($leparent){
	global $id_parent;
	global $id_rubrique;
	global $toutes_rubriques;
	global $i;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=spip_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);
	
		if (!ereg(",$my_rubrique,","$toutes_rubriques")){
			$espace="";
			for ($count=0;$count<$i;$count++){$espace.="&nbsp;&nbsp;";}
			$espace .= "|";
			if ($i==1)
				$espace = "*";

			echo "<OPTION VALUE='$my_rubrique'>$espace ".supprimer_tags($titre)."\n";
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


if ($row = spip_fetch_array($result)) {
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
	$abonne = $row['abonne'];
	$abonne_pass = $row['abonne_pass'];

debut_page($nom_auteur, "asuivre", "perso");



echo "<br><br><br>";
gros_titre($nom);

if (($connect_statut == "0minirezo") OR ($connect_id_auteur == $id_auteur)) {
	$statut_auteur=$statut;
	barre_onglets("auteur", "messagerie");
}


debut_gauche();



debut_boite_info();

echo "<CENTER>";

echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('info_gauche_numero_auteur')."&nbsp;:</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$id_auteur</B></FONT>";
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
			echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_messagerie_interne')."</FONT></B>".aide ("messconf")."</TD></TR>";
			echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
			echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2>"._T('texte_auteur_messagerie_1')."</FONT>";
			echo "</TD></TR>";

			echo "<TR><TD>&nbsp;</TD></TR>";
			echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_messagerie_interne')."</FONT></B></TD></TR>";

			echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
			echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='#000000'>"._T('info_desactiver_messagerie_personnelle')."</FONT>";
			echo "</TD></TR>";


			echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
			echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='#000000'>";
			if ($messagerie=="non"){
				echo "<INPUT TYPE='radio' NAME='perso_activer_messagerie' VALUE='oui' id='perso_activer_messagerie_on'>";
				echo " <label for='perso_activer_messagerie_on'>"._T('bouton_radio_activer_messagerie_interne')."</label> ";
				echo "<BR><INPUT TYPE='radio' NAME='perso_activer_messagerie' VALUE='non' CHECKED id='perso_activer_messagerie_off'>";
				echo " <B><label for='perso_activer_messagerie_off'>"._T('bouton_radio_desactiver_messagerie')."</label></B> ";
			}else{
				echo "<INPUT TYPE='radio' NAME='perso_activer_messagerie' VALUE='oui' id='perso_activer_messagerie_on' CHECKED>";
				echo " <B><label for='perso_activer_messagerie_on'>"._T('bouton_radio_activer_messagerie')."</label></B> ";
				echo "<BR><INPUT TYPE='radio' NAME='perso_activer_messagerie' VALUE='non' id='perso_activer_messagerie_off'>";
				echo " <label for='perso_activer_messagerie_off'>"._T('bouton_radio_desactiver_messagerie')."</label> ";
			}

			echo "</FONT>";
			echo "</TD></TR>\n";


			if ($activer_imessage!="non"){
				if ($messagerie!="non"){
					/// Liste des redacteurs connectes

					echo "<TR><TD>&nbsp;</TD></TR>";
					echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_liste_redacteurs_connectes')."</FONT></B></TD></TR>";

					echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
					echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='#000000'>"._T('texte_auteur_messagerie')."</FONT>";
					echo "</TD></TR>";

					echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
					echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='#000000'>";
					if ($imessage=="non"){
						echo "<INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='oui' id='perso_activer_imessage_on'>";
						echo " <label for='perso_activer_imessage_on'>"._T('bouton_radio_apparaitre_liste_redacteurs_connectes')."</label> ";
						echo "<BR><INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='non' CHECKED id='perso_activer_imessage_off'>";
						echo " <B><label for='perso_activer_imessage_off'>"._T('bouton_radio_non_apparaitre_liste_redacteurs_connectes')."</label></B> ";
					}else{
						echo "<INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='oui' id='perso_activer_imessage_on' CHECKED>";
						echo " <B><label for='perso_activer_imessage_on'>"._T('bouton_radio_apparaitre_liste_redacteurs_connectes')."</label></B> ";

						echo "<BR><INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='non' id='perso_activer_imessage_off'>";
						echo " <label for='perso_activer_imessage_off'>"._T('bouton_radio_non_apparaitre_liste_redacteurs_connectes')."</label> ";
					}
					echo "</FONT>";
					echo "</TD></TR>\n";
				}
			}
			echo "</TABLE>\n";
		}

		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='"._T('bouton_valider')."'></DIV>";

		fin_cadre_formulaire();

	echo "</form>";
	}

}


fin_page();

?>