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
 * Gestion générique de modification des objets éditoriaux
 *
 * @package SPIP\Core\Edition
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Point d'entrée d'édition d'un objet
 *
 * On ne peut entrer que par un appel en fournissant $id et $objet
 * ou avec un argument d'action sécurisée de type "objet/id"
 *
 * @param int $id
 * @param string $objet
 * @param array $set
 * @return array
 */
function action_editer_objet_dist($id = null, $objet = null, $set = null) {

	// appel direct depuis une url avec arg = "objet/id"
	if (is_null($id) or is_null($objet)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
		[$objet, $id] = array_pad(explode('/', $arg, 2), 2, null);
	}

	// appel incorrect ou depuis une url erronnée interdit
	if (is_null($id) or is_null($objet)) {
		include_spip('inc/minipres');
		echo minipres(_T('info_acces_interdit'));
		die();
	}

	// si id n'est pas un nombre, c'est une creation
	// mais on verifie qu'on a toutes les donnees qu'il faut.
	if (!$id = intval($id)) {
		// on ne sait pas si un parent existe mais on essaye
		$id_parent = _request('id_parent');
		$id = objet_inserer($objet, $id_parent);
	}

	if (!($id = intval($id)) > 0) {
		return [$id, _L('echec enregistrement en base')];
	}

	// Enregistre l'envoi dans la BD
	$err = objet_modifier($objet, $id, $set);

	return [$id, $err];
}

/**
 * Appelle toutes les fonctions de modification d'un objet
 * $err est un message d'erreur eventuelle
 *
 * @param string $objet
 * @param int $id
 * @param array|null $set
 * @return mixed|string
 */
function objet_modifier($objet, $id, $set = null) {
	if (($t = objet_type($objet)) !== $objet) {
		spip_log("objet_modifier: appel avec type $objet invalide au lieu de $t", 'editer' . _LOG_INFO_IMPORTANTE);
		$objet = $t;
	}
	if (
		include_spip('action/editer_' . $objet)
		and function_exists($modifier = $objet . '_modifier')
	) {
		return $modifier($id, $set);
	}

	$table_sql = table_objet_sql($objet);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table_sql);
	if (!$desc or !isset($desc['field'])) {
		spip_log("Objet $objet inconnu dans objet_modifier", 'editer' . _LOG_ERREUR);

		return _L("Erreur objet $objet inconnu");
	}
	include_spip('inc/modifier');

	$champ_date = '';
	if (isset($desc['date']) and $desc['date']) {
		$champ_date = $desc['date'];
	} elseif (isset($desc['field']['date'])) {
		$champ_date = 'date';
	}

	$include_list = array_keys($desc['field']);
	// on ne traite pas la cle primaire par defaut, notamment car
	// sur une creation, id_x vaut 'oui', et serait enregistre en id_x=0 dans la base
	$include_list = array_diff($include_list, [$desc['key']['PRIMARY KEY']]);

	if (isset($desc['champs_editables']) and is_array($desc['champs_editables'])) {
		$include_list = $desc['champs_editables'];
	}
	$c = collecter_requests(
		// include list
		$include_list,
		// exclude list
		[$champ_date, 'statut', 'id_parent', 'id_secteur'],
		// donnees eventuellement fournies
		$set
	);

	// Si l'objet est publie, invalider les caches et demander sa reindexation
	if (objet_test_si_publie($objet, $id)) {
		$invalideur = "id='$objet/$id'";
		$indexation = true;
	} else {
		$invalideur = '';
		$indexation = false;
	}

	if (
		$err = objet_modifier_champs(
			$objet,
			$id,
			[
			'data' => $set,
			'nonvide' => '',
			'invalideur' => $invalideur,
			'indexation' => $indexation,
			// champ a mettre a date('Y-m-d H:i:s') s'il y a modif
			'date_modif' => (isset($desc['field']['date_modif']) ? 'date_modif' : '')
			],
			$c
		)
	) {
		return $err;
	}

	// Modification de statut, changement de rubrique ?
	// FIXME: Ici lorsqu'un $set est passé, la fonction collecter_requests() retourne tout
	//         le tableau $set hors liste d’exclusion, mais du coup on a possiblement des champs en trop.
	$c = collecter_requests([$champ_date, 'statut', 'id_parent'], [], $set);
	$err = objet_instituer($objet, $id, $c);

	return $err;
}

