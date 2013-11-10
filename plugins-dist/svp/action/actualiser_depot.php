<?php
/**
 * Gestion de l'action actualiser_depot
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Actions
 */
 
/**
 * Action de mise à jour en base de données de la liste des plugins
 * d'un ou de tous les dépots
 */
function action_actualiser_depot_dist(){

	// Securisation: aucun argument attendu
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	// Verification des autorisations
	if (!autoriser('webmestre')) {
		include_spip('inc/minipres');
		echo minipres();
		exit();
	}

	// Actualisation des plugins du depot ou de tous les plugins suivant l'argument de l'action
	// Le depot lui-meme n'est mis a jour que partiellement via le fichier XML une fois que
	// la premiere insertion a ete effectuee. En effet, seules les infos non editables dans le prive
	// peuvent etre actualisees lors de cette action
	include_spip('inc/svp_depoter_distant');
	if ($arg === 'tout') {
		if ($ids_depots = Sql::allfetsel('id_depot', 'spip_depots')) {
			$ids_depots = array_map('reset', $ids_depots);
			foreach ($ids_depots as $_id_depot)
				svp_actualiser_depot($_id_depot);
			// On consigne l'action
			spip_log("ACTION ACTUALISER TOUS LES DEPOTS (manuel)", 'svp_actions.' . _LOG_INFO);
		}
	}
	else if ($id_depot = intval($arg)) {
		svp_actualiser_depot($id_depot);
		// On consigne l'action
		spip_log("ACTION ACTUALISER DEPOT (manuel) : id_depot = ". $id_depot, 'svp_actions.' . _LOG_INFO);
	}
}

?>
