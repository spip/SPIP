<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_AGENDA")) return;
define("_ECRIRE_INC_AGENDA", "1");

// pour low_sec (iCal)
include_ecrire("inc_acces.php3");


//
// Afficher un agenda (un mois) sous forme de petit tableau
//

function agenda ($mois, $annee, $jour_ved, $mois_ved, $annee_ved, $semaine = false) {
	global $couleur_foncee, $couleur_claire;
	global $connect_id_auteur;

	$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];


	$date = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date);
	$annee = annee($date);


	// rendez-vous personnels dans le mois
	$result_messages=spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure >='$annee-$mois-1' AND date_heure < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH) AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while($row=spip_fetch_array($result_messages)){
		$date_heure=$row["date_heure"];
		$lejour=journum($row['date_heure']);
		$les_rv[$lejour] ++;
	}

	
	$nom = mktime(1,1,1,$mois,1,$annee);
	$jour_semaine = date("w",$nom);
	$nom_mois = nom_mois('2000-'.sprintf("%02d", $mois).'-01');
	
	if ($semaine) {
		$jour_valide = mktime(1,1,1,$mois_ved,$jour_ved,$annee_ved);
		$jour_semaine_valide = date("w",$jour_valide);
		$debut = mktime(1,1,1,$mois_ved,$jour_ved-$jour_semaine_valide+1,$annee_ved);
		$fin = mktime(1,1,1,$mois_ved,$jour_ved-$jour_semaine_valide+7,$annee_ved);
	}
	
	echo "<div align='center' style='padding: 5px;'><b class='verdana1'><a href='calendrier.php3?mois=$mois&&annee=$annee' style='color: black;'>".affdate_mois_annee("$annee-$mois-1")."</a></b></div>";
	
	echo "<table width='100%' cellspacing='1' cellpadding='2'>";

	echo "<tr>";
	for ($i=1;$i<$jour_semaine;$i++){
		echo "<td></td>";
	}

	for ($j=1; $j<32; $j++) {
		$jour_j = sprintf("%02d", $j);
		$nom = mktime(1,1,1,$mois,$jour_j,$annee);
		$jour_semaine = date("w",$nom);
		
		if (checkdate($mois,$j,$annee)){

			if ($j == $jour_ved AND $mois == $mois_ved AND $annee == $annee_ved) {
				echo "<td class='arial2' style='background-color: white; border: 1px solid $couleur_foncee; text-align: center; -moz-border-radius: 5px;'>";
				echo "<a href='calendrier_semaine.php3?jour=$j&mois=$mois&annee=$annee' style='color: black'><b>$j</b></a>";
				echo "</td>";
			} else if ($semaine AND $nom >= $debut AND $nom <= $fin) {
				echo "<td class='arial2' style='background-color: white; text-align: center;'>";
				echo "<a href='calendrier_semaine.php3?jour=$j&mois=$mois&annee=$annee' style='color: black'><b>$j</b></a>";
				echo "</td>";
			} else {
				if ($j == $jour_today AND $mois == $mois_today AND $annee == $annee_today) {
					$couleur_fond = $couleur_foncee;
					$couleur = "white";
				}
				else {
					if ($jour_semaine == 0) {
						$couleur_fond = $couleur_claire;
						$couleur = "#aaaaaa";
					} else {
						$couleur_fond = "#eeeeee";
						$couleur = "#aaaaaa";
					}
					if ($les_rv[$j] > 0) {
						$couleur = "black";
					}
				}
				echo "<td class='arial2' style='background-color: $couleur_fond; text-align: center; -moz-border-radius: 5px;'>";
				if ($semaine) echo "<a href='calendrier_semaine.php3?jour=$j&mois=$mois&annee=$annee' style='color: $couleur;'>$j</a>";
				else echo "<a href='calendrier_jour.php3?jour=$j&mois=$mois&annee=$annee' style='color: $couleur;'>$j</a>";
				echo "</td>";
			}			
			
			if ($jour_semaine==0) echo "</tr>\n<tr>";

		}	
	
	}
	echo "</tr>\n";
	echo "</table>";

}



