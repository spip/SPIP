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

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

// faudrait plutot recuperer dans inc_serialbase et inc_auxbase
// mais il faudra prevenir ceux qui affectent les globales qui s'y trouvent
// Afficher la liste de ce qu'on va detruire et demander confirmation 
// ca vaudrait mieux

/**
 * Supprimer les referers
 * @param strinf $titre
 * @param bool $reprise
 * @return string
 */
function base_delete_referers_dist($titre='', $reprise='')
{
	if (!$titre) return; // anti-testeur automatique
	Sql::delete("spip_referers");
	Sql::delete("spip_referers_articles");
	Sql::update("spip_articles", array('referers'=>0));

	// un pipeline pour detruire les tables de referers installees par les plugins ?
	//pipeline('delete_referers', '');
	
	spip_log("raz des referers operee redirige vers " . _request('redirect'));
}
?>
