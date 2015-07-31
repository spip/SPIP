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

/**
 * Gestion de l'API de modification/suppression des logos
 *
 * @package SPIP\Core\Logo\Edition
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Supprimer le logo d'un objet
 * @param string $objet
 * @param int $id_objet
 * @param string $etat
 *   on ou off
 */
function logo_supprimer($objet, $id_objet, $etat){
	$chercher_logo = charger_fonction('chercher_logo','inc');
	$objet = objet_type($objet);
	$primary = id_table_objet($objet);
	include_spip('inc/chercher_logo');

	// existe-t-il deja un logo ?
	$logo = $chercher_logo($id_objet, $primary, $etat);
	if ($logo)
		spip_unlink($logo[0]);
}

/**
 * @param string $objet
 * @param int $id_objet
 * @param string $etat
 *   on ou off
 * @param string|array $source
 *   array : sous tableau de $_FILE issu de l'upload
 *   string : fichier source (chemin complet ou chemin relatif a tmp/upload)
 * @return string
 */
function logo_modifier($objet, $id_objet, $etat, $source){
	$chercher_logo = charger_fonction('chercher_logo','inc');
	$objet = objet_type($objet);
	$primary = id_table_objet($objet);
	include_spip('inc/chercher_logo');
	$type = type_du_logo($primary);

	// nom du logo
	$nom = $type.$etat.$id_objet;

	// supprimer le logo eventueel existant
	logo_supprimer($objet, $id_objet, $etat);

	include_spip('inc/documents');
	$erreur = "";

	if (!$source){
		spip_log("spip_image_ajouter : source inconnue");
		$erreur = "source inconnue";
		return $erreur;
	}

	$file_tmp = _DIR_LOGOS . $nom . '.tmp';

	$ok = false;
	// fichier dans upload/
	if (is_string($source)){
		if (file_exists($source)) {
			$ok = @copy($source, $file_tmp);
		}
		elseif(file_exists($f=determine_upload() . $source)){
			$ok = @copy($f, $file_tmp);
		}
	} // Intercepter une erreur a l'envoi
	elseif (!$erreur = check_upload_error($source['error'], "", true)) {
		// analyse le type de l'image (on ne fait pas confiance au nom de
		// fichier envoye par le browser : pour les Macs c'est plus sur)
		$ok = deplacer_fichier_upload($source['tmp_name'], $file_tmp);
	}

	if ($erreur){
		return $erreur;
	}
	if (!$ok OR !file_exists($file_tmp)){
		spip_log($erreur = "probleme de copie pour $file_tmp ");
		return $erreur;
	}

	$size = @getimagesize($file_tmp);
	$type = !$size ? '' : ($size[2]>3 ? '' : $GLOBALS['formats_logos'][$size[2]-1]);
	if ($type){
		$poids = filesize($file_tmp);

		if (_LOGO_MAX_SIZE>0
			AND $poids>_LOGO_MAX_SIZE*1024
		){
			spip_unlink($file_tmp);
			$erreur = _T('info_logo_max_poids',
				array('maxi' => taille_en_octets(_LOGO_MAX_SIZE*1024),
					'actuel' => taille_en_octets($poids)));
		} elseif (_LOGO_MAX_WIDTH*_LOGO_MAX_HEIGHT
			AND ($size[0]>_LOGO_MAX_WIDTH
				OR $size[1]>_LOGO_MAX_HEIGHT)
		) {
			spip_unlink($file_tmp);
			$erreur = _T('info_logo_max_poids',
				array(
					'maxi' =>
						_T('info_largeur_vignette',
							array('largeur_vignette' => _LOGO_MAX_WIDTH,
								'hauteur_vignette' => _LOGO_MAX_HEIGHT)),
					'actuel' =>
						_T('info_largeur_vignette',
							array('largeur_vignette' => $size[0],
								'hauteur_vignette' => $size[1]))
				));
		} else
			@rename($file_tmp, _DIR_LOGOS . $nom . ".$type");
	} else {
		spip_unlink($file_tmp);
		$erreur = _T('info_logo_format_interdit',
			array('formats' => join(', ', $GLOBALS['formats_logos'])));
	}

	return $erreur;
}
