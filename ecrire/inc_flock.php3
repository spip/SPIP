<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_FLOCK")) return;
define("_ECRIRE_INC_FLOCK", "1");

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
		@flock($fl, LOCK_SH);

		// a-t-il ete supprime par le locker ?
		if (!@file_exists($fichier)) {
			@fclose($fl);
			return false;
		}

		// lire le fichier
		$contenu = spip_file_get_contents($fichier);

		// liberer le verrou
		@flock($fl, LOCK_UN);
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

	// Ne rien faire si on est en preview, debug, ou si une erreur
	// grave s'est presentee (compilation du squelette, MySQL, etc)
	if ($GLOBALS['var_preview'] OR $GLOBALS['var_debug']
	OR defined('spip_erreur_fatale'))
		return;

	$gzip = (substr($fichier, -3) == '.gz');

	#spip_timer('ecrire_fichier');

	// verrouiller le fichier destination
	if ($fp = @fopen($fichier, 'a'))
		@flock($fp, LOCK_EX);
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

	spip_log("$fputs $fichier ".spip_timer('ecrire_fichier'));

	// liberer le verrou et fermer le fichier
	@flock($fp, LOCK_UN);
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
	if ($fp = @fopen($fichier, 'a'))
		@flock($fp, LOCK_EX);
	else
		return;

	// supprimer
	@unlink($fichier);

	// liberer le verrou
	@flock($fp, LOCK_UN);
	@fclose($fp);
}

?>
