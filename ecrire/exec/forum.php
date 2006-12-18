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
include_spip('inc/texte');
charger_generer_url();


// http://doc.spip.org/@liste_numeros_forum
function liste_numeros_forum($script, $debut, $total)
{
	echo "\n<p>";
	for ($i = 0; $i < $total; $i = $i + 10){
		if ($i > 0) echo " | ";
		if ($i == $debut)
			echo "\n<font size='3'><b>$i</b></font>";
		else
			echo "\n<a href='", generer_url_ecrire($script, "debut=$i"), "'>$i</a>";
	}
	echo "\n</p>\n";
}

// http://doc.spip.org/@exec_forum_dist
function exec_forum_dist()
{
  global $connect_statut, $debut, $admin;

  $debut = intval($debut);

  $commencer_page = charger_fonction('commencer_page', 'inc');
  if ($admin) {
	echo $commencer_page(_T('titre_page_forum'), "forum", "privadm");
	$statutforum = 'privadm';
	$logo = "forum-admin-24.gif";
	$script = 'forum_admin';
  } else {
	echo $commencer_page(_T('titre_forum'), "forum", "forum-interne");
	$statutforum = 'privrac';
	$logo = "forum-interne-24.gif";
	$script = 'forum';
  }

  debut_gauche();

  debut_droite();

  if ($admin)
	gros_titre(_T('titre_cadre_forum_administrateur'));
  else
	gros_titre(_T('titre_cadre_forum_interne'));

  if ($admin AND $connect_statut != "0minirezo") {
	echo _T('avis_non_acces_page');
	exit;
  }

  echo "<div class='serif2'>";

  $result_forum = spip_query("SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='$statutforum' AND id_parent=0 LIMIT 11");

  $total =  ($row = spip_fetch_array($result_forum)) ? $row['cnt'] : 0;

  if ($total > 10) liste_numeros_forum($script, $debut, $total);

  
  echo "\n<div align='center'>\n";
  icone (_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=$statutforum&script=$script"), $logo, "creer.gif");
  echo "\n</div>";

  echo "\n<p align='left'>";
  $limit = $debut ? "LIMIT $debut,10" : "LIMIT 10" ;
  $result_forum = spip_query("SELECT * FROM spip_forum WHERE statut='$statutforum' AND id_parent=0 ORDER BY date_heure DESC $limit");
 
  echo afficher_forum($result_forum,$script,'');
 
  echo "</p></div>";

  echo fin_page();
}
?>
