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
// Options du contenu des forums
//

function configuration_contenu_forums_dist(){
	global $spip_lang_left;

	$forums_titre = $GLOBALS['meta']["forums_titre"];
	$forums_texte = $GLOBALS['meta']["forums_texte"];
	$forums_urlref = $GLOBALS['meta']["forums_urlref"];

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"

	. "<tr><td colspan='2' class='verdana2'>"
	. typo(_T('config_activer_champs').':')
	. "</td></tr>"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. _T('info_titre')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('forums_titre', $forums_titre,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. typo(_T('info_texte').':')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('forums_texte', $forums_texte,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"


	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. _T('info_urlref')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('forums_urlref', $forums_urlref,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "</table>";

	$res = debut_cadre_trait_couleur("forum-24.gif", true, "", _T('titre_forum'))
	. ajax_action_post('configurer', 'contenu_forums', 'configuration','',$res)
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-contenu_forums', '', $res);

}
?>
