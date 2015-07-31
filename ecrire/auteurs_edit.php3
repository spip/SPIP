<?php

include ("inc.php3");
include_local ("inc_acces.php3");
include_local ("inc_index.php3");
include_local ("inc_logos.php3");


function supp_auteur($id_auteur) {
	$query="UPDATE spip_auteurs SET statut='5poubelle' WHERE id_auteur=$id_auteur";
	$result=mysql_query($query);
}


function afficher_auteur_rubriques($leparent){
	global $id_parent;
	global $id_rubrique;
	global $toutes_rubriques;
	global $i;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=mysql_query($query);

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


if ($connect_toutes_rubriques AND $add_rub){
	$query = "INSERT INTO spip_auteurs_rubriques (id_auteur,id_rubrique) VALUES('$id_auteur','$add_rub')";
	$result = mysql_query($query);
}

if ($connect_toutes_rubriques AND $supp_rub){
	$query = "DELETE FROM spip_auteurs_rubriques WHERE id_auteur='$id_auteur' AND id_rubrique='$supp_rub'";
	$result = mysql_query($query);
}


$query = "SELECT nom FROM spip_auteurs WHERE id_auteur='$id_auteur'";
$result = mysql_query($query);

if ($row = mysql_fetch_array($result)) $nom_auteur = $row[0];

if ($connect_statut == "0minirezo" OR $connect_id_auteur == $id_auteur) {
	if ($new == "oui") {
		$query = "INSERT INTO spip_auteurs (nom,statut) VALUES ('Nouvel auteur','5poubelle')";
		$result = mysql_query($query);
		$id_auteur = mysql_insert_id();
	}
	if ($nom) {
		$ok = true;
		if (($login OR $new_pass) AND ($statut != '5poubelle')) {
			if (strlen($login) < 4) {
				echo "<P>Login trop court, veuillez recommencer.<P>";
				$ok = false;
			}
			else if ($new_pass AND $new_pass != $new_pass2) {
				echo "<P>Les deux mots de passe ne sont pas identiques, veuillez recommencer.<P>";
				$ok = false;
			}
			else if ($new_pass AND strlen($new_pass) < 6) {
				echo "<P>Mot de passe trop court, veuillez recommencer.<P>";
				$ok = false;
			}
			else {
				$query = "SELECT * FROM spip_auteurs WHERE login='$login' AND id_auteur!=$id_auteur AND statut!='5poubelle'";
				$result = mysql_query($query);
				if (mysql_num_rows($result)) {
					echo "<P>Ce login existe d&eacute;j&agrave;, veuillez recommencer<P>";
					$ok = false;
				}
			}
		}
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
				if ($connect_statut != '0minirezo') $statut = $connect_statut;
				$query = "UPDATE spip_auteurs SET nom='$nom', bio='$bio', email='$email', nom_site='$nom_site_auteur', url_site='$url_site', statut='$statut', pgp='$pgp', messagerie='$perso_activer_messagerie', imessage='$perso_activer_imessage' WHERE id_auteur=$id_auteur";
				$result = mysql_query($query);

				if ($login AND $connect_statut == '0minirezo') {
					$query = "UPDATE spip_auteurs SET login='$login' WHERE id_auteur=$id_auteur";
					$result = mysql_query($query);
				}
				if ($new_pass) {
					$htpass = generer_htpass($new_pass);
					$pass = md5($new_pass);
					$query = "UPDATE spip_auteurs SET pass='$pass', htpass='$htpass' WHERE id_auteur=$id_auteur";
					$result = mysql_query($query);
				}
				if (lire_meta('activer_moteur') == 'oui') {
					indexer_auteur($id_auteur);
				}
			}
			// METTRE A JOUR LE FICHIER DE PASSWORD
			ecrire_acces();
		}
	}
}

if ($redirect_ok == 'oui' && $redirect) {
	@header("Location: ".rawurldecode($redirect));
	exit;
}

	
debut_page($nom_auteur);
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


