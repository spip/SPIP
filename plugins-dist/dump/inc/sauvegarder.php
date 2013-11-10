<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/dump');

function inc_sauvegarder_dist($status_file, $redirect='') {
	$status_file = _DIR_TMP.basename($status_file).".txt";
	if (!lire_fichier($status_file, $status)
		OR !$status = unserialize($status)) {
	}
	else {
		$timeout = ini_get('max_execution_time');
		// valeur conservatrice si on a pas reussi a lire le max_execution_time
		if (!$timeout) $timeout=30; // parions sur une valeur tellement courante ...
		$max_time = time()+$timeout/2;
		
		include_spip('inc/minipres');
		@ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure

		$titre = _T('dump:sauvegarde_en_cours') . " (".count($status['tables']).") ";
		$balise_img = chercher_filtre('balise_img');
		$titre .= $balise_img(chemin_image('searching.gif'));
		echo ( install_debut_html($titre));
		// script de rechargement auto sur timeout
		echo http_script("window.setTimeout('location.href=\"".$redirect."\";',".($timeout*1000).")");
		echo "<div style='text-align: left'>\n";

		dump_serveur($status['connect']);
		spip_connect('dump');

		// au premier coup on ne fait rien sauf afficher l'ecran de sauvegarde
		$res = false;
		if (_request('step')) {
			$options = array(
				'callback_progression' => 'dump_afficher_progres',
				'max_time' => $max_time,
				'no_erase_dest' => lister_tables_noerase(),
				'where' => $status['where']?$status['where']:array(),
			);
			$res = base_copier_tables($status_file, $status['tables'], '', 'dump', $options);
		}
		
		echo ( "</div>\n");

		if (!$res AND $redirect)
			echo dump_relance($redirect);
		echo (install_fin_html());
		ob_end_flush();
		flush();

		return $res;
	}
}


?>
