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

// http://doc.spip.org/@exec_regler_moderation_dist
function exec_regler_moderation_dist()
{
	$id_article = intval(_request('id_article'));
	$script = _request('script');

	if (!autoriser('modererforum','article',$id_article)) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
	include_spip('inc/actions');
	$regler_moderation = charger_fonction('regler_moderation', 'inc');
	ajax_retour($regler_moderation($id_article, $script, "&id_article=$id_article", true));
}
?>
