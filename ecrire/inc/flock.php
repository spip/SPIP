<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion de recherche et d'écriture de répertoire ou fichiers
 *
 * @package SPIP\Core\Flock
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('_TEST_FILE_EXISTS')) {
	/** Permettre d'éviter des tests file_exists sur certains hébergeurs */
	define('_TEST_FILE_EXISTS', preg_match(',(online|free)[.]fr$,', $_ENV['HTTP_HOST'] ?? ''));
}

#define('_SPIP_LOCK_MODE',0); // ne pas utiliser de lock (deconseille)
#define('_SPIP_LOCK_MODE',1); // utiliser le flock php
#define('_SPIP_LOCK_MODE',2); // utiliser le nfslock de spip

if (_SPIP_LOCK_MODE == 2) {
	include_spip('inc/nfslock');
}

$GLOBALS['liste_verrous'] = [];

/**
 * Ouvre un fichier et le vérrouille
 *
 * @link http://php.net/manual/fr/function.flock.php pour le type de verrou.
 * @see  _SPIP_LOCK_MODE
 * @see  spip_fclose_unlock()
 * @uses spip_nfslock() si _SPIP_LOCK_MODE = 2.
 *
 * @param string $fichier
 *     Chemin du fichier
 * @param string $mode
 *     Mode d'ouverture du fichier (r,w,...)
 * @param int $verrou
 *     Type de verrou (avec _SPIP_LOCK_MODE = 1)
 * @return resource|bool
 *     Ressource sur le fichier ouvert, sinon false.
 **/
function spip_fopen_lock($fichier, $mode, $verrou) {
	if (_SPIP_LOCK_MODE == 1) {
		if ($fl = @fopen($fichier, $mode)) {
			// verrou
			@flock($fl, $verrou);
		}

		return $fl;
	} elseif (_SPIP_LOCK_MODE == 2) {
		if (($verrou = spip_nfslock($fichier)) && ($fl = @fopen($fichier, $mode))) {
			$GLOBALS['liste_verrous'][$fl] = [$fichier, $verrou];

			return $fl;
		} else {
			return false;
		}
	}

	return @fopen($fichier, $mode);
}

/**
 * Dévérrouille et ferme un fichier
 *
 * @see _SPIP_LOCK_MODE
 * @see spip_fopen_lock()
 *
 * @param resource $handle
 *     Chemin du fichier
 * @return bool
 *     true si succès, false sinon.
 **/
function spip_fclose_unlock($handle) {
	if (_SPIP_LOCK_MODE == 1) {
		@flock($handle, LOCK_UN);
	} elseif (_SPIP_LOCK_MODE == 2) {
		spip_nfsunlock(reset($GLOBALS['liste_verrous'][$handle]), end($GLOBALS['liste_verrous'][$handle]));
		unset($GLOBALS['liste_verrous'][$handle]);
	}

	return @fclose($handle);
}


/**
 * Retourne le contenu d'un fichier, même si celui ci est compréssé
 * avec une extension en `.gz`
 *
 * @param string $fichier
 *     Chemin du fichier
 * @return string
 *     Contenu du fichier
 **/
function spip_file_get_contents($fichier) {
	if (!str_ends_with($fichier, '.gz')) {
		if (function_exists('file_get_contents')) {
			// quand on est sous windows on ne sait pas si file_get_contents marche
			// on essaye : si ca retourne du contenu alors c'est bon
			// sinon on fait un file() pour avoir le coeur net
			$contenu = @file_get_contents($fichier);
			if (!$contenu && _OS_SERVEUR == 'windows') {
				$contenu = @file($fichier);
			}
		} else {
			$contenu = @file($fichier);
		}
	} else {
		$contenu = @gzfile($fichier);
	}

	return is_array($contenu) ? implode('', $contenu) : (string)$contenu;
}


