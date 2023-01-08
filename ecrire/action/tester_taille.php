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
 * Gestion de l'action testant, pour la librairie graphique GD2, la taille
 * maximale des images qu'il est capable de traiter
 *
 * @package SPIP\Core\Configurer
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/headers');

/**
 * Interception très probable d'une impossibilité de créer l'image demandée
 * dans le buffer de ob_start()
 *
 * Si c'est le cas, on redirige sur la page prévue, testant un autre cas
 * de traitement
 *
 * @param string $output
 *     Sortie du buffer
 * @return string
 *     Sortie du buffer
 **/
function action_tester_taille_error_handler($output) {
	// on est ici, donc echec lors de la creation de l'image
	if (!empty($GLOBALS['redirect'])) {
		return redirige_formulaire($GLOBALS['redirect']);
	}

	return $output;
}


/**
 * Tester nos capacités à redimensionner des images avec GD2 (taille mémoire)
 *
 * Ce test par dichotomie permet de calculer la taille (en pixels) de la
 * plus grande image traitable. Ce test se relance jusqu'à trouver cette
 * taille.
 *
 * La clé `arg` attendue est une chaîne indiquant les valeurs minimum et
 * maximum de taille à tester tel que '3000' (maximum) ou '3000-5000'
 * (minimum-maximum)
 *
 **/
function action_tester_taille_dist() {

	if (!autoriser('configurer')) {
		return;
	}

	$taille = _request('arg');
	$taille = explode('-', $taille);

	$GLOBALS['taille_max'] = end($taille);
	$GLOBALS['taille_min'] = 0;
	if (count($taille) > 1) {
		$GLOBALS['taille_min'] = reset($taille);
	}

	// si l'intervalle est assez petit, on garde la valeur min
	if ($GLOBALS['taille_max'] * $GLOBALS['taille_max'] - $GLOBALS['taille_min'] * $GLOBALS['taille_min'] < 50000) {
		$t = ($GLOBALS['taille_min'] * $GLOBALS['taille_min']);
		if ($GLOBALS['taille_min'] !== $GLOBALS['taille_max']) {
			$t = $t * 0.9; // marge de securite
			echo round($t / 1_000_000, 3) . ' Mpx';
		} else {
			// c'est un cas "on a reussi la borne max initiale, donc on a pas de limite connue"
			$t = 0;
			echo '&infin;';
		}
		ecrire_meta('max_taille_vignettes', $t, 'non');
		die();
	}

	$taille = $GLOBALS['taille_test'] = round(($GLOBALS['taille_max'] + $GLOBALS['taille_min']) / 2);

	include_spip('inc/filtres');
	// des inclusions representatives d'un hit prive et/ou public pour la conso memoire
	include_spip('public/assembler');
	include_spip('public/balises');
	include_spip('public/boucles');
	include_spip('public/cacher');
	include_spip('public/compiler');
	include_spip('public/composer');
	include_spip('public/criteres');
	include_spip('public/interfaces');
	include_spip('public/parametrer');
	include_spip('public/phraser_html');
	include_spip('public/references');

	include_spip('inc/presentation');
	include_spip('inc/charsets');
	include_spip('inc/documents');
	include_spip('inc/header');
	propre('<doc1>'); // charger propre avec le trairement d'un modele

	$i = _request('i') + 1;
	$image_source = chemin_image('test.png');
	$GLOBALS['redirect'] = generer_url_action(
		'tester_taille',
		"i=$i&arg=" . $GLOBALS['taille_min'] . '-' . $GLOBALS['taille_test']
	);

	ob_start('action_tester_taille_error_handler');
	filtrer('image_recadre', $image_source, $taille, $taille);
	$GLOBALS['redirect'] = generer_url_action('tester_taille', "i=$i&arg=$taille-" . $GLOBALS['taille_max']);

	// si la valeur intermediaire a reussi, on teste la valeur maxi qui est peut etre sous estimee
	// si $GLOBALS['taille_min']==0 (car on est au premier coup)
	if ($GLOBALS['taille_min'] == 0) {
		$taille = $GLOBALS['taille_max'];
		filtrer('image_recadre', $image_source, $taille, $taille);
		$GLOBALS['redirect'] = generer_url_action('tester_taille', "i=$i&arg=$taille-" . $GLOBALS['taille_max']);
	}
	ob_end_clean();


	// on est ici, donc pas de plantage
	echo redirige_formulaire($GLOBALS['redirect']);
}
