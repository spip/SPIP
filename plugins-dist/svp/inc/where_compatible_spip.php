<?php

/**
 * Gestion des recherches de plugins par version ou branche
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Recherche
**/

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Construit le WHERE d'une requête SQL de selection des plugins ou paquets
 * compatibles avec une version ou une branche de spip.
 * 
 * Cette fonction est appelée par le critère {compatible_spip}
 * 
 * @used-by svp_compter()
 * @used-by critere_compatible_spip_dist()
 *
 * @param string $version
 *     Numéro de version de SPIP, tel que 2.0.8
 * @param string $table
 *     Table d'application ou son alias SQL
 * @param string $op
 *     Opérateur de comparaison, tel que '>' ou '='
 * @return string
 *     Expression where de la requête SQL
 */
function inc_where_compatible_spip($version='', $table, $op) {

	// le critere s'applique a une VERSION (1.9.2, 2.0.8, ...)
	if (count(explode('.', $version)) == 3) {
		$min = 'SUBSTRING_INDEX(' . $table . '.compatibilite_spip, \';\', 1)';
		$max = 'SUBSTRING_INDEX(' . $table . '.compatibilite_spip, \';\', -1)';

		$where = 'CASE WHEN '.$min.' = \'\'
	OR '.$min.' = \'[\'
	THEN \'1.9.0\' <= \''.$version.'\'
	ELSE TRIM(LEADING \'[\' FROM '.$min.') <= \''.$version.'\'
	END
AND
	CASE WHEN '.$max.' = \'\'
	OR '.$max.' = \']\'
	THEN \'99.99.99\' >= \''.$version.'\'
	WHEN '.$max.' = \')\'
	OR '.$max.' = \'[\'
	THEN \'99.99.99\' > \''.$version.'\'
	WHEN RIGHT('.$max.', 1) = \')\'
	OR RIGHT('.$max.', 1) = \'[\'
	THEN LEFT('.$max.', LENGTH('.$max.') - 1) > \''.$version.'\'
	ELSE LEFT('.$max.', LENGTH('.$max.') - 1) >= \''.$version.'\'
	END';
	}
	// le critere s'applique a une BRANCHE (1.9, 2.0, ...)
	elseif (count(explode('.', $version)) == 2) {
		$where = 'LOCATE(\''.$version.'\', '.$table.'.branches_spip) '.$op.' 0';
	}
	// le critere est vide ou mal specifie
	else {
		$where = '1=1';
	}

	return $where;
}
?>