/**
 * Insere en base un objet generique
 *
 * @param string $objet
 * @param int $id_parent
 * @param array|null $set
 * @global array $GLOBALS ['visiteur_session']
 * @global array $GLOBALS ['meta']
 * @global string $GLOBALS ['spip_lang']
 * @return bool|int
 */
function objet_inserer($objet, $id_parent = null, $set = null) {
	$d = null;
	if (($t = objet_type($objet)) !== $objet) {
		spip_log("objet_inserer: appel avec type $objet invalide au lieu de $t", 'editer' . _LOG_INFO_IMPORTANTE);
		$objet = $t;
	}
	if (
		include_spip('action/editer_' . $objet)
		and function_exists($inserer = $objet . '_inserer')
	) {
		return $inserer($id_parent, $set);
	}

	$table_sql = table_objet_sql($objet);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table_sql);
	if (!$desc or !isset($desc['field'])) {
		return 0;
	}

	$lang_rub = '';
	$champs = [];
	if (isset($desc['field']['id_rubrique'])) {
		// Si id_rubrique vaut 0 ou n'est pas definie, creer l'objet
		// dans la premiere rubrique racine
		if (!$id_rubrique = intval($id_parent)) {
			$row = sql_fetsel('id_rubrique, id_secteur, lang', 'spip_rubriques', 'id_parent=0', '', '0+titre,titre', '1');
			$id_rubrique = $row['id_rubrique'];
		} else {
			$row = sql_fetsel('lang, id_secteur', 'spip_rubriques', 'id_rubrique=' . intval($id_rubrique));
		}

		$champs['id_rubrique'] = $id_rubrique;
		if (isset($desc['field']['id_secteur'])) {
			$champs['id_secteur'] = $row['id_secteur'];
		}
		$lang_rub = $row['lang'];
	}

	// La langue a la creation : si les liens de traduction sont autorises
	// dans les rubriques, on essaie avec la langue de l'auteur,
	// ou a defaut celle de la rubrique
	// Sinon c'est la langue de la rubrique qui est choisie + heritee
	if (
		isset($desc['field']['lang']) and !empty($GLOBALS['meta']['multi_objets']) and in_array(
			$table_sql,
			explode(',', $GLOBALS['meta']['multi_objets'])
		)
	) {
		lang_select($GLOBALS['visiteur_session']['lang']);
		if (
			in_array(
				$GLOBALS['spip_lang'],
				explode(',', $GLOBALS['meta']['langues_multilingue'])
			)
		) {
			$champs['lang'] = $GLOBALS['spip_lang'];
			if (isset($desc['field']['langue_choisie'])) {
				$champs['langue_choisie'] = 'oui';
			}
		}
	} elseif (isset($desc['field']['lang']) and isset($desc['field']['langue_choisie'])) {
		$champs['lang'] = ($lang_rub ?: $GLOBALS['meta']['langue_site']);
		$champs['langue_choisie'] = 'non';
	}

	if (isset($desc['field']['statut'])) {
		if (isset($desc['statut_textes_instituer'])) {
			$cles_statut = array_keys($desc['statut_textes_instituer']);
			$champs['statut'] = reset($cles_statut);
		} else {
			$champs['statut'] = 'prepa';
		}
	}


	if ((isset($desc['date']) and $d = $desc['date']) or isset($desc['field'][$d = 'date'])) {
		$champs[$d] = date('Y-m-d H:i:s');
	}

	if ($set) {
		$champs = array_merge($champs, $set);
	}

	// Envoyer aux plugins
	$champs = pipeline(
		'pre_insertion',
		[
			'args' => [
				'table' => $table_sql,
				'id_parent' => $id_parent,
			],
			'data' => $champs
		]
	);

	$id = sql_insertq($table_sql, $champs);

	if ($id) {
		// controler si le serveur n'a pas renvoye une erreur
		// et associer l'auteur sinon
		// si la table n'a pas deja un champ id_auteur
		// et si le form n'a pas poste un id_auteur (meme vide, ce qui sert a annuler cette auto association)
		if (
			$id > 0
			and !isset($desc['field']['id_auteur'])
		) {
			$id_auteur = ((is_null(_request('id_auteur')) and isset($GLOBALS['visiteur_session']['id_auteur'])) ?
				$GLOBALS['visiteur_session']['id_auteur']
				: _request('id_auteur'));
			if ($id_auteur) {
				include_spip('action/editer_auteur');
				auteur_associer($id_auteur, [$objet => $id]);
			}
		}

		pipeline(
			'post_insertion',
			[
				'args' => [
					'table' => $table_sql,
					'id_parent' => $id_parent,
					'id_objet' => $id,
				],
				'data' => $champs
			]
		);
	}

	return $id;
}


