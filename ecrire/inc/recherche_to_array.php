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


// methodes sql
function inc_recherche_to_array_dist($recherche, $options = array()) {

	$options = array_merge(
		array(
		'score' => true,
		'champs' => false,
		'matches' => false
		), $options);

	include_spip('inc/rechercher');
	include_spip('inc/autoriser');

	$requete = array(
	"SELECT"=>array(),
	"FROM"=>array(),
	"WHERE"=>array(),
	"GROUPBY"=>array(),
	"ORDERBY"=>array(),
	"LIMIT"=>"",
	"HAVING"=>array()
	);

	$table = sinon($options['table'], 'article');
	$serveur = $options['serveur'];

	list($methode, $q, $preg) = expression_recherche($recherche, $options);

	$l = liste_des_champs();
	$champs = $l[$table];

	$jointures = $options['jointures']
		? liste_des_jointures()
		: array();

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

	$r = array();

	$s = sql_select(
		$requete['SELECT'], $requete['FROM'], $requete['WHERE'],
		implode(" ",$requete['GROUPBY']),
		$requete['ORDERBY'], $requete['LIMIT'],
		$requete['HAVING'], $serveur
	);

	while ($t = sql_fetch($s,$serveur)
	AND (!isset($t['score']) OR $t['score']>0)) {
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
				$r[$id] = array();
				if ($champs_vus)
					$r[$id]['champs'] = $champs_vus;
				if ($score)
					$r[$id]['score'] = $score;
				if ($matches)
					$r[$id]['matches'] = $matches;
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
			$it = id_table_objet($table);
			$ij =  id_table_objet($jtable);
			if (in_array($jtable, array('auteur', 'document', 'mot')))
				$s = sql_select("id_objet as $it, $ij", "spip_${jtable}s_liens", array("objet='$table'",sql_in('id_'.${jtable}, array_keys($jj))), '','','','',$serveur);
			else
				$s = sql_select("$it,$ij", "spip_${jtable}s_${table}s", sql_in('id_'.${jtable}, array_keys($jj)), '','','','',$serveur);
			while ($t = sql_fetch($s,$serveur)) {
				$id = $t[$it];
				$joint = $jj[$t[$ij]];
				if (!isset($r))
					$r = array();
				if (!isset($r[$id]))
					$r[$id] = array();
				if ($joint['score'])
					$r[$id]['score'] += $joint['score'];
				if ($joint['champs'])
				foreach($joint['champs'] as $c => $val)
					$r[$id]['champs'][$jtable.'.'.$c] = $val;
				if ($joint['matches'])
				foreach($joint['matches'] as $c => $val)
					$r[$id]['matches'][$jtable.'.'.$c] = $val;
			}
		}
	}

	return $r;
}


?>
