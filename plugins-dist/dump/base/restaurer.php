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
include_spip('inc/actions');

function base_restaurer_dist($titre='', $reprise=false) {
	$status_file = _DUMP_STATUS_FILE;
	$status_file = _DIR_TMP.basename($status_file).".txt";
	if (!lire_fichier($status_file, $status)
		OR !$status = unserialize($status)) {
	}
	else {
		$redirect = parametre_url(generer_action_auteur('restaurer',_DUMP_STATUS_FILE),"step",intval(_request('step')+1),'&');

		$timeout = ini_get('max_execution_time');
		// valeur conservatrice si on a pas reussi a lire le max_execution_time
		if (!$timeout) $timeout=30; // parions sur une valeur tellement courante ...
		$max_time = time()+$timeout/2;
		
		include_spip('inc/minipres');
		@ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure

		$titre = _T('dump:restauration_en_cours') . " (".count($status['tables']).") ";
		$balise_img = chercher_filtre('balise_img');
		$titre .= $balise_img(chemin_image('searching.gif'));
		echo ( install_debut_html($titre));
		// script de rechargement auto sur timeout
		echo http_script("window.setTimeout('location.href=\"".$redirect."\";',".($timeout*1000).")");
		echo "<div style='text-align: left'>\n";

		dump_serveur($status['connect']);
		spip_connect('dump');

		// au premier coup on ne fait rien sauf afficher l'ecran de sauvegarde
		if (_request('step')) {
			$options = array(
				'callback_progression' => 'dump_afficher_progres',
				'max_time' => $max_time,
				'no_erase_dest' => lister_tables_noerase(),
				'where' => $status['where']?$status['where']:array(),
				'desc_tables_dest' => array()
			);
			if ($desc = Sql::getfetsel('valeur','spip_meta',"nom='dump_structure_temp'",'','','','','dump')
				AND $desc = unserialize($desc))
				$options['desc_tables_dest'] = $desc;
			#var_dump(Sql::allfetsel('nom,valeur','spip_meta',"",'','','','','dump'));
			#die();
			$res = base_copier_tables($status_file, $status['tables'], 'dump', '', $options);
		}
		else {
			// mais on en profite pour reparer les version base pour etre sur de ne pas les perdre
			Sql::updateq("spip_meta",array('impt'=>'oui'),"nom='version_installee'",'','dump');
			Sql::updateq("spip_meta",array('impt'=>'oui'),"nom LIKE '%_base_version'",'','dump');
		}
		
		echo ( "</div>\n");

		if (!$res)
			echo dump_relance($redirect);
		
		echo (install_fin_html());
		ob_end_flush();
		flush();

		if (!$res)
			exit;
		
		// quand on sort de $export avec true c'est qu'on a fini
		dump_end(_DUMP_STATUS_FILE,'restaurer');
		include_spip('inc/headers');
		echo redirige_formulaire(generer_url_ecrire("restaurer",'status='._DUMP_STATUS_FILE,'',true, true));

	}
}


?>
