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

/**
 * Gestion des documents et de leur emplacement sur le serveur
 *
 * @package SPIP\Core\Documents
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Donne le chemin du fichier relatif à `_DIR_IMG`
 * pour stockage 'tel quel' dans la base de données
 *
 * @uses _DIR_IMG
 *
 * @param string $fichier
 * @return string
 */
function set_spip_doc($fichier) {
	if (strpos($fichier, _DIR_IMG) === 0) {
		return substr($fichier, strlen(_DIR_IMG));
	} else {
		return $fichier;
	} // ex: fichier distant
}

/**
 * Donne le chemin complet du fichier
 *
 * @uses _DIR_IMG
 *
 * @param string $fichier
 * @return bool|string
 */
function get_spip_doc($fichier) {
	// fichier distant
	if (tester_url_absolue($fichier)) {
		return $fichier;
	}

	// gestion d'erreurs, fichier=''
	if (!strlen($fichier)) {
		return false;
	}

	$fichier = (
		strncmp($fichier, _DIR_IMG, strlen(_DIR_IMG)) != 0
	)
		? _DIR_IMG . $fichier
		: $fichier;

	// fichier normal
	return $fichier;
}

/**
 * Créer un sous-répertoire IMG/$ext/ tel que IMG/pdf
 *
 * @uses sous_repertoire()
 * @uses _DIR_IMG
 * @uses verifier_htaccess()
 *
 * @param string $ext
 * @return string
 */
function creer_repertoire_documents($ext) {
	$rep = sous_repertoire(_DIR_IMG, $ext);

	if (!$ext or !$rep) {
		spip_log("creer_repertoire_documents '$rep' interdit");
		exit;
	}

	// Cette variable de configuration peut etre posee par un plugin
	// par exemple acces_restreint
	// sauf pour logo/ utilise pour stocker les logoon et logooff
	if (isset($GLOBALS['meta']['creer_htaccess'])
		and $GLOBALS['meta']['creer_htaccess'] == 'oui'
	  and $ext !== 'logo') {
		include_spip('inc/acces');
		verifier_htaccess($rep);
	}

	return $rep;
}

/**
 * Efface le répertoire de manière récursive !
 *
 * @param string $nom
 */
function effacer_repertoire_temporaire($nom) {
	if ($d = opendir($nom)) {
		while (($f = readdir($d)) !== false) {
			if (is_file("$nom/$f")) {
				spip_unlink("$nom/$f");
			} else {
				if ($f <> '.' and $f <> '..'
					and is_dir("$nom/$f")
				) {
					effacer_repertoire_temporaire("$nom/$f");
				}
			}
		}
	}
	closedir($d);
	@rmdir($nom);
}

//
/**
 * Copier un document `$source`
 * dans un dossier `IMG/$ext/$orig.$ext` ou `IMG/$subdir/$orig.$ext` si `$subdir` est fourni
 * en numérotant éventuellement si un fichier de même nom existe déjà
 *
 * @param string $ext
 * @param string $orig
 * @param string $source
 * @param string $subdir
 * @return bool|mixed|string
 */
function copier_document($ext, $orig, $source, $subdir = null) {

	$orig = preg_replace(',\.\.+,', '.', $orig); // pas de .. dans le nom du doc
	$dir = creer_repertoire_documents($subdir ? $subdir : $ext);
	$dest = preg_replace(
		'/[^.=\w-]+/',
		'_',
		translitteration(preg_replace(
			'/\.([^.]+)$/',
			'',
			preg_replace('/<[^>]*>/', '', basename($orig))
		))
	);

	// ne pas accepter de noms de la forme -r90.jpg qui sont reserves
	// pour les images transformees par rotation (action/documenter)
	$dest = preg_replace(',-r(90|180|270)$,', '', $dest);

	// Si le document "source" est deja au bon endroit, ne rien faire
	if ($source == ($dir . $dest . '.' . $ext)) {
		return $source;
	}

	// sinon tourner jusqu'a trouver un numero correct
	$n = 0;
	while (@file_exists($newFile = $dir . $dest . ($n++ ? ('-' . $n) : '') . '.' . $ext)) {
		;
	}

	return deplacer_fichier_upload($source, $newFile);
}

/**
 * Trouver le dossier utilisé pour upload un fichier
 *
 * @uses autoriser()
 * @uses _DIR_TRANSFERT
 * @uses _DIR_TMP
 * @uses sous_repertoire()
 *
 * @param string $type
 * @return bool|string
 */
