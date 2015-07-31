<?php

include("ecrire/inc_version.php3");

include_ecrire("inc_presentation.php3");

function bad_dirs($bad_dirs, $test_dir, $install) {
	if ($install) {
		$titre = "Pr&eacute;liminaire : <B>R&eacute;gler les droits d'acc&egrave;s</B>";
		$continuer = " afin de commencer r&eacute;ellement l'installation";
	} else
		$titre = "<b>Probl&egrave;me de droits d'acc&egrave;s</b>";

	echo "<BR><FONT FACE=\"Verdana,Arial,Helvetica,sans-serif\" SIZE=3>$titre</FONT>
		<P><B>Les r&eacute;pertoires suivants ne sont pas accessibles en &eacute;criture&nbsp;: <ul>$bad_dirs.</ul> </B>
		<P>Pour y rem&eacute;dier, utilisez votre client FTP afin de r&eacute;gler les droits d'acc&egrave;s de chacun
		de ces r&eacute;pertoires. La proc&eacute;dure est expliqu&eacute;e en d&eacute;tail dans le guide d'installation.
		<P>Une fois cette manipulation effectu&eacute;e, vous pourrez <B><A HREF='spip_test_dirs.php3";
	if ($test_dir) echo '?test_dir='.$test_dir;
	echo "'>recharger cette page</A>$continuer.";
}

//
// teste les droits sur les repertoires
//

$install = !file_exists("ecrire/inc_connect.php3");

if ($test_dir)
	$test_dirs[] = $test_dir;
else
	$test_dirs = array("CACHE", "IMG", "ecrire", "ecrire/data");
	unset($bad_dirs);

while (list(, $my_dir) = each($test_dirs)) {
	$ok = true;
	$nom_fich = "$my_dir/test.txt";
	$f = @fopen($nom_fich, "w");
	if (!$f) $ok = false;
	else if (!@fclose($f)) $ok = false;
	else if (!@unlink($nom_fich)) $ok = false;
	
	if (!$ok) $bad_dirs[] = "<LI>".$my_dir;
}

if ($bad_dirs) {
	$bad_dirs = join(" ", $bad_dirs);
	install_debut_html();
	bad_dirs($bad_dirs, $test_dir, $install);

	if ($install)
		echo aide ("install0");

	install_fin_html();
} else {
	if ($install)
		header("Location: ./ecrire/install.php3?etape1=oui");
	else
		header("Location: ./ecrire/");
}

?>
