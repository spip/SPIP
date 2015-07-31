<?php

if (!file_exists("ecrire/inc_connect.php3")) {
	$db_ok = 0;
	include ("ecrire/inc_version.php3");
	include_ecrire ("inc_presentation.php3");
	install_debut_html(_T('info_travaux_titre'));
	echo "<P>"._T('info_travaux_texte')."</P>";
	install_fin_html();
	exit;
}

include ("sommaire.php3");


?>
