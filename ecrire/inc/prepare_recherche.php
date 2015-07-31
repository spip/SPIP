<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/rechercher');
if (!defined('_DELAI_CACHE_resultats')) define('_DELAI_CACHE_resultats', 600);

/**
 * Preparer les listes id_article IN (...) pour les parties WHERE
 * et points =  des requetes du moteur de recherche
 * http://doc.spip.org/@inc_prepare_recherche_dist
 * 
 * Le parametre $serveur est utilise pour savoir sur quelle base on cherche
 * mais l'index des resultats est toujours stock� sur le serveur principal
 * car on ne sait pas si la base distante dispose d'une table spip_resultats
 * ni meme si on aurait le droit d'ecrire dedans
 *
 * @param string $recherche
 *    chaine recherchee
 * @param string $table
 *    table dans laquelle porte la recherche
 * @param bool $cond
 *    critere conditionnel sur {recherche?}
 * @param string $serveur
 *    serveur de base de donnees
 * @param array $modificateurs
 *    modificateurs de boucle, ie liste des criteres presents
 * @param string $primary
 *    cle primaire de la table de recherche
 * @return array
 */
function inc_prepare_recherche_dist($recherche, $table='articles', $cond=false, $serveur='', $modificateurs = array(), $primary='') {
	static $cache = array();
	$delai_fraicheur = min(_DELAI_CACHE_resultats,time()-$GLOBALS['meta']['derniere_modif']);

	// si recherche n'est pas dans le contexte, on va prendre en globals
	// ca permet de faire des inclure simple.
	if (!isset($recherche) AND isset($GLOBALS['recherche']))
		$recherche = $GLOBALS['recherche'];

	// traiter le cas {recherche?}
	if ($cond AND !strlen($recherche))
		return array("0 as points" /* as points */, /* where */ '');
		
	
	$rechercher = false;

	if (!isset($cache[$serveur][$table][$recherche])){
		$hash_serv = ($serveur?substr(md5($serveur),0,16):'');
		$hash = substr(md5($recherche . $table),0,16);
		$where = "(resultats.recherche='$hash' AND resultats.table_objet=".sql_quote($table)." AND resultats.serveur='$hash_serv')";
		$row = sql_fetsel('UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(resultats.maj) AS fraicheur','spip_resultats AS resultats',$where,'','fraicheur DESC','0,1');
		if (!$row
		  OR ($row['fraicheur']>$delai_fraicheur)
		  OR (defined('_VAR_MODE') AND _VAR_MODE=='recalcul')){
		 	$rechercher = true;
		}
	}

	// si on n'a pas encore traite les donnees dans une boucle precedente
	if ($rechercher) {
		//$tables = liste_des_champs();
		$x = objet_type($table);
		$points = recherche_en_base($recherche,
			$x,
			array(
				'score' => true,
				'toutvoir' => true,
				'jointures' => true
				),
			$serveur);
		// pas de résultat, pas de point
		$points = isset($points[$x]) ? $points[$x] : array();

		// permettre aux plugins de modifier le resultat
		$points = pipeline('prepare_recherche',array(
			'args'=>array('type'=>$x,'recherche'=>$recherche,'serveur'=>$serveur,'modificateurs'=>$modificateurs),
			'data'=>$points
		));

		// supprimer les anciens resultats de cette recherche
		// et les resultats trop vieux avec une marge
		// pas de AS resultats dans un delete (mysql)
		$whered = str_replace(array("resultats.recherche","resultats.table_objet","resultats.serveur"),array("recherche","table_objet","serveur"),$where);
		sql_delete('spip_resultats', 'NOT(' .sql_date_proche('maj', (0-($delai_fraicheur+100)), " SECOND") . ") OR ($whered)");

		// inserer les resultats dans la table de cache des resultats
		if (count($points)){
			$tab_couples = array();
			foreach ($points as $id => $p){
				$tab_couples[] = array(
					'recherche' => $hash,
					'id' => $id,
					'points' => $p['score'],
					'table_objet' => $table,
					'serveur' => $hash_serv,
				);
			}
			sql_insertq_multi('spip_resultats',$tab_couples,array());
		}
	}

	if (!isset($cache[$serveur][$table][$recherche])){
		if (!$serveur)
			$cache[$serveur][$table][$recherche] = array("resultats.points AS points",$where);
		else {
			if (sql_countsel('spip_resultats as resultats',$where))
			$rows = sql_allfetsel('resultats.id,resultats.points','spip_resultats as resultats',$where);
			$cache[$serveur][$table][$recherche] = generer_select_where_explicites($table, $primary, $rows, $serveur);
		}
	}

	return $cache[$serveur][$table][$recherche];
}


/**
 * Generer le select et where qui contiennent explicitement
 * les id et points (ie comme dans SPIP 1.9.x)
 * quand on fait une recherche sur une table externe
 *
 * @param string $table
 * @param string $primary
 * @param array $rows
 * @param string $serveur
 * @return array
 */
function generer_select_where_explicites($table, $primary, $rows, $serveur){
	# calculer le {id_article IN()} et le {... as points}
	if (!count($rows)){
		return array("''", "0=1");
	}
	else {
		$listes_ids = array();
		$select = '0';
		foreach ($rows as $r)
			$listes_ids[$r['points']][] = $r['id'];

		foreach ($listes_ids as $p => $ids)
			$select .= "+$p*(".
			           sql_in("$table.$primary", $ids,'',$serveur)
			           .") ";

		return array("$select AS points ",calcul_mysql_in("$table.$primary",array_map('reset',$rows),'',$serveur));
	}
}


?>
