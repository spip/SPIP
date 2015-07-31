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

//
// Articles post-dates
//

function configuration_redirection_dist()
{
	global $spip_lang_left;

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"
	. "<tr><td class='verdana2'>"
	. _T('info_redirection')
	. "</td></tr>"

	. "<tr><td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('articles_redirection', $GLOBALS['meta']["articles_redirection"],
		array('oui' => _T('item_oui'),
			'non' => _T('item_non')))
	. "</td></tr>\n"
	. "</table>\n";

	$res = debut_cadre_relief("", true, "", _L('Redirection d\'article').aide ("artvirt"))
	. ajax_action_post('configurer', 'redirection', 'configuration','',$res)
 	. fin_cadre_relief(true);

	return ajax_action_greffe('configurer-redirection', '', $res);
}
?>
