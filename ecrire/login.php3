<?php

include ("inc_version.php3");
include_local ("inc_connect.php3");
include_local ("inc_meta.php3");
include_local ("inc_presentation.php3");
include_local ("inc_session.php3");

$nom_site = lire_meta('nom_site');
if (!$nom_site) $nom_site = 'Mon site SPIP';
$url_site = lire_meta('adresse_site');
if (!$url_site) $url_site = '../index.php3';

// Le login est memorise dans le cookie d'admin eventuel
if (ereg("^@(.*)$", $spip_admin, $regs)) $login = $regs[1];
else $login = "";


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
affiche_formulaire_login($login, './ecrire/index.php3?essai_cookie=oui', './ecrire/login.php3');


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