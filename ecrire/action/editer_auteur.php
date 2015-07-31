<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_editer_auteur_dist
function action_editer_auteur_dist($arg=null) {

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}


	// si id_auteur n'est pas un nombre, c'est une creation
	if (!$id_auteur = intval($arg)) {

		if (($id_auteur = insert_auteur()) > 0){

			# cf. GROS HACK
			# recuperer l'eventuel logo charge avant la creation
			# ils ont un id = 0-id_auteur de la session
			$id_hack = 0 - $GLOBALS['visiteur_session']['id_auteur'];
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			if (list($logo) = $chercher_logo($id_hack, 'id_auteur', 'on'))
				rename($logo, str_replace($id_hack, $id_auteur, $logo));
			if (list($logo) = $chercher_logo($id_hack, 'id_auteur', 'off'))
				rename($logo, str_replace($id_hack, $id_auteur, $logo));
		}
	}

	// Enregistre l'envoi dans la BD
	if ($id_auteur > 0)
		$err = auteurs_set($id_auteur);

	if ($redirect = _request('redirect')) {
		if ($err){
			$ret = ('&redirect=' . $redirect);
			spip_log("echec editeur auteur: " . join(' ',$echec));
			$echec = '&echec=' . join('@@@', $echec);
			$redirect = generer_url_ecrire('auteur_infos',"id_auteur=$id_auteur$echec$ret",'&');
		}
		else
			$redirect = urldecode($redirect);

		$redirect = parametre_url($redirect,'id_auteur', $id_auteur, '&');

		include_spip('inc/headers');
		redirige_par_entete($redirect);
	}
	else
		return array($id_auteur,$err);

	$redirect = _request('redirect');

}

function insert_auteur($source=null) {

	// Ce qu'on va demander comme modifications
	$champs = array();
	$champs['source'] = $source?$source:'spip';

	$champs['login'] = '';
	$champs['statut'] = '5poubelle';  // inutilisable tant qu'il n'a pas ete renseigne et institue
	$champs['webmestre'] = 'non';

	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_auteurs',
			),
			'data' => $champs
		)
	);
	$id_auteur = sql_insertq("spip_auteurs", $champs);
	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => 'spip_auteurs',
				'id_objet' => $id_auteur
			),
			'data' => $champs
		)
	);
	return $id_auteur;
}


// Appelle toutes les fonctions de modification d'un auteur
function auteurs_set($id_auteur, $set = null) {
	$err = '';

	if (is_null($set)){
		$c = array();
		foreach (array(
			'nom','email','bio',
			'nom_site','url_site',
			'imessage','pgp',
		) as $champ)
			$c[$champ] = _request($champ,$set);
	}
	else{
		$c = $set;
		unset($c['webmestre']);
		unset($c['pass']);
		unset($c['login']);
	}

	include_spip('inc/modifier');
	revision_auteur($id_auteur, $c);

	// Modification de statut, changement de rubrique ?
	$c = array();
	foreach (array(
		'statut', 'new_login','new_pass','login','pass','webmestre','restreintes','id_parent'
	) as $champ)
		if (_request($champ,$set))
			$c[preg_replace(',^new_,','',$champ)] = _request($champ,$set);

	$err .= instituer_auteur($id_auteur, $c);

	// Un lien auteur a prendre en compte ?
	$err .= auteur_associer($id_auteur, array('article' => _request('lier_id_article',$set)));

	return $err;
}

/**
 * Associer un auteur a des objets listes sous forme
 * array('objet'=>$id_objet,...)
 *
 * on peut passer optionnellement une qualification du (des) lien(s) qui sera
 * alors appliquee dans la foulee.
 * En cas de lot de liens, c'est la meme qualification qui est appliquee a tous
 *
 * @param int $id_auteur
 * @param array $objets
 * @param array $qualif
 * @return string
 */
function auteur_associer($id_auteur,$objets, $qualif = null){
	foreach($objets as $objet => $id_objet){
		if ($id_objet=intval($id_objet)
			AND !sql_getfetsel(
							'id_auteur',
							"spip_auteurs_liens",
							array('id_objet='.intval($id_objet), 'objet='.sql_quote($objet), 'id_auteur='.intval($id_auteur))))
		{
				sql_insertq("spip_auteurs_liens", array('id_objet' => $id_objet, 'objet'=>$objet, 'id_auteur' =>$id_auteur));
		}
	}
	if ($qualif)
		auteur_qualifier($id_auteur, $objets, $qualif);

	return ''; // pas d'erreur
}


/**
 * Ancien nommage pour compatibilite
 * @param int $id_auteur
 * @param array $c
 * @return string
 */
function auteur_referent($id_auteur,$c){
	return auteur_associer($id_auteur,$c);
}

