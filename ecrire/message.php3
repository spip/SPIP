<?php

include ("inc.php3");
include_ecrire ("inc_mots.php3");



$query = "SELECT COUNT(*) FROM spip_auteurs_messages WHERE id_auteur=$connect_id_auteur AND id_message=$id_message";
$result = spip_query($query);
list($n) = spip_fetch_array($result);
if (!$n) {

	$query_message = "SELECT * FROM spip_messages WHERE id_message=$id_message";
	$result_message = spip_query($query_message);
	while($row = spip_fetch_array($result_message)) {
		$type = $row['type'];
	}
	if ($type != "affich"){
		debut_page(_T('info_acces_refuse'));
		debut_gauche();
		debut_droite();
		echo "<b>"._T('avis_non_acces_message')."</b><p>";
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
	my_sel("01", _T('date_mois_1'), $mois);
	my_sel("02", _T('date_mois_2'), $mois);
	my_sel("03", _T('date_mois_3'), $mois);
	my_sel("04", _T('date_mois_4'), $mois);
	my_sel("05", _T('date_mois_5'), $mois);
	my_sel("06", _T('date_mois_6'), $mois);
	my_sel("07", _T('date_mois_7'), $mois);
	my_sel("08", _T('date_mois_8'), $mois);
	my_sel("09", _T('date_mois_9'), $mois);
	my_sel("10", _T('date_mois_10'), $mois);
	my_sel("11", _T('date_mois_11'), $mois);
	my_sel("12", _T('date_mois_12'), $mois);
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

while($row = spip_fetch_array($result_message)) {
	$id_message = $row['id_message'];
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
		icone (_T('icone_arret_discussion'), "messagerie.php3?id_message=$id_message&supp_dest=$connect_id_auteur", "messagerie-24.gif", "supprimer.gif");
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
		$le_type = _T('info_pense_bete').aide ("messpense");
		$la_couleur = "blue";	
	}
	else if ($type == 'affich') {
		$le_type = _T('info_annonce');
		$la_couleur = "red";	
	}

	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='$la_couleur'><b>$le_type</b></font><br>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=5><b>$titre</b></font>";
	if ($statut == 'redac') {
		echo "<br><font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='red'><b>"._T('info_redaction_en_cours')."</b></font>";
	}
	else if ($rv != 'oui') {
		echo "<br><font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='#666666'><b>".nom_jour($date_heure).' '.affdate_heure($date_heure)."</b></font>";
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

		if ($row = spip_fetch_array($result)) {

			echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC'>";
			echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'><B>"._T('info_auteur_message')."</B></FONT>";
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
			echo "&nbsp;".bonhomme_statut($row)."&nbsp;";
			echo ' '.$nom_auteur;
			echo "</font></td></tr>";
				
			echo "</table>";

		}


		//
		// Recherche d'auteur
		//

		if ($cherche_auteur) {
			echo "<P ALIGN='left'>";
			$query = "SELECT id_auteur, nom FROM spip_auteurs WHERE messagerie<>'non' AND id_auteur<>'$connect_id_auteur' AND pass<>'' AND login<>''";
			$result = spip_query($query);
			unset($table_auteurs);
			unset($table_ids);
			while ($row = spip_fetch_array($result)) {
				$table_auteurs[] = $row['nom'];
				$table_ids[] = $row['id_auteur'];
			}
			$resultat = mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
			debut_boite_info();
			if (!$resultat) {
				echo _T('info_recherche_auteur_zero', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
			}
			else if (count($resultat) == 1) {
				$ajout_auteur = 'oui';
				list(, $nouv_auteur) = each($resultat);
				echo "<B>"._T('info_ajout_participant')."</B><BR>";
				$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$nouv_auteur";
				$result = spip_query($query);
				echo "<UL>";
				while ($row = spip_fetch_array($result)) {
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
					echo "<B>"._T('info_recherche_auteur_ok', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
					$query = "SELECT * FROM spip_auteurs WHERE id_auteur IN ($les_auteurs) ORDER BY nom";
					$result = spip_query($query);
					echo "<UL>";
					while ($row = spip_fetch_array($result)) {
						$id_auteur = $row['id_auteur'];
						$nom_auteur = $row['nom'];
						$email_auteur = $row['email'];
						$bio_auteur = $row['bio'];
			
						echo "<LI><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B><FONT SIZE=3>$nom_auteur</FONT></B>";
					
						if ($email_auteur) echo " ($email_auteur)";
						echo " | <A HREF=\"message.php3?id_message=$id_message&ajout_auteur=oui&nouv_auteur=$id_auteur\">"._T('lien_ajout_destinataire')."</A>";
					
						if (trim($bio_auteur)) {
							echo "<BR><FONT SIZE=1>".propre(couper($bio_auteur, 100))."</FONT>\n";
						}
						echo "</FONT><p>\n";
					}
					echo "</UL>";
				}
			}
			else {
				echo "<B>"._T('info_recherche_auteur_a_affiner', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
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

		$total_dest = spip_num_rows($result_auteurs);

		if ($total_dest > 0) {
			echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC' colspan=2>";
			echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'><B>"._T('info_nombre_partcipants')."</B></FONT>";
			echo "</td></tr>";

			$ifond = 0;
			while($row = spip_fetch_array($result_auteurs)) {
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
				echo "&nbsp;".bonhomme_statut($row)."&nbsp;";
				echo " $nom_auteur";
				echo "</font></td>";
				
				echo "<td background='' bgcolor='$couleur' align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
				if ($id_auteur != $connect_id_auteur) {
					echo "[<a href='message.php3?id_message=$id_message&supp_dest=$id_auteur'>"._T('lien_retrait_particpant')."</a>]";
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
				$query_ajout_auteurs .= " messagerie<>'non' AND statut IN ('0minirezo', '1comite') ORDER BY statut, nom";
				$result_ajout_auteurs = spip_query($query_ajout_auteurs);

				if (spip_num_rows($result_ajout_auteurs) > 0) {

					echo "<FORM ACTION='message.php3' METHOD='post'>";
					echo "<DIV align=left><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B>AJOUTER UN PARTICIPANT : &nbsp; </B></FONT>\n";
					echo "<INPUT TYPE='Hidden' NAME='id_message' VALUE=\"$id_message\">";

					if (spip_num_rows($result_ajout_auteurs) > 50 AND $flag_mots_ressemblants) {
						echo "<INPUT TYPE='text' NAME='cherche_auteur' CLASS='fondl' VALUE='' SIZE='20'>";
						echo "<INPUT TYPE='submit' NAME='Chercher' VALUE='"._T('bouton_chercher')."' CLASS='fondo'>";
					}
					else {
						echo "<SELECT NAME='nouv_auteur' SIZE='1' STYLE='WIDTH=150' CLASS='fondl'>";
						$group = false;
						$group2 = false;

						while($row=spip_fetch_array($result_ajout_auteurs)) {
							$id_auteur = $row['id_auteur'];
							$nom = $row['nom'];
							$email = $row['email'];
							$statut_auteur = $row['statut'];

							$statut_auteur=ereg_replace("0minirezo", _T('info_statut_administrateur'), $statut_auteur);
							$statut_auteur=ereg_replace("1comite", _T('info_statut_redacteur'), $statut_auteur);
							$statut_auteur=ereg_replace("2redac", _T('info_statut_redacteur'), $statut_auteur);
							$statut_auteur=ereg_replace("5poubelle", _T('info_statut_efface'), $statut_auteur);

							$premiere = strtoupper(substr(trim($nom), 0, 1));

							if ($connect_statut != '0minirezo') {
								if ($p = strpos($email, '@')) $email = substr($email, 0, $p).'@...';
							}

							if ($statut_auteur != $statut_old) {
								echo "\n<OPTION VALUE=\"x\">";
								echo "\n<OPTION VALUE=\"x\"> $statut_auteur".'s';
							}
						
							if ($premiere != $premiere_old AND ($statut_auteur != _T('info_administrateur') OR !$premiere_old)) {
								echo "\n<OPTION VALUE=\"x\">";
							}
				
							$texte_option = couper("$nom ($email) ", 40);
							echo "\n<OPTION VALUE=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;$texte_option";
							$statut_old = $statut_auteur;
							$premiere_old = $premiere;
						}
						
						echo "</SELECT>";
						echo "<INPUT TYPE='submit' NAME='Ajouter' VALUE='"._T('bouton_ajouter')."' CLASS='fondo'>";
					}
					echo "</div></FORM>";
				}
			}
			else {
				echo "<br><div align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size='2'><a href='message.php3?id_message=$id_message&forcer_dest=oui'>"._T('lien_ajouter_participant')."</a></font></div>";
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
		echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif' color='#FFFFFF'><B>"._T('titre_rendez_vous')."</B></FONT>";
		echo "</td></tr></table>";
		echo "<input type='hidden' name='id_message' value='$id_message'>";
		echo "<input type='hidden' name='changer_rv' value='$id_message'>";
		if ($rv != "oui") {
			echo "<INPUT TYPE='radio' NAME='rv' VALUE='oui' id='rv_on'>";
			echo " <label for='rv_on'>"._T('item_afficher_calendrier')."</label> ";
			echo " <br><INPUT TYPE='radio' NAME='rv' VALUE='non' CHECKED id='rv_off'>";
			echo " <B><label for='rv_off'>"._T('item_non_afficher_calendrier')."</label></B> ";
		}
		else {
			echo "<INPUT TYPE='radio' NAME='rv' VALUE='oui' CHECKED id='rv_on'>";
			echo " <b><label for='rv_on'>"._T('item_afficher_calendrier')."</label></b> ";

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
			echo " <label for='rv_off'>"._T('item_non_afficher_calendrier')."</label> ";
		}

		echo "<div align='right'><INPUT TYPE='submit' NAME='Ajouter' VALUE='"._T('bouton_ajouter')."' CLASS='fondo'></div>";

		fin_boite_info();
		echo "</form>";
	}
	else if ($rv == "oui") {
		echo "<p><center><font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='#666666'><b>"._L('RENDEZ-VOUS :')." <font color='red'>".majuscules(nom_jour($date_heure))." ".majuscules(affdate_heure($date_heure))."</font></b></font></center>";
		if ($type != "affich") echo "<div align='right'><font size=2><a href='message.php3?id_message=$id_message&forcer_rv=oui'>"._T('lien_modifer_date')."</a></font></div><p>";
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
		icone (_T('icone_modifier_message'), newLinkUrl("message_edit.php3?id_message=$id_message"), "messagerie-24.gif", "edit.gif");
		echo "</td></tr></table>";
	}

	echo "<p>$texte";
	echo "</font>";
	echo "</td></tr></table>";	


	if ($expediteur == $connect_id_auteur AND ($statut == 'redac' OR $type == 'pb') OR ($type == 'affich' AND $connect_statut == '0minirezo')) {

		echo "<hr noshade size=1>";

		if ($expediteur == $connect_id_auteur AND ($statut == 'redac' OR $type == 'pb') OR ($type == 'affich' AND $connect_statut == '0minirezo')) {
			echo "\n<table align='left'><tr><td>";
			icone (_T('icone_supprimer_message'), newLinkUrl("messagerie.php3?detruire_message=$id_message"), "messagerie-24.gif", "supprimer.gif");
			echo "</td></tr></table>";
		}
		if ($expediteur == $connect_id_auteur AND $statut == 'redac') {
			if ($type == 'normal' AND $total_dest < 2){
				echo "<p align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#666666'><b>"._T('avis_destinataire_obligatoire')."</b></font></p>";
			}
			else {
				echo "\n<table align='right'><tr><td>";
				icone (_T('icone_envoyer_message'), newLinkUrl("message.php3?id_message=$id_message&change_statut=publie"), "messagerie-24.gif", "creer.gif");
				echo "</td></tr></table>";
			}
		}
		
	}

	echo "</td></tr></table></div>";
	fin_cadre_relief();
	
	//////////////////////////////////////////////////////
	// Forums
	//

	echo "<BR><BR>";

	$forum_retour = urlencode("message.php3?id_message=$id_message");


	echo "\n<div align='center'>";
		icone(_T('icone_poster_message'), "forum_envoi.php3?statut=perso&adresse_retour=".$forum_retour."&id_message=$id_message&titre_message=".urlencode($titre), "forum-interne-24.gif", "creer.gif");
	echo "</div>";


	echo "<P align='left'>";

	$query_forum = "SELECT * FROM spip_forum WHERE statut='perso' AND id_message='$id_message' AND id_parent=0 ORDER BY date_heure DESC LIMIT 0,20";
	$result_forum = spip_query($query_forum);
	afficher_forum($result_forum, $forum_retour);
}

fin_page();

?>
