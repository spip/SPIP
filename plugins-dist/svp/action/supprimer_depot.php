<?php
/**
 * Gestion de l'action supprimer_depot
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Actions
 */
 
/**
 * Action de suppression en base de données d'un dépot et de ses plugins
 *
 * @return void
 */
function action_supprimer_depot_dist(){

	// Securisation: aucun argument attendu
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	// Verification des autorisations
	if (!autoriser('webmestre')) {
		include_spip('inc/minipres');
		echo minipres();
		exit();
	}

	// Suppression du depot et de ses plugins
	if ($id_depot = intval($arg)) {
		include_spip('inc/svp_depoter_distant');
		svp_supprimer_depot($id_depot);
		spip_log("ACTION SUPPRIMER DEPOT (manuel) : id_depot = ". $id_depot, 'svp_actions.' . _LOG_INFO);
	}
}

?>