function calendrier_jour($jour,$mois,$annee,$large = "large", $le_message = 0) {
	global $spip_lang_rtl, $spip_lang_right, $spip_lang_left;
	global $connect_id_auteur, $connect_statut;
	global $couleur_claire;


	$date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
	$jour = journum($date);
	$mois = mois($date);
	$annee = annee($date);


	if ($large == "large") {
		$largeur = 300;
		$modif_decalage = 40;
		$debut_gauche = 40;
	} else if ($large == "col") {
		$largeur = 120;
		$modif_decalage = 15;
		$debut_gauche = 20;
	} else {
		$largeur = 80;
		$modif_decalage = 5;
		$debut_gauche = 5;
	}
	
	$bgcolor = "white";
	
	if ($large == "etroit") {
		$bgcolor = "#eeeeee";
		
		$today=getdate(time());
		$jour_today = $today["mday"];
		$mois_today = $today["mon"];
		$annee_today = $today["year"];
		
		if ($jour == $jour_today AND $mois == $mois_today AND $annee == $annee_today) $bgcolor = "white";
	}
	
	$nom = mktime(1,1,1,$mois,$jour,$annee);
	$jour_semaine = date("w",$nom);
	if ($jour_semaine == 0) $bgcolor = $couleur_claire;

	if ($large == "col" ) echo "<div align='center' style='padding: 5px;'><b class='verdana1'><a href='calendrier_jour.php3?jour=$jour&mois=$mois&annee=$annee' style='color:black;'>".affdate_jourcourt("$annee-$mois-$jour")."</a></b></div>";
	else if ($large == "large") echo "<div align='center' style='padding: 5px;'><b class='verdana1'>&nbsp;</b></div>";
	
	if ($large != "etroit") echo "<div style='background-color: $bgcolor; border-left: 1px solid #aaaaaa; border-right: 1px solid #aaaaaa; border-bottom: 1px solid #aaaaaa;'>"; // bordure
	else echo "<div style='background-color: $bgcolor;'>"; // bordure

	echo "<div style='position: relative; width: 100%; height: 450px; background: url(img_pack/fond-calendrier.gif);'>";
	
	echo "<div style='position: absolute; $spip_lang_left: 2px; top: 2px; color: #666666;' class='arial0'><b class='arial0'>0:00<br />7:00</b></div>";
	for ($i = 7; $i < 20; $i++) {
		echo "<div style='position: absolute; $spip_lang_left: 2px; top: ".(($i-6)*30+2)."px; color: #666666;' class='arial0'><b class='arial0'>$i:00</b></div>";
	}
	echo "<div style='position: absolute; $spip_lang_left: 2px; top: 422px; color: #666666;' class='arial0'><b class='arial0'>20:00<br />23:59</b></div>";


	if ($large == "large") {
		// articles du jour
		$query="SELECT * FROM spip_articles WHERE statut='publie' AND date >='$annee-$mois-$jour' AND date < DATE_ADD('$annee-$mois-$jour', INTERVAL 1 DAY) ORDER BY date";
		$result=spip_query($query);
		while($row=spip_fetch_array($result)){
			$id_article=$row['id_article'];
			$titre=typo($row['titre']);
			$lejour=journum($row['date']);
			$lemois = mois($row['date']);		
			$les_articles.="<div><a href='articles.php3?id_article=$id_article' class='arial1'><img src='img_pack/puce-verte.gif' width='7' height='7' border='0'> $titre</a></div>";
		}
	
		// breves du jour
		$query="SELECT * FROM spip_breves WHERE statut='publie' AND date_heure >='$annee-$mois-$jour' AND date_heure < DATE_ADD('$annee-$mois-$jour', INTERVAL 1 DAY) ORDER BY date_heure";
		$result=spip_query($query);
		while($row=spip_fetch_array($result)){
			$id_breve=$row['id_breve'];
			$titre=typo($row['titre']);
			$lejour=journum($row['date_heure']);
			$lemois = mois($row['date_heure']);		
			$les_breves.="<div><a href='breves_voir.php3?id_breve=$id_breve' class='arial1'><img src='img_pack/puce-blanche.gif' width='7' height='7' border='0'> $titre</a></div>";
		}
		if ($les_articles OR $les_breves) {
			if ($les_articles) $les_articles = "<div><b class='verdana1'>"._T('info_articles')."</b></div>".$les_articles;
			if ($les_breves) $les_breves = "<div><b class='verdana1'>"._T('info_breves_02')."</b></div>".$les_breves;
			echo "<div style='position: absolute; $spip_lang_left: 355px; top: 32px; width: 140px;'>";
			echo $les_articles;
			echo $les_breves;
			echo "</div>";
		}
	}

	// rendez-vous personnels
	$result_messages=spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure >='$annee-$mois-$jour' AND messages.date_heure <= '$annee-$mois-$jour 23:59:59' AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while($row=spip_fetch_array($result_messages)){
		$id_message=$row['id_message'];
		$date_heure=$row["date_heure"];
		$date_fin=$row["date_fin"];
		$titre=propre($row["titre"]);
		$texte = propre($row["texte"]);
		$type=$row["type"];
		$lejour=journum($row['date_heure']);

		if ($type=="normal") {
			$la_couleur = "#0A9C60";
			$couleur_fond = "#BDF0DB";
		}
		elseif ($type=="pb") {
			$la_couleur = "#0000ff";
			$couleur_fond = "#ccccff";
		}
		elseif ($type=="affich") {
			$la_couleur = "#ccaa00";
			$couleur_fond = "#ffffee";
		}
		else {
			$la_couleur="black";
			$couleur_fond="#aaaaaa";
		}

		$heure_debut = heures($date_heure);
		$minutes_debut = minutes($date_heure);

		// En attendant gestion heure de fin...
		$heure_fin = heures($date_fin);
		$minutes_fin = minutes($date_fin);

		if ($heure_debut < 6) {
			$heure_debut = 6;
			$minutes_debut = 0;	
		}
		if ($heure_fin < 7) {
			$heure_fin = 7;
			$minutes_fin = 00;
		}
		
		if ($heure_debut > 20) {
			$heure_debut = 20;
			$minutes_debut = 0;
		}
		if ($heure_fin > 20) {
			$heure_fin = 21;
			$minutes_fin = 00;
		}
		
		$haut = floor((($heure_debut - 6)*60 + $minutes_debut)/2);
		$bas = floor((($heure_fin - 6)*60 + $minutes_fin)/2);
		
		$hauteur = ($bas-$haut) - 7;
		if ($hauteur < 23) $hauteur = 23;
		
		if ($bas_prec > $haut) $decalage = $decalage + $modif_decalage;
		else $decalage = $debut_gauche;
		
		if ($bas > $bas_prec) $bas_prec = $bas;		
		
		if ($le_message == $id_message)	$couleur_cadre = "red";
		else $couleur_cadre = "#999999";
		
		
		echo "<div style='position: absolute; $spip_lang_left: ".$decalage."px; top: ".$haut."px; height: ".$hauteur."px; width: ".$largeur."px;  border: 1px solid $la_couleur; padding: 3px; background-color: $couleur_fond; -moz-opacity: 0.7; -moz-border-radius: 5px; filter: alpha(opacity=70);'>";
		echo "</div>";
		echo "<div style='position: absolute; overflow: hidden; $spip_lang_left: ".$decalage."px; top: ".$haut."px; height: ".$hauteur."px; width: ".$largeur."px;  border: 1px solid $couleur_cadre; padding: 3px; -moz-border-radius: 5px;'>";
		echo "<div><b><a href='message.php3?id_message=$id_message' class='verdana1' style='color: black;'>$titre</a></b></div>";
		
		if ($type == "normal") {
			$result_auteurs=spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE (lien.id_message='$id_message' AND (auteurs.id_auteur!='$connect_id_auteur' AND lien.id_auteur=auteurs.id_auteur))");
			while($row_auteur=spip_fetch_array($result_auteurs)){
				$id_auteur=$row_auteur['id_auteur'];
				$nom_auteur=$row_auteur['nom'];
				$les_auteurs[$id_message][] = $nom_auteur;
			}
			if (count($les_auteurs[$id_message]) > 0) {
				echo "<div><font class='verdana1'>".join($les_auteurs[$id_message],", ")."</font></div>";
			}
		}
		
		if ($large) echo "<div><a href='message.php3?id_message=$id_message' class='arial1' style='color: #333333; text-decoration: none;'>$texte</a></div>";
		echo "</div>";
	}

	echo "</div>";
	echo "</div>";
}

