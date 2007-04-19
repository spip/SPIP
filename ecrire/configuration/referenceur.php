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

function configuration_referenceur_dist()
{
  global $spip_lang_right;

	$res =  "<p>"._T('texte_multilinguisme')."</p>"
	. "<div>"
	. _T('info_multi_articles')
	. "<div style='text-align: $spip_lang_right;'>"
	. afficher_choix('multi_articles', $GLOBALS['meta']['multi_articles'],
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</div>"
	. "</div>"
	. "<div>"
	. _T('info_multi_rubriques')
	. "<div style='text-align: $spip_lang_right;'>"
	. afficher_choix('multi_rubriques', $GLOBALS['meta']['multi_rubriques'],
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</div>"
	. "</div>";

	if  ($GLOBALS['meta']['multi_rubriques'] == 'oui') {
		$res .= "\n<div>"
		. _T('info_multi_secteurs')
		. "<div style='text-align: $spip_lang_right;'>"
		. afficher_choix('multi_secteurs', $GLOBALS['meta']['multi_secteurs'],
			array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
		. "</div>"
		. "</div>";
	} else
		$res .= "<input type='hidden' name='multi_secteurs' value='".$GLOBALS['meta']['multi_secteurs']."' />";

	if (($GLOBALS['meta']['multi_rubriques'] == 'oui') OR ($GLOBALS['meta']['multi_articles'] == 'oui')) {
		$res .= "<hr />"
		. "<p>"._T('texte_multilinguisme_trad')."</p>";

		$res .= _T('info_gerer_trad')
		. "<div style='text-align: $spip_lang_right;'>"
		. afficher_choix('gerer_trad', $GLOBALS['meta']['gerer_trad'],
			array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
		. "</div>";
	} else
		$res .= "<input type='hidden' name='gerer_trad' value='".$GLOBALS['meta']['gerer_trad']."' />";

	$res = debut_cadre_couleur("traductions-24.gif", true, "", _T('info_multilinguisme'))
	. ajax_action_post('configurer', 'referenceur', 'config_multilang', '', $res)
	. fin_cadre_couleur(true);

	return ajax_action_greffe("configurer-referenceur", $res);
}
?>
