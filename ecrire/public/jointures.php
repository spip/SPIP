<?php

use Spip\Compilateur\Noeud\Boucle;

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
 * Déduction automatique d'une chaîne de jointures
 *
 * @package SPIP\Core\Compilateur\Jointures
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Décomposer un champ id_truc en (id_objet,objet,truc)
 *
 * Exemple : décompose id_article en (id_objet,objet,article)
 *
 * @param string $champ
 *     Nom du champ à décomposer
 * @return array|string
 *     Tableau si décomposable : 'id_objet', 'objet', Type de l'objet
 *     Chaine sinon : le nom du champ (non décomposable donc)
 */
function decompose_champ_id_objet($champ) {
	if (($champ !== 'id_objet') and preg_match(',^id_([a-z_]+)$,', $champ, $regs)) {
		return ['id_objet', 'objet', objet_type($champ)];
	}

	return $champ;
}

/**
 * Mapping d'un champ d'une jointure en deux champs id_objet,objet si nécessaire
 *
 * Si le champ demandé existe dans la table, on l'utilise, sinon on
 * regarde si le champ se décompose en objet/id_objet et si la table
 * possède ces champs, et dans ce cas, on les retourne.
 *
 * @uses decompose_champ_id_objet()
 * @param string $champ Nom du champ à tester (ex. id_article)
 * @param array $desc Description de la table
 * @return array
 *     Liste du/des champs. Soit
 *     - array($champ), si le champ existe dans la table ou si on ne peut décomposer.
 *     - array(id_objet, objet), si le champ n'existe pas mais qu'on peut décomposer
 */
function trouver_champs_decomposes($champ, $desc) {
	if (
		!is_array($desc) // on ne se risque pas en conjectures si on ne connait pas la table
		or array_key_exists($champ, $desc['field'])
	) {
		return [$champ];
	}
	// si le champ se décompose, tester que les colonnes décomposées sont présentes
	if (is_array($decompose = decompose_champ_id_objet($champ))) {
		array_pop($decompose);
		if (count(array_intersect($decompose, array_keys($desc['field']))) == count($decompose)) {
			return $decompose;
		}
	}

	return [$champ];
}


/**
 * Calculer et construite une jointure entre $depart et $arrivee
 *
 * L'objet boucle est modifié pour compléter la requête.
 * La fonction retourne l'alias d'arrivée une fois la jointure construire,
 * en general un "Lx"
 *
 * @uses calculer_chaine_jointures()
 * @uses fabrique_jointures()
 *
 * @param Boucle $boucle
 *     Description de la boucle
 * @param array $depart
 *     Table de départ, sous la forme (nom de la table, description de la table)
 * @param array $arrivee
 *     Table d'arrivée, sous la forme (nom de la table, description de la table)
 * @param string $col
 *     Colonne cible de la jointure
 * @param bool $cond
 *     Flag pour savoir si le critère est conditionnel ou non
 * @param int $max_liens
 *     Nombre maximal de liaisons possibles pour trouver la jointure.
 * @return string
 *     Alias de la table de jointure (Lx)
 */
function calculer_jointure(&$boucle, $depart, $arrivee, $col = '', $cond = false, $max_liens = 5) {
	// les jointures minimales sont optimales :
	// on contraint le nombre d'etapes en l'augmentant
	// jusqu'a ce qu'on trouve une jointure ou qu'on atteigne la limite maxi
	$max = 1;
	$res = false;
	$milieu_exclus = ($col ?: []);
	while ($max <= $max_liens and !$res) {
		$res = calculer_chaine_jointures($boucle, $depart, $arrivee, [], $milieu_exclus, $max);
		$max++;
	}
	if (!$res) {
		return '';
	}

	[$nom, $desc] = $depart;

	return fabrique_jointures($boucle, $res, $cond, $desc, $nom, $col);
}

