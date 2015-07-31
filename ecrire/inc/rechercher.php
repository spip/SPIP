<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;


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
			)
		)
	);
}


// Recherche des auteurs et mots-cles associes
// en ne regardant que le titre ou le nom
// http://doc.spip.org/@liste_des_jointures
function liste_des_jointures() {
	return array(
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
		'documents' => array(
			'mot' => array('titre' => 3)
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
function recherche_en_base($recherche='', $tables=NULL, $options=array()) {
	include_spip('base/abstract_sql');

	if (!is_array($tables)) {
		$liste = liste_des_champs();

		if (is_string($tables)
		AND $tables != '') {
			$toutes = array();
			foreach(explode(',', $tables) as $t)
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

	if (!strlen($recherche))
		return array();

	// Si la chaine est inactive, on va utiliser LIKE pour aller plus vite
	if (preg_quote($recherche, '/') == $recherche) {
		$methode = 'LIKE';
		$q = _q(
			"%"
			. str_replace(array('%','_'), array('\%', '\_'), $recherche)
			. "%"
		);
	} else {
		$methode = 'REGEXP';
		$q = _q($recherche);
	}

	$preg = '/'.$recherche.'/' . $options['preg_flags'];

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
			// il est possible de passer des elements de requete par la table des champs
			// (jointure par exemple)
			// pourquoi pas, mais pas vu ou ca sert... ??
			if (is_array($champ)){
				foreach($champ as $sousreq=>$partie)
					foreach($partie as $elt)
						$requete[$sousreq][] = $elt;
			}
			else {
				if (strpos($champ,".")===FALSE)
					$champ = "t.$champ";
				$requete['SELECT'][] = $champ;
				$a[] = $champ.' '.$methode.' '.$q;
			}
		}
		$requete['WHERE'][] = join(" OR ", $a);
		$requete['FROM'][] = 'spip_'.table_objet($table).' AS t';

		$s = sql_select (
			$requete['SELECT'], $requete['FROM'], $requete['WHERE'],
			implode(" ",$requete['GROUPBY']),
			$requete['ORDERBY'], $requete['LIMIT'], '',
			$requete['HAVING']
		);

		while ($t = sql_fetch($s)) {
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
						? preg_match_all($preg, $t[$champ], $regs, PREG_SET_ORDER)
						: preg_match($preg, $t[$champ])
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
			foreach ($joints as $jtable => $jj) {
				$s = spip_query(
				"SELECT ".id_table_objet($table).", ".id_table_objet($jtable)
				." FROM spip_${jtable}s_${table}s"
				." WHERE ". calcul_mysql_in('id_'.${jtable}, array_keys($jj)));
				while ($t = sql_fetch($s)) {
					$id = $t[id_table_objet($table)];
					$joint = $jj[$t[id_table_objet($jtable)]];
					if (!isset($results[$table]))
						$results[$table] = array();
					if (!isset($results[$table][$id]))
						$results[$table][$id] = array();
					if ($joint['score'])
						$results[$table][$id]['score'] += $joint['score'];
					if ($joint['champs'])
					foreach($joint['champs'] as $c => $val)
						$results[$table][$id]['champs'][$jtable.'.'.$c] = $val;
					if ($joint['matches'])
					foreach($joint['matches'] as $c => $val)
						$results[$table][$id]['matches'][$jtable.'.'.$c] = $val;
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

	$preg = '/'.$recherche.'/' . $options['preg_flags'];

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
