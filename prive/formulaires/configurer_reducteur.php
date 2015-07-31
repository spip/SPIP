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

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_configurer_reducteur_charger_dist(){
	foreach(array(
		"image_process",
		"formats_graphiques",
		"creer_preview",
		"taille_preview",
		) as $m)
		$valeurs[$m] = $GLOBALS['meta'][$m];

	$valeurs['taille_preview'] = intval($valeurs['taille_preview']);
	if ($valeurs['taille_preview']<10)
		$valeurs['taille_preview'] = 120;
	return $valeurs;
}


function formulaires_configurer_reducteur_traiter_dist(){
	$res = array('editable'=>true);

	if (is_array($image_process = _request('image_process_'))) {
		$image_process = array_keys($image_process);
		$image_process = reset($image_process);

			// application du choix de vignette
		if ($image_process) {
				// mettre a jour les formats graphiques lisibles
			switch ($image_process) {
					case 'gd1':
					case 'gd2':
						$formats_graphiques = $GLOBALS['meta']['gd_formats_read'];
						break;
					case 'netpbm':
						$formats_graphiques = $GLOBALS['meta']['netpbm_formats'];
						break;
					case 'convert':
					case 'imagick':
						$formats_graphiques = 'gif,jpg,png';
						break;
					default: #debug
						$formats_graphiques = '';
						$image_process = 'non';
						break;
				}
			ecrire_meta('formats_graphiques', $formats_graphiques,'non');
			ecrire_meta('image_process', $image_process,'non');
		}
	}

	foreach(array(
		"creer_preview",
		) as $m)
		if (!is_null($v=_request($m)))
			ecrire_meta($m, $v=='oui'?'oui':'non');
	if (!is_null($v=_request('taille_preview')))
		ecrire_meta("taille_preview", intval($v));

	$res['message_ok'] = _T('config_info_enregistree');
	return $res;
}

function url_vignette_choix($process){
	switch ($process){
		case 'gd2':
			if (!function_exists("ImageCreateTrueColor"))
				return '';
		case 'gd1':
			if (!function_exists('ImageGif')
			  AND !function_exists('ImageJpeg')
			  AND !function_exists('ImagePng'))
				return '';
			break;
		case 'netpbm':
			if (defined('_PNMSCALE_COMMAND') AND _PNMSCALE_COMMAND=='')
				return '';
			break;
		case 'imagick':
			if (!method_exists('Imagick','readImage'))
				return '';
			break;
		case 'convert':
			if (defined('_CONVERT_COMMAND') AND _CONVERT_COMMAND=='')
				return '';
			break;
	}
	return generer_url_action("tester", "arg=$process&time=".time());
}
