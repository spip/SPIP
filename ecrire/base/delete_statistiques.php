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

// faudrait plutot recuperer dans inc_serialbase et inc_auxbase
// mais il faudra prevenir ceux qui affectent les globales qui s'y trouvent
// Afficher la liste de ce qu'on va detruire et demander confirmation 
// ca vaudrait mieux

// http://doc.spip.org/@base_delete_statistiques_dist
function base_delete_statistiques_dist($titre='', $reprise='')
{
	if (!$titre) return; // anti-testeur automatique
	sql_delete("spip_visites");
	sql_delete("spip_visites_articles");
	sql_delete("spip_referers");
	sql_delete("spip_referers_articles");
	sql_update("spip_articles", array('visites'=>0, 'referers'=>0, 'popularite'=>0));

	// un pipeline pour detruire les tables de stats installees par les plugins
	pipeline('delete_statistiques', '');
	spip_log("raz des stats operee redirige vers " . _request('redirect'));
}
?>
