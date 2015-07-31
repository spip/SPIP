<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/presentation');
include_spip('inc/config');

function configuration_reducteur_dist()
{
	global $spip_lang_left, $spip_lang_right;
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
		ecrire_meta('formats_graphiques', $formats_graphiques,'non');
		ecrire_meta('image_process', $image_process,'non');
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

		
	$test_out = "";
	if ($GLOBALS['meta']['image_process']=='gd1' OR $GLOBALS['meta']['image_process']=='gd2') {
		effacer_meta('max_taille_vignettes');
		$test_out .= "<p>"._T('info_taille_maximale_images')."</p>";
		$time = time();
		$url = generer_url_action("tester_taille", "arg=3000&time=$time");
		$test_out .= "<iframe style='border:0;height:3em;overflow:hidden;' src='$url'></iframe>";
		$test_out .= "<br style='clear:both;' />";
	}
	else {
		effacer_meta('max_taille_vignettes');
	}

	return ajax_action_greffe("configurer-reducteur", '', 
	  debut_cadre_trait_couleur("image-24.gif", true, "", _T("info_image_process_titre"))
	. debut_cadre_relief("", true)
	.  "<p class='verdana2'>"
	. _T('info_image_process')
	. "</p>"
	. $res
	. "<div class='nettoyeur'></div>"
	. "<p class='verdana2'>"
	. _T('info_image_process2')
	. "</p>"
	. $test_out
	. fin_cadre_relief(true)
	. (!$formats_graphiques ? '' : format_choisi())
	. fin_cadre_trait_couleur(true)
	);
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

	$res .= "<div id='config-preview' class='verdana2' style='display: $style margin-$spip_lang_left: 40px;'>"
	  . "<label for='taille_preview'>"
	  ._T('info_taille_maximale_vignette')
	  . "</label>"
	  . "<br /><input type='text' name='taille_preview' id='taille_preview' value='$taille_preview' size='5' />";
	$res .= " "._T('info_pixels');
	
	$res .= '<br /><br />';
	$res .= "</div>";
	$res .= bouton_radio("creer_preview", "non", _T('item_choix_non_generation_miniature'), $creer_preview != "oui", "changeVisible(this.checked, 'config-preview', 'none', 'block');");

	return 
	  debut_cadre_relief("", true, "", _T('info_generation_miniatures_images'))
	.  ajax_action_post('configurer', 'reducteur', 'config_fonctions', '', $res)
	. fin_cadre_relief(true);
}

// http://doc.spip.org/@afficher_choix_vignette
function afficher_choix_vignette($process) {

	//global $taille_preview;
	$taille_preview = 120;

	// Ici on va tester les capacites de GD independamment des tests realises
	// dans les images spip_image -- qui servent neanmoins pour la qualite
	/* if (function_exists('imageformats')) {
		
	} */


	$class = '';
	if ($process == $GLOBALS['meta']['image_process']) {
	  $class = " selected";
	} 
	return "\n<div class='vignette_reducteur$class'"
	. "><a href='"
	. generer_url_ecrire("config_fonctions", "image_process=$process")
	. "'><img src='"
	. generer_url_action("tester", "arg=$process&time=".time())
	. "' alt='$process' /></a><span>$process</span></div>\n";

}

?>
