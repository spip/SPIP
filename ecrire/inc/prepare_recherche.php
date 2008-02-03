<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/rechercher');
@define('_DELAI_CACHE_RECHERCHES',600);

// Preparer les listes id_article IN (...) pour les parties WHERE
// et points =  des requetes du moteur de recherche
// http://doc.spip.org/@inc_prepare_recherche_dist
function inc_prepare_recherche_dist($recherche, $table='articles', $cond=false, $serveur='') {
	static $cache = array();

	// si recherche n'est pas dans le contexte, on va prendre en globals
	// ca permet de faire des inclure simple.
	if (!isset($recherche) AND isset($GLOBALS['recherche']))
		$recherche = $GLOBALS['recherche'];

	// traiter le cas {recherche?}
	if ($cond AND !strlen($recherche))
		return array("''" /* as points */, /* where */ '1');


	$rechercher = false;

	if (!isset($cache[$recherche][$table])){
		$hash = substr(md5($recherche . $table),0,16);
		$res = sql_select('UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(maj) AS fraicheur','spip_recherches',"recherche='$hash'",'','fraicheur DESC','0,1','',$serveur);
		if ((!$row = sql_fetch($res))
		 OR ($row['fraicheur']>_DELAI_CACHE_RECHERCHES)){
		 	$rechercher = true;
		}
		$cache[$recherche][$table] = array("points","(recherche='$hash')");
	}

	// si on n'a pas encore traite les donnees dans une boucle precedente
	if ($rechercher) {
		//$tables = liste_des_champs();
		$x = preg_replace(',s$,', '', $table); // eurk
		if ($x == 'syndic') $x = 'site';
		$points = recherche_en_base($recherche,
			$x,
			array(
				'score' => true,
				'toutvoir' => true,
				'jointures' => true
				),
					    $serveur);
		$points = $points[$x];

		# Pour les forums, unifier par id_thread et forcer statut='publie'
		if ($x == 'forum' AND $points) {
			$p2 = array();
			$s = sql_select("id_thread, id_forum", "spip_forum", "statut='publie' AND ".sql_in('id_forum', array_keys($points)), '','','','', $serveur);
			while ($t = sql_fetch($s, $serveur))
				$p2[intval($t['id_thread'])]['score']
					+= $points[intval($t['id_forum'])]['score'];
			$points = $p2;
		}

		// supprimer les anciens resultats de cette recherche et les resultats trop vieux avec une marge
		// hash=0x$hash OR HEX(hash)='$hash' permet d'avoir une requete qui marche qu'on soit en mysql <4.1 ou >4.1
		// il y a des versions ou install de mysql ou il faut l'un ou l'autre selon le hash ... !
		sql_delete('spip_recherches','(maj<DATE_SUB(NOW(), INTERVAL '.(_DELAI_CACHE_RECHERCHES+100)." SECOND)) OR (recherche='$hash')",$serveur);

		// inserer les resultats dans la table de cache des recherches
		if (count($points)){
			$values = "";
			foreach ($points as $id => $p){
				$values.= ",('$hash',".intval($id).",".intval($p['score']).")";
				if (strlen($values)>16000) { // eviter les debordements de pile sur tres gros resultats
					sql_insert('spip_recherches',"(recherche,id,points)",substr($values,1),array(),$serveur);
					$values = "";
				}
			}
			sql_insert('spip_recherches',"(recherche,id,points)",substr($values,1),array(),$serveur);
		}
	}

	return $cache[$recherche][$table];
}



?>
