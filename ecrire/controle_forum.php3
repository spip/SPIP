<?php

include ("inc.php3");


debut_page(_T('titre_page_forum_suivi'), "messagerie", "forum-controle");

$requete_base_controle = "statut!='perso' AND statut != 'redac'";

if (!$page) $page = "public";

echo "<br><br><br>";
gros_titre(_T('titre_forum_suivi'));

barre_onglets("suivi_forum", $page);


debut_gauche();

debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo _T('info_gauche_suivi_forum_2');

echo aide ("suiviforum");
echo "</FONT>";

fin_boite_info();

//
// Raccourcis
//
$activer_stats = lire_meta("activer_statistiques");
if (($activer_stats != "non") AND ($connect_statut == '0minirezo')) {
	debut_raccourcis();
	icone_horizontale(_T('icone_evolution_visites_2'), "statistiques_visites.php3", "statistiques-24.gif", "rien.gif");
	fin_raccourcis();
}


debut_droite();
	$mots_cles_forums = lire_meta("mots_cles_forums");



function forum_parent($id_forum) {
	$query_forum = "SELECT * FROM spip_forum WHERE id_forum=$id_forum AND statut != 'redac'";
 	$result_forum = spip_query($query_forum);

 	while($row=spip_fetch_array($result_forum)){
		$id_forum=$row['id_forum'];
		$forum_id_parent=$row['id_parent'];
		$forum_id_rubrique=$row['id_rubrique'];
		$forum_id_article=$row['id_article'];
		$forum_id_breve=$row['id_breve'];
		$forum_id_syndic=$row['id_syndic'];
		$forum_date_heure=$row['date_heure'];
		$forum_titre=$row['titre'];
		$forum_texte=$row['texte'];
		$forum_auteur=$row['auteur'];
		$forum_email_auteur=$row['email_auteur'];
		$forum_nom_site=$row['nom_site'];
		$forum_url_site=$row['url_site'];
		$forum_stat=$row['statut'];
		$forum_ip=$row['ip'];

		if ($forum_id_article > 0) {
	
			$query = "SELECT id_article, titre, statut FROM spip_articles WHERE id_article='$forum_id_article'";
		 	$result = spip_query($query);

			while($row=spip_fetch_array($result)) {
				$id_article = $row['id_article'];
				$titre = $row['titre'];
				$statut = $row['statut'];
			}

			if ($forum_stat == "prive" OR $forum_stat == "privoff") {
				return $retour."<B>"._T('item_reponse_article')."<A HREF='articles.php3?id_article=$id_article'>$titre</A></B>";
			}
			else {
				$retour .= "<a href='articles_forum.php3?id_article=$id_article'><font color='red'>"._T('lien_forum_public')."</font></a><br>";
				return $retour."<B>"._T('lien_reponse_article')." <A HREF='".generer_url_article($id_article)."'>$titre</A></B>";
			}
		}
		else if ($forum_id_rubrique > 0) {
			$query2 = "SELECT * FROM spip_rubriques WHERE id_rubrique=\"$forum_id_rubrique\"";
			$result2 = spip_query($query2);

			while($row = spip_fetch_array($result2)){
				$id_rubrique = $row['id_rubrique'];
				$titre = $row['titre'];
			}
			return "<B>"._T('lien_reponse_rubrique')." <A HREF='".generer_url_rubrique($id_rubrique)."'>$titre</A></B>";
		}
		else if ($forum_id_syndic > 0) {
			$query2 = "SELECT * FROM spip_syndic WHERE id_syndic=\"$forum_id_syndic\"";
			$result2 = spip_query($query2);

			while($row = spip_fetch_array($result2)){
				$id_syndic = $row['id_syndic'];
				$titre = $row['nom_site'];
				$statut = $row['statut'];
			}
			return "<B>"._T('lien_reponse_site_reference')." <A HREF='sites.php3?id_syndic=$id_syndic'>$titre</A></B>";
		}
		else if ($forum_id_breve > 0) {
			$query2 = "SELECT * FROM spip_breves WHERE id_breve=\"$forum_id_breve\"";
		 	$result2 = spip_query($query2);

		 	while($row = spip_fetch_array($result2)){
				$id_breve = $row['id_breve'];
				$date_heure = $row['date_heure'];
				$titre = $row['titre'];
			}
			if ($forum_stat == "prive") {
				return "<B>"._T('lien_reponse_breve')." <A HREF='breves_voir.php3?id_breve=$id_breve'>$titre</A></B>";
			}
			else {
				return "<B>"._T('lien_reponse_breve_2')." <A HREF='".generer_url_breve($id_breve)."'>$titre</A></B>";
			}
		}
		else if ($forum_stat == "privadm") {
			$retour = forum_parent($forum_id_parent);
			
			if (strlen($retour)>0) return $retour;
			else return "<B>"._T('info_message')."<A HREF='forum_admin.php3'>"._T('info_forum_administrateur')."</A></B>";
		}
		else {
			$retour = forum_parent($forum_id_parent);

			if (strlen($retour)>0) return $retour;
			else return "<B>"._T('info_message')."<A HREF='forum.php3'>"._T('info_forum_interne')."</A></B>";
		}
	}

}


