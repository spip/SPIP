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
			'breve' => array(
				'titre' => 8, 'texte' => 2, 'lien_titre' => 1, 'lien_url' => 1
			),
			'rubrique' => array(
				'titre' => 8, 'descriptif' => 5, 'texte' => 1
			),
			'site' => array(
				'nom_site' => 5, 'url_site' => 1, 'descriptif' => 3
			),
			'mot' => array(
				'titre' => 8, 'texte' => 1, 'descriptif' => 5
			),
			'auteur' => array(
				'nom' => 5, 'bio' => 1, 'email' => 1, 'nom_site' => 1, 'url_site' => 1, 'login' => 1
			),
			'forum' => array(
				'titre' => 3, 'texte' => 1, 'auteur' => 2, 'email_auteur' => 2, 'nom_site' => 1, 'url_site' => 1
			),
			'document' => array(
				'titre' => 3, 'descriptif' => 1, 'fichier' => 1
			),
			'syndic_article' => array(
				'titre' => 5, 'descriptif' => 1
			),
			'signature' => array(
				'nom_email' => 2, 'ad_email' => 4,
				'nom_site' => 2, 'url_site' => 4,
				'message' => 1
			)

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
				'mot' => array('titre' => 3),
				'document' => array('titre' => 2, 'descriptif' => 1)
			),
			'breve' => array(
				'mot' => array('titre' => 3),
				'document' => array('titre' => 2, 'descriptif' => 1)
			),
			'rubrique' => array(
				'mot' => array('titre' => 3),
				'document' => array('titre' => 2, 'descriptif' => 1)
			),
			'document' => array(
				'mot' => array('titre' => 3)
			)
		)
	);
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

	include_spip('inc/autoriser');

	// options par defaut
	$options = array_merge(array(
		'preg_flags' => 'UimsS',
		'toutvoir' => false,
		'champs' => false,
		'score' => false,
		'matches' => false,
		'jointures' => false
		),
		$options
	);

	$results = array();

	if (!strlen($recherche) OR !count($tables))
		return array();
	include_spip('inc/charsets');
	$recherche = translitteration($recherche);

	$is_preg = false;
	if (substr($recherche,0,1)=='/' AND substr($recherche,-1,1)=='/'){
		// c'est une preg
		$preg = $recherche.$options['preg_flags'];
		$is_preg = true;
	}
	else
		$preg = '/'.str_replace('/', '\\/', $recherche).'/' . $options['preg_flags'];
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
		$q = sql_quote(substr($recherche,1,-1));
	}

	$jointures = $options['jointures']
		? liste_des_jointures()
		: array();

	foreach ($tables as $table => $champs) {
		$requete = array(
		"SELECT"=>array(),
		"FROM"=>array(),
		"WHERE"=>array(),
		"GROUPBY"=>array(),
		"ORDERBY"=>array(),
		"LIMIT"=>"",
		"HAVING"=>array()
		);

		$_id_table = id_table_objet($table);
		$requete['SELECT'][] = "t.".$_id_table;
		$a = array();
		// Recherche fulltext
		foreach ($champs as $champ => $poids) {
			if (is_array($champ)){
			  spip_log("requetes imbriquees interdites");
			} else {
				if (strpos($champ,".")===FALSE)
					$champ = "t.$champ";
				$requete['SELECT'][] = $champ;
				$a[] = $champ.' '.$methode.' '.$q;
			}
		}
		if ($a) $requete['WHERE'][] = join(" OR ", $a);
		$requete['FROM'][] = table_objet_sql($table).' AS t';

		$s = sql_select(
			$requete['SELECT'], $requete['FROM'], $requete['WHERE'],
			implode(" ",$requete['GROUPBY']),
			$requete['ORDERBY'], $requete['LIMIT'],
			$requete['HAVING'], $serveur
		);

		while ($t = sql_fetch($s,$serveur)) {
			$id = intval($t[$_id_table]);
			if ($options['toutvoir']
			OR autoriser('voir', $table, $id)) {
				// indiquer les champs concernes
				$champs_vus = array();
				$score = 0;
				$matches = array();

				$vu = false;
				foreach ($champs as $champ => $poids) {
					$champ = explode('.',$champ);
					$champ = end($champ);
					if ($n = 
						($options['score'] || $options['matches'])
						? preg_match_all($preg, translitteration_rapide($t[$champ]), $regs, PREG_SET_ORDER)
						: preg_match($preg, translitteration_rapide($t[$champ]))
					) {
						$vu = true;

						if ($options['champs'])
							$champs_vus[$champ] = $t[$champ];
						if ($options['score'])
							$score += $n * $poids;
						if ($options['matches'])
							$matches[$champ] = $regs;

						if (!$options['champs']
						AND !$options['score']
						AND !$options['matches'])
							break;
					}
				}

				if ($vu) {
					if (!isset($results[$table]))
						$results[$table] = array();
					$results[$table][$id] = array();
					if ($champs_vus)
						$results[$table][$id]['champs'] = $champs_vus;
					if ($score)
						$results[$table][$id]['score'] = $score;
					if ($matches)
						$results[$table][$id]['matches'] = $matches;
				}
			}
		}


		// Gerer les donnees associees
		if (isset($jointures[$table])
		AND $joints = recherche_en_base(
				$recherche,
				$jointures[$table],
				array_merge($options, array('jointures' => false))
			)
		) {
			foreach ($joints as $table_liee => $ids_trouves) {
				if (!$rechercher_joints = charger_fonction("rechercher_joints_${table}_${table_liee}","inc",true)){
					$cle_depart = id_table_objet($table);
					$cle_arrivee =  id_table_objet($table_liee);
					$table_sql = preg_replace('/^spip_/', '', table_objet_sql($table));
					$table_liee_sql = preg_replace('/^spip_/', '', table_objet_sql($table_liee));
					if ($table_liee == 'document')
						$s = sql_select("id_objet as $cle_depart, $cle_arrivee", "spip_documents_liens", array("objet='$table'",sql_in('id_'.${table_liee}, array_keys($ids_trouves))), '','','','',$serveur);
					else
						$s = sql_select("$cle_depart,$cle_arrivee", "spip_${table_liee_sql}_${table_sql}", sql_in('id_'.${table_liee}, array_keys($ids_trouves)), '','','','',$serveur);
				}
				else
					list($cle_depart,$cle_arrivee,$s) = $rechercher_joints($table,$table_liee,array_keys($ids_trouves), $serveur);

				while ($t = is_array($s)?array_shift($s):sql_fetch($s)) {
					$id = $t[$cle_depart];
					$joint = $ids_trouves[$t[$cle_arrivee]];
					if (!isset($results[$table]))
						$results[$table] = array();
					if (!isset($results[$table][$id]))
						$results[$table][$id] = array();
					if ($joint['score'])
						$results[$table][$id]['score'] += $joint['score'];
					if ($joint['champs'])
					foreach($joint['champs'] as $c => $val)
						$results[$table][$id]['champs'][$table_liee.'.'.$c] = $val;
					if ($joint['matches'])
					foreach($joint['matches'] as $c => $val)
						$results[$table][$id]['matches'][$table_liee.'.'.$c] = $val;
				}
			}
		}
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
