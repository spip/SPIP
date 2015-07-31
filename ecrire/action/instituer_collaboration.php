<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_instituer_collaboration_dist
function action_instituer_collaboration_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	$arg = _request('arg');

	if ($arg) {
		include_spip('inc/drapeau_edition');
		if ($arg == 'tous')
			debloquer_tous($GLOBALS['auteur_session']['id_auteur']);
		else
			debloquer_edition($GLOBALS['auteur_session']['id_auteur'], $arg, 'article');
	}
}
?>