/**
 * Fabriquer une jointure à l'aide d'une liste descriptive d'étapes
 *
 * Ajoute
 * - la jointure dans le tableau $boucle->join,
 * - la table de jointure dans le from
 * - un modificateur 'lien'
 *
 * @uses nogroupby_if()
 * @uses liste_champs_jointures()
 *
 * @param Boucle $boucle
 *     Description de la boucle
 * @param array $res
 *     Chaîne des jointures
 *     $res = array(
 *         array(table_depart,array(table_arrivee,desc),jointure),
 *         ...
 *     )
 *     Jointure peut être un tableau pour les jointures sur champ decomposé
 *     array('id_article','id_objet','objet','article')
 *     array('id_objet','id_article','objet','article')
 * @param bool $cond
 *     Flag pour savoir si le critère est conditionnel ou non
 * @param array $desc
 *     Description de la table de départ
 * @param string $nom
 *     Nom de la table de départ
 * @param string $col
 *     Colonne cible de la jointure
 * @param bool $echap
 *     Écrire les valeurs dans boucle->join en les échappant ou non ?
 * @return string
 *     Alias de la table de jointure (Lx)
 */
function fabrique_jointures(&$boucle, $res, $cond = false, $desc = [], $nom = '', $col = '', $echap = true) {
	$a = [];
	$j = null;
	$n = null;
	static $num = [];
	$id_table = '';
	$cpt = &$num[$boucle->descr['nom']][$boucle->descr['gram']][$boucle->id_boucle];
	foreach ($res as $cle => $r) {
		[$d, $a, $j] = $r;
		if (!$id_table) {
			$id_table = $d;
		}
		$n = ++$cpt;
		if (is_array($j)) { // c'est un lien sur un champ du type id_objet,objet,'article'
			[$j1, $j2, $obj, $type] = $j;
			// trouver de quel cote est (id_objet,objet)
			if ($j1 == "id_$obj") {
				$obj = "$id_table.$obj";
			} else {
				$obj = "L$n.$obj";
			}
			// le where complementaire est envoye dans la jointure et dans le where
			// on utilise une clé qui le relie a la jointure pour que l'optimiseur
			// sache qu'il peut enlever ce where si il enleve la jointure
			$boucle->where["JOIN-L$n"] =
				$echap ?
					["'='","'$obj'","sql_quote('$type')"]
					:
					['=',"$obj",sql_quote($type)];
			$boucle->join["L$n"] =
				$echap ?
					["'$id_table'", "'$j2'", "'$j1'", "'$obj='.sql_quote('$type')"]
					:
					[$id_table, $j2, $j1, "$obj=" . sql_quote($type)];
		} else {
			$boucle->join["L$n"] = $echap ? ["'$id_table'", "'$j'"] : [$id_table, $j];
		}
		$boucle->from[$id_table = "L$n"] = $a[0];
	}


	// pas besoin de group by
	// (cf http://article.gmane.org/gmane.comp.web.spip.devel/30555)
	// si une seule jointure et sur une table avec primary key formee
	// de l'index principal et de l'index de jointure (non conditionnel! [6031])
	// et operateur d'egalite (https://core.spip.net/issues/477)

	if ($pk = (isset($a[1]) && (count($boucle->from) == 2) && !$cond)) {
		$pk = nogroupby_if($desc, $a[1], $col);
	}

	// pas de group by
	// si une seule jointure
	// et si l'index de jointure est une primary key a l'arrivee !
	if (
		!$pk
		and (count($boucle->from) == 2)
		and isset($a[1]['key']['PRIMARY KEY'])
		and ($j == $a[1]['key']['PRIMARY KEY'])
	) {
		$pk = true;
	}

	// la clause Group by est en conflit avec ORDER BY, a completer
	$groups = liste_champs_jointures($nom, $desc, true);
	if (!$pk) {
		foreach ($groups as $id_prim) {
			$id_field = $nom . '.' . $id_prim;
			if (!in_array($id_field, $boucle->group)) {
				$boucle->group[] = $id_field;
			}
		}
	}

	$boucle->modificateur['lien'] = true;

	return "L$n";
}

