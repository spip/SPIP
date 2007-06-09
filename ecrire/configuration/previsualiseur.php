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

# non = personne n'est autorise a previsualiser (defaut)
# oui = les admins
# 1comite = admins et redacteurs

function configuration_previsualiseur_dist()
{
	$res = "<div class='verdana2'>"
	. _T('info_preview_texte')
	. "</div>"
	. "<div class='verdana2'>"
	. afficher_choix('preview', $GLOBALS['meta']["preview"],
		array('oui' => _T('info_preview_admin'),
			'1comite' => _T('info_preview_comite'),
			'non' => _T('info_preview_desactive')
		      )
			 )
	. "</div>";

	$res = debut_cadre_trait_couleur("naviguer-site.png", true, "", _T('previsualisation')
	. aide("previsu"))
	. ajax_action_post('configurer', 'previsualiseur', 'config_fonctions', '', $res)
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-previsualiseur", '', $res);
}
?>
