<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_LOGIN")) return;
define("_INC_LOGIN", "1");

include_ecrire ("inc_connect.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_presentation.php3");
include_ecrire ("inc_session.php3");
include_ecrire ("inc_filtres.php3");
include_ecrire ("inc_texte.php3");


// gerer l'auth http
function auth_http($cible, $redirect_echec, $essai_auth_http) {
	if ($essai_auth_http == 'oui') {
		include_ecrire('inc_session.php3');
		if (! verifier_php_auth()) {
			ask_php_auth("<b>Connexion refus&eacute;e.</b><p>(Login ou mot de passe incorrect.)<p>[<a href='./'>Retour au site public</a>] [<a href='login.php3?essai_auth_http=oui'>Nouvelle tentative</a>] [<a href='ecrire/'>espace priv&eacute</a>]");
		} else {
			$cible->addVar('bonjour','oui');
			@header("Location: " . $cible->getUrl() );
		}
		exit;
	}
	// si demande logout auth_http
	else if ($essai_auth_http == 'logout') {
		include_ecrire('inc_session.php3');
		ask_php_auth("<b>D&eacute;connexion effectu&eacute;e.</b><p>(V&eacute;rifiez toutefois que votre navigateur n'a pas m&eacute;moris&eacute; votre mot de passe...)<p>[<a href='./'>Retour au site public</a>] [<a href='$redirect_echec?essai_auth_http=oui&redirect=ecrire'>test navigateur/reconnexion</a>] [<a href='ecrire/'>espace priv&eacute</a>]");
		exit;
	}
}

