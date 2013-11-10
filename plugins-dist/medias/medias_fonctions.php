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


//
// Ce fichier definit les boucles standard de SPIP
//

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Afficher la puce de statut pour les documents
 *
 * @param unknown_type $id_document
 * @param unknown_type $statut
 * @param unknown_type $id_rubrique
 * @param unknown_type $type
 * @param unknown_type $ajax
 * @return unknown
 */
function medias_puce_statut_document($id_document, $statut){
	if ($statut=='publie') {
		$puce='puce-verte.gif';
	}
	else if ($statut == "prepa") {
		$puce = 'puce-blanche.gif';
	}
	else if ($statut == "poubelle") {
		$puce = 'puce-poubelle.gif';
	}
	else
		$puce = 'puce-blanche.gif';

	return http_img_pack($puce, $statut, "class='puce'");
}

//
// <BOUCLE(DOCUMENTS)>
//
// http://doc.spip.org/@boucle_DOCUMENTS_dist
function boucle_DOCUMENTS($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;

	// on ne veut pas des fichiers de taille nulle,
	// sauf s'ils sont distants (taille inconnue)
	array_unshift($boucle->where,array("'($id_table.taille > 0 OR $id_table.distant=\\'oui\\')'"));

	/**
	 * N'afficher que les modes de documents que l'on accepte
	 * Utiliser le "pipeline medias_documents_visibles" pour en ajouter
	 */
	if (!isset($boucle->modificateur['criteres']['mode'])
	AND !isset($boucle->modificateur['tout'])) {
		$modes = pipeline('medias_documents_visibles',array('image','document'));
		$f = Sql::serveur('quote', $boucle->sql_serveur, true);
		$modes = addslashes(join(',', array_map($f, array_unique($modes))));
		array_unshift($boucle->where,array("'IN'", "'$id_table.mode'", "'($modes)'"));
	}

	return calculer_boucle($id_boucle, $boucles);
}


function lien_objet($id,$type,$longueur=80,$connect=NULL){
	include_spip('inc/liens');
	$titre = traiter_raccourci_titre($id, $type, $connect);
	// lorsque l'objet n'est plus declare (plugin desactive par exemple)
	// le raccourcis n'est plus valide
	$titre = isset($titre['titre']) ? typo($titre['titre']) : '';
	if (!strlen($titre))
		$titre = _T('info_sans_titre');
	$url = generer_url_entite($id,$type);
	return "<a href='$url' class='$type'>".couper($titre,$longueur)."</a>";
}

/**
 * critere {orphelins} selectionne les documents sans liens avec un objet editorial
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DOCUMENTS_orphelins_dist($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];
	$cond = $crit->cond;
	$not = $crit->not?"":"NOT";

	$select = Sql::get_select("DISTINCT id_document","spip_documents_liens as oooo");
	$where = "'".$boucle->id_table.".id_document $not IN ($select)'";
	if ($cond){
		$_quoi = '@$Pile[0]["orphelins"]';
		$where = "($_quoi)?$where:''";
	}

	$boucle->where[]= $where;
}

/**
 * critere {portrait} qui selectionne
 * - les documents dont les dimensions sont connues
 * - les documents dont la hauteur est superieure a la largeur
 *
 * {!portrait} exclus ces documents
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DOCUMENTS_portrait_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;
	$not = ($crit->not?"NOT ":"");
	$boucle->where[] = "'$not($table.largeur>0 AND $table.hauteur > $table.largeur)'";
}

/**
 * critere {paysage} qui selectionne
 * - les documents dont les dimensions sont connues
 * - les documents dont la hauteur est inferieure a la largeur
 *
 * {!paysage} exclus ces documents
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DOCUMENTS_paysage_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;
	$not = ($crit->not?"NOT ":"");
	$boucle->where[] = "'$not($table.largeur>0 AND $table.largeur > $table.hauteur)'";
}

/**
 * critere {carre} qui selectionne
 * - les documents dont les dimensions sont connues
 * - les documents dont la hauteur est egale a la largeur
 *
 * {!carre} exclus ces documents
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DOCUMENTS_carre_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;
	$not = ($crit->not?"NOT ":"");
	$boucle->where[] = "'$not($table.largeur>0 AND $table.largeur = $table.hauteur)'";
}
?>
