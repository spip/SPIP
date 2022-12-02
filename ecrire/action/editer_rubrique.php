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
 * Gestion de l'action editer_rubrique et de l'API d'édition des rubriques
 *
 * @package SPIP\Core\Rubriques\Edition
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/rubriques');

/**
 * Action d'édition d'une rubrique
 *
 * Crée la rubrique si elle n'existe pas encore
 * Redirige après l'action sur _request('redirect') si présent
 *
 * @param null|int $arg
 *     - null : vérifie la sécurité de l'action.
 *              Si ok, obtient l'identifiant de rubrique à éditer
 *              (oui 'oui' pour une nouvelle rubrique)
 *     - int  : identifiant de rubrique dont on demande l'édition
 * @return array
 *     Liste : identifiant de la rubrique, message d'erreur éventuel.
 *
 */
function action_editer_rubrique_dist($arg = null) {

	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	if (!$id_rubrique = intval($arg)) {
		if ($arg != 'oui') {
			include_spip('inc/headers');
			redirige_url_ecrire();
		}
		$id_rubrique = rubrique_inserer(_request('id_parent'));
	}

	$err = rubrique_modifier($id_rubrique);

	if (_request('redirect')) {
		$redirect = parametre_url(
			urldecode(_request('redirect')),
			'id_rubrique',
			$id_rubrique,
			'&'
		);

		include_spip('inc/headers');
		redirige_par_entete($redirect);
	}

	return [$id_rubrique, $err];
}


/**
 * Insérer une rubrique en base
 *
 * @param int $id_parent
 *     Identifiant de la rubrique parente.
 *     0 pour la racine.
 * @param array|null $set
 * @return int
 *     Identifiant de la rubrique crée
 */
function rubrique_inserer($id_parent, $set = null) {
	$champs = [
		'titre' => _T('item_nouvelle_rubrique'),
		'id_parent' => intval($id_parent),
		'statut' => 'prepa'
	];

	if ($set) {
		$champs = array_merge($champs, $set);
	}

	// Envoyer aux plugins
	$champs = pipeline(
		'pre_insertion',
		[
			'args' => [
				'table' => 'spip_rubriques',
			],
			'data' => $champs
		]
	);

	$id_rubrique = sql_insertq('spip_rubriques', $champs);
	pipeline(
		'post_insertion',
		[
			'args' => [
				'table' => 'spip_rubriques',
				'id_objet' => $id_rubrique
			],
			'data' => $champs
		]
	);
	propager_les_secteurs();
	calculer_langues_rubriques();

	return $id_rubrique;
}

/**
 * Modifier une rubrique en base
 *
 * @param int $id_rubrique
 *     Identifiant de la rubrique modifiée
 * @param array|null $set
 *     Tableau qu'on peut proposer en lieu et place de _request()
 * @return bool|string
 *     - false  : Aucune modification, aucun champ n'est à modifier
 *     - chaîne vide : Vide si tout s'est bien passé
 *     - chaîne : texte d'un message d'erreur
 */
function rubrique_modifier($id_rubrique, $set = null) {
	include_spip('inc/autoriser');
	include_spip('inc/filtres');

	include_spip('inc/modifier');
	$c = collecter_requests(
		// include list
		objet_info('rubrique', 'champs_editables'),
		// exclude list
		['id_parent', 'confirme_deplace'],
		// donnees eventuellement fournies
		$set
	);

	if (
		$err = objet_modifier_champs(
			'rubrique',
			$id_rubrique,
			[
			'data' => $set,
			'nonvide' => ['titre' => _T('titre_nouvelle_rubrique') . ' ' . _T('info_numero_abbreviation') . $id_rubrique]
			],
			$c
		)
	) {
		return $err;
	}

	$c = collecter_requests(['id_parent', 'confirme_deplace'], [], $set);
	// Deplacer la rubrique
	if (isset($c['id_parent'])) {
		$err = rubrique_instituer($id_rubrique, $c);
	}

	// invalider les caches marques de cette rubrique
	include_spip('inc/invalideur');
	suivre_invalideur("id='rubrique/$id_rubrique'");
	// et celui de menu_rubriques
	effacer_meta('date_calcul_rubriques');

	return $err;
}

