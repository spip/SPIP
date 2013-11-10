<?php

/**
 * Gestion du formulaire de téléchargement de plugin via une URL
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Formulaires
 */
 
if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Chargement du formulaire de téléchargement de plugin
 *
 * @return array
 *     Environnement du formulaire
**/
function formulaires_charger_plugin_archive_charger_dist() {
	return array(
		'archive' =>'',
		'destination' =>''
	);
}

/**
 * Vérifications du formulaire de téléchargement de plugin
 *
 * Vérifie qu'une archive est saisie, et si la destination existe
 * demande à confirmer l'écrasement
 * 
 * @return array
 *     Tableau des erreurs
**/
function formulaires_charger_plugin_archive_verifier_dist(){
	include_spip('inc/plugin'); // _DIR_PLUGINS_AUTO
	$erreurs = array();
	if (!$archive = _request('archive')) {
		$erreurs['archive'] = _T('info_obligatoire');
	} else {
		// calcul du répertoire de destination
		if (!$destination = _request('destination')) {
			$destination = pathinfo($archive);
			$destination = $destination['filename'];
		}
		$destination = str_replace('../', '', $destination);
		set_request('destination', $destination);

		// si la destination existe, on demande confirmation de l'ecrasement.
		$dir = _DIR_PLUGINS_AUTO . $destination;
		if (is_dir($dir) and !_request('confirmer')) {
			$base = dirname($dir);
			$nom = basename($dir);
			$backup = "$base/.$nom.bck";
			$erreurs['confirmer'] = _T("svp:confirmer_telecharger_dans", array(
				'dir' => joli_repertoire($dir),
				'dir_backup' => joli_repertoire($backup)));
		}
	}

	return $erreurs;
}

/**
 * Traitement du formulaire de téléchargement de plugin
 *
 * Télécharge le plugin via le téléporteur et rend la main.
 * 
 * @return array
 *     Retours du traitement
**/
function formulaires_charger_plugin_archive_traiter_dist(){
	$retour = array();

	$archive = _request('archive');
	$dest = _request('destination');

	include_spip('action/teleporter');
	$teleporter_composant = charger_fonction('teleporter_composant', 'action');
	$ok = $teleporter_composant('http', $archive, _DIR_PLUGINS_AUTO . $dest);
	if ($ok !== true) {
		$retour['message_erreur'] = $ok;
	} else {
		$retour['message_ok'] = _T('svp:message_telechargement_archive_effectue',
			array('dir' => joli_repertoire( _DIR_PLUGINS_AUTO . $dest )));
	}
	$retour['editable'] = true;
	return $retour;
}


?>