/**
 * Condition suffisante pour qu'un Group-By ne soit pas nécéssaire
 *
 * À améliorer, notamment voir si calculer_select ne pourrait pas la réutiliser
 * lorsqu'on sait si le critere conditionnel est finalement present
 *
 * @param array $depart
 * @param array $arrivee
 * @param string|array $col
 * @return bool
 */
function nogroupby_if($depart, $arrivee, $col) {
	if (
		empty($arrivee['key']['PRIMARY KEY'])
		or !($pk = $arrivee['key']['PRIMARY KEY'])
		or empty($depart['key']['PRIMARY KEY'])
	) {
		return false;
	}
	$id_primary = $depart['key']['PRIMARY KEY'];
	if (is_array($col)) {
		$col = implode(', *', $col);
	} // cas id_objet, objet
	return (preg_match("/^$id_primary, *$col$/", $pk) or
		preg_match("/^$col, *$id_primary$/", $pk));
}

/**
 * Lister les champs candidats a une jointure, sur une table
 * si un join est fourni dans la description, c'est lui qui l'emporte
 * sauf si cle primaire explicitement demandee par $primary
 *
 * sinon on construit une liste des champs a partir de la liste des cles de la table
 *
 * @uses split_key()
 * @param string $nom
 * @param array $desc
 * @param bool $primary
 * @return array
 */
function liste_champs_jointures($nom, $desc, $primary = false) {

	static $nojoin = ['idx', 'maj', 'date', 'statut'];

	// si cle primaire demandee, la privilegier
	if ($primary && isset($desc['key']['PRIMARY KEY'])) {
		return split_key($desc['key']['PRIMARY KEY']);
	}

	// les champs declares explicitement pour les jointures
	if (isset($desc['join'])) {
		return $desc['join'];
	}
	/*elseif (isset($GLOBALS['tables_principales'][$nom]['join'])) return $GLOBALS['tables_principales'][$nom]['join'];
	elseif (isset($GLOBALS['tables_auxiliaires'][$nom]['join'])) return $GLOBALS['tables_auxiliaires'][$nom]['join'];*/

	// si pas de cle, c'est fichu
	if (!isset($desc['key'])) {
		return [];
	}

	// si cle primaire
	if (isset($desc['key']['PRIMARY KEY'])) {
		return split_key($desc['key']['PRIMARY KEY']);
	}

	// ici on se rabat sur les cles secondaires,
	// en eliminant celles qui sont pas pertinentes (idx, maj)
	// si jamais le resultat n'est pas pertinent pour une table donnee,
	// il faut declarer explicitement le champ 'join' de sa description

	$join = [];
	foreach ($desc['key'] as $v) {
		$join = split_key($v, $join);
	}
	foreach ($join as $k) {
		if (in_array($k, $nojoin)) {
			unset($join[$k]);
		}
	}

	return $join;
}

/**
 * Eclater une cle composee en plusieurs champs
 *
 * @param string $v
 * @param array $join
 * @return array
 */
function split_key($v, $join = []) {
	foreach (preg_split('/,\s*/', $v) as $k) {
		if (strpos($k, '(') !== false) {
			$k = explode('(', $k);
			$k = trim(reset($k));
		}
		$join[$k] = $k;
	}
	return $join;
}

/**
 * Constuire la chaine de jointures, de proche en proche
 *
 * @uses liste_champs_jointures()
 * @uses trouver_champs_decomposes()
 *
 * @param objetc $boucle
 * @param array $depart
 *  sous la forme array(nom de la table, description)
 * @param array $arrivee
 *  sous la forme array(nom de la table, description)
 * @param array $vu
 *  tables deja vues dans la jointure, pour ne pas y repasser
 * @param array $milieu_exclus
 *  cles deja utilisees, pour ne pas les reutiliser
 * @param int $max_liens
 *  nombre maxi d'etapes
 * @return array
 */
