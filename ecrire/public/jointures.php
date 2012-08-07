<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Déduction automatique d'une chaîne de jointures
 *
 * @package SPIP\Compilateur\Jointures
**/

if (!defined('_ECRIRE_INC_VERSION')) return;


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
function decompose_champ_id_objet($champ){
	if (($champ!=='id_objet') AND preg_match(',^id_([a-z_]+)$,', $champ, $regs)){
		return array('id_objet', 'objet', objet_type($regs[1]));
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
 * @param string $champ  Nom du champ à tester (ex. id_article)
 * @param array $desc    Description de la table
 * @return array
 *     Liste du/des champs. Soit
 *     - array($champ), si le champ existe dans la table ou si on ne peut décomposer.
 *     - array(id_objet, objet), si le champ n'existe pas mais qu'on peut décomposer
 */
function trouver_champs_decomposes($champ, $desc){
	if (!is_array($desc) // on ne se risque pas en conjectures si on ne connait pas la table
	    OR array_key_exists($champ, $desc['field'])
	) {
		return array($champ);
	}
	// si le champ se décompose, tester que les colonnes décomposées sont présentes
	if (is_array($decompose = decompose_champ_id_objet($champ))){
		array_pop($decompose);
		if (count(array_intersect($decompose, array_keys($desc['field'])))==count($decompose))
			return $decompose;
	}
	return array($champ);
}


/**
 * Calculer et construite une jointure entre $depart et $arrivee
 * 
 * L'objet boucle est modifié pour compléter la requête.
 * La fonction retourne l'alias d'arrivée une fois la jointure construire,
 * en general un "Lx"
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
function calculer_jointure(&$boucle, $depart, $arrivee, $col = '', $cond = false, $max_liens=5){
	// les jointures minimales sont optimales :
	// on contraint le nombre d'etapes en l'augmentant
	// jusqu'a ce qu'on trouve une jointure ou qu'on atteigne la limite maxi 
	$max = 1;
	$res = false;
	$milieu_exclus = ($col?$col:array());
	while ($max<=$max_liens AND !$res){
		$res = calculer_chaine_jointures($boucle, $depart, $arrivee, array(), $milieu_exclus, $max);
		$max++;
	}
	if (!$res) return "";

	list($nom, $desc) = $depart;

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
function fabrique_jointures(&$boucle, $res, $cond = false, $desc = array(), $nom = '', $col = '', $echap = true){
	static $num = array();
	$id_table = "";
	$cpt = &$num[$boucle->descr['nom']][$boucle->descr['gram']][$boucle->id_boucle];
	foreach ($res as $cle => $r) {
		list($d, $a, $j) = $r;
		if (!$id_table) $id_table = $d;
		$n = ++$cpt;
		if (is_array($j)) { // c'est un lien sur un champ du type id_objet,objet,'article'
			list($j1, $j2, $obj, $type) = $j;
			// trouver de quel cote est (id_objet,objet)
			if ($j1=="id_$obj")
				$obj = "$id_table.$obj";
			else
				$obj = "L$n.$obj";
			// le where complementaire est envoye dans la jointure pour pouvoir etre elimine avec la jointure
			// en cas d'optimisation
			//$boucle->where[] = array("'='","'$obj'","sql_quote('$type')");
			$boucle->join["L$n"] =
				$echap ?
					array("'$id_table'", "'$j2'", "'$j1'", "'$obj='.sql_quote('$type')")
					:
					array($id_table, $j2, $j1, "$obj=".sql_quote($type));
		}
		else {
			$boucle->join["L$n"] = $echap ? array("'$id_table'", "'$j'") : array($id_table, $j);
		}
		$boucle->from[$id_table = "L$n"] = $a[0];
	}


	// pas besoin de group by
	// (cf http://article.gmane.org/gmane.comp.web.spip.devel/30555)
	// si une seule jointure et sur une table avec primary key formee
	// de l'index principal et de l'index de jointure (non conditionnel! [6031])
	// et operateur d'egalite (http://trac.rezo.net/trac/spip/ticket/477)

	if ($pk = (isset($a[1]) && (count($boucle->from)==2) && !$cond)){
		$pk = nogroupby_if($desc, $a[1], $col);
	}

	// pas de group by 
	// si une seule jointure
	// et si l'index de jointure est une primary key a l'arrivee !
	if (!$pk
	    AND (count($boucle->from)==2)
	        AND isset($a[1]['key']['PRIMARY KEY'])
	            AND ($j==$a[1]['key']['PRIMARY KEY'])
	)
		$pk = true;

	// la clause Group by est en conflit avec ORDER BY, a completer
	$groups = liste_champs_jointures($nom, $desc, true);
	if (!$pk) foreach ($groups as $id_prim){
		$id_field = $nom.'.'.$id_prim;
		if (!in_array($id_field, $boucle->group)){
			$boucle->group[] = $id_field;
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
function nogroupby_if($depart, $arrivee, $col){
	$pk = $arrivee['key']['PRIMARY KEY'];
	if (!$pk) return false;
	$id_primary = $depart['key']['PRIMARY KEY'];
	if (is_array($col)) $col = implode(', *', $col); // cas id_objet, objet
	return (preg_match("/^$id_primary, *$col$/", $pk) OR
	        preg_match("/^$col, *$id_primary$/", $pk));
}

/**
 * Lister les champs candidats a une jointure, sur une table
 * si un join est fourni dans la description, c'est lui qui l'emporte
 * sauf si cle primaire explicitement demandee par $primary
 *
 * sinon on construit une liste des champs a partir de la liste des cles de la table
 * 
 * http://doc.spip.org/@liste_champs_jointures
 *
 * @param string $nom
 * @param array $desc
 * @param bool $primary
 * @return array
 */
function liste_champs_jointures($nom, $desc, $primary = false){

	static $nojoin = array('idx', 'maj', 'date', 'statut');

	// si cle primaire demandee, la privilegier
	if ($primary && isset($desc['key']['PRIMARY KEY']))
		return split_key($desc['key']['PRIMARY KEY']);

	// les champs declares explicitement pour les jointures
	if (isset($desc['join'])) return $desc['join'];
	/*elseif (isset($GLOBALS['tables_principales'][$nom]['join'])) return $GLOBALS['tables_principales'][$nom]['join'];
	elseif (isset($GLOBALS['tables_auxiliaires'][$nom]['join'])) return $GLOBALS['tables_auxiliaires'][$nom]['join'];*/

	// si pas de cle, c'est fichu
	if (!isset($desc['key'])) return array();

	// si cle primaire
	if (isset($desc['key']['PRIMARY KEY']))
		return split_key($desc['key']['PRIMARY KEY']);

	// ici on se rabat sur les cles secondaires, 
	// en eliminant celles qui sont pas pertinentes (idx, maj)
	// si jamais le resultat n'est pas pertinent pour une table donnee,
	// il faut declarer explicitement le champ 'join' de sa description

	$join = array();
	foreach ($desc['key'] as $v) $join = split_key($v, $join);
	foreach ($join as $k) if (in_array($k, $nojoin)) unset($join[$k]);
	return $join;
}

/**
 * Eclater une cle composee en plusieurs champs
 *
 * http://doc.spip.org/@split_key
 *
 * @param string $v
 * @param array $join
 * @return array
 */
function split_key($v, $join = array()){
	foreach (preg_split('/,\s*/', $v) as $k) $join[$k] = $k;
	return $join;
}

/**
 * Constuire la chaine de jointures, de proche en proche
 *
 * http://doc.spip.org/@calculer_chaine_jointures
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
function calculer_chaine_jointures(&$boucle, $depart, $arrivee, $vu = array(), $milieu_exclus = array(), $max_liens = 5){
	static $trouver_table;
	if (!$trouver_table)
		$trouver_table = charger_fonction('trouver_table', 'base');

	if (is_string($milieu_exclus))
		$milieu_exclus = array($milieu_exclus);
	// quand on a exclus id_objet comme cle de jointure, il faut aussi exclure objet
	// faire une jointure sur objet tout seul n'a pas de sens
	if (in_array('id_objet',$milieu_exclus) AND !in_array('objet',$milieu_exclus))
		$milieu_exclus[] = 'objet';

	list($dnom, $ddesc) = $depart;
	list($anom, $adesc) = $arrivee;
	if (!count($vu)){
		$vu[] = $dnom; // ne pas oublier la table de depart
		$vu[] = $anom; // ne pas oublier la table d'arrivee
	}

	$akeys = array();
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

	if ($v)
		return array(array($dnom, array($adesc['table'], $adesc), array_shift($v)));

	// regarder si l'on a (id_objet,objet) au depart et si on peut le mapper sur un id_xx
	if (count(array_intersect(array('id_objet', 'objet'), $keys))==2){
		// regarder si l'une des cles d'arrivee peut se decomposer en 
		// id_objet,objet
		// si oui on la prend
		foreach ($akeys as $key){
			$v = decompose_champ_id_objet($key);
			if (is_array($v)){
				$objet = array_shift($v); // objet,'article'
				array_unshift($v, $key); // id_article,objet,'article'
				array_unshift($v, $objet); // id_objet,id_article,objet,'article'
				return array(array($dnom, array($adesc['table'], $adesc), $v));
			}
		}
	}
	else {
		// regarder si l'une des cles de depart peut se decomposer en 
		// id_objet,objet a l'arrivee
		// si oui on la prend
		foreach ($keys as $key){
			if (count($v = trouver_champs_decomposes($key, $adesc))>1){
				if (count($v)==count(array_intersect($v, $akeys))){
					$v = decompose_champ_id_objet($key); // id_objet,objet,'article'
					array_unshift($v, $key); // id_article,id_objet,objet,'article'
					return array(array($dnom, array($adesc['table'], $adesc), $v));
				}
			}
		}
	}
	// si l'on voulait une jointure direct, c'est rate !
	if ($max_liens<=1) return array();

	// sinon essayer de passer par une autre table
	$new = $vu;
	foreach ($boucle->jointures as $v){
		if ($v
		    AND !in_array($v, $vu)
		    AND $def = $trouver_table($v, $boucle->sql_serveur)
			  AND !in_array($def['table_sql'], $vu)
		){
			// ne pas tester les cles qui sont exclues a l'appel
			// ie la cle de la jointure precedente
			$test_cles = $milieu_exclus;
			$new[] = $v;
			$max_iter = 50; // securite
			while (count($jointure_directe_possible = calculer_chaine_jointures($boucle, $depart, array($v, $def), $vu, $test_cles, 1))
			       AND $max_iter--){
				$jointure_directe_possible = reset($jointure_directe_possible);
				$milieu = end($jointure_directe_possible);
				$exclure_fin = $milieu_exclus;
				if (is_string($milieu)){
					$exclure_fin[] = $milieu;
					$test_cles[] = $milieu;
				}
				else{
					$exclure_fin = array_merge($exclure_fin, $milieu);
					$test_cles = array_merge($test_cles, $milieu);
				}
				// essayer de rejoindre l'arrivee a partir de cette etape intermediaire
				// sans repasser par la meme cle milieu, ni une cle deja vue !
				$r = calculer_chaine_jointures($boucle, array($v, $def), $arrivee, $new, $exclure_fin, $max_liens-1);
				if ($r){
					array_unshift($r, $jointure_directe_possible);
					return $r;
				}
			}
		}
	}
	return array();
}

/**
 * applatit les cles multiples
 * redondance avec split_key() ? a mutualiser
 *
 * http://doc.spip.org/@trouver_cles_table
 *
 * @param $keys
 * @return array
 */
function trouver_cles_table($keys){
	$res = array();
	foreach ($keys as $v){
		if (!strpos($v, ","))
			$res[$v] = 1;
		else {
			foreach (preg_split("/\s*,\s*/", $v) as $k){
				$res[$k] = 1;
			}
		}
	}
	return array_keys($res);
}

/**
 * Cherche une colonne (ou plusieurs colonnes) dans les tables de jointures
 * possibles indiquées.
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
 *     liste si trouvé : nom de la table, description de la table
 */
function trouver_champ_exterieur($cle, $joints, &$boucle, $checkarrivee = false){
	static $trouver_table = '';
	if (!$trouver_table)
		$trouver_table = charger_fonction('trouver_table', 'base');

	// support de la recherche multi champ :
	// si en seconde etape on a decompose le champ id_xx en id_objet,objet
	// on reentre ici soit en cherchant une table les 2 champs id_objet,objet
	// soit une table avec les 3 champs id_xx, id_objet, objet
	if (!is_array($cle))
		$cle = array($cle);

	foreach ($joints as $k => $join){
		if ($join && $table = $trouver_table($join, $boucle->sql_serveur)){
			if (isset($table['field'])
			    // verifier que toutes les cles cherchees sont la
			    AND (count(array_intersect($cle, array_keys($table['field'])))==count($cle))
			        // si on sait ou on veut arriver, il faut que ca colle
			        AND ($checkarrivee==false || $checkarrivee==$table['table'])
			)
				return array($table['table'], $table);
		}
	}

	// au premier coup, on essaye de decomposer, si possible
	if (count($cle)==1
	    AND $c = reset($cle)
	        AND is_array($decompose = decompose_champ_id_objet($c))
	){

		$desc = $boucle->show;
		// cas 1 : la cle id_xx est dans la table de depart
		// -> on cherche uniquement id_objet,objet a l'arrivee
		if (isset($desc['field'][$c])){
			$cle = array();
			$cle[] = array_shift($decompose); // id_objet
			$cle[] = array_shift($decompose); // objet
			return trouver_champ_exterieur($cle, $joints, $boucle, $checkarrivee);
		}
			// cas 2 : la cle id_xx n'est pas dans la table de depart
			// -> il faut trouver une cle de depart zzz telle que
			// id_objet,objet,zzz soit a l'arrivee
		else {
			$depart = liste_champs_jointures($desc['table'], $desc);
			foreach ($depart as $d){
				$cle = array();
				$cle[] = array_shift($decompose); // id_objet
				$cle[] = array_shift($decompose); // objet
				$cle[] = $d;
				if ($ext = trouver_champ_exterieur($cle, $joints, $boucle, $checkarrivee))
					return $ext;
			}
		}
	}
	return "";
}

/**
 * Cherche a ajouter la possibilite d'interroger un champ sql
 * dans une boucle. Cela construira les jointures necessaires
 * si une possibilite est trouve et retournera le nom de
 * l'alias de la table contenant ce champ
 * (L2 par exemple pour 'spip_mots AS L2' dans le FROM),
 *
 * 
 * @param string $champ
 * 		Nom du champ cherche (exemple id_article)
 * @param object $boucle
 * 		Informations connues de la boucle
 * @param array $jointures
 * 		Liste des tables parcourues (articles, mots) pour retrouver le champ sql
 * 		et calculer la jointure correspondante.
 * 		En son absence et par defaut, on utilise la liste des jointures connues
 * 		par SPIP pour la table en question ($boucle->jointures)
 * @param bool $cond
 *     flag pour savoir si le critere est conditionnel ou non
 * 
 * @return string
 */
function trouver_jointure_champ($champ, &$boucle, $jointures = false, $cond = false) {
	if ($jointures === false) {
		$jointures = $boucle->jointures;
	}
	$cle = trouver_champ_exterieur($champ, $jointures, $boucle);
	if ($cle){
		$desc = $boucle->show;
		$cle = calculer_jointure($boucle, array($desc['id_table'], $desc), $cle, '', $cond);
	}
	if ($cle) return $cle;
	spip_log("trouver_jointure_champ: $champ inconnu");
	return '';
}

?>
