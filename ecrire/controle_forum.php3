<?php

include ("inc.php3");


debut_page("Suivi des forums", "messagerie", "forum-controle");

echo "<br><br><br>";
if ($controle_sans == 'oui') {
	$controle_sans = '&controle_sans=oui';
	gros_titre("Messages sans texte");
	barre_onglets("suivi_forum", "sans");
} else {
	$controle_sans = '';
	gros_titre("Suivi des forums");
	$query_forum = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut!='perso' AND statut != 'redac' AND texte='' AND date_heure>DATE_SUB(NOW(),INTERVAL 30 DAY)";
	$result_forum = spip_query($query_forum);
	if ($row = mysql_fetch_array($result_forum)) $total = $row['cnt'];
	if ($total > 0) barre_onglets("suivi_forum", "tous");
}


debut_gauche();


debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo propre("La page de {suivi des forums} est un outil de gestion de votre site (et non un espace de discussion ou de r&eacute;daction). Elle affiche toutes les contributions des forums du site, aussi bien celles du site public que de l'espace priv&eacute; et vous permet de g&eacute;rer ces contributions.");

echo aide ("suiviforum");
echo "</FONT>";

fin_boite_info();

//
// Afficher les boutons de creation d'article et de breve
//
if ($connect_statut == '0minirezo') {
	debut_raccourcis();
	
	icone_horizontale("Forum interne", "forum.php3", "forum-interne-24.gif", "rien.gif");
	icone_horizontale("Forum des administrateurs", "forum_admin.php3", "forum-admin-24.gif", "rien.gif");

	$query_petition = "SELECT COUNT(*) AS cnt FROM spip_signatures WHERE (statut='publie' OR statut='poubelle')";
	$result_petition = spip_query($query_petition);
	if ($row = mysql_fetch_array($result_petition)){
		$nombre_petition = $row['cnt'];
	}
	if ($nombre_petition > 0) {
		echo "<p>";
		icone_horizontale("$nombre_petition signatures de p&eacute;titions", "controle_petition.php3", "petition-24.gif", "rien.gif");
	}
			
	$activer_stats = lire_meta("activer_statistiques");
	if ($activer_stats != "non") icone_horizontale("Evolution des visites", "statistiques_visites.php3", "statistiques-24.gif", "rien.gif");
	
	fin_raccourcis();
}




debut_droite();
	$mots_cles_forums = lire_meta("mots_cles_forums");



function forum_parent($id_forum) {
	$query_forum = "SELECT * FROM spip_forum WHERE id_forum=\"$id_forum\" AND statut != 'redac'";
 	$result_forum = spip_query($query_forum);

 	while($row=mysql_fetch_array($result_forum)){
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

			while($row=mysql_fetch_array($result)) {
				$id_article = $row['id_article'];
				$titre = $row['titre'];
				$statut = $row['statut'];
			}

			if ($forum_stat == "prive") {
				return $retour."<B>R&eacute;ponse &agrave; l'article <A HREF='articles.php3?id_article=$id_article'>$titre</A></B>";
			}
			else {
				$retour .= "<a href='articles_forum.php3?id_article=$id_article'><font color='red'>G&eacute;rer le forum public de cet article</font></a><br>";
				return $retour."<B>R&eacute;ponse &agrave; l'article <A HREF='".generer_url_article($id_article)."'>$titre</A></B>";
			}
		}
		else if ($forum_id_rubrique > 0) {
			$query2 = "SELECT * FROM spip_rubriques WHERE id_rubrique=\"$forum_id_rubrique\"";
			$result2 = spip_query($query2);

			while($row = mysql_fetch_array($result2)){
				$id_rubrique = $row['id_rubrique'];
				$titre = $row['titre'];
			}
			return "<B>R&eacute;ponse &agrave; la rubrique <A HREF='".generer_url_rubrique($id_rubrique)."'>$titre</A></B>";
		}
		else if ($forum_id_syndic > 0) {
			$query2 = "SELECT * FROM spip_syndic WHERE id_syndic=\"$forum_id_syndic\"";
			$result2 = spip_query($query2);

			while($row = mysql_fetch_array($result2)){
				$id_syndic = $row['id_syndic'];
				$titre = $row['nom_site'];
				$statut = $row['statut'];
			}
			return "<B>R&eacute;ponse au site r&eacute;f&eacute;renc&eacute; : <A HREF='sites.php3?id_syndic=$id_syndic'>$titre</A></B>";
		}
		else if ($forum_id_breve > 0) {
			$query2 = "SELECT * FROM spip_breves WHERE id_breve=\"$forum_id_breve\"";
		 	$result2 = spip_query($query2);

		 	while($row = mysql_fetch_array($result2)){
				$id_breve = $row['id_breve'];
				$date_heure = $row['date_heure'];
				$titre = $row['titre'];
			}
			if ($forum_stat == "prive") {
				return "<B>R&eacute;ponse &agrave; la br&egrave;ve <A HREF='breves_voir.php3?id_breve=$id_breve'>$titre</A></B>";
			}
			else {
				return "<B>R&eacute;ponse &agrave; la br&egrave;ve <A HREF='".generer_url_breve($id_breve)."'>$titre</A></B>";
			}
		}
		else if ($forum_stat == "privadm") {
			$retour = forum_parent($forum_id_parent);
			
			if (strlen($retour)>0) return $retour;
			else return "<B>Message du <A HREF='forum_admin.php3'>forum des administrateurs</A></B>";
		}
		else {
			$retour = forum_parent($forum_id_parent);
			
			if (strlen($retour)>0) return $retour;
			else return "<B>Message du <A HREF='forum.php3'>forum interne</A></B>";
		}
	}

}



