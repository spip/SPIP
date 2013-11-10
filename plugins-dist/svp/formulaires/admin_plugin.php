<?php

/**
 * Gestion du formulaire de gestion des plugins 
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Formulaires
 */
 
if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Chargement du formulaire de gestion des plugins
 *
 * @param string $voir
 *     Statut des plugins que l'on souhaite voir : actif, inactif, tous
 * @param string $verrouille
 *     Types de plugins que l'on souhaite voir :
 *     - 'non' : les plugins utilisateurs
 *     - 'oui' : les plugins verrouillés (plugins-dist)
 *     - 'tous' : les deux !
 * @param string|int $id_paquet
 *     Identifiant du paquet dont on veut obtenir une description complète
 *     lors de l'affichage du formulaire
 * @param string $redirect
 *     URL de redirection après les traitements
 * @return array
 *     Environnement du formulaire
**/
function formulaires_admin_plugin_charger_dist($voir='actif', $verrouille='non', $id_paquet='',$redirect=''){
	$valeurs = array();

	// actualiser la liste des paquets locaux systematiquement
	include_spip('inc/svp_depoter_local');
	// sans forcer tout le recalcul en base, mais en récupérant les erreurs XML
	$valeurs['erreurs_xml'] = array();
	svp_actualiser_paquets_locaux(false, $valeurs['erreurs_xml']);

	$valeurs['actif'] = 'oui';
	if ($voir == 'inactif')
		$valeurs['actif'] = 'non';
	if ($voir == 'tous')
		$valeurs['actif'] = '';

	$valeurs['constante'] = array('_DIR_PLUGINS','_DIR_PLUGINS_SUPPL');
	if ($verrouille == 'oui')
		$valeurs['constante'] = array('_DIR_PLUGINS_DIST');
	if ($verrouille == 'tous')
		$valeurs['constante'] = array();

	$valeurs['verrouille'] = $verrouille;
	$valeurs['id_paquet'] = $id_paquet;
	$valeurs['actions'] = array();
	$valeurs['ids_paquet'] = _request('ids_paquet');
	$valeurs['_todo'] = _request('_todo');

	return $valeurs;
}

/**
 * Vérifications du formulaire de gestion des plugins
 *
 * Appelle le décideur qui détermine la liste des actions à faire et si celles-ci
 * peuvent être faites (dépendances connues). Une erreur sera levé dans le
 * cas contraire.
 *
 * Si toutes les actions peuvent être faites, une demande de confirmation
 * est envoyée (dans une erreur spéciale), présentant alors toutes les
 * actions qui seront réalisées (celle demandée + celles à faire par voie
 * de conséquence.
 *
 * Si on reçoit une demande de confirmation, on sort sans lever d'erreur !
 * 
 * @param string $voir
 *     Statut des plugins que l'on souhaite voir : actif, inactif, tous
 * @param string $verrouille
 *     Types de plugins que l'on souhaite voir :
 *     - 'non' : les plugins utilisateurs
 *     - 'oui' : les plugins verrouillés (plugins-dist)
 *     - 'tous' : les deux !
 * @param string|int $id_paquet
 *     Identifiant du paquet dont on veut obtenir une description complète
 *     lors de l'affichage du formulaire
 * @param string $redirect
 *     URL de redirection après les traitements
 * @return array
 *     Tableau des erreurs
**/
function formulaires_admin_plugin_verifier_dist($voir='actif', $verrouille='non', $id_paquet='',$redirect=''){

	$erreurs = array();

	if (_request('annuler_actions')) {
		// Requete : Annulation des actions d'installation en cours
		// -- On vide la liste d'actions en cours
		set_request('_todo', '');
		// -- vider les paquets coches s'il y en a
		set_request('ids_paquet', array());
	} elseif (_request('valider_actions')) {
		// ... 
	} else {
		$a_actionner = array();
		
		// actions globales...
		if ($action_globale = _request('action_globale') AND _request('appliquer')) {
			$ids_paquet = _request('ids_paquet');
			if (!is_array($ids_paquet)) {
				$erreurs['message_erreur'] = _T('svp:message_erreur_aucun_plugin_selectionne');
			} else {
				foreach ($ids_paquet as $i) {
					$a_actionner[$i] = $action_globale;
				}
			}
		// action unitaire
		} else {
			$actions = _request('actions');
			// $actions[type][id] = Texte
			// -> $a_actionner[id] = type
			foreach ($actions as $action => $p) {
				foreach ($p as $i => $null) {
					$a_actionner[$i] = $action;
				}
			}
		}
		// lancer les verifications
		if (!$a_actionner)
			$erreurs['message_erreur'] = _T('svp:message_erreur_aucun_plugin_selectionne');
		else {
			
			// On fait appel au decideur pour determiner la liste exacte des commandes apres
			// verification des dependances
			include_spip('inc/svp_decider');
			svp_decider_verifier_actions_demandees($a_actionner, $erreurs);
		}
	}
	
	return $erreurs;
}

/**
 * Traitement du formulaire de gestion des plugins
 *
 * Si une liste d'action est validée, on redirige de formulaire sur
 * l'action 'actionner' qui les traitera une par une.
 * 
 * @param string $voir
 *     Statut des plugins que l'on souhaite voir : actif, inactif, tous
 * @param string $verrouille
 *     Types de plugins que l'on souhaite voir :
 *     - 'non' : les plugins utilisateurs
 *     - 'oui' : les plugins verrouillés (plugins-dist)
 *     - 'tous' : les deux !
 * @param string|int $id_paquet
 *     Identifiant du paquet dont on veut obtenir une description complète
 *     lors de l'affichage du formulaire
 * @param string $redirect
 *     URL de redirection après les traitements
 * @return array
 *     Retours du traitement
**/
function formulaires_admin_plugin_traiter_dist($voir='actif', $verrouille='non', $id_paquet='',$redirect=''){
	
	$retour = array();

	if (_request('valider_actions')) {
		#refuser_traiter_formulaire_ajax();
		// Ajout de la liste des actions à l'actionneur
		// c'est lui qui va effectuer rellement les actions
		// lors de l'appel de action/actionner 
		$actions = unserialize(_request('_todo'));
		include_spip('inc/svp_actionner');
		svp_actionner_traiter_actions_demandees($actions, $retour,$redirect);
	}

	$retour['editable'] = true;
	return $retour;
}

/**
 * Crée une valeur d'action pour l'attribut 'name' d'une saisie de formulaire
 *
 * @example
 *     [(#ID_PAQUET|svp_nom_action{on})]
 *     écrit : actions[on][24]
 * @param int $id_paquet
 *     Identifiant du paquet
 * @param string $action
 *     Une action possible (on, off, stop, up, on, upon, kill)
**/
function filtre_svp_nom_action($id_paquet, $action) {
	return "actions[$action][$id_paquet]";
}

?>
