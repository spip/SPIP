<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/admin');
include_spip('base/serial');
include_spip('base/auxiliaires');

// par defaut tout est importe sauf les tables ci-dessous
// possibiliter de definir cela tables via la meta
global $IMPORT_tables_noimport;
if (isset($GLOBALS['meta']['IMPORT_tables_noimport']))
	$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
else{
	include_spip('inc/meta');
	$IMPORT_tables_noimport[]='spip_ajax_fonc';
	$IMPORT_tables_noimport[]='spip_caches';
	$IMPORT_tables_noimport[]='spip_meta';
	ecrire_meta('IMPORT_tables_noimport',serialize($IMPORT_tables_noimport));
	ecrire_metas();
}

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = include_spip('mes_fonctions', false)) {
	global $dossier_squelettes;
	@include_once ($f); 
}

function verifier_version_sauvegarde ($archive) {
	global $spip_version;
	global $flag_gz;

	$ok = @file_exists(_DIR_SESSIONS . $archive);
	$gz = $flag_gz;
	$_fopen = ($gz) ? gzopen : fopen;
	$_fread = ($gz) ? gzread : fread;
	$buf_len = 1024; // la version doit etre dans le premier ko

	if ($ok) {
		$f = $_fopen(_DIR_SESSIONS . $archive, "rb");
		$buf = $_fread($f, $buf_len);

		if (ereg("<SPIP [^>]* version_base=\"([0-9\.]+)\" ", $buf, $regs)
			AND $regs[1] == $spip_version)
			return false; // c'est bon
		else
			return _T('avis_erreur_version_archive', array('archive' => $archive));
	} else
		return _T('avis_probleme_archive', array('archive' => $archive));
}

function import_all_check() {

	global $archive;

	// cas de l'appel apres demande de confirmation
	if ($archive) {
			$action = _T('info_restauration_sauvegarde', array('archive' => $archive));
			$commentaire = verifier_version_sauvegarde ($archive);
		}

	// au tout premier appel, on ne revient pas de cette fonction
	debut_admin(generer_url_post_ecrire("import_all","archive=$archive"), $action, $commentaire);

	// on est revenu: l'authentification ftp est ok
	fin_admin($action);
	// dire qu'on commence
	ecrire_meta("request_restauration", serialize($_REQUEST));
	ecrire_meta("debut_restauration", "debut");
	ecrire_meta("status_restauration", "0");
	ecrire_metas();
	// se rappeler pour montrer illico ce qu'on fait 
	header('Location: ./');
	exit();
}

function exec_import_all_dist()
{
	// si l'appel est explicite, passer par l'authentification ftp
	if (!$GLOBALS['meta']["debut_restauration"])
		import_all_check();

	// construction de la liste des tables pour le dump :
	// toutes les tables principales
	// + toutes les tables auxiliaires hors relations
	// + les tables relations dont les deux tables liees sont dans la liste
	$tables_for_dump = array();
	$tables_pointees = array();
	global $IMPORT_tables_noimport;
	global $tables_principales;
	global $tables_auxiliaires;
	global $table_des_tables;
	global $tables_jointures;

	// on construit un index des tables de liens
	// pour les ajouter SI les deux tables qu'ils connectent sont sauvegardees
	$tables_for_link = array();
	foreach($tables_jointures as $table=>$liste_relations)
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
		$name = preg_replace("{^spip_}","",$table);
		if (		!isset($tables_pointees[$table]) 
				&&	!in_array($table,$IMPORT_tables_noimport)
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
			# si une restauration est interrompue, cela se verra mieux si il manque des objets
			# que des liens
			array_unshift($tables_for_dump,$link_table);
	}
	
	// puis commencer ou continuer
	include_spip('inc/import');
	import_all_continue($tables_for_dump);
}
?>
