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
		)
	);
}


// Effectue une recherche sur toutes les tables de la base de donnees
function recherche_en_base($recherche='', $tables=NULL) {
	if (!is_array($tables))
		$tables = liste_des_champs();

	$results = array();

	if (!strlen($recherche))
		return array();

	// Si la chaine est inactive, on va utiliser LIKE pour aller plus vite
	if (preg_quote($recherche) == $recherche) {
		$methode = 'LIKE';
		$q = _q(
			"%"
			. str_replace(array('%','_'), array('\%', '\_'), $recherche)
			. "%"
		);
	} else {
		$methode == 'REGEXP';
		$q = _q($recherche);
	}

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
			$results[$table][$t[$_id_table]] = $t;
		}
	}

	return $results;
}


// Effectue une recherche sur toutes les tables de la base de donnees
function remplace_en_base($recherche='', $remplace=NULL, $tables=NULL, $callback=NULL) {
	if (!is_array($tables))
		$tables = liste_des_champs();

}


?>
