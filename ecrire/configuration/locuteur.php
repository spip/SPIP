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

function configuration_locuteur_dist()
{
	global $spip_lang_right;

	include_spip('inc/lang_liste');
	$langues = $GLOBALS['codes_langues'];
	$cesure = floor((count($langues) + 1) / 2);

	$langues_installees = explode(',', $GLOBALS['meta']['langues_proposees']);
	$langues_autorisees = explode(',', $GLOBALS['meta']['langues_multilingue']);

	while (list(,$l) = each ($langues_installees)) {
		$langues_trad[$l] = true;
	}

	while (list(,$l) = each ($langues_autorisees)) {
		$langues_auth[$l] = true;
	}

	$l_bloquees_tmp = explode(',',$GLOBALS['meta']['langues_utilisees']);
	while (list(,$l) = each($l_bloquees_tmp)) {
		$langues_bloquees[$l] = true;
	}

	$res = "<p class='verdana2'>"
	. _T('info_multi_langues_choisies')
	. '</p>'
	. "\n<table width='100%' cellspacing='10'><tr><td style='width: 50%'  class='verdana1'>";

	while (list($code_langue) = each($langues_bloquees)) {
		$i++;
		$nom_langue = $langues[$code_langue];
		if ($langues_trad[$code_langue]) $nom_langue = "<span style='text-decoration: underline'>$nom_langue</span>";

		$res .= "\n<div class='langues_bloquees'>";
		$res .= "\n<input type='hidden' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue' />";
		$res .= "\n<input type='checkbox' checked='checked' disabled='disabled' />";
		$res .=  $nom_langue ."\n&nbsp; &nbsp;<span style='color: #777777'>[$code_langue]</span>";
		$res .= "</div>";

		if ($i == $cesure) $res .= "\n</td><td style='width: 50%' class='verdana1'>";
	}

	$res .= "\n<div>&nbsp;</div>";

	while (list($code_langue, $nom_langue) = each($langues)) {
		if ($langues_bloquees[$code_langue]) continue;
		$i++;
		$res .= "\n<div>";
		if ($langues_trad[$code_langue]) $nom_langue = "<span style='text-decoration: underline'>$nom_langue</span>";

		if ($langues_auth[$code_langue]) {
			$res .= "<input type='checkbox' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue' checked='checked' />";
			$nom_langue = "<b>$nom_langue</b>";
		}
		else {
			$res .= "<input type='checkbox' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue' />";
		}
		$res .=  "\n<label for='langue_auth_$code_langue'>$nom_langue</label> &nbsp; &nbsp;<span style='color: #777777'>[$code_langue]</span>";

		$res .= "</div>";

		if ($i == $cesure) $res .= "</td><td style='width: 50%' class='verdana1'>";
	}

	$res .= "</td></tr></table>"
	  . "<div class='verdana1'>"._T("info_multi_langues_soulignees")."</div>";

	$res = debut_cadre_relief("langues-24.gif", true)
	. ajax_action_post('configurer', 'locuteur', 'config_multilang', '', $res)
	. fin_cadre_relief(true);

	return ajax_action_greffe("configurer-locuteur", '', $res);
}
?>
