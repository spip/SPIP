<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_PRESENTATION")) return;
define("_ECRIRE_INC_PRESENTATION", "1");

//
// initialisations globales de presentation (beurk!)
//

global $spip_couleur, $couleur_foncee, $couleur_claire;


switch ($spip_couleur){

	case 1:
		/// Vert
		$couleur_foncee="#02531B";
		$couleur_claire="#CFFEDE";
		break;
	case 2:
		/// Rouge
		$couleur_foncee="#640707";
		$couleur_claire="#FFE0E0";
		break;
	case 3:
		/// Jaune
		$couleur_foncee="#666500";
		$couleur_claire="#FFFFE0";
		break;
	case 4:
		/// Violet
		$couleur_foncee="#340049";
		$couleur_claire="#F9EBFF";
		break;
	case 5:
		/// Gris
		$couleur_foncee="#3F3F3F";
		$couleur_claire="#F2F2F2";
		break;
	case 6:
		/// Bleu
		$couleur_foncee="#044476";
		$couleur_claire="#EDF3FE";
		break;
	default:
		/// Bleu
		$couleur_foncee="#044476";
		$couleur_claire="#EDF3FE";
}

//
// Aide
//
function aide ($aide) {
	return " <font size='1'>[<b><a href=\"javascript:window.open('aide_index.php3?aide=$aide', 'aide_spip', 'scrollbars=yes,resizable=yes,width=700'); void(0);\">AIDE</a></b>]</font>";
}



//
// affiche un bouton imessage
//
function bouton_imessage($destinataire, $row = '') {
	// si on passe "force" au lieu de $row, on affiche l'icone sans verification
	global $connect_id_auteur;

	$url = "message_edit.php3?";

	// verifier que ce n'est pas un auto-message
	if ($destinataire == $connect_id_auteur)
		return;
	// verifier que le destinataire a un login

	if ($row != "force") {
		$login_req = "select login, messagerie from spip_auteurs where id_auteur=$destinataire AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 DAY)";
		$row = mysql_fetch_array(mysql_query($login_req));
		
		if (($row['login'] == "") OR ($row['messagerie'] == "non")) {
			return;
		}
	}
	$url .= "dest=$destinataire&";
	$url .= "new=oui&type=normal";
	
	$texte_bouton = "<IMG SRC='IMG2/m_envoi.gif' WIDTH='14' HEIGHT='7' BORDER='0'>";
	return "<a href='$url'>$texte_bouton</a>";
}

//
// un cadre en relief
//
function debut_cadre_relief(){
	echo "<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=0 WIDTH=\"100%\">";
	echo "<TR><TD WIDTH=\"100%\">";
	echo "<TABLE CELLPADDING=1 CELLSPACING=0 BORDER=0 WIDTH=\"100%\"><TR><TD BGCOLOR='#000000' WIDTH=\"100%\">";
	echo "<TABLE CELLPADDING=8 CELLSPACING=0 BORDER=0 WIDTH=\"100%\"><TR><TD BACKGROUND='IMG2/rayures.gif' BGCOLOR='#FFFFFF' WIDTH=\"100%\">";
}

function fin_cadre_relief(){
	echo "</TD></TR></TABLE>";
	echo "</TD></TR></TABLE>";
	echo "</TD>";
	echo "<TD VALIGN='top' BACKGROUND='IMG2/ombre-d.gif' WIDTH=5><img src='IMG2/ombre-hd.gif' width='5' height='9' border=0><TD></TR>";
	echo "<TR><TD BACKGROUND='IMG2/ombre-b.gif' ALIGN='left'><img src='IMG2/ombre-bg.gif' width='8' height='5' border='0'></TD><TD><img src='IMG2/ombre-bd.gif' width='5' height='5' border='0'></TD></TR></TABLE>";
}


//
// une boite alerte
//
function debut_boite_alerte() {
	echo "<p><table cellpadding='6' border='0'><tr><td width='100%' bgcolor='red'>";
	echo "<table width='100%' cellpadding='12' border='0'><tr><td width='100%' bgcolor='white'>";
}

function fin_boite_alerte() {
	echo "</td></tr></table>";
	echo "</td></tr></table>";
}


//
// une boite info
//
function debut_boite_info() {
	echo "<p><table cellpadding='5' cellspacing='0' border='1' width='100%' class='profondeur' background=''>";
	echo "<tr><td bgcolor='#DBE1C5' width='100%'>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#333333'>";
}

function fin_boite_info() {
	echo "</font></td></tr></table>";
}

//
// une autre boite
//
function bandeau_titre_boite($titre, $afficher_auteurs, $boite_importante = true) {
	global $couleur_foncee;
	if ($boite_importante) {
		$couleur_fond = $couleur_foncee;
		$couleur_texte = '#FFFFFF';
	}
	else {
		$couleur_fond = '#EEEECC';
		$couleur_texte = '#000000';
	}
	echo "<TR BGCOLOR='$couleur_fond'><TD WIDTH=\"100%\"><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='$couleur_texte'>";
	echo "<B>$titre</B></FONT></TD>";
	if ($afficher_auteurs){
		echo "<TD WIDTH='100'>";
		echo "<img src='IMG2/rien.gif' width='100' height='12' border='0'>";
		echo "</TD>";
	}
	echo "<TD WIDTH='90'>";
	echo "<img src='IMG2/rien.gif' width='90' height='12' border='0'>";
	echo "</TD>";
	echo "</TR>";
}


//
// Fonctions d'affichage
//

function tableau($texte,$lien,$image){
	echo "<td width=15>&nbsp;</td>\n";
	echo "<td width=80 valign='top' align='center'><a href='$lien'><img src='$image' border='0'></a><br><font size=1 face='arial,helvetica' color='#e86519'><b>$texte</b></font></td>";
}

