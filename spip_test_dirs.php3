<?php
if (defined("_TEST_DIRS")) return;
define("_TEST_DIRS", "1");

include("ecrire/inc_version.php3");

include_ecrire("inc_presentation.php3");

utiliser_langue_visiteur();


//
// Tente d'ecrire
//
function test_ecrire($my_dir) {
	$ok = true;
	$nom_fich = "$my_dir/test.txt";
	$f = @fopen($nom_fich, "w");
	if (!$f) $ok = false;
	else if (!@fclose($f)) $ok = false;
	else if (!@unlink($nom_fich)) $ok = false;
	return $ok;
}

//
// teste les droits sur les repertoires $test_dirs declares dans inc_version
//

// rajouter celui passer dans l'url ou celui du source (a l'installation)

if ($test_dir)
	$test_dirs[] = $test_dir;
else {
	if (!_FILE_CONNECT)
	  $test_dirs[] = dirname(_FILE_CONNECT_INS);
}

unset($bad_dirs);
unset($absent_dirs);

while (list(, $my_dir) = each($test_dirs)) {
	if (!test_ecrire($my_dir)) {
		@umask(0);
		if (@file_exists($my_dir)) {
			@chmod($my_dir, 0777);
			// ???
			if (!test_ecrire($my_dir))
				@chmod($my_dir, 0775);
			if (!test_ecrire($my_dir))
				@chmod($my_dir, 0755);
			if (!test_ecrire($my_dir))
				$bad_dirs[] = "<LI>".$my_dir;
		} else
			$absent_dirs[] = "<LI>".$my_dir;
	}
}

if ($bad_dirs OR $absent_dirs) {
	install_debut_html();

	if (!_FILE_CONNECT) {
		$titre = _T('dirs_preliminaire');
		$continuer = ' '._T('dirs_commencer');
	} else
		$titre = _T('dirs_probleme_droits');

	$bad_url = "spip_test_dirs.php3";
	if ($test_dir) $bad_url .= '?test_dir='.$test_dir;

	echo "<FONT FACE=\"Verdana,Arial,Helvetica,sans-serif\" SIZE=3>$titre</FONT>\n<p>";
	echo "<div align='right'>". menu_langues('var_lang_ecrire')."</div>\n";

	if ($bad_dirs) {
		echo "<p>";
		echo _T('dirs_repertoires_suivants',
			array('bad_dirs' => join(" ", $bad_dirs)));
		echo "<B>". _T('login_recharger')."</b>";
	}

	if ($absent_dirs) {
		echo "<p>";
		echo _T('dirs_repertoires_absents',
			array('bad_dirs' => join(" ", $absent_dirs)));
		echo "<B>". _T('login_recharger')."</b>";
	}

	echo $continuer.'. ';
	echo aide ("install0");
	echo "<p>";

	echo "<FORM ACTION='$bad_urls' METHOD='GET'>\n";
	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='". _T('login_recharger')."'></DIV>";
	echo "</FORM>";

	install_fin_html();

} else {
	if (!_FILE_CONNECT)
		header("Location: " . _DIR_RESTREINT_ABS . "install.php3?etape=1");
	else
		header("Location: " . _DIR_RESTREINT_ABS);
}

?>
