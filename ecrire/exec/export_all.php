<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * 
 * On arrive ici depuis exec=admin_tech
 * - le premier coup on initialise par exec_export_all_args puis export_all_start
 * - ensuite on enchaine sur inc/export, qui remplit le dump et renvoie ici a chaque timeout
 * - a chaque coup on relance inc/export
 * - lorsque inc/export a fini, il retourne $arg
 * - on l'utilise pour clore le fichier
 * - on renvoie
 *   vers action=export_all pour afficher le resume
 * 
 */

include_spip('inc/presentation');
include_spip('base/dump');

// http://doc.spip.org/@exec_export_all_dist
function exec_export_all_dist(){
	$rub = intval(_request('id_parent'));
	$meta = base_dump_meta_name($rub);
	utiliser_langue_visiteur();
	if (!isset($GLOBALS['meta'][$meta])){
		// c'est un demarrage en arrivee directe depuis exec=admin_tech
		// on initialise  (mais si c'est le validateur, ne rien faire)
		if ($GLOBALS['exec'] == 'valider_xml') return;
		$gz = _request('gz');
		$archive = exec_export_all_args($rub, $gz);
		$tables = export_all_start($meta, $archive, $rub, _request('export'));
		ecrire_meta($meta, serialize(array($gz, $archive, $rub, $tables, 1, 0)), 'non');
		// rub=$rub sert AUSSI a distinguer cette redirection
		// d'avec l'appel initial sinon FireFox croit malin
		// d'optimiser la redirection
		$url = generer_url_ecrire('export_all',"rub=$rub", true);
	} else {
		// appels suivants
		$export = charger_fonction('export', 'inc');
		$arg = $export($meta);
		// Si retour de $export c'est fini; dernier appel pour ramasser
		// et produire l'en tete du fichier a partir de l'espace public
		$url = generer_action_auteur("export_all",$arg,'',true, true, true);
	}
	include_spip('inc/headers');
	redirige_par_entete($url);
}

function exec_export_all_args($rub, $gz){

	$gz = $gz ? '.gz' : '';
	$nom = $gz 
	?  _request('znom_sauvegarde') 
	:  _request('nom_sauvegarde');

	if (!preg_match(',^[\w_][\w_.]*$,', $nom)) $nom = 'dump';
	return $nom . '.xml' . $gz;
}

// Ici on construit la liste des tables pour confirmation.

function export_all_start($meta, $archive, $rub, $tables){

	// si pas de tables listees en post, utiliser la liste par defaut
	if (!$tables)
		list($tables,) = base_liste_table_for_dump(lister_tables_noexport());

	// en mode partiel, commencer par les articles et les rubriques
	// pour savoir quelles parties des autres tables sont a sauver
	if ($rub) {
		if ($t = array_search('spip_rubriques', $tables)) {
			unset($tables[$t]);
			array_unshift($tables, 'spip_rubriques');
		}
		if ($t = array_search('spip_articles', $tables)) {
			unset($tables[$t]);
			array_unshift($tables, 'spip_articles');
		}
	}
	return $tables;
}

?>
