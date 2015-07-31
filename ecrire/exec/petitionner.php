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

// http://doc.spip.org/@exec_petitionner_dist
function exec_petitionner_dist()
{
	$id_article = intval(_request('id_article'));
	$script = _request('script');

	if (!autoriser('modererpetition','article',$id_article)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		include_spip('inc/actions');
		$petitionner = charger_fonction('petitionner', 'inc');
		ajax_retour($petitionner($id_article, $script, "id_article=$id_article", 'ajax'));
	}
}
?>
