<?php

include ("inc_version.php3");
include_local ("inc_connect.php3");
include_local ("inc_meta.php3");
include_local ("inc_presentation.php3");
include_local ("inc_session.php3");

$nom_site = lire_meta('nom_site');
$url_site = lire_meta('adresse_site');

// deja connecte
/*	if (($session = $GLOBALS['HTTP_COOKIE_VARS']['spip_session']) && $auteur = verifie_cookie_session($session)) {
	$connecte = true;
}*/

install_debut_html("$nom_site : identification");



echo "<p><b>Pour acc&eacute;der &agrave; l'espace priv&eacute; de ce site, ";
echo "vous devez entrer vos codes d'identification.</b> Ceux-ci vous ont &eacute;t&eacute; ";
echo "fournis lors de votre inscription en tant qu'auteur.";

echo "<p>";
affiche_formulaire_login($login, './ecrire/index.php3');

/*	if ($connecte)
	echo "<p>Vous etes connecte en tant que $auteur->statut.";
*/

if ($url_site) {
	echo "<p><font size='2' face='Verdana, Arial, Helvetica, sans-serif'>";
	echo "[<a href='$url_site'>retour au site public</a>]</font>";
}

install_fin_html();

?>