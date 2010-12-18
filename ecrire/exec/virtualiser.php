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

// http://doc.spip.org/@exec_virtualiser_dist
function exec_virtualiser_dist()
{
	$id_article = intval(_request('id_article'));

	if (!autoriser('modifier', 'article', $id_article)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		include_spip('inc/actions');
		$r = sql_fetsel("chapo", "spip_articles", "id_article=$id_article");

		$virtuel = $r['chapo'];

		if (substr($virtuel, 0, 1) == '=') {
			$virtuel = substr($virtuel, 1);
		}

		$virtualiser = charger_fonction('virtualiser', 'inc');
		ajax_retour($virtualiser($id_article, $virtuel, "articles", "id_article=$id_article"));
	}
}
?>
