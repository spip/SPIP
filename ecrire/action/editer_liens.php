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

/**
 * API d'édition de liens
 *
 * @package SPIP\Liens\API
 */
 
if (!defined('_ECRIRE_INC_VERSION')) return;




/**
 * Teste l'existence de la table de liaison xxx_liens d'un objet
 *
 * @api
 * @param string $objet
 *     Objet à tester
 * @return array|bool
 *     - false si l'objet n'est pas associable.
 *     - array(clé primaire, nom de la table de lien) si associable
 */
function objet_associable($objet){
	$trouver_table = charger_fonction('trouver_table','base');
	$table_sql = table_objet_sql($objet);

	$l="";
	if ($primary = id_table_objet($objet)
	  AND $trouver_table($l = $table_sql."_liens")
		AND !preg_match(',[^\w],',$primary)
		AND !preg_match(',[^\w],',$l))
		return array($primary,$l);

	spip_log("Objet $objet non associable : ne dispose pas d'une cle primaire $primary OU d'une table liens $l");
	return false;
}

/**
 * Associer un ou des objets à des objets listés
 * 
 * $objets_source et $objets_lies sont de la forme
 * array($objet=>$id_objets,...)
 * $id_objets peut lui meme etre un scalaire ou un tableau pour une liste d'objets du meme type
 * ou de la forme array("NOT",$id_objets) pour une selection par exclusion
 *
 * Les objets sources sont les pivots qui portent les liens
 * et pour lesquels une table spip_xxx_liens existe
 * (auteurs, documents, mots)
 *
 * on peut passer optionnellement une qualification du (des) lien(s) qui sera
 * alors appliquee dans la foulee.
 * En cas de lot de liens, c'est la meme qualification qui est appliquee a tous
 * 
 * @api
 * @param array $objets_source
 * @param array|string $objets_lies
 * @param array $qualif
 * @return bool|int
 */
function objet_associer($objets_source, $objets_lies, $qualif = null){
	$modifs = objet_traiter_liaisons('lien_insert', $objets_source, $objets_lies);

	if ($qualif)
		objet_qualifier_liens($objets_source, $objets_lies, $qualif);

	return $modifs; // pas d'erreur
}


/**
 * Dissocier un (ou des) objet(s)  des objets listés
 * 
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
 * @api
 * @param array $objets_source
 * @param array|string $objets_lies
 * @return bool|int
 */
function objet_dissocier($objets_source,$objets_lies){
	return objet_traiter_liaisons('lien_delete',$objets_source,$objets_lies);
}



/**
 * Qualifier le lien entre un (ou des) objet(s) et des objets listés
 * 
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
 * @api
 * @param array $objets_source
 * @param array|string $objets_lies
 * @param array $qualif
 * @return bool|int
 */
function objet_qualifier_liens($objets_source,$objets_lies,$qualif){
	return objet_traiter_liaisons('lien_set',$objets_source,$objets_lies,$qualif);
}


/**
 * Trouver les liens entre objets
 * 
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
 * renvoie une liste de tableaux decrivant chaque lien
 * dans lequel objet_source et objet_lie sont aussi affectes avec l'id de chaque
 * par facilite
 * ex :
 * array(
 *   array('id_document'=>23,'objet'=>'article','id_objet'=>12,'vu'=>'oui',
 *         'document'=>23,'article'=>12)
 * )
 * 
 * @api
 * @param array $objets_source
 * @param array|string $objets_lies
 * @return array
 */
function objet_trouver_liens($objets_source,$objets_lies){
	return objet_traiter_liaisons('lien_find',$objets_source,$objets_lies);
}


/**
 * Nettoyer les liens morts vers des objets qui n'existent plus
 * 
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
 * @api
 * @param array $objets_source
 * @param array|string $objets_lies
 * @return int
 */
function objet_optimiser_liens($objets_source,$objets_lies){
	return objet_traiter_liaisons('lien_optimise',$objets_source,$objets_lies);
}


