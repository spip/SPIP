<?php

	include ("inc_version.php3");
	include_local ("inc_presentation.php3");
	if (file_exists("inc_meta_cache.php3")) {
		include_local ("inc_meta_cache.php3");
	} else {
		include_local ("inc_connect.php3");
		include_local ("inc_meta.php3");
	}
	include_local ("inc_session.php3");

	$nom_site = lire_meta('nom_site');
	$url_site = lire_meta('adresse_site');

	// deja connecte
/*	if (($session = $GLOBALS['HTTP_COOKIE_VARS']['spip_session']) && $auteur = verifie_cookie_session($session)) {
		$connecte = true;
	}*/

	install_debut_html("$nom_site : connexion &agrave; l'espace priv&eacute;");

	// reconnaitre un login du cookie d'admin
	if (ereg("^[0-9]+@([^@]+)", $GLOBALS['spip_admin'], $regs))
		$login = $regs[1];

	echo "<p>";
	affiche_formulaire_login($login, './ecrire/index.php3');

/*	if ($connecte)
		echo "<p>Vous etes connecte en tant que $auteur->statut.";
*/

	if ($url_site)
		echo "<p><font size=2><a href='$url_site'>Retour au site</a></font>";

	install_fin_html();

?>