$query = "SELECT * FROM spip_auteurs WHERE id_auteur='$id_auteur'";
$result = mysql_query($query);


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

	echo "<A HREF='auteurs_edit.php3?redirect=$redirect&redirect_ok=oui' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt='Retour' width='49' height='46' border='0' name='retour' align='middle'></A>";

	echo "<FONT SIZE=5 FACE='Verdana,Arial,Helvetica,sans-serif'><B>".typo($nom)."</B></FONT>";

	if (strlen($email) > 2 OR strlen($bio) > 0 OR strlen($nom_site_auteur) > 0) {
		debut_boite_info();
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif'>";
		if (strlen($email) > 2) echo "<P>email : <B><A HREF='mailto:$email'>$email</A></B><BR> ";
		if (strlen($nom_site_auteur) > 2) echo "site : <B><A HREF='$url_site'>$nom_site_auteur</A></B>";
		echo "<P>".propre($bio);
		echo "</FONT>";
		fin_boite_info();
	}


	echo "<P>";
	if ($connect_statut == "0minirezo") $aff_art = "prepa,prop,publie,refuse";
	else if($connect_id_auteur == $id_auteur) $aff_art = "prepa,prop,publie";
	else $aff_art = "prop,publie";
	
	afficher_articles("Les articles de cet auteur",
	"SELECT article.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles AS article, spip_auteurs_articles AS lien WHERE lien.id_auteur='$id_auteur' AND lien.id_article=article.id_article AND FIND_IN_SET(article.statut,'$aff_art')>0 ORDER BY article.date DESC");

	//
	// Editer les donnees de l'auteur
	//

	if ($connect_statut == "0minirezo" OR $connect_id_auteur == $id_auteur) {
		echo "<FORM ACTION='auteurs_edit.php3' METHOD='post'>";
		echo "<INPUT TYPE='Hidden' NAME='id_auteur' VALUE=\"$id_auteur\">";
		echo "<INPUT TYPE='Hidden' NAME='redirect' VALUE=\"$redirect\">";

		//
		// Infos personnelles
		//

		echo "<P>";
		debut_cadre_relief();

		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=4>";
		echo "<B>Informations personnelles</B></FONT>";
		echo "<P>";
		echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";

		$nom = htmlspecialchars($nom);
		$nom_site_auteur = htmlspecialchars($nom_site_auteur);

		echo "<fieldset style='padding:5'><legend><B>Signature</B> [Obligatoire]<BR></legend>";
		echo "(Votre nom ou votre pseudo)<BR>";
		echo "<INPUT TYPE='text' NAME='nom' CLASS='formo' VALUE=\"$nom\" SIZE='40'><P>";
	
		echo "<B>Qui &ecirc;tes-vous ?</B><BR>";
		echo "(Courte biographie en quelques mots.)<BR>";
		echo "<TEXTAREA NAME='bio' CLASS='fondl' ROWS='4' COLS='40' wrap=soft>";
		echo $bio;
		echo "</TEXTAREA></fieldset><P>\n";
		
		echo "<fieldset style='padding:5'><legend><B>Votre adresse email</B> <BR></legend>";
	
		if ($connect_statut == "0minirezo") {
			echo "<INPUT TYPE='text' NAME='email' CLASS='formo' VALUE=\"$email\" SIZE='40'><P>\n";
		}
		else {
			echo "<B>".typo($email)."</B><P>";	
			echo "<INPUT TYPE='hidden' NAME='email' VALUE=\"$email\">";
		}
		
		echo "<B>Votre cl&eacute; PGP</B><BR>";
		echo "<TEXTAREA NAME='pgp' CLASS='fondl' ROWS='4' COLS='40' wrap=soft>";
		echo $pgp;
		echo "</TEXTAREA></fieldset><P>\n";
		
		echo "<fieldset style='padding:5'><legend><B>Le nom de votre site</B><BR></legend>";
		echo "<INPUT TYPE='text' NAME='nom_site_auteur' CLASS='forml' VALUE=\"$nom_site_auteur\" SIZE='40'><P>\n";

		echo "<B>L'adresse (URL) de votre site</B><BR>";
		echo "<INPUT TYPE='text' NAME='url_site' CLASS='forml' VALUE=\"$url_site\" SIZE='40'></fieldset><P>\n";

		//
		// Fonctionnement de la messagerie interne
		//
		$activer_messagerie=lire_meta("activer_messagerie");
		$activer_imessage=lire_meta("activer_imessage");
		
		if ($activer_messagerie!="non"){
		
			debut_boite_info();
			echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
			echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Messagerie interne</FONT></B>".aide ("messconf")."</TD></TR>";
			echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
			echo "<img src='IMG2/m_sans.gif' alt='' width='32' height='32' border='0' align='left'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>Ce site permet l'&eacute;change de messages et la constitution de forums de discussion priv&eacute;s entre les participants du site. Vous pouvez d&eacute;cider de ne pas participer &agrave; ces &eacute;changes.</FONT>";
			echo "</TD></TR>";

			echo "<TR><TD>&nbsp;</TD></TR>";
			echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Messagerie interne</FONT></B></TD></TR>";

			echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Vous pouvez activer ou d&eacute;sactiver votre messagerie personnelle sur ce site.</FONT>";
			echo "</TD></TR>";


			echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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
					echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Liste des r&eacute;dacteurs connect&eacute;s</FONT></B></TD></TR>";

					echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
					echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Ce site peut vous indiquer en permanence la liste des r&eacute;dacteurs connect&eacute;s, ce qui vous permet d'&eacute;changer des messages en direct (lorsque la messagerie est d&eacute;sactiv&eacute;e ci-dessus, la liste des r&eacute;dacteurs est elle-m&ecirc;me d&eacute;sactiv&eacute;e). Vous pouvez d&eacute;cider de ne pas appara&icirc;tre dans cette liste (vous &ecirc;tes &laquo;invisible&raquo; pour les autres utilisateurs).</FONT>";
					echo "</TD></TR>";

					echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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
			fin_boite_info();
			echo "<p>";
		}

		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='Valider'></DIV>";

		fin_cadre_relief();
		echo "&nbsp;<p>";

		//
		// Partie administrative : login, mot de passe....
		//

		echo "<P>";
		debut_cadre_relief();
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=4>";
		echo "<B>Informations de connexion</B></FONT>";
		echo "<P>";
		echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";

		if ($login == $connect_login) {
			echo "<p align='justify'><font size=3><b>Attention&nbsp;! Ceci est le login sous lequel vous &ecirc;tes connect&eacute; actuellement.
			<font color=\"red\">Utilisez ce formulaire avec pr&eacute;caution.</font></b></font><p>\n";
		}

		if (($connect_statut == "0minirezo" AND $connect_toutes_rubriques) OR $connect_id_auteur == $id_auteur) {
			echo "<fieldset style='padding:5'><legend><B>Login</B><BR></legend>";
			echo "(Plus de 3 caract&egrave;res)<BR>";
			echo "<INPUT TYPE='text' NAME='login' CLASS='formo' VALUE=\"$login\" SIZE='40'><P>\n";

			echo "<B>Nouveau mot de passe</B><BR>";
			echo "(Plus de 5 caract&egrave;res)<BR>";
			echo "<INPUT TYPE='password' NAME='new_pass' CLASS='formo' VALUE=\"\" SIZE='40'><BR>\n";
			echo "Confirmer ce nouveau mot de passe :<BR>";
			echo "<INPUT TYPE='password' NAME='new_pass2' CLASS='formo' VALUE=\"\" SIZE='40'><P>\n";
		}
		else {
			echo "<INPUT TYPE='hidden' NAME='login' VALUE=\"$login\">\n";
		}

		if ($connect_statut == "0minirezo" AND ($connect_toutes_rubriques OR $statut != "0minirezo")) {
			// par defaut nouvel auteur cree comme '5poubelle' mais passe '1comite' si validation
			if ($new == "oui") {
				$statut = '1comite';
			}
			echo "<fieldset style='padding:5'><legend><B>Statut du r&eacute;dacteur : </B><BR></legend>";
			echo " <SELECT NAME='statut' SIZE=1 CLASS='fondl'>";
			if ($connect_statut == "0minirezo" AND $connect_toutes_rubriques)
				echo "<OPTION".mySel("0minirezo",$statut).">Administrateur";
			echo "<OPTION".mySel("1comite",$statut).">R&eacute;dacteur";
			//echo "<OPTION".mySel("2redac",$statut).">Nouveau r&eacute;dacteur";
			echo "<OPTION".mySel("5poubelle",$statut).">Effac&eacute;";
			if ($statut=="6forum") echo "<OPTION".mySel("6forum",$statut).">Participant au forum";
			echo "</SELECT></fieldset><P>\n";
		}
		else {
			echo "<INPUT TYPE='Hidden' NAME='statut' VALUE=\"$statut\">";
		}

		echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='Valider'></DIV>";


		//
		// Gestion restreinte des rubriques
		//
		if ($statut == '0minirezo') {
			echo "<hr><p>";
			
			$query_admin = "SELECT lien.id_rubrique, titre FROM spip_auteurs_rubriques AS lien, spip_rubriques AS rubriques WHERE lien.id_auteur=$id_auteur AND lien.id_rubrique=rubriques.id_rubrique GROUP BY lien.id_rubrique";
			$result_admin = mysql_query($query_admin);
			
			if (mysql_num_rows($result_admin) == 0) {
				echo "Cet administrateur g&egrave;re <b>toutes les rubriques</b>.";
			}
			else {
				echo "Cet administrateur g&egrave;re les rubriques suivantes :\n";
				echo "<ul>";
				while ($row_admin = mysql_fetch_array($result_admin)) {
					$id_rubrique = $row_admin["id_rubrique"];
					$titre = typo($row_admin["titre"]);
					echo "<li>$titre";
					if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {
						echo " [<a href='auteurs_edit.php3?id_auteur=$id_auteur&supp_rub=$id_rubrique'>supprimer cette rubrique</a>]";
					}
					$toutes_rubriques .= "$id_rubrique,";
				}
				echo "</ul>";
				$toutes_rubriques = ",$toutes_rubriques";
			}
			
			if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {
				echo "<FORM ACTION='auteurs_edit.php3' METHOD='get'>";

				if (mysql_num_rows($result_admin) == 0) {
					echo "<p><fieldset style='padding:5'><legend><B>Restreindre la gestion &agrave; la rubrique :</b><BR></legend>";
				}
				else {
					echo "<p><fieldset style='padding:5'><legend><B>Ajouter une autre rubrique &agrave; administrer :</b><BR></legend>";
				}
				echo "<INPUT NAME='id_auteur' VALUE='$id_auteur' TYPE='hidden'>";
				echo "<SELECT NAME='add_rub' SIZE=1 CLASS='formo'>";
				echo "<OPTION VALUE='0'>   \n";
				afficher_auteur_rubriques("0");
				echo "</SELECT>";
				echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='Valider'>";
				echo "</fieldset>";
			}
		}
		fin_cadre_relief();
	}
}


fin_page();

?>