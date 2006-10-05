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
include_spip('inc/texte');

function exec_documenter_dist()
{
	$type = _request("type");
	$s = _request("s");
	$id = intval(_request(($type == 'article') ? 'id_article' : 'id_rubrique'));

	if (!$s)
	  $album = 'documents';
	else  $album = 'portfolio'; 

	if ($type == 'rubrique')
		$flag_editable = acces_rubrique($id);
	else {
		$row = spip_fetch_array(spip_query("SELECT id_rubrique, statut FROM spip_articles WHERE id_article=$id"));
		if (!$flag_editable = acces_rubrique($row['id_rubrique'])) {
			if ($row['statut'] == 'prepa' OR $row['statut'] == 'prop' OR $row['statut'] == 'poubelle')
			  $flag_editable = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur LIMIT 1"));
		}
	}

	if (!$flag_editable) {
		spip_log("Tentative d'intrusion de " . $GLOBALS['auteur_session']['nom'] . " dans " . $GLOBALS['exec']);
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	$f = charger_fonction('documenter', 'inc');
	return $f($id, $type, $album, 'ajax');
}
?>
