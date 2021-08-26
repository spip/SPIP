<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * API d'édition de liens
 *
 * Cette API gère la création, modification et suppressions de liens
 * entre deux objets éditoriaux par l'intermédiaire de tables de liaison
 * tel que spip_xx_liens.
 *
 * L'unicité est assurée dans les fonctions sur le trio (id_x, objet, id_objet)
 * par défaut, ce qui correspond à la déclaration de clé primaire.
 *
 * Des rôles peuvent être déclarés pour des liaisons. À ce moment là,
 * une colonne spécifique doit être présente dans la table de liens
 * et l'unicité est alors assurée sur le quatuor (id_x, objet, id_objet, role)
 * et la clé primaire adaptée en conséquence.
 *
 * @package SPIP\Core\Liens\API
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// charger la gestion les rôles sur les objets
include_spip('inc/roles');


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
function objet_associable($objet) {
	$trouver_table = charger_fonction('trouver_table', 'base');
	$table_sql = table_objet_sql($objet);

	$l = "";
	if ($primary = id_table_objet($objet)
		and $trouver_table($l = $table_sql . "_liens")
		and !preg_match(',[^\w],', $primary)
		and !preg_match(',[^\w],', $l)
	) {
		return array($primary, $l);
	}

	spip_log("Objet $objet non associable : ne dispose pas d'une cle primaire $primary OU d'une table liens $l");

	return false;
}

/**
 * Associer un ou des objets à des objets listés
 *
 * `$objets_source` et `$objets_lies` sont de la forme
 * `array($objet=>$id_objets,...)`
 * `$id_objets` peut lui même être un scalaire ou un tableau pour une liste d'objets du même type
 * ou de la forme `array("NOT", $id_objets)` pour une sélection par exclusion
 *
 * Les objets sources sont les pivots qui portent les liens
 * et pour lesquels une table spip_xxx_liens existe
 * (auteurs, documents, mots)
 *
 * On peut passer optionnellement une qualification du (des) lien(s) qui sera
 * alors appliquée dans la foulée.
 * En cas de lot de liens, c'est la même qualification qui est appliquée a tous
 *
 * @api
 * @param array $objets_source
 * @param array|string $objets_lies
 * @param array $qualif
 * @return bool|int
 */
function objet_associer($objets_source, $objets_lies, $qualif = null) {
	$modifs = objet_traiter_liaisons('lien_insert', $objets_source, $objets_lies, $qualif);

	if ($qualif) {
		objet_qualifier_liens($objets_source, $objets_lies, $qualif);
	}

	return $modifs; // pas d'erreur
}


/**
 * Dissocier un (ou des) objet(s)  des objets listés
 *
 * `$objets_source` et `$objets_lies` sont de la forme
 * `array($objet=>$id_objets,...)`
 * `$id_objets` peut lui-même être un scalaire ou un tableau pour une liste d'objets du même type
 *
 * Les objets sources sont les pivots qui portent les liens
 * et pour lesquels une table spip_xxx_liens existe
 * (auteurs, documents, mots)
 *
 * un * pour $objet, $id_objet permet de traiter par lot
 * seul le type de l'objet source ne peut pas accepter de joker et doit etre explicite
 *
 * S'il y a des rôles possibles entre les 2 objets, et qu'aucune condition
 * sur la colonne du rôle n'est transmise, on ne supprime que les liens
 * avec le rôle par défaut. Si on veut supprimer tous les rôles,
 * il faut spécifier $cond => array('role' => '*')
 *
 * @api
 * @param array $objets_source
 * @param array|string $objets_lies
 * @param array|null $cond
 *     Condition du where supplémentaires
 *
 *     À l'exception de l'index 'role' qui permet de sélectionner un rôle
 *     ou tous les rôles (*), en s'affranchissant du vrai nom de la colonne.
 * @return bool|int
 */