/**
 * Modifie le statut et/ou la date d'un objet
 *
 * @param string $objet
 * @param int $id
 * @param array $c
 *   $c est un array ('statut', 'id_parent' = changement de rubrique)
 *   statut et rubrique sont lies, car un admin restreint peut deplacer
 *   un objet publie vers une rubrique qu'il n'administre pas
 * @param bool $calcul_rub
 * @return string
 */
function objet_instituer($objet, $id, $c, $calcul_rub = true) {
	if (($t = objet_type($objet)) !== $objet) {
		spip_log("objet_instituer: appel avec type $objet invalide au lieu de $t", 'editer' . _LOG_INFO_IMPORTANTE);
		$objet = $t;
	}
	if (
		include_spip('action/editer_' . $objet)
		and function_exists($instituer = $objet . '_instituer')
	) {
		return $instituer($id, $c, $calcul_rub);
	}

	$table_sql = table_objet_sql($objet);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table_sql);
	if (!$desc or !isset($desc['field'])) {
		return _L("Impossible d'instituer $objet : non connu en base");
	}

	include_spip('inc/autoriser');
	include_spip('inc/rubriques');
	include_spip('inc/modifier');

	$sel = [];
	$sel[] = (isset($desc['field']['statut']) ? 'statut' : "'' as statut");

	$champ_date = '';
	if (isset($desc['date']) and $desc['date']) {
		$champ_date = $desc['date'];
	} elseif (isset($desc['field']['date'])) {
		$champ_date = 'date';
	}

	$sel[] = ($champ_date ? "$champ_date as date" : "'' as date");
	$sel[] = (isset($desc['field']['id_rubrique']) ? 'id_rubrique' : '0 as id_rubrique');

	$row = sql_fetsel($sel, $table_sql, id_table_objet($objet) . '=' . intval($id));

	$id_rubrique = $row['id_rubrique'];
	$statut_ancien = $statut = $row['statut'];
	$date_ancienne = $date = $row['date'];
	$champs = [];

	$d = ($date and isset($c[$champ_date])) ? $c[$champ_date] : null;
	$s = (isset($desc['field']['statut']) and isset($c['statut'])) ? $c['statut'] : $statut;

	// cf autorisations dans inc/instituer_objet
	if ($s != $statut or ($d and $d != $date)) {
		if (
			$id_rubrique ?
			autoriser('publierdans', 'rubrique', $id_rubrique)
			:
			autoriser('instituer', $objet, $id, null, ['statut' => $s])
		) {
			$statut = $champs['statut'] = $s;
		} else {
			if ($s != 'publie' and autoriser('modifier', $objet, $id)) {
				$statut = $champs['statut'] = $s;
			} else {
				spip_log("editer_objet $objet #$id refus " . json_encode($c, JSON_THROW_ON_ERROR), 'editer' . _LOG_INFO_IMPORTANTE);
			}
		}

		// En cas de publication, fixer la date a "maintenant"
		// sauf si $c commande autre chose
		// ou si l'objet est deja date dans le futur
		// En cas de proposition d'un objet (mais pas depublication), idem
		if ($champ_date) {
			if (
				$champs['statut'] == 'publie'
				or ($champs['statut'] == 'prop' and !in_array($statut_ancien, ['publie', 'prop']))
				or $d
			) {
				if ($d or strtotime($d = $date) > time()) {
					$champs[$champ_date] = $date = $d;
				} else {
					$champs[$champ_date] = $date = date('Y-m-d H:i:s');
				}
			}
		}
	}

	// Verifier que la rubrique demandee existe et est differente
	// de la rubrique actuelle
	if (
		$id_rubrique
		and isset($c['id_parent'])
		and $id_parent = $c['id_parent']
		and $id_parent != $id_rubrique
		and (sql_fetsel('1', 'spip_rubriques', 'id_rubrique=' . intval($id_parent)))
	) {
		$champs['id_rubrique'] = $id_parent;

		// si l'objet etait publie
		// et que le demandeur n'est pas admin de la rubrique
		// repasser l'objet en statut 'propose'.
		if (
			$statut == 'publie'
			and !autoriser('publierdans', 'rubrique', $id_rubrique)
		) {
			$champs['statut'] = 'prop';
		}
	}


	// Envoyer aux plugins
	$champs = pipeline(
		'pre_edition',
		[
			'args' => [
				'table' => $table_sql,
				'id_objet' => $id,
				'action' => 'instituer',
				'statut_ancien' => $statut_ancien,
				'date_ancienne' => $date_ancienne,
				'id_parent_ancien' => $id_rubrique,
			],
			'data' => $champs
		]
	);

	if (!(is_countable($champs) ? count($champs) : 0)) {
		return '';
	}

	// Envoyer les modifs.
	objet_editer_heritage($objet, $id, $id_rubrique, $statut_ancien, $champs, $calcul_rub);

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='$objet/$id'");

	/*
	if ($date) {
		$t = strtotime($date);
		$p = @$GLOBALS['meta']['date_prochain_postdate'];
		if ($t > time() AND (!$p OR ($t < $p))) {
			ecrire_meta('date_prochain_postdate', $t);
		}
	}*/

	// Pipeline
	pipeline(
		'post_edition',
		[
			'args' => [
				'table' => $table_sql,
				'id_objet' => $id,
				'action' => 'instituer',
				'statut_ancien' => $statut_ancien,
				'date_ancienne' => $date_ancienne,
				'id_parent_ancien' => $id_rubrique,
			],
			'data' => $champs
		]
	);

	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications(
			"instituer$objet",
			$id,
			['statut' => $statut, 'statut_ancien' => $statut_ancien, 'date' => $date, 'date_ancienne' => $date_ancienne]
		);
	}

	return ''; // pas d'erreur
}

