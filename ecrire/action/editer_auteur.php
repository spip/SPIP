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
 * Gestion de l'action editer_auteur et de l'API d'édition d'un auteur
 *
 * @package SPIP\Core\Auteurs\Edition
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Éditer ou créer un auteur
 *
 * Si aucun identifiant d'auteur n'est donné, on crée alors un nouvel auteur.
 *
 * @global array visiteur_session
 * @uses auteur_inserer()
 * @uses auteur_modifier()
 *
 * @param array|null $arg
 *     Identifiant de l'auteur. En absence utilise l'argument
 *     de l'action sécurisée.
 * @return array
 *     Liste (identifiant de l'auteur, Texte d'erreur éventuel)
 */
function action_editer_auteur_dist($arg = null) {

	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}


	// si id_auteur n'est pas un nombre, c'est une creation
	if (!$id_auteur = intval($arg)) {
		if (($id_auteur = auteur_inserer()) > 0) {
			# cf. GROS HACK
			# recuperer l'eventuel logo charge avant la creation
			# ils ont un id = 0-id_auteur de la session
			$id_hack = 0 - $GLOBALS['visiteur_session']['id_auteur'];
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			foreach (['on', 'off'] as $type) {
				if ($logo = $chercher_logo($id_hack, 'id_auteur', $type)) {
					if ($logo = reset($logo)) {
						rename($logo, str_replace($id_hack, $id_auteur, $logo));
					}
				}
			}
		}
	}

	// Enregistre l'envoi dans la BD
	$err = '';
	if ($id_auteur > 0) {
		$err = auteur_modifier($id_auteur);
	}

	if ($err) {
		spip_log("echec editeur auteur: $err", _LOG_ERREUR);
	}

	return [$id_auteur, $err];
}

/**
 * Insérer un auteur en base
 *
 * @pipeline_appel pre_insertion
 * @pipeline_appel post_insertion
 *
 * @param string|null $source
 *     D'où provient l'auteur créé ? par défaut 'spip', mais peut être 'ldap' ou autre.
 * @param array|null $set
 * @return int
 *     Identifiant de l'auteur créé
 */
function auteur_inserer($source = null, $set = null) {

	// Ce qu'on va demander comme modifications
	$champs = [];
	$champs['source'] = $source ?: 'spip';

	$champs['login'] = '';
	$champs['statut'] = '5poubelle';  // inutilisable tant qu'il n'a pas ete renseigne et institue
	$champs['webmestre'] = 'non';
	if (empty($champs['imessage'])) {
		$champs['imessage'] = 'oui';
	}

	if ($set) {
		$champs = array_merge($champs, $set);
	}

	// Envoyer aux plugins
	$champs = pipeline(
		'pre_insertion',
		[
			'args' => [
				'table' => 'spip_auteurs',
			],
			'data' => $champs
		]
	);
	$id_auteur = sql_insertq('spip_auteurs', $champs);
	pipeline(
		'post_insertion',
		[
			'args' => [
				'table' => 'spip_auteurs',
				'id_objet' => $id_auteur
			],
			'data' => $champs
		]
	);
	
	// Appeler une notification
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications(
			"auteur_inserer",
			$id_auteur,
			array(
				'champs' => $champs,
			)
		);
	}

	return $id_auteur;
}


/**
 * Modifier un auteur
 *
 * Appelle toutes les fonctions de modification d'un auteur
 *
 * @param int $id_auteur
 *     Identifiant de l'auteur
 * @param array|null $set
 *     Couples (colonne => valeur) de données à modifier.
 *     En leur absence, on cherche les données dans les champs éditables
 *     qui ont été postés (via collecter_requests())
 * @param bool $force_update
 *   Permet de forcer la maj en base des champs fournis, sans passer par instancier.
 *   Utilise par auth/spip
 * @return string|null
 *
 *     - Chaîne vide si aucune erreur,
 *     - Chaîne contenant un texte d'erreur sinon.
 */
