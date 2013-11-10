<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion de l'action editer_depot
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Actions
 */
if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Action de mise à jour des descriptions d'un dépot 
 *
 * @return array
 *     Liste identifiant du dépot, texte d'erreur éventuel
**/
function action_editer_depot_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	// Verification des autorisations
	if (!autoriser('webmestre')) {
		include_spip('inc/minipres');
		echo minipres();
		exit();
	}

	// Le depot n'est jamais cree par une edition mais via le formulaire ajouter_depot
	// On est toujours en presence d'une mise a jour pour cette action, l'id_depot
	// doit donc etre renseigne sinon c'est une erreur
	if ($id_depot = intval($arg)) {
		// On teste si l'auteur est connecte. Si non on renvoie sur le formulaire login
		$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];
		if (!($id_auteur)) {
			include_spip('inc/headers');
			redirige_url_ecrire();
		}

		// On met a jour le depot avec les saisies
		if (sql_updateq('spip_depots', 
						array('titre'=> _request('titre'), 
							'descriptif'=> _request('descriptif'),
							'type'=> _request('type')),
						'id_depot=' . Sql::quote($id_depot)));
			// Enregistre l'envoi dans la BD
			// Dans le cas du depot rien n'est fait actuellement, on garde cette fonction
			// par souci de coherence avec les autres editions d'objet et pour usage futur
			$err = depots_set($id_depot);
			if (!$err)
				spip_log("ACTION MODIFIER DEPOT (manuel) : id_depot = ". $id_depot, 'svp_actions.' . _LOG_INFO);
	}

	return array($id_depot, $err);
}


/**
 * Appelle toutes les fonctions de modification d'un dépot
 * $err est de la forme '&trad_err=1'
 *
 * @note
 *     Cette fonction ne fait rien actuellement !!
 * 
 * @param int $id_depot
 *     Identifiant du dépot
 * @return string
 *     Texte d'une eventuelle erreur
**/
function depots_set($id_depot) {
	$err = '';

	// unifier $texte en cas de texte trop long
	// - non utilisabe sur le descriptif aujourd'huiez

	// Enregistrer les revisions
	// - revisions_depot()

	// Modifier le statut ?
	// - instituer_depot()

	return $err;
}

?>
