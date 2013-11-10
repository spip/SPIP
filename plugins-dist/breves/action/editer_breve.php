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

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_editer_breve_dist
function action_editer_breve_dist($arg=null) {

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// Envoi depuis le formulaire d'edition d'une breve
	if (!$id_breve = intval($arg)) {
		$id_breve = breve_inserer(_request('id_parent'));
	}

	if (!$id_breve)
		return array(0,''); // erreur

	$err = breve_modifier($id_breve);

	return array($id_breve,$err);
}

/**
 * Inserer une breve en base
 * http://doc.spip.org/@insert_breve
 *
 * @param int $id_rubrique
 * @return int
 */
function breve_inserer($id_rubrique) {

	include_spip('inc/rubriques');

	// Si id_rubrique vaut 0 ou n'est pas definie, creer la breve
	// dans la premiere rubrique racine
	if (!$id_rubrique = intval($id_rubrique)) {
		$id_rubrique = Sql::getfetsel("id_rubrique", "spip_rubriques", "id_parent=0",'', '0+titre,titre', "1");
	}

	// La langue a la creation : c'est la langue de la rubrique
	$row = Sql::fetsel("lang, id_secteur", "spip_rubriques", "id_rubrique=$id_rubrique");
	$lang = $row['lang'];
	$id_rubrique = $row['id_secteur']; // garantir la racine

	$champs = array(
		'id_rubrique' => $id_rubrique,
		'statut' => 'prop',
		'date_heure' => date('Y-m-d H:i:s'),
		'lang' => $lang,
		'langue_choisie' => 'non');
	
	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_breves',
			),
			'data' => $champs
		)
	);
	$id_breve = Sql::insertq("spip_breves", $champs);
	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => 'spip_breves',
				'id_objet' => $id_breve
			),
			'data' => $champs
		)
	);
	return $id_breve;
}


/**
 * Modifier une breve en base
 * $c est un contenu (par defaut on prend le contenu via _request())
 *
 * http://doc.spip.org/@revisions_breves
 *
 * @param int $id_breve
 * @param array $set
 * @return string|bool
 */
function breve_modifier ($id_breve, $set=null) {

	include_spip('inc/modifier');
	$c = collecter_requests(
		// white list
		array('titre', 'texte', 'lien_titre', 'lien_url'),
		// black list
		array('id_parent', 'statut'),
		// donnees eventuellement fournies
		$set
	);

	// Si la breve est publiee, invalider les caches et demander sa reindexation
	$t = Sql::getfetsel("statut", "spip_breves", "id_breve=$id_breve");
	if ($t == 'publie') {
		$invalideur = "id='breve/$id_breve'";
		$indexation = true;
	}

	if ($err = objet_modifier_champs('breve', $id_breve,
		array(
			'nonvide' => array('titre' => _T('breves:titre_nouvelle_breve')." "._T('info_numero_abbreviation').$id_breve),
			'invalideur' => $invalideur,
			'indexation' => $indexation
		),
		$c))
		return $err;

	$c = collecter_requests(array('id_parent', 'statut'),array(),$set);
	$err = breve_instituer($id_breve, $c);
	return $err;
}

/**
 * Instituer une breve : modifier son statut ou son parent
 *
 * @param int $id_breve
 * @param array $c
 * @return string
 */
function breve_instituer($id_breve, $c) {
	$champs = array();

	// Changer le statut de la breve ?
	$row = Sql::fetsel("statut, id_rubrique,lang, langue_choisie", "spip_breves", "id_breve=".intval($id_breve));
	$id_rubrique = $row['id_rubrique'];

	$statut_ancien = $statut = $row['statut'];
	$langue_old = $row['lang'];
	$langue_choisie_old = $row['langue_choisie'];

	if ($c['statut']
	AND $c['statut'] != $statut
	AND autoriser('publierdans', 'rubrique', $id_rubrique)) {
		$statut = $champs['statut'] = $c['statut'];
	}

	// Changer de rubrique ?
	// Verifier que la rubrique demandee est a la racine et differente
	// de la rubrique actuelle
	if ($id_parent = intval($c['id_parent'])
	AND $id_parent != $id_rubrique
	AND (NULL !== ($lang=SQl::getfetsel('lang', 'spip_rubriques', 'id_parent=0 AND id_rubrique='.intval($id_parent)))))
	{
		$champs['id_rubrique'] = $id_parent;
		// - changer sa langue (si heritee)
		if ($langue_choisie_old != "oui") {
			if ($lang != $langue_old)
				$champs['lang'] = $lang;
		}
		// si la breve est publiee
		// et que le demandeur n'est pas admin de la rubrique
		// repasser la breve en statut 'prop'.
		if ($statut == 'publie') {
			if (!autoriser('publierdans','rubrique',$id_parent))
				$champs['statut'] = $statut = 'prop';
		}
	}

	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_breves',
				'id_objet' => $id_breve,
				'action'=>'instituer',
				'statut_ancien' => $statut_ancien,
			),
			'data' => $champs
		)
	);

	if (!$champs) return;

	Sql::updateq('spip_breves', $champs, "id_breve=".intval($id_breve));

	//
	// Post-modifications
	//

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='breve/$id_breve'");

	// Au besoin, changer le statut des rubriques concernees 
	include_spip('inc/rubriques');
	calculer_rubriques_if($id_rubrique, $champs, $statut_ancien);

	// Pipeline
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_breves',
				'id_objet' => $id_breve,
				'action'=>'instituer',
				'statut_ancien' => $statut_ancien,
			),
			'data' => $champs
		)
	);


	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications('instituerbreve', $id_breve,
			array('statut' => $statut, 'statut_ancien' => $statut_ancien)
		);
	}

	return ''; // pas d'erreur
}

function insert_breve($id_rubrique) {
	return breve_inserer($id_rubrique);
}
function revisions_breves ($id_breve, $set=false) {
	return breve_modifier($id_breve,$set);
}
?>