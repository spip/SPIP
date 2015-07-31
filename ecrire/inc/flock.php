<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) return;
// ajouter define('_CREER_DIR_PLAT', true); dans mes_options pour restaurer
// le fonctionnement des faux repertoires en .plat
define('_CREER_DIR_PLAT', false);
if (!defined('_TEST_FILE_EXISTS')) define('_TEST_FILE_EXISTS', preg_match(',(online|free)[.]fr$,', isset($_ENV["HTTP_HOST"]) ? $_ENV["HTTP_HOST"] : ""));

#define('_SPIP_LOCK_MODE',0); // ne pas utiliser de lock (deconseille)
#define('_SPIP_LOCK_MODE',1); // utiliser le flock php
#define('_SPIP_LOCK_MODE',2); // utiliser le nfslock de spip

if (_SPIP_LOCK_MODE==2)
	include_spip('inc/nfslock');

$GLOBALS['liste_verrous'] = array();
// http://doc.spip.org/@spip_fopen_lock
function spip_fopen_lock($fichier,$mode,$verrou){
	if (_SPIP_LOCK_MODE==1){
		if ($fl = @fopen($fichier,$mode))
			// verrou
			@flock($fl, $verrou);
		return $fl;
	}
	elseif(_SPIP_LOCK_MODE==2) {
		if (($verrou = spip_nfslock($fichier)) && ($fl = @fopen($fichier,$mode))){
			$GLOBALS['liste_verrous'][$fl] = array($fichier,$verrou);
			return $fl;
		}
		else return false;
	}
	return @fopen($fichier,$mode);
}
// http://doc.spip.org/@spip_fclose_unlock
function spip_fclose_unlock($handle){
	if (_SPIP_LOCK_MODE==1){
		@flock($handle, LOCK_UN);
	}
	elseif(_SPIP_LOCK_MODE==2) {
		spip_nfsunlock(reset($GLOBALS['liste_verrous'][$handle]),end($GLOBALS['liste_verrous'][$handle]));
		unset($GLOBALS['liste_verrous'][$handle]);
	}
	return @fclose($handle);
}


// http://doc.spip.org/@spip_file_get_contents
function spip_file_get_contents ($fichier) {
	if (substr($fichier, -3) != '.gz') {
		if (function_exists('file_get_contents')
		AND ( 
			// quand on est sous window on ne sait pas si file_get_contents marche
			// on essaye : si ca retourne du contenu alors c'est bon
			// sinon on fait un file() pour avoir le coeur net
		  ($contenu = @file_get_contents ($fichier))
		  OR _OS_SERVEUR != 'windows')
		)
			return $contenu;
		else
			$contenu = @file($fichier);
	} else
			$contenu = @gzfile($fichier);
	return is_array($contenu)?join('', $contenu):(string)$contenu;
}

// options = array(
// 'phpcheck' => 'oui' # verifier qu'on a bien du php
// dezippe automatiquement les fichiers .gz
// http://doc.spip.org/@lire_fichier
function lire_fichier ($fichier, &$contenu, $options=false) {
	$contenu = '';
	// inutile car si le fichier n'existe pas, le lock va renvoyer false juste apres
	// economisons donc les acces disque, sauf chez free qui rale pour un rien
	if (_TEST_FILE_EXISTS AND !@file_exists($fichier))
		return false;

	#spip_timer('lire_fichier');

	// pas de @ sur spip_fopen_lock qui est silencieux de toute facon
	if ($fl = spip_fopen_lock($fichier, 'r', LOCK_SH)) {
		// lire le fichier avant tout
		$contenu = spip_file_get_contents($fichier);

		// le fichier a-t-il ete supprime par le locker ?
		// on ne verifie que si la tentative de lecture a echoue
		// pour discriminer un contenu vide d'un fichier absent
		// et eviter un acces disque
		if (!$contenu AND !@file_exists($fichier)) {
			spip_fclose_unlock($fl);
			return false;
		}

		// liberer le verrou
		spip_fclose_unlock($fl);

		// Verifications
		$ok = true;
		if ($options['phpcheck'] == 'oui')
			$ok &= (preg_match(",[?]>\n?$,", $contenu));

		#spip_log("$fread $fichier ".spip_timer('lire_fichier'));
		if (!$ok)
			spip_log("echec lecture $fichier");

		return $ok;
	}
	return false;
}

