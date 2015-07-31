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

//
// Options des articles
//

function configuration_articles_dist(){
	global $spip_lang_left;

	$articles_surtitre = $GLOBALS['meta']["articles_surtitre"];
	$articles_soustitre = $GLOBALS['meta']["articles_soustitre"];
	$articles_descriptif = $GLOBALS['meta']["articles_descriptif"];
	$articles_chapeau = $GLOBALS['meta']["articles_chapeau"];
	$articles_texte = $GLOBALS['meta']["articles_texte"];
	$articles_ps = $GLOBALS['meta']["articles_ps"];
	$articles_redac = $GLOBALS['meta']["articles_redac"];
	$articles_urlref = $GLOBALS['meta']["articles_urlref"];

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"

	. "<tr><td colspan='2' class='verdana2'>"
	. _T('texte_contenu_articles')
	. "</td></tr>"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. _T('info_surtitre')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('articles_surtitre', $articles_surtitre,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. _T('info_sous_titre')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('articles_soustitre', $articles_soustitre,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. _T('info_descriptif')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('articles_descriptif', $articles_descriptif,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. _T('info_chapeau_2')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('articles_chapeau', $articles_chapeau,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. typo(_T('info_texte').':')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('articles_texte', $articles_texte,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. _T('info_post_scriptum_2')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('articles_ps', $articles_ps,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. _T('info_date_publication_anterieure')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('articles_redac', $articles_redac,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. _T('info_urlref')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('articles_urlref', $articles_urlref,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"


	. "</table>";

	$res = debut_cadre_relief("", true, "", _T('info_contenu_articles').aide ("confart"))
	. ajax_action_post('configurer', 'articles', 'configuration','',$res)
	. fin_cadre_relief(true);

	return ajax_action_greffe('configurer-articles', '', $res);

}
?>
