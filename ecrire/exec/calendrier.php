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

// http://doc.spip.org/@exec_calendrier_dist
function exec_calendrier_dist()
{
  global $type;
// icones standards, fonction de la direction de la langue

  global $bleu, $vert, $jaune, $spip_lang_rtl;
  $bleu = http_img_pack("m_envoi_bleu$spip_lang_rtl.gif", 'B', "class='calendrier-icone'");
  $vert = http_img_pack("m_envoi$spip_lang_rtl.gif", 'V', "class='calendrier-icone'");
  $jaune= http_img_pack("m_envoi_jaune$spip_lang_rtl.gif", 'J', "class='calendrier-icone'");

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

  if (_request('var_ajaxcharset')) return $r;

  debut_page($titre, "accueil", "calendrier");
  echo "\n<div>&nbsp;</div>\n<div id='", $ancre, "'>",$r,'</div>';
  echo fin_page();
}

// http://doc.spip.org/@http_calendrier_ics_message
function http_calendrier_ics_message($annee, $mois, $jour, $large)
{	
  global $bleu, $vert,$jaune;
  $b = _T("lien_nouvea_pense_bete");
  $v = _T("lien_nouveau_message");
  $j=  _T("lien_nouvelle_annonce");

  return 
    http_href(generer_url_ecrire("message_edit","rv=$annee-$mois-$jour&new=oui&type=pb"), 
	      $bleu . ($large ? $b : ''), 
	      $b,
	      'color: blue;',
	      'calendrier-arial10') .
    "\n" .
    http_href(generer_url_ecrire("message_edit","rv=$annee-$mois-$jour&new=oui&type=normal"),
	      $vert . ($large ? $v : ''), 
	      $v,
	      'color: green;',
	      'calendrier-arial10') .
    (($GLOBALS['connect_statut'] != "0minirezo") ? "" :
     ("\n" .
      http_href(generer_url_ecrire("message_edit","rv=$annee-$mois-$jour&new=oui&type=affich"),
		$jaune . ($large ? $j : ''), 
		$j,
		'color: #ff9900;',
		'calendrier-arial10')));
}

// http://doc.spip.org/@http_calendrier_aide_mess
function http_calendrier_aide_mess()
{
  global $bleu, $vert, $jaune, $spip_lang_left;
  return
   "\n<br /><br /><br />\n<font face='arial,helvetica,sans-serif' size='2'><table width='700'>\n<tr><th style='text-align: $spip_lang_left; font-weight: bold;'> " . _T('info_aide').
    "</th></tr><tr><td>$bleu\n"._T('info_symbole_bleu')."\n" .
    "</td></tr><tr><td>$vert\n"._T('info_symbole_vert')."\n" .
    "</th></tr><tr><td>$jaune\n"._T('info_symbole_jaune')."\n" .
    "</td></tr>\n</table></font>";
 }

?>
