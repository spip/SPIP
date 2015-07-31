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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/minipres');

// Creer IMG/pdf/
// http://doc.spip.org/@creer_repertoire_documents
function creer_repertoire_documents($ext) {
	$rep = sous_repertoire(_DIR_DOC, $ext);

	if (!$ext OR !$rep) {
		spip_log("creer_repertoire_documents interdit");
		exit;
	}

	if ($GLOBALS['meta']["creer_htaccess"] == 'oui') {
		include_spip('inc/acces');
		verifier_htaccess($rep);
	}

	return $rep;
}

// Efface le repertoire de maniere recursive !
// http://doc.spip.org/@effacer_repertoire_temporaire
function effacer_repertoire_temporaire($nom) {
	$d = opendir($nom);
	while (($f = readdir($d)) !== false) {
		if (is_file("$nom/$f"))
			@unlink("$nom/$f");
		else if ($f <> '.' AND $f <> '..'
		AND is_dir("$nom/$f"))
			effacer_repertoire_temporaire("$nom/$f");
	}
	@rmdir($nom);
}

// http://doc.spip.org/@copier_document
function copier_document($ext, $orig, $source) {

	$dir = creer_repertoire_documents($ext);
	$dest = ereg_replace("[^.a-zA-Z0-9_=-]+", "_", 
			translitteration(ereg_replace("\.([^.]+)$", "", 
						      ereg_replace("<[^>]*>", '', basename($orig)))));

	// ne pas accepter de noms de la forme -r90.jpg qui sont reserves
	// pour les images transformees par rotation (action/documenter)
	$dest = preg_replace(',-r(90|180|270)$,', '', $dest);

	// Si le document "source" est deja au bon endroit, ne rien faire
	if ($source == ($dir . $dest . '.' . $ext))
		return $source;

	// sinon tourner jusqu'a trouver un numero correct
	$n = 0;
	while (@file_exists($newFile = $dir . $dest .($n++ ? ('-'.$n) : '').'.'.$ext));

	return (deplacer_fichier_upload($source, $newFile)) ? $newFile : '';
}

//
// Deplacer un fichier
//

// http://doc.spip.org/@deplacer_fichier_upload
function deplacer_fichier_upload($source, $dest, $move=false) {
	// Securite
	## !! interdit pour le moment d'uploader depuis l'espace prive (UPLOAD_DIRECT)
	if (strstr($dest, "..")) {
		spip_log("stop deplacer_fichier_upload: '$dest'");
		exit;
	}

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
			include_spip('inc/headers');
			redirige_par_entete(generer_url_action("test_dirs", "test_dir=". dirname($dest), true));
		}
		@unlink($dest);
	}
	return $ok;
}


// Erreurs d'upload
// renvoie false si pas d'erreur
// et true si erreur = pas de fichier
// pour les autres erreurs affiche le message d'erreur et meurt
// http://doc.spip.org/@check_upload_error
function check_upload_error($error, $msg='') {
	global $spip_lang_right;
	switch ($error) {
		case 0:
			return false;
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
	}

	spip_log ("erreur upload $error");

  if(_request("iframe")=="iframe") {
    echo "<div class='upload_answer upload_error'>$msg</div>";
    exit;
  }
  
	echo minipres($msg, '<form action="' .
		rawurldecode($GLOBALS['redirect']).
		'" method="post"><div align="'.  #ici method='post' permet d'aller au bon endroit, alors qu'en GET on perd les variables... mais c'est un hack sale.
		$spip_lang_right.
		'"><input type="submit" class="fondl"  value="'.
		_T('ecrire:bouton_suivant').
		' &gt;&gt;"></div></form>');
	exit;
}

// Erreur appelee depuis public.php (la precedente ne fonctionne plus
// depuis qu'on est sortis de spip_image.php, apparemment).
// http://doc.spip.org/@erreur_upload_trop_gros
function erreur_upload_trop_gros() {
	include_spip('inc/filtres');
	
	$msg = 		"<p>"
		.taille_en_octets($_SERVER["CONTENT_LENGTH"])
		.'<br />'
		._T('upload_limit',
		array('max' => ini_get('upload_max_filesize')))
		."</p>";
	
  echo minipres(_T('pass_erreur'),"<div class='upload_answer upload_error'>".$msg."</div>");
	exit;
}

//
// Gestion des fichiers ZIP
//
// http://doc.spip.org/@accepte_fichier_upload
function accepte_fichier_upload ($f) {
	if (!ereg(".*__MACOSX/", $f)
	AND !ereg("^\.", basename($f))) {
		$ext = corriger_extension((strtolower(substr(strrchr($f, "."), 1))));
		$row =  @spip_fetch_array(spip_query("SELECT extension FROM spip_types_documents WHERE extension=" . _q($ext) . " AND upload='oui'"));
		return $row;
	}
}

# callback pour le deballage d'un zip telecharge
# http://www.phpconcept.net/pclzip/man/en/?options-pclzip_cb_pre_extractfunction
// http://doc.spip.org/@callback_deballe_fichier
function callback_deballe_fichier($p_event, &$p_header) {
	if (accepte_fichier_upload($p_header['filename'])) {
		$p_header['filename'] = _tmp_dir . basename($p_header['filename']);
		return 1;
	} else {
		return 0;
	}
}

?>
