<?php

include ("ecrire/inc_version.php3");

include_local ("ecrire/inc_connect.php3");
include_local ("ecrire/inc_meta.php3");
include_local ("inc-forum.php3");

?>



<HTML>
<TITLE>Votre identifiant</TITLE>
<BODY BGCOLOR="#FFFFFF">

<TABLE WIDTH=100% HEIGHT=80%>
<TR WIDTH=100% HEIGHT=80%>
<TD WIDTH=100% HEIGHT=80%>

<?php

if (!$email) {
	echo"\n<FORM ACTION='spip_pass.php3' METHOD='post'>";
	echo "<FONT FACE='verdana,arial,helvetica,sans-serif'>";
	echo "Indiquez votre adresse e-mail:";
	echo "<BR>\n<INPUT TYPE='text' NAME='email' VALUE=\"$email\" SIZE='25'><P>";
	echo "<P ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='Valider'>";
	echo "</FONT>";
	echo "</FORM>";
}
else {
	$query = "SELECT * FROM spip_auteurs WHERE email='$email'";
	$result = mysql_query($query);
	$ok = true;

	if (mysql_num_rows($result) > 0) {
	 	while($row = mysql_fetch_array($result)) {
			$id_auteur = $row[0];
			$statut = $row[8];
		}
		if ($statut == '5poubelle') {
			echo "<h4>Vous n'avez plus acc&egrave;s &agrave; ces forums.</h4>";
			$ok = false;
		}
		else if ($statut != '6forum') {
			echo "<h4>Cette adresse e-mail est d&eacute;j&agrave; enregistr&eacute;e en tant que r&eacute;dacteur ou
			administrateur du site, vous pouvez donc utiliser votre mot de passe habituel.</h4>";
			$ok = false;
		}
	}

	if ($ok) {
		$pass = generer_pass_forum($email);

		$mdpass = md5($pass);
		$query = "INSERT INTO spip_auteurs (nom, email, login, pass, statut) VALUES ('Auteur forum', '$email', '', '$mdpass', '6forum')";
		$result = mysql_query($query);

		$nom_site = lire_meta("nom_site");
		$adresse_site = lire_meta("adresse_site");

		$message = "(ceci est un message automatique)\n\n";	
		$message .= "Bonjour\n\n";
		$message .= "Voici vos identifiants pour pouvoir participer aux forums\n";
		$message .= "du site \"$nom_site\" ($adresse_site) :\n\n";
		$message .= "- email : $email\n";
		$message .= "- mot de passe : $pass\n\n";
		if (envoyer_mail($email, "[$nom_site] Connexion au forum", $message)) {
			echo "<FONT FACE='verdana,arial,helvetica,sans-serif'>";
			echo "Votre nouvel identifiant vient de vous &ecirc;tre envoy&eacute; par email.";
			echo "</FONT>";
		}
		else {
			echo "<FONT FACE='verdana,arial,helvetica,sans-serif'>";
			echo "Erreur : l'identifiant ne peut pas &ecirc;tre envoy&eacute;.";
			echo "</FONT>";
		}
	}
}



?>
</TD></TR></TABLE>


</BODY>
</HTML>
