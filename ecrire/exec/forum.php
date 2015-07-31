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

include_ecrire("inc_presentation");
include_ecrire("inc_texte");
charger_generer_url();
include_ecrire("inc_rubriques");

function liste_numeros_forum($urlforum, $debut, $total)
{
	echo "\n<p>";
	for ($i = 0; $i < $total; $i = $i + 10){
		if ($i > 0) echo " | ";
		if ($i == $debut)
			echo "\n<FONT SIZE='3'><B>$i</B></FONT>";
		else
			echo "\n<A HREF='$urlforum&amp;debut=$i'>$i</A>";
	}
	echo "\n</p>\n";
}

function forum_dist()
{
  global $connect_statut, $debut, $admin;

  $debut = intval($debut);

  if ($admin) {
	debut_page(_T('titre_page_forum'), "redacteurs", "privadm");
	$statutforum = 'privadm';
	$logo = "forum-admin-24.gif";
	$urlforum = generer_url_ecrire('forum_admin');
  } else {
	debut_page(_T('titre_forum'), "redacteurs", "forum-interne");
	$statutforum = 'privrac';
	$logo = "forum-interne-24.gif";
	$urlforum = generer_url_ecrire('forum','', true);
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

  if ($total > 10) liste_numeros_forum($urlforum, $debut, $total);

  echo "<p><div align='center'>";
  icone (_T('icone_poster_message'), generer_url_ecrire("forum_envoi", 
			 "statut=$statutforum&adresse_retour=" .
			 urlencode($urlforum) . 
			 "&titre_message=" .
			 urlencode(filtrer_entites(_T('texte_nouveau_message')))),
       $logo, "creer.gif");
  echo "</div></p>";

  echo "<p align='left'>";
  $limit = $debut ? "LIMIT $debut,10" : "LIMIT 10" ;
  $query_forum="SELECT * FROM spip_forum WHERE statut='$statutforum' AND id_parent=0 ORDER BY date_heure DESC $limit";
  $result_forum = spip_query($query_forum);
 
  afficher_forum($result_forum,$urlforum);
 
  echo "</div>";

  fin_page();
}
?>
