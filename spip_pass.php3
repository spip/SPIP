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

utiliser_langue_site();

$inscriptions_ecrire = (lire_meta("accepter_inscriptions") == "oui") ;

// recuperer le cookie de relance
if ($p = addslashes($p)) {
	$oubli_pass = 'oui';
	$res = spip_query ("SELECT * FROM spip_auteurs WHERE cookie_oubli='$p' AND statut<>'5poubelle' AND pass<>''");
	if ($row = spip_fetch_array($res)) {
		if ($pass) {
			$mdpass = md5($pass);
			$htpass = generer_htpass($pass);
			spip_query ("UPDATE spip_auteurs SET htpass='$htpass', pass='$mdpass', alea_actuel='',
				cookie_oubli='' WHERE cookie_oubli='$p'");

			$login = $row['login'];
			$erreur = "<b>"._T('pass_nouveau_enregistre')."</b>".
			"<p>"._T('pass_rappel_login', array('login' => $login));
		} else {
			install_debut_html(_T('pass_nouveau_pass'));
			echo "<p><br>";
			echo "<form action='spip_pass.php3' method='post'>";
			echo "<input type='hidden' name='p' value='$p'>";
			echo _T('pass_choix_pass')."<br>\n";
			echo "<input type='password' name='pass' value=''>";
			echo '  <input type=submit class="fondl" name="oubli" value="'._T('pass_ok').'"></div></form>';
			install_fin_html();
			exit;
		}
	}
	else
		$erreur = _T('pass_erreur_code_inconnu');
}

// envoyer le cookie de relance mot de passe
if ($email_oubli) {
	if (email_valide($email_oubli)) {
		$email = addslashes($email_oubli);
		$res = spip_query("SELECT * FROM spip_auteurs WHERE email ='$email'");
		if ($row = spip_fetch_array($res)) {
			if ($row['statut'] == '5poubelle')
				$erreur = _T('pass_erreur_acces_refuse');
			else {
				$cookie = creer_uniqid();
				spip_query("UPDATE spip_auteurs SET cookie_oubli = '$cookie' WHERE email ='$email'");

				$nom_site_spip = lire_meta("nom_site");
				$adresse_site = lire_meta("adresse_site");

				$message = _T('pass_mail_passcookie', array('nom_site_spip' => $nom_site_spip, 'adresse_site' => $adresse_site, 'cookie' => $cookie));
				if (envoyer_mail($email, "[$nom_site_spip] "._T('pass_oubli_mot'), $message))
					$erreur = _T('pass_recevoir_mail');
				else
					$erreur = _T('pass_erreur_probleme_technique');
			}
		}
		else
			$erreur = _T('pass_erreur_non_enregistre', array('email_oubli' => $email_oubli));
	}
	else
		$erreur = _T('pass_erreur_non_valide', array('email_oubli' => $email_oubli));
}

if ($oubli_pass == 'oui') {
	// debut presentation
	install_debut_html(_T('pass_mot_oublie'));

	echo "<p>";
	if ($erreur)
		echo $erreur;
	else {
		echo _T('pass_indiquez_cidessous');

		echo "<p>";
		echo '<form action="spip_pass.php3" method="post">';
		echo '<div align="right">';
		echo '<input type="text" class="fondo" name="email_oubli" value="">';
		echo '<input type="hidden" name="oubli_pass" value="oui">';
		echo '  <input type=submit class="fondl" name="oubli" value="'._T('pass_ok').'"></div></form>';
	}
}
else if ($inscriptions_ecrire || (lire_meta('accepter_visiteurs') == 'oui') OR (lire_meta('forums_publics') == 'abo')) {
	// debut presentation
	install_debut_html(_T('pass_vousinscrire'));
	echo "<p>";

	if ($inscriptions_ecrire)
		echo _T('pass_espace_prive_bla');
	else
		echo _T('pass_forum_bla');
	echo "\n<p>";

	formulaire_inscription(($inscriptions_ecrire)? 'redac' : 'forum');
}
else {
	install_debut_html(_T('pass_erreur'));
	echo "<p>"._T('pass_rien_a_faire_ici');
}

echo "<p align='right'><script type='text/javascript'><!--
	if (window.opener) document.write(\"<a href='javascript:close();'>\");
	else document.write(\"<a href='./'>\");
	document.write(\""._T('pass_quitter_fenetre')."</a>\");
	//--></script>
<noscript>[<a href='./'>"._T('pass_retour_public')."</a>]</noscript>
</p>";

install_fin_html();

?>
