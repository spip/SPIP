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

if ($connect_toutes_rubriques AND $add_rub){
	$query = "INSERT INTO spip_auteurs_rubriques (id_auteur,id_rubrique) VALUES('$id_auteur','$add_rub')";
	$result = spip_query($query);
}

if ($connect_toutes_rubriques AND $supp_rub){
	$query = "DELETE FROM spip_auteurs_rubriques WHERE id_auteur='$id_auteur' AND id_rubrique='$supp_rub'";
	$result = spip_query($query);
}





$query = "SELECT nom FROM spip_auteurs WHERE id_auteur='$id_auteur'";
$result = spip_query($query);

if ($row = mysql_fetch_array($result)) $nom_auteur = $row['nom'];

if ($connect_statut == "0minirezo" OR $connect_id_auteur == $id_auteur) {
	if ($new == "oui") {
		$query = "INSERT INTO spip_auteurs (nom,statut) VALUES ('Nouvel auteur','5poubelle')";
		$result = spip_query($query);
		$id_auteur = mysql_insert_id();
	}
	if ($statut) {
		$ok = true;
		if (($login OR $new_pass) AND ($statut != '5poubelle')) {
			if (strlen($login) < 4) {
				$echec = "<P>Login trop court, veuillez recommencer.<P>";
				$ok = false;
			}
			else if ($new_pass AND $new_pass != $new_pass2) {
				$echec .= "<P>Les deux mots de passe ne sont pas identiques, veuillez recommencer.<P>";
				$ok = false;
			}
			else if ($new_pass AND strlen($new_pass) < 6) {
				$echec .= "<P>Mot de passe trop court, veuillez recommencer.<P>";
				$ok = false;
			}
			else {
				$query = "SELECT * FROM spip_auteurs WHERE login='$login' AND id_auteur!=$id_auteur AND statut!='5poubelle'";
				$result = spip_query($query);
				if (mysql_num_rows($result)) {
					$echec .= "<P>Ce login existe d&eacute;j&agrave;, veuillez recommencer<P>";
					$ok = false;
				}
			}
		}
		if (!$ok) {
		}
		else {
			if ($statut == '5poubelle') {
				supp_auteur($id_auteur);
			}
			else {
				if ($connect_statut != '0minirezo') $statut = $connect_statut;
				$query = "UPDATE spip_auteurs SET statut='$statut' WHERE id_auteur=$id_auteur";
				$result = spip_query($query);

				if ($login AND $connect_statut == '0minirezo') {
					$query = "UPDATE spip_auteurs SET login='$login' WHERE id_auteur=$id_auteur";
					$result = spip_query($query);
				}
				if ($new_pass) {
					$htpass = generer_htpass($new_pass);
					$pass = md5($new_pass);
					$query = "UPDATE spip_auteurs SET pass='$pass', htpass='$htpass' WHERE id_auteur=$id_auteur";
					$result = spip_query($query);
				}
				if (lire_meta('activer_moteur') == 'oui') {
					indexer_auteur($id_auteur);
				}
			}
			// METTRE A JOUR LE FICHIER DE PASSWORD
			ecrire_acces();
		}
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


		if ($echec){
			
			debut_cadre_relief();	
			echo '<img src="img_pack/warning.gif" alt="warning.gif" width="48" height="48" align="left">';
			echo "<font color='red'>$echec</font>";
			fin_cadre_relief();	
			echo "<p>";
		}



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

		
		debut_cadre_relief("fiche-perso-24.gif");

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
		
		debut_cadre_relief("site-24.gif");
		
		echo "<B>Le nom de votre site</B><BR>";
		echo "<INPUT TYPE='text' NAME='nom_site_auteur' CLASS='forml' VALUE=\"$nom_site_auteur\" SIZE='40'><P>\n";

		echo "<B>L'adresse (URL) de votre site</B><BR>";
		echo "<INPUT TYPE='text' NAME='url_site' CLASS='forml' VALUE=\"$url_site\" SIZE='40'>\n";
		fin_cadre_relief();



		///////
		// login modifiable ?
		if (($connect_statut == "0minirezo" AND $connect_toutes_rubriques) OR $connect_id_auteur == $id_auteur) {
			debut_cadre_relief("base-24.gif");

			if ($connect_id_auteur == $id_auteur) {
				debut_cadre_enfonce();	
				echo '<img src="img_pack/warning.gif" alt="warning.gif" width="48" height="48" align="right">';
				echo "<b>Attention&nbsp;! Ceci est le login sous lequel vous &ecirc;tes connect&eacute; actuellement.
				<font color=\"red\">Utilisez ce formulaire avec pr&eacute;caution&nbsp;: si vous oubliez votre mot de passe, il sera impossible de le retrouver (seul un responsable Žditorial pourra vous en attribuer un nouveau).</font></b>\n";
				fin_cadre_enfonce();	
				echo "<p>";
			}



			echo "<B>Login</B> ";
			echo "<font color='red'>(plus de 3 caract&egrave;res)</font> :<BR>";
			echo "<INPUT TYPE='text' NAME='login' CLASS='formo' VALUE=\"$login\" SIZE='40'><P>\n";
			echo "<B>Nouveau mot de passe</B> ";
			echo "<font color='red'>(plus de 5 caract&egrave;res)</font> :<BR>";
			echo "<INPUT TYPE='password' NAME='new_pass' CLASS='formo' VALUE=\"\" SIZE='40'><BR>\n";
			echo "Confirmer ce nouveau mot de passe :<BR>";
			echo "<INPUT TYPE='password' NAME='new_pass2' CLASS='formo' VALUE=\"\" SIZE='40'><P>\n";
			fin_cadre_relief();
		} else {
			echo "<INPUT TYPE='hidden' NAME='login' VALUE=\"$login\">\n";
			if ($connect_id_auteur == $id_auteur) {
				echo "<fieldset style='padding:5'><legend><B>Login</B><BR></legend><br><b>$login</b><p>\n";
			}
		}

		if ($connect_statut == "0minirezo" AND ($connect_toutes_rubriques OR $statut != "0minirezo") AND $connect_id_auteur != $id_auteur) {
			// par defaut nouvel auteur cree comme '5poubelle' mais passe '1comite' si validation
			if ($new == "oui") {
				$statut = '1comite';
			}
			debut_cadre_relief();
			echo "<center><B>Statut de cet auteur : </B> ";
			echo " <SELECT NAME='statut' SIZE=1 CLASS='fondl'>";
			if ($connect_statut == "0minirezo" AND $connect_toutes_rubriques)
				echo "<OPTION".mySel("0minirezo",$statut).">responsable &eacute;ditorial";
			echo "<OPTION".mySel("1comite",$statut).">r&eacute;dacteur";
			echo "<OPTION".mySel("5poubelle",$statut).">&agrave; la poubelle";
			if ($statut=="6forum") echo "<OPTION".mySel("6forum",$statut).">participant au forum";
			echo "</SELECT></center>\n";
			fin_cadre_relief();
		}
		else {
			echo "<INPUT TYPE='Hidden' NAME='statut' VALUE=\"$statut\">";
		}

		//
		// Gestion restreinte des rubriques
		//
		if ($statut == '0minirezo') {
			debut_cadre_enfonce("secteur-24.gif");
			
			$query_admin = "SELECT lien.id_rubrique, titre FROM spip_auteurs_rubriques AS lien, spip_rubriques AS rubriques WHERE lien.id_auteur=$id_auteur AND lien.id_rubrique=rubriques.id_rubrique GROUP BY lien.id_rubrique";
			$result_admin = spip_query($query_admin);
			
			if (mysql_num_rows($result_admin) == 0) {
				echo "Ce responsable &eacute;ditorial g&egrave;re <b>toutes les rubriques</b>.";
			}
			else {
				echo "Cet responsable &eacute;ditorial g&egrave;re les rubriques suivantes :\n";
				echo "<ul style='list-style-image: url(img_pack/rubrique-12.png)'>";
				while ($row_admin = mysql_fetch_array($result_admin)) {
					$id_rubrique = $row_admin["id_rubrique"];
					$titre = typo($row_admin["titre"]);
					echo "<li>$titre";
					if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {
						echo " <font size=1>[<a href='auteur_infos.php3?id_auteur=$id_auteur&supp_rub=$id_rubrique'>supprimer cette rubrique</a>]</font>";
					}
					$toutes_rubriques .= "$id_rubrique,";
				}
				echo "</ul>";
				$toutes_rubriques = ",$toutes_rubriques";
			}
			
			if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {

				if (mysql_num_rows($result_admin) == 0) {
					echo "<p><B>Restreindre la gestion &agrave; la rubrique :</b><BR>";
				}
				else {
					echo "<p><B>Ajouter une autre rubrique &agrave; administrer :</b><BR>";
				}
				echo "<INPUT NAME='id_auteur' VALUE='$id_auteur' TYPE='hidden'>";
				echo "<SELECT NAME='add_rub' SIZE=1 CLASS='formo'>";
				echo "<OPTION VALUE='0'>   \n";
				afficher_auteur_rubriques("0");
				echo "</SELECT>";
			}
			fin_cadre_enfonce();
		}




		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='Valider'></DIV>";
		echo "</form>";
		fin_cadre_formulaire();
		echo "&nbsp;<p>";

	}
}


fin_page();

?>