function controle_forum($request,$adresse_retour) {
	global $compteur_forum;
	static $nb_forum;
	global $debut;
	static $i;
	global $couleur_foncee;
	global $mots_cles_forums;
	global $controle_sans;

	
	$compteur_forum++; 

	$nb_forum[$compteur_forum] = mysql_num_rows($request);
	$i[$compteur_forum] = 1;
 	while($row=mysql_fetch_array($request)){
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
		
		if ($compteur_forum==1)
			echo "<BR><BR>";
		if ($forum_stat=="off" OR $forum_stat == "privoff") {
			echo "<div style='border: 1px #ff0000 solid'>";
		}
		else if($forum_stat=="prop"){
			echo "<div style='border: 1px yellow solid'>";
		}
		else {
			echo "<div style='border-right: 1px solid #cccccc; border-bottom: 1px solid #cccccc;'>";
			echo "<div style='border: 1px #999999 dashed; background-color: white;'>";
		}
		echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0><TR>";
		echo "<TD WIDTH=100% VALIGN='top'>";

		echo "<TABLE WIDTH=100% CELLPADDING=5 CELLSPACING=0><TR><TD BGCOLOR='$couleur_foncee'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#FFFFFF'><B>".typo($forum_titre)."</B></FONT></TD></TR>";
		echo "<TR><TD>";
		echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
		if ($forum_stat=="publie" OR $forum_stat == "off") {
			echo "<img src='img_pack/racine-site-24.gif' border=0 align='left'>";
			echo "<FONT FACE='arial,helvetica' COLOR='#$couleur_foncee'>[sur le site public]</FONT> ";
		}
		else if ($forum_stat == "prive" OR $forum_stat == "privrac" OR $forum_stat == "privadm" OR $forum_stat == "privoff"){
			echo "<img src='img_pack/cadenas-24.gif' border=0 align='left'>";
			echo "<FONT FACE='arial,helvetica' COLOR='#$couleur_foncee'>[dans l'espace priv&eacute;]</FONT> ";
		}
		echo "<FONT FACE='arial,helvetica'>".nom_jour($forum_date_heure)." ".affdate($forum_date_heure).", ".heures($forum_date_heure)."h".minutes($forum_date_heure)."</FONT>";
		if (strlen($forum_auteur) > 2) {
			if (strlen($forum_email_auteur) > 3) {
				$forum_auteur="<A HREF=\"mailto:$forum_email_auteur?SUBJECT=".rawurlencode($forum_titre)."\">$forum_auteur</A>";
			}
			echo "<FONT FACE='arial,helvetica'> / <B>$forum_auteur</B></FONT>";
		}

		if ($forum_stat <> "off" AND $forum_stat <> "prioff") {
			if ($forum_stat == "publie") icone ("Supprimer ce message", "controle_forum.php3?supp_forum=$id_forum&debut=$debut$controle_sans", "forum-interne-24.gif", "supprimer.gif", "right");
			else if ($forum_stat == "prive" OR $forum_stat == "privrac" OR $forum_stat == "privadm") icone ("Supprimer ce message", "controle_forum.php3?supp_forum_priv=$id_forum&debut=$debut$controle_sans", "forum-interne-24.gif", "supprimer.gif", "right");
		}
		else {
			echo "<BR><FONT COLOR='red'><B>MESSAGE SUPPRIM&Eacute; $forum_ip</B></FONT>";
			if($forum_id_auteur>0){
				echo " - <A HREF='auteurs_edit.php3?id_auteur=$forum_id_auteur'>Voir cet auteur</A>";
			
			}

		}

		if ($forum_stat=="prop"){
			icone("Valider ce message", "controle_forum.php3?valid_forum=$id_forum&debut=$debut", "forum-interne-24.gif", "creer.gif", "right");
		}

		echo "<BR>".forum_parent($id_forum);

		echo "<P align='justify'>".propre($forum_texte);
		
		if (strlen($forum_url_site) > 10 AND strlen($forum_nom_site) > 3) {
			echo "<P align='left'><FONT FACE='Verdana,Arial,Helvetica,sans-serif'><B><A HREF='$forum_url_site'>$forum_nom_site</A></B></FONT>";
		}

		if ($mots_cles_forums == "oui"){
			
			$query_mots = "SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot";
			$result_mots = spip_query($query_mots);
			
			while ($row_mots = mysql_fetch_array($result_mots)) {
				$id_mot = $row_mots['id_mot'];
				$titre_mot = propre($row_mots['titre']);
				$type_mot = propre($row_mots['type']);
				echo "<li> <b>$type_mot :</b> $titre_mot";
			}
			
		}

		echo "</FONT>";
		echo "</TD></TR></TABLE>";
		
		echo "</TD></TR></TABLE>\n";
		
		if (!($forum_stat == 'off' OR $forum_stat == 'privoff' OR $forum_stat=='prop')) {
			echo "</div>";
		}
		
		echo "</div>";
	}
}

  
echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
 
