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

// http://doc.spip.org/@exec_referencer_traduction_dist
function exec_referencer_traduction_dist()
{
	$id_article = intval(_request('id_article'));

	if (!autoriser('modifier','article',$id_article)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		include_spip('inc/actions');
		$row = sql_fetsel("id_trad, id_rubrique", "spip_articles", "id_article=$id_article");

		$referencer_traduction = charger_fonction('referencer_traduction', 'inc');
		ajax_retour($referencer_traduction($id_article, 'ajax', intval($row['id_rubrique']), intval($row['id_trad']))); 
	}
}
?>
