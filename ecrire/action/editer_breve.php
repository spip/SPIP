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

// http://doc.spip.org/@action_editer_breve_dist
function action_editer_breve_dist($arg=null) {

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// Envoi depuis les boutons "publier/supprimer cette breve"
	if (preg_match(',^(\d+)\Wstatut\W(\w+)$,', $arg, $r)) {
		$id_breve = $r[1];
		set_request('statut', $r[2]);
		revisions_breves($id_breve);
	} 
	// Envoi depuis le formulaire d'edition pour chgt de langue
	else if (preg_match(',^(\d+)\W(\w+)$,', $arg, $r)) {
		revisions_breves_langue($id_breve=$r[1], $r[2], _request('changer_lang'));
	}
	// Envoi depuis le formulaire d'edition d'une breve existante
	else if ($id_breve = intval($arg)) {
		revisions_breves($id_breve);
	}
	// Envoi depuis le formulaire de creation d'une breve
	else if ($arg == 'oui') {
		$id_breve = insert_breve(_request('id_parent'));
		if ($id_breve) revisions_breves($id_breve);
	} 
	// Erreur
	else{
		include_spip('inc/headers');
		redirige_url_ecrire();
	}

	if (_request('redirect')) {
		$redirect = parametre_url(urldecode(_request('redirect')),
			'id_breve', $id_breve, '&');
			
		include_spip('inc/headers');
		redirige_par_entete($redirect);
	}
	else 
		return array($id_breve,'');
}

// http://doc.spip.org/@insert_breve
function insert_breve($id_rubrique) {

	include_spip('inc/rubriques');

	// Si id_rubrique vaut 0 ou n'est pas definie, creer la breve
	// dans la premiere rubrique racine
	if (!$id_rubrique = intval($id_rubrique)) {
		$id_rubrique = sql_getfetsel("id_rubrique", "spip_rubriques", "id_parent=0",'', '0+titre,titre', "1");
	}

	// La langue a la creation : c'est la langue de la rubrique
	$row = sql_fetsel("lang, id_secteur", "spip_rubriques", "id_rubrique=$id_rubrique");
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
	$id_breve = sql_insertq("spip_breves", $champs);
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


// Enregistre une revision de breve
// $c est un contenu (par defaut on prend le contenu via _request())
// http://doc.spip.org/@revisions_breves
function revisions_breves ($id_breve, $c=false) {

	// champs normaux
	if ($c === false) {
		$c = array();
		foreach (array(
			'titre', 'texte', 'lien_titre', 'lien_url',
			'id_parent', 'statut'
		) as $champ)
			if (($a = _request($champ)) !== null)
				$c[$champ] = $a;
	}

	// Si la breve est publiee, invalider les caches et demander sa reindexation
	$t = sql_getfetsel("statut", "spip_breves", "id_breve=$id_breve");
	if ($t == 'publie') {
		$invalideur = "id='id_breve/$id_breve'";
		$indexation = true;
	}

	include_spip('inc/modifier');
	modifier_contenu('breve', $id_breve,
		array(
			'nonvide' => array('titre' => _T('info_sans_titre')),
			'invalideur' => $invalideur,
			'indexation' => $indexation
		),
		$c);


	// Changer le statut de la breve ?
	$row = sql_fetsel("statut, id_rubrique,lang, langue_choisie", "spip_breves", "id_breve=$id_breve");

	$id_rubrique = $row['id_rubrique'];
	$statut_ancien = $statut = $row['statut'];
	$langue_old = $row['lang'];
	$langue_choisie_old = $row['langue_choisie'];

	if (_request('statut', $c)
	AND _request('statut', $c) != $statut
	AND autoriser('publierdans', 'rubrique', $id_rubrique)) {
		$statut = $champs['statut'] = _request('statut', $c);
	}

	// Changer de rubrique ?
	// Verifier que la rubrique demandee est a la racine et differente
	// de la rubrique actuelle
	if ($id_parent = intval(_request('id_parent', $c))
	AND $id_parent != $id_rubrique
	AND (NULL !== ($lang=sql_getfetsel('lang', 'spip_rubriques', "id_parent=0 AND id_rubrique=$id_parent")))) {
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

	if (!$champs) return;

	sql_updateq('spip_breves', $champs, "id_breve=$id_breve");

	//
	// Post-modifications
	//

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_breve/$id_breve'");

	// Au besoin, changer le statut des rubriques concernees 
	include_spip('inc/rubriques');
	calculer_rubriques_if($id_rubrique, $champs, $statut_ancien);

	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications('instituerbreve', $id_breve,
			array('statut' => $statut, 'statut_ancien' => $statut_ancien)
		);
	}

}

// http://doc.spip.org/@revisions_breves_langue
function revisions_breves_langue($id_breve, $id_rubrique, $changer_lang)
{
	if ($changer_lang == "herit") {
		$row = sql_fetsel("lang", "spip_rubriques", "id_rubrique=$id_rubrique");
		$langue_parent = $row['lang'];
		sql_updateq('spip_breves', array('lang'=>$langue_parent, 'langue_choisie'=>'non'), "id_breve=$id_breve");
	} else 	{
		sql_updateq('spip_breves', array('lang'=>$changer_lang, 'langue_choisie'=>'oui'), "id_breve=$id_breve");
		include_spip('inc/rubriques');
		$langues = calculer_langues_utilisees();
		ecrire_meta('langues_utilisees', $langues);
	}

}

?>
