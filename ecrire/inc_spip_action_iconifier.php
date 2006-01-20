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

function spip_action_iconifier_dist()
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST') 
		spip_image_ajouter_dist();
	else	spip_image_effacer_dist();
}

function spip_image_effacer_dist() {

	global $arg;
	if (!strstr($arg, ".."))
		@unlink(_DIR_IMG . $arg);
}

//
// Ajouter un logo
//

// $source = $_FILES[0]
// $dest = arton12.xxx
function spip_image_ajouter_dist() {
	global $sousaction2, $source, $arg;

	include_ecrire('inc_getdocument');
	if (!$sousaction2) {
		if (!$_FILES) $_FILES = $GLOBALS['HTTP_POST_FILES'];
		$source = (is_array($_FILES) ? array_pop($_FILES) : "");
	}
	if ($source) {
		$f =_DIR_DOC . $arg . '.tmp';

		if (!is_array($source)) 
		// fichier dans upload/
	  		$source = @copy(_DIR_TRANSFERT . $source, $f);
		else {
		// Intercepter une erreur a l'envoi
			if (check_upload_error($source['error']))
				$source ="";
			else
		// analyse le type de l'image (on ne fait pas confiance au nom de
		// fichier envoye par le browser : pour les Macs c'est plus sur)

				$source = deplacer_fichier_upload($source['tmp_name'], $f);
		}
	}
	if (!$source)
		spip_log("pb de copie pour $f");
	else {

		$size = @getimagesize($f);
		$type = decoder_type_image($size[2], true);

		if ($type) {
			$poids = filesize($f);
			if (_LOGO_MAX_SIZE > 0
			AND $poids > _LOGO_MAX_SIZE*1024) {
				@unlink ($f);
				check_upload_error(6,
				_T('info_logo_max_poids',
					array('maxi' => taille_en_octets(_LOGO_MAX_SIZE*1024),
					'actuel' => taille_en_octets($poids))));
			}

			if (_LOGO_MAX_WIDTH * _LOGO_MAX_HEIGHT
			AND ($size[0] > _LOGO_MAX_WIDTH
			OR $size[1] > _LOGO_MAX_HEIGHT)) {
				@unlink ($f);
				check_upload_error(6, 
				_T('info_logo_max_taille',
					array(
					'maxi' =>
						_T('info_largeur_vignette',
							array('largeur_vignette' => _LOGO_MAX_WIDTH,
							'hauteur_vignette' => _LOGO_MAX_HEIGHT)),
					'actuel' =>
						_T('info_largeur_vignette',
							array('largeur_vignette' => $size[0],
							'hauteur_vignette' => $size[1]))
				)));
			}
			@rename ($f, _DIR_DOC . $arg . ".$type");
		}
		else {
			@unlink ($f);
			check_upload_error(6,
				_T('info_logo_format_interdit',
				array ('formats' => 'GIF, JPG, PNG'))
			);
		}
	
	}
}
?>