//
// Ecrire un fichier de maniere un peu sure
//
// zippe les fichiers .gz
// http://doc.spip.org/@ecrire_fichier
function ecrire_fichier ($fichier, $contenu, $ignorer_echec = false, $truncate=true) {

	#spip_timer('ecrire_fichier');

	// verrouiller le fichier destination
	if ($fp = spip_fopen_lock($fichier, 'a',LOCK_EX)) {
	// ecrire les donnees, compressees le cas echeant
	// (on ouvre un nouveau pointeur sur le fichier, ce qui a l'avantage
	// de le recreer si le locker qui nous precede l'avait supprime...)
		if (substr($fichier, -3) == '.gz')
			$contenu = gzencode($contenu);
		// si c'est une ecriture avec troncation , on fait plutot une ecriture complete a cote suivie unlink+rename
		// pour etre sur d'avoir une operation atomique
		// y compris en NFS : http://www.ietf.org/rfc/rfc1094.txt
		// sauf sous wintruc ou ca ne marche pas
		$ok = false;
		if ($truncate AND _OS_SERVEUR != 'windows'){
			if (!function_exists('creer_uniqid'))
				include_spip('inc/acces');
			$id = creer_uniqid();
			// on ouvre un pointeur sur un fichier temporaire en ecriture +raz
			if ($fp2 = spip_fopen_lock("$fichier.$id", 'w',LOCK_EX)) {
				$s = @fputs($fp2, $contenu, $a = strlen($contenu));
				$ok = ($s == $a);
				spip_fclose_unlock($fp2);
				spip_fclose_unlock($fp);
				// unlink direct et pas spip_unlink car on avait deja le verrou
				// a priori pas besoin car rename ecrase la cible
				// @unlink($fichier);
				// le rename aussitot, atomique quand on est pas sous windows
				// au pire on arrive en second en cas de concourance, et le rename echoue
				// --> on a la version de l'autre process qui doit etre identique
				@rename("$fichier.$id",$fichier);
				// precaution en cas d'echec du rename
				if (!_TEST_FILE_EXISTS OR @file_exists("$fichier.$id"))
					@unlink("$fichier.$id");
				if ($ok)
					$ok = file_exists($fichier);
			}
			else
				// echec mais penser a fermer ..
				spip_fclose_unlock($fp);
		}
		// sinon ou si methode precedente a echoueee
		// on se rabat sur la methode ancienne
		if (!$ok){
			// ici on est en ajout ou sous windows, cas desespere
			if ($truncate)
				@ftruncate($fp,0);
			$s = @fputs($fp, $contenu, $a = strlen($contenu));

			$ok = ($s == $a);
			spip_fclose_unlock($fp);
		}

		// liberer le verrou et fermer le fichier
		@chmod($fichier, _SPIP_CHMOD & 0666);
		if ($ok) {
			if (!defined('_OPCACHE_BUG') AND function_exists('opcache_invalidate'))
				opcache_invalidate($fichier, true);
			return $ok;
		}
	}

	if (!$ignorer_echec){
		include_spip('inc/autoriser');
		if (autoriser('chargerftp'))
			raler_fichier($fichier);
		spip_unlink($fichier);
	}
	spip_log("Ecriture fichier $fichier impossible",_LOG_INFO_IMPORTANTE);
	return false;
}

/**
 * Ecrire un contenu dans un fichier encapsule en php pour en empecher l'acces en l'absence
 * de htaccess
 * @param string $fichier
 * @param <type> $contenu
 * @param <type> $ecrire_quand_meme
 * @param <type> $truncate 
 */
function ecrire_fichier_securise ($fichier, $contenu, $ecrire_quand_meme = false, $truncate=true) {
	if (substr($fichier,-4) !== '.php')
		spip_log('Erreur de programmation: '.$fichier.' doit finir par .php');
	$contenu = "<"."?php die ('Acces interdit'); ?".">\n" . $contenu;
	return ecrire_fichier($fichier, $contenu, $ecrire_quand_meme, $truncate);
}

/**
 * Lire un fichier encapsule en php
 * @param <type> $fichier
 * @param <type> $contenu
 * @param <type> $options 
 */
