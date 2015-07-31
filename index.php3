<?

if (!file_exists("ecrire/inc_connect.php3")) {
	$db_ok = 0;
	include ("ecrire/inc_install.php3");
	debut_html("Site en travaux");
	echo "<P>Ce site n'est pas encore configur&eacute;. Revenez plus tard...</P>";
	fin_html();
	exit;
}

include ("sommaire.php3");


?>
