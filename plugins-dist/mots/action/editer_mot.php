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

include_spip('inc/filtres');

// Editer (modification) d'un mot-cle
// http://doc.spip.org/@action_editer_mot_dist
function action_editer_mot_dist($arg=null)
{
	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	$id_mot = intval($arg);

	$id_groupe = intval(_request('id_groupe'));
	if (!$id_mot AND $id_groupe) {
		$id_mot = mot_inserer($id_groupe);
	}

	// Enregistre l'envoi dans la BD
	if ($id_mot > 0) $err = mot_modifier($id_mot);
	
	return array($id_mot,$err);
}

/**
 * Insertion d'un mot dans un groupe
 * @param int $id_groupe
 * @return int
 */
function mot_inserer($id_groupe) {

	$champs = array();
	$row = Sql::fetsel("titre", "spip_groupes_mots", "id_groupe=".intval($id_groupe));
	if ($row) {
		$champs['id_groupe'] = $id_groupe;
		$champs['type'] = $row['titre'];
	}
	else
		return false;


	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_mots',
			),
			'data' => $champs
		)
	);

	$id_mot = Sql::insertq("spip_mots", $champs);

	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => 'spip_mots',
				'id_objet' => $id_mot
			),
			'data' => $champs
		)
	);

	return $id_mot;
}

/**
 * Modifier un mot
 * @param int $id_mot
 * @param array $set
 * @return string
 */
function mot_modifier($id_mot, $set=null) {
	include_spip('inc/modifier');
	$c = collecter_requests(
		// white list
		array(
		 'titre', 'descriptif', 'texte', 'id_groupe'
		),
		// black list
		array('id_groupe'),
		// donnees eventuellement fournies
		$set
	);
	
	if ($err = objet_modifier_champs('mot', $id_mot,
		array(
			'nonvide' => array('titre' => _T('info_sans_titre'))
		),
		$c))
		return $err;

	$c = collecter_requests(array('id_groupe', 'type'),array(),$set);
	$err = mot_instituer($id_mot, $c);
	return $err;
}

/**
 * Modifier le groupe parent d'un mot
 * @param  $id_mot
 * @param  $c
 * @return void
 */
function mot_instituer($id_mot, $c){
	$champs = array();
	// regler le groupe
	if (isset($c['id_groupe']) OR isset($c['type'])) {
		$row = Sql::fetsel("titre", "spip_groupes_mots", "id_groupe=".intval($c['id_groupe']));
		if ($row) {
			$champs['id_groupe'] = $c['id_groupe'];
			$champs['type'] = $row['titre'];
		}
	}

	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_mots',
				'id_objet' => $id_mot,
				'action'=>'instituer',
			),
			'data' => $champs
		)
	);

	if (!$champs) return;

	Sql::updateq('spip_mots', $champs, "id_mot=".intval($id_mot));

	//
	// Post-modifications
	//

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='mot/$id_mot'");

	// Pipeline
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_mots',
				'id_objet' => $id_mot,
				'action'=>'instituer',
			),
			'data' => $champs
		)
	);

	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications('instituermot', $id_mot,
			array('id_groupe' => $champs['id_groupe'])
		);
	}

	return ''; // pas d'erreur
}

/**
 * Supprimer un mot
 * @param int $id_mot
 * @return void
 */
function mot_supprimer($id_mot) {
	Sql::delete("spip_mots", "id_mot=".intval($id_mot));
	mot_dissocier($id_mot, '*');
	pipeline('trig_supprimer_objets_lies',
		array(
			array('type'=>'mot','id'=>$id_mot)
		)
	);
}



/**
 * Associer un mot a des objets listes sous forme
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
 *
 * on peut passer optionnellement une qualification du (des) lien(s) qui sera
 * alors appliquee dans la foulee.
 * En cas de lot de liens, c'est la meme qualification qui est appliquee a tous
 *
 * Exemples:
 * mot_associer(3, array('auteur'=>2));
 * mot_associer(3, array('auteur'=>2), array('vu'=>'oui)); // ne fonctionnera pas ici car pas de champ 'vu' sur spip_mots_liens
 * 
 * @param int $id_mot
 * @param array $objets
 * @param array $qualif
 * @return string
 */
function mot_associer($id_mot,$objets, $qualif = null){

	include_spip('action/editer_liens');

	// si il s'agit d'un groupe avec 'unseul', alors supprimer d'abord les autres
	// mots de ce groupe associe a ces objets
	$id_groupe = Sql::getfetsel('id_groupe','spip_mots','id_mot='.intval($id_mot));
	if (un_seul_mot_dans_groupe($id_groupe)) {
		$mots_groupe = Sql::allfetsel("id_mot", "spip_mots", "id_groupe=".intval($id_groupe));
		$mots_groupe = array_map('reset',$mots_groupe);
		objet_dissocier(array('mot'=>$mots_groupe), $objets);
	}

	return objet_associer(array('mot'=>$id_mot), $objets, $qualif);
}



/**
 * Dossocier un mot des objets listes sous forme
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
 *
 * un * pour $id_mot,$objet,$id_objet permet de traiter par lot
 *
 * @param int $id_mot
 * @param array $objets
 * @return string
 */
function mot_dissocier($id_mot,$objets){
	include_spip('action/editer_liens');
	return objet_dissocier(array('mot'=>$id_mot), $objets);
}

/**
 * Qualifier le lien d'un mot avec les objets listes
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
 * exemple :
 * $c = array('vu'=>'oui');
 * un * pour $id_auteur,$objet,$id_objet permet de traiter par lot
 *
 * @param int $id_mot
 * @param array $objets
 * @param array $qualif
 */
function mot_qualifier($id_mot,$objets,$qualif){
	include_spip('action/editer_liens');
	return objet_qualifier(array('mot'=>$id_mot), $objets, $qualif);
}



/**
 * Renvoyer true si le groupe de mot ne doit etre associe qu'une fois aux objet
 * (maximum un seul mot de ce groupe associe a chaque objet)
 *
 * @param int $id_groupe
 * @return bool
 */
function un_seul_mot_dans_groupe($id_groupe)
{
	return Sql::countsel('spip_groupes_mots', "id_groupe=$id_groupe AND unseul='oui'");
}



function insert_mot($id_groupe) {
	return mot_inserer($id_groupe);
}
function mots_set($id_mot, $set=null) {
	return mot_modifier($id_mot, $set);
}
function revision_mot($id_mot, $c=false) {
	return mot_modifier($id_mot, $c);
}

?>
