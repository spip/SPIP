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

// http://doc.spip.org/@exec_editer_auteurs_dist
function exec_editer_auteurs_dist()
{
	$id_article = intval(_request('id_article'));

	if (! acces_article($id_article)) {
		spip_log("Tentative d'intrusion de " . $GLOBALS['auteur_session']['nom'] . " dans " . $GLOBALS['exec']);
		include_spip('inc/minipres');
		minipres();
		exit;
	}

	$editer_auteurs = charger_fonction('editer_auteurs', 'inc');
	ajax_retour($editer_auteurs($id_article, 'ajax', _request('cherche_auteur'), _request('ids')));
}
?>
