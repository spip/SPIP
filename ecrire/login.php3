<?php

	include ("inc_presentation.php3");
	include ("inc_version.php3");
	include ("inc_connect.php3");
	include ("inc_session.php3");


	install_debut_html("SPIP connexion experimentale par cookie");
	echo "<p><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=4><B>Vos codes d'acces</B></FONT>";
	echo "<p>";
	affiche_formulaire_login($login, 'ecrire/index.php3');

	echo '<p><font face="Verdana,Arial,Helvetica,sans-serif">';
	if (($session = $GLOBALS[HTTP_COOKIE_VARS][spip_session]) && verifie_cookie_session($session)) {
		echo '<a href="../spip_session.php3?cookie=-1&redirect=ecrire/login.php3">Vous etes connecte : déconnexion</a><br>';
	} else {
		echo 'Vous n\'etes pas connecte par cookie de session<br>';
	}


	if ($GLOBALS[cookie] == 'cookie') {
		setcookie('cookie_login','experimental', time()+(3600*24*365));
	} else if ($GLOBALS[cookie] == 'off') {
		setcookie('cookie_login','');
	}

	echo "<p><font size=2>";
	if (($GLOBALS[cookie_login] == 'experimental' OR $GLOBALS[cookie] == 'cookie') AND !($GLOBALS[cookie] == 'off'))
		echo "(La connexion par cookie est active sur ce navigateur : pour la desactiver supprimez le cookie 'cookie_login' de votre navigateur
			ou <a href='login.php3?cookie=off'>cliquez ici</a>...)";
	else
		echo '<a href="login.php3?cookie=cookie">Demander la connexion par cookie sur ce navigateur</a>';

	echo "</font>";

	install_fin_html();

?>