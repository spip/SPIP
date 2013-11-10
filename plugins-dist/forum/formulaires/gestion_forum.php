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

function formulaires_gestion_forum_charger_dist($id_forum='', $id_rubrique='', $id_article='', $id_breve='', $id_syndic='', $id_message='', $id_auteur='', $auteur='', $email_auteur='', $ip='') {
	
	$valeurs = array(
		'editable'=>true
		);
	
	$valeurs['id_forums'] = array();
	$valeurs['pagination'] = _request('pagination');
	$valeurs['select_type'] = _request('select_type');
	$valeurs['select_statut'] = _request('select_statut');
	
	$valeurs['id_forum'] = _request('id_forum');
	$valeurs['id_rubrique'] = _request('id_rubrique');
	$valeurs['id_article'] = _request('id_article');
	$valeurs['id_breve'] = _request('id_breve');
	$valeurs['id_syndic'] = _request('id_syndic');
	$valeurs['id_message'] = _request('id_message');
	$valeurs['id_auteur'] = _request('id_auteur');
	$valeurs['auteur'] = _request('auteur');
	$valeurs['email_auteur'] = _request('email_auteur');
	$valeurs['ip'] = _request('ip');
	$valeurs['debut_forum'] = _request('debut_forum');
	
	return $valeurs;
}

function formulaires_gestion_forum_verifier_dist($id_forum='', $id_rubrique='', $id_article='', $id_breve='', $id_syndic='', $id_message='', $id_auteur='', $auteur='', $email_auteur='', $ip='') {

	$erreurs = array();
	
	return $erreurs;
}


function formulaires_gestion_forum_traiter_dist($id_forum='', $id_rubrique='', $id_article='', $id_breve='', $id_syndic='', $id_message='', $id_auteur='', $auteur='', $email_auteur='', $ip='') {

	$retour = array();
	
	$retour['message_ok'] = 'rien a faire';
	
	if (!$forum_ids = _request('forum_ids'))
		$forum_ids = array();
	
	$select_type = _request('select_type');
	$select_statut = _request('select_statut');
	$pagination = _request('pagination');
	$pagination_ancien = _request('pagination_ancien');

	set_request('select_type',$select_type);
	set_request('voir_staut',$select_statut);
	
	if ($pagination != $pagination_ancien)
		set_request('debut_forum','');
	
	if (_request('valider')){
		$statut = 'publie';
		$retour['message_ok'] = 'messages publies';
	}
	
	if (_request('bruler')){
		$statut = 'spam';
		$retour['message_ok'] = 'messages marquees comme spam';
	}
	
	if(_request('supprimer')){
		$statut = 'off';
		$retour['message_ok'] = 'messages supprimes';
	}
	
	include_spip('action/instituer_forum');
	foreach ($forum_ids as $id) {
		$row = Sql::fetsel("*", "spip_forum", "id_forum=$id");
		instituer_un_forum($statut,$row);
	}
	
	return $retour;
	
}

?>
