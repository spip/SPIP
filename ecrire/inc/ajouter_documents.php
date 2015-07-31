<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/getdocument');
include_spip('inc/documents');

// Joindre un document ou un Zip a deballer (fonction pour action/joindre)
// Distinguer les deux cas pour commencer

// http://doc.spip.org/@inc_ajouter_documents_dist
function inc_ajouter_documents_dist ($sources, $file, $type, $id, $mode, $id_document, &$actifs, $hout='', $redirect='', $iframe_redirect='')
{
	if (is_array($sources))
	  return liste_archive_jointe($sources, $file, $type, $id, $mode, $id_document, $hout, $redirect, $iframe_redirect);
	else
	  return ajouter_un_document($sources, $file, $type, $id, $mode, $id_document, $actifs, $hout);
}


/**
 * Choisir le mode du document : image/document
 * fonction surchargeable
 *
 * @param unknown_type $fichier
 * @param unknown_type $type_image
 * @param unknown_type $largeur
 * @param unknown_type $hauteur
 */
function inc_choisir_mode_document_dist($fichier, $type_lien, $type_image, $largeur, $hauteur){
	
	// si ce n'est pas une image, c'est forcement un document
	if (!in_array($type_image, array('gif', 'png', 'jpg')))
		return 'document';

	// si on a pas le droit d'ajouter de document a l'objet, c'est donc un mode image
	if ($type_lien AND isset($GLOBALS['meta']["documents_$type_lien"]) AND ($GLOBALS['meta']["documents_$type_lien"]=='non'))
		return 'image';
	

	// _INTERFACE_DOCUMENTS
	// en fonction de la taille de l'image
	// par defaut l'affectation en fonction de la largeur de l'image
	// est desactivee car pas comprehensible par le novice
	// il suffit de faire dans mes_options
	// define('_LARGEUR_MODE_IMAGE', 450);
	// pour beneficier de cette detection auto
	if (!defined('_LARGEUR_MODE_IMAGE')) define('_LARGEUR_MODE_IMAGE', 0);
	
	if (!_LARGEUR_MODE_IMAGE)
		return 'image';
	
	if ($largeur > 0
	  AND $largeur < _LARGEUR_MODE_IMAGE)
		return 'image';
	else
		return 'document';
}

//
// Ajouter un document (au format $_FILES)
//
# $source,	# le fichier sur le serveur (/var/tmp/xyz34)
# $nom_envoye,	# son nom chez le client (portequoi.pdf)
# $type_lien,	# lie a un article, une breve ou une rubrique ?
# $id_lien,	# identifiant de l'article (ou rubrique) lie
# $mode,	# 'image' => image en mode image
#			# 'vignette' => personnalisee liee a un document
			# 'document' => doc ou image en mode document
			# 'distant' => lien internet
# $id_document,	# pour une vignette, l'id_document de maman
# $actifs	# les documents dont il faudra ouvrir la boite de dialogue