function login($cible, $redirect_echec) {
	global $login ;
	global $spip_admin ;
	global $erreur, $echec_cookie ;
	global $php_module ;

	// initialisations
	$nom_site = lire_meta('nom_site');
	if (!$nom_site) $nom_site = 'Mon site SPIP';
	$url_site = lire_meta('adresse_site');
	if (!$url_site) $url_site = './index.php3';
	if ($erreur=='pass') $erreur = "Erreur de mot de passe.";

	// Le login est memorise dans le cookie d'admin eventuel
	if (!$login)
		if (ereg("^@(.*)$", $spip_admin, $regs))
			$login = $regs[1];

	// quels sont les aleas a passer ?
	if ($login) {
		$login = addslashes($login);
		$query = "SELECT * FROM spip_auteurs WHERE login='$login' AND statut!='5poubelle'";
		$result = spip_query($query);
		if ($row = mysql_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$alea_actuel = $row['alea_actuel'];
			$alea_futur = $row['alea_futur'];
		} else {
			$erreur = "L'identifiant &laquo; $login &raquo; est inconnu.";
			$login = '';
			@setcookie("spip_admin", "", time() - 3600);
		}
	}

	// javascript pour le focus
	if ($login)
		$focus = 'document.forms[0].elements[1].focus();';
	else
		$focus = 'document.forms[0].elements[0].focus();';


	if ($echec_cookie == "oui") {
		install_debut_html("$nom_site : probl&egrave;me de cookie", $focus);
		echo "<p><b>Pour vous identifier de fa&ccedil;on s&ucirc;re sur ce site, vous devez accepter les cookies.</b> ";
		echo "Veuillez r&eacute;gler votre navigateur pour qu'il les accepte (au moins pour ce site).\n";
	}
	else {
		install_debut_html("$nom_site : acc&egrave;s &agrave; l'espace priv&eacute;", $focus);
		echo "<p>Pour acc&eacute;der &agrave; l'espace priv&eacute; de ce site, ";
		echo "vous devez entrer les codes d'identification qui vous ont &eacute;t&eacute; ";
		echo "fournis lors de votre inscription.";
	}


	// fond d'ecran de login
	$images = array ('login.gif', 'login.jpg', 'login.png', 'login-dist.png');
	while (list(,$img) = each ($images)) {
		$img = 'IMG/icones/'.$img;
		if (file_exists($img)) {
			echo "<style type=\"text/css\"><!--\n" .
				"body {background-image: url(\"$img\"); background-repeat: no-repeat; background-position: top left;}\n" .
				"--></style>\n";
			break;
		}
	}

	echo "<p>&nbsp;<p>";

	if ($login) {
		// affiche formulaire de login en incluant le javascript MD5
		echo "<script type=\"text/javascript\" src=\"ecrire/md5.js\"></script>";
		echo "<form action='spip_cookie.php3' method='post'";
		echo " onSubmit='if (this.session_password.value) {
				this.session_password_md5.value = calcMD5(\"$alea_actuel\" + this.session_password.value);
				this.next_session_password_md5.value = calcMD5(\"$alea_futur\" + this.session_password.value);
				this.session_password.value = \"\";
			}'";
		echo ">\n";
		// statut
		if ($row['statut'] == '0minirezo') {
			$icone = "redacteurs-admin-24.gif";
		} else if ($row['statut'] == '1comite') {
			$icone = "redacteurs-24.gif";
		}
		debut_cadre_enfonce($icone);
		if ($erreur) echo "<font color=red><b>$erreur</b></font><p>";

		if (file_exists("IMG/auton$id_auteur.gif")) $logo = "IMG/auton$id_auteur.gif";
		else if (file_exists("IMG/auton$id_auteur.jpg")) $logo = "IMG/auton$id_auteur.jpg";
		else if (file_exists("IMG/auton$id_auteur.png")) $logo = "IMG/auton$id_auteur.png";


		echo "<table cellpadding=0 cellspacing=0 border=0 width=100%>";
		echo "<tr width=100%>";
		echo "<td width=100%>";
		// si jaja actif, on affiche le login en 'dur', et on le passe en champ hidden
		echo "<script type=\"text/javascript\"><!--\n" .
			"document.write('Login : <b>$login</b> <br><font size=\\'2\\'>[<a href=\\'spip_cookie.php3?cookie_admin=non&url=login.php3\\'>se connecter sous un autre identifiant</a>]</font>');\n" .
			"//--></script>\n";
		echo "<input type='hidden' name='session_login_hidden' value='$login'>";

		// si jaja inactif, le login est modifiable (puisque le challenge n'est pas utilise)
		echo "<noscript><input type='text' name='session_login' class='formo' value=\"$login\" size='40'></noscript>";

		echo "<p>\n<label><b>Mot de passe :</b><br></label>";
		echo "<input type='password' name='session_password' class='formo' value=\"\" size='40'><p>\n";
		echo "<input type='hidden' name='essai_login' value='oui'>\n";

		$url = $cible->getUrl();
		echo "<input type='hidden' name='url' value='$url'>\n";
		echo "<input type='hidden' name='session_password_md5' value=''>\n";
		echo "<input type='hidden' name='next_session_password_md5' value=''>\n";
		echo "</td>";
		if ($logo) {
			echo "<td width=10><img src='ecrire/img_pack/rien.gif' width=10></td>";
			echo "<td valign='top'>";
			echo "<img src='$logo'>";
			echo "</td>";
		}
		echo "</tr></table>";
		echo "<div align='right'><input type='submit' class='fondl' name='submit' value='Valider'></div>\n";
		fin_cadre_enfonce();
		echo "</form>";

	}

	else {
		// demander seulement le login
		echo "<form action='$redirect_echec' method='get'>\n";
		debut_cadre_enfonce("redacteurs-24.gif");
		if ($erreur) echo "<font color=red><b>$erreur</b></font><p>";
		echo "<label><b>Login (identifiant de connexion au site)</b><br></label>";
		echo "<input type='text' name='login' class='formo' value=\"\" size='40'><p>\n";

		$url = $cible->getUrl();
		echo "<input type='hidden' name='url' value='$url'>\n";
		echo "<div align='right'><input type='submit' class='fondl' name='submit' value='Valider'></div>\n";
		fin_cadre_enfonce();
		echo "</form>";
	}

	if ($echec_cookie == "oui" AND $php_module) {
		echo "<form action='$redirect_echec' method='get'>";
		echo "<fieldset>\n";
		echo "<p><b>Si vous pr&eacute;f&eacute;rez refuser les cookies</b>, une autre m&eacute;thode ";
		echo "de connexion (moins s&eacute;curis&eacute;e) est &agrave; votre disposition&nbsp;: \n";
		echo "<input type='hidden' name='essai_auth_http' value='oui'> ";
		echo "<div align='right'><input type='submit' name='submit' class='fondl' value='Identification sans cookie'></div>\n";
		echo "</fieldset></form>\n";
	}


	echo "<p><font size='2' face='Verdana, Arial, Helvetica, sans-serif'>";
	echo "[<a href='$url_site'>retour au site public</a>]</font>";

	install_fin_html();

}

?>
