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
 * Teste l'existence de la table xxx_liens et renvoie celle ci precedee
 * de la cle primaire.
 * Renvoie false si l'objet n'est pas associable.
 *
 * @param string $objet
 */
function objet_associable($objet){
	$trouver_table = charger_fonction('trouver_table','base');
	$table_sql = table_objet_sql($objet);
	
	if ($primary = id_table_objet($objet)
	  AND $trouver_table($l = $table_sql."_liens")
		AND !preg_match(',[^\w],',$primary)
		AND !preg_match(',[^\w],',$table_lien))
		return array($primary,$l);
	
	spip_log("Objet $objet non associable : ne dispose pas d'une cle primaire $primary OU d'une table liens $l");
	return false;
}

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
	$modifs = objet_traiter_laisons('lien_insert', $objets_source, $objets_lies);

	if ($qualif)
		objet_qualifier($objets_source, $objets, $qualif);

	return $modifs; // pas d'erreur
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
	return objet_traiter_laisons('lien_delete',$objets_source,$objets_lies);
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
	return objet_traiter_laisons('lien_set',$objets_source,$objets_lies,$qualif);
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
	$modifs = 0; // compter le nombre de modifications
	$echec = null;
	foreach($objets_source as $objet=>$ids){
		if ($a = objet_associable($objet)) {
			list($primary,$l) = $a;
			if (!is_array($ids)) $ids = array($ids);
			foreach($ids as $id) {
				$res = $operation($objet,$primary,$l,$id,$objets_lies,$set);
				if ($res===false) {
					spip_log("objet_traiter_laisons [Echec] : $operation sur $objet/$primary/$l/$id");
					$echec = true;
				}
				else
					$modifs+=$res;
			}
		}
		else
			$echec = true;
	}

	return ($echec?false:$modifs); // pas d'erreur
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
	$ins = 0;
	$echec = null;
	foreach($objets as $objet => $id_objets){
		if (!is_array($id_objets)) $id_objets = array($id_objets);
		foreach($id_objets as $id_objet) {
			$objet = objet_type($objet); # securite
			if ($id_objet=intval($id_objet)
				AND !sql_getfetsel(
								$primary,
								$table_lien,
								array('id_objet='.intval($id_objet), 'objet='.sql_quote($objet), $primary.'='.intval($id))))
			{
					$e = sql_insertq($table_lien, array('id_objet' => $id_objet, 'objet'=>$objet, $primary=>$id));
					if ($e!==false)
						$ins++;
					else
						$echec = true;
			}
		}
	}
	return ($echec?false:$ins);
}

function lien_where($primary, $id_source, $objet, $id_objet){
	if (!strlen($id_source) 
	  OR !strlen($objet)
	  OR (!is_array($id_objet) AND !strlen($id_objet)))
		return "0=1"; // securite

	$where = array();
	if ($id_source!=='*')
		$where[] = addslashes($primary) . "=" . intval($id_source);
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
	$retire = array();
	$dels = 0;
	$echec = false;
	foreach($objets as $objet => $id_objets){
		$objet = objet_type($objet); # securite
		if (!is_array($id_objets)) $id_objets = array($id_objets);
		foreach($id_objets as $id_objet) {
			$where = lien_where($primary, $id, $objet, $id_objet);
			$e = sql_delete($table_lien, $where);
			if ($e!==false)
				$dels+=$e;
			else
				$echec = true;
			$retire[] = array('source'=>array($objet_source=>$id),'lien'=>array($objet=>$id_objet),'type'=>$objet,'id'=>$id_objet);
		}
	}
	pipeline('trig_supprimer_objets_lies',$retire);

	return ($echec?false:$dels);
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
	$echec = null;
	if (!$qualif)
		return false;
	foreach($objets as $objet => $id_objets){
		$objet = objet_type($objet); # securite
		if (!is_array($id_objets)) $id_objets = array($id_objets);
		foreach($id_objets as $id_objet) {
			$where = lien_where($primary, $id, $objet, $id_objet);
			$e = sql_updateq($table_lien,$qualif,$where);
			if ($e===false)
				$echec = true;
		}
	}
	return ($echec?false:true);
}


?>