function controle_forum($row) {
	global $couleur_foncee;
	global $mots_cles_forums;
	global $controle_sans;
	global $debut, $page;

	$controle = "<BR><BR>";

	$id_forum = $row['id_forum'];
	$forum_id_parent = $row['id_parent'];
	$forum_id_rubrique = $row['id_rubrique'];
	$forum_id_article = $row['id_article'];
	$forum_id_breve = $row['id_breve'];
	$forum_date_heure = $row['date_heure'];
	$forum_titre = echapper_tags($row['titre']);
	$forum_texte = echapper_tags($row['texte']);
	$forum_auteur = echapper_tags($row['auteur']);
	$forum_email_auteur = echapper_tags($row['email_auteur']);
	$forum_nom_site = echapper_tags($row['nom_site']);
	$forum_url_site = echapper_tags($row['url_site']);
	$forum_stat = $row['statut'];
	$forum_ip = $row['ip'];
	$forum_id_auteur = $row["id_auteur"];

	if ($forum_stat=="off" OR $forum_stat == "privoff")
		$controle .= "<div style='border: 2px #ff0000 dashed; background-color: white;'>";
	else if ($forum_stat=="prop")
		$controle .= "<div style='border: 2px yellow solid; background-color: white;'>";
	else {
		$controle .= "<div style='border-right: 1px solid #cccccc; border-bottom: 1px solid #cccccc;'>";
		$controle .= "<div style='border: 1px #999999 dashed; background-color: white;'>";
	}

	$controle .= "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0><TR>";
	$controle .= "<TD WIDTH=100% VALIGN='top'>";

	$controle .= "<TABLE WIDTH=100% CELLPADDING=5 CELLSPACING=0><TR><TD BGCOLOR='$couleur_foncee'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#FFFFFF'><B>".typo($forum_titre)."</B></FONT></TD></TR>";
	$controle .= "<TR><TD>";
	$controle .= "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";

	$controle .= "<FONT FACE='arial,helvetica'>".affdate_heure($forum_date_heure)."</FONT>";
	if ($forum_auteur) {
		if ($forum_email_auteur)
			$forum_auteur="<A HREF=\"mailto:$forum_email_auteur?SUBJECT=".rawurlencode($forum_titre)."\">$forum_auteur</A>";
		$controle .= "<FONT FACE='arial,helvetica'> / <B>$forum_auteur</B></FONT>";
	}

	if ($forum_stat != "off" AND $forum_stat != "privoff") {
		if ($forum_stat == "publie" OR $forum_stat == "prop")
			$controle .= icone(_T('icone_supprimer_message'), "controle_forum.php3?supp_forum=$id_forum&debut=$debut$controle_sans&page=$page", "forum-interne-24.gif", "supprimer.gif", "right", 'non');
		else if ($forum_stat == "prive" OR $forum_stat == "privrac" OR $forum_stat == "privadm")
			$controle .= icone(_T('icone_supprimer_message'), "controle_forum.php3?supp_forum_priv=$id_forum&debut=$debut$controle_sans&page=$page", "forum-interne-24.gif", "supprimer.gif", "right", 'non');
	}
	else {
		$controle .= "<BR><FONT COLOR='red'><B>"._T('info_message_supprime')." $forum_ip</B></FONT>";
		if($forum_id_auteur>0)
			$controle .= " - <A HREF='auteurs_edit.php3?id_auteur=$forum_id_auteur'>"._T('lien_voir_auteur')."</A>";
	}

	if ($forum_stat=="prop")
		$controle .= icone(_T('icone_valider_message'), "controle_forum.php3?valid_forum=$id_forum&debut=$debut&page=$page", "forum-interne-24.gif", "creer.gif", "right", 'non');

	$controle .= "<BR>".forum_parent($id_forum);

	$controle .= "<P align='justify'>".propre($forum_texte);

	if (strlen($forum_url_site) > 10 AND strlen($forum_nom_site) > 3)
		$controle .= "<P align='left'><FONT FACE='Verdana,Arial,Helvetica,sans-serif'><B><A HREF='$forum_url_site'>$forum_nom_site</A></B></FONT>";

	if ($mots_cles_forums == "oui") {
		$query_mots = "SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot";
		$result_mots = spip_query($query_mots);

		while ($row_mots = spip_fetch_array($result_mots)) {
			$id_mot = $row_mots['id_mot'];
			$titre_mot = propre($row_mots['titre']);
			$type_mot = propre($row_mots['type']);
			$controle .= "<li> <b>$type_mot :</b> $titre_mot";
		}
	}

	$controle .= "</FONT>";
	$controle .= "</TD></TR></TABLE>";

	$controle .= "</TD></TR></TABLE>\n";

	if (!($forum_stat == 'off' OR $forum_stat == 'privoff' OR $forum_stat=='prop'))
		$controle .= "</div>";

	$controle .= "</div>";

	return $controle;
}


