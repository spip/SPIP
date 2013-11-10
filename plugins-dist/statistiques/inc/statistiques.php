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

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Calculer la moyenne glissante sur un nombre d'echantillons donnes
 * @param int|bool $valeur
 * @param int $glisse
 * @return float
 */
function moyenne_glissante($valeur = false, $glisse=0) {
	static $v = array();
	// pas d'argument, raz de la moyenne
	if ($valeur === false) {
		$v = array();
		return 0;
	}

	// argument, on l'ajoute au tableau...
	// surplus, on enleve...
	$v[] = $valeur;
	if (count($v) > $glisse)
		array_shift($v);

	return round(statistiques_moyenne($v),2);
}

/**
 * Calculer la moyenne d'un tableau de valeurs
 *
 * http://doc.spip.org/@statistiques_moyenne
 *
 * @param array $tab
 * @return float
 */
function statistiques_moyenne($tab){
	if (!$tab) return 0;
	$moyenne = 0;
	foreach($tab as $v) $moyenne += $v;
	return  $moyenne / count($tab);
}

/**
 * Construire un tableau par popularite
 *   classemnt => id_truc
 * @param string $type
 * @param string $serveur
 * @return array
 */
function classement_populaires($type, $serveur='')
{
	static $classement = array();
	if (isset($classement[$type]))
		return $classement[$type];
	$classement[$type] = Sql::allfetsel(id_table_objet($type, $serveur), table_objet_sql($type, $serveur), "statut='publie' AND popularite > 0", "", "popularite DESC",'','',$serveur);
	$classement[$type] = array_map('reset',$classement[$type]);
	return $classement[$type];
}


?>
