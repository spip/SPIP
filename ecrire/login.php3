<?php

	include ("inc_version.php3");
	include_local ("inc_presentation.php3");
	include_local ("inc_connect.php3");
	include_local ("inc_meta.php3");
	include_local ("inc_session.php3");

	$nom_site = lire_meta('nom_site');
	$url_site = lire_meta('adresse_site');

	install_debut_html("$nom_site : connexion &agrave; l'espace priv&eacute;");

	// reconnaitre un login du cookie d'admin
	if (ereg("^[0-9]+@([^@]+)", $GLOBALS['spip_admin'], $regs))
		$login = $regs[1];

	echo "<p>";
	affiche_formulaire_login($login, './ecrire/index.php3');

	echo '<p><font face="Verdana,Arial,Helvetica,sans-serif">';
	if (($session = $GLOBALS['HTTP_COOKIE_VARS']['spip_session']) && verifie_cookie_session($session)) {
		echo '<a href="../spip_cookie.php3?cookie_session=-1&redirect=./ecrire/login.php3">Vous &ecirc;tes connect&eacute; : d&eacute;connexion</a><br>';
	} else {
		echo 'Vous n\'&ecirc;tes pas connect&eacute; par cookie de session<br>';
	}

	// temporaire...
	if ($GLOBALS['cookie'] == 'cookie') {
		setcookie('cookie_login','experimental', time()+(3600*24*365));
	} else if ($GLOBALS['cookie'] == 'off') {
		setcookie('cookie_login','');
	}

	echo "<p><font size=2>";
	if (($GLOBALS['cookie_login'] == 'experimental' OR $GLOBALS['cookie'] == 'cookie') AND !($GLOBALS['cookie'] == 'off'))
		echo "(La connexion par cookie est active sur ce navigateur : pour la desactiver supprimez le cookie 'cookie_login' de votre navigateur
			ou <a href='login.php3?cookie=off'>cliquez ici</a>...)";
	else
		echo '<a href="login.php3?cookie=cookie">Demander la connexion par cookie sur ce navigateur</a>';

	echo "</font>";
	// ...temporaire

	if ($url_site)
		echo "<p><font size=2><a href='$url_site'>Retour au site</a></font>";

	install_fin_html();

?>