/**
 * Déplace les brèves d'une rubrique dans le secteur d'un nouveau parent
 *
 * Si c'est une rubrique-secteur contenant des brèves, on ne deplace
 * que si $confirme_deplace == 'oui', et change alors l'id_rubrique des
 * brèves en question
 *
 * @todo À déporter dans le plugin brèves via un pipeline ?
 *
 * @param int $id_rubrique
 *     Identifiant de la rubrique déplacée
 * @param int $id_parent
 *     Identifiant du nouveau parent de la rubrique
 * @param array $c
 *     Informations pour l'institution (id_rubrique, confirme_deplace)
 * @return bool
 *     true si le déplacement est fait ou s'il n'y a rien à faire
 *     false si la confirmation du déplacement n'est pas présente
 */
function editer_rubrique_breves($id_rubrique, $id_parent, $c = []) {
	if (sql_table_exists('spip_breves')) {
		if (!sql_countsel('spip_breves', "id_rubrique=$id_rubrique")) {
			return true;
		}

		if (empty($c['confirme_deplace']) or $c['confirme_deplace'] != 'oui') {
			return false;
		}

		if (
			$id_secteur = sql_getfetsel(
				'id_secteur',
				'spip_rubriques',
				"id_rubrique=$id_parent"
			)
		) {
			sql_updateq('spip_breves', ['id_rubrique' => $id_secteur], "id_rubrique=$id_rubrique");
		}
	}

	return true;
}


/**
 * Instituer une rubrique (changer son parent)
 *
 * Change le parent d'une rubrique, si les autorisations sont correctes,
 * mais n'accèpte pas de déplacer une rubrique dans une de ses filles, tout de même !
 *
 * Recalcule les secteurs, les langues et déplace les brèves au passage.
 *
 * @param int $id_rubrique
 *     Identifiant de la rubrique à instituer
 * @param array $c
 *     Informations pour l'institution (id_rubrique, confirme_deplace)
 * @global array $GLOBALS ['visiteur_session']
 * @return string
 *     Chaîne vide : aucune erreur
 *     Chaîne : texte du message d'erreur
 */
function rubrique_instituer($id_rubrique, $c) {
	// traitement de la rubrique parente
	// interdiction de deplacer vers ou a partir d'une rubrique
	// qu'on n'administre pas.

	if (null !== ($id_parent = $c['id_parent'])) {
		$id_parent = intval($id_parent);
		$filles = calcul_branche_in($id_rubrique);
		if (strpos(",$id_parent,", (string) ",$filles,") !== false) {
			spip_log("La rubrique $id_rubrique ne peut etre fille de sa descendante $id_parent");
		} else {
			$s = sql_fetsel('id_parent, statut', 'spip_rubriques', "id_rubrique=$id_rubrique");
			$old_parent = $s['id_parent'];

			if (
				!($id_parent != $old_parent
				and autoriser('publierdans', 'rubrique', $id_parent)
				and autoriser('creerrubriquedans', 'rubrique', $id_parent)
				and autoriser('publierdans', 'rubrique', $old_parent)
				)
			) {
				if ($s['statut'] != 'prepa') {
					spip_log("deplacement de $id_rubrique vers $id_parent refuse a " . $GLOBALS['visiteur_session']['id_auteur'] . ' ' . $GLOBALS['visiteur_session']['statut']);
				}
			} elseif (editer_rubrique_breves($id_rubrique, $id_parent, $c)) {
				$statut_ancien = $s['statut'];
				sql_updateq('spip_rubriques', ['id_parent' => $id_parent], "id_rubrique=$id_rubrique");


				propager_les_secteurs();

				// Deplacement d'une rubrique publiee ==> chgt general de leur statut
				if ($statut_ancien == 'publie') {
					calculer_rubriques_if($old_parent, ['id_rubrique' => $id_parent], ['statut_ancien' => $statut_ancien]);
				}
				// Creation ou deplacement d'une rubrique non publiee
				// invalider le cache de leur menu
				elseif (!$statut_ancien || $old_parent != $id_parent) {
					effacer_meta('date_calcul_rubriques');
				}

				calculer_langues_rubriques();
			}
		}
	}

	return ''; // pas d'erreur
}
