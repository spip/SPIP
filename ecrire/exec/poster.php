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

// http://doc.spip.org/@exec_poster_dist
function exec_poster_dist()
{
	global $id_article, $script;
	$id_article = intval($id_article);

	include_spip('inc/forum');
	include_spip('inc/actions');

	return formulaire_poster($id_article, $script, "&id_article=$id_article", true);
}
?>
