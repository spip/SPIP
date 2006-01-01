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
include_ecrire("inc_urls");
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

function forum_admin_dist()
{
  global $connect_statut, $debut, $admin;

  $debut = intval($debut);

  if ($admin) {
	debut_page(_T('titre_page_forum'), "redacteurs", "privadm");
	$statutforum = 'privadm';
	$logo = "forum-admin-24.gif";
	$urlforum = generer_url_ecrire('forum_admin', 'admin=admin');
  } else {
	debut_page(_T('titre_forum'), "redacteurs", "forum-interne");
	$statutforum = 'privrac';
	$logo = "forum-interne-24.gif";
	$urlforum = generer_url_ecrire('forum_admin', 'admin=');
  }

  debut_gauche();

  debut_droite();

  if ($admin=='oui')
	gros_titre(_T('titre_cadre_forum_administrateur'));
  else
	gros_titre(_T('titre_cadre_forum_interne'));

  if ($admin == 'oui' AND $connect_statut != "0minirezo") {
	echo _T('avis_non_acces_page');
	exit;
  }

  echo "<div class='serif2'>";

  $result_forum = spip_query("SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='$statutforum' AND id_parent=0 LIMIT 11");

  $total =  ($row = spip_fetch_array($result_forum)) ? $row['cnt'] : 0;

  if ($total > 10) liste_numeros_forum($urlforum, $debut, $total);

  echo "<p><div align='center'>";
  icone (_T('icone_poster_message'), 
	 generer_url_ecrire("forum_envoi", 
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


//
// Suppression de forums 
//

# fonction invoquee par calcul dans iframe_action
# Elle n'a rien a faire ici en fait, et devra migrer en inc_forum
# quand on abandonnera les .php 3

function changer_statut_forum_admin($id_forum, $statut) {
	$id_forum = intval($id_forum);
	$result = spip_query("SELECT * FROM spip_forum WHERE id_forum=$id_forum");
	if (!($row = spip_fetch_array($result)))
		return;

	$id_parent = $row['id_parent'];

	// invalider les pages comportant ce forum
	include_ecrire('inc_invalideur');
	include_ecrire('inc_forum');
	$index_forum = calcul_index_forum($row['id_article'], $row['id_breve'], $row['id_rubrique'], $row['id_syndic']);
	suivre_invalideur("id='id_forum/$index_forum'");

	// Signaler au moteur de recherche qu'il faut reindexer le thread
	if ($id_parent) {
		include_ecrire('inc_index');
		marquer_indexer ('forum', $id_parent);
	}

	// changer le statut de toute l'arborescence dependant de ce message
	$id_messages = array($id_forum);
	while ($id_messages) {
		$id_messages = join(',', $id_messages);
		$query_forum = "UPDATE spip_forum SET statut='$statut'
		WHERE id_forum IN ($id_messages)";
		$result_forum = spip_query($query_forum);
		$query_forum = "SELECT id_forum FROM spip_forum
		WHERE id_parent IN ($id_messages)";
		$result_forum = spip_query($query_forum);
		unset($id_messages);
		while ($row = spip_fetch_array($result_forum))
			$id_messages[] = $row['id_forum'];
	}
}

?>
