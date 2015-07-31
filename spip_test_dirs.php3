<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (defined("_TEST_DIRS")) return;
define("_TEST_DIRS", "1");

include("ecrire/inc_version.php3");

include_ecrire("inc_minipres.php");

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
// teste les droits en ecriture sur les repertoires
//
$test_dirs = array(_DIR_CACHE, _DIR_IMG, _DIR_SESSIONS);

// rajouter celui passer dans l'url ou celui du source (a l'installation)
if ($test_dir) {
  if (!ereg("/$", $test_dir)) $test_dir .= '/';
  if (!in_array($test_dir, $test_dirs)) $test_dirs[] = $test_dir;
 }
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

	if (!_FILE_CONNECT) {
		$titre = _T('dirs_preliminaire');
		$continuer = ' '._T('dirs_commencer') . '.';
	} else
		$titre = _T('dirs_probleme_droits');

	$bad_url = "spip_test_dirs.php3";
	if ($test_dir) $bad_url .= '?test_dir='.$test_dir;

	$res = "<div align='right'>". menu_langues('var_lang_ecrire')."</div>\n";

	if ($bad_dirs) {
		$res .=
		  _T('dirs_repertoires_suivants',
			   array('bad_dirs' => join(" ", $bad_dirs))) .
		  	"<b>". _T('login_recharger')."</b>.";
	}

	if ($absent_dirs) {
	  	$res .=
			_T('dirs_repertoires_absents',
			   array('bad_dirs' => join(" ", $absent_dirs))) .
			"<b>". _T('login_recharger')."</b>.";
	}

	$res = "<p>" . $continuer  . $res . aide ("install0") . "</p>" .
	  "<FORM ACTION='$bad_urls' METHOD='GET'>\n" .
	  "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' VALUE='". 
	  _T('login_recharger')."'></DIV>" .
	  "</FORM>";
	install_debut_html($titre);echo $res;	install_fin_html();

} else {
	if (!_FILE_CONNECT)
		header("Location: " . _DIR_RESTREINT_ABS . "install.php3?etape=1");
	else
		header("Location: " . _DIR_RESTREINT_ABS);
}

?>
