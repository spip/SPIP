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

// http://doc.spip.org/@action_dater_dist
function action_dater_dist() {
	
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	$arg = _request('arg');

	if (!preg_match(",^\W*(\d+)$,", $arg, $r)) {
		spip_log("action_dater_dist $arg pas compris");
	}
	else action_dater_post($r);
}

// http://doc.spip.org/@action_dater_post
function action_dater_post($r)
{
	include_spip('inc/date');
	if (!isset($_REQUEST['avec_redac']))

		spip_query("UPDATE spip_articles SET date='" . format_mysql_date(_request('annee'), _request('mois'), _request('jour'), _request('heure'), _request('minute')) ."'	WHERE id_article=$r[1]");

	else {
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

		spip_query("UPDATE spip_articles SET date_redac='" . format_mysql_date($annee_redac, $mois_redac, $jour_redac, $heure_redac, $minute_redac) ."' WHERE id_article=$r[1]");

	}
	include_spip('inc/rubriques');
	calculer_rubriques();
}
?>
