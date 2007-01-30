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
include_spip('inc/meta');
include_spip('inc/headers');

// Tester nos capacites a creer des images avec GD2 (taille memoire)
// http://doc.spip.org/@action_tester_dist
function action_tester_taille_dist() {
	global $arg;
	//$securiser_action = charger_fonction('securiser_action', 'inc');
	//$arg = $securiser_action();
	$max_size = isset($GLOBALS['meta']['max_taille_vignettes'])?$GLOBALS['meta']['max_taille_vignettes']:0;
	$max_size_echec = isset($GLOBALS['meta']['max_taille_vignettes_echec'])?$GLOBALS['meta']['max_taille_vignettes_echec']:0;
	$max_size_test = isset($GLOBALS['meta']['max_taille_vignettes_test'])?$GLOBALS['meta']['max_taille_vignettes_test']:0;
	$taille = intval($arg);
	
	if (($s = $taille*$taille)>$max_size){
		if (!$max_size_echec OR $s < $max_size_echec) {
			include_spip('inc/filtres');
			$image_source = _DIR_IMG_PACK."test.png";
			$res = spip_query("SELECT valeur FROM spip_meta WHERE nom='max_taille_vignettes_test'");
			if ($row = spip_fetch_array($res))
				$max_size_test = $row['valeur'];
			if (!$max_size_test OR $max_size_test>$s)
				ecrire_meta('max_taille_vignettes_test',$s,'non');
			$result = filtrer('image_recadre',$image_source,$taille,$taille);
			// on est ici, donc pas de plantage
			if ($max_size_test>$s)
				ecrire_meta('max_taille_vignettes_test',$max_size_test,'non');
			else 
				effacer_meta('max_taille_vignettes_test');
			$src = extraire_attribut($result,'src');
		}
		// et maintenant envoyer la vignette de tests
		if ($src) {
			ecrire_meta('max_taille_vignettes',$taille*$taille,'non');
			ecrire_metas();
			@unlink($src);
		}
		else {
			if (!$max_size_echec OR $s < $max_size_echec)
				ecrire_meta('max_taille_vignettes_echec',$taille*$taille,'non');
			# image echec
			redirige_par_entete(_DIR_IMG_PACK . 'jauge-rouge.gif');
		}
	}
	redirige_par_entete(_DIR_IMG_PACK . 'jauge-vert.gif');
}
?>
