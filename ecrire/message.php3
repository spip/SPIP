<?php

include ("inc.php3");
include_local ("inc_mots.php3");



$query = "SELECT COUNT(*) FROM spip_auteurs_messages WHERE id_auteur=$connect_id_auteur AND id_message=$id_message";
$result = spip_query($query);
list($n) = mysql_fetch_array($result);
if (!$n) {

	$query_message = "SELECT * FROM spip_messages WHERE id_message=$id_message";
	$result_message = spip_query($query_message);
	while($row = mysql_fetch_array($result_message)) {
		$type = $row['type'];
	}
	if ($type != "affich"){
		debut_page("Acc&egrave;s refus&eacute;");
		debut_gauche();
		debut_droite();
		echo "<b>Vous n'avez pas acc&egrave;s &agrave; ce message.</b><p>";
		fin_page();
		exit;
	}
}

function my_sel($num, $tex, $comp) {
	if ($num == $comp) {
		echo "<OPTION VALUE='$num' SELECTED>$tex\n";
	}
	else {
		echo "<OPTION VALUE='$num'>$tex\n";
	}
}

function afficher_mois($mois){
	my_sel("01", "janvier", $mois);
	my_sel("02", "f&eacute;vrier", $mois);
	my_sel("03", "mars", $mois);
	my_sel("04", "avril", $mois);
	my_sel("05", "mai", $mois);
	my_sel("06", "juin", $mois);
	my_sel("07", "juillet", $mois);
	my_sel("08", "ao&ucirc;t", $mois);
	my_sel("09", "septembre", $mois);
	my_sel("10", "octobre", $mois);
	my_sel("11", "novembre", $mois);
	my_sel("12", "d&eacute;cembre", $mois);
}

function afficher_annee($annee) {
	if ($annee < 1996) {
		echo "<OPTION VALUE='$annee' SELECTED>$annee\n";
	}
	for ($i=date("Y") - 1; $i < date("Y") + 3; $i++) {
		my_sel($i,$i,$annee);
	}
}

function afficher_jour($jour){
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		my_sel($i,$aff,$jour);
	}
}



if ($ajout_forum AND strlen($texte) > 10 AND strlen($titre) > 2) {
	spip_query("UPDATE spip_auteurs_messages SET vu='non' WHERE id_message='$id_message'");
}

if ($modifier_message == "oui") {
	$titre = addslashes($titre);
	$texte = addslashes($texte);
	spip_query("UPDATE spip_messages SET titre='$titre', texte='$texte' WHERE id_message='$id_message'");	
}

if ($changer_rv) {
	spip_query("UPDATE spip_messages SET rv='$rv' WHERE id_message='$id_message'");	
}

if ($jour) {
	spip_query("UPDATE spip_messages SET date_heure='$annee-$mois-$jour $heures:$minutes:00' WHERE id_message='$id_message'");	
}

if ($change_statut) {
	spip_query("UPDATE spip_messages SET statut='$change_statut' WHERE id_message='$id_message'");	
	spip_query("UPDATE spip_messages SET date_heure=NOW() WHERE id_message='$id_message' AND rv<>'oui'");	
}

if ($supp_dest) {
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_message='$id_message' AND id_auteur='$supp_dest'");
}



//
//

$query_message = "SELECT * FROM spip_messages WHERE id_message=$id_message";
$result_message = spip_query($query_message);

