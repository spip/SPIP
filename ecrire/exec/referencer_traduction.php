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

function exec_referencer_traduction_dist()
{
	$id_article = intval(_request('id_article'));

	if (!acces_article($id_article)) {
		spip_log("Tentative d'intrusion de " . $GLOBALS['auteur_session']['nom'] . " dans " . $GLOBALS['exec']);
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	$row = spip_fetch_array(spip_query("SELECT id_trad, id_rubrique FROM spip_articles WHERE id_article=$id_article"));

	$referencer_traduction = charger_fonction('referencer_traduction', 'inc');
	ajax_retour($referencer_traduction($id_article, 'ajax', $row['id_rubrique'], $row['id_trad'])); 
}
?>
