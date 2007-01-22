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
	global $archive, $gz, $connect_toutes_rubriques;

	if ($connect_toutes_rubriques AND file_exists(_DIR_DUMP))
		$dir = _DIR_DUMP;
	else $dir = determine_upload();

	if (!is_writable($dir)) {
		include_spip('inc/headers');
		$dir = preg_replace(",^" . _DIR_RACINE .",", '', $dir);
		redirige_par_entete(generer_url_action("test_dirs", "test_dir=$dir", true));
	}
	if (!$archive) $archive = export_nom_fichier_dump($dir,$gz);
	
	$file = $dir . $archive;
  
	// utiliser une version fraiche des metas (ie pas le cache)
	include_spip('inc/meta');
	lire_metas();

	if (!isset($GLOBALS['meta']["status_dump"])){
		$start = true;
	} else{
		$status_dump = explode("::",$GLOBALS['meta']["status_dump"]);
		if (($status_dump[0]!=$gz)||($status_dump[1]!=$archive))
			$start = true;
		else
			$start = ($status_dump[2]==0)&&($status_dump[3]==0);
	}

	if ($start){
		$status_dump = "$gz::$archive::1::0";
		ecrire_meta("status_dump", "$status_dump",'non');
		$status_dump = explode("::",$status_dump);
		ecrire_metas();

// Au cas ou le dernier dump n'aurait pas ete acheve correctement

		foreach(preg_files($file .  ".part\.[0-9]*") as $dummy)
			@unlink($dummy);

		$reprise = '';
	} else	$reprise = " (" . $status_dump[2] . ", " . $status_dump[3] . ")";

	list($tables_for_dump, $tables_for_link) = export_all_list_tables();

	$status_dump = explode("::",$GLOBALS['meta']["status_dump"]);
	$etape_actuelle = $status_dump[2];
	$sous_etape = $status_dump[3];
	$all = count($tables_for_dump);

	// Pour avoir les valeurs de _DIR_IMG etc relatives a l'espace public
	// la phase finale de reunion des fichiers en un seul est faite la-bas
	$href = generer_action_auteur("export_all","$archive/$all",'',true);

	if ($etape_actuelle > $all){ // au timeout
		include_spip('inc/headers');
		redirige_par_entete($href);
	}

	echo install_debut_html(_T('info_sauvegarde') . " ($all)");
	$f = ($gz) ? gzopen($file, "ab") : fopen($file, "ab");
	if (!$f) {
		echo "<p>",
		  _T('avis_erreur_sauvegarde', 
		     array('type'=>'.', 'id_objet'=>'. .')),
		  "</p>\n";
	  exit;
	}

	$_fputs = ($gz) ? gzputs : fputs;
	if ($gz) gzclose($f); else fclose($f);

	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();

	if (!($timeout = ini_get('max_execution_time')*1000));
	$timeout = 30000; // parions sur une valeur tellement courante ...
	if ($start) $timeout = round($timeout/2);
		// script de rechargement auto sur timeout
	echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"".generer_url_ecrire("export_all","archive=$archive&gz=$gz",true)."\";',$timeout);</script>\n");

	echo "<div style='text-align: left'>\n";
	$etape = 1;
	foreach($tables_for_dump as $table){

		$liens = $tables_for_link[$table];
		if ($etape_actuelle <= $etape) {
		  $r = spip_query("SELECT COUNT(*) FROM $table");
		  $r = spip_fetch_array($r, SPIP_NUM);
		  $r = $r[0];
		  echo "\n<br /><strong>",$etape, '. ', $table,"</strong> ";
		  if (!$r) echo _T('texte_vide');
		  else {
		    $cpt = export_objets($table, $liens, $etape, $sous_etape,$dir, $archive, $gz, $r);
			$filetable = $dir . $archive . '_' . $etape;
			ramasse_parties($dir . $archive . ".$etape", $filetable, $cpt);
		  }
		  $etape++;
		  $sous_etape = 0;
		  $status_dump = "$gz::$archive::" . $etape . "::0";
		  ecrire_meta("status_dump", $status_dump,'non');
		}
	}
	echo "</div>\n";
	// si Javascript est dispo, anticiper le Time-out
	echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"$href\";',0);</script>\n");
	echo install_fin_html();
	}

// construction de la liste des tables pour le dump :
// toutes les tables principales
// + toutes les tables auxiliaires hors relations
// + les tables relations dont les deux tables liees sont dans la liste

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