/**
 * Fabrique la requete d'institution de l'objet, avec champs herites
 *
 * @param string $objet
 * @param int $id
 * @param int $id_rubrique
 * @param string $statut
 * @param array $champs
 * @param bool $cond
 * @return void
 */
function objet_editer_heritage($objet, $id, $id_rubrique, $statut, $champs, $cond = true) {
	$table_sql = table_objet_sql($objet);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table_sql);

	// Si on deplace l'objet
	// changer aussi son secteur et sa langue (si heritee)
	if (isset($champs['id_rubrique'])) {
		$row_rub = sql_fetsel('id_secteur, lang', 'spip_rubriques', 'id_rubrique=' . sql_quote($champs['id_rubrique']));
		$langue = $row_rub['lang'];

		if (isset($desc['field']['id_secteur'])) {
			$champs['id_secteur'] = $row_rub['id_secteur'];
		}

		if (isset($desc['field']['lang']) and isset($desc['field']['langue_choisie'])) {
			if (
				sql_fetsel(
					'1',
					$table_sql,
					id_table_objet($objet) . '=' . intval($id) . " AND langue_choisie<>'oui' AND lang<>" . sql_quote($langue)
				)
			) {
				$champs['lang'] = $langue;
			}
		}
	}

	if (!$champs) {
		return;
	}
	sql_updateq($table_sql, $champs, id_table_objet($objet) . '=' . intval($id));

	// Changer le statut des rubriques concernees
	if ($cond) {
		include_spip('inc/rubriques');
		//$postdate = ($GLOBALS['meta']["post_dates"] == "non" AND isset($champs['date']) AND (strtotime($champs['date']) < time()))?$champs['date']:false;
		$postdate = false;
		// On rajoute les infos de l'objet
		$infos = [
			'objet' => $objet,
			'id_objet' => $id,
			'statut_ancien' => $statut,
		];
		calculer_rubriques_if($id_rubrique, $champs, $infos, $postdate);
	}
}


