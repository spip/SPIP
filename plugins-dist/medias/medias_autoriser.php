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

/* Pour que le pipeline de rale pas ! */
function medias_autoriser(){}


function autoriser_mediatheque_administrer_dist($faire,$quoi,$id,$qui,$options) {
	return $qui['statut'] == '0minirezo';
}

function autoriser_documents_menu_dist($faire,$quoi,$id,$qui,$options) {
	return autoriser('administrer','mediatheque',$id,$qui,$options);
}

/**
 * Autoriser le changement des dimensions sur un document
 * @param <type> $faire
 * @param <type> $quoi
 * @param <type> $id
 * @param <type> $qui
 * @param <type> $options
 * @return <type>
 */
function autoriser_document_tailler_dist($faire,$quoi,$id,$qui,$options) {

	if (!$id_document=intval($id))
		return false;
	if (!autoriser('modifier','document',$id,$qui,$options))
		return false;
	
	if (!isset($options['document']) OR !$document = $options['document'])
		$document = Sql::fetsel('*','spip_documents','id_document='.intval($id_document));
	
	// (on ne le propose pas pour les images qu'on sait
	// lire : gif jpg png), sauf bug, ou document distant
	if (in_array($document['extension'], array('gif','jpg','png'))
		AND $document['hauteur']
		AND $document['largeur']
		AND $document['distant']!='oui')
		return false;
	
	// Donnees sur le type de document
	$extension = $document['extension'];
	$type_inclus = Sql::getfetsel('inclus','spip_types_documents', "extension=".Sql::quote($extension));

	if (($type_inclus == "embed" OR $type_inclus == "image")
	AND (
		// documents dont la taille est definie
		($document['largeur'] * $document['hauteur'])
		// ou distants
		OR $document['distant'] == 'oui'
		// ou tous les formats qui s'affichent en embed
		OR $type_inclus == "embed"
	))
		return true;
}

/**
 * On ne peut joindre un document qu'a un objet qu'on a le droit d'editer
 * mais il faut prevoir le cas d'une *creation* par un redacteur, qui correspond
 * au hack id_objet = 0-id_auteur
 * Il faut aussi que les documents aient ete actives sur les objets concernes
 * ou que ce soit un article, sur lequel on peut toujours uploader des images
 *
 * http://doc.spip.org/@autoriser_joindredocument_dist
 *
 * @return bool
 */
function autoriser_joindredocument_dist($faire, $type, $id, $qui, $opt){
	include_spip('inc/config');
	return
		(
			$type=='article'
			OR in_array(table_objet_sql($type),explode(',',lire_config('documents_objets', '')))
		)
		AND (
		  (
			  $id>0
		    AND autoriser('modifier', $type, $id, $qui, $opt)
		  )
			OR (
				$id<0
				AND abs($id) == $qui['id_auteur']
				AND autoriser('ecrire', $type, $id, $qui, $opt)
			)
		);
}


/**
 * On ne peut modifier un document que s'il n'est pas lie a un objet qu'on n'a pas le droit d'editer
 *
 * @staticvar <type> $m
 * @param <type> $faire
 * @param <type> $type
 * @param <type> $id
 * @param <type> $qui
 * @param <type> $opt
 * @return <type>
 */
function autoriser_document_modifier_dist($faire, $type, $id, $qui, $opt){
	static $m = array();

	$q=$qui['id_auteur'];
	if (isset($m[$q][$id]))
		return $m[$q][$id];
	
	$s = Sql::getfetsel("statut", "spip_documents", "id_document=".intval($id));
	// les admins ont le droit de modifier tous les documents existants
	if ($qui['statut'] == '0minirezo'
	AND !$qui['restreint'])
		return is_string($s)?true:false;

	if (!isset($m[$q][$id])) {
		// un document non publie peut etre modifie par tout le monde (... ?)
		if ($s AND $s!=='publie' AND ($qui['id_auteur'] > 0))
			$m[$q][$id] = true;
	}

	if (!isset($m[$q][$id])) {
		$interdit = false;

		$s = Sql::select("id_objet,objet", "spip_documents_liens", "id_document=".intval($id));
		while ($t = Sql::fetch($s)) {
			if (!autoriser('modifier', $t['objet'], $t['id_objet'], $qui, $opt)) {
				$interdit = true;
				break;
			}
		}

		$m[$q][$id] = ($interdit?false:true);
	}

	return $m[$q][$id];
}


