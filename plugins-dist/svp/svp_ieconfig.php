<?php

/**
 * Déclarations des configurations qui peuvent être sauvegardées
 * 
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Pipelines
**/

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Ajoute les metas sauvegardables de SVP pour le plugin IEConfig
 *
 * @pipeline ieconfig_metas
 *
 * @param array $table
 *     Déclaration des sauvegardes
 * @return array 
 *     Déclaration des sauvegardes complétées
**/
function svp_ieconfig_metas($table){
	$table['svp']['titre'] = _T('svp:titre_page_configurer');
	$table['svp']['icone'] = 'svp-16.png';
	$table['svp']['metas_serialize'] = 'svp';

	return $table;
}

?>
