<?php
/**
 * Déclarations d'autorisations et utilisations de pipelines
 * 
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Pipelines
**/

/**
 * Fonction du pipeline autoriser. N'a rien à faire
 * @pipeline autoriser
 */
function svp_autoriser(){}

/**
 * Autoriser l'iconification (mettre un logo) d'un dépot
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_depot_iconifier_dist($faire, $type, $id, $qui, $opt){
	return true;
}


/**
 * Autoriser l'ajout d'un plugin ou d'un dépôt
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_plugins_ajouter_dist($faire, $type, $id, $qui, $opt){
	if (!defined('_AUTORISER_TELECHARGER_PLUGINS'))
		define('_AUTORISER_TELECHARGER_PLUGINS', true);
	return _AUTORISER_TELECHARGER_PLUGINS AND autoriser('webmestre');
}


/**
 * Ajout de l'onglet 'Ajouter les plugins'
 *
 * L'URL dépend de l'existence ou pas d'un dépot de plugins.
 * En absence, on amène sur la page permettant de créer un premier dépot.
 *
 * @pipeline ajouter_onglets
 * @param array $flux Données du pipeline
 * @return array      Données du pipeline
 */
function svp_ajouter_onglets($flux){
	if (($flux['args']=='plugins')
	AND (autoriser('ajouter', '_plugins'))){
		$compteurs = svp_compter('depot');
		$page = ($compteurs['depot'] == 0) ? 'depots' : 'charger_plugin';
		$flux['data']['charger_plugin'] =
			new Bouton(
				find_in_theme('images/plugin-add-24.png'),
				'plugin_titre_automatique_ajouter',
				generer_url_ecrire($page));
	}
	return $flux;
}


/**
 * Ne pas afficher par défaut les paquets,dépots,plugins locaux dans les boucles
 * 
 * On n'affiche dans les boucles (PLUGINS) (DEPOTS) et (PAQUETS)
 * que les éléments distants par défaut (on cache les locaux).
 * 
 * Utiliser {tout} pour tout avoir.
 * Utiliser {tout}{id_depot=0} pour avoir les plugins ou paquets locaux.
 *
 * @pipeline pre_boucle
 * @param Boucle $boucle Description de la boucle
 * @return Boucle        Description de la boucle
**/
function svp_pre_boucle($boucle) {

	// DEPOTS, PAQUETS
	// Pour DEPOTS, on n'a jamais id_depot=0 dedans... donc... pas la peine.
	if (
		$boucle->type_requete == 'paquets'
		# OR $boucle->type_requete == 'depots'
	) {
		$id_table = $boucle->id_table;
		$m_id_depot = $id_table .'.id_depot';
		// Restreindre aux depots distants
		if (
			#!isset($boucle->modificateur['criteres']['id_depot']) && 
			!isset($boucle->modificateur['tout'])) {
				$boucle->where[] = array("'>'", "'$m_id_depot'", "'\"0\"'");
		}
	}
	// PLUGINS
	elseif ($boucle->type_requete == 'plugins') {
		$id_table = $boucle->id_table;
		/*
		// les modificateurs ne se creent que sur les champs de la table principale
		// pas sur une jointure, il faut donc analyser les criteres passes pour
		// savoir si l'un deux est un 'id_depot'...

		$id_depot = false;
		foreach($boucle->criteres as $c){
			if (($c->op == 'id_depot') // {id_depot} ou {id_depot?}
			OR ($c->param[0][0]->texte == 'id_depot')) // {id_depot=x}
			{
				$id_depot = true;
				break;
			}
		}
		*/
		if (
		#	!$id_depot && 
			!isset($boucle->modificateur['tout'])) {
				// Restreindre aux plugins distant (id_depot > 0)
				$boucle->from["depots_plugins"] =  "spip_depots_plugins";
				$boucle->where[] = array("'='", "'depots_plugins.id_plugin'", "'$id_table.id_plugin'");
				$boucle->where[] = array("'>'", "'depots_plugins.id_depot'", "'\"0\"'");
		}
	}

	return $boucle;

}
?>
