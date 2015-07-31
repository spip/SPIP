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
	global $id_article, $script;
	$id_article = intval($id_article);

	if (!acces_article($id_article)) {
		spip_log("Tentative d'intrusion de " . $GLOBALS['auteur_session']['nom'] . " dans " . $GLOBALS['exec']);
		include_spip('inc/minipres');
		minipres();
		exit;
	}

	$regler_moderation = charger_fonction('regler_moderation', 'inc');
	ajax_retour($regler_moderation($id_article, $script, "&id_article=$id_article", true));
}
?>
