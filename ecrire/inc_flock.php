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


if (!defined("_ECRIRE_INC_VERSION")) return;

function spip_file_get_contents ($fichier) {
	if (substr($fichier, -3) != '.gz') {
		if (function_exists('file_get_contents')
		AND os_serveur != 'windows') # windows retourne ''
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
function ecrire_fichier ($fichier, $contenu, $ecrire_quand_meme = false) {

	// Ne rien faire si on est en preview, debug, ou si une erreur
	// grave s'est presentee (compilation du squelette, MySQL, etc)
	if (($GLOBALS['var_preview'] OR ($GLOBALS['var_mode'] == 'debug')
	OR defined('spip_interdire_cache'))
	AND !$ecrire_quand_meme)
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

	// liberer le verrou et fermer le fichier
	@flock($fp, LOCK_UN);
	@fclose($fp);

	if (!$ok) {
		spip_log("echec ecriture fichier $fichier");
		@unlink($fichier);
	}

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

	// liberer le verrou
	@flock($fp, LOCK_UN);
	@fclose($fp);

	// supprimer
	@unlink($fichier);
}


//
// Retourne $subdir/ si le sous-repertoire peut etre cree, '' sinon
//
function creer_repertoire($base, $subdir) {
	$path = $base.'/'.$subdir;
	if (@is_dir($path)) return "$subdir/";

	@mkdir($path, 0777);
	@chmod($path, 0777);
	$ok = false;
	if ($f = @fopen("$path/.test", "w")) {
		@fputs($f, '<'.'?php $ok = true; ?'.'>');
		@fclose($f);
		include("$path/.test");
		@unlink("$path/.test");
	}
	if ($ok) return "$subdir/";

	redirige_par_entete(generer_url_action('test_dirs',
		'test_dir='.urlencode($path),true));
}

?>
