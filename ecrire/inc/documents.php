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

/**
 * donne le chemin du fichier relatif a _DIR_IMG
 * pour stockage 'tel quel' dans la base de donnees
 *
 * http://doc.spip.org/@set_spip_doc
 *
 * @param string $fichier
 * @return string
 */
function set_spip_doc($fichier) {
	if (strpos($fichier, _DIR_IMG) === 0)
		return substr($fichier, strlen(_DIR_IMG));
	else
		return $fichier; // ex: fichier distant
}

/**
 * donne le chemin complet du fichier
 *
 * http://doc.spip.org/@get_spip_doc
 *
 * @param string $fichier
 * @return bool|string
 */
function get_spip_doc($fichier) {
	// fichier distant
	if (preg_match(',^\w+://,', $fichier))
		return $fichier;

	// gestion d'erreurs, fichier=''
	if (!strlen($fichier))
		return false;

	$fichier = (
		    strncmp($fichier,_DIR_IMG, strlen(_DIR_IMG))!=0
		)
		? _DIR_IMG . $fichier
		: $fichier ;

	// fichier normal
	return $fichier;
}

/**
 * Creer IMG/pdf/
 *
 * http://doc.spip.org/@creer_repertoire_documents
 *
 * @param  $ext
 * @return string
 */
function creer_repertoire_documents($ext) {
	$rep = sous_repertoire(_DIR_IMG, $ext);

	if (!$ext OR !$rep) {
		spip_log("creer_repertoire_documents '$rep' interdit");
		exit;
	}

	// Cette variable de configuration peut etre posee par un plugin
	// par exemple acces_restreint
	if (isset($GLOBALS['meta']["creer_htaccess"]) AND $GLOBALS['meta']["creer_htaccess"] == 'oui') {
		include_spip('inc/acces');
		verifier_htaccess($rep);
	}

	return $rep;
}

/**
 * Efface le repertoire de maniere recursive !
 *
 * http://doc.spip.org/@effacer_repertoire_temporaire
 *
 * @param string $nom
 */
function effacer_repertoire_temporaire($nom) {
	$d = opendir($nom);
	while (($f = readdir($d)) !== false) {
		if (is_file("$nom/$f"))
			spip_unlink("$nom/$f");
		else if ($f <> '.' AND $f <> '..'
		AND is_dir("$nom/$f"))
			effacer_repertoire_temporaire("$nom/$f");
	}
	closedir($d);
	@rmdir($nom);
}

//
/**
 * Copier un document $source un dossier IMG/$ext/$orig.$ext
 * en numerotant eventuellement si un du meme nom existe deja
 *
 * http://doc.spip.org/@copier_document
 *
 * @param string $ext
 * @param string $orig
 * @param string $source
 * @return bool|mixed|string
 */
function copier_document($ext, $orig, $source) {

	$orig = preg_replace(',\.\.+,', '.', $orig); // pas de .. dans le nom du doc
	$dir = creer_repertoire_documents($ext);
	$dest = preg_replace("/[^.=\w-]+/", "_",
			translitteration(preg_replace("/\.([^.]+)$/", "",
						      preg_replace("/<[^>]*>/", '', basename($orig)))));

	// ne pas accepter de noms de la forme -r90.jpg qui sont reserves
	// pour les images transformees par rotation (action/documenter)
	$dest = preg_replace(',-r(90|180|270)$,', '', $dest);

	// Si le document "source" est deja au bon endroit, ne rien faire
	if ($source == ($dir . $dest . '.' . $ext))
		return $source;

	// sinon tourner jusqu'a trouver un numero correct
	$n = 0;
	while (@file_exists($newFile = $dir . $dest .($n++ ? ('-'.$n) : '').'.'.$ext));

	return deplacer_fichier_upload($source, $newFile);
}

/**
 * Trouver le dossier utilise pour upload un fichier
 *
 * http://doc.spip.org/@determine_upload
 *
 * @param string $type
 * @return bool|string
 */
function determine_upload($type='') {
	if(!function_exists('autoriser'))
		include_spip('inc/autoriser');
	
	if (!autoriser('chargerftp')
	OR $type == 'logos') # on ne le permet pas pour les logos
		return false;

	$repertoire = _DIR_TRANSFERT;
	if (!@is_dir($repertoire)) {
		$repertoire = str_replace(_DIR_TMP, '', $repertoire);
		$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
	}

	if (!$GLOBALS['visiteur_session']['restreint'])
		return $repertoire;
	else
		return sous_repertoire($repertoire, $GLOBALS['visiteur_session']['login']);
}

/**
 * Deplacer ou copier un fichier
 *
 * http://doc.spip.org/@deplacer_fichier_upload
 *
 * @param string $source
 * @param string $dest
 * @param bool $move
 * @return bool|mixed|string
 */
function deplacer_fichier_upload($source, $dest, $move=false) {
	// Securite
	if (substr($dest,0,strlen(_DIR_RACINE))==_DIR_RACINE)
		$dest = _DIR_RACINE.preg_replace(',\.\.+,', '.', substr($dest,strlen(_DIR_RACINE)));
	else
		$dest = preg_replace(',\.\.+,', '.', $dest);

	if ($move)	$ok = @rename($source, $dest);
	else				$ok = @copy($source, $dest);
	if (!$ok) $ok = @move_uploaded_file($source, $dest);
	if ($ok)
		@chmod($dest, _SPIP_CHMOD & ~0111);
	else {
		$f = @fopen($dest,'w');
		if ($f) {
			fclose ($f);
		} else {
			include_spip('inc/flock');
			raler_fichier($dest);
		}
		spip_unlink($dest);
	}
	return $ok ? $dest : false;
}


// Erreurs d'upload
// renvoie false si pas d'erreur
// et true si erreur = pas de fichier
// pour les autres erreurs affiche le message d'erreur et meurt
// http://doc.spip.org/@check_upload_error
function check_upload_error($error, $msg='', $return=false) {
	global $spip_lang_right;

	if (!$error) return false;

	spip_log("Erreur upload $error -- cf. http://php.net/manual/fr/features.file-upload.errors.php");

	switch ($error) {

		case 4: /* UPLOAD_ERR_NO_FILE */
			return true;

		# on peut affiner les differents messages d'erreur
		case 1: /* UPLOAD_ERR_INI_SIZE */
			$msg = _T('upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
		case 2: /* UPLOAD_ERR_FORM_SIZE */
			$msg = _T('upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
		case 3: /* UPLOAD_ERR_PARTIAL  */
			$msg = _T('upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;

		default: /* autre */
			if (!$msg)
			$msg = _T('pass_erreur').' '. $error
			. '<br />' . propre("[->http://php.net/manual/fr/features.file-upload.errors.php]");
			break;
	}

	spip_log ("erreur upload $error");
	if ($return)
		return $msg;

  if(_request("iframe")=="iframe") {
	  echo "<div class='upload_answer upload_error'>$msg</div>";
	  exit;
	}

	include_spip('inc/minipres');
	echo minipres($msg,
		      "<div style='text-align: $spip_lang_right'><a href='"  . rawurldecode($GLOBALS['redirect']) . "'><button type='button'>" . _T('ecrire:bouton_suivant') . "</button></a></div>");
	exit;
}
?>
