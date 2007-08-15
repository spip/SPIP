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

include_spip('inc/presentation');
include_spip('inc/config');
include_spip('inc/meta');

function configuration_reducteur_dist()
{
	$image_process = _request('image_process');

		// application du choix de vignette
	if ($image_process) {
			// mettre a jour les formats graphiques lisibles
		switch ($image_process) {
				case 'gd1':
				case 'gd2':
					$formats_graphiques = $GLOBALS['meta']['gd_formats_read'];
					break;
				case 'netpbm':
					$formats_graphiques = $GLOBALS['meta']['netpbm_formats'];
					break;
				case 'convert':
				case 'imagick':
					$formats_graphiques = 'gif,jpg,png';
					break;
				default: #debug
					$formats_graphiques = '';
					$image_process = 'non';
					break;
			}
		ecrire_meta('formats_graphiques', $formats_graphiques);
		ecrire_meta('image_process', $image_process);
		ecrire_metas();
	} else 	$formats_graphiques = $GLOBALS['meta']["formats_graphiques"];

	$nb_process = 0;
	$res = '';

	// Tester les formats
	if ( /* GD disponible ? */
	function_exists('ImageGif')
	OR function_exists('ImageJpeg')
	OR function_exists('ImagePng')
	) {
		$res .= afficher_choix_vignette($p = 'gd1');
		if (function_exists("ImageCreateTrueColor")) {
			$res .= afficher_choix_vignette($p = 'gd2');
		}
	}

	if (_PNMSCALE_COMMAND!='') {
		$res .= afficher_choix_vignette($p = 'netpbm');
	}

	if (function_exists('imagick_readimage')) {
		$res .=afficher_choix_vignette('imagick');
	}

	if (_CONVERT_COMMAND!='') {
		$res .= afficher_choix_vignette($p = 'convert');
	}

	return ajax_action_greffe("configurer-reducteur", '', 
	  debut_cadre_trait_couleur("image-24.gif", true)
	. debut_cadre_relief("", true, "", _T("info_image_process_titre"))
	.  "<p class='verdana2'>"
	. _T('info_image_process')
	. "</p>"
	. $res
	. "<br class='nettoyeur' />"
	. "<p class='verdana2'>"
	. _T('info_image_process2')
	. "</p>"
	. fin_cadre_relief(true)
	. (!$formats_graphiques ? '' : format_choisi())
	. fin_cadre_trait_couleur(true));
}