/**
 * Lit un objet donné connu par son id ou par un identifiant textuel unique et renvoie tout ou partie de sa
 * description.
 * Il est possible pour un objet donné de fournir la fonction `<objet>_lire_champs` qui renvoie simplement tous les
 * champs de l'objet concerné sans aucun autre traitement. Sinon, l'appel SQL est réalisé par l'API.
 *
 * @param string     $objet     Type d'objet (comme article ou rubrique)
 * @param int|string $valeur_id Valeur du champ identifiant
 * @param array      $options   Tableau d'options dont les index possibles sont:
 *                              - champs : liste des champs à renvoyer. Si absent ou vide la fonction
 *                                renvoie tous les champs.
 *                              - champ_id : nom du champ utilisé comme identifiant de l'objet. Si absent ou vide on
 *                                utilise l'id défini dans la déclaration de l'objet.
 *                              - force : true pour reforcer une lecture en base meme si un cache existe
 *
 * @return array|string|int|bool
 *   si champs est non fourni ou au format array
 *     false : l'objet demande n'existe pas
 *     array vide : l'objet existe, mais aucun champ demande n'existe
 *     array non vide : objet avec le ou les champs demandes existants (les champs demandes non existant sont absent)
 *   si champs est fourni au format string
 *     false : l'objet demande n'existe pas OU le champs demande n'existe pas
 *     string|int : valeur du champ demande pour l'objet demande
 */
function objet_lire($objet, $valeur_id, $options = []) {
	if (($t = objet_type($objet)) !== $objet) {
		spip_log("objet_lire: appel avec type $objet invalide au lieu de $t", 'editer' . _LOG_INFO_IMPORTANTE);
		$objet = $t;
	}

	// tableau du cache des descriptions et des id d'objet (au sens id_xxx).
	// Les tableaux sont toujours indexés par le trio [objet][cle][valeur_cle]
	static $descriptions = [];

	// On détermine le nom du champ id de la table.
	include_spip('base/objets');
	$primary = id_table_objet($objet);

	// On détermine l'id à utiliser.
	$champ_id = (!empty($options['champ_id']) ? $options['champ_id'] : $primary);

	// Si l'objet n'a pas encore été stocké, il faut récupérer sa description complète.
	if (
		!isset($descriptions[$objet][$champ_id][$valeur_id])
		or (isset($options['force']) and $options['force'])
	) {
		// Il est possible pour un type d'objet de fournir une fonction de lecture de tous les champs d'un objet.
		if (
			include_spip('action/editer_' . $objet)
			and function_exists($lire = "{$objet}_lire_champs")
		) {
			$valeurs = $lire($objet, $valeur_id, $champ_id);
		} else {
			// On récupère la table SQL à partir du type d'objet.
			$table = table_objet_sql($objet);

			// La condition est appliquée sur le champ désigné par l'utilisateur.
			$where = [
				$champ_id . '=' . sql_quote($valeur_id)
			];

			// Acquisition de tous les champs de l'objet : si l'accès SQL retourne une erreur on renvoie un tableau vide.
			$valeurs = sql_fetsel('*', $table, $where);
		}

		if (!$valeurs) {
			$valeurs = false;
		}

		$descriptions[$objet][$champ_id][$valeur_id] = $valeurs;

		if ($champ_id !== $primary and isset($valeurs[$primary])) {
			$descriptions[$objet][$primary][$valeurs[$primary]] = $valeurs;
			$descriptions[$objet][$champ_id][$valeur_id] = &$descriptions[$objet][$primary][$valeurs[$primary]];
		}
	}

	$retour = $descriptions[$objet][$champ_id][$valeur_id];

	// On ne retourne maintenant que les champs demandés.
	// - on détermine les informations à renvoyer.
	if ($retour and !empty($options['champs'])) {
		$champs = $options['champs'];
		// Extraction des seules informations demandées.
		// -- si on demande une information unique on renvoie la valeur simple, sinon on renvoie un tableau.
		// -- si une information n'est pas un champ valide elle n'est pas renvoyée sans renvoyer d'erreur.
		if (is_array($champs)) {
			// Tableau des informations valides
			$retour = array_intersect_key($retour, array_flip($champs));
		} else {
			// Valeur unique demandée.
			$retour = ($retour[$champs] ?? false);
		}
	}

	return $retour;
}
