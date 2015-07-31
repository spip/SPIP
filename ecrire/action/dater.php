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

function action_dater_dist() {
	
	global $action, $arg, $hash, $id_auteur, $redirect;
	include_spip('inc/actions');
	if (!verifier_action_auteur("$action-$arg", $hash, $id_auteur)) {
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	if (!preg_match(",^\W*(\d+)$,", $arg, $r)) {
		spip_log("action_dater_dist $arg pas compris");
	}
	else {
		include_spip('inc/date');
		if (!isset($_POST['avec_redac']))
			spip_query("UPDATE spip_articles SET date='" . format_mysql_date($_POST['annee'], $_POST['mois'], $_POST['jour'], $_POST['heure'], $_POST['minute']) ."'	WHERE id_article=$r[1]");
		else {

			if ($_POST['avec_redac'] == 'non')
				$annee_redac = $mois_redac = $jour_redac = $heure_redac = $minute_redac = 0;
			else  {
				$annee_redac = $_POST['annee_redac'];
				$mois_redac = $_POST['mois_redac'];
				$jour_redac = $_POST['jour_redac'];
				$heure_redac = $_POST['heure_redac'];
				$minute_redac = $_POST['minute_redac'];

				if ($annee_redac<>'' AND $annee_redac < 1001) 
					$annee_redac += 9000;
			}

			spip_query("UPDATE spip_articles SET date_redac='" . format_mysql_date($annee_redac, $mois_redac, $jour_redac, $heure_redac, $minute_redac) ."' WHERE id_article=$r[1]");

		}
		include_spip('inc/rubriques');
		calculer_rubriques();
	}
}
?>