if ($connect_statut == "0minirezo") {

//	gros_titre("Suivi des forums");

	if (!$debut) $debut = 0;

	if ($controle_sans)
		$query_forum = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut!='perso' AND statut != 'redac' AND texte='' AND date_heure>DATE_SUB(NOW(),INTERVAL 30 DAY)";
	else
		$query_forum = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut!='perso' AND statut != 'redac' AND texte!='' AND date_heure>DATE_SUB(NOW(),INTERVAL 30 DAY)";

	$result_forum = spip_query($query_forum);
	$total = 0;
	if ($row = mysql_fetch_array($result_forum)) $total = $row['cnt'];

	if ($total > 10) {
		echo "<p>";
		for ($i = 0; $i < $total; $i = $i + 10){
			if ($i > 0) echo " | ";
			if ($i == $debut)
				echo "<FONT SIZE=3><B>$i</B></FONT>";
			else
				echo "<A HREF='controle_forum.php3?debut=$i$controle_sans'>$i</A>";
		}
	}

	if ($controle_sans)
		$query_forum = "SELECT * FROM spip_forum WHERE statut!='perso' AND statut != 'redac' AND texte='' ORDER BY date_heure DESC LIMIT $debut,10";
	else
		$query_forum = "SELECT * FROM spip_forum WHERE statut!='perso' AND statut != 'redac' AND texte!='' ORDER BY date_heure DESC LIMIT $debut,10";

	$result_forum = spip_query($query_forum);
	controle_forum($result_forum, "forum.php3");

//	afficher_forum($result_forum, $forum_retour,'oui','non');

}
else {
	echo "<B>Vous n'avez pas acc&egrave;s &agrave; cette page.</B>";
}	
		

echo "</FONT>";


fin_page();


?>

