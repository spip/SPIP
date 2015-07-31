<?php

include ("ecrire/inc_version.php3");

include_ecrire ("inc_meta.php3");
include_ecrire ("inc_presentation.php3");
include_ecrire ("inc_session.php3");
include_ecrire ("inc_filtres.php3");
include_ecrire ("inc_texte.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_mail.php3");
include_ecrire ("inc_acces.php3");

include_local("inc-formulaires.php3");

$inscriptions_ecrire = (lire_meta("accepter_inscriptions") == "oui") ;

// recuperer le cookie de relance
if ($p = addslashes($p)) {
	$oubli_pass = 'oui';
	$res = spip_query ("SELECT * FROM spip_auteurs WHERE cookie_oubli='$p' AND statut<>'5poubelle' AND pass<>''");
	if ($row = mysql_fetch_array($res)) {
		if ($pass) {
			$mdpass = md5($pass);
			$htpass = generer_htpass($pass);
			spip_query ("UPDATE spip_auteurs SET htpass='$htpass', pass='$mdpass', alea_actuel='',
				cookie_oubli='' WHERE cookie_oubli='$p'");

			$login = $row['login'];
			$erreur = "<b>Votre nouveau mot de passe a &eacute;t&eacute; enregistr&eacute;.</b>".
			"<p>Rappel : votre identifiant (login) est &laquo; $login &raquo;.";
		} else {
			install_debut_html("Nouveau mot de passe");
			echo "<p><br>";
			echo "<form action='spip_pass.php3' method='post'>";
			echo "<input type='hidden' name='p' value='$p'>";
			echo "Veuillez choisir votre nouveau mot de passe :<br>\n";
			echo "<input type='password' name='pass' value=''>";
			echo "</form>\n";
			install_fin_html();
			exit;
		}
	}
	else
		$erreur = "<b>Erreur :</b> ce code ne correspond &agrave; aucun des visiteurs ayant acc&egrave;s &agrave; ce site.";
}

// envoyer le cookie de relance mot de passe
if ($email_oubli) {
	if (email_valide($email_oubli)) {
		$email = addslashes($email_oubli);
		$res = spip_query("SELECT * FROM spip_auteurs WHERE email ='$email'");
		if ($row = mysql_fetch_array($res)) {
			if ($row['statut'] == '5poubelle')
				$erreur = "<b>Erreur :</b> vous n'avez plus acc&egrave;s &agrave; ce site.";
			else {
				$cookie = creer_uniqid();
				spip_query("UPDATE spip_auteurs SET cookie_oubli = '$cookie' WHERE email ='$email'");

				$nom_site_spip = lire_meta("nom_site");
				$adresse_site = lire_meta("adresse_site");

				$message = "(ceci est un message automatique)\n\n";
				$message .= "Pour retrouver votre acc\xe8s au site\n";
				$message .= "$nom_site_spip ($adresse_site)\n\n";
				$message .= "Veuillez vous rendre \xe0 l'adresse suivante :\n\n";
				$message .= "   $adresse_site/spip_pass.php3?p=$cookie\n\n";
				$message .= "Vous pourrez alors entrer un nouveau mot de passe\n";
				$message .= "et vous reconnecter au site.\n";

				if (envoyer_mail($email, "[$nom_site_spip] Oubli du mot de passe", $message))
					$erreur = "Vous allez recevoir un email vous indiquant comment retrouver votre acc&egrave;s au site.";
				else
					$erreur = "<b>Erreur :</b> &agrave; cause d'un probl&egrave;me technique, l'email ne peut pas &ecirc;tre envoy&eacute;.";
			}
		}
		else
			$erreur = propre("<b>Erreur :</b> l'adresse <code>$email_oubli</code> n'est pas enregistr&eacute;e sur ce site.");
	}
	else
		$erreur = propre("<b>Erreur :</b> cet email <code>$email_oubli</code> n'est pas valide !");
}

if ($oubli_pass == 'oui') {
	// debut presentation
	install_debut_html("Mot de passe oubli&eacute;");

	echo "<p>";
	if ($erreur)
		echo $erreur;
	else {
		echo propre("Indiquez ci-dessous l'adresse email sous laquelle vous
			vous &ecirc;tes pr&eacute;c&eacute;demment enregistr&eacute;. Vous
			recevrez un email vous indiquant la marche &agrave; suivre pour
			r&eacute;cup&eacute;rer votre acc&egrave;s.");

		echo "<p>";
		echo '<form action="spip_pass.php3" method="post">';
		echo '<div align="right">';
		echo '<input type="text" class="fondo" name="email_oubli" value="">';
		echo '<input type="hidden" name="oubli_pass" value="oui">';
		echo '  <input type=submit class="fondl" name="oubli" value="OK"></div></form>';
	}
}
else if ($inscriptions_ecrire || (lire_meta('accepter_visiteurs') == 'oui') OR (lire_meta('forums_publics') == 'abo')) {
	// debut presentation
	install_debut_html("Vous inscrire sur ce site");
	echo "<p>";

	if ($inscriptions_ecrire)
		echo propre ("L'espace priv&eacute; de ce site est ouvert aux
		visiteurs, apr&egrave;s inscription. Une fois enregistr&eacute;,
		vous pourrez consulter les articles en cours de r&eacute;daction,
		proposer des articles et participer &agrave; tous les forums.");
	else
		echo propre("Vous avez demand&eacute; &agrave; intervenir sur un forum
		r&eacute;serv&eacute; aux visiteurs enregistr&eacute;s.");
	echo "\n<p>".propre ("Indiquez ici votre nom et votre adresse email.
	Votre identifiant personnel vous parviendra rapidement, par courrier
	&eacute;lectronique.");

	formulaire_inscription(($inscriptions_ecrire)? 'redac' : 'forum');
}
else {
	install_debut_html("Erreur");
	echo "<p>".propre("Rien &agrave; faire ici.");
}

echo "<p align='right'><script type='text/javascript'><!--
	if (window.opener) document.write(\"<a href='javascript:close();'>\");
	else document.write(\"<a href='./'>\");
	document.write(\"Quitter cette fen&ecirc;tre</a>\");
	//--></script>
<noscript>[<a href='./'>Retour sur le site public</a>]</noscript>
</p>";

install_fin_html();

?>