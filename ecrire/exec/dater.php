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

// http://doc.spip.org/@exec_dater_dist
function exec_dater_dist()
{
	$type = _request('type');
	$id = intval(_request('id'));

	if (($GLOBALS['auteur_session']['statut'] != '0minirezo')
	OR ($type == 'article' AND    !autoriser('modifier','article',$id))
	OR (!preg_match('/^\w+$/',$type))) { // securite 
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$table = ($type=='syndic') ? 'syndic' : ($type . 's');
	$row = spip_fetch_array(spip_query("SELECT * FROM spip_$table WHERE id_$type=$id"));

	$statut = $row['statut'];
	$date = $row[($type!='breve')?"date":"date_heure"];
	$date_redac = isset($row["date_redac"]) ? $row["date_redac"] : '';

	$script = ($type=='article')? 'articles' : ($type == 'breve' ? 'breves_voir' : 'sites');
	$dater = charger_fonction('dater', 'inc');
	ajax_retour($dater($id, 'ajax', $statut, $type, $script, $date, $date_redac));
}
?>