function liste_rv($query, $type) {
	global $spip_lang_rtl, $spip_lang_left;
	
	if ($type == annonces) {
		$titre = _T('info_annonces_generales');
		$couleur_titre = "yellow";
		$couleur_texte = "black";
		$couleur_fond = "#ffffee";
	}
	else if ($type == pb) {
		$titre = _T('infos_vos_pense_bete');
		$couleur_titre = "blue";
		$couleur_fond = "#eeeeff";
		$couleur_texte = "white";
	}
	else if ($type == rv) {
		$titre = _T('info_vos_rendez_vous');
		$couleur_titre = "#666666";
		$couleur_fond = "#eeeeee";
		$couleur_texte = "white";
	}

	$result = spip_query($query);
	if (spip_num_rows($result) > 0){
		echo "<div>&nbsp;</div><div style='border: 1px solid #999999; background-color: $couleur_fond; -moz-border-radius: 5px;'>";
		echo "<div style='background-color: $couleur_titre; padding: 3px; color: $couleur_texte;'>";
		echo "<b class='verdana1'>$titre</b>";
		echo "</div>";
		echo "<div style='padding: 3px;'>";
		while ($row = spip_fetch_object($result)) {
			if (ereg("^=([^[:space:]]+)$",$row->texte,$match))
				$url = $match[1];
			else
				$url = "message.php3?id_message=".$row->id_message;
				$type=$row->type;
				$rv = $row->rv;
				$date = $row->date_heure;

				if ($type=="normal") $bouton = "m_envoi";
				elseif ($type=="pb") $bouton = "m_envoi_bleu";
				elseif ($type=="affich") $bouton = "m_envoi_jaune";
				else $bouton = "m_envoi";
			
			$titre = typo($row->titre);

			echo "<div style='margin: 5px; padding-$spip_lang_left: 20px; background: url(img_pack/$bouton$spip_lang_rtl.gif) $spip_lang_left center no-repeat;'>";
			if ($rv == "oui") {
				echo "<b class='arial0'>".affdate_jourcourt($date)."</b><br />";
			}
			echo "<b><a href='$url' class='arial1'>$titre</a></b>";
			echo "</div>\n";
		}
		echo "</div>";
		
		echo "</div>";
	}
}

function afficher_taches () {
	global $connect_id_auteur, $options;
	$query = "SELECT * FROM spip_messages WHERE type = 'affich' AND rv != 'oui' AND statut = 'publie' ORDER BY date_heure DESC";
	liste_rv($query, "annonces");

	$query = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'";
	liste_rv($query, "pb");

	$query = "SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) AND messages.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH) AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure";
	liste_rv($query, "rv");
}

// afficher l'encadre "lien iCal"
function afficher_ical($id) {
	echo debut_cadre_enfonce();
	echo "<div class='verdana1'>"._T("calendrier_synchro")."</div>";
	icone_horizontale (_T("calendrier_synchro_lien"), "../spip_cal.php3?id=$id&cle=".afficher_low_sec($id,'ical'), "calendrier-24.gif");
	echo fin_cadre_enfonce();
}

?>