//
// Debut de la page de controle
//

echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";

if ($connect_statut != "0minirezo" OR !$connect_toutes_rubriques) {
	echo "<B>"._T('avis_non_acces_page')."</B>";
	exit;
}


// reglages
if (!$debut) $debut = 0;
$pack = 20;		// nb de forums affiches par page
$enplus = 200;	// intervalle affiche autour du debut
$limitdeb = ($debut > $enplus) ? $debut-$enplus : 0;
$limitnb = $debut + $enplus - $limitdeb;
$wheretexte = $controle_sans ? "texte=''" : "texte!=''";


$query_forum = "SELECT * FROM spip_forum WHERE ";
switch ($page) {
case 'public':
	$query_forum .= "statut IN ('publie', 'off', 'prop') AND texte!=''";
	break;
case 'interne':
	$query_forum .= "statut IN ('prive', 'privrac', 'privoff', 'privadm') AND texte!=''";
	break;
case 'vide':
	$query_forum .= "statut IN ('publie', 'off', 'prive', 'privrac', 'privoff', 'privadm') AND texte=''";
	break;
default:
	$query_forum .= "0=1";
	break;
}

$query_forum .= " ORDER BY date_heure DESC LIMIT $limitdeb, $limitnb";
$result_forum = spip_query($query_forum);

$controle = '';

$i = $limitdeb;
if ($i>0)
	echo "<A HREF='controle_forum.php3?page=$page'>0</A> ... | ";

while ($row = spip_fetch_array($result_forum)) {
	// est-ce que ce message doit s'afficher dans la liste ?
	$ok_controle = (($i>=$debut) AND ($i<$debut + $pack));

	// barre de navigation
	if ($i == $pack*floor($i/$pack)) {
		if ($i == $debut)
			echo "<FONT SIZE=3><B>$i</B></FONT>";
		else
			echo "<A HREF='controle_forum.php3?debut=$i&page=$page'>$i</A>";
		echo " | ";
	}

	// elements a controler
	if ($ok_controle)
		$controle .= controle_forum($row);

	$i ++;
}

echo "<A HREF='controle_forum.php3?debut=$i&page=$page'>...</A>";

echo $controle;

echo "</FONT>";

fin_page();

?>

