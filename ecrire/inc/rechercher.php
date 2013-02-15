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
 * Gestion des recherches
 *
 * @package SPIP\Core\Recherche
**/

if (!defined('_ECRIRE_INC_VERSION')) return;


// Donne la liste des champs/tables ou l'on sait chercher/remplacer
// avec un poids pour le score
// http://doc.spip.org/@liste_des_champs
function liste_des_champs() {
	static $liste=null;
	if (is_null($liste)) {
		$liste = array();
		// recuperer les tables_objets_sql declarees
		include_spip('base/objets');
		$tables_objets = lister_tables_objets_sql();
		foreach($tables_objets as $t=>$infos){
			if ($infos['rechercher_champs']){
				$liste[$infos['type']] = $infos['rechercher_champs'];
			}
		}
		// puis passer dans le pipeline
		$liste = pipeline('rechercher_liste_des_champs', $liste);
	}
	return $liste;
}


// Recherche des auteurs et mots-cles associes
// en ne regardant que le titre ou le nom
// http://doc.spip.org/@liste_des_jointures
function liste_des_jointures() {
	static $liste=null;
	if (is_null($liste)) {
		$liste = array();
		// recuperer les tables_objets_sql declarees
		include_spip('base/objets');
		$tables_objets = lister_tables_objets_sql();
		foreach($tables_objets as $t=>$infos){
			if ($infos['rechercher_jointures']){
				$liste[$infos['type']] = $infos['rechercher_jointures'];
			}
		}
		// puis passer dans le pipeline
		$liste = pipeline('rechercher_liste_des_jointures', $liste);
	}
	return $liste;
}

function expression_recherche($recherche, $options) {
	$u = $GLOBALS['meta']['pcre_u'];
	include_spip('inc/charsets');
	$recherche = trim(translitteration($recherche));

	$is_preg = false;
	if (substr($recherche,0,1)=='/' AND substr($recherche,-1,1)=='/'){
		// c'est une preg
		$preg = $recherche.$options['preg_flags'];
		$is_preg = true;
	}
	else{
		// s'il y a plusieurs mots il faut les chercher tous : oblige REGEXP
		if (preg_match(",\s+,".$u, $recherche)){
			$is_preg = true;
			$recherche = preg_replace(',\s+,'.$u, '|', $recherche);
		}

		$preg = '/'.str_replace('/', '\\/', $recherche).'/' . $options['preg_flags'];
	}

	// Si la chaine est inactive, on va utiliser LIKE pour aller plus vite
	// ou si l'expression reguliere est invalide
	if (!$is_preg
	  OR (@preg_match($preg,'')===FALSE) ) {
		$methode = 'LIKE';
		$u = $GLOBALS['meta']['pcre_u'];
		// eviter les parentheses et autres caractères qui interferent avec pcre par la suite (dans le preg_match_all) s'il y a des reponses
		$recherche = str_replace(
			array('(',')','?','[', ']', '+', '*', '/'),
			array('\(','\)','[?]', '\[', '\]', '\+', '\*', '\/'),
			$recherche);
		$recherche_mod = $recherche;

		// echapper les % et _
		$q = str_replace(array('%','_'), array('\%', '\_'), trim($recherche));
		// les expressions entre " " sont un mot a chercher tel quel
		// -> on remplace les espaces par un _ et on enleve les guillemets
		if (preg_match(',["][^"]+["],Uims',$q,$matches)){
			foreach($matches as $match){
				// corriger le like dans le $q
				$word = preg_replace(",\s+,Uims","_",$match);
				$word = trim($word,'"');
				$q = str_replace($match,$word,$q);
				// corriger la regexp
				$word = preg_replace(",\s+,Uims","[\s]",$match);
				$word = trim($word,'"');
				$recherche_mod = str_replace($match,$word,$recherche_mod);
			}
		}
		$q = sql_quote(
			"%"
			. preg_replace(",\s+,".$u, "%", $q)
			. "%"
		);

		$preg = '/'.preg_replace(",\s+,".$u, ".+", trim($recherche_mod)).'/' . $options['preg_flags'];

	} else {
		$methode = 'REGEXP';
		$q = sql_quote(trim($recherche, '/'));
	}

	return array($methode, $q, $preg);
}


