<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/actions');
include_spip('base/abstract_sql');

function filtre_objets_associes_mot_dist($id_mot,$id_groupe) {
	static $occurences = array();

	// calculer tous les liens du groupe d'un coup
	if (!isset ($occurences[$id_groupe]))
		$occurrences[$id_groupe] = calculer_utilisations_mots($id_groupe);

	$associes = array();
	foreach (array('article','breve','site','rubrique') as $type) {
		$table = table_objet($type);
		$nb = (isset($occurrences[$id_groupe][$table][$id_mot]) ? $occurrences[$id_groupe][$table][$id_mot] : 0);
		if ($nb)
			$associes[] = singulier_ou_pluriel ($nb, "info_1_$type", "info_nb_{$type}s");
	}

	$associes = pipeline('afficher_nombre_objets_associes_a',array('args'=>array('objet'=>'mot','id_objet'=>$id_mot),'data'=>$associes));
	return $associes;

}

/**
 * Calculer les nombres d'elements (articles, etc.) lies a chaque mot
 *
 * @param int $id_groupe
 * @return array
 */
function calculer_utilisations_mots($id_groupe)
{
	$aff_articles = sql_in('O.statut',  ($GLOBALS['connect_statut'] =="0minirezo")  ? array('prepa','prop','publie') : array('prop','publie'));

	$res = sql_allfetsel("COUNT(*) AS cnt, L.id_mot", "spip_mots_articles AS L LEFT JOIN spip_mots AS M ON L.id_mot=M.id_mot LEFT JOIN spip_articles AS O ON L.id_article=O.id_article", "M.id_groupe=$id_groupe AND $aff_articles", "L.id_mot");
	$articles = array();
	foreach($res as $row) $articles[$row['id_mot']] = $row['cnt'];

	$rubriques = array();
	$res = sql_allfetsel("COUNT(*) AS cnt, L.id_mot", "spip_mots_rubriques AS L LEFT JOIN spip_mots AS M ON L.id_mot=M.id_mot", "M.id_groupe=$id_groupe", "L.id_mot");
	foreach($res as $row) $rubriques[$row['id_mot']] = $row['cnt'];
  
	$breves = array();
	$res = sql_allfetsel("COUNT(*) AS cnt, L.id_mot", "spip_mots_breves AS L LEFT JOIN spip_mots AS M ON L.id_mot=M.id_mot LEFT JOIN spip_breves AS O ON L.id_breve=O.id_breve", "M.id_groupe=$id_groupe AND $aff_articles", "L.id_mot");
	foreach($res as $row) $breves[$row['id_mot']] = $row['cnt'];

	$syndic = array(); 
	$res = sql_allfetsel("COUNT(*) AS cnt, L.id_mot", "spip_mots_syndic AS L LEFT JOIN spip_mots AS M ON L.id_mot=M.id_mot LEFT JOIN spip_syndic AS O ON L.id_syndic=O.id_syndic", "M.id_groupe=$id_groupe AND $aff_articles", "L.id_mot");
	foreach($res as $row) $syndic[$row['id_mot']] = $row['cnt'];

	return array('articles' => $articles, 
		'breves' => $breves, 
		'rubriques' => $rubriques, 
		'syndic' => $syndic);
}
?>
