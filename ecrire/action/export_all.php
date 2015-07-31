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

include_spip('inc/export');
include_spip('inc/minipres');

// http://doc.spip.org/@action_export_all_dist
function action_export_all_dist()
{
	global $spip_lang_left,$spip_lang_right;
	
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	// determine upload va aussi initialiser l'index "restreint"
	$dir = determine_upload();
	if (!$GLOBALS['auteur_session']['restreint'] AND file_exists(_DIR_DUMP))
		$dir = _DIR_DUMP;

	list($quoi, $gz, $archive) = split(',', $arg);
	
	$file =  $dir . $archive;

	include_spip('inc/meta');
	utiliser_langue_visiteur();
	if ($quoi =='start'){
		// creer l'en tete du fichier et retourner dans l'espace prive
		include_spip('inc/export');
		ecrire_fichier($file, export_entete(),false);
		ecrire_meta("status_dump", "$gz::$archive::1::0",'non');
		ecrire_metas();
		include_spip('inc/headers');
		  // suite=1 ne sert qu'a distinguer cette redirection
		  // d'avec l'appel initial sinon FireFox croit malin
		  // d'optimiser la redirection
		redirige_par_entete(generer_url_ecrire('export_all',"suite=1", true));
	}elseif ($quoi=='end'){
		lire_metas();
		$tables_sauvegardees = isset($GLOBALS['meta']['status_dump_tables'])?unserialize($GLOBALS['meta']['status_dump_tables']):array();
		effacer_meta("status_dump");
		effacer_meta("status_dump_tables");
		effacer_meta("export_session_id");
		ecrire_metas();

		$size = @(!file_exists($file) ? 0 : filesize($file));

		if (!$size) {
			$corps = _T('avis_erreur_sauvegarde', array('type'=>'.', 'id_objet'=>'. .'));
	
		} else {
	// ne pas effrayer inutilement: il peut y avoir moins de fichiers
	// qu'annonce' si certains etaient vides
			$n = _T('taille_octets', array('taille' => number_format($size, 0, ' ', ' ')));
		
			$corps = "<p style='text-align: $spip_lang_left'>".
			  _T('info_sauvegarde_reussi_02',
			     array('archive' => ':<br /><b>'.joli_repertoire($file)."</b> ($n)")) .
			  " <a href='" . generer_url_ecrire() . "'>".
			_T('info_sauvegarde_reussi_03')
			. "</a> "
			._T('info_sauvegarde_reussi_04')
			. "</p>\n";
			
			$corps .= "<p style='text-align: $spip_lang_right'>".
			  " <a href='" . generer_url_ecrire() . "'>" .
			  _T("retour") .
			  "</a></p>";
						
			// afficher la liste des tables qu'on a sauvegarde
			$tables_sauvegardees = array_keys($tables_sauvegardees);
			sort($tables_sauvegardees);
			$n = floor(count($tables_sauvegardees)/2);
			$corps .= "<div style='width:49%;float:left;'><ul><li>" . join('</li><li>', array_slice($tables_sauvegardees,0,$n)) . "</li></ul></div>"
			. "<div style='width:49%;float:left;'><ul><li>" . join('</li><li>', array_slice($tables_sauvegardees,$n)) . "</li></ul></div>"
			. "<br class='nettoyeur' />";
		}
		echo minipres(_T('info_sauvegarde'), $corps);
		exit;
	}
}
?>
