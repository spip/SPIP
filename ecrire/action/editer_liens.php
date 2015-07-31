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


/**
 * API
 */

/**
 * Associer un ou des objets a des objets listes
 * $objets_source et $objets_lies sont de la forme
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
 *
 * Les objets sources sont les pivots qui portent les liens
 * et pour lesquels une table spip_xxx_liens existe
 * (auteurs, documents, mots)
 *
 * on peut passer optionnellement une qualification du (des) lien(s) qui sera
 * alors appliquee dans la foulee.
 * En cas de lot de liens, c'est la meme qualification qui est appliquee a tous
 *
 * @param array $objets_source
 * @param array $objets_lies
 * @param array $qualif
 * @return string
 */
function objet_associer($objets_source, $objets_lies, $qualif = null){
	objet_traiter_laisons('lien_insert', $objets_source, $objets_lies);

	if ($qualif)
		objet_qualifier($objets_source, $objets, $qualif);

	return ''; // pas d'erreur
}


/**
 * Dissocier un (ou des) objet(s)  des objets listes
 * $objets_source et $objets sont de la forme
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
 *
 * Les objets sources sont les pivots qui portent les liens
 * et pour lesquels une table spip_xxx_liens existe
 * (auteurs, documents, mots)
 *
 * un * pour $objet,$id_objet permet de traiter par lot
 * seul le type de l'objet source ne peut pas accepter de joker et doit etre explicite
 *
 *
 * @param array $objets_source
 * @param array $objets_lies
 * @return string
 */
function objet_dissocier($objets_source,$objets_lies){
	objet_traiter_laisons('lien_delete',$objets_source,$objets_lies);

	return ''; // pas d'erreur
}



/**
 * Qualifier le lien entre un (ou des) objet(s) et des objets listes
 * $objets_source et $objets sont de la forme
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
 * 
 * Les objets sources sont les pivots qui portent les liens
 * et pour lesquels une table spip_xxx_liens existe
 * (auteurs, documents, mots)
 *
 * un * pour $objet,$id_objet permet de traiter par lot
 * seul le type de l'objet source ne peut pas accepter de joker et doit etre explicite
 *
 * @param array $objets_source
 * @param array $objets_lies
 * @param array $qualif
 */
function objet_qualifier($objets_source,$objets_lies,$qualif){
	objet_traiter_laisons('lien_set',$objets_source,$objets_lies,$qualif);
}







/**
 * Fonctions techniques
 * ne pas les appeler directement
 */


/**
 * Fonction generique
 * appliquer une operation de liaison entre un ou des objets et des objets listes
 * $objets_source et $objets_lies sont de la forme
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
 *
 * Les objets sources sont les pivots qui portent les liens
 * et pour lesquels une table spip_xxx_liens existe
 * (auteurs, documents, mots)
 *
 * on peut passer optionnellement une qualification du (des) lien(s) qui sera
 * alors appliquee dans la foulee.
 * En cas de lot de liens, c'est la meme qualification qui est appliquee a tous
 *
 * @param array $objets_source
 * @param array $objets_lies
 * @param array $qualif
 * @return string
 */
function objet_traiter_laisons($operation,$objets_source,$objets_lies, $set = null){
	// accepter une syntaxe minimale pour supprimer tous les liens
	if ($objets_lies=='*') $objets_lies = array('*'=>'*');
	
	$trouver_table = charger_fonction('trouver_table','base');
	foreach($objets_source as $objet=>$ids){
		$table = table_objet_sql($objet);
		$primary = id_table_objet($objet);
		if ($primary AND $trouver_table($l = $table."_liens")){
			if (!is_array($ids)) $ids = array($ids);
			foreach($ids as $id) {
				$res = $operation($objet,$primary,$l,$id,$objets_lies,$set);
				if ($res===false)
					spip_log("objet_traiter_laisons [Echec] : $operation sur $objet/$primary/$l/$id");
			}
		}
	}

	return ''; // pas d'erreur
}


