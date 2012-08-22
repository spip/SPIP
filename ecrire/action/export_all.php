<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/lang');
include_spip('inc/actions');
include_spip('base/dump');

// http://doc.spip.org/@action_export_all_dist
function action_export_all_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	@list(, , $archive, $rub) = explode(',', $arg);
	$meta = base_dump_meta_name($rub);
	$file = ramasse_parties($rub, $archive, $meta);
	$size = !$file ? 0 : @(!file_exists($file) ? 0 : filesize($file));
	$metatable = $meta . '_tables';
	utiliser_langue_visiteur();
	if (!$size)
		$corps = _T('avis_erreur_sauvegarde', array('type'=>'.', 'id_objet'=>'. .'));
	else {
		$tables_sauvegardees = isset($GLOBALS['meta'][$metatable])?unserialize($GLOBALS['meta'][$metatable]):array();
		$corps = export_all_report($file, $rub, $size, $tables_sauvegardees);
	}
	effacer_meta($metatable);
	effacer_meta($meta);
	include_spip('inc/minipres');
	echo minipres(_T('info_sauvegarde'), $corps);
}

function export_all_rename($nom, $subdir)
{
	$dir = dirname($subdir);
	$dest = $dir . '/' . $nom;
	if (file_exists($dest)) {
			$n = 1;
			while (@file_exists($new = "$dir/$n-$nom")) $n++;
			spip_log("renomme vieux $dest en $new");
			@rename($dest, $new);
	}
	return $dest;
}

// Concatenation des tranches

// http://doc.spip.org/@ramasse_parties
function ramasse_parties($rub, $archive, $meta)
{
	$dir = base_dump_dir($meta);
	$files = preg_files($dir . $archive . ".part_[0-9]+_[0-9]+[.gz]?");
	if (!$files) return false;
	$ok = true;
	$files_o = array();
	$but = export_all_rename($archive, $dir);
	// creer l'en tete du fichier
	ecrire_fichier($but, export_entete(_VERSION_ARCHIVE),false);
	foreach($files as $f) {
	  $contenu = "";
	  if (lire_fichier ($f, $contenu)) {
	    if (!ecrire_fichier($but,$contenu,false,false))
	      { $ok = false; break;}
	  }
	  spip_unlink($f);
	  $files_o[]=$f;
	}
	ecrire_fichier($but, export_enpied(),false,false);
	spip_unlink($dir);
	spip_log("concatenation " . join(' ', $files_o));
	return $ok ? $but : false;
}

function export_all_end($meta, $archive){
	$dir = base_dump_dir($meta);
	$file = $dir . $archive;
}

// http://doc.spip.org/@export_entete
function export_entete($version_archive)
{
	return
"<" . "?xml version=\"1.0\" encoding=\"".
$GLOBALS['meta']['charset']."\"?".">\n" .
"<SPIP
	version=\"" . $GLOBALS['spip_version_affichee'] . "\"
	version_base=\"" . $GLOBALS['spip_version_base'] . "\"
	version_archive=\"" . $version_archive . "\"
	adresse_site=\"" .  $GLOBALS['meta']["adresse_site"] . "\"
	dir_img=\"" . _DIR_IMG . "\"
	dir_logos=\"" . _DIR_LOGOS . "\"
>\n";
}


// production de l'en-pied du fichier d'archive
// http://doc.spip.org/@export_enpied
function export_enpied () { return  "</SPIP>\n";}

function export_all_report($dest, $rub, $size, $tables_sauvegardees)
{
	global $spip_lang_left,$spip_lang_right;

	// ne pas effrayer inutilement: il peut y avoir moins de fichiers
	// qu'annonce' si certains etaient vides

	$n = _T('taille_octets', array('taille' => number_format($size, 0, ' ', ' ')));
		
		// cette chaine est a refaire car il y a double ambiguite:
		// - si plusieurs SPIP dans une base SQL (cf table_prefix)
		// - si on exporte seulement une rubrique
#			  _T('info_sauvegarde_reussi_02',		

	if ($rub) {
			$titre = sql_getfetsel('titre', 'spip_rubriques', "id_rubrique=$rub");
			$titre = _T('info_sauvegarde_rubrique_reussi',
				    array('archive' => ':<br /><b>'.joli_repertoire($dest)."</b> ($n)", 'titre' => "<b>$titre</b>"));
	} else
			$titre = _T('info_sauvegarde_reussi_02',
			      array('archive' => ':<br /><b>'.joli_repertoire($dest)."</b> ($n)"));

	// afficher la liste des tables qu'on a sauvegarde
	include_spip('inc/filtres');
	sort($tables_sauvegardees);
	$n = floor(count($tables_sauvegardees)/2);

	return "<p style='text-align: $spip_lang_left'>".
			  $titre .
			  " <a href='" . generer_url_ecrire() . "'>".
			_T('info_sauvegarde_reussi_03')
			. "</a> "
			._T('info_sauvegarde_reussi_04')
			. "</p>\n"
			. "<div style='text-align: $spip_lang_right'>"
			. bouton_action(_T("retour"), generer_url_ecrire())
			. "</div>"
			. "<div style='width:49%;float:left;'><ul><li>" . join('</li><li>', array_slice($tables_sauvegardees,0,$n)) . "</li></ul></div>"
		  . "<div style='width:49%;float:left;'><ul><li>" . join('</li><li>', array_slice($tables_sauvegardees,$n)) . "</li></ul></div>"
		  . "<div class='nettoyeur'></div>";
}
?>
