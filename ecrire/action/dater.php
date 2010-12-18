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

// http://doc.spip.org/@action_dater_dist
function action_dater_dist() {
	
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!preg_match(",^\W*(\d+)\W(\w*)$,", $arg, $r)) {
		spip_log("action_dater_dist $arg pas compris");
	}
	else action_dater_post($r);
}

// http://doc.spip.org/@action_dater_post
function action_dater_post($r)
{
	include_spip('inc/date');
	$type = $r[2];
	$id = $r[1];
	if (!isset($_REQUEST['avec_redac'])) {
		$date = dater_table($id, $type);
	} else {
		if (_request('avec_redac') == 'non')
			$annee_redac = $mois_redac = $jour_redac = $heure_redac = $minute_redac = 0;
		else  {
				$annee_redac = _request('annee_redac');
				$mois_redac = _request('mois_redac');
				$jour_redac = _request('jour_redac');
				$heure_redac = _request('heure_redac');
				$minute_redac = _request('minute_redac');

				if ($annee_redac<>'' AND $annee_redac < 1001) 
					$annee_redac += 9000;
		}

		$date = format_mysql_date($annee_redac, $mois_redac, $jour_redac, $heure_redac, $minute_redac);
		include_spip('inc/modifier');
		revision_article($r[1],array("date_redac" => $date));
	}

	// a priori fait doublon avec instituer_xx utilise dans dater_table()
	// mais on laisse pour ne pas introduire de bug dans cette branche
	if (($type == 'article')
	AND $GLOBALS['meta']["post_dates"] == "non") {
		$t = sql_fetsel("statut, id_rubrique", "spip_articles", "id_article=$id");
		if ($t['statut'] == 'publie') {
			include_spip('inc/rubriques');
			if  (strtotime($date) >  time())
			  depublier_branche_rubrique_if($t['id_rubrique']);
			else
			  publier_branche_rubrique($t['id_rubrique']);
			calculer_prochain_postdate();
		}
	}
}

function dater_table($id, $type)
{
	$trouver_table = charger_fonction('trouver_table', 'base');
	$nom = table_objet($type);
	$desc = $trouver_table($nom);
        $table = $desc['table'];
        $col_id =  @$desc['key']["PRIMARY KEY"];
	if (!$table OR !$col_id) {
		spip_log("action_dater: table $type ?");
		return;
	}
	include_spip('public/interfaces');
	$champ = @$GLOBALS['table_date'][$nom];
	if (!$champ) $champ = 'date';
	$date = format_mysql_date(_request('annee'), _request('mois'), _request('jour'), _request('heure'), _request('minute'));
	// utiliser instituer_xx si dispo
	if (include_spip('action/editer_'.$type) AND function_exists($f='instituer_'.$type)){
		$f($id,array($champ => $date));
	}
	else
		sql_updateq($table, array($champ => $date), "$col_id=$id");
	return $date;
}
?>
