<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Tourner un document
 *
 * http://doc.spip.org/@action_tourner_dist
 *
 * lorsque les arguments sont passes dans arg en GET :
 * id_document-angle
 *
 * @param int $id_document
 * @param int $angle
 *   angle de rotation en degre>0
 * @return void
 */
function action_tourner_dist($id_document=null, $angle=null) {
	if (is_null($id_document) OR is_null($angle)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();

		if (!preg_match(",^\W*(\d+)\W?(-?\d+)$,", $arg, $r)) {
			spip_log("action_tourner_dist $arg pas compris");
		}
		else{
			array_shift($r);
			list($id_document,$angle) = $r;
		}

	}
	if ($id_document AND autoriser('modifier','document',$id_document))
		action_tourner_post($id_document,$angle);
}

/**
 * Tourner un document
 *
 * http://doc.spip.org/@action_tourner_post
 *
 * @param int $id_document
 * @param int $angle
 *   angle de rotation en degre>0
 * @return
 */
function action_tourner_post($id_document,$angle)
{
	$row = Sql::fetsel("fichier,extension", "spip_documents", "id_document=".intval($id_document));

	if (!$row) return;

	include_spip('inc/charsets');	# pour le nom de fichier
	include_spip('inc/documents'); 
	// Fichier destination : on essaie toujours de repartir de l'original
	$var_rot = $angle;

	include_spip('inc/distant'); # pour copie_locale
	$src = _DIR_RACINE . copie_locale(get_spip_doc($row['fichier']));
	if (preg_match(',^(.*)-r(90|180|270)\.([^.]+)$,', $src, $match)) {
		$effacer = $src;
		$src = $match[1].'.'.$match[3];
		$var_rot += intval($match[2]);
	}
	$var_rot = ((360 + $var_rot) % 360); // 0, 90, 180 ou 270

	if ($var_rot > 0) {
		$dest = preg_replace(',\.[^.]+$,', '-r'.$var_rot.'$0', $src);
		spip_log("rotation $var_rot $src : $dest");

		include_spip('inc/filtres');
		include_spip('public/parametrer'); // charger les fichiers fonctions #bugfix spip 2.1.0
		$res = filtrer('image_rotation',$src,$var_rot);
		$res = filtrer('image_format',$res,$row['extension']);

		list($hauteur,$largeur) = taille_image($res);
		$res = extraire_attribut($res,'src');

		include_spip('inc/getdocument');
		deplacer_fichier_upload($res,$dest);
	}
	else {
		$dest = $src;
		$size_image = @getimagesize($dest);
		$largeur = $size_image[0];
		$hauteur = $size_image[1];
	}

	// succes !
	if ($largeur>0 AND $hauteur>0) {
		$set = array(
			'fichier' => set_spip_doc($dest),
			'largeur'=>$largeur, 'hauteur'=>$hauteur,
			'distant'=>'non' // le document n'est plus distant apres une transformation
			);
		if ($taille = @filesize($dest))
			$set['taille'] = $taille;
		Sql::updateq('spip_documents', $set, "id_document=".intval($id_document));
		if ($effacer) {
			spip_log("rotation : j'efface $effacer");
			spip_unlink($effacer);
		}
		// pipeline pour les plugins
		pipeline('post_edition',
			array(
				'args' => array(
					'table' => 'spip_documents',
					'table_objet' => 'documents',
					'spip_table_objet' => 'spip_documents',
					'type' =>'document',
					'id_objet' => $id_document,
					'champs' => array('rotation'=>$angle,'orientation'=>$var_rot,'fichier'=>$row['fichier']),
					'serveur' => $serveur,
					'action'=>'tourner',
				),
				'data' => $set
			)
		);
	}

}

// Appliquer l'EXIF orientation
// cf. http://trac.rezo.net/trac/spip/ticket/1494
// http://doc.spip.org/@tourner_selon_exif_orientation
function tourner_selon_exif_orientation($id_document, $fichier) {

	if (function_exists('exif_read_data')
	AND $exif = exif_read_data($fichier)
	AND (
		$ort = $exif['IFD0']['Orientation']
		OR $ort = $exif['Orientation'])
	) {
	spip_log("rotation: $ort");
		$rot = null;
		switch ($ort) {
			case 3:
				$rot = 180;
			case 6:
				$rot = 90;
			case 8:
				$rot = -90;
		}
		if ($rot)
			action_tourner_post(array(null,$id_document, $rot));
	}
}

?>
