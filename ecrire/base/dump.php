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

define('_VERSION_ARCHIVE', '1.3');

include_spip('base/serial');
include_spip('base/auxiliaires');
include_spip('public/interfaces'); // pour table_jointures

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = find_in_path('mes_fonctions.php')) {
	global $dossier_squelettes;
	@include_once(_ROOT_CWD . $f);
}

if (@is_readable(_CACHE_PLUGINS_FCT)){
	// chargement optimise precompile
	include_once(_CACHE_PLUGINS_FCT);
}

function base_dump_meta_name($rub){
	return $meta = "status_dump_$rub_"  . $GLOBALS['visiteur_session']['id_auteur'];
}
function base_dump_dir($meta){
	// determine upload va aussi initialiser l'index "restreint"
	$maindir = determine_upload();
	if (!$GLOBALS['visiteur_session']['restreint'])
		$maindir = _DIR_DUMP;
	$dir = sous_repertoire($maindir, $meta);
	return $dir;
}

/**
 * Lister les tables non exportables par defaut
 * (liste completable par le pipeline lister_tables_noexport
 *
 * @staticvar array $EXPORT_tables_noexport
 * @return array
 */
function lister_tables_noexport(){
	// par defaut tout est exporte sauf les tables ci-dessous
	static $EXPORT_tables_noexport = null;
	if (!is_null($EXPORT_tables_noexport))
		return $EXPORT_tables_noexport;

	$EXPORT_tables_noexport= array(
		'spip_caches', // plugin invalideur
		'spip_resultats', // resultats de recherche ... c'est un cache !
		'spip_referers',
		'spip_referers_articles',
		'spip_visites',
		'spip_visites_articles',
		'spip_versions', // le dump des fragments n'est pas robuste
		'spip_versions_fragments' // le dump des fragments n'est pas robuste
		);

	if (!$GLOBALS['connect_toutes_rubriques']){
		$EXPORT_tables_noexport[]='spip_messages';
		$EXPORT_tables_noexport[]='spip_auteurs_messages';
	}

	//var_dump($EXPORT_tables_noexport);
	$EXPORT_tables_noexport = pipeline('lister_tables_noexport',$EXPORT_tables_noexport);

	return $EXPORT_tables_noexport;
}

/**
 * Lister les tables non importables par defaut
 * (liste completable par le pipeline lister_tables_noexport
 *
 * @staticvar array $IMPORT_tables_noimport
 * @return array
 */
function lister_tables_noimport(){
	static $IMPORT_tables_noimport=null;
	if (!is_null($EXPORT_tables_noexport))
		return $EXPORT_tables_noexport;

	$IMPORT_tables_noimport = array();
	// par defaut tout est importe sauf les tables ci-dessous
	// possibiliter de definir cela tables via la meta
	// compatibilite
	if (isset($GLOBALS['meta']['IMPORT_tables_noimport'])){
		$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
		if (!is_array($IMPORT_tables_noimport)){
			include_spip('inc/meta');
			effacer_meta('IMPORT_tables_noimport');
		}
	}
	$IMPORT_tables_noimport = pipeline('lister_tables_noimport',$IMPORT_tables_noimport);
	return $IMPORT_tables_noimport;
}


/**
 * Lister les tables a ne pas effacer
 * (liste completable par le pipeline lister_tables_noerase
 *
 * @staticvar array $IMPORT_tables_noerase
 * @return array
 */
function lister_tables_noerase(){
	static $IMPORT_tables_noerase=null;
	if (!is_null($IMPORT_tables_noerase))
		return $IMPORT_tables_noerase;

	$IMPORT_tables_noerase = array(
		'spip_meta',
		// par defaut on ne vide pas les stats, car elles ne figurent pas dans les dump
		// et le cas echeant, un bouton dans l'admin permet de les vider a la main...
		'spip_referers',
		'spip_referers_articles',
		'spip_visites',
		'spip_visites_articles'
	);
	$IMPORT_tables_noerase = pipeline('lister_tables_noerase',$IMPORT_tables_noerase);
	return $IMPORT_tables_noerase;
}


/**
 * construction de la liste des tables pour le dump :
 * toutes les tables principales
 * + toutes les tables auxiliaires hors relations
 * + les tables relations dont les deux tables liees sont dans la liste
 *
 * @global <type> $tables_principales
 * @global <type> $tables_auxiliaires
 * @global <type> $tables_jointures
 * @param array $exclude_tables
 * @return array
 */
function base_liste_table_for_dump($exclude_tables = array()){
	$tables_for_dump = array();
	$tables_pointees = array();
	global $tables_principales;
	global $tables_auxiliaires;
	global $tables_jointures;

	// on construit un index des tables de liens
	// pour les ajouter SI les deux tables qu'ils connectent sont sauvegardees
	$tables_for_link = array();
	foreach($tables_jointures as $table => $liste_relations)
		if (is_array($liste_relations))
		{
			$nom = $table;
			if (!isset($tables_auxiliaires[$nom])&&!isset($tables_principales[$nom]))
				$nom = "spip_$table";
			if (isset($tables_auxiliaires[$nom])||isset($tables_principales[$nom])){
				foreach($liste_relations as $link_table){
					if (isset($tables_auxiliaires[$link_table])/*||isset($tables_principales[$link_table])*/){
						$tables_for_link[$link_table][] = $nom;
					}
					else if (isset($tables_auxiliaires["spip_$link_table"])/*||isset($tables_principales["spip_$link_table"])*/){
						$tables_for_link["spip_$link_table"][] = $nom;
					}
				}
			}
		}

	$liste_tables = array_merge(array_keys($tables_principales),array_keys($tables_auxiliaires));
	foreach($liste_tables as $table){
	  //		$name = preg_replace("{^spip_}","",$table);
	  if (		!isset($tables_pointees[$table])
	  		&&	!in_array($table,$exclude_tables)
	  		&&	!isset($tables_for_link[$table])){
			$tables_for_dump[] = $table;
			$tables_pointees[$table] = 1;
		}
	}
	foreach ($tables_for_link as $link_table =>$liste){
		$connecte = true;
		foreach($liste as $connect_table)
			if (!in_array($connect_table,$tables_for_dump))
				$connecte = false;
		if ($connecte)
			# on ajoute les liaisons en premier
			# si une restauration est interrompue,
			# cela se verra mieux si il manque des objets
			# que des liens
			array_unshift($tables_for_dump,$link_table);
	}
	return array($tables_for_dump, $tables_for_link);
}

?>