function auteur_modifier($id_auteur, $set = null, $force_update = false) {

	include_spip('inc/modifier');
	include_spip('inc/filtres');
	$c = collecter_requests(
		// include list
		objet_info('auteur', 'champs_editables'),
		// exclude list
		$force_update ? [] : ['webmestre', 'pass', 'login'],
		// donnees eventuellement fournies
		$set
	);

	if (
		$err = objet_modifier_champs(
			'auteur',
			$id_auteur,
			[
			'data' => $set,
			'nonvide' => ['nom' => _T('ecrire:item_nouvel_auteur')]
			],
			$c
		)
	) {
		return $err;
	}
	$session = $c;

	$err = '';
	if (!$force_update) {
		// Modification de statut, changement de rubrique ?
		$c = collecter_requests(
		// include list
			[
				'statut',
				'new_login',
				'new_pass',
				'login',
				'pass',
				'webmestre',
				'restreintes',
				'id_parent'
			],
			// exclude list
			[],
			// donnees eventuellement fournies
			$set
		);
		if (isset($c['new_login']) and !isset($c['login'])) {
			$c['login'] = $c['new_login'];
		}
		if (isset($c['new_pass']) and !isset($c['pass'])) {
			$c['pass'] = $c['new_pass'];
		}
		$err = auteur_instituer($id_auteur, $c);
		$session = array_merge($session, $c);
	}

	// .. mettre a jour les sessions de cet auteur
	include_spip('inc/session');
	$session['id_auteur'] = $id_auteur;
	unset($session['new_login']);
	unset($session['new_pass']);
	actualiser_sessions($session);

	return $err;
}

/**
 * Associer un auteur à des objets listés
 *
 * @uses objet_associer()
 *
 * @param int $id_auteur
 *     Identifiant de l'auteur
 * @param array $objets
 *     Liste sous la forme `array($objet=>$id_objets,...)`.
 *     `$id_objets` peut lui-même être un scalaire ou un tableau pour une liste
 *     d'objets du même type.
 * @param array|null $qualif
 *     Optionnellement indique une qualification du (des) lien(s) qui sera
 *     alors appliquée dans la foulée.
 *     En cas de lot de liens, c'est la même qualification qui est appliquée à tous
 * @return string
 */
function auteur_associer($id_auteur, $objets, $qualif = null) {
	include_spip('action/editer_liens');

	return objet_associer(['auteur' => $id_auteur], $objets, $qualif);
}

/**
 * Dissocier un auteur des objets listés
 *
 * @uses objet_dissocier()
 *
 * @param int $id_auteur
 *     Identifiant de l'auteur
 * @param array $objets
 *     Liste sous la forme `array($objet=>$id_objets,...)`.
 *     `$id_objets` peut lui-même être un scalaire ou un tableau pour une liste
 *     d'objets du même type.
 *
 *     Un `*` pour $id_auteur,$objet,$id_objet permet de traiter par lot
 * @return string
 */
function auteur_dissocier($id_auteur, $objets) {
	include_spip('action/editer_liens');

	return objet_dissocier(['auteur' => $id_auteur], $objets);
}

/**
 * Qualifier le lien d'un auteur avec les objets listés
 *
 * @uses objet_qualifier_liens()
 *
 * @param int $id_auteur
 *     Identifiant de l'auteur
 * @param array $objets
 *     Liste sous la forme `array($objet=>$id_objets,...)`.
 *     `$id_objets` peut lui-même être un scalaire ou un tableau pour une liste
 *     d'objets du même type.
 *
 *     Un `*` pour $id_auteur,$objet,$id_objet permet de traiter par lot
 * @param array $qualif
 *     Couples (colonne, valeur) tel que `array('vu'=>'oui');`
 * @return bool|int
 */
function auteur_qualifier($id_auteur, $objets, $qualif) {
	include_spip('action/editer_liens');

	return objet_qualifier_liens(['auteur' => $id_auteur], $objets, $qualif);
}


/**
 * Modifier le statut d'un auteur, ou son login/pass
 *
 * @pipeline_appel pre_edition
 * @pipeline_appel post_edition
 *
 * @param int $id_auteur
 *     Identifiant de l'auteur
 * @param array $c
 *     Couples (colonne => valeur) des données à instituer
 * @param bool $force_webmestre
 *     Autoriser un auteur à passer webmestre (force l'autorisation)
 * @return bool|string
 */
