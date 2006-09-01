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

// http://doc.spip.org/@exec_dater_dist
function exec_dater_dist()
{
	global $id_article;
	$id_article = intval($id_article);

	if (!acces_article($id_article)) {
		spip_log("Tentative d'intrusion de " . $GLOBALS['auteur_session']['nom'] . " dans " . $GLOBALS['exec']);
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	$row = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article=$id_article"));

	$statut_article = $row['statut'];
	$date = $row["date"];
	$date_redac = $row["date_redac"];

	include_spip('inc/actions');
	include_spip('exec/articles');

	return formulaire_dater($id_article, 'ajax', $statut_article, $date, $date_redac);
}

?>
