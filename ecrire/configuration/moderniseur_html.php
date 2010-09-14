<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/config');
include_spip('inc/presentation');
include_spip('inc/utils');

function configuration_moderniseur_html_dist()
{
	global $spip_lang_right;

	$val_actuelle = html5_permis() ? 'html5' : 'html4';
	$res = "<div>"
	. _T('texte_compatibilite_html')
	. "</div>"
	. "<div>"
	. afficher_choix('version_html_max',$val_actuelle,
		array('html4' => _T('item_version_html_max_html4'),
			'html5' => _T('item_version_html_max_html5')))
	. "</div>\n<div>\n<p><em>"
	. _T('texte_compatibilite_html_attention')
	. "</em></p>\n</div>";


	$res = debut_cadre_trait_couleur("compat-24.png", true, "", _T('info_compatibilite_html'))
	.  ajax_action_post('configuration', 'moderniseur_html', 'config_fonctions', '#configurer-moderniseur_html', $res)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-moderniseur_html", '', $res);
}
?>
