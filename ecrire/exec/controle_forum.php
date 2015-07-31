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
charger_generer_url();
include_spip('inc/forum');

// http://doc.spip.org/@forum_parent
function forum_parent($id_forum) {
	$row=spip_fetch_array(spip_query("SELECT * FROM spip_forum WHERE id_forum=$id_forum AND statut != 'redac'"));
	if (!$row) return '';
	$id_forum=$row['id_forum'];
	$forum_id_parent=$row['id_parent'];
	$forum_id_rubrique=$row['id_rubrique'];
	$forum_id_article=$row['id_article'];
	$forum_id_breve=$row['id_breve'];
	$forum_id_syndic=$row['id_syndic'];
	$forum_stat=$row['statut'];

	if ($forum_id_article > 0) {
	  $row=spip_fetch_array(spip_query("SELECT id_article, titre, statut FROM spip_articles WHERE id_article='$forum_id_article'"));
	  $id_article = $row['id_article'];
	  $titre = $row['titre'];
	  $statut = $row['statut'];
	  if ($forum_stat == "prive" OR $forum_stat == "privoff") {
	    return array('pref' => _T('item_reponse_article'),
			 'url' => generer_url_ecrire("articles","id_article=$id_article"),
			 'type' => 'id_article',
			 'valeur' => $id_article,
			 'titre' => $titre);
	  } else {
	    return array('pref' =>  _T('lien_reponse_article'),
			 'url' => generer_url_article($id_article),
			 'type' => 'id_article',
			 'valeur' => $id_article,
			 'titre' => $titre,
			 'avant' => "<a href='" . generer_url_ecrire("articles_forum","id_article=$id_article") . "'><font color='red'>"._T('lien_forum_public'). "</font></a><br>");
	  }
	}
	else if ($forum_id_rubrique > 0) {
	  $row = spip_fetch_array(spip_query("SELECT * FROM spip_rubriques WHERE id_rubrique='$forum_id_rubrique'"));
	  $id_rubrique = $row['id_rubrique'];
	  $titre = $row['titre'];
	  return array('pref' => _T('lien_reponse_rubrique'),
		       'url' => generer_url_rubrique($id_rubrique),
		       'type' => 'id_rubrique',
		       'valeur' => $id_rubrique,
		       'titre' => $titre);
	}
	else if ($forum_id_syndic > 0) {
	  $row = spip_fetch_array(spip_query("SELECT * FROM spip_syndic WHERE id_syndic='$forum_id_syndic'"));
	  $id_syndic = $row['id_syndic'];
	  $titre = $row['nom_site'];
	  $statut = $row['statut'];
	  return array('pref' => _T('lien_reponse_site_reference'),
		       'url' => generer_url_ecrire("sites","id_syndic=$id_syndic"),
		       'type' => 'id_syndic',
		       'valeur' => $id_syndic,
		       'titre' => $titre);
	}
	else if ($forum_id_breve > 0) {
	  $row = spip_fetch_array(spip_query("SELECT * FROM spip_breves WHERE id_breve='$forum_id_breve'"));
	  $id_breve = $row['id_breve'];
	  $date_heure = $row['date_heure'];
	  $titre = $row['titre'];
	  if ($forum_stat == "prive") {
	    return array('pref' => _T('lien_reponse_breve'),
			 'url' => generer_url_ecrire("breves_voir","id_breve=$id_breve"),
			 'type' => 'id_breve',
			 'valeur' => $id_breve,
			 'titre' => $titre);
	  } else {
	    return array('pref' => _T('lien_reponse_breve_2'),
			 'url' => generer_url_breve($id_breve),
			 'type' => 'id_breve',
			 'valeur' => $id_breve,
			 'titre' => $titre);
	  }
	}
	else if ($forum_stat == "privadm") {
	  $retour = forum_parent($forum_id_parent);
	  if ($retour) return $retour;
	  else return array('pref' => _T('info_message'),
			    'url' => generer_url_ecrire('forum_admin'),
			    'titre' => _T('info_forum_administrateur'));
	}
	else {
	  $retour = forum_parent($forum_id_parent);
	  if ($retour) return $retour;
	  else return array('pref' => _T('info_message'),
			    'url' => generer_url_ecrire('forum'),
			    'titre' => _T('info_forum_interne'));
	}
}