// http://doc.spip.org/@ajouter_un_document
function ajouter_un_document($source, $nom_envoye, $type_lien, $id_lien, $mode, $id_document, &$documents_actifs, $titrer=false) {

	include_spip('inc/modifier');

// Documents distants : pas trop de verifications bloquantes, mais un test
// via une requete HEAD pour savoir si la ressource existe (non 404), si le
// content-type est connu, et si possible recuperer la taille, voire plus.
	if ($mode == 'distant') {
		include_spip('inc/distant');
		if ($a = recuperer_infos_distantes($source)) {

			# NB: dans les bonnes conditions (fichier autorise et pas trop gros)
			# $a['fichier'] est une copie locale du fichier

			$type_image = $a['type_image'];

			unset($a['type_image']);
			unset($a['body']);

			$a['date'] = date('Y-m-d H:i:s');
			$a['distant'] = 'oui';
			$a['mode'] = 'document';
			$a['fichier'] = set_spip_doc($source);
		}
		else {
			spip_log("Echec du lien vers le document $source, abandon");
			return;
		}
	} else { // pas distant

		$type_image = ''; // au pire
		// tester le type de document :
		// - interdit a l'upload ?
		// - quelle extension dans spip_types_documents ?
		// - est-ce "inclus" comme une image ?

		preg_match(",^(.*)\.([^.]+)$,", $nom_envoye, $match);
		@list(,$titre,$ext) = $match;
		// securite : pas de . en dehors de celui separant l'extension
		// sinon il est possible d'injecter du php dans un toto.php.txt
		$nom_envoye = str_replace('.','-',$titre).'.'.$ext;
		if ($titrer) {
			$titre = preg_replace(',[[:punct:][:space:]]+,u', ' ', $titre);
		} else $titre = '';
		$ext = corriger_extension(strtolower($ext));

		$row = sql_fetsel("inclus", "spip_types_documents", "extension=" . sql_quote($ext) . " AND upload='oui'");

		if ($row) {
			$type_inclus_image = ($row['inclus'] == 'image');
			$fichier = copier_document($ext, $nom_envoye, $source);
		} else {

/* STOCKER LES DOCUMENTS INCONNUS AU FORMAT .ZIP */
			$type_inclus_image = false;

			if (!sql_countsel("spip_types_documents", "extension='zip' AND upload='oui'")) {
				spip_log("Extension $ext interdite a l'upload");
				return;
			}

			$ext = 'zip';
			if (!$tmp_dir = tempnam(_DIR_TMP, 'tmp_upload')) return;
			spip_unlink($tmp_dir); @mkdir($tmp_dir);
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
			spip_unlink($source);
		}

		// Prevoir traitement specifique pour videos
		// (http://www.getid3.org/ peut-etre
		if ($ext == "mov") {
			$largeur = 0;
			$hauteur = 0;
		} else if ($ext == "svg") {
			// recuperer les dimensions et supprimer les scripts
			list($largeur,$hauteur)= traite_svg($fichier);
		} else { // image ?
		// Si c'est une image, recuperer sa taille et son type (detecte aussi swf)
			$size_image = @getimagesize($fichier);
			$largeur = intval($size_image[0]);
			$hauteur = intval($size_image[1]);
			$type_image = decoder_type_image($size_image[2]);
		}

		// Quelques infos sur le fichier
		if (!$fichier OR !@file_exists($fichier)
		OR !$taille = @intval(filesize($fichier))) {
			spip_log ("Echec copie du fichier $fichier");
			return;
		}


		
		// _INTERFACE_DOCUMENTS
		// Si mode == 'choix', fixer le mode image/document
		if ($mode == 'choix') {
			$choisir_mode_document = charger_fonction('choisir_mode_document','inc');
			$mode = $choisir_mode_document($fichier, $type_lien, $type_image, $largeur, $hauteur);
		}


		if (!$type_image) {
			if (_DOC_MAX_SIZE > 0
			AND $taille > _DOC_MAX_SIZE*1024) {
				spip_unlink ($fichier);
				check_upload_error(6,
				_T('info_logo_max_poids',
					array('maxi' => taille_en_octets(_DOC_MAX_SIZE*1024),
					'actuel' => taille_en_octets($taille))));
			}
			if ($mode == 'image') {
				spip_log ("le format de $fichier ne convient pas pour une image");
				spip_unlink($fichier);
				return;
			}
		}
		else { // image
			if (_IMG_MAX_SIZE > 0
			AND $taille > _IMG_MAX_SIZE*1024) {
				spip_unlink ($fichier);
				check_upload_error(6,
				_T('info_logo_max_poids',
					array('maxi' => taille_en_octets(_IMG_MAX_SIZE*1024),
					'actuel' => taille_en_octets($taille))));
			}
	
			if (_IMG_MAX_WIDTH * _IMG_MAX_HEIGHT
			AND ($size_image[0] > _IMG_MAX_WIDTH
			OR $size_image[1] > _IMG_MAX_HEIGHT)) {
				spip_unlink ($fichier);
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
				spip_unlink($fichier);
				return;
			}

			if (!($largeur OR $hauteur)) {
				spip_log('erreur upload vignette '.$fichier);
				spip_unlink($fichier);
				return;
			}
		} elseif (!in_array($mode, array('distant', 'image', 'document'))) {
			if ($type_image AND $type_inclus_image)
				$mode = 'image';
			else
				$mode = 'document';
		}
		$a =  array(
			'date' => date('Y-m-d H:i:s'),
			'distant' => 'non',
			'mode' => $mode,
			'titre'=> $titre,
			'largeur' => $largeur,
			'hauteur' => $hauteur,
			'taille' => $taille,
			'extension'=> $ext, 
			'fichier' => set_spip_doc($fichier));
	}

	if (($id_document=intval($id_document)) AND $mode!='vignette') {

		 // Mise a jour des descripteurs d'un vieux doc
		unset($a['titre']);
		unset($a['date']);
		unset($a['distant']);
		unset($a['mode']);

		sql_updateq('spip_documents', $a, "id_document=$id_document");
		$id = $id_document;

	} else {
	// Installer le document dans la base
	// attention piege semantique : les images s'installent en mode 'vignette'
	// note : la fonction peut "mettre a jour un document" si on lui
	// passe "mode=document" et "id_document=.." (pas utilise)

		$id = sql_insertq("spip_documents", $a);

		spip_log ("ajout du document $source $nom_envoye  (M '$mode' T '$type_lien' L '$id_lien' D '$id')");

		if ($id_lien AND $id
		AND preg_match('/^[a-z0-9_]+$/i', $type_lien) # securite
		) {
			sql_insertq('spip_documents_liens',
				    array('id_document' => $id,
					  'id_objet' => $id_lien,
					  'objet' => $type_lien));
		} else spip_log("Pb d'insertion $id_lien $type_lien");

		if ($id_document) {
			sql_updateq("spip_documents", array("id_vignette" => $id, "mode" => 'document'), "id_document=$id_document");

		} else  $id_document = $id;


		// Appliquer l'exif orientation
		// http://trac.rezo.net/trac/spip/ticket/1494
		define('_TOURNER_SELON_EXIF', false); # par defaut non, risque memoire
		if (defined('_TOURNER_SELON_EXIF')
		AND _TOURNER_SELON_EXIF
		AND $mode == 'document'
		AND $a['distant'] == 'non'
		AND $a['extension'] == 'jpg') {
			include_spip('action/tourner');
			tourner_selon_exif_orientation($id_document, $fichier);
		}

	}
	// pour que le retour vers ecrire/ active le bon doc.
	$documents_actifs[$fichier] = $id_document;
	// Notifications, gestion des revisions, reindexation...
	pipeline('post_edition',
		array(
			'args' => array(
				'operation' => 'ajouter_document',
				'table' => 'spip_documents',
				'id_objet' => $id,
				'type_image' => $type_image
			),
			'data' => null
		)
	);

	return $id ;
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
	AND $GLOBALS['visiteur_session']['statut'] != '0minirezo') {
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
// A noter : une extension 'pdf ' passe dans la requete de controle
// mysql> SELECT * FROM spip_types_documents WHERE extension="pdf ";
// http://doc.spip.org/@corriger_extension
function corriger_extension($ext) {
	$ext = preg_replace(',[^a-z0-9],i', '', $ext);
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

// Cherche dans la base le type-mime du tableau representant le document
// et corrige le nom du fichier ; retourne array(extension, nom corrige)
// s'il ne trouve pas, retourne '' et le nom inchange
// http://doc.spip.org/@fixer_extension_document
function fixer_extension_document($doc) {
	$extension = '';
	$name = $doc['name'];
	if (preg_match(',[.]([^.]+)$,', $name, $r)
	AND $t = sql_fetsel("extension", "spip_types_documents",
	"extension=" . sql_quote(corriger_extension($r[1])))) {
		$extension = $t['extension'];
		$name = preg_replace(',[.][^.]*$,', '', $doc['name']).'.'.$extension;
	}
	else if ($t = sql_fetsel("extension", "spip_types_documents",
	"mime_type=" . sql_quote($doc['type']))) {
		$extension = $t['extension'];
		$name = preg_replace(',[.][^.]*$,', '', $doc['name']).'.'.$extension;
	}

	return array($extension,$name);
}

// Afficher un formulaire de choix: decompacter et/ou garder tel quel
// et reconstruire un generer_action_auteur.
// Passer ca en squelette un de ces jours.

// http://doc.spip.org/@liste_archive_jointe
function liste_archive_jointe($valables, $zip, $type, $id, $mode, $id_document, $hash, $redirect, $iframe_redirect)
{
	include_spip('inc/layer');

	$arg = (intval($id) .'/' .intval($id_document) . "/$mode/$type");

	$texte = "<div style='text-align: left'>
<input type='hidden' name='redirect' value='$redirect' />
<input type='hidden' name='iframe_redirect' value='$iframe_redirect' />
<input type='hidden' name='hash' value='$hash' />
<input type='hidden' name='chemin' value='$zip' />
<input type='hidden' name='arg' value='$arg' />
<input type='radio' checked='checked' name='sousaction5' id='sousaction5_5' value='5' onchange='jQuery(\"#options_deballe_zip\").slideUp();' />" .
	  "<label for='sousaction5_5'>" . _T('upload_zip_telquel'). "</label>" .
		"<br />".
		"<input type='radio' name='sousaction5' id='sousaction5_6' value='6' onchange='jQuery(\"#options_deballe_zip\").slideDown();' />".
		"<label for='sousaction5_6'>" . _T('upload_zip_decompacter') . "</label>" .
		"<ol>" .
		liste_archive_taille($valables) .
		"</ol>"

		. debut_block_depliable(false,'options_deballe_zip') 
		. "<input type='checkbox' name='sousaction4' id='sousaction4_4' value='4' />".
			"<label for='sousaction4_4'>" . _T('upload_zip_conserver') . "</label>" .
			"<br /><input type='checkbox' name='titrer' id='titrer' />"
			. "<label for='titrer'>" . _T('upload_zip_titrer') .
			"</label>".
			"</div></div>"
		. fin_block()


		. "<div style='text-align: right;'><input type='submit' value='".
		_T('bouton_valider').
		  "' />";

	$texte = "<p>" .
		_T('upload_fichier_zip_texte') .
		"</p><p>" .
		_T('upload_fichier_zip_texte2') .
		 "</p>" .
		generer_form_action('joindre', $texte,' method="post"');

	if(_request("iframe")=="iframe") {
		return "<p>build form $iframe_redirect</p>" .
		  "<div class='upload_answer upload_zip_list'>" .
		  $texte .
		  "</div>";
	} else { return minipres(_T('upload_fichier_zip'), $texte); }
}

// http://doc.spip.org/@liste_archive_taille
function liste_archive_taille($files) {
	$res = '';
	foreach ($files as $nom => $file) {
		$date = date_interface(date("Y-m-d H:i:s", $file['mtime']));

		$taille = taille_en_octets($file['size']);
		$res .= "<li title=\"".texte_backend($title)."\"><b>$nom</b> &ndash; $taille<br />&nbsp; $date</li>\n";
	}
	return $res;
}
?>
