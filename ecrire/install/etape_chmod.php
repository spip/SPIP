<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (defined('_TEST_DIRS')) {
	return;
}
define('_TEST_DIRS', '1');

include_spip('inc/minipres');
utiliser_langue_visiteur();

//
// Tente d'ecrire
//
// https://code.spip.net/@test_ecrire
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
	if (!$chmod) {
		@rmdir('test');
		spip_unlink('test'); // effacer au cas ou
		@touch('test');
		if ($uid > 0 && $uid == $uid2 && @fileowner('test') == $uid) {
			$chmod = 0700;
		} else {
			if ($gid > 0 && $gid == $gid2 && @filegroup('test') == $gid) {
				$chmod = 0770;
			} else {
				$chmod = 0777;
			}
		}
		// Appliquer de plus les droits d'acces du script
		if ($perms > 0) {
			$perms = ($perms & 0777) | (($perms & 0444) >> 2);
			$chmod |= $perms;
		}
		spip_unlink('test');
	}
	$ok = is_dir($my_dir) && is_writable($my_dir);

	return $ok ? $chmod : false;
}

//
// tester les droits en ecriture sur les repertoires
// rajouter celui passe dans l'url ou celui du source (a l'installation)
//

// https://code.spip.net/@install_etape_chmod_dist
function install_etape_chmod_dist() {

	$test_dir = _request('test_dir');
	$chmod = 0;

	if ($test_dir) {
		if (substr($test_dir, -1) !== '/') {
			$test_dir .= '/';
		}
		if (!in_array($test_dir, $GLOBALS['test_dirs'])) {
			$GLOBALS['test_dirs'][] = _DIR_RACINE . $test_dir;
		}
	} else {
		if (!_FILE_CONNECT) {
			$GLOBALS['test_dirs'][] = _DIR_CONNECT;
			$GLOBALS['test_dirs'][] = _DIR_CHMOD;
		}
	}

	$bad_dirs = [];
	$absent_dirs = [];

	foreach ($GLOBALS['test_dirs'] as $i => $my_dir) {
		$test = test_ecrire($my_dir);
		if (!$test) {
			$m = preg_replace(',^' . _DIR_RACINE . ',', '', $my_dir);
			if (@file_exists($my_dir)) {
				$bad_dirs['<li>' . $m . '</li>'] = 1;
			} else {
				$absent_dirs['<li>' . $m . '</li>'] = 1;
			}
		} else {
			$chmod = max($chmod, $test);
		}
	}

	if ($bad_dirs or $absent_dirs) {
		if (!_FILE_CONNECT) {
			$titre = _T('dirs_preliminaire');
			$continuer = ' ' . _T('dirs_commencer') . '.';
		} else {
			$titre = _T('dirs_probleme_droits');
		}


		$res = "<div align='right'>" . menu_langues('var_lang_ecrire') . "</div>\n";

		if ($bad_dirs) {
			$res .=
				_T(
					'dirs_repertoires_suivants',
					['bad_dirs' => join("\n", array_keys($bad_dirs))]
				) .
				'<b>' . _T('login_recharger') . '</b>.';
		}

		if ($absent_dirs) {
			$res .=
				_T(
					'dirs_repertoires_absents',
					['bad_dirs' => join("\n", array_keys($absent_dirs))]
				) .
				'<b>' . _T('login_recharger') . '</b>.';
		}
		$res = '<p>' . $continuer . $res . aider('install0', true) . '</p>';

		$t = _T('login_recharger');
		$t = (!$test_dir ? '' :
				"<input type='hidden' name='test_dir' value='" . spip_htmlspecialchars($test_dir, ENT_QUOTES) . "' />")
			. "<input type='hidden' name='etape' value='chmod' />"
			. "<div style='text-align: right'><input type='submit' value='" . attribut_html($t) . "' /></div>";

		echo minipres($titre, $res . generer_form_ecrire('install', $t));
	} else {
		$deja = (_FILE_CONNECT and analyse_fichier_connection(_FILE_CONNECT));
		if (!$deja) {
			redirige_url_ecrire('install', 'etape=1&chmod=' . $chmod);
		} else {
			redirige_url_ecrire();
		}
	}
}
