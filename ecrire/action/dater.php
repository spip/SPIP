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

		$date = format_mysql_date(_request('annee'), _request('mois'), _request('jour'), _request('heure'), _request('minute'));
		if ($type == 'article')
			sql_updateq("spip_articles", array("date" => $date), "id_article=$r[1]");
		else action_dater_breve_syndic($id, $type);
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
		sql_updateq("spip_articles", array("date_redac" => $date), "id_article=$r[1]");

	}

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

// Breves et Syndications ne sont pas post-datables

// http://doc.spip.org/@action_dater_breve_syndic
function action_dater_breve_syndic($id, $type)
{
	if (_request('jour')) {
		$annee = _request('annee');
		$mois = _request('mois');
		$jour = _request('jour');
		if ($annee == "0000") $mois = "00";
		if ($mois == "00") $jour = "00";
		if ($type == 'breve')
		  sql_updateq("spip_breves", array("date_heure" => "$annee-$mois-$jour"), "id_breve=$id");
		else sql_updateq("spip_syndic", array("date" => "$annee-$mois-$jour"), "id_syndic=$id");
	}
}
?>