// $rappel n'est pas utilise. Pourquoi ?

// http://doc.spip.org/@controle_un_forum
function controle_un_forum($row, $rappel) {

	$id_forum = $row['id_forum'];
	$forum_id_parent = $row['id_parent'];
	$forum_id_rubrique = $row['id_rubrique'];
	$forum_id_article = $row['id_article'];
	$forum_id_breve = $row['id_breve'];
	$forum_date_heure = $row['date_heure'];
	$forum_titre = echapper_tags($row['titre']);
	$forum_texte = $row['texte'];
	$forum_auteur = echapper_tags($row['auteur']);
	$forum_email_auteur = echapper_tags($row['email_auteur']);
	$forum_nom_site = echapper_tags($row['nom_site']);
	$forum_url_site = echapper_tags($row['url_site']);
	$forum_stat = $row['statut'];
	$forum_ip = $row['ip'];
	$forum_id_auteur = $row["id_auteur"];

	$r = forum_parent($id_forum);
	$avant = $r['avant'];
	$url = $r['url'];
	$titre = $r['titre'];
	$type = $r['type'];
	$valeur = $r['valeur'];
	$pref = $r['pref'];
	
	$cadre = "";
	
	$controle = "\n<br /><br /><a id='id$id_forum'></a>";
	
	$controle .= debut_cadre_thread_forum("", true, "", typo($forum_titre));

	if ($forum_stat=="off" OR $forum_stat == "privoff") {
		$controle .= "<div style='border: 2px #ff0000 dashed;'>";
	}
	else if ($forum_stat=="prop") {
		$controle .= "<div style='border: 2px yellow solid; background-color: white;'>";
	}
	else {
		$controle .= "<div>";
	}
	
	$controle .= "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n<tr><td width='100%' valign='top'><table width='100%' cellpadding='5' cellspacing='0'>\n<tr><td class='serif'><span class='arial2'>" .
	  date_relative($forum_date_heure) .
	  "</span>";
	if ($forum_auteur) {
		if ($forum_email_auteur)
			$forum_auteur="<a href='mailto:"
			.htmlspecialchars($forum_email_auteur)
			."?subject=".rawurlencode($forum_titre)."'>".$forum_auteur
			."</A>";
		$controle .= safehtml("<span class='arial2'> / <b>$forum_auteur</b></span>");
	}

	$controle .= boutons_controle_forum($id_forum, $forum_stat, $forum_id_auteur, "$type=$valeur", $forum_ip);

	$suite = "\n<br />$avant<b>$pref
	<a href='$url'>$titre</a></b>" . justifier(propre($forum_texte));

	if (strlen($forum_url_site) > 10 AND strlen($forum_nom_site) > 3)
		$suite .= "\n<div align='left' class='serif'><B><A HREF='$forum_url_site'>$forum_nom_site</A></B></div>";

	$controle .= safehtml($suite);

	if ($GLOBALS['meta']["mots_cles_forums"] == "oui") {
	  $result_mots = spip_query("SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot");


		while ($row_mots = spip_fetch_array($result_mots)) {
			$titre_mot = propre($row_mots['titre']);
			$type_mot = propre($row_mots['type']);
			$controle .= "\n<li> <b>$type_mot :</b> $titre_mot";
		}
	}

	$controle .= "</TD></TR></TABLE>";
	$controle .= "</TD></TR></TABLE>\n";

	$controle .= "</div>".fin_cadre_thread_forum(true);
	return $controle;
}

//
// Debut de la page de controle
//

