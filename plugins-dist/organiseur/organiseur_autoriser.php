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

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Fonction vide pour charger ce fichier sans declencher de warning
 * @return void
 */
function organiseur_autoriser(){}

function autoriser_calendrier_menu_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	if($GLOBALS['meta']['messagerie_agenda'] == 'oui')
		return true;
	return false;
}

function autoriser_messagerie_menu_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	if($GLOBALS['meta']['messagerie_agenda'] == 'oui')
		return true;
	return false;
}

function autoriser_message_modifier_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	if (!intval($qui['id_auteur']))
		return false;
	$row = Sql::fetsel('statut,type,id_auteur','spip_messages','id_message='.intval($id));
	// on peut modifier ses penses betes ou ses messages brouillons
	if ($row['id_auteur']==$qui['id_auteur'] AND ($row['statut']=='prepa' OR $row['type']=='pb'))
		return true;
	// on peut modifier les annonces si on est admin
	if ($qui['statut']=='0minirezo' AND $row['type']=='affich')
		return true;
	return false;
}

function autoriser_message_instituer_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	// rediriger vers la bonne autorisation en cas de suppression
	if ($opt['statut'] == 'poub')
		return autoriser('supprimer','message',$id,$qui,$opt);
	return autoriser('modifier','message',$id,$qui,$opt);
}

function autoriser_message_supprimer_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	// on peut supprimer un message que l'on peut modifier
	if (autoriser('modifier','message',$id,$qui,$opt))
		return true;
	// mais on peut aussi supprimer un message envoye par soi
	// si tous ses dest l'on supprime aussi
	if (
		$qui['id_auteur']
		AND Sql::countsel('spip_messages',"statut='publie' AND id_auteur=".intval($qui['id_auteur'])." AND id_message=".intval($id))
		AND !Sql::countsel('spip_auteurs_liens',"objet='message' AND id_objet=".intval($id)." AND vu!='poub' AND id_auteur!=".intval($qui['id_auteur']))
		)
		return true;
	return false;
}

function autoriser_messagerecu_effacer_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	if (isset($opt['id_auteur']))
		$id_auteur = $opt['id_auteur'];
	else
		$id_auteur = $qui['id_auteur'];
	// seul le destinataire peut supprimer un message qui lui est destine
	if (!intval($id_auteur) OR intval($id_auteur) != intval($qui['id_auteur']))
		return false;
	// rien d'autre a verifier?...
	return true;
}

function autoriser_message_dater_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	return false;
}

function autoriser_envoyermessage_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	if (!($GLOBALS['meta']['messagerie_agenda'] == 'oui') OR !intval($qui['id_auteur']))
		return false;
	// on peut envoyer une annonce si on est admin
	if (!($qui['statut']=='0minirezo') AND $type=='affich')
		return false;
	return true;
}

function autoriser_message_voir_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	if (!intval($qui['id_auteur']))
		return false;
	// message annonce ou message dont $qui est l'auteur : droit de le voir
	if (Sql::countsel('spip_messages','id_message='.intval($id).' AND (type=\'affich\' OR id_auteur='.intval($qui['id_auteur']).')'))
		return true;
	// message dont $qui est destinataire
	if (Sql::countsel('spip_auteurs_liens','objet=\'message\' AND id_objet='.intval($id)." AND vu!='poub' AND id_auteur=".intval($qui['id_auteur'])))
		return true;

	return false;
}