/**
 * On ne peut supprimer un document que s'il n'est lie a aucun objet
 * ET qu'on a le droit de le modifier !
 *
 * @param <type> $faire
 * @param <type> $type
 * @param <type> $id
 * @param <type> $qui
 * @param <type> $opt
 * @return <type>
 */
function autoriser_document_supprimer_dist($faire, $type, $id, $qui, $opt){
	if (!intval($id)
		OR !$qui['id_auteur']
		OR !autoriser('ecrire','','',$qui))
		return false;

	// ne pas considerer les document parent
	// (cas des vignettes ou autre document annexe rattache a un document)
	if (Sql::countsel('spip_documents_liens', "objet!='document' AND id_document=".intval($id)))
		return false;

	// si c'est une vignette, se ramener a l'autorisation de son parent
	if (Sql::getfetsel('mode','spip_documents','id_document='.intval($id))=='vignette'){
		$id_document = Sql::getfetsel('id_document','spip_documents','id_vignette='.intval($id));
		return !$id_document OR autoriser('modifier','document',$id_document);
	}
	// si c'est un document annexe, se ramener a l'autorisation de son parent
	if ($id_document=Sql::getfetsel('id_objet','spip_documents_liens',"objet='document' AND id_document=".intval($id))){
		return autoriser('modifier','document',$id_document);
	}

	return autoriser('modifier','document',$id,$qui,$opt);
}


//
// Peut-on voir un document dans _DIR_IMG ?
// Tout le monde (y compris les visiteurs non enregistres), puisque par
// defaut ce repertoire n'est pas protege ; si une extension comme
// acces_restreint a positionne creer_htaccess, on regarde
// si le document est lie a un element publie
// (TODO: a revoir car c'est dommage de sortir de l'API true/false)
//
// http://doc.spip.org/@autoriser_document_voir_dist
function autoriser_document_voir_dist($faire, $type, $id, $qui, $opt) {

	if (!isset($GLOBALS['meta']["creer_htaccess"])
	OR $GLOBALS['meta']["creer_htaccess"] != 'oui')
		return true;

	if ((!is_numeric($id)) OR $id < 0) return false;

	if (in_array($qui['statut'], array('0minirezo', '1comite')))
		return 'htaccess';

	if ($liens = Sql::allfetsel('objet,id_objet', 'spip_documents_liens', 'id_document='.intval($id)))
	foreach ($liens as $l) {
		$table_sql = table_objet_sql($l['objet']);
		$id_table = id_table_objet($l['objet']);
		if (SQl::countsel($table_sql, "$id_table = ". intval($l['id_objet'])
		. (in_array($l['objet'], array('article', 'rubrique', 'breve'))
			? " AND statut = 'publie'"
			: '')
		) > 0)
			return 'htaccess';
	}
	return false;
}


/**
 * Auto-association de documents a du contenu editorial qui le reference
 * par defaut true pour tous les objets
 */
function autoriser_autoassocierdocument_dist($faire, $type, $id, $qui, $opts) {
	return true;
}

/**
 * Autoriser a nettoyer les orphelins de la base des documents
 * reserve aux admins complets
 *
 * @param  $faire
 * @param  $type
 * @param  $id
 * @param  $qui
 * @param  $opt
 * @return bool
 */
function autoriser_orphelins_supprimer_dist($faire, $type, $id, $qui, $opt){
	if ($qui['statut'] == '0minirezo'
	AND !$qui['restreint'])
		return true;
}