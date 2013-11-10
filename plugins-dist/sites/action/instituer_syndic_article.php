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

// http://doc.spip.org/@action_instituer_syndic_article_dist
function action_instituer_syndic_article_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	list($id_syndic_article, $statut) = preg_split('/\W/', $arg);

	if ($id_syndic_article = intval($id_syndic_article)
	  AND $id_syndic = Sql::getfetsel('id_syndic','spip_syndic_articles',"id_syndic_article=".intval($id_syndic_article))
		AND autoriser('moderer','site',$id_syndic)) {
		Sql::updateq("spip_syndic_articles", array("statut" => $statut), "id_syndic_article=".intval($id_syndic_article));
	}

}

?>
