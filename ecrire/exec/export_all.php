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

include_spip('inc/actions');
include_spip('inc/export');
include_spip('base/abstract_sql');
include_spip('inc/acces');

// http://doc.spip.org/@exec_export_all_dist
function exec_export_all_dist()
{
	global $connect_toutes_rubriques;
	// utiliser une version fraiche des metas (ie pas le cache)
	lire_metas();
	$rub = intval(_request('id_parent'));
	$meta = 'status_dump_'  . $GLOBALS['auteur_session']['id_auteur'];

	if (!isset($GLOBALS['meta'][$meta])) {
		$gz = _request('gz') ? '.gz' : '';
		$archive = $gz 
		?  _request('znom_sauvegarde') 
		:  _request('nom_sauvegarde');
		if (!$archive) $archive = 'dump';
		$archive .= '.xml' . $gz;

		//  creer l'en tete du fichier a partir de l'espace public
		include_spip('inc/headers');
		redirige_par_entete(generer_action_auteur("export_all", "start,$gz,$archive,$rub", '', true));
	} 

	$start = false;
	list($gz, $archive, $rub, $etape_actuelle, $sous_etape) = 
		explode("::",$GLOBALS['meta'][$meta]);
	$dir =  export_subdir($rub);
	$file = $dir . $archive;
	$metatable = $meta . '_tables';
	$redirect = generer_url_ecrire("export_all");

	if (!$etape_actuelle AND !$sous_etape) {
		$l = preg_files($file .  ".part_[0-9]+_[0-9]+");
		if ($l) {
			spip_log("menage d'une sauvegarde inachevee: " . join(',', $l));
			foreach($l as $dummy)spip_unlink($dummy);
		}
		$start = true; //  utilise pour faire un premier hit moitie moins long
		$tables_sauvegardees = array();
	} else 	$tables_sauvegardees = isset($GLOBALS['meta'][$metatable])?unserialize($GLOBALS['meta'][$metatable]):array();

	list($tables_for_dump,) = export_all_list_tables();
	
	// en mode partiel, commencer par les articles et les rubriques
	// pour savoir quelles parties des autres tables sont a sauver
	if ($rub) {
		if ($t = array_search('spip_rubriques', $tables_for_dump)) {
			unset($tables_for_dump[$t]);
			array_unshift($tables_for_dump, 'spip_rubriques');
		}
		if ($t = array_search('spip_articles', $tables_for_dump)) {
			unset($tables_for_dump[$t]);
			array_unshift($tables_for_dump, 'spip_articles');
		}
	}
	  
	// concatenation des fichiers crees a l'appel precedent
	ramasse_parties($dir, $archive);
	$all = count($tables_for_dump);
	if ($etape_actuelle > $all){ 
	  // l'appel precedent avait fini le boulot. mettre l'en-pied.
		ecrire_fichier($file, export_enpied(),false,false);
		include_spip('inc/headers');
		redirige_par_entete(generer_action_auteur("export_all","end,$gz,$archive,$rub",'',true));
	}

	include_spip('inc/minipres');
	echo_flush( install_debut_html(_T('info_sauvegarde') . " ($all)"));

	if (!($timeout = ini_get('max_execution_time')*1000));
	$timeout = 30000; // parions sur une valeur tellement courante ...
	// le premier hit est moitie moins long car seulement une phase d'ecriture de morceaux
	// sans ramassage
	// sinon grosse ecriture au 1er hit, puis gros rammassage au deuxieme avec petite ecriture,... ca oscille
	if ($start) $timeout = round($timeout/2);
	// script de rechargement auto sur timeout
	echo_flush("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"".$redirect."\";',$timeout);</script>\n");

	echo_flush( "<div style='text-align: left'>\n");
	$etape = 1;

	// Les sauvegardes partielles prennent le temps d'indiquer les logos
	// Instancier une fois pour toutes, car on va boucler un max.
	// On ne complete par jusqu'au secteur
	// (il faudrait, sans prendre les soeurs, pour pouvoir
	// resituer dans l'arborescence)
	if ($rub) {
		$GLOBALS['chercher_logo'] = charger_fonction('chercher_logo', 'inc',true);
		$les_rubriques = complete_fils(array($rub));
		$les_meres  = complete_secteurs(array($rub));
	} else {
		$GLOBALS['chercher_logo'] = false;
		$les_rubriques = $les_meres = '';
	}

	foreach($tables_for_dump as $table){
		if ($etape_actuelle <= $etape) {
		  $r = sql_countsel($table);
		  echo_flush( "\n<br /><strong>".$etape. '. '. $table."</strong> ");
		  if (!$r) echo_flush( _T('texte_vide'));
		  else
		    export_objets($table, $etape, $sous_etape,$dir, $archive, $gz, $r, $les_rubriques, $les_meres, $rub, $meta);
		  $sous_etape = 0;
		  // on utilise l'index comme ca c'est pas grave si on ecrit plusieurs fois la meme
		  $tables_sauvegardees[$table] = 1;
		  ecrire_meta($metatable, serialize($tables_sauvegardees),'non');
		}
		$etape++;
		$status_dump = "$gz::$archive::$rub::" . $etape . "::0";
	// on se contente d'une ecriture en base pour aller plus vite
	// a la relecture on en profitera pour mettre le cache a jour
		ecrire_meta($meta, $status_dump,'non');
	}
	echo_flush( "</div>\n");
	// si Javascript est dispo, anticiper le Time-out
	echo_flush ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"$redirect\";',0);</script>\n");
	echo_flush(install_fin_html());
}


// http://doc.spip.org/@complete_secteurs
function complete_secteurs($les_rubriques)
{
	$res = array();
	foreach($les_rubriques as $r) {
		do {
			$r = spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$r");
			$r = sql_fetch($r);
			if ($r AND $r = $r['id_parent']) {
				if ((isset($les_rubriques[$r])) OR isset($res[$r]))
					$r = false;
				else  $res[$r] = $r;
			}
		} while ($r);
	}
	return $res;
}

// http://doc.spip.org/@complete_fils
function complete_fils($rubriques)
{
	$r = $rubriques;
	do {
		$q = spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN (".join(',',$r).")");
		$r = array();
		while ($row = sql_fetch($q)) {
			$r[]= $rubriques[] = $row['id_rubrique'];
		}
	} while ($r);


	return $rubriques;
}
?>
