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
include_spip('inc/actions');
include_spip('inc/minipres');

// http://doc.spip.org/@action_export_all_dist
function action_export_all_dist()
{
	global $connect_toutes_rubriques ;

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	// determine upload va aussi initialiser connect_toutes_rubrique
	$dir = determine_upload();
	if ($connect_toutes_rubriques AND file_exists(_DIR_DUMP))
		$dir = _DIR_DUMP;

	list($quoi, $gz, $archive) = split(',', $arg);
	
	$file =  $dir . $archive;
	spip_log("action $arg $file");
	include_spip('inc/meta');
	if ($quoi =='start'){
		// creer l'en tete du fichier et retourner dans l'espace prive
		include_spip('inc/export');
		if (ecrire_fichier($file, export_entete(),false)) {
		
		  ecrire_meta("status_dump", "$gz::$archive::1::0",'non');
		  ecrire_metas();
		  include_spip('inc/headers');
		  redirige_par_entete(generer_url_ecrire('export_all'));
		} else {
			echo install_debut_html(_T('info_sauvegarde'));
		  echo "<p>",
		    _T('avis_erreur_sauvegarde', 
		       array('type'=>'.', 'id_objet'=>'. .')),
		    "</p>\n";
		  exit;
		}
	}elseif ($quoi=='end'){

		effacer_meta("status_dump");
		ecrire_metas();
	
		$size = 0;
		if (file_exists($file))
			$size = filesize($file);
		$n = _T('taille_octets',
			array('taille' => number_format($size, 0, ' ', ' ')));
		$n = _T('info_sauvegarde_reussi_02',
			array('archive' => ':<br /><b>'.joli_repertoire($file)."</b> ($n)"));
	
		echo install_debut_html(_T('info_sauvegarde'));
		if (!$size) {
		  echo _T('avis_erreur_sauvegarde', array('type'=>'.', 'id_objet'=>'. .'));
	
		} else {
			// ne pas effrayer inutilement: il peut y avoir moins de fichiers
			// qu'annonce' si certains etaient vides
		
			echo "<p style='text-align: left'>".
			  $n,
			" <a href='" . _DIR_RESTREINT . "'>".
			_T('info_sauvegarde_reussi_03')
			. "</a> "
			._T('info_sauvegarde_reussi_04')
			. "</p>\n";
		}
		echo install_fin_html();
	}
}
?>