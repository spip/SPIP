<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_FLOCK")) return;
define("_ECRIRE_INC_FLOCK", "1");

// flock() marche dans ce repertoire <=> j'ai le droit de flock() sur ce fichier
if (LOCK_UN!=3) {
	define ('LOCK_SH', 1);
	define ('LOCK_EX', 2);
	define ('LOCK_UN', 3);
	define ('LOCK_NB', 4);
}

function test_flock ($fichier, $fp=false) {
	static $flock = array();
	global $flag_flock;
	if (!$flag_flock)
		return false;

	preg_match('|(.*)/([^/]*)$|', $fichier, $match);
	$dir = $match[1];
	if ($dir == '')
		return false;	// a la racine on ne fait que lire

	// premier appel pour ce $dir ?
	if (!isset($flock[$dir])) {
		// si un fichier d'etat flock est la et pas trop vieux -- id est:
		// pas recopie depuis une autre installation ! -- c'est ok.
		if (@file_exists("$dir/.flock_ok")
		AND (filemtime("$dir/.flock_ok") > time() - 3600))
			$flock[$dir] = true;
		else if (@file_exists("$dir/.flock_naze")
		AND (filemtime("$dir/.flock_naze") > time() - 3600))
			$flock[$dir] = false;

		else {
			// pas d'infos de flock, on va tester
			$fichiertest = $dir.'/'
			.substr(uniqid(@getmypid(), true),-6).".tmp";
			if ($fp = @fopen($fichiertest, 'w')) {
				if (@flock($fp, LOCK_SH)) {
					@flock($fp, LOCK_UN);
					$flock[$dir] = true;
					@touch("$dir/.flock_ok");
					@unlink("$dir/.flock_naze");
					spip_log("test $dir: flock ok");
				} else {
					$flock[$dir] = false;
					@touch("$dir/.flock_naze");
					@unlink("$dir/.flock_ok");
					spip_log("test $dir: flock naze");
				}
				@unlink($fichiertest);
			} else {
				spip_log("test $dir: echec du test sur $fichiertest !");
				@touch("$dir/.flock_naze");
				@unlink("$dir/.flock_ok");
			}
		}
	}

	return $flock[$dir];
}

// Si flock ne marche pas dans ce repertoire ou chez cet hebergeur,
// on renvoie OK pour ne pas bloquer
function spip_flock($filehandle, $mode, $fichier) {
	if (!test_flock($fichier))
		return true;

	$r = flock($filehandle, $mode);

	// demande de verrou ==> risque de sleep ==> forcer la relecture de l'etat
	if ($mode == LOCK_EX)
		clearstatcache();

	return $r;
}

function spip_file_get_contents ($fichier) {

	if (substr($fichier, -3) != '.gz') {
		if (function_exists('file_get_contents')
		AND $GLOBALS['os_serveur'] !='windows') # windows retourne ''
			return @file_get_contents ($fichier);
		else
			return join('', @file($fichier));
	} else
			return join('', @gzfile($fichier));
	
}

// options = array(
// 'phpcheck' => 'oui' # verifier qu'on a bien du php
// dezippe automatiquement les fichiers .gz
function lire_fichier ($fichier, &$contenu, $options=false) {
	$contenu = '';
	if (!@file_exists($fichier))
		return false;

	#spip_timer('lire_fichier');

	if ($fl = @fopen($fichier, 'r')) {

		// verrou lecture
		while (!spip_flock($fl, LOCK_SH, $fichier));

		// a-t-il ete supprime par le locker ?
		if (!@file_exists($fichier)) {
			@fclose($fl);
			return false;
		}

		// lire le fichier
		$contenu = spip_file_get_contents($fichier);

		// liberer le verrou
		spip_flock($fl, LOCK_UN, $fichier);
		@fclose($fl);

		// Verifications
		$ok = true;
		if ($options['phpcheck'] == 'oui')
			$ok &= (ereg("[?]>\n?$", $contenu));

		#spip_log("$fread $fichier ".spip_timer('lire_fichier'));
		if (!$ok)
			spip_log("echec lecture $fichier");

		return $ok;
	}
}


//
// Ecrire un fichier de maniere un peu sure
//
// zippe les fichiers .gz
function ecrire_fichier ($fichier, $contenu) {

	// Ne rien faire si on est en preview ou si une erreur
	// grave s'est presentee (compilation du squelette, MySQL, etc)
	if ($GLOBALS['var_preview'] OR defined('spip_erreur_fatale'))
		return;

	$gzip = (substr($fichier, -3) == '.gz');

	#spip_timer('ecrire_fichier');

	// verrouiller le fichier destination
	if ($fp = @fopen($fichier, 'a'))
		while (!spip_flock($fp, LOCK_EX, $fichier));
	else
		return false;

	// ecrire les donnees, compressees le cas echeant
	// (on ouvre un nouveau pointeur sur le fichier, ce qui a l'avantage
	// de le recreer si le locker qui nous precede l'avait supprime...)
	if ($gzip) $contenu = gzencode($contenu);
	@ftruncate($fp,0);
	$s = @fputs($fp, $contenu, $a = strlen($contenu));

	$ok = ($s == $a);
	if (!$ok)
		spip_log("echec ecriture fichier $fichier");

	#spip_log("$fputs $fichier ".spip_timer('ecrire_fichier'));

	// liberer le verrou et fermer le fichier
	spip_flock($fp, LOCK_UN, $fichier);
	@fclose($fp);

	return $ok;
}

//
// Supprimer le fichier de maniere sympa (flock)
//
function supprimer_fichier($fichier) {
	if (!@file_exists($fichier))
		return;

	// verrouiller le fichier destination
	if ($flock = test_flock($fichier)) {
		if ($fp = @fopen($fichier, 'a'))
			while (!spip_flock($fp, LOCK_EX, $fichier));
		else
			return;
	}

	// supprimer
	@unlink($fichier);
	
	// liberer le verrou
	if ($flock) {
		spip_flock($fp, LOCK_UN, $fichier);
		@fclose($fp);
	}

}

?>
