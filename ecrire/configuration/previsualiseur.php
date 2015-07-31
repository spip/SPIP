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

// Formulaire pour fixer qui peut previsualiser
// Gestion des libelles a revoir

function configuration_previsualiseur_dist()
{
	$recom = array("info_administrateurs" => _T('info_preview_admin'),
                       "info_redacteurs" =>  _T('info_preview_comite'));

	$voir = $GLOBALS['meta']["preview"];

	$res = '';

	foreach($GLOBALS['liste_des_statuts'] as $k => $v) {
		if (isset($recom[$k])) {
			$vu = strpos($voir,",$v,")!==false;
			$lib = _T($k);

			$res .= "<input type='checkbox' name='preview[]' value='$v' id='preview$v'"
			. ($vu ? " checked='checked'" : '')
			. " /> "
			. ($vu ? "<b>$lib</b>" : $lib)
			.  "<br />";
		}
	}

	$res = "<div class='verdana2'>"
	. _T('info_preview_texte')
	. "<br /><br />"
	. $res
	. "</div>";

	$res = debut_cadre_trait_couleur("naviguer-site.png", true, "", _T('previsualisation')
	. aide("previsu"))
	. ajax_action_post('configurer_previsualiseur', 0, 'config_fonctions', '', $res)
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer_previsualiseur", 0, $res);
}
?>
