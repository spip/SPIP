<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
@ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure

include_spip('base/serial');
include_spip('base/auxiliaires');
include_spip('public/interfaces'); // pour table_des_tables
include_spip('inc/flock');
include_spip('inc/actions');
include_spip('inc/export');

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = include_spip('mes_fonctions', false)) {
	global $dossier_squelettes;
	@include_once ($f); 
}
if (@is_readable(_DIR_TMP."charger_plugins_fonctions.php")){
	// chargement optimise precompile
	include_once(_DIR_TMP."charger_plugins_fonctions.php");
}

// par defaut tout est exporte sauf les tables ci-dessous

global $EXPORT_tables_noexport;

if (!isset($EXPORT_tables_noexport)){
	$EXPORT_tables_noexport= array(
		'spip_caches',
		'spip_index',
		'spip_index_dico',
		'spip_referers',
		'spip_referers_articles',
		'spip_visites',
		'spip_visites_articles',
		'spip_ortho_cache',
		'spip_ortho_dico'
		);
	if (!$GLOBALS['connect_toutes_rubriques']){
		$EXPORT_tables_noexport[]='spip_messages';
		$EXPORT_tables_noexport[]='spip_auteurs_messages';
	}
}
$GLOBALS['flag_ob_flush'] = function_exists('ob_flush');


// http://doc.spip.org/@exec_export_all_dist
function exec_export_all_dist()
{
	global $connect_toutes_rubriques;
	$start = false;

	if ($connect_toutes_rubriques AND file_exists(_DIR_DUMP))
		$dir = _DIR_DUMP;
	else $dir = determine_upload();

	if (!is_writable($dir)) {
		include_spip('inc/headers');
		$dir = preg_replace(",^" . _DIR_RACINE .",", '', $dir);
		redirige_par_entete(generer_url_action("test_dirs", "test_dir=$dir", true));
	}

	// utiliser une version fraiche des metas (ie pas le cache)
	include_spip('inc/meta');
	lire_metas();

	if (!isset($GLOBALS['meta']["status_dump"])) {
		$gz = _request('gz');
		$archive = export_nom_fichier_dump($dir,$gz);

		//  creer l'en tete du fichier a partir de l'espace public
		include_spip('inc/headers');
		redirige_par_entete(generer_action_auteur("export_all", "start,$gz,$archive", '', true));
	} 

	list($gz, $archive, $etape_actuelle, $sous_etape) = 
	  explode("::",$GLOBALS['meta']["status_dump"]);

	$file = $dir . $archive;
	$redirect = generer_url_ecrire("export_all");

	if (!$etape_actuelle AND !$sous_etape) {
		$l = preg_files($file .  ".part_[0-9]+_[0-9]+");
		if ($l) {
			spip_log("menage d'une sauvegarde inachevee: " . join(',', $l));
			foreach($l as $dummy)@unlink($dummy);
		}
		$start = true; //  utilise pour faire un premier hit moitie moins long
	}

	list($tables_for_dump, $tables_for_link) = export_all_list_tables();

	$all = count($tables_for_dump);

	// concatenation des fichiers crees a l'appel precedent
	ramasse_parties($file, $file);

	if ($etape_actuelle > $all){ 
	  // l'appel precedent avait fini le boulot. mettre l'en-pied.
		ecrire_fichier($file, export_enpied(),false,false);
		include_spip('inc/headers');
		echo 'toto';
		redirige_par_entete(generer_action_auteur("export_all","end,$gz,$archive",'',true));
	}

	echo install_debut_html(_T('info_sauvegarde') . " ($all)");

	if (!($timeout = ini_get('max_execution_time')*1000));
	$timeout = 30000; // parions sur une valeur tellement courante ...
	// le premier hit est moitie moins long car seulement une phase d'ecriture de morceaux
	// sans ramassage
	// sinon grosse ecriture au 1er hit, puis gros rammassage au deuxieme avec petite ecriture,... ca oscille
	if ($start) $timeout = round($timeout/2);
	// script de rechargement auto sur timeout
	//echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"".$redirect."\";',$timeout);</script>\n");

	if ($GLOBALS['flag_ob_flush']) @ob_flush();
	flush();

	echo "<div style='text-align: left'>\n";
	$etape = 1;

	// Instancier une fois pour toutes, car on va boucler un max.
	if (isset($GLOBALS['EXPORT_logos']) && $GLOBALS['EXPORT_logos']==true)
		$GLOBALS['chercher_logo'] = charger_fonction('chercher_logo', 'inc',true);
	else	$GLOBALS['chercher_logo'] = false;

	foreach($tables_for_dump as $table){
		if ($etape_actuelle <= $etape) {
		  $r = spip_query("SELECT COUNT(*) FROM $table");
		  $r = spip_fetch_array($r, SPIP_NUM);
		  $r = $r[0];
		  echo "\n<br /><strong>",$etape, '. ', $table,"</strong> ";
		  if (!$r) echo _T('texte_vide');
		  else
		    export_objets($table, $etape, $sous_etape,$dir, $archive, $gz, $r);
		  if ($GLOBALS['flag_ob_flush']) @ob_flush();
		  flush();
		  $sous_etape = 0;
		}
		$etape++;
		$status_dump = "$gz::$archive::" . $etape . "::0";
	// on se contente d'une ecriture en base pour aller plus vite
	// a la relecture on en profitera pour mettre le cache a jour
		ecrire_meta("status_dump", $status_dump,'non');
	}
	echo "</div>\n";
	// si Javascript est dispo, anticiper le Time-out
	//echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"$redirect\";',0);</script>\n");
	echo install_fin_html();
}

// construction de la liste des tables pour le dump :
// toutes les tables principales
// + toutes les tables auxiliaires hors relations
// + les tables relations dont les deux tables liees sont dans la liste

// http://doc.spip.org/@export_all_list_tables
function export_all_list_tables()
{
	$tables_for_dump = array();
	$tables_pointees = array();
	global $EXPORT_tables_noexport;
	global $tables_principales;
	global $tables_auxiliaires;
	global $table_des_tables;
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
	  		&&	!in_array($table,$EXPORT_tables_noexport) 
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