/**
 * Dupliquer tous les liens entrant ou sortants d'un objet
 * vers un autre (meme type d'objet, mais id different)
 * si $types est fourni, seuls les liens depuis/vers les types listes seront copies
 * si $exclure_types est fourni, les liens depuis/vers les types listes seront ignores
 *
 * @api
 * @param string $objet
 * @param int $id_source
 * @param int $id_cible
 * @param array $types
 * @param array $exclure_types
 * @return int
 *     Nombre de liens copiés
 */
function objet_dupliquer_liens($objet,$id_source,$id_cible,$types=null,$exclure_types=null){
	include_spip('base/objets');
	$tables = lister_tables_objets_sql();
	$n = 0;
	foreach($tables as $table_sql => $infos){
		if (
			(is_null($types) OR in_array($infos['type'],$types))
			AND (is_null($exclure_types) OR !in_array($infos['type'],$exclure_types))
			){
			if (objet_associable($infos['type'])){
				$liens = (($infos['type']==$objet)?
						objet_trouver_liens(array($objet=>$id_source),'*')
					:
						objet_trouver_liens(array($infos['type']=>'*'),array($objet=>$id_source)));
				foreach($liens as $lien){
					$n++;
					if ($infos['type']==$objet){
						objet_associer(array($objet=>$id_cible),array($lien['objet']=>$lien[$lien['objet']]),$lien);
					}
					else {
						objet_associer(array($infos['type']=>$lien[$infos['type']]),array($objet=>$id_cible),$lien);
					}
				}
			}
		}
	}
	return $n;
}

/**
 * Fonctions techniques
 * ne pas les appeler directement
 */


/**
 * Fonction générique qui
 * applique une operation de liaison entre un ou des objets et des objets listés
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
 * @internal
 * @param string $operation
 * @param array $objets_source
 * @param array $objets_lies
 * @param array $set
 * @return bool|int|array
 */
function objet_traiter_liaisons($operation,$objets_source,$objets_lies, $set = null){
	// accepter une syntaxe minimale pour supprimer tous les liens
	if ($objets_lies=='*') $objets_lies = array('*'=>'*');
	$modifs = 0; // compter le nombre de modifications
	$echec = null;
	foreach($objets_source as $objet=>$ids){
		if ($a = objet_associable($objet)) {
			list($primary,$l) = $a;
			if (!is_array($ids))
				$ids = array($ids);
			elseif(reset($ids)=="NOT"){
				// si on demande un array('NOT',...) => recuperer la liste d'ids correspondants
				$where = lien_where($primary,$ids,'*','*');
				$ids = sql_allfetsel($primary,$l,$where);
				$ids = array_map('reset',$ids);
			}
			foreach($ids as $id) {
				$res = $operation($objet,$primary,$l,$id,$objets_lies,$set);
				if ($res===false) {
					spip_log("objet_traiter_liaisons [Echec] : $operation sur $objet/$primary/$l/$id",_LOG_ERREUR);
					$echec = true;
				}
				else
					$modifs=($modifs?(is_array($res)?array_merge($modifs,$res):$modifs+$res):$res);
			}
		}
		else
			$echec = true;
	}

	return ($echec?false:$modifs); // pas d'erreur
}


/**
 * Sous fonction insertion
 * qui traite les liens pour un objet source dont la clé primaire
 * et la table de lien sont fournies
 * 
 * $objets et de la forme
 * array($objet=>$id_objets,...)
 *
 * Retourne le nombre d'insertions realisees
 *
 * @internal
 * @param string $objet_source
 * @param string $primary
 * @param sgring $table_lien
 * @param int $id
 * @param array $objets
 * @return bool|int
 */