while($row = mysql_fetch_array($result_message)) {
	$id_message = $row[0];
	$date_heure = $row["date_heure"];
	$titre = typo($row["titre"]);
	$texte = propre($row["texte"]);
	$type = $row["type"];
	$statut = $row["statut"];
	$page = $row["page"];
	$rv = $row["rv"];
	$expediteur = $row['id_auteur'];
	
	// Marquer le message vu pour le visiteur
	if ($type != "affich")
		spip_query("UPDATE spip_auteurs_messages SET vu='oui' WHERE id_message='$id_message' AND id_auteur='$connect_id_auteur'");


	
	debut_page($titre, "messagerie", "messagerie");

	debut_gauche();
	
	if ($statut == 'publie' AND $type == 'normal' AND $type != 'affich') {

		echo "<div align='center'>";
		icone ("Ne plus participer &agrave; cette discussion", "messagerie.php3?id_message=$id_message&supp_dest=$connect_id_auteur", "messagerie-24.gif", "supprimer.gif");
		echo "</div>";
	}
	
	
	debut_droite();

	debut_cadre_relief("messagerie-24.gif");
	echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0>";
	echo "<TR><TD>";

	if ($type == 'normal') {
		$le_type = "MESSAGE".aide ("messut");
		$la_couleur = "green";
	}
	else if ($type == 'pb') {
		$le_type = "PENSE-B&Ecirc;TE".aide ("messpense");
		$la_couleur = "blue";	
	}
	else if ($type == 'affich') {
		$le_type = "ANNONCE";
		$la_couleur = "red";	
	}

	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='$la_couleur'><b>$le_type</b></font><br>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=5><b>$titre</b></font>";
	if ($statut == 'redac') {
		echo "<br><font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='red'><b>EN COURS DE R&Eacute;DACTION</b></font>";
	}
	else if ($rv != 'oui') {
		echo "<br><font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='#666666'><b>".nom_jour($date_heure)." ".affdate($date_heure)." &Agrave; ".heures($date_heure)."H".minutes($date_heure)."</b></font>";
	}
	echo "<p>";


	//////////////////////////////////////////////////////
	// Message normal
	//
	
	if ($type == 'normal') {
		debut_cadre_enfonce("redacteurs-24.gif");

		//
		// Expediteur
		//		
		$result = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$expediteur");

		if ($row = mysql_fetch_array($result)) {

			echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC'>";
			echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'><B>AUTEUR DU MESSAGE :</B></FONT>";
			echo "</td></tr>";

			$nom_auteur = typo($row["nom"]);
			$statut_auteur = $row["statut"];
			$id_auteur = $expediteur;
			
			if (!$ifond) {
				$ifond = 1;
				$couleur = '#FFFFFF';
			}
			else {
				$ifond = 0;
				$couleur = $couleur_claire;
			}				

			echo "<tr><td background='' bgcolor='$couleur'><font face='Verdana,Arial,Helvetica,sans-serif' size=2>";

			switch ($statut_auteur) {
			case "0minirezo":
				echo "<img src='img_pack/bonhomme-noir.gif' alt='Admin' width='23' height='12' border='0'>";
				break;					
			case "2redac":
			case "1comite":
				echo "<img src='img_pack/bonhomme-bleu.gif' alt='Admin' width='23' height='12' border='0'>";
				break;					
			case "5poubelle":
				echo "<img src='img_pack/bonhomme-rouge.gif' alt='Admin' width='23' height='12' border='0'>";
				break;					
			case "nouveau":
			default:
				echo "&nbsp;";
			}

			echo ' '.$nom_auteur;
			echo "</font></td></tr>";
				
			echo "</table>";

		}


		//
		// Recherche d'auteur
		//

		if ($cherche_auteur) {
			echo "<P ALIGN='left'>";
			$query = "SELECT id_auteur, nom FROM spip_auteurs WHERE messagerie<>'non' AND id_auteur<>'$connect_id_auteur'";
			$result = spip_query($query);
			unset($table_auteurs);
			unset($table_ids);
			while ($row = mysql_fetch_array($result)) {
				$table_auteurs[] = $row[1];
				$table_ids[] = $row[0];
			}
			$resultat = mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
			debut_boite_info();
			if (!$resultat) {
				echo "<B>Aucun r&eacute;sultat pour \"$cherche_auteur\".</B><BR>";
			}
			else if (count($resultat) == 1) {
				$ajout_auteur = 'oui';
				list(, $nouv_auteur) = each($resultat);
				echo "<B>Le participant suivant est ajout&eacute; :</B><BR>";
				$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$nouv_auteur";
				$result = spip_query($query);
				echo "<UL>";
				while ($row = mysql_fetch_array($result)) {
					$id_auteur = $row['id_auteur'];
					$nom_auteur = $row['nom'];
					$email_auteur = $row['email'];
					$bio_auteur = $row['bio'];

					echo "<LI><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B><FONT SIZE=3>$nom_auteur</FONT></B>";
					echo "</FONT>\n";
				}
				echo "</UL>";
			}
			else if (count($resultat) < 16) {
				reset($resultat);
				unset($les_auteurs);
				while (list(, $id_auteur) = each($resultat)) $les_auteurs[] = $id_auteur;
				if ($les_auteurs) {
					$les_auteurs = join(',', $les_auteurs);
					echo "<B>Plusieurs r&eacute;dacteurs trouv&eacute;s pour \"$cherche_auteur\":</B><BR>";
					$query = "SELECT * FROM spip_auteurs WHERE id_auteur IN ($les_auteurs) ORDER BY nom";
					$result = spip_query($query);
					echo "<UL>";
					while ($row = mysql_fetch_array($result)) {
						$id_auteur = $row['id_auteur'];
						$nom_auteur = $row['nom'];
						$email_auteur = $row['email'];
						$bio_auteur = $row['bio'];
			
						echo "<LI><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B><FONT SIZE=3>$nom_auteur</FONT></B>";
					
						if ($email_auteur) echo " ($email_auteur)";
						echo " | <A HREF=\"message.php3?id_message=$id_message&ajout_auteur=oui&nouv_auteur=$id_auteur\">Ajouter ce destinataire</A>";
					
						if (trim($bio_auteur)) {
							echo "<BR><FONT SIZE=1>".propre(couper($bio_auteur, 100))."</FONT>\n";
						}
						echo "</FONT><p>\n";
					}
					echo "</UL>";
				}
			}
			else {
				echo "<B>Trop de r&eacute;sultats pour \"$cherche_auteur\" ; veuillez affiner la recherche.</B><BR>";
			}
			fin_boite_info();
			echo "<P>";

		}

		if ($nouv_auteur > 0) {
			$query = "DELETE FROM spip_auteurs_messages WHERE id_auteur='$nouv_auteur' AND id_message='$id_message'";
			$result = spip_query($query);
			$query = "INSERT INTO spip_auteurs_messages (id_auteur,id_message,vu) VALUES ('$nouv_auteur','$id_message','non')";
			$result = spip_query($query);
		}


		//
		// Liste des participants
		//

		$query_auteurs = "SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE lien.id_message=$id_message AND lien.id_auteur=auteurs.id_auteur";
		$result_auteurs = spip_query($query_auteurs);
		
		$total_dest = mysql_num_rows($result_auteurs);
		
		if ($total_dest > 0) {
			echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC' colspan=2>";
			echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'><B>PARTICIPANTS A LA DISCUSSION :</B></FONT>";
			echo "</td></tr>";
			
			$ifond = 0;
			while($row = mysql_fetch_array($result_auteurs)) {
				$id_auteur = $row["id_auteur"];
				$nom_auteur = typo($row["nom"]);
				$statut_auteur = $row["statut"];
				$ze_auteurs[] = $id_auteur;
			
				if ($ifond == 0) {
					$ifond = 1;
					$couleur = "#FFFFFF";
				}
				else {
					$ifond = 0;
					$couleur = "$couleur_claire";
				}				

				echo "<tr><td background='' bgcolor='$couleur'><font face='Verdana,Arial,Helvetica,sans-serif' size=2>";

				switch ($statut_auteur) {
				case "0minirezo":
					echo "<img src='img_pack/bonhomme-noir.gif' alt='Admin' width='23' height='12' border='0'>";
					break;					
				case "2redac":
				case "1comite":
					echo "<img src='img_pack/bonhomme-bleu.gif' alt='Admin' width='23' height='12' border='0'>";
					break;					
				case "5poubelle":
					echo "<img src='img_pack/bonhomme-rouge.gif' alt='Admin' width='23' height='12' border='0'>";
					break;					
				case "nouveau":
					echo "&nbsp;";
					break;
				default:
					echo "&nbsp;";
				}

				echo " $nom_auteur";
				echo "</font></td>";
				
				echo "<td background='' bgcolor='$couleur' align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
				if ($id_auteur != $connect_id_auteur) {
					echo "[<a href='message.php3?id_message=$id_message&supp_dest=$id_auteur'>retirer ce participant</a>]";
				}
				else {
					echo "&nbsp;";
				}
				
				echo "</font></td>";
				echo "</tr>\n";
			}
			echo "</table>";
		}

		$ze_auteurs = join(',', $ze_auteurs);

		//
		// Ajouter des participants
		//
		
		if ($type == 'normal') {

			if ($statut == 'redac' OR $forcer_dest) {
				$query_ajout_auteurs = "SELECT * FROM spip_auteurs WHERE ";
				if ($les_auteurs) $query_ajout_auteurs .= "id_auteur NOT IN ($ze_auteurs) AND ";
				$query_ajout_auteurs .= " messagerie<>'non' AND statut<>'5poubelle' AND statut<>'nouveau' AND statut<>'6forum' AND nom<>'Nouvel auteur' ORDER BY statut, nom";
				$result_ajout_auteurs = spip_query($query_ajout_auteurs);

				if (mysql_num_rows($result_ajout_auteurs) > 0) {

					echo "<FORM ACTION='message.php3' METHOD='post'>";
					echo "<DIV align=left><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B>AJOUTER UN PARTICIPANT : &nbsp; </B></FONT>\n";
					echo "<INPUT TYPE='Hidden' NAME='id_message' VALUE=\"$id_message\">";

					if (mysql_num_rows($result_ajout_auteurs) > 50 AND $flag_mots_ressemblants) {
						echo "<INPUT TYPE='text' NAME='cherche_auteur' CLASS='fondl' VALUE='' SIZE='20'>";
						echo "<INPUT TYPE='submit' NAME='Chercher' VALUE='Chercher' CLASS='fondo'>";
					}
					else {
						echo "<SELECT NAME='nouv_auteur' SIZE='1' STYLE='WIDTH=150' CLASS='fondl'>";
						$group = false;
						$group2 = false;
				
						while($row=mysql_fetch_array($result_ajout_auteurs)) {
							$id_auteur = $row[0];
							$nom = $row[1];
							$email = $row[3];
							$statut_auteur = $row[8];
				
							$statut_auteur=ereg_replace("0minirezo", "Administrateur", $statut_auteur);
							$statut_auteur=ereg_replace("1comite", "R&eacute;dacteur", $statut_auteur);
							$statut_auteur=ereg_replace("2redac", "R&eacute;dacteur", $statut_auteur);
							$statut_auteur=ereg_replace("5poubelle", "Effac&eacute;", $statut_auteur);
				
							$premiere = strtoupper(substr(trim($nom), 0, 1));
				
							if ($connect_statut != '0minirezo') {
								if ($p = strpos($email, '@')) $email = substr($email, 0, $p).'@...';
							}
				
							if ($statut_auteur != $statut_old) {
								echo "\n<OPTION VALUE=\"x\">";
								echo "\n<OPTION VALUE=\"x\"> $statut_auteur".'s';
							}
						
							if ($premiere != $premiere_old AND ($statut_auteur != 'Administrateur' OR !$premiere_old)) {
								echo "\n<OPTION VALUE=\"x\">";
							}
				
							$texte_option = couper("$nom ($email) ", 40);
							echo "\n<OPTION VALUE=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;$texte_option";
							$statut_old = $statut_auteur;
							$premiere_old = $premiere;
						}
						
						echo "</SELECT>";
						echo "<INPUT TYPE='submit' NAME='Ajouter' VALUE='Ajouter' CLASS='fondo'>";
					}
					echo "</FORM>";
				}
			}
			else {
				echo "<br><div align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size='2'><a href='message.php3?id_message=$id_message&forcer_dest=oui'>Ajouter un participant</a></font>";
			}
		}
		fin_cadre_enfonce();
	}
	
	else {
		$expediteur = $connect_id_auteur;
		$ze_auteurs = $expediteur;
	}
	

	//////////////////////////////////////////////////////
	// Fixer rendez-vous?
	//

	if ($statut == 'redac' OR $type == 'pb' OR $forcer_rv) {
		echo "<form action='message.php3' method='post'>";
		debut_boite_info();
		echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#$couleur_foncee' colspan=2>";
		echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif' color='#FFFFFF'><B>RENDEZ-VOUS :</B></FONT>";
		echo "</td></tr></table>";
		echo "<input type='hidden' name='id_message' value='$id_message'>";
		echo "<input type='hidden' name='changer_rv' value='$id_message'>";
		if ($rv != "oui") {
			echo "<INPUT TYPE='radio' NAME='rv' VALUE='oui' id='rv_on'>";
			echo " <label for='rv_on'>Afficher dans le calendrier</label> ";
			echo " <br><INPUT TYPE='radio' NAME='rv' VALUE='non' CHECKED id='rv_off'>";
			echo " <B><label for='rv_off'>Ne pas afficher dans le calendrier</label></B> ";
		}
		else {
			echo "<INPUT TYPE='radio' NAME='rv' VALUE='oui' CHECKED id='rv_on'>";
			echo " <b><label for='rv_on'>Afficher dans le calendrier</label></b> ";

			echo "<center><SELECT NAME='jour' SIZE=1 CLASS='fondl'>";
			afficher_jour(jour($date_heure));
			echo "</SELECT> ";
			echo "<SELECT NAME='mois' SIZE=1 CLASS='fondl'>";
			afficher_mois(mois($date_heure));
			echo "</SELECT> ";
			echo "<SELECT NAME='annee' SIZE=1 CLASS='fondl'>";
			afficher_annee(annee($date_heure));
			echo "</SELECT>\n";
			
			echo " &Agrave;&nbsp;<INPUT TYPE='text' CLASS='fondl' NAME='heures' VALUE=\"".heures($date_heure)."\" SIZE='3'>&nbsp;HEURES&nbsp;";
			echo "<INPUT TYPE='text' CLASS='fondl' NAME='minutes' VALUE=\"".minutes($date_heure)."\" SIZE='3'> ";

			echo "</center>";

			echo " <p><INPUT TYPE='radio' NAME='rv' VALUE='non' id='rv_off'>";
			echo " <label for='rv_off'>Ne pas afficher dans le calendrier</label> ";
		}

		echo "<div align='right'><INPUT TYPE='submit' NAME='Ajouter' VALUE='Ajouter' CLASS='fondo'>";

		fin_boite_info();
		echo "</form>";
	}
	else if ($rv == "oui") {
		echo "<p><center><font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='#666666'><b>RENDEZ-VOUS : <font color='red'>".majuscules(nom_jour($date_heure))." ".majuscules(affdate($date_heure))." &Agrave; ".heures($date_heure)."H".minutes($date_heure)."</font></b></font></center>";
		if ($type != "affich") echo "<div align='right'><font size=2><a href='message.php3?id_message=$id_message&forcer_rv=oui'>Modifier la date</a></font><p>";
	}


	//////////////////////////////////////////////////////
	// Le message lui-meme
	//

	echo "<div align='left'>";
	echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0>";
	echo "<TR><TD>";

	echo "<br><font face='Georgia,Garamond,Times,serif' size=3>";
	
	if ($expediteur == $connect_id_auteur AND ($statut == 'redac' OR $type == 'pb') OR ($type == 'affich' AND $connect_statut == '0minirezo')) {
		echo "\n<table align='right'><tr><td>";
		icone ("Modifier ce message", newLinkUrl("message_edit.php3?id_message=$id_message"), "messagerie-24.gif", "edit.gif");
		echo "</td></tr></table>";
	}

	echo "<p>$texte";
	echo "</font>";
	echo "</td></tr></table>";	


	if ($expediteur == $connect_id_auteur AND ($statut == 'redac' OR $type == 'pb') OR ($type == 'affich' AND $connect_statut == '0minirezo')) {

		echo "<hr noshade size=1>";

		if ($expediteur == $connect_id_auteur AND ($statut == 'redac' OR $type == 'pb') OR ($type == 'affich' AND $connect_statut == '0minirezo')) {
			echo "\n<table align='left'><tr><td>";
			icone ("Supprimer ce message", newLinkUrl("messagerie.php3?detruire_message=$id_message"), "messagerie-24.gif", "supprimer.gif");
			echo "</td></tr></table>";
		}
		if ($expediteur == $connect_id_auteur AND $statut == 'redac') {
			if ($type == 'normal' AND $total_dest < 2){
				echo "<p align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#666666'><b>Vous devez indiquer un destinataire avant d'envoyer ce message.</b></font></p>";
			}
			else {
				echo "\n<table align='right'><tr><td>";
				icone ("Envoyer ce message", newLinkUrl("message.php3?id_message=$id_message&change_statut=publie"), "messagerie-24.gif", "creer.gif");
				echo "</td></tr></table>";
			}
		}
		
	}

	echo "</td></tr></table>";
	fin_cadre_relief();
	
	//////////////////////////////////////////////////////
	// Forums
	//

	echo "<BR><BR>";

	$forum_retour = urlencode("message.php3?id_message=$id_message");


	echo "\n<div align='center'>";
		icone("Poster un message", "forum_envoi.php3?statut=perso&adresse_retour=".$forum_retour."&id_message=$id_message&titre_message=".urlencode($titre), "forum-interne-24.gif", "creer.gif");
	echo "</div>";


	echo "<P align='left'>";

	$query_forum = "SELECT * FROM spip_forum WHERE statut='perso' AND id_message='$id_message' AND id_parent=0 ORDER BY date_heure DESC LIMIT 0,20";
	$result_forum = spip_query($query_forum);
	afficher_forum($result_forum, $forum_retour);
}

fin_page();

?>