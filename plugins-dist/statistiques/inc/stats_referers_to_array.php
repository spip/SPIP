<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/statistiques');
// moyenne glissante sur 30 jours
define('MOYENNE_GLISSANTE_JOUR', 30);
// moyenne glissante sur 12 mois
define('MOYENNE_GLISSANTE_MOIS', 12);
include_spip('inc/referenceurs');

function inc_stats_referers_to_array_dist($limit, $jour, $id_article, $options = array()) {

	$visites = 'visites';
	$table = "spip_referers";
	$where = array();
	$serveur = '';

	if (in_array($jour,array('jour','veille'))){
		$visites .= "_$jour";
		$where[] = "$visites>0";
	}
	//$res = $referenceurs (0, "SUM(visites_$jour)", 'spip_referers', "visites_$jour>0", "referer", $limit);

	if ($id_article) {
			$table = "spip_referers_articles";
			$where[] = "id_article=".intval($id_article);
	}

	$where = implode(" AND ",$where);
	$limit = $limit?"0,".intval($limit):'';

	$result = Sql::select("referer_md5, referer, $visites AS vis", $table, $where, '', "maj DESC", $limit,'',$serveur);

	$referers = array();
	$trivisites = array(); // pour le tri
	while ($row = Sql::fetch($result,$serveur)) {
		$referer = interdire_scripts($row['referer']);
		$buff = stats_show_keywords($referer, $referer);

		if ($buff["host"]) {
			$refhost = $buff["hostname"];
			$visites = $row['vis'];
			$host = $buff["scheme"]."://".$buff["host"];
			
			$referers[$refhost]['referer_md5'] = $row['referer_md5'];

			if (!isset($referers[$refhost]['liens'][$referer]))
				$referers[$refhost]['liens'][$referer] = 0;
			if (!isset($referers[$refhost]['hosts'][$host]))
				$referers[$refhost]['hosts'][$host] = 0;

			if (!isset($referers[$refhost]['visites']))
				$referers[$refhost]['visites'] = 0;
			if (!isset($referers[$refhost]['visites_racine']))
				$referers[$refhost]['visites_racine'] = 0;
			if (!isset($referers[$refhost]['referers']))
				$referers[$refhost]['referers'] = array();

			$referers[$refhost]['hosts'][$host]++;
			$referers[$refhost]['liens'][$referer]++ ;
			$referers[$refhost]['visites'] += $visites;
			$trivisites[$refhost] = $referers[$refhost]['visites'];

			$tmp = "";
			$set = array(
				'referer'=>$referer,
				'visites'=>$visites,
				'referes'=>$id_article?'':referes($row['referer_md5'])
			);
			if (isset($buff["keywords"])
			  AND  $c = $buff["keywords"]) {
				if (!isset($referers[$refhost]['keywords'][$c])) {
					$referers[$refhost]['keywords'][$c] = true;
					$set['keywords'] = $c;
				}
			}
			else {
				$tmp = $buff["path"];
				if ($buff["query"])
					$tmp .= "?".$buff['query'];
				if (strlen($tmp))
					$set['path'] = "/$tmp";
			}
			if (isset($set['path']) OR isset($set['keywords']))
				$referers[$refhost]['referers'][] = $set;
			else
				$referers[$refhost]['visites_racine'] += $visites;
		}
	}

	// trier les liens pour trouver le principal
	foreach($referers as $k=>$r){
		arsort($referers[$k]['liens']);
		$referers[$k]['liens'] = array_keys($referers[$k]['liens']);
		arsort($referers[$k]['hosts']);
		$referers[$k]['hosts'] = array_keys($referers[$k]['hosts']);
		$referers[$k]['url'] = reset($referers[$k]['hosts']);
	}

	if (count($trivisites)){
		array_multisort($trivisites, SORT_DESC,$referers);
	}
	
	return $referers;
}


?>