function lien_insert($objet_source,$primary,$table_lien,$id,$objets) {
	$ins = 0;
	$echec = null;
	foreach($objets as $objet => $id_objets){
		if (!is_array($id_objets)) $id_objets = array($id_objets);
		foreach($id_objets as $id_objet) {
			$objet = ($objet=='*')?$objet:objet_type($objet); # securite
			// Envoyer aux plugins
			$id_objet = pipeline('pre_edition_lien',
				array(
					'args' => array(
						'table_lien' => $table_lien,
						'objet_source' => $objet_source,
						'id_objet_source' => $id,
						'objet' => $objet,
						'id_objet' => $id_objet,
						'action'=>'insert',
					),
					'data' => $id_objet
				)
			);
			if ($id_objet=intval($id_objet)
				AND !sql_getfetsel(
								$primary,
								$table_lien,
								array('id_objet='.intval($id_objet), 'objet='.sql_quote($objet), $primary.'='.intval($id))))
			{

					$e = sql_insertq($table_lien, array('id_objet' => $id_objet, 'objet'=>$objet, $primary=>$id));
					if ($e!==false) {
						$ins++;
						lien_propage_date_modif($objet,$id_objet);
						lien_propage_date_modif($objet_source,$id);
						// Envoyer aux plugins
						pipeline('post_edition_lien',
							array(
								'args' => array(
									'table_lien' => $table_lien,
									'objet_source' => $objet_source,
									'id_objet_source' => $id,
									'objet' => $objet,
									'id_objet' => $id_objet,
									'action'=>'insert',
								),
								'data' => $id_objet
							)
						);
					}
					else
						$echec = true;
			}
		}
	}
	return ($echec?false:$ins);
}

/**
 * Fabriquer la condition where en tenant compte des jokers *
 *
 * @internal
 * @param string $primary
 * @param int|string|array $id_source
 * @param string $objet
 * @param int|string|array $id_objet
 * @return array
 */
function lien_where($primary, $id_source, $objet, $id_objet){
	if ((!is_array($id_source) AND !strlen($id_source))
	  OR !strlen($objet)
	  OR (!is_array($id_objet) AND !strlen($id_objet)))
		return array("0=1"); // securite

	$not="";
	if (is_array($id_source) AND reset($id_source)=="NOT"){
		$not = array_shift($id_source);
		$id_source = reset($id_source);
	}
	$where = array();
	if ($id_source!=='*')
		$where[] = (is_array($id_source)?sql_in(addslashes($primary),array_map('intval',$id_source),$not):addslashes($primary) . ($not?"<>":"=") . intval($id_source));
	elseif ($not)
		$where[] = "0=1"; // idiot mais quand meme

	$not="";
	if (is_array($id_objet) AND reset($id_objet)=="NOT"){
		$not = array_shift($id_objet);
		$id_objet = reset($id_objet);
	}

	if ($objet!=='*')
		$where[] = "objet=".sql_quote($objet);
	if ($id_objet!=='*')
		$where[] = (is_array($id_objet)?sql_in('id_objet',array_map('intval',$id_objet),$not):"id_objet" . ($not?"<>":"=") . intval($id_objet));
	elseif ($not)
		$where[] = "0=1"; // idiot mais quand meme

	return $where;
}

/**
 * Sous fonction suppression
 * qui traite les liens pour un objet source dont la clé primaire
 * et la table de lien sont fournies
 *
 * $objets et de la forme
 * array($objet=>$id_objets,...)
 * un * pour $id,$objet,$id_objets permet de traiter par lot
 *
 * @internal
 * @param string $objet_source
 * @param string $primary
 * @param sgring $table_lien
 * @param int $id
 * @param array $objets
 * @return bool|int
 */
