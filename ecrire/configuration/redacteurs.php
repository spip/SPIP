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

function configuration_redacteurs_dist()
{
	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"
	. "\n<tr><td class='verdana2'>"
	. _T('info_question_inscription_nouveaux_redacteurs')."</i></blockquote>"
	. "</td></tr>"
	. "\n<tr><td align='center' class='verdana2'>"
	. afficher_choix('accepter_inscriptions', $GLOBALS['meta']["accepter_inscriptions"],
		array('oui' => _T('item_accepter_inscriptions'),
			'non' => _T('item_non_accepter_inscriptions')), " &nbsp; ")

	. "</td></tr>\n"
	. "</table>\n";

	$res = debut_cadre_trait_couleur("redacteurs-24.gif", true, "", _T('info_inscription_automatique'))
	. ajax_action_post('configurer', 'redacteurs', 'config_contenu','',$res)
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-redacteurs', '', $res);
}
?>
