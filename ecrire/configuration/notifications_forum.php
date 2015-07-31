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

function configuration_notifications_forum_dist()
{
	$res = '';
	$m = $GLOBALS['meta']['prevenir_auteurs'];
	$l = $GLOBALS['liste_des_forums'];
	unset($l['info_pas_de_forum']);
	foreach ($l as $desc => $val) {
		$name = 'prevenir_auteurs_' . $val;
		$lib = _T($desc);
		$vu = (($m == 'oui') OR strpos($m,",$val,")!==false);
		$res .= "<input type='checkbox' name='$name' value='oui' id='$name'"
			. ($vu ? " checked='checked'" : '')
			. " /> <label for='$name'>"
			. ($vu ? "<b>$lib</b>" : $lib)
			.  "</label><br />";
	}

	$res = "<div class='verdana2'>"
		. _T('info_option_email')
	  . "<br /><br />"
	  . $res
	  . "</div>\n";

	$res = debut_cadre_trait_couleur("mail-forum-24.gif", true, "", _T('info_envoi_forum'))
	. ajax_action_post('configurer_notifications_forum', 0, 'config_contenu','',$res)
	. fin_cadre_trait_couleur(true);

	return ajax_action_greffe('configurer_notifications_forum', 0, $res);
}
?>