function lire_fichier_securise ($fichier, &$contenu, $options=false) {
	if ($res = lire_fichier($fichier,$contenu,$options)){
		$contenu = substr($contenu,strlen("<"."?php die ('Acces interdit'); ?".">\n"));
	}
	return $res;
}

// http://doc.spip.org/@raler_fichier
function raler_fichier($fichier)
{
	include_spip('inc/minipres');
	$dir = dirname($fichier);
	http_status(401);
	echo minipres(_T('texte_inc_meta_2'), "<h4 style='color: red'>"
		. _T('texte_inc_meta_1', array('fichier' => $fichier))
		. " <a href='"
		. generer_url_ecrire('install', "etape=chmod&test_dir=$dir")
		. "'>"
		. _T('texte_inc_meta_2')
		. "</a> "
		. _T('texte_inc_meta_3',
		     array('repertoire' => joli_repertoire($dir)))
		. "</h4>\n");
	exit;
}

//
// Retourne Vrai si son premier argument a ete cree il y a moins de N secondes
//

// http://doc.spip.org/@jeune_fichier
function jeune_fichier($fichier, $n)
{
	if (!file_exists($fichier)) return false;
	if (!$c = @filemtime($fichier)) return false;
	return (time()-$n <= $c);
}

//
// Supprimer le fichier de maniere sympa (flock)
//
// http://doc.spip.org/@supprimer_fichier
function supprimer_fichier($fichier, $lock=true) {
	if (!@file_exists($fichier))
		return true;

	if ($lock) {
		// verrouiller le fichier destination
		if (!$fp = spip_fopen_lock($fichier, 'a', LOCK_EX))
			return false;
	
		// liberer le verrou
		spip_fclose_unlock($fp);
	}
	
	// supprimer
	return @unlink($fichier);
}

// Supprimer brutalement, si le fichier existe
// http://doc.spip.org/@spip_unlink
function spip_unlink($f) {
	if (!is_dir($f))
		supprimer_fichier($f,false);
	else {
		@unlink("$f/.ok");
		@rmdir($f);
	}
}

/**
 * clearstatcache adapte a la version PHP
 * @param bool $clear_realpath_cache
 * @param null $filename
 */
function spip_clearstatcache($clear_realpath_cache = false, $filename=null){
	return (version_compare(PHP_VERSION, '5.3.0') >= 0)?
		clearstatcache($clear_realpath_cache,$filename):clearstatcache();
}

/*
 * Suppression complete d'un repertoire.
 *
 * http://www.php.net/manual/en/function.rmdir.php#92050
 *
 * @param string $dir Chemin du repertoire
 * @return bool Suppression reussie.
 */