function calculer_chaine_jointures(
	&$boucle,
	$depart,
	$arrivee,
	$vu = [],
	$milieu_exclus = [],
	$max_liens = 5
) {
	static $trouver_table;
	if (!$trouver_table) {
		$trouver_table = charger_fonction('trouver_table', 'base');
	}

	if (is_string($milieu_exclus)) {
		$milieu_exclus = [$milieu_exclus];
	}
	// quand on a exclus id_objet comme cle de jointure, il faut aussi exclure objet
	// faire une jointure sur objet tout seul n'a pas de sens
	if (in_array('id_objet', $milieu_exclus) and !in_array('objet', $milieu_exclus)) {
		$milieu_exclus[] = 'objet';
	}

	[$dnom, $ddesc] = $depart;
	[$anom, $adesc] = $arrivee;
	if (!count($vu)) {
		$vu[] = $dnom; // ne pas oublier la table de depart
		$vu[] = $anom; // ne pas oublier la table d'arrivee
	}

	$akeys = [];
	foreach ($adesc['key'] as $k) {
		// respecter l'ordre de $adesc['key'] pour ne pas avoir id_trad en premier entre autres...
		$akeys = array_merge($akeys, preg_split('/,\s*/', $k));
	}

	// enlever les cles d'arrivee exclues par l'appel
	$akeys = array_diff($akeys, $milieu_exclus);

	// cles candidates au depart
	$keys = liste_champs_jointures($dnom, $ddesc);
	// enlever les cles dde depart exclues par l'appel
	$keys = array_diff($keys, $milieu_exclus);

	$v = !$keys ? false : array_intersect(array_values($keys), $akeys);

	if ($v) {
		return [[$dnom, [$adesc['table'], $adesc], array_shift($v)]];
	}

	// regarder si l'on a (id_objet,objet) au depart et si on peut le mapper sur un id_xx
	if (count(array_intersect(['id_objet', 'objet'], $keys)) == 2) {
		// regarder si l'une des cles d'arrivee peut se decomposer en
		// id_objet,objet
		// si oui on la prend
		foreach ($akeys as $key) {
			$v = decompose_champ_id_objet($key);
			if (is_array($v)) {
				$objet = array_shift($v); // objet,'article'
				array_unshift($v, $key); // id_article,objet,'article'
				array_unshift($v, $objet); // id_objet,id_article,objet,'article'
				return [[$dnom, [$adesc['table'], $adesc], $v]];
			}
		}
	} else {
		// regarder si l'une des cles de depart peut se decomposer en
		// id_objet,objet a l'arrivee
		// si oui on la prend
		foreach ($keys as $key) {
			if (count($v = trouver_champs_decomposes($key, $adesc)) > 1) {
				if (count($v) == count(array_intersect($v, $akeys))) {
					$v = decompose_champ_id_objet($key); // id_objet,objet,'article'
					array_unshift($v, $key); // id_article,id_objet,objet,'article'
					return [[$dnom, [$adesc['table'], $adesc], $v]];
				}
			}
		}
	}
	// si l'on voulait une jointure direct, c'est rate !
	if ($max_liens <= 1) {
		return [];
	}

	// sinon essayer de passer par une autre table
	$new = $vu;
	foreach ($boucle->jointures as $v) {
		if (
			$v
			and !in_array($v, $vu)
			and $def = $trouver_table($v, $boucle->sql_serveur)
			and !in_array($def['table_sql'], $vu)
		) {
			// ne pas tester les cles qui sont exclues a l'appel
			// ie la cle de la jointure precedente
			$test_cles = $milieu_exclus;
			$new[] = $v;
			$max_iter = 50; // securite
			while (
				count($jointure_directe_possible = calculer_chaine_jointures(
					$boucle,
					$depart,
					[$v, $def],
					$vu,
					$test_cles,
					1
				))
				and $max_iter--
			) {
				$jointure_directe_possible = reset($jointure_directe_possible);
				$milieu = end($jointure_directe_possible);
				$exclure_fin = $milieu_exclus;
				if (is_string($milieu)) {
					$exclure_fin[] = $milieu;
					$test_cles[] = $milieu;
				} else {
					$exclure_fin = array_merge($exclure_fin, $milieu);
					$test_cles = array_merge($test_cles, $milieu);
				}
				// essayer de rejoindre l'arrivee a partir de cette etape intermediaire
				// sans repasser par la meme cle milieu, ni une cle deja vue !
				$r = calculer_chaine_jointures($boucle, [$v, $def], $arrivee, $new, $exclure_fin, $max_liens - 1);
				if ($r) {
					array_unshift($r, $jointure_directe_possible);

					return $r;
				}
			}
		}
	}

	return [];
}

