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

// declarer la fonction du pipeline
function forum_autoriser(){}


function autoriser_foruminternesuivi_menu_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	if ((($GLOBALS['meta']['forum_prive'] == 'non') && ($GLOBALS['meta']['forum_prive_admin'] == 'non'))
		OR (($GLOBALS['meta']['forum_prive'] == 'non') && ($qui['statut'] == '1comite')))
		return false;
	return true;
}

function autoriser_forumreactions_menu_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	return (Sql::countsel('spip_forum') && autoriser('publierdans','rubrique',_request('id_rubrique')));
}


// Moderer le forum ?
// = modifier l'objet correspondant (si forum attache a un objet)
// = droits par defaut sinon (admin complet pour moderation complete)
// http://doc.spip.org/@autoriser_modererforum_dist
function autoriser_modererforum_dist($faire, $type, $id, $qui, $opt) {
	return $type ? autoriser('modifier', $type, $id, $qui, $opt):autoriser('moderer', 'forum', 0, $qui, $opt);
}

/**
 * Autorise a changer le statut d'un message de forum :
 * seulement sur les objets qu'on a le droit de moderer
 */
function autoriser_forum_instituer_dist($faire, $type, $id, $qui, $opt){
	if (!intval($id)) return autoriser('moderer','forum');
	$row = Sql::fetsel('objet,id_objet','spip_forum','id_forum='.intval($id));
	return $row?autoriser('modererforum',$row['objet'],$row['id_objet'],$qui,$opt):false;
}

function autoriser_forum_moderer_dist($faire, $type, $id, $qui, $opt){
	// si on fournit un id : deleguer a modererforum sur l'objet concerne
	if ($id){
		include_spip('inc/forum');
		if ($racine = racine_forum($id)
		  AND list($objet,$id_objet,) = $racine
		  AND $objet){
			return autoriser('modererforum',$objet,$id_objet);
		}
	}
	
	// sinon : admins uniquement
	return $qui['statut']=='0minirezo'; // les admins restreints peuvent moderer leurs messages
}

// Modifier un forum ?
// = jamais !
// http://doc.spip.org/@autoriser_forum_modifier_dist
function autoriser_forum_modifier_dist($faire, $type, $id, $qui, $opt) {
	return false;
}

// Consulter le forum des admins ?
// admins y compris restreints
// http://doc.spip.org/@autoriser_forum_admin_dist
function autoriser_forum_admin_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo';
}

/**
 * Auto-association de documents sur des forum : niet
 */
function autoriser_forum_autoassocierdocument_dist($faire, $type, $id, $qui, $opts) {
	return false;
}

/**
 * Autoriser a participer au forum des admins
 *
 * @return bool
 */
function autoriser_forumadmin_participer_dist($faire, $type, $id, $qui, $opts) {
	return ($GLOBALS['meta']['forum_prive_admin'] == 'oui') && $qui['statut']=='0minirezo';
}

?>
