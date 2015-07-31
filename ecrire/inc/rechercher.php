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
// http://doc.spip.org/@recherche_en_base
function recherche_en_base($recherche='', $tables=NULL, $options=NULL) {
	if (!is_array($tables))
		$tables = liste_des_champs();

	include_spip('inc/autoriser');

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

	if (!isset($options['preg_flags']))
		$options['flags'] = 'UimsS';
	$preg = '/'.$recherche.'/' . $options['flags'];

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
			$id = $t[$_id_table];
			if ($options['toutvoir'] OR autoriser('voir', $table, $id)) {
				// indiquer les champs concernes
				$vus = array();
				foreach ($champs as $champ) {
					if (preg_match($preg, $t[$champ]))
						$vus[$champ] = $t[$champ];
				}
				// et l'id, si besoin
				if ($vus OR $recherche == "$id")
					$results[$table][$id] = array_merge(array($_id_table => $id), $vus);
			}
		}
	}

	return $results;
}


// Effectue une recherche sur toutes les tables de la base de donnees
// http://doc.spip.org/@remplace_en_base
function remplace_en_base($recherche='', $remplace=NULL, $tables=NULL, $options=array()) {
	include_spip('inc/modifier');

	if (!is_array($tables))
		$tables = liste_des_champs();

	$results = recherche_en_base($recherche, $tables, $options);

	if (!isset($options['preg_flags']))
		$options['flags'] = 'UimsS';
	$preg = '/'.$recherche.'/' . $options['flags'];

	foreach ($results as $table => $r) {
		foreach ($r as $id => $x) {
			if ($options['toutmodifier']
			OR autoriser('modifier', $table, $id)) {
				$modifs = array();
				foreach ($x as $key => $val) {
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
