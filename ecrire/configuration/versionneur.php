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

function configuration_versionneur_dist()
{
	global $spip_lang_right;

	$res =  "<div class='verdana2'>"
	. _T('info_historique_texte')
	. "</div>"
	. "<div class='verdana2'>"
	. afficher_choix('articles_versions', $GLOBALS['meta']["articles_versions"],
		array('oui' => _T('info_historique_activer'),
			'non' => _T('info_historique_desactiver')))
	. "</div>";

	$res = debut_cadre_trait_couleur("historique-24.gif", true, "", _T('info_historique_titre').aide("suivimodif"))
	.  ajax_action_post('configurer', 'versionneur', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-versionneur", '', $res);
}
?>