/**
 * applatit les cles multiples
 * redondance avec split_key() ? a mutualiser
 *
 * @param $keys
 * @return array
 */
function trouver_cles_table($keys) {
	$res = [];
	foreach ($keys as $v) {
		if (!strpos($v, ',')) {
			$res[$v] = 1;
		} else {
			foreach (preg_split('/\s*,\s*/', $v) as $k) {
				$res[$k] = 1;
			}
		}
	}

	return array_keys($res);
}


/**
 * Indique si une colonne (ou plusieurs colonnes) est présente dans l'une des tables indiquée.
 *
 * @param string|array $cle
 *     Nom de la ou des colonnes à trouver dans les tables indiquées
 * @param array $tables
 *     Liste de noms de tables ou des couples (alias => nom de table).
 *     - `$boucle->from` (alias => nom de table) : les tables déjà utilisées dans une boucle
 *     - `$boucle->jointures` : les tables utilisables en tant que jointure
 *     - `$boucle->jointures_explicites` les jointures explicitement indiquées à l'écriture de la boucle
 * @param string $connect
 *     Nom du connecteur SQL
 * @param bool|string $checkarrivee
 *     false : peu importe la table, si on trouve le/les champs, c'est bon.
 *     string : nom de la table où on veut trouver le champ.
 * @return array|false
 *     false : on n'a pas trouvé
 *     array : infos sur la table trouvée. Les clés suivantes sont retournés :
 *     - 'desc' : tableau de description de la table,
 *     - 'table' : nom de la table
 *     - 'alias' : alias utilisé pour la table (si pertinent. ie: avec `$boucle->from` transmis par exemple)
 */
function chercher_champ_dans_tables($cle, $tables, $connect, $checkarrivee = false) {
	static $trouver_table = '';
	if (!$trouver_table) {
		$trouver_table = charger_fonction('trouver_table', 'base');
	}

	if (!is_array($cle)) {
		$cle = [$cle];
	}

	foreach ($tables as $k => $table) {
		if ($table && $desc = $trouver_table($table, $connect)) {
			if (
				isset($desc['field'])
				// verifier que toutes les cles cherchees sont la
				and (count(array_intersect($cle, array_keys($desc['field']))) == count($cle))
				// si on sait ou on veut arriver, il faut que ca colle
				and ($checkarrivee == false || $checkarrivee == $desc['table'])
			) {
				return [
					'desc' => $desc,
					'table' => $desc['table'],
					'alias' => $k,
				];
			}
		}
	}

	return false;
}

/**
 * Cherche une colonne (ou plusieurs colonnes) dans les tables de jointures
 * possibles indiquées.
 *
 * @uses chercher_champ_dans_tables()
 * @uses decompose_champ_id_objet()
 * @uses liste_champs_jointures()
 *
 * @param string|array $cle
 *     Nom de la ou des colonnes à trouver dans les tables de jointures
 * @param array $joints
 *     Liste des jointures possibles (ex: $boucle->jointures ou $boucle->jointures_explicites)
 * @param Boucle $boucle
 *     Description de la boucle
 * @param bool|string $checkarrivee
 *     false : peu importe la table, si on trouve le/les champs, c'est bon.
 *     string : nom de la table jointe où on veut trouver le champ.
 * @return array|string
 *     chaîne vide : on n'a pas trouvé
 *     liste si trouvé : nom de la table, description de la table, clé(s) de la table
 */