function lien_delete($objet_source,$primary,$table_lien,$id,$objets){
	$retire = array();
	$dels = 0;
	$echec = false;
	foreach($objets as $objet => $id_objets){
		$objet = ($objet=='*')?$objet:objet_type($objet); # securite
		if (!is_array($id_objets) OR reset($id_objets)=="NOT") $id_objets = array($id_objets);
		foreach($id_objets as $id_objet) {
			// id_objet peut valoir '*'
			$where = lien_where($primary, $id, $objet, $id_objet);
			// lire les liens existants pour propager la date de modif
			$liens = sql_allfetsel("$primary,id_objet,objet",$table_lien,$where);
			// iterer sur les liens pour permettre aux plugins de gerer
			foreach($liens as $l){
				// Envoyer aux plugins
				$id_o = pipeline('pre_edition_lien',
					array(
						'args' => array(
							'table_lien' => $table_lien,
							'objet_source' => $objet_source,
							'id_objet_source' => $l[$primary],
							'objet' => $l['objet'],
							'id_objet' => $l['id_objet'],
							'action'=>'delete',
						),
						'data' => $l['id_objet']
					)
				);
				if ($id_o=intval($id_o)){
					$where = lien_where($primary, $l[$primary], $l['objet'], $id_o);
					$e = sql_delete($table_lien, $where);
					if ($e!==false){
						$dels+=$e;
						lien_propage_date_modif($l['objet'],$id_o);
						lien_propage_date_modif($objet_source,$l[$primary]);
					}
					else
						$echec = true;
					$retire[] = array('source'=>array($objet_source=>$l[$primary]),'lien'=>array($l['objet']=>$id_o),'type'=>$l['objet'],'id'=>$id_o);
					// Envoyer aux plugins
					pipeline('post_edition_lien',
						array(
							'args' => array(
								'table_lien' => $table_lien,
								'objet_source' => $objet_source,
								'id_objet_source' => $l[$primary],
								'objet' => $l['objet'],
								'id_objet' => $id_o,
								'action'=>'delete',
							),
							'data' => $id_o
						)
					);
				}
			}
		}
	}
	pipeline('trig_supprimer_objets_lies',$retire);

	return ($echec?false:$dels);
}


/**
 * Sous fonction optimisation
 * qui nettoie les liens morts (vers un objet inexistant)
 * pour un objet source dont la clé primaire
 * et la table de lien sont fournies
 *
 * $objets et de la forme
 * array($objet=>$id_objets,...)
 * un * pour $id,$objet,$id_objets permet de traiter par lot
 *
 * @internal
 * @param string $objet_source
 * @param string $primary
 * @param sgring $table_lien
 * @param int $id
 * @param array $objets
 * @return bool|int
 */
function lien_optimise($objet_source,$primary,$table_lien,$id,$objets){
	include_spip('genie/optimiser');
	$echec = false;
	$dels = 0;
	foreach($objets as $objet => $id_objets){
		$objet = ($objet=='*')?$objet:objet_type($objet); # securite
		if (!is_array($id_objets) OR reset($id_objets)=="NOT") $id_objets = array($id_objets);
		foreach($id_objets as $id_objet) {
			$where = lien_where($primary, $id, $objet, $id_objet);
			# les liens vers un objet inexistant
			$r = sql_select("DISTINCT objet",$table_lien,$where);
			while ($t = sql_fetch($r)){
				$type = $t['objet'];
				$spip_table_objet = table_objet_sql($type);
				$id_table_objet = id_table_objet($type);
				$res = sql_select("L.$primary AS id,L.id_objet",
					// la condition de jointure inclue L.objet='xxx' pour ne joindre que les bonnes lignes
					// du coups toutes les lignes avec un autre objet ont un id_xxx=NULL puisque LEFT JOIN
					// il faut les eliminier en repetant la condition dans le where L.objet='xxx'
								"$table_lien AS L
									LEFT JOIN $spip_table_objet AS O
										ON (O.$id_table_objet=L.id_objet AND L.objet=".sql_quote($type).")",
						"L.objet=".sql_quote($type)." AND O.$id_table_objet IS NULL");
				// sur une cle primaire composee, pas d'autres solutions que de virer un a un
				while ($row = sql_fetch($res)){
					$e = sql_delete($table_lien, array("$primary=".$row['id'],"id_objet=".$row['id_objet'],"objet=".sql_quote($type)));
					if ($e!=false){
						$dels+=$e;
						spip_log("Entree ".$row['id']."/".$row['id_objet']."/$type supprimee dans la table $table_lien",_LOG_INFO_IMPORTANTE);
					}
				}
			}

			# les liens depuis un objet inexistant
			$table_source = table_objet_sql($objet_source);
			// filtrer selon $id, $objet, $id_objet eventuellement fournis
			// (en general '*' pour chaque)
			$where = lien_where("L.$primary", $id, $objet, $id_objet);
			$where[] = "O.$primary IS NULL";
			$res = sql_select("L.$primary AS id",
				      "$table_lien AS L LEFT JOIN $table_source AS O ON L.$primary=O.$primary",
							$where);
			$dels+= optimiser_sansref($table_lien, $primary, $res);
		}
	}
	return ($echec?false:$dels);
}