function afficher_liste($largeurs, $table, $styles = '') {
	global $couleur_claire;

	if (!is_array($table)) return;
	reset($table);
	echo "\n";
	while (list(, $t) = each($table)) {
		$couleur_fond = ($ifond ^= 1) ? '#FFFFFF' : $couleur_claire;
		echo "<tr bgcolor=\"$couleur_fond\">";
		reset($largeurs);
		if ($styles) reset($styles);
		while (list(, $texte) = each($t)) {
			$style = $largeur = "";
			list(, $largeur) = each($largeurs);
			if ($styles) list(, $style) = each($styles);
			if (!trim($texte)) $texte .= "&nbsp;";
			echo "<td";
			if ($largeur) echo " width=\"$largeur\"";
			if ($style) echo " class=\"$style\"";
			echo ">$texte</td>";
		}
		echo "</tr>\n";
	}
	echo "\n";
}

function afficher_tranches_requete(&$query, $colspan) {
	$query = trim($query);
	$query_count = eregi_replace('^(SELECT)[[:space:]].*[[:space:]](FROM)[[:space:]]', '\\1 COUNT(*) \\2 ', $query);
	list($num_rows) = mysql_fetch_row(mysql_query($query_count));
	if (!$num_rows) return;

	$nb_aff = 10;
	// Ne pas couper pour trop peu
	if ($num_rows <= 1.5 * $nb_aff) $nb_aff = $num_rows;
	if (ereg('LIMIT .*,([0-9]+)', $query, $regs)) {
		if ($num_rows > $regs[1]) $num_rows = $regs[1];
	}

	$texte = "\n";

	if ($num_rows > $nb_aff) {
		$tmp_var = $query;
		$deb_aff = intval(getTmpVar($tmp_var));

		$texte .= "<tr><td background=\"\" class=\"arial2\" colspan=\"".($colspan - 1)."\">";

		for ($i = 0; $i < $num_rows; $i += $nb_aff){
			$deb = $i + 1;
			$fin = $i + $nb_aff;
			if ($fin > $num_rows) $fin = $num_rows;
			if ($deb_aff + 1 >= $deb AND $deb_aff + 1 <= $fin) {
				$texte .= "[<B>$deb-$fin</B>] ";
			}
			else {
				$link = new Link;
				$link->addTmpVar($tmp_var, strval($deb - 1));
				$texte .= "[<A HREF=\"".$link->getUrl()."\">$deb-$fin</A>] ";
			}
		}
		$texte .= "</td>\n";
		$texte .= "<td background=\"\" class=\"arial2\" colspan=\"1\" align=\"right\" valign=\"top\">";
		if ($deb_aff == -1) {
			$texte .= "[<B>Tout afficher</B>]";
		} else {
			$link = new Link;
			$link->addTmpVar($tmp_var, -1);
			$texte .= "[<A HREF=\"".$link->getUrl()."\">Tout afficher</A>]";
		}		
	
		$texte .= "</td>\n";
		$texte .= "</tr>\n";
		
		
		if ($deb_aff != -1) {
			$query = eregi_replace('LIMIT[[:space:]].*$', '', $query);
			$query .= " LIMIT $deb_aff, $nb_aff";
		}
	}

	return $texte;
}


//
// Afficher tableau d'articles
//
function afficher_articles($titre_table, $requete, $afficher_visites = false, $afficher_auteurs = true, $toujours_afficher = false) {
	global $connect_id_auteur;

	$activer_messagerie = lire_meta("activer_messagerie");
	$activer_statistiques = lire_meta("activer_statistiques");

	$tranches = afficher_tranches_requete($requete, $afficher_auteurs ? 3 : 2);

	if (strlen($tranches) OR $toujours_afficher) {
	 	$result = mysql_query($requete);
	 	$num_rows = mysql_num_rows($result);

		echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0><tr><td width=100% background=''>";
		echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";

		bandeau_titre_boite($titre_table, $afficher_auteurs);

		echo $tranches;

		$compteur_liste = 0;
		$table = '';

		while ($row = mysql_fetch_array($result)) {
			$vals = '';

			$id_article = $row['id_article'];
			$titre = $row['titre'];
			$id_rubrique = $row['id_rubrique'];
			$date = $row['date'];
			$statut = $row['statut'];
			$visites = $row['visites'];

			$query_petition = "SELECT COUNT(*) FROM spip_petitions WHERE id_article=$id_article";
			$row_petition = mysql_fetch_array(mysql_query($query_petition));
			$petition = ($row_petition[0] > 0);

			if ($afficher_auteurs) {
				$les_auteurs = "";
			 	$query2 = "SELECT spip_auteurs.id_auteur, nom, messagerie, login, en_ligne FROM spip_auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$id_article AND spip_auteurs.id_auteur=lien.id_auteur";
				$result_auteurs = mysql_query($query2);

				while ($row = mysql_fetch_array($result_auteurs)) {
					$id_auteur = $row['id_auteur'];
					$nom_auteur = typo($row['nom']);
					$auteur_messagerie = $row['messagerie'];
					
					$les_auteurs .= ", $nom_auteur";
					if ($id_auteur != $connect_id_auteur AND $auteur_messagerie != "non" AND $activer_messagerie != "non") {
						$les_auteurs .= "&nbsp;".bouton_imessage($id_auteur, $row);
					}
				}
				$les_auteurs = substr($les_auteurs, 2);
			}

			$s = "<A HREF=\"articles.php3?id_article=$id_article\">";
			if ($statut=='publie') $puce = 'verte';
			else if ($statut == 'prepa') $puce = 'blanche';
			else if ($statut == 'prop') $puce = 'orange';
			else if ($statut == 'refuse') $puce = 'rouge';
			else if ($statut == 'poubelle') $puce = 'poubelle';
			if (acces_restreint_rubrique($id_rubrique))
				$puce = "puce-$puce-anim.gif";
			else
				$puce = "puce-$puce.gif";

			$s .= "<img src=\"IMG2/$puce\" width=\"13\" height=\"14\" border=\"0\">";
			$s .= "&nbsp;&nbsp;".typo($titre)."</A>";
			if ($petition) $s .= " <Font size=1 color='red'>P&Eacute;TITION</font>";

			$vals[] = $s;
		
			if ($afficher_auteurs) $vals[] = $les_auteurs;

			$s = affdate($date);
			if ($activer_statistiques == "oui" AND $afficher_visites AND $visites > 0) {
				$s .= "<br><font size=\"1\">($visites&nbsp;visites)</font>";
			}
			$vals[] = $s;

			$table[] = $vals;
		}
		mysql_free_result($result);

		if ($afficher_auteurs) {
			$largeurs = array('', 100, 90);
			$styles = array('arial2', 'arial1', 'arial1');
		}
		else {
			$largeurs = array('', 90);
			$styles = array('arial2', 'arial1');
		}
		afficher_liste($largeurs, $table, $styles);
		
		echo "</table></td></tr></table>";
	}
	return $num_rows;
}


