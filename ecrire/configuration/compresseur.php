<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/config');

function configuration_compresseur_dist()
{
	global $spip_lang_right;

	$res =  "<div class='verdana2'>"
	. _T('info_compresseur_texte')
	. "</div>"
	. "<div class='verdana2'>"
	. afficher_choix('auto_compress', $GLOBALS['auto_compress']?($GLOBALS['meta']["auto_compress"]=='oui'?'oui':'non'):'non',
		array('oui' => _T('info_compresseur_activer'),
			'non' => _T('info_compresseur_desactiver')))
	. "</div>";

	$res = debut_cadre_trait_couleur("", true, "", _T('info_compresseur_titre'))
	.  ajax_action_post('configurer', 'compresseur', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-compresseur", '', $res);
}
?>
