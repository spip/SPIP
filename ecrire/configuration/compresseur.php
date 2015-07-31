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

function configuration_compresseur_dist()
{
	global $spip_lang_right;

	$res = '';

	// Compression du flux HTTP
	if (!function_exists('ob_gzhandler')) {
		$GLOBALS['meta']['auto_compress_http'] = 'non';
	} else {
		$res .= debut_cadre_relief("", true, "", _T('titre_compresser_flux_http'))
			.  "<p class='verdana2'>"
			. _T('texte_compresseur_page')
			. "</p>"
			. "<p class='verdana2'>"
			. _T('info_compresseur_gzip', array('testgzip' => propre('[->'.parametre_url('http://redbot.org/', 'uri',$GLOBALS['meta']['adresse_site']).']'))
			)
			. "</p>"

			. "<div class='verdana2'>"
			. "<p class='verdana2'>"
			. _T('info_question_activer_compresseur')
			. "</p>"
			. afficher_choix('auto_compress_http',
				($GLOBALS['meta']['auto_compress_http'] != 'non') ? 'oui' : 'non',
				array(
					'oui' => _T('item_compresseur'),
					'non' => _T('item_non_compresseur')
				)
			)
			. "</div>"
		. fin_cadre_relief(true);
	}


	// Compression des scripts et css
	$res .= debut_cadre_relief("", true, "", _T('titre_compacter_script_css'))
		.  "<p class='verdana2'>"
		. _T('texte_compacter_script_css')
		. " "
		. "</p>"

		. "<div class='verdana2'>"
		. "<p class='verdana2'>"
		. _T('info_question_activer_compactage_js')
		. "</p>"
		. afficher_choix('auto_compress_js',
			($GLOBALS['meta']['auto_compress_js'] != 'non') ? 'oui' : 'non',
			array(
				'oui' => _T('item_compresseur'),
				'non' => _T('item_non_compresseur')
			)
		)
		. "</div>"

		. "<div class='verdana2'>"
		. "<p class='verdana2'>"
		. _T('info_question_activer_compactage_css')
		. "</p>"
		. afficher_choix('auto_compress_css',
			($GLOBALS['meta']['auto_compress_css'] != 'non') ? 'oui' : 'non',
			array(
				'oui' => _T('item_compresseur'),
				'non' => _T('item_non_compresseur')
			)
		)
		. "</div>"

		. "<p><em>"._T('texte_compacter_avertissement')."</em></p>"


		. fin_cadre_relief(true);




	$res = '<br />'.debut_cadre_trait_couleur("", true, "",
		_T('info_compresseur_titre'))
	.  ajax_action_post('configurer', 'compresseur', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-compresseur", '', $res);
}
?>
