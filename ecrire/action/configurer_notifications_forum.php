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


// http://doc.spip.org/@action_configurer_notifications_forum_dist
function action_configurer_notifications_forum_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$res = array();
	foreach ($GLOBALS['liste_des_forums'] as $desc => $val) {
		$name = 'prevenir_auteurs_' . $val;
		if (_request($name)) $res[]=$val;
	}
	ecrire_meta('prevenir_auteurs', $res ? (','.join(',',$res).',') : 'non');
}
?>
