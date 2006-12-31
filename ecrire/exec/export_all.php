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

$GLOBALS['version_archive'] = '1.3';

include_spip('base/serial');
include_spip('base/auxiliaires');
include_spip('inc/indexation'); // pour la fonction primary_index_table 
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
  global $archive, $debut_limit, $etape, $gz, $spip_version, $spip_version_affichee, $version_archive, $connect_login, $connect_toutes_rubriques;

	if ($connect_toutes_rubriques AND file_exists(_DIR_DUMP))
		$dir = _DIR_DUMP;
	else $dir = determine_upload();

	if (!is_writable($dir)) {
		include_spip('inc/headers');
		$dir = preg_replace(",^" . _DIR_RACINE .",", '', $dir);
		redirige_par_entete(generer_url_action("test_dirs", "test_dir=$dir", true));
	}
	if (!$archive)
		$archive = export_nom_fichier_dump($dir,$gz);
	
  
	// utiliser une version fraiche des metas (ie pas le cache)
	include_spip('inc/meta');
	lire_metas();

	$action = _T('info_exportation_base', array('archive' => $archive));
	if (!isset($GLOBALS['meta']["status_dump"])){
	  $start = true;
	}
	else{
		$status_dump = explode("::",$GLOBALS['meta']["status_dump"]);
		if (($status_dump[0]!=$gz)||($status_dump[1]!=$archive))
			$start = true;
		else
			$start = ($status_dump[2]==0)&&($status_dump[3]==0);
	}

	echo install_debut_html(_T('info_sauvegarde'));

	$file = $dir . $archive;
	$partfile = $file . ".part";

	//if (!$etape) echo "<p><blockquote><font size=2>"._T('info_sauvegarde_echouee')." <a href='" . generer_url_ecrire("export_all","reinstall=non&etape=1&gz=$gz") . "'>"._T('info_procedez_par_etape')."</a></font></blockquote><p>";

	$_fputs = ($gz) ? gzputs : fputs;

	if ($start){
		$status_dump = "$gz::$archive::0::0";
		ecrire_meta("status_dump", "$status_dump",'non');
		$status_dump = explode("::",$status_dump);
		ecrire_metas();
		// un ramassage preventif au cas ou le dernier dump n'aurait pas ete acheve correctement
		#ramasse_parties(_DIR_DUMP . $archive, $gz, _DIR_DUMP . $partfile);
		// et au cas ou (le rammase_parties s'arrete si un fichier de la serie est absent)
		// on ratisse large avec un preg_files
		$liste = preg_files($file .  ".part\.[0-9]*");
		foreach($liste as $dummy)
			@unlink($dummy);

		echo "<p>"._T("info_sauvegarde")."</p>\n";
		$f = ($gz) ? gzopen($file, "wb") : fopen($file, "wb");
		if (!$f) {
			echo "<p>"._T('avis_erreur_sauvegarde', array('type'=>'.', 'id_objet'=>'. .'))."</p>\n";
			exit;
		}

		$_fputs($f, "<"."?xml version=\"1.0\" encoding=\"".$GLOBALS['meta']['charset']."\"?".">\n<SPIP version=\"$spip_version_affichee\" version_base=\"$spip_version\" version_archive=\"$version_archive\">\n\n");
		if ($gz) gzclose($f);
		else fclose($f);
	}
	else{ // reprise
		echo "<p>"._T("info_sauvegarde"). " (" . $status_dump[2] . ", " . $status_dump[3] . ")</p>\n";
		$f = ($gz) ? gzopen($file, "ab") : fopen($file, "ab");
		if (!$f) {
			echo "<p>"._T('avis_erreur_sauvegarde', array('type'=>'.', 'id_objet'=>'. .'))."</p>\n";
			exit;
		}
		if ($gz) gzclose($f);
		else fclose($f);
	}

	// construction de la liste des tables pour le dump :
	// toutes les tables principales
	// + toutes les tables auxiliaires hors relations
	// + les tables relations dont les deux tables liees sont dans la liste
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
			# si une restauration est interrompue, cela se verra mieux si il manque des objets
			# que des liens
			array_unshift($tables_for_dump,$link_table);
	}

	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();

	$status_dump = explode("::",$GLOBALS['meta']["status_dump"]);
	$etape = $status_dump[2];

	if ($etape >= count($tables_for_dump)){
		echo "<ul>\n";
		foreach($tables_for_dump as $i=>$table){
			// appel simplement pour l'affichage. Rien n'est fait puisqu'on a fini 
			export_objets($table, primary_index_table($table), $tables_for_link[$table], 0, false, $i, _T("info_sauvegarde").", $table");
		}
		echo "</ul>\n";

		if ($GLOBALS['flag_ob_flush']) ob_flush();
		flush();
		ramasse_parties($file, $gz, $partfile);

		$f = ($gz) ? gzopen($file, "ab") : fopen($file, "ab");
		$_fputs ($f, build_end_tag("SPIP")."\n");
		if ($gz) gzclose($f);
		else fclose($f);
		
		effacer_meta("status_dump");
		ecrire_metas();
		echo "<p><b>"._T('info_sauvegarde_reussi_01')."</b></p><p>"._T('info_sauvegarde_reussi_02', array('archive' => '<b>'.joli_repertoire($file).'</b>'))." <a href='./'>"._T('info_sauvegarde_reussi_03')."</a> "._T('info_sauvegarde_reussi_04')."</p>\n";
	}
	else{
		if (!($timeout = ini_get('max_execution_time')*1000));
			$timeout = 30000; // parions sur une valeur tellement courante ...
		if ($start) $timeout = round($timeout/2);
		// script de rechargement auto sur timeout
		echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"".generer_url_ecrire("export_all","archive=$archive&gz=$gz",true)."\";',$timeout);</script>\n");
		$cpt = 0;
		$paquets = 400; // nombre d'enregistrements dans chaque paquet
		echo "<ul>\n";
		foreach($tables_for_dump as $i=>$table){
			// par paquets
			list($string,$status_dump)=export_objets($table, primary_index_table($table), $tables_for_link[$table],0, false, $i, _T("info_sauvegarde").", $table",$paquets);
		  while ($string!=''){
				if ($cpt == 0)
					ramasse_parties($file, $gz, $partfile);

				// on ecrit dans un fichier generique
				ecrire_fichier ($partfile, $string);
				// on le renomme avec un numero -> operation atomique en linux
				rename($partfile,$partfile.".$cpt");
				$cpt ++;
				ecrire_meta("status_dump", implode("::",$status_dump),'non');
				#lire_metas();
				list($string,$status_dump)=export_objets($table, primary_index_table($table), $tables_for_link[$table],0, false, $i, _T("info_sauvegarde").", $table",$paquets);
			}
			ecrire_meta("status_dump", implode("::",$status_dump),'non');
			#lire_metas();
		}
		echo "</ul>\n";
		// pour recharger la page tout de suite en finir le ramassage
		echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"".str_replace("&amp;","&",generer_url_ecrire("export_all","archive=$archive&gz=$gz"))."\";',0);</script>\n");
	}

	echo install_fin_html();

}
?>
