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

function configuration_futurs()
{
	global $spip_lang_left;

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"
	. "<tr><td class='verdana2'>"
	. _T('texte_publication_articles_post_dates')
	. "</td></tr>"

	. "<tr><td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('post_dates', $GLOBALS['meta']["post_dates"],
		array('oui' => _T('item_publier_articles'),
			'non' => _T('item_non_publier_articles')))
	. "</td></tr>\n"
	. "</table>\n";

	$res = debut_cadre_relief("", true, "", _T('titre_publication_articles_post_dates').aide ("confdates"))
	. ajax_action_post('configurer', 'futurs', 'configuration','',$res)
 	. fin_cadre_relief(true);

	return ajax_action_greffe('configurer-futurs', $res);
}
?>
