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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_calendrier_dist
function exec_calendrier_dist()
{
	exec_calendrier_args(agenda_controle(), _request('type'), _request('echelle'), _request('partie_cal'));
}

function exec_calendrier_args($time, $type, $echelle, $partie_cal)
{
	if ($time < 0) $time = time();

	if (!$type)
		$type = 'mois';
	elseif ($type == 'semaine')
		$GLOBALS['afficher_bandeau_calendrier_semaine'] = true;

	$ancre = 'calendrier-1';
	$r = generer_url_ecrire('calendrier', "type=$type") . "#$ancre";
	$r = http_calendrier_init($time, $type, $echelle, $partie_cal, $r);

	if (_AJAX) {
		ajax_retour($r);
	} else {
		$date = date("Y-m-d", $time);
		if ($type == 'jour') {
			$titre = nom_jour($date)." ". affdate_jourcourt($date);
		}  else {
			$titre = _T('titre_page_calendrier',
					array('nom_mois' => nom_mois($date),
						'annee' => annee($date)));
		}

		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page($titre, "accueil", "calendrier");
// ne produit rien par defaut, mais est utilisee par le plugin agenda
		echo barre_onglets("calendrier", "calendrier"); 
		echo debut_grand_cadre(true);
		echo "\n<div>&nbsp;</div>\n<div id='", $ancre, "'>",$r,'</div>';
		echo fin_grand_cadre(true);
		echo fin_page();
	}
}

?>
