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

include_spip('inc/actions'); // *action_auteur et determine_upload
include_spip('inc/documents');
include_spip('inc/presentation');
include_spip('inc/filtres');

// http://doc.spip.org/@formulaire_tourner
function inc_tourner_dist($id_document, $document, $script, $flag_modif, $type)
{
	global $spip_lang_right;

	if (!$document) {
		// retour d'Ajax
		$document = spip_fetch_array(spip_query("SELECT * FROM spip_documents WHERE id_document = " . intval($id_document)));
	}

	$id = $document["id_$type"];
	$titre = $document['titre'];
	$id_vignette = $document['id_vignette'];
	$fichier = entites_html($document['fichier']);

	if (isset($document['url']))
		$url = $document['url'];
	else {
		charger_generer_url();
		$url = generer_url_document($id_document);
	}

	if ($flag_modif)
		$res .= boutons_rotateurs($document, $type, $id, $id_document,$script,  $id_vignette);
	else $res = '';
	// Indiquer les documents manquants avec un panneau de warning

	if ($document['distant'] != 'oui'
	AND !@file_exists(_DIR_RACINE.$document['fichier'])) {
			$c = _T('fichier_introuvable',
					array('fichier'=>basename($document['fichier'])));
			$res .= "<img src='" . _DIR_IMG_PACK . "warning-24.gif'"
				."\n\tstyle='float: right;'\n\talt=\"$c\"\n\ttitle=\"$c\" />";
	}

	$res .= "<div style='text-align: center;'>";
	$res .= document_et_vignette($document, $url, true);
	$res .= "</div>\n";

	$res .= "<div class='verdana1' style='text-align: center;'>";
	$res .= " <font size='1' face='arial,helvetica,sans-serif' color='333333'>&lt;doc$id_document&gt;</font>";
	$res .= "</div>";

	if ($flag_modif === 'ajax') return $res;

	$boite = '';

	// Signaler les documents distants par une icone de trombone
	if ($document['distant'] == 'oui')
		$boite .= "\n<img src='"._DIR_IMG_PACK.'attachment.gif'."'\n\t style='float: $spip_lang_right;'\n\talt=\"$fichier\"\n\ttitle=\"$fichier\" />\n";
	return "$boite<div id='tourner-$id_document'>$res</div>";
}

// http://doc.spip.org/@boutons_rotateurs
function boutons_rotateurs($document, $type, $id, $id_document, $script, $id_vignette) {
	global $spip_lang_right;
	static $ftype = array(1 => 'jpg', 2 => 'png', 3 => 'gif');

	$process = $GLOBALS['meta']['image_process'];

	// bloc rotation de l'image
	// si c'est une image, qu'on sait la faire tourner, qu'elle
	// n'est pas distante, qu'elle est bien presente dans IMG/
	// qu'elle n'a pas de vignette perso ; et qu'on a la bibli !
	if ($document['distant']!='oui' AND !$id_vignette
	AND isset($ftype[$document['id_type']])
	AND strstr($GLOBALS['meta']['formats_graphiques'],
		   $ftype[$document['id_type']])
	AND ($process == 'imagick' OR $process == 'gd2'
	OR $process == 'convert' OR $process == 'netpbm')
	AND @file_exists(_DIR_RACINE.$document['fichier'])
	) {

	  return "\n<div class='verdana1' style='float: $spip_lang_right; text-align: $spip_lang_right;'>" .

		bouton_tourner_document($id, $id_document, $script, -90, $type, 'tourner-gauche.gif', _T('image_tourner_gauche')) .

		bouton_tourner_document($id, $id_document, $script,  90, $type, 'tourner-droite.gif', _T('image_tourner_droite')) .

		bouton_tourner_document($id, $id_document, $script, 180, $type, 'tourner-180.gif', _T('image_tourner_180')) .
		"</div>\n";
	}
}

// http://doc.spip.org/@bouton_tourner_document
function bouton_tourner_document($id, $id_document, $script, $rot, $type, $img, $title)
{
  return ajax_action_auteur("tourner",
			    "$id_document-$rot",
			    $script,
			    "show_docs=$id_document&id_$type=$id#tourner-$id_document",
			    array(http_img_pack($img, $title, ''),
				  " class='bouton_rotation'"),
			    "&id_document=$id_document&id=$id&type=$type");
}
?>