//
// Afficher tableau de breves
//

function afficher_breves($titre_table, $requete) {
	global $connect_id_auteur;

	$tranches = afficher_tranches_requete($requete, 2);

	if (strlen($tranches)) {

		if ($titre_table) {
			echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0 background=''>";
			echo "<tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			echo "<tr bgcolor='#EEEECC'><td width=100% colspan=2><font face='Verdana,Arial,Helvetica,sans-serif' size=3 color='#000000'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
		else {
			echo "<p><table width=100% cellpadding=3 cellspacing=0 border=0 background=''>";
		}

		echo $tranches;

	 	$result = mysql_query($requete);
		$num_rows = mysql_num_rows($result);

		$table = '';
		while ($row = mysql_fetch_array($result)) {
			$vals = '';

			$id_breve = $row['id_breve'];
			$date_heure = $row['date_heure'];
			$titre = $row['titre'];
			$statut = $row['statut'];

			$s = "<a href=\"breves_voir.php3?id_breve=$id_breve\">";
			$puce = "IMG2/breve-$statut.gif";
			$s .= "<img src=\"$puce\" alt=\"o\" width=\"8\" height=\"9\" border=\"0\"> ";
			$s .= typo($titre);
			$s .= "</A>";
			$vals[] = $s;

			$s = "<div align=\"right\">";
			if ($statut == "prop") $s .= "[<font color=\"red\">&agrave; valider</font>]";
			else $s .= affdate($date_heure);
			$s .= "</div>";
			$vals[] = $s;
			$table[] = $vals;
		}
		mysql_free_result($result);

		$largeurs = array('', '');
		$styles = array('arial2', 'arial2');
		afficher_liste($largeurs, $table, $styles);

		if ($titre_table) echo "</TABLE></TD></TR>";
		echo "</TABLE>";
	}
	return $num_rows;
}


//
// Afficher tableau de rubriques
//

function afficher_rubriques($titre_table, $requete) {
	global $connect_id_auteur;

	$tranches = afficher_tranches_requete($requete, 2);

	if (strlen($tranches)) {

		if ($titre_table) {
			echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0 background=''>";
			echo "<tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			echo "<tr bgcolor='#333333'><td width=100% colspan=2><font face='Verdana,Arial,Helvetica,sans-serif' size=3 color='#FFFFFF'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
		else {
			echo "<p><table width=100% cellpadding=3 cellspacing=0 border=0 background=''>";
		}

		echo $tranches;

	 	$result = mysql_query($requete);
		$num_rows = mysql_num_rows($result);

		$table = '';
		while ($row = mysql_fetch_array($result)) {
			$vals = '';

			$id_rubrique = $row['id_rubrique'];
			$titre = $row['titre'];

			$s = "<b><a href=\"naviguer.php3?coll=$id_rubrique\">";
			$puce = "puce.gif";
			$s .= "<img src=\"$puce\" alt=\">\" border=\"0\"> ";
			$s .= typo($titre);
			$s .= "</A></b>";
			$vals[] = $s;

			$s = "<div align=\"right\">";
			$s .= "</div>";
			$vals[] = $s;
			$table[] = $vals;
		}
		mysql_free_result($result);

		$largeurs = array('', '');
		$styles = array('arial2', 'arial2');
		afficher_liste($largeurs, $table, $styles);

		if ($titre_table) echo "</TABLE></TD></TR>";
		echo "</TABLE>";
	}
	return $num_rows;
}

//
// Afficher les forums
//
 
function afficher_forum($request, $adresse_retour, $controle = "non", $recurrence = "oui") {
	global $debut;
	static $compteur_forum;
	static $nb_forum;
	static $i;
	global $couleur_foncee;
	global $connect_id_auteur;
	global $connect_activer_messagerie;	
	global $mots_cles_forums;


	$activer_messagerie = lire_meta("activer_messagerie");
	
	$compteur_forum++; 

	$nb_forum[$compteur_forum] = mysql_num_rows($request);
	$i[$compteur_forum] = 1;
 	while($row = mysql_fetch_array($request)) {
		$id_forum=$row[0];
		$id_parent=$row[1];
		$id_rubrique=$row[2];
		$id_article=$row[3];
		$id_breve=$row[4];
		$id_message=$row['id_message'];
		$id_syndic=$row['id_syndic'];
		$date_heure=$row[5];
		$titre=$row[6];
		$texte=$row[7];
		$auteur=$row[8];
		$email_auteur=$row[9];
		$nom_site=$row[10];
		$url_site=$row[11];
		$statut=$row[12];
		$ip=$row["ip"];
		$id_auteur=$row["id_auteur"];

		if ($compteur_forum==1){echo "<BR><BR>\n";}

		$afficher = ($controle=="oui") ? ($statut!="perso") :
			(($statut=="prive" OR $statut=="privrac" OR $statut=="privadm" OR $statut=="perso")
			OR ($statut=="publie" AND $id_parent > 0));

		if ($afficher) {
			echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr>";
			for ($count=2;$count<=$compteur_forum AND $count<11;$count++){
				$fond[$count]='IMG2/rien.gif';
				if ($i[$count]!=$nb_forum[$count]){		
					$fond[$count]='IMG2/forum-vert.gif';
				}		
				$fleche='IMG2/rien.gif';
				if ($count==$compteur_forum){		
					$fleche='IMG2/forum-droite.gif';
				}		
				echo "<td width=10 valign='top' background=$fond[$count]><img src=$fleche alt='' width=10 height=13 border=0></td>\n";
			}
			
			echo "\n<td width=100% bgcolor='#eeeeee' valign='top'>";

			// Si refuse, cadre rouge
			if ($statut=="off") {
				echo "<table width=100% cellpadding=2 cellspacing=0 border=0><tr><td bgcolor='#FF0000'>";
			}
			// Si propose, cadre jaune
			else if ($statut=="prop") {
				echo "<table width=100% cellpadding=2 cellspacing=0 border=0><tr><td bgcolor='#FFFF00'>";
			}

			echo "<table width=100% cellpadding=3 cellspacing=0><tr><td bgcolor='$couleur_foncee'><font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='#FFFFFF'><b>".typo($titre)."</b></font></td></tr>";
			echo "<tr><td bgcolor='#EEEEEE'>";
			echo "<font size=2 face='Georgia,Garamond,Times,serif'>";
			echo "<font face='arial,helvetica'>$date_heure</font>";

			if ($email_auteur) {
				echo " <a href=\"mailto:$email_auteur?subject=".rawurlencode($titre)."\">$auteur</a>";
			}
			else {
				echo " $auteur";
			}

			if ($id_auteur AND $activer_messagerie != "non" AND $connect_activer_messagerie != "non") {
				$bouton = bouton_imessage($id_auteur,$row_auteur);
				if ($bouton) echo "&nbsp;".$bouton;
			}

			if ($controle == "oui") {
				if ($statut != "off") {
					echo "<a href='articles_forum.php3?id_article=$id_article&supp_forum=$id_forum&debut=$debut' ".
						"onMouseOver=\"message$id_forum.src='IMG2/supprimer-message-on.gif'\" onMouseOut=\"message$id_forum.src='IMG2/supprimer-message-off.gif'\">";
					echo "<img src='IMG2/supprimer-message-off.gif' width=64 height=52 name='message$id_forum' align='right' border=0></a>";
				}
				else {
					echo "<br><font color='red'><b>MESSAGE SUPPRIM&Eacute; $ip</b></font>";
					if ($id_auteur) {
						echo " - <a href='auteurs_edit.php3?id_auteur=$id_auteur'>Voir cet auteur</A>";
					}
				}
				if ($statut == "prop" OR $statut == "off") {
					echo "<a href='articles_forum.php3?id_article=$id_article&valid_forum=$id_forum&debut=$debut' onMouseOver=\"valider_message$id_forum.src='IMG2/valider-message-on.gif'\" onMouseOut=\"valider_message$id_forum.src='IMG2/valider-message-off.gif'\"><img src='IMG2/valider-message-off.gif' width=60 height=52 name='valider_message$id_forum' align='right' border=0></a>";
				}
			}
			echo justifier(propre($texte));
			
			if (strlen($url_site) > 10 AND $nom_site) {
				echo "<p align='left'><font face='Verdana,Arial,Helvetica,sans-serif'><b><a href='$url_site'>$nom_site</a></b></font>";
			}
				
			if ($controle != "oui") {
				echo "<p align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
				$url = "forum_envoi.php3?id_parent=$id_forum&adresse_retour=".rawurlencode($adresse_retour)
					."&titre_message=".rawurlencode($titre);
				echo "<b><a href=\"$url\">R&eacute;pondre &agrave; ce message</a></b></font>";
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
			
			echo "</font>";
			echo "</td></tr></table>";
			if ($statut == "off" OR $statut == "prop") {
				echo "</td></tr></table>";
			}			
			echo "</td></tr></table>\n";

			if ($recurrence == "oui") forum($id_forum,$adresse_retour,$controle);
		}
		$i[$compteur_forum]++;
	}
	mysql_free_result($request);
	$compteur_forum--;
}

function forum($le_forum, $adresse_retour, $controle = "non") {
	global $id_breve;
      	echo "<font size=2 face='Georgia,Garamond,Times,serif'>";
	
	if ($controle == "oui") {
		$query_forum2 = "SELECT * FROM spip_forum WHERE id_parent='$le_forum' ORDER BY date_heure";
	}
	else {
		$query_forum2 = "SELECT * FROM spip_forum WHERE id_parent='$le_forum' AND statut<>'off' ORDER BY date_heure";
	}
 	$result_forum2 = mysql_query($query_forum2);
	afficher_forum($result_forum2, $adresse_retour, $controle);
}

//
// un bouton (en POST) a partir d'un URL en format GET
//
function bouton($titre,$lien) {
	$lapage=substr($lien,0,strpos($lien,"?"));
	$lesvars=substr($lien,strpos($lien,"?")+1,strlen($lien));

	echo "\n<form action='$lapage' method='get'>\n";
	$lesvars=explode("&",$lesvars);
	
	for($i=0;$i<count($lesvars);$i++){
		$var_loc=explode("=",$lesvars[$i]);
		echo "<input type='Hidden' name='$var_loc[0]' value=\"$var_loc[1]\">\n";
	}
	echo "<input type='submit' name='Submit' class='fondo' value=\"$titre\">\n";
	echo "</form>";
}


//
// Presentation de l'interface privee, debut du HTML
//

function debut_html($titre = "") {
	global $couleur_foncee;
	global $couleur_claire;
	$nom_site_spip = htmlspecialchars(lire_meta("nom_site"));
	$titre = textebrut(typo($titre));

	if ($nom_site_spip == "") $nom_site_spip="SPIP";
	
	?>
	<html>
	<head>
	<title>[<?php echo $nom_site_spip; ?>] <?php echo $titre; ?></TITLE>
	<meta http-equiv="Expires" content="0">
	<meta http-equiv="cache-control" content="no-cache,no-store">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style><!--
	.forml {width: 100%; background-color: #E4E4E4; background-position: center bottom; float: none; color: #000000}
	.formo {width: 100%; background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none;}
	.fondl {background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none; color: #000000}
	.fondo {background-color: <?php echo $couleur_foncee; ?>; background-position: center bottom; float: none; color: #FFFFFF}
	.fondf {background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519}
	.profondeur {border-right-color:white; border-top-color:#666666; border-left-color:#666666; border-bottom-color:white; border-style:solid}
	.hauteur {border-right-color:#666666; border-top-color:white; border-left-color:white; border-bottom-color:#666666; border-style:solid}
	label {cursor: pointer;}
	.arial1 {font-family: Arial, Helvetica, sans-serif; font-size: 10px;}
	.arial2 {font-family: Arial, Helvetica, sans-serif; font-size: 12px;}

	a {text-decoration: none;}
	a:hover {color:#FF9900; text-decoration: underline;}
	a.spip_in  {background-color:#eeeeee;}
	a.spip_out {}
	a.spip_note {}
	.spip_recherche {width : 100%}
	.spip_cadre { 
		width : 100%;
		background-color: #FFFFFF; 
		padding: 5px; 
	}

	.boutonlien {
		font-family: Verdana,Arial,Helvetica,sans-serif;
		font-weight: bold;
		font-size: 9px;
	}
	a.boutonlien:hover {color:#454545; text-decoration: none;}
	a.boutonlien {color:#808080; text-decoration: none;}

	h3.spip {
		font-family: Verdana,Arial,Helvetica,sans-serif;
		font-weight: bold;
		font-size: 115%;
		text-align: center;
	}
	.spip_documents{
		font-family: Verdana,Arial,Helvetica,sans-serif;
		font-size : 70%;
	}
	table.spip {
	}
	table.spip tr.row_first {
		background-color: #FCF4D0;
	}
	table.spip tr.row_odd {
		background-color: #C0C0C0;
	}
	table.spip tr.row_even {
		background-color: #F0F0F0;
	}
	table.spip td {
		padding: 1px;
		text-align: left;
		vertical-align: center;
	}

--></style>
<?php
afficher_script_layer();
?>
<script language="JavaScript">
<!--
	function MM_preloadImages() { //v3.0
		var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
		var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
		if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
	}

//-->
</script>
</head>
<body bgcolor="#E4E4E4" <?php
	global $fond;
	if ($fond == 1) $img='IMG2/rayures.gif';
	else if ($fond == 2) $img='IMG2/blob.gif';
	else if ($fond == 3) $img='IMG2/carreaux.gif';
	else if ($fond == 4) $img='IMG2/fond-trame.gif';
	else if ($fond == 5) $img='IMG2/degrade.jpg';
	else if (!$img) $img='IMG2/rayures.gif';

	echo "background='$img'";
	
	?> text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" >

<?php

}

//
// Debut du corps de la page
//

function debut_page($titre = "") {
	debut_html($titre);
?>
	<center>
<?php
	global $spip_survol;
	if ($spip_survol=="off"){

	?>	
		<table cellpadding=0 cellspacing=0 border=0>
		<td valign="top">
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td width=179>
		<a href="naviguer.php3" onMouseOver="texte.src='IMG2/naviguer-texte.gif'" onMouseOut="texte.src='IMG2/rien.gif'"><img src="IMG2/naviguer-off.gif" name="naviguer" alt="Naviguer" width="56" height="79" border="0"></A><A HREF="index.php3" onMouseOver="texte.src='IMG2/asuivre-texte.gif'" onMouseOut="texte.src='IMG2/rien.gif'"><img src="IMG2/asuivre-off.gif" name="asuivre" alt="A suivre" width="69" height="79" border="0"></A><A HREF="articles_tous.php3" onMouseOver="texte.src='IMG2/tout-texte.gif'" onMouseOut="texte.src='IMG2/rien.gif'"><img src="IMG2/tout-off.gif" name="tout" alt="Tout le site" width="54" height="79" border="0"></A>
		</td></tr>
		<tr><td><img src="IMG2/rien.gif" name="texte" alt="" width="179" height="24" border="0"></td>
		</tr></table>
		</td>
		<td valign="top">
		<table cellpadding="0" cellspacing="0" border="0">
		<tr><td><?php
		global $articles_mots;
		if ($articles_mots != "non"){
		?><a href="mots_tous.php3" onMouseOver="fond.src='IMG2/cles-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/cles-off.gif" name="cles" alt="Les mots-cl&eacute;s" width="78" height="58" border="0"></A><?php
	}else{
	?><img src="IMG2/cles-non.gif" name="cles" alt="Les mots-cl&eacute;s" width="78" height="58" border="0"><?php
	}
	?></td></tr>
		<tr><td><?php global $activer_breves;
		if ($activer_breves!="non"){ ?><A HREF="breves.php3" onMouseOver="fond.src='IMG2/breves-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/breves-off.gif" name="breves" alt="Les br&egrave;ves" width="78" height="45" border="0"></A><?php }else{ ?><img src="IMG2/breves-non.gif" name="breves" alt="Les br&egrave;ves" width="78" height="45" border="0"><?php } ?></TD></TR>
		</table></td>
		<td valign="top">
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td><a href="auteurs.php3" onMouseOver="fond.src='IMG2/redacteurs-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/redacteurs-off.gif" name="redacteurs" alt="Les r&eacute;dacteurs" width="52"  height="58" border="0"></A><?php
		global $options;
		global $connect_statut;
		if ($options=='avancees' AND $connect_statut == '0minirezo'){
		?><a href="controle_forum.php3" onMouseOver="fond.src='IMG2/suivre-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/suivre-off.gif" name="suivre" alt="Suivre les forums" width="58" height="58" border="0"></A><?php

	global $activer_statistiques;

	if ($activer_statistiques=="non"){
		echo "<img src='IMG2/statistiques-non.gif' name='statistiques' alt='Statistiques' width='43' height='58' border='0'>";
	}else{

	?><A HREF="articles_class.php3" onMouseOver="fond.src='IMG2/statistiques-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/statistiques-off.gif" name="statistiques" alt="Statistiques" width="43" height="58" border="0"></A><?php
	}
	?><?php
	global $connect_toutes_rubriques;
	
	if ($connect_toutes_rubriques){
	?><A HREF="configuration.php3" onMouseOver="fond.src='IMG2/config-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/config-off.gif" name="config" alt="Configurer" width="44" height="58" border="0"></A><A HREF="admin_tech.php3" onMouseOver="fond.src='IMG2/sauvegarde-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/sauvegarde-off.gif" name="sauvegarde" alt="Sauvegarde de la base" width="53" height="58" border="0"></A><?php
	}else{
		echo "<img src='IMG2/haut-vide.gif' alt='' width='97' height='58' border='0'>";
	}?><?php
		}else{
		?><img src="IMG2/haut-vide.gif" alt="" width="198" height="58" border="0"><?php
		}
		?><a href="forum.php3" onMouseOver="fond.src='IMG2/forum-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/forum-off.gif" name="forum" alt="Forum interne" width="57" height="58" border="0"></a><a href="javascript:window.open('aide_index.php3', 'aide_spip', 'scrollbars=yes,resizable=yes,width=700'); void(0);" onMouseOver="fond.src='IMG2/aide-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/aide-off.gif" name="aide" alt="A l'aide" width="50" height="58" border="0"></a></td></tr>
		<tr><?php
		if ($options != 'avancees'){
			echo "<td background='IMG2/rond-vert-simp.gif'>";
		}else{
			echo "<td background='IMG2/rond-vert-comp.gif'>";
		
		}
		?><img src="IMG2/rien.gif" name="fond" alt="" width="357" height="45" border="0" usemap="#map-interface"></td>
		</tr>
		</table>
		

		</td>
		<td valign="top">
		
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td><?php
		global $adresse_site;
		if (strlen($adresse_site)<10) $adresse_site="../";

		echo "<a href='$adresse_site' onMouseOver=\"visiter2.src='IMG2/visiter-texte.gif'\" onMouseOut=\"visiter2.src='IMG2/rien.gif'\"><img src=\"IMG2/visiter-off.gif\" name=\"visiter\" alt=\"Visiter le site\" width=\"86\" height=\"79\" border=\"0\"></A>";
		?></td></tr>
		<tr><td><img src="IMG2/rien.gif" name="visiter2" alt="" width="86" height="24" border="0">
		</td>
		</tr></table>
		</td>
		</tr></table>
<?php

}else{

?>		
		<table cellpadding=0 cellspacing=0 border=0>
		<td valign="top">
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td width=179>
		<a href="naviguer.php3" onMouseOver="naviguer.src='IMG2/naviguer-on.gif'; texte.src='IMG2/naviguer-texte.gif'" onMouseOut="naviguer.src='IMG2/naviguer-off.gif'; texte.src='IMG2/rien.gif'"><img src="IMG2/naviguer-off.gif" name="naviguer" alt="Naviguer" width="56" height="79" border="0"></A><A HREF="index.php3" onMouseOver="asuivre.src='IMG2/asuivre-on.gif'; texte.src='IMG2/asuivre-texte.gif'" onMouseOut="asuivre.src='IMG2/asuivre-off.gif'; texte.src='IMG2/rien.gif'"><img src="IMG2/asuivre-off.gif" name="asuivre" alt="A suivre" width="69" height="79" border="0"></A><A HREF="articles_tous.php3" onMouseOver="tout.src='IMG2/tout-on.gif'; texte.src='IMG2/tout-texte.gif'" onMouseOut="tout.src='IMG2/tout-off.gif'; texte.src='IMG2/rien.gif'"><img src="IMG2/tout-off.gif" name="tout" alt="Tout le site" width="54" height="79" border="0"></A>
		</td></tr>
		<tr><td><img src="IMG2/rien.gif" name="texte" alt="" width="179" height="24" border="0"></TD>
		</tr></table>
		</td>
		<td valign="top">
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td><?php
		global $articles_mots;
		if ($articles_mots!="non"){
		?><A HREF="mots_tous.php3" onMouseOver="cles.src='IMG2/cles-on.gif'; fond.src='IMG2/cles-texte.gif'" onMouseOut="cles.src='IMG2/cles-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/cles-off.gif" name="cles" alt="Les mots-cl&eacute;s" width="78" height="58" border="0"></A><?php
	}else{
	?><img src="IMG2/cles-non.gif" name="cles" alt="Les mots-cl&eacute;s" width="78" height="58" border="0"><?php
	}
	?></td></tr>
		<tr><td><?php global $activer_breves;
		if ($activer_breves!="non"){ ?><A HREF="breves.php3" onMouseOver="breves.src='IMG2/breves-on.gif'; fond.src='IMG2/breves-texte.gif'" onMouseOut="breves.src='IMG2/breves-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/breves-off.gif" name="breves" alt="Les br&egrave;ves" width="78" height="45" border="0"></A><?php }else{ ?><img src="IMG2/breves-non.gif" name="breves" alt="Les br&egrave;ves" width="78" height="45" border="0"><?php } ?></TD></TR>
		</table></td>
		<td valign="top">
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td><a href="auteurs.php3" onMouseOver="redacteurs.src='IMG2/redacteurs-on.gif'; fond.src='IMG2/redacteurs-texte.gif'" onMouseOut="redacteurs.src='IMG2/redacteurs-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/redacteurs-off.gif" name="redacteurs" alt="Les r&eacute;dacteurs" width="52"  height="58" border="0"></A><?php
		global $options;
		global $connect_statut;
		if ($options=='avancees' AND $connect_statut == '0minirezo'){
		?><a href="controle_forum.php3" onMouseOver="suivre.src='IMG2/suivre-on.gif'; fond.src='IMG2/suivre-texte.gif'" onMouseOut="suivre.src='IMG2/suivre-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/suivre-off.gif" name="suivre" alt="Suivre les forums" width="58" height="58" border="0"></A><?php

	global $activer_statistiques;

	if ($activer_statistiques=="non"){
		echo "<img src='IMG2/statistiques-non.gif' name='statistiques' alt='Statistiques' width='43' height='58' border='0'>";
	}else{

	?><a href="articles_class.php3" onMouseOver="statistiques.src='IMG2/statistiques-on.gif'; fond.src='IMG2/statistiques-texte.gif'" onMouseOut="statistiques.src='IMG2/statistiques-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/statistiques-off.gif" name="statistiques" alt="Statistiques" width="43" height="58" border="0"></A><?php
	}
	?><?php
	 global $connect_toutes_rubriques;

	if ($connect_toutes_rubriques){	
	?><a href="configuration.php3" onMouseOver="config.src='IMG2/config-on.gif'; fond.src='IMG2/config-texte.gif'" onMouseOut="config.src='IMG2/config-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/config-off.gif" name="config" alt="Configurer" width="44" height="58" border="0"></A><A HREF="admin_tech.php3" onMouseOver="sauvegarde.src='IMG2/sauvegarde-on.gif'; fond.src='IMG2/sauvegarde-texte.gif'" onMouseOut="sauvegarde.src='IMG2/sauvegarde-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/sauvegarde-off.gif" name="sauvegarde" alt="Sauvegarde de la base" width="53" height="58" border="0"></A><?php
	}else{
		echo "<img src='IMG2/haut-vide.gif' alt='' width='97' height='58' border='0'>";
	}?><?php
		}else{
		?><img src="IMG2/haut-vide.gif" alt="" width="198" height="58" border="0"><?php
		}
		?><a href="forum.php3" onMouseOver="forum.src='IMG2/forum-on.gif'; fond.src='IMG2/forum-texte.gif'" onMouseOut="forum.src='IMG2/forum-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/forum-off.gif" name="forum" alt="Forum interne" width="57" height="58" border="0"></a><a href="javascript:window.open('aide_index.php3', 'aide_spip', 'scrollbars=yes,resizable=yes,width=700'); void(0);" onMouseOver="aide.src='IMG2/aide-on.gif'; fond.src='IMG2/aide-texte.gif'" onMouseOut="aide.src='IMG2/aide-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/aide-off.gif" name="aide" alt="A l'aide" width="50" height="58" border="0"></a></td></tr>
		<tr><?php
		if ($options != 'avancees'){
			echo "<td background='IMG2/rond-vert-simp.gif'>";
		}else{
			echo "<td background='IMG2/rond-vert-comp.gif'>";
		
		}
		?><img src="IMG2/rien.gif" name="fond" alt="" width="357" height="45" border="0" usemap="#map-interface"></td>
		</tr>
		</table>
		

		</td>
		<td valign="top">
		
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td><?php
		global $adresse_site;
		if (strlen($adresse_site)<10) $adresse_site="../";

		echo "<A HREF='$adresse_site' onMouseOver=\"visiter.src='IMG2/visiter-on.gif'; visiter2.src='IMG2/visiter-texte.gif'\" onMouseOut=\"visiter.src='IMG2/visiter-off.gif'; visiter2.src='IMG2/rien.gif'\"><img src=\"IMG2/visiter-off.gif\" name=\"visiter\" alt=\"Visiter le site\" width=\"86\" height=\"79\" border=\"0\"></A>";
		?></td></tr>
		<tr><td><img src="IMG2/rien.gif" name="visiter2" alt="" width="86" height="24" border="0">
		</td>
		</tr></table>
		</td>
		</tr></table>
<?php
}

?>

	<map name="map-interface">
	<area shape='rect' coords='19,29,31,44' href='interface.php3' onMouseOver="fond.src='IMG2/modifier-interface-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'">
	<?php

	global $REQUEST_URI;
	global $requete_fichier;
	global $connect_id_auteur;
	
	if (!$requete_fichier) {
		$requete_fichier = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1);
	}
	$lien = ereg_replace("\&set_options=(basiques|avancees)", "", $requete_fichier);
	if (!ereg('\?', $lien)) $lien .= '?';

	if ($options=="avancees"){
		echo "<area shape='rect' coords='56,30,156,44' href='$lien&set_options=basiques' onMouseOver=\"fond.src='IMG2/interface-texte.gif'\" onMouseOut=\"fond.src='IMG2/rien.gif'\">";
	}else{
		echo "<area shape='rect' coords='163,30,268,44' href='$lien&set_options=avancees' onMouseOver=\"fond.src='IMG2/interface-texte.gif'\" onMouseOut=\"fond.src='IMG2/rien.gif'\">";
	}

	?>

	</map>

	<?php

	//
	// Resume de messagerie
	//
	
	global $changer_config;
	global $activer_messagerie;
	global $activer_imessage;
	global $connect_activer_messagerie;
	
	
	if ($changer_config != "oui") {
		$activer_messagerie = lire_meta("activer_messagerie");
		$activer_imessage = lire_meta("activer_imessage");
	}
	
	echo "<font face='verdana,arial,helvetica,sans-serif' size=1><b><a href='sites_tous.php3'><font color='#666666'><img src='IMG2/tous-sites.gif' align='middle' alt='' width='16' height='15' border='0'> TOUS LES SITES R&Eacute;F&Eacute;RENC&Eacute;S</font></a></b></font>";

	if ($activer_messagerie != 'non' AND $connect_activer_messagerie != 'non') {
		echo "<font face='verdana,arial,helvetica,sans-serif' size=1><b>";
		
		echo " &nbsp; &nbsp; <a href='messagerie.php3'><font color='#666666'><img src='IMG2/tous-messages.gif' align='middle' alt='' width='17' height='15' border='0'> TOUS VOS MESSAGES</font></a>";

		$result_messages = mysql_query("SELECT * FROM spip_messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=spip_messages.id_message");
		$total_messages = @mysql_num_rows($result_messages);
		if ($total_messages == 1) {
			while($row = @mysql_fetch_array($result_messages)) {
				$ze_message=$row[0];
				echo " | <a href='message.php3?id_message=$ze_message'><font color='red'>VOUS AVEZ UN NOUVEAU MESSAGE</font></a>";
			}
		}
		if ($total_messages > 1) echo " | <a href='messagerie.php3'><font color='red'>VOUS AVEZ $total_messages NOUVEAUX MESSAGES</font></a>";
		

		$result_messages = mysql_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur='$connect_id_auteur' AND messages.statut='publie' AND lien.id_message=messages.id_message AND messages.rv='oui' AND messages.date_heure>DATE_SUB(NOW(),INTERVAL 1 DAY) GROUP BY messages.id_message");
		$total_messages = @mysql_num_rows($result_messages);
		
		if ($total_messages == 1) {
			while ($row = @mysql_fetch_array($result_messages)) {
				$ze_message = $row[0];
				echo " | <a href='message.php3?id_message=$ze_message'><font color='red'>UN RENDEZ-VOUS</font></a> ";
			}
		}
		if ($total_messages > 1) echo " | <a href='calendrier.php3'><font color='red'>$total_messages RENDEZ-VOUS</font></a> ";

		echo "</b></font>";
	}
	
	echo " &nbsp; &nbsp; <font face='verdana,arial,helvetica,sans-serif' size=1><b><a href='calendrier.php3'><font color='#666666'><img src='IMG2/calendrier.gif' align='middle' alt='' width='14' height='18' border='0'> CALENDRIER</font></a></b></font>";
	 
}


//
// Debut de la colonne de gauche
//

function debut_gauche() {
	global $connect_statut, $cookie_admin;
	global $REQUEST_URI;
	global $options;
	global $requete_fichier;
	global $connect_id_auteur;

	if (!$requete_fichier) {
		$requete_fichier = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1);
	}
	$lien = $requete_fichier;
	if (!ereg('\?', $lien)) $lien .= '?';

	$lapage=$lien;
	if ($lapage=="?") $lapage="index.php3?";
	if (ereg("&",$lapage)) $lapage=substr($lapage,0,strpos($lapage,"&"));
	
	?>
	<br><br>

	<table width=700 cellpadding=0 cellspacing=0 border=0>

	<tr>
	<td width=180 valign="top">
	<font face='Georgia,Garamond,Times,serif' size=2>
	<?php
	
	
	// Afficher les auteurs recemment connectes
	
	global $changer_config;
	global $activer_messagerie;
	global $activer_imessage;
	global $connect_activer_messagerie;
	global $connect_activer_imessage;
	
	if ($changer_config!="oui"){
		$activer_messagerie=lire_meta("activer_messagerie");
		$activer_imessage=lire_meta("activer_imessage");
	}

	if ($activer_messagerie!="non" AND $connect_activer_messagerie!="non"){
	
		debut_cadre_relief();

		echo "<a href='message_edit.php3?new=oui&type=normal'><img src='IMG2/m_envoi.gif' width='14' height='7' border='0'>";
		echo "<font color='#169249' face='verdana,arial,helvetica,sans-serif' size=1><b>&nbsp;NOUVEAU MESSAGE</b></font></a>";
		echo "\n<br><a href='message_edit.php3?new=oui&type=pb'><img src='IMG2/m_envoi_bleu.gif' width='14' height='7' border='0'>";
		echo "<font color='#044476' face='verdana,arial,helvetica,sans-serif' size=1><b>&nbsp;NOUVEAU PENSE-B&Ecirc;TE</b></font></a>";

		if ($activer_imessage != "non" AND ($connect_activer_imessage != "non" OR $connect_statut == "0minirezo")) {
		 	$query2 = "SELECT * FROM spip_auteurs WHERE spip_auteurs.id_auteur!=$connect_id_auteur AND spip_auteurs.imessage!='non' AND spip_auteurs.messagerie!='non' AND spip_auteurs.en_ligne>DATE_SUB(NOW(),INTERVAL 5 MINUTE)";
			$result_auteurs = mysql_query($query2);

			if (mysql_num_rows($result_auteurs) > 0) {
				echo "<font face='verdana,arial,helvetica,sans-serif' size=2>";
				echo "<p><b>Actuellement en ligne&nbsp;:</b>";
			
				while($row = mysql_fetch_array($result_auteurs)){
					$id_auteur = $row["id_auteur"];
					$nom_auteur = typo($row["nom"]);
					echo "<br>".bouton_imessage($id_auteur,$row)." $nom_auteur";
				}	
				echo "</font>";
			}
		}
		fin_cadre_relief();
	}	
}


//
// Presentation de l'interface privee, marge de droite
//

function debut_droite() {
	//
	// Boite de recherche
	//

	echo '<p><form method="get" action="recherche.php3">';
	debut_cadre_relief();
	echo '<font face="verdana,arial,helvetica,sans-serif" size="2">';
	echo 'Recherche sur les titres des articles et des br&egrave;ves&nbsp;:<br>';
	echo '<input type="text" size="18" name="recherche" class="spip_recherche">';
	echo "</font>\n";
	fin_cadre_relief();
	echo "</form>";

	?>
	<br></font>
	&nbsp;
	</td>
	<td width=40 rowspan=1>&nbsp;</td>
	<td width=480 valign="top" rowspan=2>
	<font face="Georgia,Garamond,Times,serif" size=3>
	<?php
}


//
// Presentation de l'interface privee, fin de page et flush()
//

function fin_html() {
	global $spip_version_affichee;
?>
<p align='right'><font face="Verdana, Arial, Helvetica, sans-serif" size='2'>
<a href='http://www.uzine.net/spip'>SPIP <?php echo $spip_version_affichee; ?></a>
est distribu&eacute; <a href='gpl.txt'>sous licence GPL</a>.</p>
</body></html>
<?php
	flush();
}


function fin_page() {

?>
</td></tr>
</table></center>
<?php
	fin_html();
}

//
// Presentation des pages d'installation et d'erreurs
//

function install_debut_html($titre="Installation du syst&egrave;me de publication...") {
	?>
<html>
<head>
<title><?php echo $titre; ?></title>
<meta http-equiv="Expires" content="0">
<meta http-equiv="cache-control" content="no-cache,no-store">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<style>
<!--
	a {text-decoration: none; }
	A:Hover {color:#FF9900; text-decoration: underline;}
	.forml {width: 100%; background-color: #FFCC66; background-position: center bottom; float: none; color: #000000}
	.formo {width: 100%; background-color: #FFF0E0; background-position: center bottom; weight: bold; float: none; color: #000000}
	.fondl {background-color: #FFCC66; background-position: center bottom; float: none; color: #000000}
	.fondo {background-color: #FFF0E0; background-position: center bottom; float: none; color: #000000}
	.fondf {background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519}
-->
</style>
</head>

<body bgcolor="#FFFFFF" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" topmargin="0" leftmargin="0" marginwidth="0" marginheight="0">

<br><br><br>
<center>
<table width="450">
<tr><td width="450">
<font face="Verdana,Arial,Helvetica,sans-serif" size="4" color="#970038"><B><?php 
	echo $titre; 
?></b></font>
<font face="Georgia,Garamond,Times,serif" size="3">
	<?php
}

function install_fin_html() {

	echo '
	</font>
	</td></tr></table>
	</center>
	</body>
	</html>
	';
}

?>