/**
 * Dossocier un auteur des objets listes sous forme
 * array($objet=>$id_objet,...)
 *
 * un * pour $id_auteur,$objet,$id_objet permet de traiter par lot
 *
 * @param int $id_auteur
 * @param array $objets
 * @return string
 */
function auteur_dissocier($id_auteur,$objets){
	$retire = array();
	foreach($objets as $objet => $id_objet){
		$where = array();
		if ($id_auteur AND $id_auteur!=='*')
			$where[] = "id_auteur=".intval($id_auteur);
		if ($objet AND $objet!=='*')
			$where[] = "objet=".sql_quote($objet);
		if ($id_objet AND $id_objet!=='*')
			$where[] = "id_objet=".intval($id_objet);
		sql_delete("spip_auteurs_liens", $where);
		$retire[] = array('source'=>array('auteur'=>$id_auteur),'lien'=>array($objet=>$id_objet),'type'=>$objet,'id'=>$id_objet);
	}
	pipeline('trig_supprimer_objets_lies',$retire);

	return ''; // pas d'erreur
}

/**
 * Qualifier le lien d'un auteur avec un objet
 * exemple :
 * $c = array('vu'=>'oui');
 * un * pour $id_auteur,$objet,$id_objet permet de traiter par lot
 *
 * @param int $id_auteur
 * @param array $objets
 * @param array $qualif
 */
function auteur_qualifier($id_auteur,$objets,$qualif){
	foreach($objets as $objet => $id_objet){
		$where = array();
		if ($id_auteur AND $id_auteur!=='*')
			$where[] = "id_auteur=".intval($id_auteur);
		if ($objet AND $objet!=='*')
			$where[] = "objet=".sql_quote($objet);
		if ($id_objet AND $id_objet!=='*')
			$where[] = "id_objet=".intval($id_objet);
		if ($c)
			sql_updateq("spip_auteurs_liens",$qualif,$where);
	}
}


// http://doc.spip.org/@instituer_auteur
function instituer_auteur($id_auteur, $c, $force_webmestre = false) {
	if (!$id_auteur=intval($id_auteur))
		return false;
	// commencer par traiter les cas particuliers des logins et pass
	// avant le changement de statut eventuel
	if (isset($c['login']) OR isset($c['pass'])){
		$auth_methode = sql_getfetsel('source','spip_auteurs','id_auteur='.intval($id_auteur));
		include_spip('inc/auth');
		if (isset($c['login']))
			auth_modifier_login($auth_methode, $c['login'], $id_auteur);
		if (isset($c['pass'])){
			$c['login'] = sql_getfetsel('login','spip_auteurs','id_auteur='.intval($id_auteur));
			auth_modifier_pass($auth_methode, $c['login'], $c['pass'], $id_auteur);
		}
	}

	
	$champs = array();
	$statut =	$statut_ancien = sql_getfetsel('statut','spip_auteurs','id_auteur='.intval($id_auteur));
	
	if (isset($c['statut']))
		$statut = $champs['statut'] = $c['statut'];

	// Restreindre avant de declarer l'auteur
	// (section critique sur les droits)
	if ($c['id_parent']) {
		if (is_array($c['restreintes']))
			$c['restreintes'][] = $c['id_parent'];
		else
			$c['restreintes'] = array($c['id_parent']);
	}

	if (isset($c['webmestre']) AND ($force_webmestre OR autoriser('modifier', 'auteur', $id_auteur,null, array('webmestre' => '?'))))
		$champs['webmestre'] = $c['webmestre']=='oui'?'oui':'non';
	
	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_auteurs',
				'id_objet' => $id_auteur,
				'action' => 'instituer',
			),
			'data' => $champs
		)
	);
	
	if (is_array($c['restreintes'])
	AND autoriser('modifier', 'auteur', $id_auteur, NULL, array('restreint'=>$c['restreintes']))) {
		sql_delete("spip_auteurs_liens", "objet='rubrique' AND id_auteur=".sql_quote($id_auteur));
		foreach (array_unique($c['restreintes']) as $id_rub)
			if ($id_rub = intval($id_rub)) // si '0' on ignore
				sql_insertq('spip_auteurs_liens', array('id_auteur' => $id_auteur, 'objet'=>'rubrique', 'id_objet'=>$id_rub));
	}

	if (!count($champs)) return;
	sql_updateq('spip_auteurs', $champs , 'id_auteur='.$id_auteur);
	include_spip('inc/modifier');
	sql_updateq('spip_auteurs',$champs,'id_auteur='.$id_auteur);
	
	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='auteur/$id_auteur'");
	
	// Pipeline
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_auteurs',
				'id_objet' => $id_auteur
			),
			'data' => $champs
		)
	);

	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications('instituerauteur', $id_auteur,
			array('statut' => $statut, 'statut_ancien' => $statut_ancien)
		);
	}

	return ''; // pas d'erreur

}


?>