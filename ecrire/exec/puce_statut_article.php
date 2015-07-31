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

include_spip('inc/presentation');
include_spip('inc/autoriser');

// http://doc.spip.org/@exec_puce_statut_article_dist
function exec_puce_statut_article_dist()
{
	$id = _request('id');
	$s = spip_query(
	"SELECT id_rubrique,statut FROM spip_articles WHERE id_article="._q($id));
	$r = spip_fetch_array($s);

	ajax_retour(puce_statut_article($id,$r['statut'],$r['id_rubrique'],true));
}

?>