/**
 * Lit un fichier et place son contenu dans le paramètre transmis.
 *
 * Décompresse automatiquement les fichiers `.gz`
 *
 * @uses spip_fopen_lock()
 * @uses spip_file_get_contents()
 * @uses spip_fclose_unlock()
 *
 * @param string $fichier
 *     Chemin du fichier
 * @param string $contenu
 *     Le contenu du fichier sera placé dans cette variable
 * @param array $options
 *     Options tel que :
 *
 *     - 'phpcheck' => 'oui' : vérifie qu'on a bien du php
 * @return bool
 *     true si l'opération a réussie, false sinon.
 **/
function lire_fichier($fichier, &$contenu, $options = []) {
	$contenu = '';
	// inutile car si le fichier n'existe pas, le lock va renvoyer false juste apres
	// economisons donc les acces disque, sauf chez free qui rale pour un rien
	if (_TEST_FILE_EXISTS && !@file_exists($fichier)) {
		return false;
	}

	#spip_timer('lire_fichier');

	// pas de @ sur spip_fopen_lock qui est silencieux de toute facon
	if ($fl = spip_fopen_lock($fichier, 'r', LOCK_SH)) {
		// lire le fichier avant tout
		$contenu = spip_file_get_contents($fichier);

		// le fichier a-t-il ete supprime par le locker ?
		// on ne verifie que si la tentative de lecture a echoue
		// pour discriminer un contenu vide d'un fichier absent
		// et eviter un acces disque
		if (!$contenu && !@file_exists($fichier)) {
			spip_fclose_unlock($fl);

			return false;
		}

		// liberer le verrou
		spip_fclose_unlock($fl);

		// Verifications
		$ok = true;
		if (isset($options['phpcheck']) && $options['phpcheck'] == 'oui') {
			$ok &= (preg_match(",[?]>\n?$,", $contenu));
		}

		#spip_log("$fread $fichier ".spip_timer('lire_fichier'));
		if (!$ok) {
			spip_log("echec lecture $fichier");
		}

		return $ok;
	}

	return false;
}


/**
 * Écrit un fichier de manière un peu sûre
 *
 * Cette écriture s’exécute de façon sécurisée en posant un verrou sur
 * le fichier avant sa modification. Les fichiers .gz sont compressés.
 *
 * @uses raler_fichier() Si le fichier n'a pu peut être écrit
 * @see  lire_fichier()
 * @see  supprimer_fichier()
 *
 * @param string $fichier
 *     Chemin du fichier
 * @param string $contenu
 *     Contenu à écrire
 * @param bool $ignorer_echec
 *     - true pour ne pas raler en cas d'erreur
 *     - false affichera un message si on est webmestre
 * @param bool $truncate
 *     Écriture avec troncation ?
 * @return bool
 *     - true si l’écriture s’est déroulée sans problème.
 **/
