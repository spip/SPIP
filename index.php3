<?php

if (!file_exists("ecrire/inc_connect.php3")) {
	$db_ok = 0;
	include ("ecrire/inc_version.php3");
	include_ecrire ("inc_presentation.php3");
	install_debut_html("Site en travaux");
	echo "<P>Ce site n'est pas encore configur&eacute;. Revenez plus tard...</P>";
	install_fin_html();
	exit;
}

include ("sommaire.php3");


?>
