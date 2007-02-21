<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

if (defined("_TEST_DIRS")) return;
define("_TEST_DIRS", "1");

include_spip('inc/minipres');
include_spip('inc/lang');
utiliser_langue_visiteur();

//
// Tente d'ecrire
//
// http://doc.spip.org/@test_ecrire
function test_ecrire($my_dir) {
	static $chmod = 0;
	
	$ok = false;
	$script = @file_exists('spip_loader.php') ? 'spip_loader.php' : $_SERVER['PHP_SELF'];
	$self = basename($script);
	$uid = @fileowner('.');
	$uid2 = @fileowner($self);
	$gid = @filegroup('.');
	$gid2 = @filegroup($self);
	$perms = @fileperms($self);

	// Comparer l'appartenance d'un fichier cree par PHP
	// avec celle du script et du repertoire courant
	if(!$chmod) {
		@rmdir('test');
		@unlink('test'); // effacer au cas ou
		@touch('test');
		if ($uid > 0 && $uid == $uid2 && @fileowner('test') == $uid)
			$chmod = 0700;
		else if ($gid > 0 && $gid == $gid2 && @filegroup('test') == $gid)
			$chmod = 0770;
		else
			$chmod = 0777;
		// Appliquer de plus les droits d'acces du script
		if ($perms > 0) {
			$perms = ($perms & 0777) | (($perms & 0444) >> 2);
			$chmod |= $perms;
		}
		@unlink('test');
	}
	// Verifier que les valeurs sont correctes
	$f = @fopen($my_dir.'test.php', 'w');
	if ($f) {
		@fputs($f, '<'.'?php $ok = true; ?'.'>');
		@fclose($f);
		@chmod($my_dir.'test.php', $chmod);
		include($my_dir.'test.php');
	}
	@unlink($my_dir.'test.php');
	return $ok?$chmod:false;
}

//
// tester les droits en ecriture sur les repertoires
// rajouter celui passer dans l'url ou celui du source (a l'installation)
//

// http://doc.spip.org/@action_test_dirs_dist
function action_test_dirs_dist()
{
  global $test_dir, $test_dirs;
  $chmod = 0;

if ($test_dir) {
  if (substr($test_dir,-1)!=='/') $test_dir .= '/';
  if (!in_array($test_dir, $test_dirs)) $test_dirs[] = $test_dir;
 }
else {
	if (!_FILE_CONNECT)
	  $test_dirs[] = dirname(_FILE_CONNECT_INS).'/';
}

$bad_dirs = array();
$absent_dirs  = array();;

while (list(, $my_dir) = each($test_dirs)) {
	$test = test_ecrire($my_dir);
	if (!test_ecrire($my_dir)) {
		if (@file_exists($my_dir)) {
				$bad_dirs[] = "<li>".$my_dir."</li>";
		} else
			$absent_dirs[] = "<li>".$my_dir."</li>";
	}
	$chmod = max($chmod, $test);
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
	  "<div align='right'><input type='submit' class='fondl' value='". 
	  _T('login_recharger')."' /></div>" .
	  "</form>";
	echo minipres($titre, $res);

 } else {
	if (!_FILE_CONNECT)
	  header("Location: " . generer_url_ecrire("install", "etape=1&chmod=".$chmod, true));
	else
		header("Location: " . _DIR_RESTREINT_ABS);
 }
}
?>
