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
  
	minipres($msg, '<form action="' .
		rawurldecode($GLOBALS['redirect']).
		'" method="post"><div align="'.  #ici method='post' permet d'aller au bon endroit, alors qu'en GET on perd les variables... mais c'est un hack sale.
		$spip_lang_right.
		'"><input type="submit" class="fondl"  value="'.
		_T('ecrire:bouton_suivant').
		' &gt;&gt;"></div></form>');
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
	
  minipres(_T('pass_erreur'),"<div class='upload_answer upload_error'>".$msg."</div>");
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

// http://doc.spip.org/@traite_svg
function traite_svg($file)
{
	global $connect_statut;
	$texte = spip_file_get_contents($file);

	// Securite si pas guru: virer les scripts et les references externes
	// Trop expeditif, a ameliorer

        $var_auth = charger_fonction('auth', 'inc');
	$var_auth();

	if ($connect_statut != '0minirezo') {
		include_spip('inc/texte');
		$new = trim(safehtml($texte));
		// petit bug safehtml
		if (substr($new,0,2) == ']>') $new = ltrim(substr($new,2));
		if ($new != $texte) ecrire_fichier($file, $texte = $new);
		
	}

	$width = $height = 150;
	if (preg_match(',<svg[^>]+>,', $texte, $s)) {
		$s = $s[0];
		if (preg_match(',\WviewBox\s*=\s*.\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+),i', $s, $r)){
			$width = $r[3];
                	$height = $r[4];
		} else {
	// si la taille est en centimetre, estimer le pixel a 1/64 de cm
		if (preg_match(',\Wwidth\s*=\s*.(\d+)([^"\']*),i', $s, $r)){
			if ($r[2] != '%') {
				$width = $r[1];
				if ($r[2] == 'cm') $width <<=6;
			}	
		}
		if (preg_match(',\Wheight\s*=\s*.(\d+)([^"\']*),i', $s, $r)){
			if ($r[2] != '%') {
	                	$height = $r[1];
				if ($r[2] == 'cm') $height <<=6;
			}
		}
	   }
	}
	return array($width, $height);
}



// Afficher un formulaire de choix: decompacter et/ou garder tel quel.
// Passer ca en squelette un de ces jours.

// http://doc.spip.org/@liste_archive_jointe
function liste_archive_jointe($valables, $mode, $type, $id, $id_document, $hash, $redirect, $zip, $iframe_redirect)
{
	$arg = (intval($id) .'/' .intval($id_document) . "/$mode/$type");
	$texte =
		"<div><input type='radio' checked='checked' name='sousaction5' value='5'>" .
	  	_T('upload_zip_telquel').
		"</div>".
		"<div><input type='radio' name='sousaction5' value='6'>".
		_T('upload_zip_decompacter').
		"</div>".
		"<ol><li><tt>" .
		join("</tt></li>\n<li><tt>", $valables) .
		"</tt></li></ol>".
		"<div>&nbsp;</div>" .
		"<div><input type='checkbox' name='sousaction4' value='4'>".
		_T('les_deux').
		"</div>".
		"<div style='text-align: right;'><input class='fondo' style='font-size: 9px;' type='submit' value='".
		_T('bouton_valider').
		  "'></div>";
	echo "<p>build form $iframe_redirect</p>";
  $action = construire_upload($texte, array(
					 'redirect' => $redirect,
					 'iframe_redirect' => $iframe_redirect,
					 'hash' => $hash,
					 'chemin' => $zip,
					 'arg' => $arg));
	
	if(_request("iframe")=="iframe") {
    echo "<div class='upload_answer upload_zip_list'><p>" .
		_T('upload_fichier_zip_texte') .
	  "</p><p>" .
		_T('upload_fichier_zip_texte2') .
	  "</p>" .
	  $action.
	  "</div>";
    exit;
  }
  				 
	minipres(_T('upload_fichier_zip'),
	  "<p>" .
		_T('upload_fichier_zip_texte') .
	  "</p><p>" .
		_T('upload_fichier_zip_texte2') .
	  "</p>" .
	  $action);
	// a tout de suite en joindre4, joindre5, ou joindre6
}


// Reconstruit un generer_action_auteur 

// http://doc.spip.org/@construire_upload
function construire_upload($corps, $args, $enctype='')
{
	$res = "";
	foreach($args as $k => $v)
	  if ($v)
	    $res .= "\n<input type='hidden' name='$k' value='$v' />";

# ici enlever $action pour uploader directemet dans l'espace prive (UPLOAD_DIRECT)
	return "\n<form method='post' action='" . generer_url_action('joindre') .
	  "'" .
	  (!$enctype ? '' : " enctype='$enctype'") .
	  " 
	  >\n" .
	  "<div>" .
  	  "\n<input type='hidden' name='action' value='joindre' />" .
	  $res . $corps . "</div></form>";
}

//
// Convertit le type numerique retourne par getimagesize() en extension fichier
//

// http://doc.spip.org/@decoder_type_image
function decoder_type_image($type, $strict = false) {
	switch ($type) {
	case 1:
		return "gif";
	case 2:
		return "jpg";
	case 3:
		return "png";
	case 4:
		return $strict ? "" : "swf";
	case 5:
		return "psd";
	case 6:
		return "bmp";
	case 7:
	case 8:
		return "tif";
	default:
		return "";
	}
}


//
// Corrige l'extension du fichier dans quelques cas particuliers
// (a passer dans ecrire/base/typedoc)
//

// http://doc.spip.org/@corriger_extension
function corriger_extension($ext) {
	switch ($ext) {
	case 'htm':
		return 'html';
	case 'jpeg':
		return 'jpg';
	case 'tiff':
		return 'tif';
	default:
		return $ext;
	}
}
?>