function ecrire_fichier($fichier, $contenu, $ignorer_echec = false, $truncate = true): bool {

	#spip_timer('ecrire_fichier');

	// verrouiller le fichier destination
	if ($fp = spip_fopen_lock($fichier, 'a', LOCK_EX)) {
		// ecrire les donnees, compressees le cas echeant
		// (on ouvre un nouveau pointeur sur le fichier, ce qui a l'avantage
		// de le recreer si le locker qui nous precede l'avait supprime...)
		if (str_ends_with($fichier, '.gz')) {
			$contenu = gzencode($contenu);
		}
		// si c'est une ecriture avec troncation , on fait plutot une ecriture complete a cote suivie unlink+rename
		// pour etre sur d'avoir une operation atomique
		// y compris en NFS : http://www.ietf.org/rfc/rfc1094.txt
		// sauf sous wintruc ou ca ne marche pas
		$ok = false;
		if ($truncate && _OS_SERVEUR != 'windows') {
			if (!function_exists('creer_uniqid')) {
				include_spip('inc/acces');
			}
			$id = creer_uniqid();
			// on ouvre un pointeur sur un fichier temporaire en ecriture +raz
			if ($fp2 = spip_fopen_lock("$fichier.$id", 'w', LOCK_EX)) {
				$s = @fwrite($fp2, (string) $contenu, $a = strlen((string) $contenu));
				$ok = ($s === $a);
				spip_fclose_unlock($fp2);
				spip_fclose_unlock($fp);
				$fp = null;
				// unlink direct et pas spip_unlink car on avait deja le verrou
				// a priori pas besoin car rename ecrase la cible
				// @unlink($fichier);
				// le rename aussitot, atomique quand on est pas sous windows
				// au pire on arrive en second en cas de concourance, et le rename echoue
				// --> on a la version de l'autre process qui doit etre identique
				@rename("$fichier.$id", $fichier);
				// precaution en cas d'echec du rename
				if (!_TEST_FILE_EXISTS || @file_exists("$fichier.$id")) {
					@unlink("$fichier.$id");
				}
				if ($ok) {
					$ok = file_exists($fichier);
				}
			} else // echec mais penser a fermer ..
			{
				spip_fclose_unlock($fp);
				$fp = null;
			}
		}
		// sinon ou si methode precedente a echoueee
		// on se rabat sur la methode ancienne
		if (!$ok && !is_null($fp)) {
			// ici on est en ajout ou sous windows, cas desespere
			if ($truncate) {
				@ftruncate($fp, 0);
			}
			$s = @fwrite($fp, $contenu, $a = strlen($contenu));

			$ok = ($s == $a);
			spip_fclose_unlock($fp);
		}
		// on tente une dernière fois file_put_contents
		if (!$ok) {
			$l = file_put_contents($fichier, $contenu, $truncate ? LOCK_EX : LOCK_EX | FILE_APPEND);
			$ok = ($l === strlen($contenu));
		}

		// liberer le verrou et fermer le fichier
		@chmod($fichier, _SPIP_CHMOD & 0666);
		if ($ok) {
			if (str_contains($fichier, '.php')) {
				spip_clear_opcode_cache(realpath($fichier));
			}

			return $ok;
		}
	}

	if (!$ignorer_echec) {
		include_spip('inc/autoriser');
		if (autoriser('chargerftp')) {
			raler_fichier($fichier);
		}
		spip_unlink($fichier);
	}
	spip_log("Ecriture fichier $fichier impossible", _LOG_INFO_IMPORTANTE);

	return false;
}

/**
 * Écrire un contenu dans un fichier encapsulé en PHP pour en empêcher l'accès en l'absence
 * de fichier htaccess
 *
 * @uses ecrire_fichier()
 *
 * @param string $fichier
 *     Chemin du fichier
 * @param string $contenu
 *     Contenu à écrire
 * @param bool $ecrire_quand_meme
 *     - true pour ne pas raler en cas d'erreur
 *     - false affichera un message si on est webmestre
 * @param bool $truncate
 *     Écriture avec troncation ?
 */
function ecrire_fichier_securise($fichier, $contenu, $ecrire_quand_meme = false, $truncate = true) {
	if (!str_ends_with($fichier, '.php')) {
		spip_log('Erreur de programmation: ' . $fichier . ' doit finir par .php');
	}
	$contenu = '<' . "?php die ('Acces interdit'); ?" . ">\n" . $contenu;

	return ecrire_fichier($fichier, $contenu, $ecrire_quand_meme, $truncate);
}


/**
 * @param string $fichier
 * @param string $contenu
 * @param bool $force
 * @return bool
 */
function ecrire_fichier_calcule_si_modifie($fichier, $contenu, $force = false, $use_copy = false) {
	$fichier_tmp = $fichier . '.last';
	if (!ecrire_fichier($fichier_tmp, $contenu, true)) {
		return false;
	}
	if (
		$force
		|| !file_exists($fichier)
		|| md5_file($fichier) !== md5_file($fichier_tmp)
	) {
		if ($use_copy) {
			@copy($fichier_tmp, $fichier);
		}
		else {
			@rename($fichier_tmp, $fichier);
		}
		// eviter que PHP ne reserve le vieux timestamp
		clearstatcache(true, $fichier);
	}
	return true;
}


/**
 * Lire un fichier encapsulé en PHP
 *
 * @uses lire_fichier()
 *
 * @param string $fichier
 *     Chemin du fichier
 * @param string $contenu
 *     Le contenu du fichier sera placé dans cette variable
 * @param array $options
 *     Options tel que :
 *
 *     - 'phpcheck' => 'oui' : vérifie qu'on a bien du php
 * @return bool
 *     true si l'opération a réussie, false sinon.
 */
