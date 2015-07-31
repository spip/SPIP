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
// http://doc.spip.org/@liste_des_champs
function liste_des_champs() {
	return
	array(
		'article' => array(
			'surtitre', 'titre', 'soustitre', 'chapo', 'texte', 'ps', 'nom_site', 'url_site'
		),
		'breve' => array(
			'titre', 'texte', 'lien_titre', 'lien_url'
		),
		'rubrique' => array(
			'titre', 'descriptif', 'texte'
		),
		'site' => array(
			'nom_site', 'url_site', 'descriptif'
		),
		'auteur' => array(
			'nom', 'bio', 'email', 'nom_site', 'url_site', 'login'
		),
		'forum' => array(
			'titre', 'texte', 'auteur', 'email_auteur', 'nom_site', 'url_site'
		),
		'document' => array(
			'titre', 'descriptif'
		)
	);
}


// Effectue une recherche sur toutes les tables de la base de donnees
// options :
// - toutvoir pour eviter autoriser(voir)
// - flags pour eviter les flags regexp par defaut (UimsS)
// - champs pour retourner les champs concernes
// - score pour retourner un score
// http://doc.spip.org/@recherche_en_base
function recherche_en_base($recherche='', $tables=NULL, $options=array()) {
	if (!is_array($tables))
		$tables = liste_des_champs();

	include_spip('inc/autoriser');

	// options par defaut
	$options = array_merge(array(
		'preg_flags' => 'UimsS',
		'toutvoir' => false,
		'champs' => false,
		'score' => false,
		'matches' => false
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

	foreach ($tables as $table => $champs) {
		$a = array();
		$_id_table = id_table_objet($table);

		// Recherche par identifiant
		if (preg_match(',^[0-9]+$,', $recherche))
			$a[] = $_id_table.' = '.$recherche;

		// Recherche fulltext
		foreach ($champs as $champ)
			$a[] = $champ.' '.$methode.' '.$q;
		$s = spip_query(
			'SELECT '.$_id_table.','
			.join(',', $champs)
			.' FROM spip_'.table_objet($table)
			.' WHERE ('
			. join (' OR ', $a)
			. ")");

		while ($t = spip_fetch_array($s)) {
			$id = intval($t[$_id_table]);
			if ($options['toutvoir']
			OR autoriser('voir', $table, $id)) {
				// indiquer les champs concernes
				$champs_vus = array();
				$score = 0;
				$matches = array();

				$vu = false;
				if ($recherche == "$id") {
					$vu = true;
					if ($options['champs'])
						$champs_vus[$_id_table] = $id;
				}

				foreach ($champs as $champ) {
					if ($n = 
						($options['score'] || $options['matches'])
						? preg_match_all($preg, $t[$champ], $regs, PREG_SET_ORDER)
						: preg_match($preg, $t[$champ])
					) {
						$vu = true;

						if ($options['champs'])
							$champs_vus[$champ] = $t[$champ];
						if ($options['score'])
							$score += $n; // * valeur du champ
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
