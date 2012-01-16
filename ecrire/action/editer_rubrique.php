<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/rubriques');

// http://doc.spip.org/@action_editer_rubrique_dist
function action_editer_rubrique_dist($arg=null) {

	if (is_null($arg)){
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
			'id_rubrique', $id_rubrique, '&');
	
		include_spip('inc/headers');
		redirige_par_entete($redirect);
	}

	return array($id_rubrique,$err);
}


/**
 * Inserer une rubrique en base
 * http://doc.spip.org/@insert_rubrique
 *
 * @param int $id_parent
 * @return int
 */
function rubrique_inserer($id_parent) {
	$champs = array(
		'titre' => _T('item_nouvelle_rubrique'),
		'id_parent' => intval($id_parent),
		'statut' => 'new');
	
	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_rubriques',
			),
			'data' => $champs
		)
	);
	
	$id_rubrique = sql_insertq("spip_rubriques", $champs);
	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => 'spip_rubriques',
				'id_objet' => $id_rubrique
			),
			'data' => $champs
		)
	);
	propager_les_secteurs();
	calculer_langues_rubriques();
	return $id_rubrique;
}

/**
 * Modifier une rubrique en base
 * $set est un tableau qu'on peut proposer en lieu et place de _request()
 * http://doc.spip.org/@revisions_rubriques
 *
 * @param int $id_rubrique
 * @param array $set
 * @return string
 */
function rubrique_modifier($id_rubrique, $set=null) {
	include_spip('inc/autoriser');
	include_spip('inc/filtres');

	include_spip('inc/modifier');
	$c = collecter_requests(
		// white list
		objet_info('rubrique','champs_editables'),
		// black list
		array('id_parent', 'confirme_deplace'),
		// donnees eventuellement fournies
		$set
	);

	if ($err = objet_modifier_champs('rubrique', $id_rubrique,
		array(
			'nonvide' => array('titre' => _T('titre_nouvelle_rubrique')." "._T('info_numero_abbreviation').$id_rubrique)
		),
		$c))
		return $err;

	$c = collecter_requests(array('id_parent', 'confirme_deplace'),array(),$set);
	// Deplacer la rubrique
	if (isset($c['id_parent'])) {
		$err = rubrique_instituer($id_rubrique, $c);
	}

	// invalider les caches marques de cette rubrique
	include_spip('inc/invalideur');
	suivre_invalideur("id='rubrique/$id_rubrique'");
	// et celui de menu_rubriques 
	effacer_meta("date_calcul_rubriques");
	return $err;
}

/**
 * si c'est une rubrique-secteur contenant des breves, ne deplacer
 * que si $confirme_deplace == 'oui', et changer l'id_rubrique des
 * breves en question
 * A deporter dans les breves via un pipeline ?
 *
 * http://doc.spip.org/@editer_rubrique_breves
 *
 * @param int $id_rubrique
 * @param int $id_parent
 * @param array $c
 * @return bool
 */
function editer_rubrique_breves($id_rubrique, $id_parent, $c=array())
{
	if (!sql_countsel('spip_breves', "id_rubrique=$id_rubrique"))
		return true;

	if ($c['confirme_deplace'] != 'oui')
		return false;

	if ($id_secteur = sql_getfetsel("id_secteur",
	"spip_rubriques", "id_rubrique=$id_parent"))
		sql_updateq("spip_breves", array("id_rubrique" => $id_secteur), "id_rubrique=$id_rubrique");

	return true;
}


/**
 * Instituer une rubrique (changer son parent)
 * http://doc.spip.org/@instituer_rubrique
 * @param int $id_rubrique
 * @param array $c
 * @return string
 */
function rubrique_instituer($id_rubrique, $c) {
	// traitement de la rubrique parente
	// interdiction de deplacer vers ou a partir d'une rubrique
	// qu'on n'administre pas.

	if (NULL !== ($id_parent = $c['id_parent'])) {
		$id_parent = intval($id_parent);
		$filles = calcul_branche_in($id_rubrique);
		if (strpos(",$id_parent,", ",$filles,") !== false)
			spip_log("La rubrique $id_rubrique ne peut etre fille de sa descendante $id_parent");
		else {
			$s = sql_fetsel("id_parent, statut", "spip_rubriques", "id_rubrique=$id_rubrique");
			$old_parent = $s['id_parent'];

			if (!($id_parent != $old_parent
			AND autoriser('publierdans', 'rubrique', $id_parent)
			AND autoriser('creerrubriquedans', 'rubrique', $id_parent)
			AND autoriser('publierdans', 'rubrique', $old_parent)
			      )) {
				if ($s['statut'] != 'new') {
					spip_log("deplacement de $id_rubrique vers $id_parent refuse a " . $GLOBALS['visiteur_session']['id_auteur'] . ' '.  $GLOBALS['visiteur_session']['statut']);
				}
			}
			elseif (editer_rubrique_breves($id_rubrique, $id_parent, $c)) {
				$statut_ancien = $s['statut'];
				sql_updateq('spip_rubriques', array('id_parent' => $id_parent), "id_rubrique=$id_rubrique");


				propager_les_secteurs();

				// Deplacement d'une rubrique publiee ==> chgt general de leur statut
				if ($statut_ancien == 'publie')
					calculer_rubriques_if($old_parent, array('id_rubrique' => $id_parent), $statut_ancien);
				// Creation ou deplacement d'une rubrique non publiee
				// invalider le cache de leur menu
				elseif (!$statut_ancien || $old_parent!=$id_parent)
					effacer_meta("date_calcul_rubriques");

				calculer_langues_rubriques();
			}
		}
	}
	return ''; // pas d'erreur
}

// obsoletes
function insert_rubrique($id_parent) {
	return rubrique_inserer($id_parent);
}
function revisions_rubriques($id_rubrique, $set=null) {
	return rubrique_modifier($id_rubrique,$set);
}
function instituer_rubrique($id_rubrique, $c) {
	return rubrique_instituer($id_rubrique, $c);
}
