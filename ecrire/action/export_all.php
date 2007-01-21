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
	global $gz, $connect_toutes_rubriques ;
	
        $securiser_action = charger_fonction('securiser_action', 'inc');
        $arg = $securiser_action();

	// determine upload va aussi initialiser connect_toutes_rubrique
	$dir = determine_upload();
	if ($connect_toutes_rubriques AND file_exists(_DIR_DUMP))
		$dir = _DIR_DUMP;

	$file =  $dir . $arg;
	spip_log("actionexp $file");
	$f = ($gz) ? gzopen($file, "ab") : fopen($file, "ab");
	$_fputs = ($gz) ? gzputs : fputs;
	$_fputs($f, export_entete());

	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();

	$files = ramasse_parties($file, $gz, $file . ".part");

	$_fputs ($f, build_end_tag("SPIP")."\n");
	if ($gz) gzclose($f); else fclose($f);
		
	effacer_meta("status_dump");
	ecrire_metas();

	$n = _T('taille_octets',
		array('taille' => number_format(filesize($file), 0, ' ', ' ')));
	$n = _T('info_sauvegarde_reussi_02',
		array('archive' => ':<br /><b>'.joli_repertoire($file)."</b> ($n)"));

	echo install_debut_html(_T('info_sauvegarde'));
	// ne pas effrayer inutilement: il peut y avoir moins de fichiers
	// qu'annonce' si certains etaient vides
#	echo "<ul><li>", join('</li><li>', $files), '</li></ul>';
	echo "<p style='text-align: left'>".
	  $n,
	" <a href='" . _DIR_RESTREINT . "'>".
	_T('info_sauvegarde_reussi_03')
	. "</a> "
	._T('info_sauvegarde_reussi_04')
	. "</p>\n";
	echo install_fin_html();
}
?>