function supprimer_repertoire($dir) {
	if (!file_exists($dir)) return true;
	if (!is_dir($dir) || is_link($dir)) return @unlink($dir);
	
	foreach (scandir($dir) as $item) {
		if ($item == '.' || $item == '..') continue;
		if (!supprimer_repertoire($dir . "/" . $item)) {
			@chmod($dir . "/" . $item, 0777);
			if (!supprimer_repertoire($dir . "/" . $item)) return false;
		};
	}
	
	return @rmdir($dir);
}

	
//
// Retourne $base/${subdir}/ si le sous-repertoire peut etre cree,
// $base/${subdir}_ sinon ; $nobase signale qu'on ne veut pas de $base/
// On peut aussi ne donner qu'un seul argument,
// subdir valant alors ce qui suit le dernier / dans $base
//
// http://doc.spip.org/@sous_repertoire
function sous_repertoire($base, $subdir='', $nobase = false, $tantpis=false) {
	static $dirs = array();

	$base = str_replace("//", "/", $base);

	# suppr le dernier caractere si c'est un / ou un _
	$base = rtrim($base, '/_');

	if (!strlen($subdir)) {
		$n = strrpos($base, "/");
		if ($n === false) return $nobase ? '' : ($base .'/');
		$subdir = substr($base, $n+1);
		$base = substr($base, 0, $n+1);
	} else {
		$base .= '/';
		$subdir = str_replace("/", "", $subdir);
	}

	$baseaff = $nobase ? '' : $base;
	if (isset($dirs[$base.$subdir]))
		return $baseaff.$dirs[$base.$subdir];


	if (_CREER_DIR_PLAT AND @file_exists("$base${subdir}.plat"))
		return $baseaff.($dirs[$base.$subdir] = "${subdir}_");

	$path = $base.$subdir; # $path = 'IMG/distant/pdf' ou 'IMG/distant_pdf'

	if (file_exists("$path/.ok"))
		return $baseaff.($dirs[$base.$subdir] = "$subdir/");

	@mkdir($path, _SPIP_CHMOD);
	@chmod($path, _SPIP_CHMOD);

	$ok = false;
	if ($test = @fopen("$path/dir_test.php", "w")) {
		@fputs($test, '<'.'?php $ok = true; ?'.'>');
		@fclose($test);
		@include("$path/dir_test.php");
		spip_unlink("$path/dir_test.php");
	}
	if ($ok) {
		@touch ("$path/.ok");
		spip_log("creation $base$subdir/");
		return $baseaff.($dirs[$base.$subdir] = "$subdir/");
	}

	// en cas d'echec c'est peut etre tout simplement que le disque est plein :
	// l'inode du fichier dir_test existe, mais impossible d'y mettre du contenu
	// => sauf besoin express (define dans mes_options), ne pas creer le .plat
	if (_CREER_DIR_PLAT
	AND $f = @fopen("$base${subdir}.plat", "w"))
		fclose($f);
	else {
		spip_log("echec creation $base${subdir}");
		if ($tantpis) return '';
		if (!_DIR_RESTREINT)
			$base = preg_replace(',^' . _DIR_RACINE .',', '',$base);
		$base .= $subdir;
		raler_fichier($base . ($test?'/.ok':'/dir_test.php'));
	}
	spip_log("faux sous-repertoire $base${subdir}");
	return $baseaff.($dirs[$base.$subdir] = "${subdir}_");
}

//
// Cette fonction parcourt recursivement le repertoire $dir, et renvoie les
// fichiers dont le chemin verifie le pattern (preg) donne en argument.
// En cas d'echec retourne un array() vide
//
// Usage: array preg_files('ecrire/data/', '[.]lock$');
//
// Attention, afin de conserver la compatibilite avec les repertoires '.plat'
// si $dir = 'rep/sous_rep_' au lieu de 'rep/sous_rep/' on scanne 'rep/' et on
// applique un pattern '^rep/sous_rep_'
// si $recurs vaut false, la fonction ne descend pas dans les sus repertoires
//
// http://doc.spip.org/@preg_files
function preg_files($dir, $pattern=-1 /* AUTO */, $maxfiles = 10000, $recurs=array()) {
	$nbfiles = 0;
	if ($pattern == -1)
		$pattern = "^$dir";
	$fichiers = array();
	// revenir au repertoire racine si on a recu dossier/truc
	// pour regarder dossier/truc/ ne pas oublier le / final
	$dir = preg_replace(',/[^/]*$,', '', $dir);
	if ($dir == '') $dir = '.';

	if (@is_dir($dir) AND is_readable($dir) AND $d = @opendir($dir)) {
		while (($f = readdir($d)) !== false && ($nbfiles<$maxfiles)) {
			if ($f[0] != '.' # ignorer . .. .svn etc
			AND $f != 'CVS'
			AND $f != 'remove.txt'
			AND is_readable($f = "$dir/$f")) {
				if (is_file($f)) {
					if (preg_match(";$pattern;iS", $f))
					{
						$fichiers[] = $f;
						$nbfiles++;
					}
				} 
				else if (is_dir($f) AND is_array($recurs)){
					$rp = @realpath($f);
					if (!is_string($rp) OR !strlen($rp)) $rp=$f; # realpath n'est peut etre pas autorise
					if (!isset($recurs[$rp])) {
						$recurs[$rp] = true;
						$beginning = $fichiers;
						$end = preg_files("$f/", $pattern,
							$maxfiles-$nbfiles, $recurs);
						$fichiers = array_merge((array)$beginning, (array)$end);
						$nbfiles = count($fichiers);
					}
				}
			}
		}
		closedir($d);
	}
	sort($fichiers);
	return $fichiers;
}

?>
