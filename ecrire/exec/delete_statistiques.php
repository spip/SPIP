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
function exec_delete_statistiques_dist()
{
	include_spip('inc/autoriser');
	if (!autoriser('detruire','statistiques')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$r = generer_url_ecrire('statistiques_visites','',true);
	$admin = charger_fonction('admin', 'inc');
	$admin('delete_statistiques', _L('Effacer les statistiques')/*_T('titre_page_delete_statistiques')*/, '', $r);
}
?>
