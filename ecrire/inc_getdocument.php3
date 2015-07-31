<?php

//
// Fonctions complementaires a spip_image.php3

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_GETDOCUMENT")) return;
define("_ECRIRE_INC_GETDOCUMENT", "1");


function creer_repertoire_documents($ext) {
	global $dossier_squelettes;

# est-il bien raisonnable d'accepter de creer si creer_rep retourne '' ?
	$rep = _DIR_DOC . creer_repertoire(_DIR_DOC, $ext);
	// Securite
	if (!$ext || ($rep == $dossier_squelettes) || (substr($rep,0,-1) == $dossier_squelettes))
		{
		  spip_log("creer_repertoire_documents interdit");
		  exit;
		}
	if (lire_meta("creer_htaccess") == 'oui') {
		include_ecrire('inc_acces.php3');
		verifier_htaccess($rep);
	}
	return $rep;
}


function copier_document($ext, $orig, $source) {

	$dir = creer_repertoire_documents($ext);
	$dest = $dir .
		ereg_replace("[^.a-zA-Z0-9_=-]+", "_", 
			translitteration(ereg_replace("\.([^.]+)$", "", 
						      ereg_replace("<[^>]*>", '', basename($orig)))));

	# bien vu ?
	if ($source == ($dest . '.' . $ext)) return $source;
	$n = 0;
	while (@file_exists($newFile = $dest.($n++ ? '-'.$n : '').'.'.$ext));
	$r = deplacer_fichier_upload($source, $newFile);
	return (!$r ? '' : $newFile);
}

function verifier_compactes($image_name) {

	$zip = new PclZip($image_name);

	if ($list = $zip->listContent()) {
	// si pas possible de decompacter: installer comme fichier zip joint
	// Verifier si le contenu peut etre uploade (verif extension)
		$aff_fichiers = array();
		for ($i=0; $i<sizeof($list); $i++) {
			for(reset($list[$i]); $key = key($list[$i]); next($list[$i])) {
			
				if ($key == "stored_filename") {
					$f =  $list[$i][$key];
					  // Regexp des fichiers a ignorer
					if (!ereg("^(\.|.*/\.|.*__MACOSX/)", $f)) {
						if (ereg("\.([^.]+)$", $f, $match)) {
							$result = spip_query("SELECT * FROM spip_types_documents WHERE extension='"
. corriger_extension(addslashes(strtolower($match[1]))) 
									     . "' AND upload='oui'");
							if ($row = @spip_fetch_array($result))
								$aff_fichiers[]= $f;
							else
							spip_log("chargement de $f interdit");
							
						}
					}
				}
			}
		}
	}

	return $aff_fichiers ;
}

function afficher_compactes($image_name, $fichiers, $link) {
// presenter une interface pour choisir si fichier joint ou decompacter
// passer ca en squelette un de ces jours.

	include_ecrire ("inc_presentation.php3");
	install_debut_html(_T('upload_fichier_zip'));
	echo "<p>",
		_T('upload_fichier_zip_texte'),
		"</p>",
		"<p>",
		_T('upload_fichier_zip_texte2'),
		"</p>",
		$link->getForm('POST'),
		"<div><input type='radio' checked name='action_zip' value='telquel'>",
		_T('upload_zip_telquel'),
		"</div>",
		"<div><input type='radio' name='action_zip' value='decompacter'>",
		_T('upload_zip_decompacter'),
		"</div>",
		"<ul><li>" ,
		 join("</li>\n<li>",$fichiers) ,
		 "</li></ul>",
		"<div>&nbsp;</div>",
		"<div style='text-align: right;'><input class='fondo' style='font-size: 9px;' type='submit' value='",
		_T('bouton_valider'),
		"'></div>",
		"</form>";
	install_fin_html();
}

//
// Ajouter un unique document
//