/**
 * Sous fonction insertion
 * qui traite les liens pour un objet source dont la cle primaire
 * et la table de lien sont fournies
 * 
 * $objets et de la forme
 * array($objet=>$id_objets,...)
 *
 * Retourne le nombre d'insertions realisees
 *
 * @param string $objet_source
 * @param string $primary
 * @param sgring $table_lien
 * @param int $id
 * @param array $objets
 * @return int
 */
function lien_insert($objet_source,$primary,$table_lien,$id,$objets) {
	if (preg_match(',[^\w],',$primary) OR preg_match(',[^\w],',$table_lien))
		return false;
	$ins = 0;
	foreach($objets as $objet => $id_objets){
		if (!is_array($id_objets)) $id_objets = array($id_objets);
		foreach($id_objets as $id_objet) {
			if ($id_objet=intval($id_objet)
				AND !sql_getfetsel(
								$primary,
								$table_lien,
								array('id_objet='.intval($id_objet), 'objet='.sql_quote($objet), $primary.'='.intval($id))))
			{
					if (sql_insertq($table_lien, array('id_objet' => $id_objet, 'objet'=>$objet, $primary=>$id))!==false);
						$ins++;
			}
		}
	}
	return $ins;
}

function lien_where($objet_source, $id_source, $objet, $id_objet){
	if (!strlen($id_source) 
	  OR !strlen($objet)
	  OR (!is_array($id_objet) AND !strlen($id_objet)))
		return "0=1"; // securite

	$where = array();
	if ($id_source!=='*')
		$where[] = id_table_objet($objet_source) . "=" . intval($id_source);
	if ($objet!=='*')
		$where[] = "objet=".sql_quote($objet);
	if ($id_objet!=='*')
		$where[] = (is_array($id_objet)?sql_in('id_objet',array_map('intval',$id_objet)):"id_objet=".intval($id_objet));

	return $where;
}

/**
 * Sous fonction suppression
 * qui traite les liens pour un objet source dont la cle primaire
 * et la table de lien sont fournies
 *
 * $objets et de la forme
 * array($objet=>$id_objets,...)
 * un * pour $id,$objet,$id_objets permet de traiter par lot
 *
 * @param string $objet_source
 * @param string $primary
 * @param sgring $table_lien
 * @param int $id
 * @param array $objets
 * @return int
 */
function lien_delete($objet_source,$primary,$table_lien,$id,$objets){
	if (preg_match(',[^\w],',$primary) OR preg_match(',[^\w],',$table_lien))
		return false;
	$retire = array();
	foreach($objets as $objet => $id_objets){
		if (!is_array($id_objets)) $id_objets = array($id_objets);
		foreach($id_objets as $id_objet) {
			$where = lien_where($objet_source, $id, $objet, $id_objet);
			sql_delete($table_lien, $where);
			$retire[] = array('source'=>array($objet_source=>$id),'lien'=>array($objet=>$id_objet),'type'=>$objet,'id'=>$id_objet);
		}
	}
	pipeline('trig_supprimer_objets_lies',$retire);

	return ''; // pas d'erreur
}

/**
 * Sous fonction qualification
 * qui traite les liens pour un objet source dont la cle primaire
 * et la table de lien sont fournies
 *
 * $objets et de la forme
 * array($objet=>$id_objets,...)
 * un * pour $id,$objet,$id_objets permet de traiter par lot
 * 
 * exemple :
 * $qualif = array('vu'=>'oui');
 *
 * @param string $objet_source
 * @param string $primary
 * @param sgring $table_lien
 * @param int $id
 * @param array $objets
 * @param array $qualif
 */
function lien_set($objet_source,$primary,$table_lien,$id,$objets,$qualif){
	if (preg_match(',[^\w],',$primary) OR preg_match(',[^\w],',$table_lien))
		return false;
	foreach($objets as $objet => $id_objets){
		if (!is_array($id_objets)) $id_objets = array($id_objets);
		foreach($id_objets as $id_objet) {
			$where = lien_where($objet_source, $id, $objet, $id_objet);
			if ($c)
				sql_updateq($table_lien,$qualif,$where);
		}
	}
}


?>
