<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) return;


// Donne la liste des champs/tables ou l'on sait chercher/remplacer
// avec un poids pour le score
// http://doc.spip.org/@liste_des_champs
function liste_des_champs() {
	return
	pipeline('rechercher_liste_des_champs',
		array(
			'article' => array(
				'surtitre' => 5, 'titre' => 8, 'soustitre' => 5, 'chapo' => 3,
				'texte' => 1, 'ps' => 1, 'nom_site' => 1, 'url_site' => 1,
				'descriptif' => 4
			),
			'rubrique' => array(
				'titre' => 8, 'descriptif' => 5, 'texte' => 1
			),
			'auteur' => array(
				'nom' => 5, 'bio' => 1, 'email' => 1, 'nom_site' => 1, 'url_site' => 1, 'login' => 1
			),
			'document' => array(
				'titre' => 3, 'descriptif' => 1, 'fichier' => 1
			),

		)
	);
}


// Recherche des auteurs et mots-cles associes
// en ne regardant que le titre ou le nom
// http://doc.spip.org/@liste_des_jointures
function liste_des_jointures() {
	return
	pipeline('rechercher_liste_des_jointures',
			array(
			'article' => array(
				'auteur' => array('nom' => 10),
				'document' => array('titre' => 2, 'descriptif' => 1)
			),
			'rubrique' => array(
				'document' => array('titre' => 2, 'descriptif' => 1)
			)
		)
	);
}

function expression_recherche($recherche, $options) {
	$u = $GLOBALS['meta']['pcre_u'];
	include_spip('inc/charsets');
	$recherche = trim(translitteration($recherche));

	// s'il y a plusieurs mots il faut les chercher tous : oblige REGEXP
	$recherche = preg_replace(',\s+,'.$u, '|', $recherche);

	$preg = '/'.str_replace('/', '\\/', $recherche).'/' . $options['preg_flags'];
	// Si la chaine est inactive, on va utiliser LIKE pour aller plus vite
	// ou si l'expression reguliere est invalide
	if (preg_quote($recherche, '/') == $recherche
	OR (@preg_match($preg,'')===FALSE) ) {
		$methode = 'LIKE';
		$u = $GLOBALS['meta']['pcre_u'];
		// eviter les parentheses qui interferent avec pcre par la suite (dans le preg_match_all) s'il y a des reponses
		$recherche = str_replace(
			array('(',')','?','[', ']'),
			array('\(','\)','[?]', '\[', '\]'),
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
		$q = sql_quote($recherche);
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
	foreach ($tables as $table => $champs) {
		# lock via memoization, si dispo
		include_spip('inc/memoization');
		if (function_exists('cache_lock'))
			cache_lock($lock = 'recherche '.$table.' '.$recherche);

		spip_timer('rech');

		# TODO : ici plutot charger un iterateur via l'API iterateurs
		include_spip('inc/recherche_to_array');
		$to_array = charger_fonction('recherche_to_array', 'inc');
		$results[$table] = $to_array($recherche,
			array_merge($options, array('table' => $table))
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
					modifier_contenu($table, $id,
						array(
							'champs' => array_keys($modifs),
						),
						$modifs);
			}
		}
	}
}

?>