function objet_dissocier($objets_source, $objets_lies, $cond = null) {
	return objet_traiter_liaisons('lien_delete', $objets_source, $objets_lies, $cond);
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
function objet_qualifier_liens($objets_source, $objets_lies, $qualif) {
	return objet_traiter_liaisons('lien_set', $objets_source, $objets_lies, $qualif);
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
 * @param array $objets_source Couples (objets_source => identifiants) (objet qui a la table de lien)
 * @param array|string $objets_lies Couples (objets_lies => identifiants)
 * @param array|null $cond Condition du where supplémentaires
 * @return array
 *     Liste des trouvailles
 */
function objet_trouver_liens($objets_source, $objets_lies, $cond = null) {
	return objet_traiter_liaisons('lien_find', $objets_source, $objets_lies, $cond);
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
function objet_optimiser_liens($objets_source, $objets_lies) {
	spip_log("objet_optimiser_liens : ".json_encode($objets_source) . ', ' . json_encode($objets_lies), 'genie'._LOG_DEBUG);
	return objet_traiter_liaisons('lien_optimise', $objets_source, $objets_lies);
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
function objet_dupliquer_liens($objet, $id_source, $id_cible, $types = null, $exclure_types = null) {
	include_spip('base/objets');
	$tables = lister_tables_objets_sql();
	$n = 0;
	foreach ($tables as $table_sql => $infos) {
		if (
			(is_null($types) or in_array($infos['type'], $types))
			and (is_null($exclure_types) or !in_array($infos['type'], $exclure_types))
		) {
			if (objet_associable($infos['type'])) {
				$liens = (($infos['type'] == $objet) ?
					objet_trouver_liens(array($objet => $id_source), '*')
					:
					objet_trouver_liens(array($infos['type'] => '*'), array($objet => $id_source)));
				foreach ($liens as $lien) {
					$n++;
					if ($infos['type'] == $objet) {
						if (
							(is_null($types) or in_array($lien['objet'], $types))
							and (is_null($exclure_types) or !in_array($lien['objet'], $exclure_types))
						) {
							objet_associer(array($objet => $id_cible), array($lien['objet'] => $lien[$lien['objet']]), $lien);
						}
					} else {
						objet_associer(array($infos['type'] => $lien[$infos['type']]), array($objet => $id_cible), $lien);
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
 *
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
 *     Nom de la fonction PHP qui traitera l'opération
 * @param array $objets_source
 *     Liste de ou des objets source
 *     De la forme array($objet=>$id_objets,...), où $id_objets peut lui
 *     même être un scalaire ou un tableau pour une liste d'objets du même type
 * @param array $objets_lies
 *     Liste de ou des objets liés
 *     De la forme array($objet=>$id_objets,...), où $id_objets peut lui
 *     même être un scalaire ou un tableau pour une liste d'objets du même type
 * @param null|array $set
 *     Liste de coupels champs valeur, soit array(champs => valeur)
 *     En fonction des opérations il peut servir à différentes utilisations
 * @return bool|int|array
 */
function objet_traiter_liaisons($operation, $objets_source, $objets_lies, $set = null) {
	// accepter une syntaxe minimale pour supprimer tous les liens
	if ($objets_lies == '*') {
		$objets_lies = array('*' => '*');
	}
	$modifs = 0; // compter le nombre de modifications
	$echec = null;
	foreach ($objets_source as $objet => $ids) {
		if ($a = objet_associable($objet)) {
			list($primary, $l) = $a;
			if (!is_array($ids)) {
				$ids = array($ids);
			} elseif (reset($ids) == "NOT") {
				// si on demande un array('NOT',...) => recuperer la liste d'ids correspondants
				$where = lien_where($primary, $ids, '*', '*');
				$ids = sql_allfetsel($primary, $l, $where);
				$ids = array_map('reset', $ids);
			}
			foreach ($ids as $id) {
				$res = $operation($objet, $primary, $l, $id, $objets_lies, $set);
				if ($res === false) {
					spip_log("objet_traiter_liaisons [Echec] : $operation sur $objet/$primary/$l/$id", _LOG_ERREUR);
					$echec = true;
				} else {
					$modifs = ($modifs ? (is_array($res) ? array_merge($modifs, $res) : $modifs + $res) : $res);
				}
			}
		} else {
			$echec = true;
		}
	}

	return ($echec ? false : $modifs); // pas d'erreur
}


/**
 * Sous fonction insertion
 * qui traite les liens pour un objet source dont la clé primaire
 * et la table de lien sont fournies
 *
 * $objets et de la forme
 * array($objet=>$id_objets,...)
 *
 * Retourne le nombre d'insertions réalisées
 *
 * @internal
 * @param string $objet_source Objet source de l'insertion (celui qui a la table de liaison)
 * @param string $primary Nom de la clé primaire de cet objet
 * @param string $table_lien Nom de la table de lien de cet objet
 * @param int $id Identifiant de l'objet sur lesquels on va insérer des liaisons
 * @param array $objets Liste des liaisons à faire, de la forme array($objet=>$id_objets)
 * @param array $qualif
 *     Liste des qualifications à appliquer (qui seront faites par lien_set()),
 *     dont on cherche un rôle à insérer également.
 *     Si l'objet dispose d'un champ rôle, on extrait des qualifications
 *     le rôle s'il est présent, sinon on applique le rôle par défaut.
 * @return bool|int
 *     Nombre d'insertions faites, false si échec.
 */
function lien_insert($objet_source, $primary, $table_lien, $id, $objets, $qualif) {
	$ins = 0;
	$echec = null;
	if (is_null($qualif)) {
		$qualif = array();
	}

	foreach ($objets as $objet => $id_objets) {
		if (!is_array($id_objets)) {
			$id_objets = array($id_objets);
		}

		// role, colonne, where par défaut
		list($role, $colonne_role, $cond) =
			roles_trouver_dans_qualif($objet_source, $objet, $qualif);

		foreach ($id_objets as $id_objet) {
			$objet = (($objet == '*') ? $objet : objet_type($objet)); # securite

			$insertions = array(
				'id_objet' => $id_objet,
				'objet' => $objet,
				$primary => $id
			);
			// rôle en plus s'il est défini
			if ($role) {
				$insertions += array(
					$colonne_role => $role
				);
			}

			if (lien_triables($table_lien)) {
				if (isset($qualif['rang_lien'])) {
					$rang = $qualif['rang_lien'];
				}
				else {
					$where = lien_where($primary, $id, $objet, $id_objet);
					// si il y a deja un lien pour ce couple (avec un autre role?) on reprend le meme rang si non nul
					if (!$rang = intval(sql_getfetsel('rang_lien', $table_lien, $where))) {
						$where = lien_rang_where($table_lien, $primary, $id, $objet, $id_objet);
						$rang = intval(sql_getfetsel('max(rang_lien)', $table_lien, $where));
						// si aucun lien n'a de rang, on en introduit pas, on garde zero
						if ($rang>0) {
							$rang = intval($rang) + 1;
						}
					}
				}
				$insertions['rang_lien'] = $rang;
			}

			$args = array(
				'table_lien' => $table_lien,
				'objet_source' => $objet_source,
				'id_objet_source' => $id,
				'objet' => $objet,
				'id_objet' => $id_objet,
				'role' => $role,
				'colonne_role' => $colonne_role,
				'action' => 'insert',
			);

			// Envoyer aux plugins
			$insertions = pipeline('pre_edition_lien',
				array(
					'args' => $args,
					'data' => $insertions
				)
			);
			$args['id_objet'] = $insertions['id_objet'];

			$where = lien_where($primary, $id, $objet, $id_objet, $cond);

			if (($id_objet = intval($insertions['id_objet']) or in_array($objet, ['site', 'rubrique']))
				and !sql_getfetsel($primary, $table_lien, $where)
			) {

				if (lien_triables($table_lien) and isset($insertions['rang_lien']) and intval($insertions['rang_lien'])) {
					$where_meme_lien = lien_where($primary, $id, $objet, $id_objet);
					$where_meme_lien = implode(' AND ', $where_meme_lien);
					// on decale les liens de rang_lien>=la valeur inseree pour faire la place
					// sauf sur le meme lien avec un role eventuellement different
					$w = lien_rang_where($table_lien, $primary, $id, $objet, $id_objet, ['rang_lien>='.intval($insertions['rang_lien']), "NOT($where_meme_lien)"]);
					sql_update($table_lien, array('rang_lien'=>'rang_lien+1'), $w);
				}

				$e = sql_insertq($table_lien, $insertions);
				if ($e !== false) {
					$ins++;
					lien_propage_date_modif($objet, $id_objet);
					lien_propage_date_modif($objet_source, $id);
					// Envoyer aux plugins
					pipeline('post_edition_lien',
						array(
							'args' => $args,
							'data' => $insertions
						)
					);
				} else {
					$echec = true;
				}
			}
		}
	}
	// si on a fait des insertions, on reordonne les liens concernes
	// pas la peine si $qualif['rang_lien'] etait fournie, on va passer dans lien_set a suivre et donc finir le recomptage
	if ($ins>0 and empty($qualif['rang_lien'])) {
		lien_ordonner($objet_source, $primary, $table_lien, $id, $objets);
	}

	return ($echec ? false : $ins);
}


/**
 * Reordonner les liens sur lesquels on est intervenus
 * @param string $objet_source
 * @param string $primary
 * @param string $table_lien
 * @param int $id
 * @param array|string $objets
 */
function lien_ordonner($objet_source, $primary, $table_lien, $id, $objets) {
	if (!lien_triables($table_lien)) {
		return;
	}

	$deja_reordonne = [];

	foreach ($objets as $objet => $id_objets) {
		if (!is_array($id_objets)) {
			$id_objets = array($id_objets);
		}

		foreach ($id_objets as $id_objet) {
			if (empty($deja_reordonne[$id][$objet][$id_objet])) {
				$objet = (($objet == '*') ? $objet : objet_type($objet)); # securite

				$where = lien_rang_where($table_lien, $primary, $id, $objet, $id_objet);
				$liens = sql_allfetsel("$primary, id_objet, objet, rang_lien", $table_lien, $where, '',"rang_lien");

				$rangs = array_column($liens, 'rang_lien');
				if (count($rangs) and (max($rangs)>0 or min($rangs)<0)) {
					$rang = 1;
					foreach ($liens as $lien) {
						if (empty($deja_reordonne[$lien[$primary]][$lien['objet']][$lien['id_objet']])) {
							$where = lien_where($primary, $lien[$primary], $lien['objet'], $lien['id_objet'], array('rang_lien!='.intval($rang)));
							sql_updateq($table_lien, array('rang_lien' => $rang), $where);

							if (empty($deja_reordonne[$lien[$primary]])) {
								$deja_reordonne[$lien[$primary]] = [];
							}
							if (empty($deja_reordonne[$lien[$primary]][$lien['objet']])) {
								$deja_reordonne[$lien[$primary]][$lien['objet']] = [];
							}
							$deja_reordonne[$lien[$primary]][$lien['objet']][$lien['id_objet']] = $rang;

							$rang++;
						}
					}
				}
			}
		}
	}
}


/**
 * Une table de lien est-elle triable ?
 * elle doit disposer d'un champ rang_lien pour cela
 * @param $table_lien
 * @return mixed
 */
function lien_triables($table_lien) {
	static $triables = array();
	if (!isset($triables[$table_lien])) {
		$trouver_table = charger_fonction('trouver_table', 'base');
		$desc = $trouver_table($table_lien);
		if ($desc and isset($desc['field']['rang_lien'])) {
			$triables[$table_lien] = true;
		}
		else {
			$triables[$table_lien] = false;
		}
	}
	return $triables[$table_lien];
}


/**
 * Fabriquer la condition where en tenant compte des jokers *
 *
 * @internal
 * @param string $primary Nom de la clé primaire
 * @param int|string|array $id_source Identifiant de la clé primaire
 * @param string $objet Nom de l'objet lié
 * @param int|string|array $id_objet Identifiant de l'objet lié
 * @param array $cond Conditions par défaut
 * @return array                        Liste des conditions
 */
function lien_where($primary, $id_source, $objet, $id_objet, $cond = array()) {
	if ((!is_array($id_source) and !strlen($id_source))
		or !strlen($objet)
		or (!is_array($id_objet) and !strlen($id_objet))
	) {
		return array("0=1");
	} // securite

	$not = "";
	if (is_array($id_source) and reset($id_source) == "NOT") {
		$not = array_shift($id_source);
		$id_source = reset($id_source);
	}

	$where = $cond;

	if ($id_source !== '*') {
		$where[] = (is_array($id_source) ? sql_in(addslashes($primary), array_map('intval', $id_source),
			$not) : addslashes($primary) . ($not ? "<>" : "=") . intval($id_source));
	} elseif ($not) {
		$where[] = "0=1";
	} // idiot mais quand meme

	$not = "";
	if (is_array($id_objet) and reset($id_objet) == "NOT") {
		$not = array_shift($id_objet);
		$id_objet = reset($id_objet);
	}

	if ($objet !== '*') {
		$where[] = "objet=" . sql_quote($objet);
	}
	if ($id_objet !== '*') {
		$where[] = (is_array($id_objet) ? sql_in('id_objet', array_map('intval', $id_objet),
			$not) : "id_objet" . ($not ? "<>" : "=") . intval($id_objet));
	} elseif ($not) {
		$where[] = "0=1";
	} // idiot mais quand meme

	return $where;
}

/**
 * Fabriquer la condition where pour compter les rangs
 * @param string $table_lien
 * @param string $primary
 * @param int|string|array $id_source
 * @param string $objet
 * @param int|string|array $id_objet
 * @param array $cond
 * @return array                        Liste des conditions
 */
function lien_rang_where($table_lien, $primary, $id_source, $objet, $id_objet, $cond = []) {

	// si on veut compter les rangs autrement que le core ne le fait par defaut, fournir le where adhoc
	if (function_exists($f = 'lien_rang_where_' . $table_lien)) {
		return $f($primary, $id_source, $objet, $id_objet, $cond);
	}

	// par defaut c'est un rang compté pour tous les id_source d'un couple objet-id_objet
	return lien_where($primary, '*', $objet, $id_objet, $cond);
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
 * On supprime tous les liens entre les objets indiqués par défaut,
 * sauf s'il y a des rôles déclarés entre ces 2 objets, auquel cas on ne
 * supprime que les liaisons avec le role déclaré par défaut si rien n'est
 * précisé dans $cond. Il faut alors passer $cond=array('role'=>'*') pour
 * supprimer tous les roles, ou array('role'=>'un_role') pour un role précis.
 *
 * @internal
 * @param string $objet_source
 * @param string $primary
 * @param string $table_lien
 * @param int $id
 * @param array $objets
 * @param array|null $cond
 *     Conditions where par défaut.
 *     Un cas particulier est géré lorsque l'index 'role' est présent (ou absent)
 * @return bool|int
 */
function lien_delete($objet_source, $primary, $table_lien, $id, $objets, $cond = null) {

	$retire = array();
	$dels = 0;
	$echec = false;
	if (is_null($cond)) {
		$cond = array();
	}

	foreach ($objets as $objet => $id_objets) {
		$objet = ($objet == '*') ? $objet : objet_type($objet); # securite
		if (!is_array($id_objets) or reset($id_objets) == "NOT") {
			$id_objets = array($id_objets);
		}
		foreach ($id_objets as $id_objet) {
			list($cond, $colonne_role, $role) = roles_creer_condition_role($objet_source, $objet, $cond);
			// id_objet peut valoir '*'
			$where = lien_where($primary, $id, $objet, $id_objet, $cond);

			// lire les liens existants pour propager la date de modif
			$select = "$primary,id_objet,objet";
			if ($colonne_role) {
				$select .= ",$colonne_role";
			}
			$liens = sql_allfetsel($select, $table_lien, $where);

			// iterer sur les liens pour permettre aux plugins de gerer
			foreach ($liens as $l) {

				$args = array(
					'table_lien' => $table_lien,
					'objet_source' => $objet_source,
					'id_objet_source' => $l[$primary],
					'objet' => $l['objet'],
					'id_objet' => $l['id_objet'],
					'colonne_role' => $colonne_role,
					'role' => ($colonne_role ? $l[$colonne_role] : ''),
					'action' => 'delete',
				);

				// Envoyer aux plugins
				$l = pipeline('pre_edition_lien',
					array(
						'args' => $args,
						'data' => $l
					)
				);
				$args['id_objet'] = $id_o = $l['id_objet'];

				if ($id_o = intval($l['id_objet']) or in_array($l['objet'], ['site', 'rubrique'])) {
					$where = lien_where($primary, $l[$primary], $l['objet'], $id_o, $cond);
					$e = sql_delete($table_lien, $where);
					if ($e !== false) {
						$dels += $e;
						lien_propage_date_modif($l['objet'], $id_o);
						lien_propage_date_modif($objet_source, $l[$primary]);
					} else {
						$echec = true;
					}
					$retire[] = array(
						'source' => array($objet_source => $l[$primary]),
						'lien' => array($l['objet'] => $id_o),
						'type' => $l['objet'],
						'role' => ($colonne_role ? $l[$colonne_role] : ''),
						'id' => $id_o
					);
					// Envoyer aux plugins
					pipeline('post_edition_lien',
						array(
							'args' => $args,
							'data' => $l
						)
					);
				}
			}
		}
	}
	// si on a supprime des liens, on reordonne les liens concernes
	if ($dels) {
		lien_ordonner($objet_source, $primary, $table_lien, $id, $objets);
	}

	pipeline('trig_supprimer_objets_lies', $retire);

	return ($echec ? false : $dels);
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
 * @param string $table_lien
 * @param int $id
 * @param array $objets
 * @return bool|int
 */
function lien_optimise($objet_source, $primary, $table_lien, $id, $objets) {
	include_spip('genie/optimiser');
	$echec = false;
	$dels = 0;
	foreach ($objets as $objet => $id_objets) {
		$objet = ($objet == '*') ? $objet : objet_type($objet); # securite
		if (!is_array($id_objets) or reset($id_objets) == "NOT") {
			$id_objets = array($id_objets);
		}
		foreach ($id_objets as $id_objet) {
			$where = lien_where($primary, $id, $objet, $id_objet);
			# les liens vers un objet inexistant
			$r = sql_select("DISTINCT objet", $table_lien, $where);
			while ($t = sql_fetch($r)) {
				$type = $t['objet'];
				$spip_table_objet = table_objet_sql($type);
				$id_table_objet = id_table_objet($type);
				$res = sql_select("L.$primary AS id,L.id_objet",
					// la condition de jointure inclue L.objet='xxx' pour ne joindre que les bonnes lignes
					// du coups toutes les lignes avec un autre objet ont un id_xxx=NULL puisque LEFT JOIN
					// il faut les eliminier en repetant la condition dans le where L.objet='xxx'
					"$table_lien AS L
									LEFT JOIN $spip_table_objet AS O
										ON (O.$id_table_objet=L.id_objet AND L.objet=" . sql_quote($type) . ")",
					"L.objet=" . sql_quote($type) . " AND O.$id_table_objet IS NULL");
				// sur une cle primaire composee, pas d'autres solutions que de virer un a un
				while ($row = sql_fetch($res)) {
					if ($primary === 'id_document' and in_array($type, ['site', 'rubrique']) and !intval($row['id_objet'])) {
						continue; // gaffe, c'est le logo du site ou des rubriques!
					}
					$e = sql_delete($table_lien,
						array("$primary=" . $row['id'], "id_objet=" . $row['id_objet'], "objet=" . sql_quote($type)));
					if ($e != false) {
						$dels += $e;
						spip_log(
							"lien_optimise: Entree " . $row['id'] . "/" . $row['id_objet'] . "/$type supprimee dans la table $table_lien",
							'genie'._LOG_INFO_IMPORTANTE
						);
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
			$dels += optimiser_sansref($table_lien, $primary, $res);
		}
	}

	return ($echec ? false : $dels);
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
 * @param string $objet_source Objet source de l'insertion (celui qui a la table de liaison)
 * @param string $primary Nom de la clé primaire de cet objet
 * @param string $table_lien Nom de la table de lien de cet objet
 * @param int $id Identifiant de l'objet sur lesquels on va insérer des liaisons
 * @param array $objets Liste des liaisons à faire, de la forme array($objet=>$id_objets)
 * @param array $qualif
 *     Liste des qualifications à appliquer.
 *
 *     Si l'objet dispose d'un champ rôle, on extrait des qualifications
 *     le rôle s'il est présent, sinon on applique les qualifications
 *     sur le rôle par défaut.
 * @return bool|int
 *     Nombre de modifications faites, false si échec.
 */
function lien_set($objet_source, $primary, $table_lien, $id, $objets, $qualif) {
	$echec = null;
	$ok = 0;
	$reordonner = false;
	if (!$qualif) {
		return false;
	}
	// nettoyer qualif qui peut venir directement d'un objet_trouver_lien :
	unset($qualif[$primary]);
	unset($qualif[$objet_source]);
	if (isset($qualif['objet'])) {
		unset($qualif[$qualif['objet']]);
	}
	unset($qualif['objet']);
	unset($qualif['id_objet']);
	foreach ($objets as $objet => $id_objets) {

		// role, colonne, where par défaut
		list($role, $colonne_role, $cond) =
			roles_trouver_dans_qualif($objet_source, $objet, $qualif);

		$objet = ($objet == '*') ? $objet : objet_type($objet); # securite
		if (!is_array($id_objets) or reset($id_objets) == "NOT") {
			$id_objets = array($id_objets);
		}
		foreach ($id_objets as $id_objet) {

			$args = array(
				'table_lien' => $table_lien,
				'objet_source' => $objet_source,
				'id_objet_source' => $id,
				'objet' => $objet,
				'id_objet' => $id_objet,
				'role' => $role,
				'colonne_role' => $colonne_role,
				'action' => 'modifier',
			);

			// Envoyer aux plugins
			$qualif = pipeline('pre_edition_lien',
				array(
					'args' => $args,
					'data' => $qualif,
				)
			);
			$args['id_objet'] = $id_objet;

			if (lien_triables($table_lien) and isset($qualif['rang_lien'])) {
				if (intval($qualif['rang_lien'])) {
					// on decale les liens de rang_lien>=la valeur inseree pour faire la place
					// sauf sur le meme lien avec un role eventuellement different
					$where_meme_lien = lien_where($primary, $id, $objet, $id_objet);
					$where_meme_lien = implode(' AND ', $where_meme_lien);
					$w = lien_rang_where($table_lien, $primary, $id, $objet, $id_objet, ['rang_lien>='.intval($qualif['rang_lien']), "NOT($where_meme_lien)"]);
					sql_update($table_lien, array('rang_lien'=>'rang_lien+1'), $w);
				}
				// tous les liens de même rôle recoivent le rang indiqué aussi
				if (roles_colonne($objet_source, $objet)) {
					$w = lien_where($primary, $id, $objet, $id_objet);
					sql_updateq($table_lien, array('rang_lien' => intval($qualif['rang_lien'])), $w);
				}
				$reordonner = true;
			}

			$where = lien_where($primary, $id, $objet, $id_objet, $cond);
			$e = sql_updateq($table_lien, $qualif, $where);

			if ($e === false) {
				$echec = true;
			} else {
				// Envoyer aux plugins
				pipeline('post_edition_lien',
					array(
						'args' => $args,
						'data' => $qualif
					)
				);
				$ok++;
			}
		}
	}
	// si on a fait des modif de rang, on reordonne les liens concernes
	if ($reordonner) {
		lien_ordonner($objet_source, $primary, $table_lien, $id, $objets);
	}

	return ($echec ? false : $ok);
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
 * Le tableau de condition peut avoir un index 'role' indiquant de
 * chercher un rôle précis, ou * pour tous les roles (alors équivalent
 * à l'absence de l'index)
 *
 * @internal
 * @param string $objet_source
 * @param string $primary
 * @param string $table_lien
 * @param int $id
 * @param array $objets
 * @param array|null $cond
 *     Condition du where par défaut
 *
 *     On peut passer un index 'role' pour sélectionner uniquement
 *     le role défini dedans (et '*' pour tous les rôles).
 * @return array
 */
function lien_find($objet_source, $primary, $table_lien, $id, $objets, $cond = null) {
	$trouve = array();
	foreach ($objets as $objet => $id_objets) {
		$objet = ($objet == '*') ? $objet : objet_type($objet); # securite
		// gerer les roles s'il y en a dans $cond
		list($cond) = roles_creer_condition_role($objet_source, $objet, $cond, true);
		// lien_where prend en charge les $id_objets sous forme int ou array
		$where = lien_where($primary, $id, $objet, $id_objets, $cond);
		$liens = sql_allfetsel('*', $table_lien, $where);
		// ajouter les entrees objet_source et objet cible par convenance
		foreach ($liens as $l) {
			$l[$objet_source] = $l[$primary];
			$l[$l['objet']] = $l['id_objet'];
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
function lien_propage_date_modif($objet, $ids) {
	static $done = array();
	$hash = md5($objet . serialize($ids));

	// sql_updateq, peut être un rien lent.
	// On évite de l'appeler 2 fois sur les mêmes choses
	if (isset($done[$hash])) {
		return;
	}

	$trouver_table = charger_fonction('trouver_table', 'base');

	$table = table_objet_sql($objet);
	if ($desc = $trouver_table($table)
		and isset($desc['field']['date_modif'])
	) {
		$primary = id_table_objet($objet);
		$where = (is_array($ids) ? sql_in($primary, array_map('intval', $ids)) : "$primary=" . intval($ids));
		sql_updateq($table, array('date_modif' => date('Y-m-d H:i:s')), $where);
	}

	$done[$hash] = true;
}
