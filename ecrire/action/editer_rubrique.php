<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
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
		$id_rubrique = insert_rubrique(_request('id_parent'));
	}

	revisions_rubriques($id_rubrique);

	if (_request('redirect')) {
		$redirect = parametre_url(
			urldecode(_request('redirect')),
			'id_rubrique', $id_rubrique, '&');
	
		include_spip('inc/headers');
		redirige_par_entete($redirect);
	}
	else 
		return array($id_rubrique,'');
}


// http://doc.spip.org/@insert_rubrique
function insert_rubrique($id_parent) {
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

// Enregistrer certaines modifications d'une rubrique
// $c est un tableau qu'on peut proposer en lieu et place de _request()
// http://doc.spip.org/@revisions_rubriques
function revisions_rubriques($id_rubrique, $c=false) {
	include_spip('inc/autoriser');
	include_spip('inc/filtres');

	// champs normaux
	if ($c === false) {
		$c = array();
		foreach (array(
			'titre', 'texte', 'descriptif', 'extra',
			'id_parent', 'confirme_deplace'
		) as $champ)
			if (($a = _request($champ)) !== null)
				$c[$champ] = $a;
	}

	include_spip('inc/modifier');
	modifier_contenu('rubrique', $id_rubrique,
		array(
			'nonvide' => array('titre' => _T('info_sans_titre'))
		),
		$c);

	// Deplacer la rubrique
	if (isset($c['id_parent'])) {
		$c['confirme_deplace'] = _request('confirme_deplace', $c);
		instituer_rubrique($id_rubrique, $c);
	}

	// invalider les caches marques de cette rubrique
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_rubrique/$id_rubrique'");
	// et celui de menu_rubriques 
	effacer_meta("date_calcul_rubriques");
}

// si c'est une rubrique-secteur contenant des breves, ne deplacer
// que si $confirme_deplace == 'oui', et changer l'id_rubrique des
// breves en question

// http://doc.spip.org/@editer_rubrique_breves
function editer_rubrique_breves($id_rubrique, $id_parent, $c=false)
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


// http://doc.spip.org/@instituer_rubrique
function instituer_rubrique($id_rubrique, $c) {
	// traitement de la rubrique parente
	// interdiction de deplacer vers ou a partir d'une rubrique
	// qu'on n'administre pas.

	$statut_ancien = $parent = '';
	if (NULL !== ($id_parent = $c['id_parent'])) {
		$id_parent = intval($id_parent);
		$filles = calcul_branche($id_rubrique);
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
			} elseif (editer_rubrique_breves($id_rubrique, $id_parent, $c)) {
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

				return true;
			}
		}
	}
}
