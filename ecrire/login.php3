<?php

include ("inc_version.php3");
include_local ("inc_connect.php3");
include_local ("inc_meta.php3");
include_local ("inc_presentation.php3");
include_local ("inc_session.php3");

$nom_site = lire_meta('nom_site');
$url_site = lire_meta('adresse_site');

if (!$flag_js) {
	// Rediriger vers la version Javascript-MD5, sauf pour Netscape < 6
	echo "<script type=\"text/javascript\"><!--\n";
	echo "if (!(navigator.appName == 'Netscape' && parseInt(navigator.appVersion) <= 4)) ";
	echo "window.location.href = \"login.php3?flag_js=1\";\n";
	echo "// --></script>\n";
}
else {
	// Inclure les fonctions de calcul du MD5 en Javascript
	echo "<script type=\"text/javascript\" src=\"md5.js\"></script>";
}

install_debut_html("$nom_site : acc&egrave;s &agrave; l'espace priv&eacute;");


// Le login est memorise dans le cookie d'admin eventuel
if (ereg("^@(.*)$", $spip_admin, $regs)) $login = $regs[1];
else $login = "";

if ($echec_cookie == "oui") {
	echo "<p><b>Vous devez accepter les cookies pour ce site afin d'acc&eacute;der ";
	echo "&agrave; l'espace priv&eacute;.</b> Le r&eacute;glage se fait dans la ";
	echo "configuration de votre navigateur Web.";
}
else {
	echo "<p>Pour acc&eacute;der &agrave; l'espace priv&eacute; de ce site, ";
	echo "vous devez entrer les codes d'identification qui vous ont &eacute;t&eacute; ";
	echo "fournis lors de votre inscription.";
}

echo "<p>";
affiche_formulaire_login($login, './ecrire/index.php3?essai_cookie=oui', './ecrire/login.php3');

if ($url_site) {
	echo "<p><font size='2' face='Verdana, Arial, Helvetica, sans-serif'>";
	echo "[<a href='$url_site'>retour au site public</a>]</font>";
}

install_fin_html();

?>