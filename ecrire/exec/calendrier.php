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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_calendrier_dist
function exec_calendrier_dist()
{
  $type = _request('type');

  $date = date("Y-m-d", time()); 
  if ($type == 'semaine') {

	$GLOBALS['afficher_bandeau_calendrier_semaine'] = true;

	$titre = _T('titre_page_calendrier',
		    array('nom_mois' => nom_mois($date), 'annee' => annee($date)));
	  }
  elseif ($type == 'jour') {
	$titre = nom_jour($date)." ". affdate_jourcourt($date);
 }
  else {
	$titre = _T('titre_page_calendrier',
		    array('nom_mois' => nom_mois($date), 'annee' => annee($date)));
	  }
  $ancre = 'calendrier-1';

  $r = http_calendrier_init('', $type, '','',generer_url_ecrire('calendrier', ($type ? "type=$type" : '')) . "#$ancre");

  if (_request('var_ajaxcharset')) 
    ajax_retour($r);
  else {
	  $commencer_page = charger_fonction('commencer_page', 'inc');
	  echo $commencer_page($titre, "accueil", "calendrier");
	  echo debut_grand_cadre(true);
	  echo "\n<div>&nbsp;</div>\n<div id='", $ancre, "'>",$r,'</div>';
	  echo fin_grand_cadre(true);
	  echo fin_page();
  }
}

?>
