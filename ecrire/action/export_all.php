<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
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
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	@list($quoi, $gz, $archive, $rub) = split(',', $arg);
	$meta = 'status_dump_'  . $GLOBALS['visiteur_session']['id_auteur'];
	$file =  export_subdir($rub) . $archive;

	utiliser_langue_visiteur();
	if ($quoi =='start'){
		// creer l'en tete du fichier et retourner dans l'espace prive
		ecrire_fichier($file, export_entete(),false);
		ecrire_meta($meta, "$gz::$archive::$rub::1::0",'non');
		include_spip('inc/headers');
		  // rub=$rub sert AUSSI a distinguer cette redirection
		  // d'avec l'appel initial sinon FireFox croit malin
		  // d'optimiser la redirection
		redirige_par_entete(generer_url_ecrire('export_all',"rub=$rub", true));
	} elseif ($quoi=='end') export_all_fin($file, $meta);
}

// http://doc.spip.org/@export_all_fin
function export_all_fin($file, $meta)
{
	global $spip_lang_left,$spip_lang_right;

	$metatable = $meta . '_tables';
	$tables_sauvegardees = isset($GLOBALS['meta'][$metatable])?unserialize($GLOBALS['meta'][$metatable]):array();
	effacer_meta($meta);
	effacer_meta($metatable);

	$size = @(!file_exists($file) ? 0 : filesize($file));

	if (!$size) {
		$corps = _T('avis_erreur_sauvegarde', array('type'=>'.', 'id_objet'=>'. .'));
	
	} else {
		$subdir = dirname($file);
		$dir = dirname($subdir);
		$nom = basename($file);
		$dest = $dir . '/' . $nom;
		if (file_exists($dest)) {
			$n = 1;
			while (@file_exists($new = "$dir/$n-$nom")) $n++;
			@rename($dest, $new);
		}
		if (@rename($file, $dest)) {
			spip_unlink($subdir);
			spip_log("$file renomme en $dir/$nom");
		}
	// ne pas effrayer inutilement: il peut y avoir moins de fichiers
	// qu'annonce' si certains etaient vides

		$n = _T('taille_octets', array('taille' => number_format($size, 0, ' ', ' ')));
		
		$corps = "<p style='text-align: $spip_lang_left'>".
			  _T('info_sauvegarde_reussi_02',
			     array('archive' => ':<br /><b>'.joli_repertoire($nom)."</b> ($n)")) .
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
?>
