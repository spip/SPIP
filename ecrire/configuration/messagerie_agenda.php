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

function configuration_messagerie_agenda_dist()
{
	$res = "<div class='verdana2'>"
	. _T('texte_messagerie_agenda')
	. "<br />\n"
	. afficher_choix('messagerie_agenda', $GLOBALS['meta']['messagerie_agenda'],
		array('oui' => _T('item_messagerie_agenda'),
			'non' => _T('item_non_messagerie_agenda')))
	. "</div>";

	$res = debut_cadre_trait_couleur("messagerie-24.gif", true, "", _T('titre_messagerie_agenda'))
	. ajax_action_post('configurer', 'messagerie_agenda', 'config_contenu','',$res)
	 . fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-messagerie_agenda', '', $res);
}
?>
