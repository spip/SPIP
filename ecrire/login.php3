<?php

include ("inc_version.php3");
include_local ("inc_connect.php3");
include_local ("inc_meta.php3");
include_local ("inc_presentation.php3");
include_local ("inc_session.php3");

// si la session existe, sauter dans ecrire/index.php3
if ($cookie_session = $HTTP_COOKIE_VARS['spip_session']) {
	if (verifier_session($cookie_session)) {
		@header("Location: ./index.php3");
		exit;
	}
}

// initialisations
$nom_site = lire_meta('nom_site');
if (!$nom_site) $nom_site = 'Mon site SPIP';
$url_site = lire_meta('adresse_site');
if (!$url_site) $url_site = '../index.php3';

// Le login est memorise dans le cookie d'admin eventuel
if (ereg("^@(.*)$", $spip_admin, $regs))
	$login = $regs[1];
else
	$login = '';

// y a-t-il d'anciennes sessions pour ce login ? Si oui proposer de les zapper
$zap_sessions = zap_sessions($login, false);

if ($echec_cookie == "oui") {
	install_debut_html("$nom_site : probl&egrave;me de cookie");
	echo "<p><b>Pour vous identifier de fa&ccedil;on s&ucirc;re sur ce site, vous devez accepter les cookies.</b> ";
	echo "Veuillez r&eacute;gler votre navigateur pour qu'il les accepte (au moins pour ce site).\n";
}
else {
	install_debut_html("$nom_site : acc&egrave;s &agrave; l'espace priv&eacute;", "document.forms[0].elements[1].focus();");
	echo "<p>Pour acc&eacute;der &agrave; l'espace priv&eacute; de ce site, ";
	echo "vous devez entrer les codes d'identification qui vous ont &eacute;t&eacute; ";
	echo "fournis lors de votre inscription.";
}


// fond d'ecran de login
$images = array ('login.gif', 'login.jpg', 'login.png', 'login-dist.png');
while (list(,$img) = each ($images)) {
	if (file_exists($img)) {
		echo '<style type="text/css"><!--
			body {background-image: url("'.$img.'"); background-repeat: no-repeat; background-position: top;}
			--></style>';
		break;
	}
}

echo "<p>&nbsp;<p>";


// affiche formulaire de login en incluant le javascript MD5
$redirect = './ecrire/index.php3?essai_cookie=oui';
$redirect_echec = './ecrire/login.php3';
$dir = "../";
echo "<script type=\"text/javascript\" src=\"md5.js\"></script>";
echo "<form action='$dir"."spip_cookie.php3' method='post'";
echo " onSubmit='this.session_password_md5.value = calcMD5(this.session_password.value); this.session_password.value = \"\";'";
echo ">\n";
echo "<fieldset>\n";
echo "<label><b>Login (identifiant de connexion au site)</b><br></label>";
echo "<input type='text' name='session_login' class='formo' value=\"$login\" size='40'><p>\n";
echo "<label><b>Mot de passe</b><br></label>";
echo "<input type='password' name='session_password' class='formo' value=\"\" size='40'><p>\n";
echo "<input type='hidden' name='essai_login' value='oui'>\n";
echo "<input type='hidden' name='redirect' value='$redirect'>\n";
echo "<input type='hidden' name='redirect_echec' value='$redirect_echec'>\n";
echo "<input type='hidden' name='session_password_md5' value=''>\n";
if ($zap_sessions) {
	echo "<font size='1'><input type='checkbox' name='zap_sessions' checked id='zap_sessions'>";
	echo "<label for='zap_sessions'>&nbsp;Zapper les autres sessions (s&eacute;curit&eacute;)</font></label>\n";
}
echo "<div align='right'><input type='submit' class='fondl' name='submit' value='Valider'></div>\n";
echo "</fieldset>\n";
echo "</form>";


if ($echec_cookie == "oui" AND $php_module) {
	echo "<form action='index.php3' method='get'>";
	echo "<fieldset>\n";
	echo "<p><b>Si vous pr&eacute;f&eacute;rez refuser les cookies</b>, une autre m&eacute;thode ";
	echo "non s&eacute;curis&eacute;e est &agrave; votre disposition&nbsp;: \n";
	echo "<input type='hidden' name='essai_auth_http' value='oui'> ";
	echo "<div align='right'><input type='submit' name='submit' class='fondl' value='Identification sans cookie'></div>\n";
	echo "</fieldset></form>\n";
}


if ($url_site) {
	echo "<p><font size='2' face='Verdana, Arial, Helvetica, sans-serif'>";
	echo "[<a href='$url_site'>retour au site public</a>]</font>";
}

install_fin_html();

?>