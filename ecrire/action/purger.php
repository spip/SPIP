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

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

// http://doc.spip.org/@action_purger_dist
function action_purger_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	include_spip('inc/invalideur');

	spip_log("purger $arg");

	switch ($arg) {

	case 'index': 
		include_spip('inc/indexation');
		purger_index();
		creer_liste_indexation();
		break;

	case 'cache': 
		supprime_invalideurs();
		@unlink(_CACHE_RUBRIQUES);
		purger_repertoire(_DIR_CACHE);
		break;

	case 'squelettes':
		purger_repertoire(_DIR_SKELS);
		break;

	case 'vignettes':
		purger_repertoire(_DIR_VAR);
		supprime_invalideurs();
		purger_repertoire(_DIR_CACHE);
		break;
	}

}

?>