// Effectue une recherche sur toutes les tables de la base de donnees
// options :
// - toutvoir pour eviter autoriser(voir)
// - flags pour eviter les flags regexp par defaut (UimsS)
// - champs pour retourner les champs concernes
// - score pour retourner un score
// On peut passer les tables, ou une chaine listant les tables souhaitees
// http://doc.spip.org/@recherche_en_base
function recherche_en_base($recherche='', $tables=NULL, $options=array(), $serveur='') {
	include_spip('base/abstract_sql');

	if (!is_array($tables)) {
		$liste = liste_des_champs();

		if (is_string($tables)
		AND $tables != '') {
			$toutes = array();
			foreach(explode(',', $tables) as $t)
				if (isset($liste[$t]))
					$toutes[$t] = $liste[$t];
			$tables = $toutes;
			unset($toutes);
		} else
			$tables = $liste;
	}

	if (!strlen($recherche) OR !count($tables))
		return array();

	include_spip('inc/autoriser');

	// options par defaut
	$options = array_merge(array(
		'preg_flags' => 'UimsS',
		'toutvoir' => false,
		'champs' => false,
		'score' => false,
		'matches' => false,
		'jointures' => false,
		'serveur' => $serveur
		),
		$options
	);

	$results = array();

	// Utiliser l'iterateur (DATA:recherche)
	// pour recuperer les couples (id_objet, score)
	// Le resultat est au format { 
	//      id1 = { 'score' => x, attrs => { } },
	//      id2 = { 'score' => x, attrs => { } },
	// }
	include_spip('inc/memoization');
	foreach ($tables as $table => $champs) {
		# lock via memoization, si dispo
		if (function_exists('cache_lock'))
			cache_lock($lock = 'recherche '.$table.' '.$recherche);

		spip_timer('rech');

		// TODO: ici plutot charger un iterateur via l'API iterateurs
		include_spip('inc/recherche_to_array');
		$to_array = charger_fonction('recherche_to_array', 'inc');
		$results[$table] = $to_array($recherche,
			array_merge($options, array('table' => $table, 'champs' => $champs))
		);
		##var_dump($results[$table]);


		spip_log("recherche $table ($recherche) : ".count($results[$table])." resultats ".spip_timer('rech'),'recherche');

		if (isset($lock))
			cache_unlock($lock);
	}

	return $results;
}


// Effectue une recherche sur toutes les tables de la base de donnees
// http://doc.spip.org/@remplace_en_base
function remplace_en_base($recherche='', $remplace=NULL, $tables=NULL, $options=array()) {
	include_spip('inc/modifier');

	// options par defaut
	$options = array_merge(array(
		'preg_flags' => 'UimsS',
		'toutmodifier' => false
		),
		$options
	);
	$options['champs'] = true;


	if (!is_array($tables))
		$tables = liste_des_champs();

	$results = recherche_en_base($recherche, $tables, $options);

	$preg = '/'.str_replace('/', '\\/', $recherche).'/' . $options['preg_flags'];

	foreach ($results as $table => $r) {
		$_id_table = id_table_objet($table);
		foreach ($r as $id => $x) {
			if ($options['toutmodifier']
			OR autoriser('modifier', $table, $id)) {
				$modifs = array();
				foreach ($x['champs'] as $key => $val) {
					if ($key == $_id_table) next;
					$repl = preg_replace($preg, $remplace, $val);
					if ($repl <> $val)
						$modifs[$key] = $repl;
				}
				if ($modifs)
					objet_modifier_champs($table, $id,
						array(
							'champs' => array_keys($modifs),
						),
						$modifs);
			}
		}
	}
}

?>
