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
		$my_rubrique=$row[0];
		$titre=typo($row[2]);
	
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

$query = "SELECT nom FROM spip_auteurs WHERE id_auteur='$id_auteur'";
$result = spip_query($query);

if ($row = mysql_fetch_array($result)) $nom_auteur = $row[0];

if ($connect_statut == "0minirezo" OR $connect_id_auteur == $id_auteur) {
	if ($new == "oui") {
		$query = "INSERT INTO spip_auteurs (nom,statut) VALUES ('Nouvel auteur','5poubelle')";
		$result = spip_query($query);
		$id_auteur = mysql_insert_id();
	}
	if ($nom) {
		$ok = true;
		if (!$ok) {
			if ($new == "oui") {
				supp_auteur($id_auteur);
			}
		}
		else {
			if ($statut == '5poubelle') {
				supp_auteur($id_auteur);
			}
			else {
				$nom_auteur = $nom; // titre page
				$nom = addslashes($nom);
				$bio = addslashes($bio);
				$pgp = addslashes($pgp);
				$nom_site_auteur = addslashes($nom_site_auteur);
				$query = "UPDATE spip_auteurs SET nom='$nom', bio='$bio', email='$email', nom_site='$nom_site_auteur', url_site='$url_site', pgp='$pgp' WHERE id_auteur=$id_auteur";
				$result = spip_query($query);
			}
			// METTRE A JOUR LE FICHIER DE PASSWORD
		}
	}
}

if ($redirect_ok == 'oui' && $redirect) {
	@header("Location: ".rawurldecode($redirect));
	exit;
}




$query = "SELECT * FROM spip_auteurs WHERE id_auteur='$id_auteur'";
$result = spip_query($query);


if ($row = mysql_fetch_array($result)) {
	$id_auteur=$row[0];
	$nom=$row[1];
	$bio=$row[2];
	$email=$row[3];
	$nom_site_auteur=$row[4];
	$url_site=$row[5];
	$login=$row[6];
	$pass=$row[7];
	$statut=$row[8];
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
	barre_onglets("auteur", "infos");
}


debut_gauche();



debut_boite_info();

echo "<CENTER>";

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>AUTEUR NUM&Eacute;RO&nbsp;:</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$id_auteur</B></FONT>";
echo "</CENTER>";

fin_boite_info();




//////////////////////////////////////////////////////
// Logos de l'auteur
//

$arton = "auton$id_auteur";
$artoff = "autoff$id_auteur";
$arton_ok = get_image($arton);
if ($arton_ok) $artoff_ok = get_image($artoff);

if ($connect_statut == '0minirezo' AND ($options == 'avancees' OR $arton_ok)) {

	debut_boite_info();
	afficher_boite_logo($arton, "LOGO DE L'AUTEUR".aide ("logoart"));
	if (($options == 'avancees' AND $arton_ok) OR $artoff_ok) {
		echo "<P>";
		afficher_boite_logo($artoff, "LOGO POUR SURVOL");
	}
	fin_boite_info();
}


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

	

	if ($connect_statut == "0minirezo" OR $connect_id_auteur == $id_auteur) {
		debut_cadre_formulaire();
		echo "<FORM ACTION='auteur_infos.php3?id_auteur=$id_auteur' METHOD='post'>";
		echo "<INPUT TYPE='Hidden' NAME='id_auteur' VALUE=\"$id_auteur\">";
		echo "<INPUT TYPE='Hidden' NAME='redirect' VALUE=\"$redirect\">";

		//
		// Infos personnelles
		//
		echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";

		$nom = htmlspecialchars($nom);
		$nom_site_auteur = htmlspecialchars($nom_site_auteur);

		
		debut_cadre_relief("fiche-perso-24.png");

		echo "<B>Signature</B> [Obligatoire]<BR>";
		echo "(Votre nom ou votre pseudo)<BR>";
		echo "<INPUT TYPE='text' NAME='nom' CLASS='formo' VALUE=\"$nom\" SIZE='40'><P>";
	
		echo "<B>Qui &ecirc;tes-vous ?</B><BR>";
		echo "(Courte biographie en quelques mots.)<BR>";
		echo "<TEXTAREA NAME='bio' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
		echo $bio;
		echo "</TEXTAREA>\n";
		fin_cadre_relief();
		
		
		debut_cadre_relief();
		echo "<B>Votre adresse email</B> <BR>";
	
		if ($connect_statut == "0minirezo") {
			echo "<INPUT TYPE='text' NAME='email' CLASS='forml' VALUE=\"$email\" SIZE='40'><P>\n";
		}
		else {
			echo "<B>".typo($email)."</B><P>";	
			echo "<INPUT TYPE='hidden' NAME='email' VALUE=\"$email\">";
		}
		
		echo "<B>Votre cl&eacute; PGP</B><BR>";
		echo "<TEXTAREA NAME='pgp' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
		echo $pgp;
		echo "</TEXTAREA>\n";
		fin_cadre_relief();
		
		debut_cadre_relief("site-24.png");
		
		echo "<B>Le nom de votre site</B><BR>";
		echo "<INPUT TYPE='text' NAME='nom_site_auteur' CLASS='forml' VALUE=\"$nom_site_auteur\" SIZE='40'><P>\n";

		echo "<B>L'adresse (URL) de votre site</B><BR>";
		echo "<INPUT TYPE='text' NAME='url_site' CLASS='forml' VALUE=\"$url_site\" SIZE='40'>\n";
		fin_cadre_relief();

		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='Valider'></DIV>";
		echo "</form>";
		fin_cadre_formulaire();
		echo "&nbsp;<p>";

	}
}


fin_page();

?>