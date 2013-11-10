<?php

/**
 * Déclarations des configurations qui peuvent être sauvegardées
 * 
 * @package SPIP\Compresseur\Pipelines
**/
if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Ajoute les metas sauvegardables du Compresseur pour le plugin IEConfig
 *
 * @pipeline ieconfig_metas
 *
 * @param array $table
 *     Déclaration des sauvegardes
 * @return array 
 *     Déclaration des sauvegardes complétées
**/
function compresseur_ieconfig_metas($table){
	$table['compresseur']['titre'] = _T('compresseur:info_compresseur_titre');
	$table['compresseur']['icone'] = 'compresseur-16.png';
	$table['compresseur']['metas_brutes'] = 'auto_compress_http,auto_compress_js,auto_compress_css,auto_compress_closure';
	
	return $table;
}

?>
