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

function configuration_messagerie_agenda_dist()
{
	$res = "<div class='verdana2'>"
	. _L('Une messagerie permet aux r&#233;dacteurs du site de communiquer entre eux directement dans l&#8217;espace priv&#233; du site. Elle est associ&#233;e &#224; un agenda.')
	. "<br />\n"
	. afficher_choix('messagerie_agenda', $GLOBALS['meta']['messagerie_agenda'],
		array('oui' => _L('Activer la messagerie et l&#8217;agenda'),
			'non' => _T('D&#233;sactiver la messagerie et l&#8217;agenda')))
	. "</div>";

	$res = debut_cadre_trait_couleur("messagerie-24.gif", true, "", _L('Messagerie et agenda'))
	. ajax_action_post('configurer', 'messagerie_agenda', 'config_contenu','',$res)
	 . fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-messagerie_agenda', '', $res);
}
?>
