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

include_spip('inc/actions'); // *action_auteur
include_spip('inc/documents');
include_spip('inc/filtres');

// http://doc.spip.org/@inc_tourner_dist
function inc_tourner_dist($id_document, $document, $script, $flag, $type)
{
	global $spip_lang_right;

	if (!$document) {
		// retour d'Ajax
		$document = sql_fetch(spip_query("SELECT * FROM spip_documents WHERE id_document = " . intval($id_document)));
	}

	if (preg_match('/^\w+$/',$type)) { // securite
		$id = sql_fetch(spip_query("SELECT id_$type FROM spip_documents_$type" . "s WHERE id_document = " . intval($id_document)));
		$id = $id["id_$type"];
	} else $id = 0; // le hash sera inutilisable

	$titre = $document['titre'];
	$id_vignette = $document['id_vignette'];
	$fichier = entites_html($document['fichier']);

	if (isset($document['url']))
		$url = $document['url'];
	else {
		charger_generer_url();
		$url = generer_url_document($id_document);
	}

	$res = '';

	// Indiquer les documents manquants avec un panneau de warning

	if ($document['distant'] != 'oui') {
		if (!@file_exists(get_spip_doc($document['fichier']))){
			$c = _T('fichier_introuvable',
					array('fichier'=>basename($document['fichier'])));
			$res = "<img src='" . _DIR_IMG_PACK . "warning-24.gif'"
				."\n\tstyle='float: right;'\n\talt=\"$c\"\n\ttitle=\"$c\" />";
		} else 	if ($flag AND !$id_vignette) 
			$res = boutons_rotateurs($document, $type, $id, $id_document,$script);

		$boite = '';

	} else {
		$res = "\n<div class='verdana1' style='float: $spip_lang_right; text-align: $spip_lang_right;'>";
		
		// Signaler les documents distants par une icone de trombone
		$res .= "<img src='"._DIR_IMG_PACK.'attachment.gif'."'\n\t \n\talt=\"$fichier\"\n\ttitle=\"$fichier\" />\n";
		// Bouton permettant de copier en local le fichier
		$res .= bouton_copier_local($document, $type, $id, $id_document, $script);
		
		$res .= "</div>\n";
	}

	$res .= "<div style='text-align: center;'>";
	$res .= document_et_vignette($document, $url, true);
	$res .= "</div>\n";

	$res .= "<div style='text-align: center; color: 333333;' class='verdana1 spip_x-small'>&lt;doc"
	.  $id_document
	. "&gt;</div>";

	return ajax_action_greffe("tourner", $id_document, $res);
}

// http://doc.spip.org/@boutons_rotateurs
function boutons_rotateurs($document, $type, $id, $id_document, $script) {
	global $spip_lang_right;

	$process = $GLOBALS['meta']['image_process'];

	// bloc rotation de l'image
	// si c'est une image, qu'on sait la faire tourner, qu'elle
	// n'est pas distante, qu'elle est bien presente dans IMG/
	// qu'elle n'a pas de vignette perso ; et qu'on a la bibli !
	if ($document['distant']!='oui' 
	AND in_array($document['extension'], array('gif', 'jpg', 'png'))
	AND (strpos($GLOBALS['meta']['formats_graphiques'], $document['extension'])!==false)
	AND ($process == 'imagick'
		OR $process == 'gd2'
		OR $process == 'convert'
		OR $process == 'netpbm')
	AND @file_exists(get_spip_doc($document['fichier']))
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

// Retourne le code HTML du bouton "copier en local".
// http://doc.spip.org/@bouton_copier_local
function bouton_copier_local($document, $type, $id, $id_document, $script) {
	global $spip_lang_right;

	// pour etre sur qu'il s'agit bien d'un doc distant
	// et qu'il existe
	$bouton_copier = '';
	if ($document['distant'] == 'oui' /* on pourrait verifier l'existence du
	 	// fichier ici, mais ne risque pas-t-on de degrader les performances ?
	 	// il sera toujours temps de le verifier lorsque l'utilisateur cliquera
	 	// sur le bouton. */) {
		$bouton_copier = ajax_action_auteur("copier_local",
			    "$id_document",
			    $script,
			    "show_docs=$id_document&id_$type=$id#tourner-$id_document",
			    array(http_img_pack('telecharger.gif', _T('copier_en_local'), ''),
				  " class='bouton_rotation'"),
				  // on aurait pu faire un nouveau style 'bouton-telecharger',
				  // mais pour l'instant on se contente de reutiliser celui-ci
				  // afin de garder une homogeneite entre les differents boutons.
			    "&id_document=$id_document&id=$id&type=$type");
			    

		// Hack ?
		// demander confirmation javascript
		$u = str_replace("'", "\\'", unicode_to_javascript(html2unicode(_T('copier_en_local'))));
		$bouton_copier = str_replace('return AjaxSqueeze',
			"return (!confirm('$u'))?false:AjaxSqueeze", $bouton_copier);
	}

	return $bouton_copier;
}
?>
