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

include_spip('inc/flock');
include_spip('inc/actions');
include_spip('inc/export');
include_spip('base/abstract_sql');
include_spip('inc/meta');
	include_spip('inc/acces');

// http://doc.spip.org/@exec_export_all_dist
function exec_export_all_dist()
{
	global $connect_toutes_rubriques;
	$start = false;

	if ($connect_toutes_rubriques AND file_exists(_DIR_DUMP))
		$dir = _DIR_DUMP;
	else $dir = determine_upload();

	// creer un id de la session d'export qui sera utilise pour verifier qu'on a toujours la main
	// avant chaque ecriture de morceau
	// permet d'eviter les process concourants qui realisent le meme export
	@define('_EXPORT_SESSION_ID',creer_uniqid());
	ecrire_meta('export_session_id',_EXPORT_SESSION_ID,'non');
	
	// utiliser une version fraiche des metas (ie pas le cache)
	lire_metas();

	if (!isset($GLOBALS['meta']["status_dump"])) {
		$gz = _request('gz') ? '.gz' : '';
		$archive = $gz 
		?  _request('znom_sauvegarde') 
		:  _request('nom_sauvegarde');
		if (!$archive) $archive = 'dump';
		$archive .= '.xml' . $gz;

		//  creer l'en tete du fichier a partir de l'espace public
		include_spip('inc/headers');
		redirige_par_entete(generer_action_auteur("export_all", "start,$gz,$archive", '', true));
	} 

	list($gz, $archive, $etape_actuelle, $sous_etape) = 
	  explode("::",$GLOBALS['meta']["status_dump"]);

	$file = $dir . $archive;
	$redirect = generer_url_ecrire("export_all");
	$tables_sauvegardees = isset($GLOBALS['meta']['status_dump_tables'])?unserialize($GLOBALS['meta']['status_dump_tables']):array();

	if (!$etape_actuelle AND !$sous_etape) {
		$l = preg_files($file .  ".part_[0-9]+_[0-9]+");
		if ($l) {
			spip_log("menage d'une sauvegarde inachevee: " . join(',', $l));
			foreach($l as $dummy)spip_unlink($dummy);
		}
		$start = true; //  utilise pour faire un premier hit moitie moins long
		$tables_sauvegardees = array();
	}

	list($tables_for_dump, $tables_for_link) = export_all_list_tables();

	$all = count($tables_for_dump);

	// concatenation des fichiers crees a l'appel precedent
	ramasse_parties($dir, $archive);

	if ($etape_actuelle > $all){ 
	  // l'appel precedent avait fini le boulot. mettre l'en-pied.
		ecrire_fichier($file, export_enpied(),false,false);
		include_spip('inc/headers');
		redirige_par_entete(generer_action_auteur("export_all","end,$gz,$archive",'',true));
	}

	include_spip('inc/minipres');
	echo install_debut_html(_T('info_sauvegarde') . " ($all)");

	if (!($timeout = ini_get('max_execution_time')*1000));
	$timeout = 30000; // parions sur une valeur tellement courante ...
	// le premier hit est moitie moins long car seulement une phase d'ecriture de morceaux
	// sans ramassage
	// sinon grosse ecriture au 1er hit, puis gros rammassage au deuxieme avec petite ecriture,... ca oscille
	if ($start) $timeout = round($timeout/2);
	// script de rechargement auto sur timeout
	echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"".$redirect."\";',$timeout);</script>\n");

	if (function_exists('ob_flush')) @ob_flush();
	flush();

	echo "<div style='text-align: left'>\n";
	$etape = 1;

	// Les sauvegardes partielles prennent le temps d'indiquer les logos
	// Instancier une fois pour toutes, car on va boucler un max.
	// Completer jusqu'au secteur (sans prendre les soeurs) pour pouvoir
	// resituer dans l'arborescence
	if ($GLOBALS['connect_id_rubrique']) {
		$GLOBALS['chercher_logo'] = charger_fonction('chercher_logo', 'inc',true);
		$les_rubriques = complete_secteurs($GLOBALS['connect_id_rubrique']);
	} else {
		$GLOBALS['chercher_logo'] = false;
		$les_rubriques = '';
	}

	foreach($tables_for_dump as $table){
		if ($etape_actuelle <= $etape) {
		  $r = spip_abstract_countsel($table);
		  echo "\n<br /><strong>",$etape, '. ', $table,"</strong> ";
		  if (!$r) echo _T('texte_vide');
		  else
		    export_objets($table, $etape, $sous_etape,$dir, $archive, $gz, $r, $les_rubriques);
		  if (function_exists('ob_flush')) @ob_flush();
		  flush();
		  $sous_etape = 0;
		  // on utilise l'index comme ca c'est pas grave si on ecrit plusieurs fois la meme
			$tables_sauvegardees[$table] = 1;
			ecrire_meta("status_dump_tables", serialize($tables_sauvegardees),'non');
		}
		$etape++;
		$status_dump = "$gz::$archive::" . $etape . "::0";
	// on se contente d'une ecriture en base pour aller plus vite
	// a la relecture on en profitera pour mettre le cache a jour
		ecrire_meta("status_dump", $status_dump,'non');
	}
	echo "</div>\n";
	// si Javascript est dispo, anticiper le Time-out
	echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"$redirect\";',0);</script>\n");
	echo install_fin_html();
}

// http://doc.spip.org/@complete_secteurs
function complete_secteurs($les_rubriques)
{
	foreach($les_rubriques as $r) {
		do {
			$r = spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$r");
			$r = spip_abstract_fetch($r);
			if ($r AND $r = $r['id_parent']) {
				if (isset($les_rubriques[$r]))
					$r = false;
				else  $les_rubriques[$r] = $r;
			}
		} while ($r);
	}
	return $les_rubriques;
}

function export_verifie_session() {
	$row = spip_abstract_fetsel(array('valeur'),array('spip_meta'),array("nom='export_session_id'"));
	if ($row['valeur']!=_EXPORT_SESSION_ID)
		die('la place est prise');
}
?>