// http://doc.spip.org/@exec_controle_forum_dist
function exec_controle_forum_dist()
{

  global $page, $debut, $debut_id_forum, $id_rubrique, $connect_statut, $connect_toutes_rubriques;

  debut_page(_T('titre_page_forum_suivi'), "forum", "forum-controle");

  if (!preg_match('/^\w+$/', $page)) $page = "public";

  echo "<br><br><br>";
  gros_titre(_T('titre_forum_suivi'));

// faut rajouter id_rubrique donc on n'appelle plus
//  barre_onglets("suivi_forum", $page); 
// on expanse
  
  $id_rubrique = intval($id_rubrique);
  $args =  (!$id_rubrique ? "" : "id_rubrique=$id_rubrique&") . 'page=';

  debut_onglet();
  onglet(_T('onglet_messages_publics'), generer_url_ecrire('controle_forum', $args . "public"), "public", $onglet, "forum-public-24.gif");
  onglet(_T('onglet_messages_internes'), generer_url_ecrire('controle_forum', $args . "interne"), "interne", $onglet, "forum-interne-24.gif");

  $n = spip_fetch_array(spip_query("SELECT id_forum FROM spip_forum WHERE statut='publie' AND texte='' LIMIT 1"));
  if ($n) onglet(_T('onglet_messages_vide'), generer_url_ecrire('controle_forum', $args . "vide"), "vide", $onglet);

    list($from,$where) = critere_statut_controle_forum('prop', $id_rubrique);
    $f = spip_fetch_array(spip_query("SELECT F.id_forum FROM $from " . (!$where ? '' : "WHERE $where ") . " LIMIT 1"));
    if ($f)
      onglet(_T('texte_statut_attente_validation'), generer_url_ecrire('controle_forum', $args . "prop"), "prop", $onglet);

    fin_onglet();

  if (($connect_statut != "0minirezo") OR 
      (!$connect_toutes_rubriques AND
       (!$id_rubrique OR !acces_rubrique($id_rubrique)))) {
	echo "<B>"._T('avis_non_acces_page')."</B>";
	exit;
  }
  list($from,$where) = critere_statut_controle_forum($page, $id_rubrique);

	// Si un id_controle_forum est demande, on adapte le debut
	if ($debut_id_forum = intval($debut_id_forum)
	AND $d = spip_fetch_array(spip_query("SELECT date_heure FROM spip_forum WHERE id_forum=$debut_id_forum"))) {
		$debut = spip_fetch_array(spip_query($q = "SELECT COUNT(*) AS n FROM $from " . (!$where ? '' : "WHERE $where ") . (!$d ? '' : (" AND F.date_heure > '".$d['date_heure']."'"))));
		$debut = $debut['n'];
	}

 $debut= intval($debut);

 $pack = 20;	// nb de forums affiches par page
 $enplus = 200;	// intervalle affiche autour du debut
 $limitdeb = ($debut > $enplus) ? $debut-$enplus : 0;
 $limitnb = $debut + $enplus - $limitdeb;

 $result_forum = spip_query("SELECT F.id_forum, F.id_parent, F.id_rubrique, F.id_article, F.id_breve, F.date_heure, F.titre, F.texte, F.auteur, F.email_auteur, F.nom_site, F.url_site, F.statut, F.ip, F.id_auteur FROM $from " . (!$where ? '' : "WHERE $where ") . "ORDER BY F.date_heure DESC LIMIT $limitdeb, $limitnb");
# LIMIT $limitnb OFFSET $limitdeb" #PG

  debut_gauche();
  debut_boite_info();
  echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2>";
  echo _T('info_gauche_suivi_forum_2');
  echo aide("suiviforum");
  echo "</FONT>";

  // Afficher le lien RSS

  echo "<div style='text-align: "
    . $GLOBALS['spip_lang_right']
    . ";'>"
    . bouton_spip_rss('forums', array('page' => $page))
    ."</div>";

  fin_boite_info();
  debut_droite();
  $args .= $page;

  echo "<div class='serif2'>";
  $i = $limitdeb;
  if ($i>0) echo "<a href='", generer_url_ecrire('controle_forum', $args),"'>0</a> ... | ";
  $controle = '';

  while ($row = spip_fetch_array($result_forum)) {

	// barre de navigation
	if ($i == $pack*floor($i/$pack)) {
		if ($i == $debut)
			echo "<FONT SIZE=3><B>$i</B></FONT>";
		else
		  echo "<a href='", generer_url_ecrire('controle_forum', $args . "&debut=$i"), "'>$i</a>";
		echo " | ";
	}
	// est-ce que ce message doit s'afficher dans la liste ?
	if (($i>=$debut) AND ($i<($debut + $pack)))
	  $controle .= controle_un_forum($row, generer_url_ecrire('controle_forum', $args . "&debut=$debut"));
	$i ++;
 }

  echo "<a href=", generer_url_ecrire('controle_forum', $args . "&debut=$i"),
    ">...</a>$controle</div>";
  fin_page();
}

?>
