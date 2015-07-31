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
include_spip('inc/mail');
include_spip('inc/config');

// http://doc.spip.org/@exec_config_contenu_dist
function configuration_visiteurs_dist()
{
	global $connect_statut, $connect_toutes_rubriques, $options, $spip_lang_right, $spip_lang_left,$changer_config, $envoi_now ;


	if ($n = ($forums_publics<>'abo')) {
			$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_articles WHERE accepter_forum='abo' LIMIT 1"));
			$n = !$n['n'];
	}
	if ($n) {
		$res = "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
		$res .= "\n<tr><td class='verdana2'>";
		$res .= _T('info_question_accepter_visiteurs');
		$res .= "</td></tr>";
		$res .= "\n<tr><td style='text-align: $spip_lang_left' class='verdana2'>";
		$res .= afficher_choix('accepter_visiteurs', $GLOBALS['meta']['accepter_visiteurs'],
				       array('oui' => _T('info_option_accepter_visiteurs'),
					'non' => _T('info_option_ne_pas_accepter_visiteurs')));
		$res .= "</td></tr>\n";
		$res .= "</td></tr></table>\n";

		$res = ajax_action_post('configurer', 'visiteurs', 'config_contenu','',$res);
	} else {
		$res = _T('info_forums_abo_invites');
	}

	$res = debut_cadre_trait_couleur("redacteurs-24.gif", true, "", _T('info_visiteurs'))
	. $res
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer-visiteurs', $res);
}
?>
