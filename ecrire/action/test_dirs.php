<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

if (defined("_TEST_DIRS")) return;
define("_TEST_DIRS", "1");

include_spip('inc/minipres');

//
// Tente d'ecrire
//
// http://doc.spip.org/@test_ecrire
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
// tester les droits en ecriture sur les repertoires
// rajouter celui passer dans l'url ou celui du source (a l'installation)
//

// http://doc.spip.org/@action_test_dirs_dist
function action_test_dirs_dist()
{
  global $test_dir, $test_dirs;

if ($test_dir) {
  if (!ereg("/$", $test_dir)) $test_dir .= '/';
  if (!in_array($test_dir, $test_dirs)) $test_dirs[] = $test_dir;
 }
else {
	if (!_FILE_CONNECT)
	  $test_dirs[] = dirname(_FILE_CONNECT_INS);
}

$bad_dirs = array();
$absent_dirs  = array();;

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
				$bad_dirs[] = "<li>".$my_dir;
		} else
			$absent_dirs[] = "<li>". $my_dir;
	}
}

if ($bad_dirs OR $absent_dirs) {

	if (!_FILE_CONNECT) {
		$titre = _T('dirs_preliminaire');
		$continuer = ' '._T('dirs_commencer') . '.';
	} else
		$titre = _T('dirs_probleme_droits');


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
	  "<form action='" . generer_url_action('test_dirs') . "'>" .
	   "<input type='hidden' name='action' value='test_dirs' />" .
	  (!$test_dir ? "" : 
	   "<input type='hidden' name='test_dir' value='$test_dir' />") .
	  "<DIV align='right'><input type='submit' class='fondl' value='". 
	  _T('login_recharger')."'></DIV>" .
	  "</form>";
	minipres($titre, $res);

 } else {
	if (!_FILE_CONNECT)
	  header("Location: " . generer_url_ecrire("install", "etape=1", true));
	else
		header("Location: " . _DIR_RESTREINT_ABS);
 }
}
?>
