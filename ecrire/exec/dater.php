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
	exec_dater_args(intval(_request('id')), _request('type'));
}

// http://doc.spip.org/@exec_dater_args
function exec_dater_args($id, $type)
{
	if (!$id OR !autoriser('voir',$type,$id)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		$table = table_objet_sql($type);
		if (!$table) {
			spip_log("dater, type inconnu: $type");
			$type = 'article';
			$table = table_objet_sql($type);
		}
		$row = sql_fetsel("*", $table, "id_$type=$id");
		$statut = $row['statut'];
		$date = $row[($type!='breve')?"date":"date_heure"];
		$date_redac = isset($row["date_redac"]) ? $row["date_redac"] : '';
		$script = ($type=='article')? 'articles' : ($type == 'breve' ? 'breves_voir' : 'sites');
		$dater = charger_fonction('dater', 'inc');
		ajax_retour($dater($id, 'ajax', $statut, $type, $script, $date, $date_redac));
	}
}
?>
