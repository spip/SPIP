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
// Options des rubriques
//

function configuration_rubriques_dist(){
	global $spip_lang_left;

	$rubriques_descriptif = $GLOBALS['meta']["rubriques_descriptif"];
	$rubriques_texte = $GLOBALS['meta']["rubriques_texte"];

	$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">"

	. "<tr><td colspan='2' class='verdana2'>"
	. typo(_T('config_activer_champs').':')
	. "</td></tr>"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. _T('info_descriptif')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('rubriques_descriptif', $rubriques_descriptif,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "<tr>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. typo(_T('info_texte').':')
	. "</td>"
	. "<td align='$spip_lang_left' class='verdana2'>"
	. afficher_choix('rubriques_texte', $rubriques_texte,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</td></tr>\n"

	. "</table>";

	$res = debut_cadre_trait_couleur("rubrique-24.gif", true, "", _T('icone_rubriques'))
	. ajax_action_post('configurer', 'rubriques', 'configuration','',$res)
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-rubriques', '', $res);

}
?>
