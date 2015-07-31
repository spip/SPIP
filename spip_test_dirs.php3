<?php

include("ecrire/inc_version.php3");

include_ecrire("inc_presentation.php3");
include_ecrire ("inc_lang.php3");
utiliser_langue_visiteur();

gerer_menu_langues();

function bad_dirs($bad_dirs, $test_dir, $install) {
	if ($install) {
		$titre = _T('dirs_preliminaire');
		$continuer = _T('dirs_commencer');
	} else
		$titre = _T('dirs_probleme_droits');

	$bad_url = "spip_test_dirs.php3";
	if ($test_dir) $bad_url .= '?test_dir='.$test_dir;

	echo "<BR><FONT FACE=\"Verdana,Arial,Helvetica,sans-serif\" SIZE=3>$titre</FONT>\n<p>";

	echo _T('dirs_repertoires_suivants', array('bad_dirs' => $bad_dirs));
	echo " <B><A HREF='$bad_url'> ". _T('login_recharger')."</A> $continuer.";

	if ($install)
		echo aide ("install0");

	echo "<br><div align='center'>". menu_langues()."</div>";

	echo "<FORM ACTION='$bad_urls' METHOD='GET'>\n";
	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='". _T('login_recharger')."'></DIV>";
	echo "</FORM>";	
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

	install_fin_html();
} else {
	if ($install)
		header("Location: ./ecrire/install.php3?etape=1");
	else
		header("Location: ./ecrire/");
}

?>
