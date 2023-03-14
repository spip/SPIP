<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Formulaire de configuration pour choisir la librairie graphique
 * et les tailles de redimensionnement des vignettes
 *
 * @package SPIP\Core\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement du formulaire de configuration de la librairie graphique
 *
 * @return array
 *     Environnement du formulaire
 **/
function formulaires_configurer_reducteur_charger_dist() {
	$valeurs = [];
	foreach (
		[
			'image_process',
			'formats_graphiques',
			'creer_preview',
			'taille_preview',
		] as $m
	) {
		$valeurs[$m] = $GLOBALS['meta'][$m] ?? null;
	}

	$valeurs['taille_preview'] = (int) $valeurs['taille_preview'];
	if ($valeurs['taille_preview'] < 10) {
		$valeurs['taille_preview'] = 120;
	}

	return $valeurs;
}


/**
 * Traitements du formulaire de configuration de la librairie graphique
 *
 * @return array
 *     Retours des traitements
 **/
function formulaires_configurer_reducteur_traiter_dist() {
	$res = ['editable' => true];

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
					$formats_graphiques = 'gif,jpg,png,webp';
					break;
				default: #debug
					$formats_graphiques = '';
					$image_process = 'non';
					break;
			}
			ecrire_meta('formats_graphiques', $formats_graphiques, 'non');
			ecrire_meta('image_process', $image_process, 'non');
		}
	}

	foreach (
		[
			'creer_preview'
		] as $m
	) {
		if (!is_null($v = _request($m))) {
			ecrire_meta($m, $v == 'oui' ? 'oui' : 'non');
		}
	}
	if (!is_null($v = _request('taille_preview'))) {
		ecrire_meta('taille_preview', (int) $v);
	}

	$res['message_ok'] = _T('config_info_enregistree');

	return $res;
}

/**
 * Indique si une librairie graphique peut être utilisée et retourne alors
 * une URL pour tester la librairie
 *
 * @param string $process
 *     Code de la libriairie, parmi gd2, gd1, netpbm, imagick ou convert
 * @return string
 *     URL d'action pour tester la librairie graphique en créant une vignette
 **/
function url_vignette_choix($process) {
	switch ($process) {
		case 'gd2':
			if (!function_exists('ImageCreateTrueColor')) {
				return '';
			}
			break;
		case 'gd1':
			if (!function_exists('ImageGif') && !function_exists('ImageJpeg') && !function_exists('ImagePng')) {
				return '';
			}
			break;
		case 'netpbm':
			if (defined('_PNMSCALE_COMMAND') && _PNMSCALE_COMMAND == '') {
				return '';
			}
			break;
		case 'imagick':
			if (!method_exists(\Imagick::class, 'readImage')) {
				return '';
			}
			break;
		case 'convert':
			if (defined('_CONVERT_COMMAND') && _CONVERT_COMMAND == '') {
				return '';
			}
			break;
	}

	return generer_url_action('tester', "arg=$process&time=" . time());
}
