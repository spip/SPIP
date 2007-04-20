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

function configuration_correcteur_dist()
{
	$res = "<div class='verdana2'>"
	. _T('ortho_avis_privacy')
	. "</div>"
	. "\n<div class='verdana2'>"
	. "\n<blockquote class='spip'><p>"
	. _T('ortho_avis_privacy2')
	. "\n</p></blockquote>"
	. "\n</div>"
	. "<div class='verdana2'>"
	. afficher_choix('articles_ortho', $GLOBALS['meta']["articles_ortho"],
		array('oui' => _T('info_ortho_activer'),
			'non' => _T('info_ortho_desactiver')))
	. "</div>";

	$res = debut_cadre_trait_couleur("ortho-24.gif", true, "", _T('ortho_orthographe').aide("corrortho"))
	.  ajax_action_post('configurer', 'correcteur', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true);

 	return  ajax_action_greffe("configurer-correcteur", $res);
}
?>
