<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


function exec_base_delete_stats_dist()
{
	include_spip('inc/autoriser');
	if (!autoriser('detruire','_statistiques')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		include_spip('inc/headers');
		$admin = charger_fonction('admin', 'inc');
		$res = $admin('delete_stats', _T('statistiques:bouton_effacer_statistiques'), '');
		if ($res) echo $res; else redirige_url_ecrire('stats_visites','');
		
	}
}
?>
