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

include_spip('inc/rechercher');

// Preparer les listes id_article IN (...) pour les parties WHERE
// et points =  des requetes du moteur de recherche
// http://doc.spip.org/@inc_prepare_recherche_dist
function inc_prepare_recherche_dist($recherche, $table='articles', $cond=false) {
	static $cache = array();
	static $fcache = array();

	// si recherche n'est pas dans le contexte, on va prendre en globals
	// ca permet de faire des inclure simple.
	if (!isset($recherche) AND isset($GLOBALS['recherche']))
		$recherche = $GLOBALS['recherche'];

	// traiter le cas {recherche?}
	if ($cond AND !strlen($recherche))
		return array("''" /* as points */, /* where */ '1');

	// Premier passage : chercher eventuel un cache des donnees sur le disque
	if (!$cache[$recherche]['hash']) {
		$dircache = sous_repertoire(_DIR_CACHE,'rech');
		$fcache[$recherche] =
			$dircache . substr(md5($recherche),0,10).'.txt';
		if (lire_fichier($fcache[$recherche], $contenu))
			$cache[$recherche] = @unserialize($contenu);
	}


	// si on n'a pas encore traite les donnees dans une boucle precedente
	if (!$cache[$recherche][$table]) {

		$tables = liste_des_champs();
		$x = preg_replace(',s$,', '', $table); // eurk
		if ($x == 'syndic') $x = 'site';
		$points = recherche_en_base($recherche,
			$x,
			array(
				'score' => true,
				'toutvoir' => true,
				'jointures' => true
			));
		$points = $points[$x];

		# Pour les forums, unifier par id_thread et forcer statut='publie'
		if ($x == 'forum' AND $points) {
			$p2 = array();
			$s = sql_select("id_thread, id_forum", "spip_forum", "statut='publie' AND ".sql_in('id_forum', array_keys($points)));
			while ($t = sql_fetch($s))
				$p2[intval($t['id_thread'])]['score']
					+= $points[intval($t['id_forum'])]['score'];
			$points = $p2;
		}

		# calculer le {id_article IN()} et le {... as points}
		if (!count($points)) {
			$cache[$recherche][$table] = array("''", '0');
		} else {
			$listes_ids = array();
			$primary = id_table_objet($table);
			foreach ($points as $id => $p)
				$listes_ids[$p['score']] .= ','.$id;
			$select = '';
			foreach ($listes_ids as $p => $liste_ids)
				$select .= "+ (case when (".
					sql_in("$table.$primary", substr($liste_ids, 1))
					.") then $p else 0 end) ";

			$select = $select ? substr($select,1) : '0';
			$cache[$recherche][$table] = array($select,
				'('.sql_in("$table.$primary",
					array_keys($points)).')'
				);
		}

		// ecrire le cache de la recherche sur le disque
		ecrire_fichier($fcache[$recherche], serialize($cache[$recherche]));
		// purger le petit cache
		nettoyer_petit_cache('rech', 300);
	}

	return $cache[$recherche][$table];
}



?>
