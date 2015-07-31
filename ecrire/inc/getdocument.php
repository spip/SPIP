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
	while (@file_exists($dir.($newFile = $dest.($n++ ? '-'.$n : '').'.'.$ext)));

	$newFile = preg_replace('/[.]+/', '.', $newFile);
	if ($r = deplacer_fichier_upload($source, $dir.$newFile))
		return $dir.$newFile;
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
		@chmod($dest, _DIR_CHMOD & ~0111);
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

	minipres($msg, '<form action="' .
		rawurldecode($GLOBALS['redirect']).
		'" method="post"><div align="'.  #ici method='post' permet d'aller au bon endroit, alors qu'en GET on perd les variables... mais c'est un hack sale.
		$spip_lang_right.
		'"><input type="submit" class="fondl"  value="'.
		_T('ecrire:bouton_suivant').
		' &gt;&gt;"></div></form>');
}


//
// Gestion des fichiers ZIP
//
// http://doc.spip.org/@accepte_fichier_upload
function accepte_fichier_upload ($f) {
	if (!ereg(".*__MACOSX/", $f)
	AND !ereg("^\.", basename($f))) {
		$ext = corriger_extension((strtolower(substr(strrchr($f, "."), 1))));
		$row =  @spip_fetch_array(spip_query("SELECT extension FROM spip_types_documents WHERE extension=" . spip_abstract_quote($ext) . " AND upload='oui'"));
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

// http://doc.spip.org/@verifier_compactes
function verifier_compactes($zip) {
	if (!$list = $zip->listContent()) return array();
	// si pas possible de decompacter: installer comme fichier zip joint
	// Verifier si le contenu peut etre uploade (verif extension)
	$aff_fichiers = array();
	foreach ($list as $file) {
		if (accepte_fichier_upload($f = $file['stored_filename']))
			$aff_fichiers[]= $f;
		else spip_log("chargement de $f interdit");
		}
	sort($aff_fichiers);
	return $aff_fichiers;
}

//
// Ajouter un document (au format $_FILES)
//
# $source,	# le fichier sur le serveur (/var/tmp/xyz34)
# $nom_envoye,	# son nom chez le client (portequoi.pdf)
# $type_lien,	# lie a un article, une breve ou une rubrique ?
# $id_lien,	# identifiant de l'article (ou rubrique) lie
# $mode,	# 'vignette' => image en mode image
#		# ou vignette personnalisee liee a un document
		# 'document' => doc ou image en mode document
		# 'distant' => lien internet
# $id_document,	# pour une vignette, l'id_document de maman
# $actifs	# les documents dont il faudra ouvrir la boite de dialogue

// http://doc.spip.org/@ajouter_un_document
function ajouter_un_document ($source, $nom_envoye, $type_lien, $id_lien, $mode, $id_document, &$documents_actifs) {

// Documents distants : pas trop de verifications bloquantes, mais un test
// via une requete HEAD pour savoir si la ressource existe (non 404), si le
// content-type est connu, et si possible recuperer la taille, voire plus.
	spip_log ("ajout du document $nom_envoye  (M '$mode' T '$type_lien' L '$id_lien' D '$id_document')");
	if ($mode == 'distant') {
		include_spip('inc/distant');
		if ($a = recuperer_infos_distantes($source)) {
			# fichier local pour creer la vignette (!!),
			# on retablira la valeur de l'url a la fin
			$fichier = $a['fichier'];

			$id_type = $a['id_type'];
			$taille = $a['taille'];
			$titre = $a['titre'];
			$largeur = $a['largeur'];
			$hauteur = $a['hauteur'];
			$ext = $a['extension'];
			$type_image = $a['type_image'];

			$distant = 'oui';
			$mode = 'document';
		}
		else {
			spip_log("Echec du lien vers le document $source, abandon");
			return;
		}
	}

	else {
	
		$distant = 'non';

		// tester le type de document :
		// - interdit a l'upload ?
		// - quel numero dans spip_types_documents ?  =-(
		// - est-ce "inclus" comme une image ?
		ereg("\.([^.]+)$", $nom_envoye, $match);
		$ext = (corriger_extension(strtolower($match[1])));

		// Si le fichier est de type inconnu, on va le stocker en .zip
		$q = spip_query("SELECT * FROM spip_types_documents WHERE extension=" . spip_abstract_quote($ext) . " AND upload='oui'");
		if (!$row = spip_fetch_array($q)) {

/* STOCKER LES DOCUMENTS INCONNUS AU FORMAT .BIN */
/*			$ext = 'bin';
			$nom_envoye .= '.bin';
			spip_log("Extension $ext");
			$row = spip_fetch_array(spip_query("SELECT * FROM spip_types_documents WHERE extension='bin' AND upload='oui'"));
			if (!$row) {
				spip_log("Extension $ext interdite a l'upload");
				return;
			}
*/

/* STOCKER LES DOCUMENTS INCONNUS AU FORMAT .ZIP */
			$ext = 'zip';

			$row = spip_fetch_array(spip_query("SELECT * FROM spip_types_documents WHERE extension='zip' AND upload='oui'"));
			if (!$row) {
				spip_log("Extension $ext interdite a l'upload");
				return;
			}
			if (!$tmp_dir = tempnam(_DIR_TMP, 'tmp_upload')) return;
			@unlink($tmp_dir); @mkdir($tmp_dir);
			if (!is_dir(_DIR_IMG.'tmp')) @mkdir(_DIR_IMG.'tmp');
			$tmp = $tmp_dir.'/'.translitteration($nom_envoye);
			$nom_envoye .= '.zip'; # conserver l'extension dans le nom de fichier, par exemple toto.js => toto.js.zip
			$fichier = deplacer_fichier_upload($source, $tmp);
			include_spip('inc/pclzip');
			$source = _DIR_IMG.'tmp/archive.zip';
			$archive = new PclZip($source);
			$v_list = $archive->create($tmp,
				PCLZIP_OPT_REMOVE_PATH, $tmp_dir,
				PCLZIP_OPT_ADD_PATH, '');
			effacer_repertoire_temporaire($tmp_dir);
			if (!$v_list) {
				spip_log("Echec creation du zip ");
				return;
			}
		}
		$id_type = $row['id_type'];	# numero du type dans spip_types_documents:(
		$type_inclus_image = ($row['inclus'] == 'image');

		// Recopier le fichier a son emplacement definitif
		$fichier = copier_document($ext, $nom_envoye, $source);
		if (!$fichier) {
			spip_log("Impossible de copier_document($ext, $nom_envoye, $source)");
			return;
		}

		// Prevoir traitement specifique pour videos
		// (http://www.getid3.org/ peut-etre
		if ($ext == "mov") {
			$largeur = 0;
			$hauteur = 0;
		} else 	if ($ext == "svg") {
		  // recuperer les dimensions et supprimer les scripts
				list($largeur,$hauteur)= traite_svg($fichier);
		} else {
		// Si c'est une image, recuperer sa taille et son type (detecte aussi swf)
			$size_image = @getimagesize($fichier);
			$largeur = intval($size_image[0]);
			$hauteur = intval($size_image[1]);
			$type_image = decoder_type_image($size_image[2]);
		}

		// Quelques infos sur le fichier
		if (!@file_exists($fichier)
		OR !$taille = @filesize($fichier)) {
			spip_log ("Echec copie du fichier $fichier");
			return;
		}

		if (!$type_image) {
			if (_DOC_MAX_SIZE > 0
			AND $taille > _DOC_MAX_SIZE*1024) {
				@unlink ($fichier);
				check_upload_error(6,
				_T('info_logo_max_poids',
					array('maxi' => taille_en_octets(_DOC_MAX_SIZE*1024),
					'actuel' => taille_en_octets($taille))));
			}
		}
		else {
			if (_IMG_MAX_SIZE > 0
			AND $taille > _IMG_MAX_SIZE*1024) {
				@unlink ($fichier);
				check_upload_error(6,
				_T('info_logo_max_poids',
					array('maxi' => taille_en_octets(_IMG_MAX_SIZE*1024),
					'actuel' => taille_en_octets($taille))));
			}
	
			if (_IMG_MAX_WIDTH * _IMG_MAX_HEIGHT
			AND ($size_image[0] > _IMG_MAX_WIDTH
			OR $size_image[1] > _IMG_MAX_HEIGHT)) {
				@unlink ($fichier);
				check_upload_error(6, 
				_T('info_logo_max_taille',
					array(
					'maxi' =>
						_T('info_largeur_vignette',
							array('largeur_vignette' => _IMG_MAX_WIDTH,
							'hauteur_vignette' => _IMG_MAX_HEIGHT)),
					'actuel' =>
						_T('info_largeur_vignette',
							array('largeur_vignette' => $size_image[0],
							'hauteur_vignette' => $size_image[1]))
				)));
			}
		}

		// Si on veut uploader une vignette, il faut qu'elle ait ete bien lue
		if ($mode == 'vignette') {
			if (!$type_inclus_image) {
				spip_log ("le format de $fichier ne convient pas pour une image"); # SVG
				@unlink($fichier);
				return;
			}

			if (!($largeur * $hauteur)) {
				spip_log('erreur upload vignette '.$fichier);
				@unlink($fichier);
				return;
			}
		}
	}

	// Preparation vignette du document $id_document
	$id_document=intval($id_document);
	if ($mode == 'vignette' AND $id_document_lie = $id_document) {
		# on force le statut "document" de ce fichier (inutile ?)
		spip_query("UPDATE spip_documents SET mode='document' WHERE id_document=$id_document");
		$id_document = 0;
	}

	// Installer le document dans la base
	// attention piege semantique : les images s'installent en mode 'vignette'
	// note : la fonction peut "mettre a jour un document" si on lui
	// passe "mode=document" et "id_document=.." (pas utilise)
	if (!$id_document) {
		// Inserer le nouveau doc et recuperer son id_
		$id_document = spip_abstract_insert("spip_documents", "(id_type, titre, date, distant)", "($id_type, " . spip_abstract_quote($titre) . ", NOW(), '$distant')");

		if ($id_lien
		AND preg_match('/^[a-z0-9_]+$/i', $type_lien) # securite
		    ) {
		  spip_abstract_insert("spip_documents_".$type_lien."s",
				       "(id_document, id_".$type_lien.")",
				       "($id_document, $id_lien)");
		}
		// par defaut (upload ZIP ou ftp) integrer
		// les images en mode 'vignette' et le reste en mode document
		if (!$mode)
			if ($type_image AND $type_inclus_image)
				$mode = 'vignette';
			else
				$mode = 'document';
		$update = "mode='$mode', ";
	}

	// Mise a jour des donnees
	spip_query("UPDATE spip_documents SET $update taille='$taille', largeur='$largeur', hauteur='$hauteur', fichier='$fichier' WHERE id_document=$id_document");

	if ($id_document_lie) {
		spip_query("UPDATE spip_documents SET id_vignette=$id_document	WHERE id_document=$id_document_lie");
		// hack pour que le retour vers ecrire/ active le bon doc.
		$documents_actifs[] = $id_document_lie; 
	}
	else
		$documents_actifs[] = $id_document; 

	// Pour les fichiers distants remettre l'URL de base
	if ($distant == 'oui')
		spip_query("UPDATE spip_documents SET fichier=" . spip_abstract_quote($source) . " WHERE id_document = $id_document");

	// Demander l'indexation du document
	include_spip('inc/indexation');
	marquer_indexer('spip_documents', $id_document);

	return $type_image;
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



//
// Traiter la liste des fichiers (action joindre3)
//

// http://doc.spip.org/@examiner_les_fichiers
function examiner_les_fichiers($files, $mode, $type, $id, $id_document, $hash, $redirect, &$actifs)
{
	if (function_exists('gzopen') 
	AND !($mode == 'distant')
	AND (count($files) == 1)) {

		$desc = $files[0];
		if (preg_match('/\.zip$/i', $desc['name'])
		    OR ($desc['type'] == 'application/zip')) {
	
	  // on pose le fichier dans le repertoire zip 
	  // (nota : copier_document n'ecrase pas un fichier avec lui-meme
	  // ca autorise a boucler)
			$zip = copier_document("zip",
					$desc['name'],
					$desc['tmp_name']
				);
			if (!$zip) die ('Erreur upload zip'); # pathologique
			// Est-ce qu'on sait le lire ?
			include_spip('inc/pclzip');
			$archive = new PclZip($zip);
			if ($archive) {
			  $valables = verifier_compactes($archive);
			  if ($valables) {
			    liste_archive_jointe($valables, $mode, $type, $id, $id_document, $hash, $redirect, $zip);
			    exit;
			  }
			}
		}
	}
	foreach ($files as $arg) {
		check_upload_error($arg['error']);
		$x = ajouter_un_document($arg['tmp_name'], $arg['name'], 
				    $type, $id, $mode, $id_document, $actifs);
	}
	return $x;
}

// Afficher un formulaire de choix: decompacter et/ou garder tel quel.
// Passer ca en squelette un de ces jours.

function liste_archive_jointe($valables, $mode, $type, $id, $id_document, $hash, $redirect, $zip)
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
		"<div><input type='radio' name='sousaction4' value='4'>".
		_L('les_deux').
		"</div>".
		"<div style='text-align: right;'><input class='fondo' style='font-size: 9px;' type='submit' value='".
		_T('bouton_valider').
		  "'></div>";
	$action = (construire_upload($texte, array(
					 'redirect' => $redirect,
					 'hash' => $hash,
					 'chemin' => $zip,
					 'arg' => $arg)));
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