function format_choisi()
{
	global $spip_lang_left, $spip_lang_right;
	
	$creer_preview = $GLOBALS['meta']["creer_preview"];
	$taille_preview = $GLOBALS['meta']["taille_preview"];
	if ($taille_preview < 10) $taille_preview = 120;

	$res .= "<p class='verdana2'>";
	$res .= _T('info_ajout_image');
	$res .= "</p>\n";
	$res .= "<div class='verdana2'>";
	$res .= bouton_radio("creer_preview", "oui", _T('item_choix_generation_miniature'), $creer_preview == "oui", "changeVisible(this.checked, 'config-preview', 'block', 'none');");
	$res .= '</div>';

	if ($creer_preview == "oui") $style = "block;"; else $style = "none;";
	
	$res .= "<div id='config-preview' class='verdana2' style='display: $style margin-$spip_lang_left: 40px;'>"._T('info_taille_maximale_vignette');
	$res .= "<br /><input type='text' name='taille_preview' value='$taille_preview' class='fondl' size='5' />";
	$res .= " "._T('info_pixels');
		
	if ($creer_preview == "oui"){
			// detection de taille maxi d'image manipulable avec GDx pour faire les image_reduire notamment
		if ($GLOBALS['meta']['image_process']=='gd1' OR $GLOBALS['meta']['image_process']=='gd2') {
			lire_metas(); // on force une mise a jour des meta avant le test
			$res .= "<p>"._L('SPIP va tester la taille maximale des images qu\'il peut traiter (en millions de pixels).<br/> Les images plus grandes ne seront pas r&eacute;duites.')."</p>";
			
			$res .= "<div dir='ltr' id='teste_memory_size_gd' style='text-align:left;float:$spip_lang_right;width:196px;background:url("._DIR_IMG_PACK . "jauge-test-gd.gif) no-repeat top left;'>";
			$max_size = isset($GLOBALS['meta']['max_taille_vignettes'])?$GLOBALS['meta']['max_taille_vignettes']:(500*500);
			$max_size_echec = isset($GLOBALS['meta']['max_taille_vignettes_echec'])?$GLOBALS['meta']['max_taille_vignettes_echec']:0;
			$max_size_test = isset($GLOBALS['meta']['max_taille_vignettes_test'])?$GLOBALS['meta']['max_taille_vignettes_test']:0;
			if ($max_size_test<$max_size_echec OR  ($max_size_test AND !$max_size_echec)){
				ecrire_meta('max_taille_vignettes_echec',$max_size_echec = $max_size_test,'non');
				ecrire_metas();
			}
			$maxtest = 1740; // 3MPixels
			$test = array();
			$time = time();
			if ($max_size >= ($maxtest-20)*($maxtest-20)) $maxtest = 2380; // 6MPixels
			$top = 16;
			for ($j = 320;$j>=20;$j = $j/2){
				$res .= "<div style='position:relative;top:{$top}px;$spip_lang_left:0px;font-size:1px;'>";
				$l = round($j/10);
				$lok = 0; $lbad =0;
				$margin_left = 0;
				$top -= 8;
				for ($i = 480;$i*$i<$max_size && $i<=$maxtest;$i+=$j) $lok += $l;
				if ($lok-$l+2>0) 
					$res .= "<img src='"._DIR_IMG_PACK . 'jauge-vert.gif'."' width='".($lok-$l+2)."' style='margin-right:".($l-2)."px;' height='8' alt='' />";
				for (;(!$max_size_echec OR $i*$i<$max_size_echec) && $i<=$maxtest;$i+=$j){
					if (!isset($test[$i])){
						$url = generer_url_action("tester_taille", "arg=$i&time=$time");
						$res .= "<img src='$url' width='2' style='margin-left:{$margin_left}px;margin-right:".($l-2)."px;' height='8' alt='' />";
						$test[$i] = 1;
						$margin_left = 0;
					}
					else $margin_left += $l;
				}
				for (;$i<=$maxtest;$i+=$j) $lbad += $l;
				if ($lbad) $res .= "<img src='"._DIR_IMG_PACK . 'jauge-rouge.gif'."' width='$lbad' height='8' style='margin-left:{$margin_left}px;' alt='' />";
				$res .= "</div>";
			}
			$res .= "</div><br style='clear:both;' />";
		}
	} else {
			effacer_meta('max_taille_vignettes');
			effacer_meta('max_taille_vignettes_echec');
			effacer_meta('max_taille_vignettes_test');
			ecrire_metas();
	}
	$res .= '<br /><br />';
	$res .= "</div>";
	$res .= bouton_radio("creer_preview", "non", _T('item_choix_non_generation_miniature'), $creer_preview != "oui", "changeVisible(this.checked, 'config-preview', 'none', 'block');");

	return 
	  debut_cadre_trait_couleur("", true, "", _T('info_generation_miniatures_images'))
	.  ajax_action_post('configurer', 'reducteur', 'config_fonctions', '', $res)
	. fin_cadre_trait_couleur(true);
}

// http://doc.spip.org/@afficher_choix_vignette
function afficher_choix_vignette($process) {
	static $cpt_cellule = 0;

	//global $taille_preview;
	$taille_preview = 120;

	// Ici on va tester les capacites de GD independamment des tests realises
	// dans les images spip_image -- qui servent neanmoins pour la qualite
	/* if (function_exists('imageformats')) {
		
	} */

	if($cpt_cellule>=3) {
		$cpt_cellule = 0;
		$retour .= "\n</tr><tr>\n";
	}
	else {
		$cpt_cellule += 1;
		$retour = '';
	}

	$class = '';
	if ($process == $GLOBALS['meta']['image_process']) {
	  $class = " selected";
	} 
	return 	$retour . "\n<div class='vignette_reducteur$class'"
	. "><a href='"
	. generer_url_ecrire("config_fonctions", "image_process=$process")
	. "'><img src='"
	. generer_url_action("tester", "arg=$process")
	. "' alt='$process' /></a><span>$process</span></div>\n";

}

?>