<?php

/**
 * Déclaration des tâches du génie
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Genie
 */
if (!defined("_ECRIRE_INC_VERSION")) return;


if (!defined('_SVP_CRON_ACTUALISATION_DEPOTS')) {
	/**
	 * Mise à jour automatique des depots (CRON)
	 * true pour autoriser les actualisations automatique
	 * @var bool
	 */
	define('_SVP_CRON_ACTUALISATION_DEPOTS', true);
}

if (!defined('_SVP_PERIODE_ACTUALISATION_DEPOTS')) {
	/**
	 * Période d'actualisation en nombre d'heures (de 1 a 24)
	 * @var int
	 */
	define('_SVP_PERIODE_ACTUALISATION_DEPOTS', 6);
}


/**
 * Ajoute la tâche d'actualisation des dépots dans la liste des tâches périodiques
 *
 * @pipeline taches_generales_cron
 *
 * @param array $taches_generales
 *     Tableau des tâches et leur périodicité en seconde
 * @return array
 *     Tableau des tâches et leur périodicité en seconde
 */
function svp_taches_generales_cron($taches_generales) {

	// Ajout de la tache CRON de mise a jour reguliere de tous les depots de la base
	// Par defaut, toutes les 6h
	// Conditionnee a la variable de configuration
	if (_SVP_CRON_ACTUALISATION_DEPOTS)
		$taches_generales['svp_actualiser_depots'] = _SVP_PERIODE_ACTUALISATION_DEPOTS*3600;

	return $taches_generales;
}

?>
