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

// http://doc.spip.org/@exec_dater_dist
function exec_dater_dist()
{
	exec_dater_args(intval(_request('id')), _request('type'), _request('script'));
}

// http://doc.spip.org/@exec_dater_args
function exec_dater_args($id, $type, $script)
{
	if (!$id OR !autoriser('voir',$type,$id)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		$table = table_objet_sql($type);
		if (!id_table_objet($table)) {
			spip_log("dater: $type table inconnue");
			$type = 'article';
			$table = table_objet_sql($type);
		}
		if (!preg_match('/^[_\w]+$/', $script)) $script = 'articles';
		if (!function_exists($f = "dater_retour_$script")) $f = "dater_retour_articles";
		include_spip('inc/actions');
		ajax_retour($f($id, $type, $table));
	}
}

function dater_retour_articles($id, $type, $table)
{
	$r = sql_fetsel("date, date_redac,statut", $table, "id_$type=$id");
	$date_redac = $r["date_redac"];
	$dater = charger_fonction('dater', 'inc');
	return $dater($id, 'ajax', $r['statut'], $type, 'articles', $r['date'], $date_redac);
}

function dater_retour_breves_voir($id, $type, $table)
{
	$r = sql_fetsel("statut, date_heure", $table, "id_$type=$id");
	$dater = charger_fonction('dater', 'inc');
	return $dater($id, 'ajax', $r['statut'], $type, 'breves_voir', $r['date_heure']);
}

function dater_retour_sites($id, $type, $table)
{
	$r = sql_fetsel("statut, date", $table, "id_$type=$id");
	$dater = charger_fonction('dater', 'inc');
	return $dater($id, 'ajax', $r['statut'], $type, 'sites', $r['date']);
}
?>
