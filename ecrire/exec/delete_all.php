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

// http://doc.spip.org/@exec_delete_all_dist
function exec_delete_all_dist()
{
	include_spip('inc/autoriser');
	if (!autoriser('detruire')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$r = generer_url_ecrire('install','',true);
	$admin = charger_fonction('admin', 'inc');
	$admin('delete_all', _T('titre_page_delete_all'), '', $r);
}
?>