function trouver_champ_exterieur($cle, $joints, &$boucle, $checkarrivee = false) {

	// support de la recherche multi champ :
	// si en seconde etape on a decompose le champ id_xx en id_objet,objet
	// on reentre ici soit en cherchant une table les 2 champs id_objet,objet
	// soit une table avec les 3 champs id_xx, id_objet, objet
	if (!is_array($cle)) {
		$cle = [$cle];
	}

	if ($infos = chercher_champ_dans_tables($cle, $joints, $boucle->sql_serveur, $checkarrivee)) {
		return [$infos['table'], $infos['desc'], $cle];
	}

	// au premier coup, on essaye de decomposer, si possible
	if (
		count($cle) == 1
		and $c = reset($cle)
		and is_array($decompose = decompose_champ_id_objet($c))
	) {
		$desc = $boucle->show;

		// cas 1 : la cle id_xx est dans la table de depart
		// -> on cherche uniquement id_objet,objet a l'arrivee
		if (isset($desc['field'][$c])) {
			$cle = [];
			$cle[] = array_shift($decompose); // id_objet
			$cle[] = array_shift($decompose); // objet
			return trouver_champ_exterieur($cle, $joints, $boucle, $checkarrivee);
		}
		// cas 2 : la cle id_xx n'est pas dans la table de depart
		// -> il faut trouver une cle de depart zzz telle que
		// id_objet,objet,zzz soit a l'arrivee
		else {
			$depart = liste_champs_jointures(($desc['table'] ?? ''), $desc);
			foreach ($depart as $d) {
				$cle = [];
				$cle[] = array_shift($decompose); // id_objet
				$cle[] = array_shift($decompose); // objet
				$cle[] = $d;
				if ($ext = trouver_champ_exterieur($cle, $joints, $boucle, $checkarrivee)) {
					return $ext;
				}
			}
		}
	}

	return '';
}

/**
 * Cherche a ajouter la possibilite d'interroger un champ sql dans une boucle.
 *
 * Cela construira les jointures necessaires
 * si une possibilite est trouve et retournera le nom de
 * l'alias de la table contenant ce champ
 * (L2 par exemple pour 'spip_mots AS L2' dans le FROM),
 *
 * @uses trouver_champ_exterieur()
 * @uses calculer_jointure()
 *
 * @param string $champ
 *    Nom du champ cherche (exemple id_article)
 * @param object $boucle
 *    Informations connues de la boucle
 * @param array $jointures
 *    Liste des tables parcourues (articles, mots) pour retrouver le champ sql
 *    et calculer la jointure correspondante.
 *    En son absence et par defaut, on utilise la liste des jointures connues
 *    par SPIP pour la table en question ($boucle->jointures)
 * @param bool $cond
 *     flag pour savoir si le critere est conditionnel ou non
 * @param bool|string $checkarrivee
 *     false : peu importe la table, si on trouve le/les champs, c'est bon.
 *     string : nom de la table jointe où on veut trouver le champ.
 *
 * @return string
 */
function trouver_jointure_champ($champ, &$boucle, $jointures = false, $cond = false, $checkarrivee = false) {
	if ($jointures === false) {
		$jointures = $boucle->jointures;
	}
	// TODO : aberration, on utilise $jointures pour trouver le champ
	// mais pas poour construire la jointure ensuite
	$arrivee = trouver_champ_exterieur($champ, $jointures, $boucle, $checkarrivee);
	if ($arrivee) {
		$desc = $boucle->show;
		array_pop($arrivee); // enlever la cle en 3eme argument
		$cle = calculer_jointure($boucle, [$desc['id_table'], $desc], $arrivee, '', $cond);
		if ($cle) {
			return $cle;
		}
	}
	spip_log("trouver_jointure_champ: $champ inconnu");

	return '';
}
