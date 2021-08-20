<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion de l'action activer_plugins
 *
 * @package SPIP\Core\Plugins
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Mise à jour des données si envoi via formulaire
 *
 * @global array $GLOBALS ['visiteur_session']
 * @global array $GLOBALS ['meta']
 * @return void
 */
function enregistre_modif_plugin() {
	include_spip('inc/plugin');
	// recuperer les plugins dans l'ordre des $_POST
	$test = [];
	foreach (liste_plugin_files() as $file) {
		$test['s' . substr(md5(_DIR_PLUGINS . $file), 0, 16)] = $file;
	}
	if (defined('_DIR_PLUGINS_SUPPL')) {
		foreach (liste_plugin_files(_DIR_PLUGINS_SUPPL) as $file) {
			$test['s' . substr(md5(_DIR_PLUGINS_SUPPL . $file), 0, 16)] = $file;
		}
	}

	$plugin = [];

	foreach ($_POST as $choix => $val) {
		if (isset($test[$choix]) && $val == 'O') {
			$plugin[] = $test[$choix];
		}
	}

	spip_log("Changement des plugins actifs par l'auteur " . $GLOBALS['visiteur_session']['id_auteur'] . ': ' . join(
		',',
		$plugin
	));
	ecrire_plugin_actifs($plugin);

	// Chaque fois que l'on valide des plugins, on memorise la liste de ces plugins comme etant "interessants", avec un score initial, qui sera decremente a chaque tour : ainsi un plugin active pourra reter visible a l'ecran, jusqu'a ce qu'il tombe dans l'oubli.
	$plugins_interessants = @unserialize($GLOBALS['meta']['plugins_interessants']);
	if (!is_array($plugins_interessants)) {
		$plugins_interessants = [];
	}

	$plugins_interessants2 = [];

	foreach ($plugins_interessants as $plug => $score) {
		if ($score > 1) {
			$plugins_interessants2[$plug] = $score - 1;
		}
	}
	foreach ($plugin as $plug) {
		$plugins_interessants2[$plug] = 10;
	} // score initial
	ecrire_meta('plugins_interessants', serialize($plugins_interessants2));
}

/**
 * Fonction d'initialisation avant l'activation des plugins
 *
 * Vérifie les droits et met à jour les méta avant de lancer l'activation des plugins
 *
 * @return void
 */
function action_activer_plugins_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	if (!autoriser('configurer', '_plugins')) {
		die('erreur');
	}
	// forcer la maj des meta pour les cas de modif de numero de version base via phpmyadmin
	lire_metas();
	enregistre_modif_plugin();
}
