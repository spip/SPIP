<?php

include ("ecrire/inc_version.php3");
include_ecrire ("inc_connect.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_presentation.php3");
include_ecrire ("inc_session.php3");
include_ecrire ("inc_filtres.php3");
include_ecrire ("inc_texte.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_mail.php3");
include_ecrire ("inc_acces.php3");

include_local("inc-formulaires.php3");

$inscriptions_ecrire = (lire_meta("autoriser_inscriptions") == "oui") ;

// recuperer le cookie de relance
if ($p = addslashes($p)) {
	$res = spip_query ("SELECT * FROM spip_auteurs WHERE cookie_oubli='$p' AND statut<>'5poubelle' AND pass<>''");
	if ($row = mysql_fetch_array($res)) {
		if ($pass) {
			$mdpass = md5($pass);
			$htpass = generer_htpass($pass);
			spip_query ("UPDATE spip_auteurs SET htpass='$htpass', pass='$mdpass', alea_actuel='',
				cookie_oubli='' WHERE cookie_oubli='$p'");

			$erreur = "Votre nouveau mot de passe a &eacute;t&eacute; pris en compte.";
		} else {
			install_debut_html("Nouveau mot de passe");
			echo "<p><br>";
			echo "<form action='spip_pass.php3' method='post'>";
			echo "<input type='hidden' name='p' value='$p'>";
			echo "Veuillez entrer votre nouveau mot de passe :<br>\n";
			echo "<input type='password' name='pass' value=''>";
			echo "</form>\n";
			install_fin_html();
			exit;
		}
	}
	else
		$erreur = "Ce code ne correspond &agrave; aucun des visiteurs ayant acc&egrave;s &agrave; ce site.";
}

// envoyer le cookie de relance mot de passe
if ($email_oubli) {
	if (email_valide($email_oubli)) {
		$email = addslashes($email_oubli);
		$res = spip_query("SELECT * FROM spip_auteurs WHERE email ='$email'");
		if ($row = mysql_fetch_array($res)) {
			if ($row['statut'] == '5poubelle')
				$erreur = "Vous n'avez plus acc&egrave;s &agrave; ce site.";
			else {
				$cookie = creer_uniqid();
				spip_query("UPDATE spip_auteurs SET cookie_oubli = '$cookie' WHERE email ='$email'");

				$nom_site_spip = lire_meta("nom_site");
				$adresse_site = lire_meta("adresse_site");

				$message = "(ceci est un message automatique)\n\n";
				$message .= "Pour retrouver votre acc\xe8s au site\n";
				$message .= "$nom_site_spip ($adresse_site),\n";
				$message .= "Veuillez vous rendre \xe0 l'adresse suivante :\n\n";
				$message .= "   <$adresse_site/spip_pass.php3?p=$cookie>\n\n";
				$message .= "Vous pourrez alors entrer un nouveau mot de passe\n";
				$message .= "et vous reconnecter au site.";

				if (envoyer_mail($email, "[$nom_site_spip] Oubli du mot de passe", $message))
					$erreur = "Vous allez recevoir un email vous indiquant comment retrouver votre acc&egrave;s au site.";
				else
					$erreur = "Probl&egrave;me de mail&nbsp;: l'email ne peut pas &ecirc;tre envoy&eacute;.";
			}
		}
	}
	else
		$erreur = "Cet email n'est pas valide !";
}

// debut presentation
install_debut_html("Votre identifiant");
echo "<p><br>";

if ($erreur)
	echo "<font color='red' size='+1'><b>$erreur</b></font>";
else {
	if ($inscription_ecrire || forums_sur_abo()) {
		echo "<small>";
		if ($inscriptions_ecrire) {
			echo propre ("L'espace priv&eacute; de ce site est ouvert aux visiteurs,
			apr&egrave;s inscription. Votre identifiant vous permettra de consulter
			les articles en cours de r&eacute;daction, de proposer des articles et de
			participer aux forums internes aussi bien qu'aux forums publics sur abonnement.");
		} else {
			echo propre("Certains forums publics de ce site sont r&eacute;serv&eacute;s aux
			visiteurs enregistr&eacute;s.");
		}
		echo "\n<p>".propre ("Pour obtenir votre identifiant personnel,
		indiquez ici votre nom et votre adresse email. Les codes vous
		parviendront rapidement par courrier électronique.");

		formulaire_inscription();
	}

	echo "<p><br>";

	gros_titre("Mot de passe oubli&eacute;?");

	echo "<p>".propre("indiquez ci-dessous l'adresse email sous laquelle vous
		vous &ecirc;tes pr&eacute;c&eacute;demment enregistr&eacute;. Vous
		recevrez un email vous indiquant la marche &agrave; suivre pour
		r&eacute;cup&eacute;rer votre acc&egrave;s.");

	echo '<form action="spip_pass.php3" method="post">';
	echo '<div align="right">';
	echo '<input type="text" name="email_oubli" value="">';
	echo '  <input type=submit name="oubli" value="Vite !" class="spip_bouton"></div></form>';

	echo "</small>";
}

echo "<p align='right'><a href='javascript:{if(window.opener) window.opener.location.href=window.opener.location.href;close();}'>Quitter cette fen&ecirc;tre</a></p>";

install_fin_html();

?>