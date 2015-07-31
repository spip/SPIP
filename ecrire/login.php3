<?php

include ("inc_version.php3");
include_local ("inc_connect.php3");
include_local ("inc_meta.php3");
include_local ("inc_presentation.php3");
include_local ("inc_session.php3");

$nom_site = lire_meta('nom_site');
$url_site = lire_meta('adresse_site');

function ask_php_auth($text_failure) {
	@Header("WWW-Authenticate: Basic realm=\"administrateur\"");
	@Header("HTTP/1.0 401 Unauthorized");
	echo $text_failure;
	exit;
}

// Le login est memorise dans le cookie d'admin eventuel
if (ereg("^@(.*)$", $spip_admin, $regs)) $login = $regs[1];
else $login = "";


// tentative de connexion en auth http
if ($PHP_AUTH_USER && $PHP_AUTH_PW) {
	@header("Location: ./index.php3");
	exit;
}
else if ($essai_auth_http == "oui") {
	ask_php_auth("Connexion refus&eacute;e...");
	exit;
}
else if ($essai_cookie == "oui") {
	if (! verifier_session($spip_session)) {
		install_debut_html("$nom_site : probl&egrave;me de cookie");
		echo "<p>La m&eacute;thode pr&eacute;f&eacute;r&eacute;e de spip pour l'authentification implique
			que vous acceptiez les cookies. Vous pouvez r&eacute;gler votre navigateur
			pour qu'il les accepte (au moins pour ce site).
			<a href='./login.php3?essai_auth_http=oui'>Sinon cliquez ici pour tenter
			une autre m&eacute;thode de connexion (par http).</a>";
	} else {
		@header("Location: ./index.php3");   // connecte
		exit;
	}
}
else {
	install_debut_html("$nom_site : acc&egrave;s &agrave; l'espace priv&eacute;", "document.forms[0].elements[1].focus();");
	echo "<p>Pour acc&eacute;der &agrave; l'espace priv&eacute; de ce site, ";
	echo "vous devez entrer les codes d'identification qui vous ont &eacute;t&eacute; ";
	echo "fournis lors de votre inscription.";
}

echo "<p>";
affiche_formulaire_login($login, './ecrire/login.php3?essai_cookie=oui', './ecrire/login.php3');

if ($url_site) {
	echo "<p><font size='2' face='Verdana, Arial, Helvetica, sans-serif'>";
	echo "[<a href='$url_site'>retour au site public</a>]</font>";
}

install_fin_html();

?>