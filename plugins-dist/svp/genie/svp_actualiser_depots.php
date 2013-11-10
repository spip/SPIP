<?php

/**
 * Gestion du génie svp_actualiser_depots
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Genie
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Actualise tous les dépots
 *
 * @genie svp_actualiser_depots
 *
 * @param int $last
 *     Timestamp de la dernière exécution de cette tâche
 * @return int
 *     Positif : la tâche a été effectuée
 */
function genie_svp_actualiser_depots_dist ($last) {

	include_spip('inc/svp_depoter_distant');

	// On recupere en base de donnees tous les depots a mettre a jour
	if ($resultats = Sql::allfetsel('id_depot', 'spip_depots')) {
		foreach ($resultats as $depot) {
			svp_actualiser_depot($depot['id_depot']);
			spip_log("ACTION ACTUALISER DEPOT (automatique) : id_depot = ". $depot['id_depot'], 'svp_actions.' . _LOG_INFO);
		}
	}

	return 1;
}

?>