function lire_fichier_securise($fichier, &$contenu, $options = []) {
	if ($res = lire_fichier($fichier, $contenu, $options)) {
		$contenu = substr((string) $contenu, strlen('<?php die (\'Acces interdit\'); ?>
'));
	}

	return $res;
}

/**
 * Affiche un message d’erreur bloquant, indiquant qu’il n’est pas possible de créer
 * le fichier à cause des droits sur le répertoire parent au fichier.
 *
 * Arrête le script PHP par un exit;
 *
 * @uses minipres() Pour afficher le message
 **/
function raler_fichier(string $fichier): never {
	if (!defined('_SPIP_ECRIRE_SCRIPT')) {
		spip_initialisation_suite();
	}
	include_spip('inc/minipres');
	$dir = dirname($fichier);
	http_response_code(401);
	echo minipres(_T('texte_inc_meta_2'), "<h4 style='color: red'>"
		. _T('texte_inc_meta_1', ['fichier' => $fichier])
		. " <a href='"
		. generer_url_ecrire('install', "etape=chmod&test_dir=$dir")
		. "'>"
		. _T('texte_inc_meta_2')
		. '</a> '
		. _T(
			'texte_inc_meta_3',
			['repertoire' => joli_repertoire($dir)]
		)
		. "</h4>\n");
	exit;
}


/**
 * Teste si un fichier est récent (moins de n secondes)
 *
 * @param string $fichier
 *     Chemin du fichier
 * @param int $n
 *     Âge testé, en secondes
 * @return bool
 *     - true si récent, false sinon
 */
function jeune_fichier($fichier, $n) {
	if (!file_exists($fichier)) {
		return false;
	}
	if (!$c = @filemtime($fichier)) {
		return false;
	}

	return (time() - $n <= $c);
}

/**
 * Supprimer un fichier de manière sympa (flock)
 *
 * @param string $fichier
 *     Chemin du fichier
 * @param bool $lock
 *     true pour utiliser un verrou
 * @return bool
 *     - true si le fichier n'existe pas ou s'il a bien été supprimé
 *     - false si on n'arrive pas poser le verrou ou si la suppression échoue
 */
function supprimer_fichier($fichier, $lock = true) {
	if (!@file_exists($fichier)) {
		return true;
	}

	if ($lock) {
		// verrouiller le fichier destination
		if (!$fp = spip_fopen_lock($fichier, 'a', LOCK_EX)) {
			return false;
		}

		// liberer le verrou
		spip_fclose_unlock($fp);
	}

	// supprimer
	return @unlink($fichier);
}

/**
 * Supprimer brutalement un fichier ou un dossier, s'il existe
 *
 * @param string $f
 *     Chemin du fichier
 */
function spip_unlink($f) {
	if (!is_dir($f)) {
		supprimer_fichier($f, false);
	} else {
		@unlink("$f/.ok");
		@rmdir($f);
	}
}

/**
 * Invalidates a PHP file from any active opcode caches.
 *
 * If the opcode cache does not support the invalidation of individual files,
 * the entire cache will be flushed.
 * kudo : http://cgit.drupalcode.org/drupal/commit/?id=be97f50
 *
 * @param string $filepath
 *   The absolute path of the PHP file to invalidate.
 */
function spip_clear_opcode_cache($filepath) {
	clearstatcache(true, $filepath);

	// Zend OPcache
	if (function_exists('opcache_invalidate')) {
		$invalidate = @opcache_invalidate($filepath, true);
		// si l'invalidation a echoue lever un flag
		if (!$invalidate && !defined('_spip_attend_invalidation_opcode_cache')) {
			define('_spip_attend_invalidation_opcode_cache', true);
		}
	} elseif (!defined('_spip_attend_invalidation_opcode_cache')) {
		// n'agira que si opcache est effectivement actif (il semble qu'on a pas toujours la fonction opcache_invalidate)
		define('_spip_attend_invalidation_opcode_cache', true);
	}
	// APC.
	if (function_exists('apc_delete_file')) {
		// apc_delete_file() throws a PHP warning in case the specified file was
		// not compiled yet.
		// @see http://php.net/apc-delete-file
		@apc_delete_file($filepath);
	}
}

/**
 * Attendre l'invalidation de l'opcache
 *
 * Si opcache est actif et en mode `validate_timestamps`,
 * le timestamp du fichier ne sera vérifié qu'après une durée
 * en secondes fixée par `revalidate_freq`.
 *
 * Il faut donc attendre ce temps là pour être sûr qu'on va bien
 * bénéficier de la recompilation du fichier par l'opcache.
 *
 * Ne fait rien en dehors de ce cas
 *
 * @note
 *     C'est une config foireuse déconseillée de opcode cache mais
 *     malheureusement utilisée par Octave.
 * @link http://stackoverflow.com/questions/25649416/when-exactly-does-php-5-5-opcache-check-file-timestamp-based-on-revalidate-freq
 * @link http://wiki.mikejung.biz/PHP_OPcache
 *
 */
function spip_attend_invalidation_opcode_cache($timestamp = null) {
	if (
		function_exists('opcache_get_configuration')
		&& @ini_get('opcache.enable')
		&& @ini_get('opcache.validate_timestamps')
		&& (($duree = (int) @ini_get('opcache.revalidate_freq')) || ($duree = 2))
		&& defined('_spip_attend_invalidation_opcode_cache') // des invalidations ont echouees
	) {
		$wait = $duree + 1;
		if ($timestamp) {
			$wait -= (time() - $timestamp);
			if ($wait < 0) {
				$wait = 0;
			}
		}
		spip_log('Probleme de configuration opcache.revalidate_freq ' . $duree . 's : on attend ' . $wait . 's', _LOG_INFO_IMPORTANTE);
		if ($wait) {
			sleep($duree + 1);
		}
	}
}


/**
 * Suppression complete d'un repertoire.
 *
 * @link http://www.php.net/manual/en/function.rmdir.php#92050
 *
 * @param string $dir Chemin du repertoire
 * @return bool Suppression reussie.
 */
function supprimer_repertoire($dir) {
	if (!file_exists($dir)) {
		return true;
	}
	if (!is_dir($dir) || is_link($dir)) {
		return @unlink($dir);
	}

	foreach (scandir($dir) as $item) {
		if ($item == '.' || $item == '..') {
			continue;
		}
		if (!supprimer_repertoire($dir . '/' . $item)) {
			@chmod($dir . '/' . $item, 0777);
			if (!supprimer_repertoire($dir . '/' . $item)) {
				return false;
			}
		};
	}

	return @rmdir($dir);
}


/**
 * Crée un sous répertoire
 *
 * Retourne `$base/{$subdir}/` si le sous-repertoire peut être crée
 *
 * @example
 *     ```
 *     sous_repertoire(_DIR_CACHE, 'demo');
 *     sous_repertoire(_DIR_CACHE . '/demo');
 *     ```
 *
 * @param string $base
 *     - Chemin du répertoire parent (avec $subdir)
 *     - sinon chemin du répertoire à créer
 * @param string $subdir
 *     - Nom du sous répertoire à créer,
 *     - non transmis, `$subdir` vaut alors ce qui suit le dernier `/` dans `$base`
 * @param bool $nobase
 *     true pour ne pas avoir le chemin du parent `$base/` dans le retour
 * @param bool $tantpis
 *     true pour ne pas raler en cas de non création du répertoire
 * @return string
 *     Chemin du répertoire créé.
 **/
function sous_repertoire($base, $subdir = '', $nobase = false, $tantpis = false): string {
	static $dirs = [];

	$base = str_replace('//', '/', $base);

	# suppr le dernier caractere si c'est un /
	$base = rtrim($base, '/');

	if (!strlen($subdir)) {
		$n = strrpos($base, '/');
		if ($n === false) {
			return $nobase ? '' : ($base . '/');
		}
		$subdir = substr($base, $n + 1);
		$base = substr($base, 0, $n + 1);
	} else {
		$base .= '/';
		$subdir = str_replace('/', '', $subdir);
	}

	$baseaff = $nobase ? '' : $base;
	if (isset($dirs[$base . $subdir])) {
		return $baseaff . $dirs[$base . $subdir];
	}

	$path = $base . $subdir; # $path = 'IMG/distant/pdf' ou 'IMG/distant_pdf'

	if (file_exists("$path/.ok")) {
		return $baseaff . ($dirs[$base . $subdir] = "$subdir/");
	}

	@mkdir($path, _SPIP_CHMOD);
	@chmod($path, _SPIP_CHMOD);

	if (is_dir($path) && is_writable($path)) {
		@touch("$path/.ok");
		spip_log("creation $base$subdir/");

		return $baseaff . ($dirs[$base . $subdir] = "$subdir/");
	}

	// en cas d'echec c'est peut etre tout simplement que le disque est plein :
	// l'inode du fichier dir_test existe, mais impossible d'y mettre du contenu
	spip_log("echec creation $base{$subdir}");
	if ($tantpis) {
		return '';
	}
	// FIXME: throw an Exception…
	if (!_DIR_RESTREINT) {
		$base = preg_replace(',^' . _DIR_RACINE . ',', '', $base);
	}
	$base .= $subdir;
	raler_fichier($base . '/.ok');
}


/**
 * Parcourt récursivement le repertoire `$dir`, et renvoie les
 * fichiers dont le chemin vérifie le pattern (preg) donné en argument.
 *
 * En cas d'echec retourne un `array()` vide
 *
 * @example
 *     ```
 *     $x = preg_files('ecrire/data/', '[.]lock$');
 *     // $x array()
 *     ```
 *
 * @note
 *   Attention, afin de conserver la compatibilite avec les repertoires '.plat'
 *   si `$dir = 'rep/sous_rep_'` au lieu de `rep/sous_rep/` on scanne `rep/` et on
 *   applique un pattern `^rep/sous_rep_`
 *
 * @param string $dir
 *     Répertoire à parcourir
 * @param int|string $pattern
 *     Expression régulière pour trouver des fichiers, tel que `[.]lock$`
 * @param int $maxfiles
 *     Nombre de fichiers maximums retournés
 * @param array $recurs
 *     false pour ne pas descendre dans les sous répertoires
 * @return array
 *     Chemins des fichiers trouvés.
 **/
function preg_files($dir, $pattern = -1 /* AUTO */, $maxfiles = 10000, $recurs = []) {
	$nbfiles = 0;
	if ($pattern == -1) {
		$pattern = '';
	}
	$fichiers = [];
	// revenir au repertoire racine si on a recu dossier/truc
	// pour regarder dossier/truc/ ne pas oublier le / final
	$dir = preg_replace(',/[^/]*$,', '', $dir);
	if ($dir == '') {
		$dir = '.';
	}

	if (@is_dir($dir) && is_readable($dir) && ($d = opendir($dir))) {
		while (($f = readdir($d)) !== false && ($nbfiles < $maxfiles)) {
			if (
				$f[0] != '.'
				&& $f != 'remove.txt'
				&& is_readable($f = "$dir/$f")
			) {
				if (is_file($f)) {
					if (!$pattern || preg_match(";$pattern;iS", $f)) {
						$fichiers[] = $f;
						$nbfiles++;
					}
				} else {
					if (is_dir($f) && is_array($recurs)) {
						$rp = @realpath($f);
						if (!is_string($rp) || !strlen($rp)) {
							$rp = $f;
						} # realpath n'est peut etre pas autorise
						if (!isset($recurs[$rp])) {
							$recurs[$rp] = true;
							$beginning = $fichiers;
							$end = preg_files(
								"$f/",
								$pattern,
								$maxfiles - $nbfiles,
								$recurs
							);
							$fichiers = array_merge((array)$beginning, (array)$end);
							$nbfiles = count($fichiers);
						}
					}
				}
			}
		}
		closedir($d);
	}
	sort($fichiers);

	return $fichiers;
}