function auteur_instituer($id_auteur, $c, $force_webmestre = false) {
	if (!$id_auteur = intval($id_auteur)) {
		return false;
	}
	$erreurs = []; // contiendra les differentes erreurs a traduire par _T()
	$champs = [];

	// les memoriser pour les faire passer dans le pipeline pre_edition
	if (isset($c['login']) and strlen($c['login'])) {
		$champs['login'] = $c['login'];
	}
	if (isset($c['pass']) and strlen($c['pass'])) {
		$champs['pass'] = $c['pass'];
	}

	$statut = $statut_ancien = sql_getfetsel('statut', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));

	if (
		isset($c['statut'])
		and (autoriser('modifier', 'auteur', $id_auteur, null, ['statut' => $c['statut']]))
	) {
		$statut = $champs['statut'] = $c['statut'];
	}

	// Restreindre avant de declarer l'auteur
	// (section critique sur les droits)
	if (isset($c['id_parent']) and $c['id_parent']) {
		if (is_array($c['restreintes'])) {
			$c['restreintes'][] = $c['id_parent'];
		} else {
			$c['restreintes'] = [$c['id_parent']];
		}
	}

	if (
		isset($c['webmestre'])
		and ($force_webmestre or autoriser('modifier', 'auteur', $id_auteur, null, ['webmestre' => '?']))
	) {
		$champs['webmestre'] = $c['webmestre'] == 'oui' ? 'oui' : 'non';
	}

	// si statut change et n'est pas 0minirezo, on force webmestre a non
	if (isset($c['statut']) and $c['statut'] !== '0minirezo') {
		$champs['webmestre'] = $c['webmestre'] = 'non';
	}

	// Envoyer aux plugins
	$champs = pipeline(
		'pre_edition',
		[
			'args' => [
				'table' => 'spip_auteurs',
				'id_objet' => $id_auteur,
				'action' => 'instituer',
				'statut_ancien' => $statut_ancien,
			],
			'data' => $champs
		]
	);

	if (
		isset($c['restreintes']) and is_array($c['restreintes'])
		and autoriser('modifier', 'auteur', $id_auteur, null, ['restreint' => $c['restreintes']])
	) {
		$rubriques = array_map('intval', $c['restreintes']);
		$rubriques = array_unique($rubriques);
		$rubriques = array_diff($rubriques, [0]);
		auteur_dissocier($id_auteur, ['rubrique' => '*']);
		auteur_associer($id_auteur, ['rubrique' => $rubriques]);
	}

	$flag_ecrire_acces = false;
	// commencer par traiter les cas particuliers des logins et pass
	// avant les autres ecritures en base
	if (isset($champs['login']) or isset($champs['pass'])) {
		$auth_methode = sql_getfetsel('source', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));
		include_spip('inc/auth');
		if (isset($champs['login']) and strlen($champs['login'])) {
			if (!auth_modifier_login($auth_methode, $champs['login'], $id_auteur)) {
				$erreurs[] = 'ecrire:impossible_modifier_login_auteur';
			}
		}
		if (isset($champs['pass']) and strlen($champs['pass'])) {
			$champs['login'] = sql_getfetsel('login', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));
			if (!auth_modifier_pass($auth_methode, $champs['login'], $champs['pass'], $id_auteur)) {
				$erreurs[] = 'ecrire:impossible_modifier_pass_auteur';
			}
		}
		unset($champs['login']);
		unset($champs['pass']);
		$flag_ecrire_acces = true;
	}

	if (!(is_countable($champs) ? count($champs) : 0)) {
		return implode(' ', array_map('_T', $erreurs));
	}
	sql_updateq('spip_auteurs', $champs, 'id_auteur=' . $id_auteur);

	// .. mettre a jour les fichiers .htpasswd et .htpasswd-admin
	if (
		$flag_ecrire_acces
		or isset($champs['statut'])
	) {
		include_spip('inc/acces');
		ecrire_acces();
	}

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='auteur/$id_auteur'");

	// Pipeline
	pipeline(
		'post_edition',
		[
			'args' => [
				'table' => 'spip_auteurs',
				'id_objet' => $id_auteur,
				'action' => 'instituer',
				'statut_ancien' => $statut_ancien,
			],
			'data' => $champs
		]
	);

	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications(
			"auteur_instituer",
			$id_auteur,
			array(
				'statut' => $statut,
				'statut_ancien' => $statut_ancien,
				'champs' => $champs,
			)
		);
		
		// Rétro-compat
		$notifications(
			'instituerauteur',
			$id_auteur,
			['statut' => $statut, 'statut_ancien' => $statut_ancien]
		);
	}

	return implode(' ', array_map('_T', $erreurs));
}
