<?php

/**
 * Déclarations des configurations qui peuvent être sauvegardées
 * 
 * @plugin Porte Plume pour SPIP
 * @license GPL
 * @package SPIP\PortePlume\Pipelines
**/

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Ajoute les metas sauvegardables du porte plume pour le plugin IEConfig
 *
 * @pipeline ieconfig_metas
 *
 * @param array $table
 *     Déclaration des sauvegardes
 * @return array 
 *     Déclaration des sauvegardes complétées
**/
function porte_plume_ieconfig_metas($table){
	$table['porte_plume']['titre'] = _T('barreoutils:info_barre_outils_public');
	$table['porte_plume']['icone'] = 'porte-plume-16.png';
	$table['porte_plume']['metas_brutes'] = 'barre_outils_public';

	return $table;
}

?>