function ajout_doc($orig, $source, $mode, $id_document) {
	global $hash_id_auteur, $hash, $id_article, $type;

	//
	// Securite
	//
	if (!verifier_action_auteur("ajout_doc", $hash, $hash_id_auteur))
		return;

	// type de document inconnu ?
	if (!ereg("\.([^.]+)$", $orig, $match)) return;

	$ext = corriger_extension(addslashes(strtolower($match[1])));
	$row = spip_query("SELECT * FROM spip_types_documents WHERE extension='$ext' AND upload='oui'" . (($mode != 'vignette') ? '' : " AND inclus='image'"));

	// type de document invalide ?
	if (!$row = spip_fetch_array($row)) {return;}

	// Recopier le fichier sauf si deja fait (zip tel quel)
	$dest_path = !$source ? $orig : copier_document($ext,$orig, $source);
	if (!$dest_path) return;
	$taille = filesize($dest_path);

	$size_image = @getimagesize($dest_path);
	$type_image = decoder_type_image($size_image[2]);
	if ($type_image) {
		$largeur = $size_image[0];
		$hauteur = $size_image[1];
	}

	if ($taille == 0
	OR ($mode == 'vignette' AND ($largeur == 0 OR $hauteur==0)))
		return;

	// Preparation
	if ($mode == 'vignette') {
		$id_document_lie = $id_document;
		$query = "UPDATE spip_documents SET mode='document' where id_document='$id_document_lie'";
		spip_query($query); // requete inutile a mon avis (Fil)...
		$id_document = 0;
	}
	if (!$id_document) {
		$id_type = $row['id_type'];
		$id_document = spip_abstract_insert("spip_documents", "(id_type, titre, date)", "($id_type, '', NOW())");
		$nouveau = true;
		if ($id_article && isset($type)) {
			$query = "INSERT INTO spip_documents_".$type."s (id_document, id_".$type.") VALUES ($id_document, $id_article)";
			spip_query($query);
		}
	}
	if ($nouveau) {
		$type_inclus = $row['inclus'];
		if (!$mode) $mode = ($type_image AND $type_inclus == 'image') ? 'vignette' : 'document';
			$update = "mode='$mode', ";
	}

	spip_query("UPDATE spip_documents SET $update taille='$taille', largeur='$largeur', hauteur='$hauteur', fichier='$dest_path' WHERE id_document=$id_document");

	if ($id_document_lie) {
		$query = "UPDATE spip_documents SET id_vignette=$id_document WHERE id_document=$id_document_lie";
		spip_query($query);
		$id_document = $id_document_lie; // pour que le 'return' active le bon doc.
	}

	// Creer la vignette
	if ($mode == 'document' AND ereg(",$ext,", ','.lire_meta('formats_graphiques').',')) {
		creer_fichier_vignette($dest_path);
	}
}

// Ajouter un ou plusieurs documents ?

function ajout_doc_zip($image, $image_name, $mode, $forcer_document, $action_zip, $id_document, $hash, $link)
{
// image_name n'est valide que par POST http, mais pas par la methode ftp/upload
// par ailleurs, pour un fichier ftp/upload, il faut effacer l'original nous-memes
// action_zip indique un rappel par la fonction affiche_compactes
	if (!$action_zip){
		// on va se rappeler: copier le fichier car PHP va le virer
		$image_name = copier_document("zip", $image_name, $image);
		// anormal, on se tire
		if (!$image_name) exit;
		// renvoyer un formulaire demandant si on deballe ou pas
		require_once(_DIR_RESTREINT . 'pclzip.lib.php');
		$fichiers = verifier_compactes($image_name);
		if ($fichiers) {
			$link->addVar("image_name", $image_name);
			afficher_compactes($image_name, $fichiers, $link);
			exit;
		}
		// pas possible de deballer, on continue
		$forcer_document = 'oui';
		$image = $image_name;
	  }
	  else {
	    // reponse au formulaire
		if ($action_zip == "telquel") {
			$forcer_document = 'oui';
			
		} else {
			require_once(_DIR_RESTREINT . 'pclzip.lib.php');
  			$archive = new PclZip($image_name);
			$tmp_dir = creer_repertoire_documents($hash);
			$archive->extract(PCLZIP_OPT_PATH, $tmp_dir, PCLZIP_OPT_REMOVE_ALL_PATH);
			# virer le zip après le déballage
			@unlink($image_name);
			$image_name = $tmp_dir;

		}
	  }
	ajout_doc_s($image, $image_name, $mode, $forcer_document, $id_document, $hash);
}

function ajout_doc_s($image, $image_name, $mode, $forcer_document, $id_document, $hash, $error=0) {

	if (check_upload_error($error)) return;

	$mode = ($forcer_document == 'oui' ? "document" : $mode);

	if (!is_dir($image_name)) {
		ajout_doc($image_name,
			$image,
			$mode,
			$id_document);
	} else {
		include_ecrire('inc_documents.php3');
		$fichiers = fichiers_upload($image_name);

		while (list(,$f) = each($fichiers)) {
			if (ereg("\.([^.]+)$", $f, $match)) {
				$ext = strtolower($match[1]);
				if ($ext == 'jpeg')
					$ext = 'jpg';
				$r = spip_query("SELECT extension FROM spip_types_documents WHERE extension='$ext'" . ($inclus ? " AND inclus='$inclus'" : ''));
				if (spip_fetch_array($r))
					ajout_doc($f, $f, $mode, false);
			}
		}

# détruire le repertoire de deballage
		if ($tmp_dir) effacer_repertoire_temporaire($tmp_dir);
	}
}

function effacer_repertoire_temporaire($nom) {
	$d = opendir($nom);
	while ($f = readdir($d)) {
		if (is_file($f = "$nom/$f")) @unlink($f);
		}
	@rmdir($nom);
}

?>
