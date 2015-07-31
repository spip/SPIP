<?php

include ("inc.php3");


debut_page("Suivi des forums");
debut_gauche();

$query_petition = "SELECT COUNT(*) FROM spip_signatures WHERE date_time>DATE_SUB(NOW(),INTERVAL 30 DAY) AND (statut='publie' OR statut='poubelle')";
$result_petition = mysql_query($query_petition);

if ($row = mysql_fetch_array($result_petition)){
	$nombre_petition = $row[0];
}
	
if ($nombre_petition > 0) {
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
	echo "<A HREF='controle_petition.php3'><IMG SRC='puce.gif' BORDER=0> $nombre_petition signatures de p&eacute;titions</A>";
	echo "</FONT><P>";
}

debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo "<P align=left>".propre("La page de {suivi des forums} est un outil de gestion de votre site (et non un espace de discussion ou de r&eacute;daction). Elle affiche toutes les contributions des forums du site, aussi bien celles du site public que de l'espace priv&eacute; et vous permet de g&eacute;rer ces contributions.");

echo aide ("suiviforum");
echo "</FONT>";

fin_boite_info();




debut_droite();
	$mots_cles_forums = lire_meta("mots_cles_forums");



function forum_parent($id_forum) {

	$query_forum = "SELECT * FROM spip_forum WHERE id_forum=\"$id_forum\" AND statut != 'redac'";
 	$result_forum = mysql_query($query_forum);

 	while($row=mysql_fetch_array($result_forum)){
		$id_forum=$row[0];
		$forum_id_parent=$row[1];
		$forum_id_rubrique=$row[2];
		$forum_id_article=$row[3];
		$forum_id_breve=$row[4];
		$forum_id_syndic=$row['id_syndic'];
		$forum_date_heure=$row[5];
		$forum_titre=$row[6];
		$forum_texte=$row[7];
		$forum_auteur=$row[8];
		$forum_email_auteur=$row[9];
		$forum_nom_site=$row[10];
		$forum_url_site=$row[11];
		$forum_stat=$row[12];
		$forum_ip=$row[13];

		if ($forum_id_article > 0) {
	
			$query = "SELECT id_article, titre, statut FROM spip_articles WHERE id_article='$forum_id_article'";
		 	$result = mysql_query($query);

			while($row=mysql_fetch_array($result)) {
				$id_article = $row[0];
				$titre = $row[1];
				$statut = $row[2];
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
			$result2 = mysql_query($query2);

			while($row = mysql_fetch_array($result2)){
				$id_rubrique = $row[0];
				$titre = $row[2];
			}
			return "<B>R&eacute;ponse &agrave; la rubrique <A HREF='".generer_url_rubrique($id_rubrique)."'>$titre</A></B>";
		}
		else if ($forum_id_syndic > 0) {
			$query2 = "SELECT * FROM spip_syndic WHERE id_syndic=\"$forum_id_syndic\"";
			$result2 = mysql_query($query2);

			while($row = mysql_fetch_array($result2)){
				$id_syndic = $row[0];
				$titre = $row['nom_site'];
				$statut = $row['statut'];
			}
			return "<B>R&eacute;ponse au site r&eacute;f&eacute;renc&eacute; : <A HREF='sites.php3?id_syndic=$id_syndic'>$titre</A></B>";
		}
		else if ($forum_id_breve > 0) {
			$query2 = "SELECT * FROM spip_breves WHERE id_breve=\"$forum_id_breve\"";
		 	$result2 = mysql_query($query2);

		 	while($row = mysql_fetch_array($result2)){
				$id_breve = $row[0];
				$date_heure = $row[1];
				$titre = $row[2];
			}
			return "<B>R&eacute;ponse &agrave; la br&egrave;ve <A HREF='".generer_url_breve($id_breve)."'>$titre</A></B>";
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

	
	$compteur_forum++; 

	$nb_forum[$compteur_forum] = mysql_num_rows($request);
	$i[$compteur_forum] = 1;
 	while($row=mysql_fetch_array($request)){
		$id_forum = $row[0];
		$forum_id_parent = $row[1];
		$forum_id_rubrique = $row[2];
		$forum_id_article = $row[3];
		$forum_id_breve = $row[4];
		$forum_date_heure = $row[5];
		$forum_titre = $row[6];
		$forum_texte = $row[7];
		$forum_auteur = $row[8];
		$forum_email_auteur = $row[9];
		$forum_nom_site = $row[10];
		$forum_url_site = $row[11];
		$forum_stat = $row[12];
		$forum_ip = $row[13];
		$forum_id_auteur = $row["id_auteur"];
		
		if ($compteur_forum==1)
			echo "<BR><BR>";
		if ($forum_stat=="off") {
			echo "<TABLE WIDTH=100% CELLPADDING=2 CELLSPACING=0 BORDER=0><TR><TD BGCOLOR='#FF0000'>";
		}else if($forum_stat=="prop"){
			echo "<TABLE WIDTH=100% CELLPADDING=2 CELLSPACING=0 BORDER=0><TR><TD BGCOLOR='#FFFF00'>";
		}
		echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0><TR>";
		
		for ($count = 2; $count <= $compteur_forum AND $count < 11; $count++) {

			$fond[$count] = 'IMG2/rien.gif';
			if ($i[$count] != $nb_forum[$count]) {
				$fond[$count] = 'IMG2/forum-vert.gif';
			}		
		
			$fleche='IMG2/rien.gif';
			if ($count == $compteur_forum) {
				$fleche='IMG2/forum-droite.gif';
			}		
			echo "<TD WIDTH=10 VALIGN='top' BACKGROUND=$fond[$count]><IMG SRC=$fleche ALT='' WIDTH=10 HEIGHT=13 BORDER=0></TD>\n";
		}
		
		echo "<TD WIDTH=100% BGCOLOR='#EEEEEE' VALIGN='top'>";

		echo "<TABLE WIDTH=100% CELLPADDING=3 CELLSPACING=0><TR><TD BGCOLOR='$couleur_foncee'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#FFFFFF'><B>".typo($forum_titre)."</B></FONT></TD></TR>";
		echo "<TR><TD>";
		echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
		if ($forum_stat=="publie") echo "<FONT FACE='arial,helvetica' COLOR='red'>[sur le site public]</FONT> ";
		echo "<FONT FACE='arial,helvetica'>$forum_date_heure</FONT>";
		if (strlen($forum_auteur) > 2) {
			if (strlen($forum_email_auteur) > 3) {
				$forum_auteur="<A HREF=\"mailto:$forum_email_auteur?SUBJECT=".rawurlencode($forum_titre)."\">$forum_auteur</A>";
			}
			echo "<FONT FACE='arial,helvetica'> / <B>$forum_auteur</B></FONT>";
		}

		if ($forum_stat <> "off") {
			echo "<A HREF='controle_forum.php3?supp_forum=$id_forum&debut=$debut' onMouseOver=\"message$id_forum.src='IMG2/supprimer-message-on.gif'\" onMouseOut=\"message$id_forum.src='IMG2/supprimer-message-off.gif'\"><IMG SRC='IMG2/supprimer-message-off.gif' WIDTH=64 HEIGHT=52 NAME='message$id_forum' ALIGN='right' BORDER=0></A>";
		}
		else {
			echo "<BR><FONT COLOR='red'><B>MESSAGE SUPPRIM&Eacute; $forum_ip</B></FONT>";
			if($forum_id_auteur>0){
				echo " - <A HREF='auteurs_edit.php3?id_auteur=$forum_id_auteur'>Voir cet auteur</A>";
			
			}

		}

		if ($forum_stat=="prop"){
			echo "<A HREF='controle_forum.php3?valid_forum=$id_forum&debut=$debut' onMouseOver=\"valider_message$id_forum.src='IMG2/valider-message-on.gif'\" onMouseOut=\"valider_message$id_forum.src='IMG2/valider-message-off.gif'\"><IMG SRC='IMG2/valider-message-off.gif' WIDTH=60 HEIGHT=52 NAME='valider_message$id_forum' ALIGN='right' BORDER=0></A>";
		}



		echo "<BR>".forum_parent($id_forum);

		echo "<P align='justify'>".propre($forum_texte);
		
		if (strlen($forum_url_site) > 10 AND strlen($forum_nom_site) > 3) {
			echo "<P align='left'><FONT FACE='Verdana,Arial,Helvetica,sans-serif'><B><A HREF='$forum_url_site'>$forum_nom_site</A></B></FONT>";
		}
		
		if ($mots_cles_forums == "oui"){
			
			$query_mots = "SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot";
			$result_mots = mysql_query($query_mots);
			
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

		if ($forum_stat == 'off' OR $forum_stat=='prop') {
			echo "</TD></TR></TABLE>";
		}
	}
}

  
echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'>";
 
if ($connect_statut == "0minirezo") {

	if (!$debut) $debut = 0;

	$query_forum = "SELECT COUNT(*) FROM spip_forum WHERE statut!='perso' AND statut != 'redac' AND date_heure>DATE_SUB(NOW(),INTERVAL 30 DAY)";
 	$result_forum = mysql_query($query_forum);
 	$total = 0;
 	if ($row = mysql_fetch_array($result_forum)) $total = $row[0];

	if ($total > 10) {
		echo "<CENTER>";
		for ($i = 0; $i < $total; $i = $i + 10){
			$y = $i + 9;
			if ($i == $debut)
				echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
			else
				echo "[<A HREF='controle_forum.php3?debut=$i'>$i-$y</A>] ";
		}
		echo "</CENTER>";
	}

	$query_forum = "SELECT * FROM spip_forum WHERE statut!='perso' AND statut != 'redac' ORDER BY date_heure DESC LIMIT $debut,10";
 	$result_forum = mysql_query($query_forum);
	controle_forum($result_forum, "forum.php3");

//	afficher_forum($result_forum, $forum_retour,'oui','non');

}
else {
	echo "<B>Vous n'avez pas acc&egrave;s &agrave; cette page.</B>";
}	
		

echo "</FONT>";


fin_page();


?>

