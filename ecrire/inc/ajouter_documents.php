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

include_spip('inc/getdocument');
include_spip('inc/documents');

// Joindre un document ou un Zip a deballer (fonction pour action/joindre)
// Distinguer les deux cas pour commencer

// http://doc.spip.org/@inc_ajouter_documents_dist
function inc_ajouter_documents_dist ($sources, $file, $type, $id, $mode, $id_document, &$actifs, $hash='', $redirect='', $iframe_redirect='')
{
	if (is_array($sources))
	  return liste_archive_jointe($sources, $file, $type, $id, $mode, $id_document, $hash, $redirect, $iframe_redirect);
	else
	  return ajouter_un_document($sources, $file, $type, $id, $mode, $id_document, $actifs);
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
function ajouter_un_document($source, $nom_envoye, $type_lien, $id_lien, $mode, $id_document, &$documents_actifs) {

// Documents distants : pas trop de verifications bloquantes, mais un test
// via une requete HEAD pour savoir si la ressource existe (non 404), si le
// content-type est connu, et si possible recuperer la taille, voire plus.
	spip_log ("ajout du document $nom_envoye  (M '$mode' T '$type_lien' L '$id_lien' D '$id_document')");
	if ($mode == 'distant') {
		include_spip('inc/distant');
		if ($a = recuperer_infos_distantes($source)) {

			# NB: dans les bonnes conditions (fichier autorise et pas trop gros)
			# $a['fichier'] est une copie locale du fichier

			$fichier = $source;
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
	} else  {$distant = 'non';
		$type_image = $titre = ''; // au pire
		// tester le type de document :
		// - interdit a l'upload ?
		// - quel numero dans spip_types_documents ?  =-(
		// - est-ce "inclus" comme une image ?
		preg_match(",\.([^.]+)$,", $nom_envoye, $match);
		$ext = (corriger_extension(strtolower($match[1])));

		// Si le fichier est de type inconnu, on va le stocker en .zip
		$q = spip_query("SELECT * FROM spip_types_documents WHERE extension=" . _q($ext) . " AND upload='oui'");
		if (!$row = spip_abstract_fetch($q)) {

/* STOCKER LES DOCUMENTS INCONNUS AU FORMAT .BIN */
/*			$ext = 'bin';
			$nom_envoye .= '.bin';
			spip_log("Extension $ext");
			$row = spip_abstract_fetch(spip_query("SELECT * FROM spip_types_documents WHERE extension='bin' AND upload='oui'"));
			if (!$row) {
				spip_log("Extension $ext interdite a l'upload");
				return;
			}
*/

/* STOCKER LES DOCUMENTS INCONNUS AU FORMAT .ZIP */
			$ext = 'zip';
			$row = spip_abstract_fetch(spip_query("SELECT * FROM spip_types_documents WHERE extension='zip' AND upload='oui'"));
			if (!$row) {
				spip_log("Extension $ext interdite a l'upload");
				return;
			}
			if (!$tmp_dir = tempnam(_DIR_TMP, 'tmp_upload')) return;
			@unlink($tmp_dir); @mkdir($tmp_dir);
			$tmp = $tmp_dir.'/'.translitteration($nom_envoye);
			$nom_envoye .= '.zip'; # conserver l'extension dans le nom de fichier, par exemple toto.js => toto.js.zip
			deplacer_fichier_upload($source, $tmp);
			include_spip('inc/pclzip');
			$source = _DIR_TMP . 'archive.zip';
			$archive = new PclZip($source);
			$v_list = $archive->create($tmp,
				PCLZIP_OPT_REMOVE_PATH, $tmp_dir,
				PCLZIP_OPT_ADD_PATH, '');
			effacer_repertoire_temporaire($tmp_dir);
			if (!$v_list) {
				spip_log("Echec creation du zip ");
				return;
			}
			$fichier = copier_document($ext, $nom_envoye, $source);
			@unlink($source);

		} else $fichier = copier_document($ext, $nom_envoye, $source);

		// Verifier que le fichier est a son emplacement definitif
		
		if (!$fichier) {
			spip_log("Impossible de copier_document($ext, $nom_envoye, $source)");
			return;
		}
		$extension = $row['extension'];
		$type_inclus_image = ($row['inclus'] == 'image');

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
		else { // image
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
	$id_document_lie = $id_document=intval($id_document);
	if ($mode == 'vignette' AND  $id_document) {
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
		$id_document = spip_abstract_insert("spip_documents", "(extension, titre, date, distant)", "("._q($ext).", " . _q($titre) . ", NOW(), '$distant')");

		if ($id_lien
		AND preg_match('/^[a-z0-9_]+$/i', $type_lien) # securite
		) {
			spip_abstract_insert("spip_documents_".$type_lien."s",
				"(id_document, id_".$type_lien.")",
				"($id_document, $id_lien)"
			);
		}
		// par defaut (upload ZIP ou ftp) integrer
		// les images en mode 'vignette' et le reste en mode document
		if (!in_array($mode, array('vignette', 'distant', 'document')))
			if ($type_image AND $type_inclus_image)
				$mode = 'vignette';
			else
				$mode = 'document';
		$update = "mode='$mode', ";
	}

	// Mise a jour des donnees
	spip_query($q = "UPDATE spip_documents SET $update taille='$taille', largeur='$largeur', hauteur='$hauteur', fichier="._q(set_spip_doc($fichier))." WHERE id_document=$id_document");

	if ($id_document_lie) {
		spip_query("UPDATE spip_documents SET id_vignette=$id_document	WHERE id_document=$id_document_lie");
		// hack pour que le retour vers ecrire/ active le bon doc.
		$documents_actifs[$fichier] = $id_document_lie; 
	}
	else
		$documents_actifs[$fichier] = $id_document; 

	// Notifications, gestion des revisions, reindexation...
	pipeline('post_edition',
		array(
			'args' => array(
				'operation' => 'ajouter_document',
				'table' => 'spip_documents',
				'id_objet' => $id_document
			),
			'data' => null
		)
	);

	return $type_image;
}



// http://doc.spip.org/@verifier_compactes
function verifier_compactes($zip) {
	if (!$list = $zip->listContent()) return array();
	// si pas possible de decompacter: installer comme fichier zip joint
	// Verifier si le contenu peut etre uploade (verif extension)
	$aff_fichiers = array();
	foreach ($list as $file) {
		if (accepte_fichier_upload($f = $file['stored_filename']))
			$aff_fichiers[$f] = $file;
		else spip_log("chargement de $f interdit");
		}
	ksort($aff_fichiers);
	return $aff_fichiers;
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


// http://doc.spip.org/@traite_svg
function traite_svg($file)
{
	$texte = spip_file_get_contents($file);

	// Securite si pas admin : virer les scripts et les references externes
	// sauf si on est en mode javascript 'ok' (1), cf. inc_version
	if ($GLOBALS['filtrer_javascript'] < 1
	AND $GLOBALS['auteur_session']['statut'] != '0minirezo') {
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

// Afficher un formulaire de choix: decompacter et/ou garder tel quel
// et reconstruire un generer_action_auteur.
// Passer ca en squelette un de ces jours.

// http://doc.spip.org/@liste_archive_jointe
function liste_archive_jointe($valables, $zip, $type, $id, $mode, $id_document, $hash, $redirect, $iframe_redirect)
{
	$arg = (intval($id) .'/' .intval($id_document) . "/$mode/$type");

	$texte = "<div style='text-align: left'>
<input type='hidden' name='redirect' value='$redirect' />
<input type='hidden' name='iframe_redirect' value='$iframe_redirect' />
<input type='hidden' name='hash' value='$hash' />
<input type='hidden' name='chemin' value='$zip' />
<input type='hidden' name='arg' value='$arg' />
<input type='radio' checked='checked' name='sousaction5' value='5' />" .
	  	_T('upload_zip_telquel').
		"<br />".
		"<input type='radio' name='sousaction5' value='6' />".
		_T('upload_zip_decompacter').
		"<ol>" .
		liste_archive_taille($valables) .
		"</ol>".
		"<br /><input type='checkbox' name='sousaction4' value='4' />".
		_T('les_deux').
		"</div>".
		"<div style='text-align: right;'><input class='fondo spip_xx-small' type='submit' value='".
		_T('bouton_valider').
		  "' />";

	$texte = "<p>" .
		_T('upload_fichier_zip_texte') .
		"</p><p>" .
		_T('upload_fichier_zip_texte2') .
		 "</p>" .
		generer_form_public('joindre', $texte);

	if(_request("iframe")=="iframe") {
		return "<p>build form $iframe_redirect</p>" .
		  "<div class='upload_answer upload_zip_list'>" .
		  $texte .
		  "</div>";
	} else { return minipres(_T('upload_fichier_zip'), $texte); }
}

// http://doc.spip.org/@liste_archive_taille
function liste_archive_taille($files)
{
  $res = '';
  foreach ($files as $nom => $file)
    {
      $res .= "\n<li><tt>$nom ("
	. _T('taille_octets', array("taille" => $file['size']))
	. '; '
	. _T('date')
	. '&nbsp;: '
	. affdate_heure(date("Y-m-d H:i:s", $file['mtime']))
	.")</tt></li>";
    }
  return $res;
}
?>
