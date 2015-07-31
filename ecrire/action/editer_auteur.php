<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@action_editer_auteur_dist
function action_editer_auteur_dist($arg=null) {

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}


	// si id_auteur n'est pas un nombre, c'est une creation
	if (!$id_auteur = intval($arg)) {

		if (($id_auteur = auteur_inserer()) > 0){

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
	$err = "";
	if ($id_auteur > 0)
		$err = auteur_modifier($id_auteur);

	if ($err)
		spip_log("echec editeur auteur: $err",_LOG_ERREUR);

	return array($id_auteur,$err);
}

/**
 * Inserer un auteur en base
 * @param string $source
 * @return int
 */
function auteur_inserer($source=null) {

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


/**
 * Appelle toutes les fonctions de modification d'un auteur
 *
 * @param int $id_auteur
 * @param array $set
 * @param bool $force_update
 *   permet de forcer la maj en base des champs fournis, sans passer par instancier
 *   utilise par auth/spip
 * @return string
 */
function auteur_modifier($id_auteur, $set = null, $force_update=false) {

	include_spip('inc/modifier');
	include_spip('inc/filtres');
	$c = collecter_requests(
		// white list
		objet_info('auteur','champs_editables'),
		// black list
		$force_update?array():array('webmestre','pass','login'),
		// donnees eventuellement fournies
		$set
	);

	if ($err = objet_modifier_champs('auteur', $id_auteur,
		array(
			'nonvide' => array('nom' => _T('ecrire:item_nouvel_auteur'))
		),
		$c))
		return $err;
	$session = $c;

	$err = '';
	if (!$force_update){
		// Modification de statut, changement de rubrique ?
		$c = collecter_requests(
			// white list
			array(
			 'statut', 'new_login','new_pass','login','pass','webmestre','restreintes','id_parent'
			),
			// black list
			array(),
			// donnees eventuellement fournies
			$set
		);
		if (isset($c['new_login']) AND !isset($c['login']))
			$c['login'] = $c['new_login'];
		if (isset($c['new_pass']) AND !isset($c['pass']))
			$c['pass'] = $c['new_pass'];
		$err = auteur_instituer($id_auteur, $c);
		$session = array_merge($session,$c);
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
 * Associer un auteur a des objets listes sous forme
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
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
	include_spip('action/editer_liens');
	return objet_associer(array('auteur'=>$id_auteur), $objets, $qualif);
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
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
 *
 * un * pour $id_auteur,$objet,$id_objet permet de traiter par lot
 *
 * @param int $id_auteur
 * @param array $objets
 * @return string
 */
function auteur_dissocier($id_auteur,$objets){
	include_spip('action/editer_liens');
	return objet_dissocier(array('auteur'=>$id_auteur), $objets);
}

/**
 * Qualifier le lien d'un auteur avec les objets listes
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
 * exemple :
 * $c = array('vu'=>'oui');
 * un * pour $id_auteur,$objet,$id_objet permet de traiter par lot
 *
 * @param int $id_auteur
 * @param array $objets
 * @param array $qualif
 * @return bool|int
 */
function auteur_qualifier($id_auteur,$objets,$qualif){
	include_spip('action/editer_liens');
	return objet_qualifier_liens(array('auteur'=>$id_auteur), $objets, $qualif);
}


/**
 * Modifier le statut d'un auteur, ou son login/pass
 * http://doc.spip.org/@instituer_auteur
 * @param  $id_auteur
 * @param  $c
 * @param bool $force_webmestre
 * @return bool|string
 */
function auteur_instituer($id_auteur, $c, $force_webmestre = false) {
	if (!$id_auteur=intval($id_auteur))
		return false;
	$erreurs = array(); // contiendra les differentes erreurs a traduire par _T()
	$champs = array();

	// les memoriser pour les faire passer dans le pipeline pre_edition
	if (isset($c['login']) AND strlen($c['login']))
		$champs['login'] = $c['login'];
	if (isset($c['pass']) AND strlen($c['pass']))
		$champs['pass'] = $c['pass'];

	$statut =	$statut_ancien = sql_getfetsel('statut','spip_auteurs','id_auteur='.intval($id_auteur));
	
	if (isset($c['statut'])
	  AND (autoriser('modifier', 'auteur', $id_auteur,null, array('statut' => $c['statut']))))
		$statut = $champs['statut'] = $c['statut'];

	// Restreindre avant de declarer l'auteur
	// (section critique sur les droits)
	if ($c['id_parent']) {
		if (is_array($c['restreintes']))
			$c['restreintes'][] = $c['id_parent'];
		else
			$c['restreintes'] = array($c['id_parent']);
	}

	if (isset($c['webmestre'])
	  AND ($force_webmestre OR autoriser('modifier', 'auteur', $id_auteur,null, array('webmestre' => '?'))))
		$champs['webmestre'] = $c['webmestre']=='oui'?'oui':'non';
	
	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_auteurs',
				'id_objet' => $id_auteur,
				'action' => 'instituer',
				'statut_ancien' => $statut_ancien,
			),
			'data' => $champs
		)
	);
	
	if (is_array($c['restreintes'])
	AND autoriser('modifier', 'auteur', $id_auteur, NULL, array('restreint'=>$c['restreintes']))) {
		$rubriques = array_map('intval',$c['restreintes']);
		$rubriques = array_unique($rubriques);
		$rubriques = array_diff($rubriques,array(0));
		auteur_dissocier($id_auteur, array('rubrique'=>'*'));
		auteur_associer($id_auteur,array('rubrique'=>$rubriques));
	}

	$flag_ecrire_acces = false;
	// commencer par traiter les cas particuliers des logins et pass
	// avant les autres ecritures en base
	if (isset($champs['login']) OR isset($champs['pass'])){
		$auth_methode = sql_getfetsel('source','spip_auteurs','id_auteur='.intval($id_auteur));
		include_spip('inc/auth');
		if (isset($champs['login']) AND strlen($champs['login']))
			if (!auth_modifier_login($auth_methode, $champs['login'], $id_auteur))
				$erreurs[] = 'ecrire:impossible_modifier_login_auteur';
		if (isset($champs['pass']) AND strlen($champs['pass'])){
			$champs['login'] = sql_getfetsel('login','spip_auteurs','id_auteur='.intval($id_auteur));
			if (!auth_modifier_pass($auth_methode, $champs['login'], $champs['pass'], $id_auteur))
				$erreurs[] = 'ecrire:impossible_modifier_pass_auteur';
		}
		unset($champs['login']);
		unset($champs['pass']);
		$flag_ecrire_acces = true;
	}

	if (!count($champs)) return implode(' ', array_map('_T', $erreurs));
	sql_updateq('spip_auteurs', $champs , 'id_auteur='.$id_auteur);

	// .. mettre a jour les fichiers .htpasswd et .htpasswd-admin
	if ($flag_ecrire_acces
	  OR isset($champs['statut'])
	  ) {
		include_spip('inc/acces');
		ecrire_acces();
	}

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='auteur/$id_auteur'");
	
	// Pipeline
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_auteurs',
				'id_objet' => $id_auteur,
				'action' => 'instituer',
				'statut_ancien' => $statut_ancien,
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

	return implode(' ', array_map('_T', $erreurs));

}




function insert_auteur($source=null) {
	return auteur_inserer($source);
}
function auteurs_set($id_auteur, $set = null) {
	return auteur_modifier($id_auteur,$set);
}
function instituer_auteur($id_auteur, $c, $force_webmestre = false) {
	return auteur_instituer($id_auteur,$c,$force_webmestre);
}
// http://doc.spip.org/@revision_auteur
function revision_auteur($id_auteur, $c=false) {
	return auteur_modifier($id_auteur,$c);
}

?>