/**
 * Sous fonction qualification
 * qui traite les liens pour un objet source dont la clé primaire
 * et la table de lien sont fournies
 *
 * $objets et de la forme
 * array($objet=>$id_objets,...)
 * un * pour $id,$objet,$id_objets permet de traiter par lot
 * 
 * exemple :
 * $qualif = array('vu'=>'oui');
 *
 * @internal
 * @param string $objet_source
 * @param string $primary
 * @param sgring $table_lien
 * @param int $id
 * @param array $objets
 * @param array $qualif
 * @return bool|int
 */
function lien_set($objet_source,$primary,$table_lien,$id,$objets,$qualif){
	$echec = null;
	$ok = 0;
	if (!$qualif)
		return false;
	// nettoyer qualif qui peut venir directement d'un objet_trouver_lien :
	unset($qualif[$primary]);
	unset($qualif[$objet_source]);
	if (isset($qualif['objet'])) {
		unset($qualif[$qualif['objet']]);
	}
	unset($qualif['objet']);
	unset($qualif['id_objet']);
	foreach($objets as $objet => $id_objets){
		$objet = ($objet=='*')?$objet:objet_type($objet); # securite
		if (!is_array($id_objets) OR reset($id_objets)=="NOT") $id_objets = array($id_objets);
		foreach($id_objets as $id_objet) {
			$where = lien_where($primary, $id, $objet, $id_objet);
			$e = sql_updateq($table_lien,$qualif,$where);
			if ($e===false)
				$echec = true;
		  else
			  $ok++;
		}
	}
	return ($echec?false:$ok);
}

/**
 * Sous fonction trouver
 * qui cherche les liens pour un objet source dont la clé primaire
 * et la table de lien sont fournies
 *
 * $objets et de la forme
 * array($objet=>$id_objets,...)
 * un * pour $id,$objet,$id_objets permet de traiter par lot
 *
 *
 * @internal
 * @param string $objet_source
 * @param string $primary
 * @param sgring $table_lien
 * @param int $id
 * @param array $objets
 * @return array
 */
function lien_find($objet_source,$primary,$table_lien,$id,$objets){
	$trouve = array();
	foreach($objets as $objet => $id_objets){
		$objet = ($objet=='*')?$objet:objet_type($objet); # securite
		// lien_where prend en charge les $id_objets sous forme int ou array
		$where = lien_where($primary, $id, $objet, $id_objets);
		$liens = sql_allfetsel('*',$table_lien,$where);
		// ajouter les entrees objet_source et objet cible par convenance
		foreach($liens as $l) {
			$l[$objet_source] = $l[$primary];
			$l[$objet] = $l['id_objet'];
			$trouve[] = $l;
		}
	}
	return $trouve;
}

/**
 * Propager la date_modif sur les objets dont un lien a été modifié
 *
 * @internal
 * @param string $objet
 * @param array|int $ids
 */
function lien_propage_date_modif($objet,$ids){
	$trouver_table = charger_fonction('trouver_table','base');

	$table = table_objet_sql($objet);
	if ($desc = $trouver_table($table)
	 AND isset($desc['field']['date_modif'])){
		$primary = id_table_objet($objet);
		$where = (is_array($ids)?sql_in($primary, array_map('intval',$ids)):"$primary=".intval($ids));
		sql_updateq($table, array('date_modif'=>date('Y-m-d H:i:s')), $where);
	}
}
?>
