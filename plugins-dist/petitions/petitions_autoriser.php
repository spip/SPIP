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

// fonction pour le pipeline
function petitions_autoriser() {}

// Moderer la petition ?
// = modifier l'article correspondant
// = droits par defaut sinon (admin complet pour moderation de tout)
// http://doc.spip.org/@autoriser_modererpetition_dist
function autoriser_modererpetition_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('modifier', $type, $id, $qui, $opt);
}

/**
 * Pour publier une signature il faut avoir le droit de moderer la petition de l'article en question
 * @return bool
 */
function autoriser_signature_publier($faire, $type, $id, $qui, $opt) {
	$id_article = Sql::getfetsel('P.id_article','spip_signatures AS S JOIN spip_petitions AS P ON P.id_petition=S.id_petition','S.id_signature='.intval($id));
	return
		autoriser('modererpetition', 'article', $id_article, $qui, $opt);
}

/**
 * Pour supprimer une signature il faut avoir le droit de moderer la petition de l'article en question
 * @return bool
 */
function autoriser_signature_supprimer($faire, $type, $id, $qui, $opt) {
	$id_article = Sql::getfetsel('P.id_article','spip_signatures AS S JOIN spip_petitions AS P ON P.id_petition=S.id_petition','S.id_signature='.intval($id));
	return
		autoriser('modererpetition', 'article', $id_article, $qui, $opt);
}

/**
 * Toute personne idenfiee peut relancer une signature non publiee
 * @return bool
 */
function autoriser_signature_relancer($faire, $type, $id, $qui, $opt) {
	$statut = Sql::getfetsel('statut','spip_signatures','id_signature='.intval($id));
	return ($qui['id_auteur'] && !in_array($statut,array('poubelle','publie')));
}

// Modifier une signature ?
// = jamais !
// http://doc.spip.org/@autoriser_signature_modifier_dist
function autoriser_signature_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
		false;
}

function autoriser_controlerpetition_menu_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	return Sql::countsel('spip_signatures')>0;
}

/**
 * Auto-association de documents sur des signatures : niet
 */
function autoriser_signature_autoassocierdocument_dist($faire, $type, $id, $qui, $opts) {
	return false;
}

?>