function determine_upload($type = '') {
	if (!function_exists('autoriser')) {
		include_spip('inc/autoriser');
	}

	if (!autoriser('chargerftp')
		or $type == 'logos'
	) { # on ne le permet pas pour les logos
	return false;
	}

	$repertoire = _DIR_TRANSFERT;
	if (!@is_dir($repertoire)) {
		$repertoire = str_replace(_DIR_TMP, '', $repertoire);
		$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
	}

	if (!$GLOBALS['visiteur_session']['restreint']) {
		return $repertoire;
	} else {
		return sous_repertoire($repertoire, $GLOBALS['visiteur_session']['login']);
	}
}

/**
 * Déplacer ou copier un fichier
 *
 * @uses _DIR_RACINE
 * @uses spip_unlink()
 *
 * @param string $source
 *     Fichier source à copier
 * @param string $dest
 *     Fichier de destination
 * @param bool $move
 *     - `true` : on déplace le fichier source vers le fichier de destination
 *     - `false` : valeur par défaut. On ne fait que copier le fichier source vers la destination.
 * @return bool|mixed|string
 */
function deplacer_fichier_upload($source, $dest, $move = false) {
	// Securite
	if (substr($dest, 0, strlen(_DIR_RACINE)) == _DIR_RACINE) {
		$dest = _DIR_RACINE . preg_replace(',\.\.+,', '.', substr($dest, strlen(_DIR_RACINE)));
	} else {
		$dest = preg_replace(',\.\.+,', '.', $dest);
	}

	if ($move) {
		$ok = @rename($source, $dest);
	} else {
		$ok = @copy($source, $dest);
	}
	if (!$ok) {
		$ok = @move_uploaded_file($source, $dest);
	}
	if ($ok) {
		@chmod($dest, _SPIP_CHMOD & ~0111);
	} else {
		$f = @fopen($dest, 'w');
		if ($f) {
			fclose($f);
		} else {
			include_spip('inc/flock');
			raler_fichier($dest);
		}
		spip_unlink($dest);
	}

	return $ok ? $dest : false;
}


/**
 * Erreurs d'upload
 *
 * Renvoie `false` si pas d'erreur
 * et `true` s'il n'y a pas de fichier à uploader.
 * Pour les autres erreurs, on affiche le message d'erreur et on arrête l'action.
 *
 * @link http://php.net/manual/fr/features.file-upload.errors.php
 *     Explication sur les messages d'erreurs de chargement de fichiers.
 * @uses propre()
 * @uses minipres()
 *
 * @global string $spip_lang_right
 * @param integer $error
 * @param string $msg
 * @param bool $return
 * @return boolean|string
 */
function check_upload_error($error, $msg = '', $return = false) {

	if (!$error) {
		return false;
	}

	spip_log("Erreur upload $error -- cf. http://php.net/manual/fr/features.file-upload.errors.php");

	switch ($error) {
		case 4: /* UPLOAD_ERR_NO_FILE */
			return true;

		# on peut affiner les differents messages d'erreur
		case 1: /* UPLOAD_ERR_INI_SIZE */
			$msg = _T(
				'upload_limit',
				array('max' => ini_get('upload_max_filesize'))
			);
			break;
		case 2: /* UPLOAD_ERR_FORM_SIZE */
			$msg = _T(
				'upload_limit',
				array('max' => ini_get('upload_max_filesize'))
			);
			break;
		case 3: /* UPLOAD_ERR_PARTIAL  */
			$msg = _T(
				'upload_limit',
				array('max' => ini_get('upload_max_filesize'))
			);
			break;

		default: /* autre */
			if (!$msg) {
				$msg = _T('pass_erreur') . ' ' . $error
					. '<br />' . propre('[->http://php.net/manual/fr/features.file-upload.errors.php]');
			}
			break;
	}

	spip_log("erreur upload $error");
	if ($return) {
		return $msg;
	}

	if (_request('iframe') == 'iframe') {
		echo "<div class='upload_answer upload_error'>$msg</div>";
		exit;
	}

	include_spip('inc/minipres');
	echo minipres(
		$msg,
		"<div style='text-align: " . $GLOBALS['spip_lang_right'] . "'><a href='" . rawurldecode($GLOBALS['redirect']) . "'><button type='button'>" . _T('ecrire:bouton_suivant') . '</button></a></div>'
	);
	exit;
}
