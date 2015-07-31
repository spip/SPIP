<?php

include ("ecrire/inc_version.php3");

include_ecrire("inc_filtres.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_charsets.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");


function ligne_x ($texte) {
	if (ereg("^BEGIN", $texte)) echo "<br>";
	echo $texte."<br>";
}
function ligne ($texte) {
	echo $texte."\n";
}

function filtrer_ical ($texte) {
	global $charset;
	$texte = html2unicode($texte);
	$texte = unicode2charset(charset2unicode($texte, $charset, 1), 'utf-8');
	$texte = ereg_replace("\n", " ", $texte);
	$texte = ereg_replace(",", "\,", $texte);

	return $texte;
}



if (!$charset = lire_meta('charset')) $charset = 'utf-8';
$nom_site = filtrer_ical(lire_meta("nom_site"));
$adresse_site = lire_meta("adresse_site");


if (strlen($cle)>1) {
	$query = "SELECT * FROM spip_auteurs WHERE low_sec='$cle'";
	$result = spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$id_utilisateur=$row['id_auteur'];
		$nom_utilisateur=$row['nom'];
		$nom_utilisateur = filtrer_ical(unicode2charset(charset2unicode($nom_utilisateur, $charset, 1), 'utf-8'));
	} else {
		die ("Interdit");
	}
} else {
	die ("Interdit");
}	


@header ("content-type:text/calendar");
//@header("Content-Disposition: attachment; filename=spipcal-$id_utilisateur.ics");

ligne ("BEGIN:VCALENDAR");
ligne ("CALSCALE:GREGORIAN");
ligne ("X-WR-CALNAME;VALUE=TEXT:$nom_site / $nom_utilisateur");
ligne ("X-WR-RELCALID:cal$id_utilisateur @ $adresse_site");
ligne ("VERSION:2.0");


	// rendez-vous
	$result_messages=spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$id_utilisateur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while($row=spip_fetch_array($result_messages)){
		$id_message=$row['id_message'];
		$date_heure=$row["date_heure"];
		$date_heure_fin=$row["date_fin"];
		//$titre=typo($row["titre"]);
		$titre = filtrer_ical($row["titre"]);
		$texte = filtrer_ical($row["texte"]);
		$type=$row["type"];

		if ($type == "affich") {
			$titre = "[$nom_site] $titre";
		}
		
		ligne ("BEGIN:VEVENT");
		if ($type == "normal") {
			$result_auteurs=spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE (lien.id_message='$id_message' AND lien.id_auteur=auteurs.id_auteur)");
			while($row_auteur=spip_fetch_array($result_auteurs)){
				$id_auteur=$row_auteur['id_auteur'];
				$nom_auteur=$row_auteur['nom'];
				$email = $row_auteur ['email'];
				$nom_auteur = filtrer_ical($nom_auteur);

				if ($id_auteur != $id_utilisateur) $titre = $titre." - ".$nom_auteur;
				
				if ($id_auteur == $id_utilisateur) ligne ("ORGANIZER:$nom_auteur <$email>");
				else  ligne ("ATTENDEE:$nom_auteur <$email>");
			}
		}
		ligne ("SUMMARY:".$titre);
		ligne ("DESCRIPTION:$texte");
		ligne ("UID:mess$id_message @ $adresse_site");
		ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		if ($date_heure_fin > $date_heure) ligne ("DTEND:".date ("Ymd\THis", mktime (heures($date_heure_fin),minutes($date_heure_fin),0,mois($date_heure_fin),jour($date_heure_fin),annee($date_heure_fin))));
		ligne ("CATEGORIES:$type");
		ligne("URL:$adresse_site/ecrire/message.php3?id_message=$id_message");
		
		ligne ("END:VEVENT");
		
	}

	// todo
	$result_messages=spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur='$id_utilisateur' AND lien.id_message=messages.id_message AND messages.type='pb' AND messages.rv!='oui' AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while($row=spip_fetch_array($result_messages)){
		$id_message=$row['id_message'];
		$date_heure=$row["date_heure"];
		$titre = filtrer_ical($row["titre"]);
		$texte = filtrer_ical($row["texte"]);
		$type=$row["type"];

		if ($type == "affich") {
			$titre = "[$nom_site] $titre";
		}
		
		ligne ("BEGIN:VTODO");
		ligne ("SUMMARY:".$titre);
		ligne ("DESCRIPTION:$texte");
		ligne ("UID:mess$id_message @ $adresse_site");
		ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("CATEGORIES:$type");
		ligne("URL:$adresse_site/ecrire/message.php3?id_message=$id_message");
		ligne ("END:VTODO");
		
	}



	// Articles et breves proposes
	$result_articles = spip_query("SELECT id_article, titre, date FROM spip_articles WHERE statut = 'prop'");
	while($row=spip_fetch_array($result_articles)){
		$id_article=$row['id_article'];
		$titre = filtrer_ical(_T('info_article_propose').": ".$row['titre']);
		$date_heure = $row['date'];
		ligne ("BEGIN:VTODO");
		ligne ("SUMMARY:[$nom_site] $titre ");
		ligne ("UID:article$id_article @ $adresse_site");
		ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("CATEGORIES:article");
		ligne("URL:$adresse_site/ecrire/articles.php3?id_article=$id_article");
		ligne ("END:VTODO");
	}
	$result_articles = spip_query("SELECT id_breve, titre, date_heure FROM spip_breves WHERE statut = 'prop'");
	while($row=spip_fetch_array($result_articles)){
		$id_breve=$row['id_breve'];
		$titre = filtrer_ical(_T('item_breve_proposee').": ".$row['titre']);
		$date_heure = $row['date_heure'];
		ligne ("BEGIN:VTODO");
		ligne ("SUMMARY:[$nom_site] $titre");
		ligne ("UID:breve$id_breve @ $adresse_site");
		ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("CATEGORIES:breve");
		ligne("URL:$adresse_site/ecrire/breves_voir.php3?id_breve=$id_breve");
		ligne ("END:VTODO");
	}
	



/*ligne ("BEGIN:VEVENT");
	DTSTAMP:20031121T064748Z
	SUMMARY:L.A. Clippers @ Kings Preseason \nW 101-82\n 
	LOCATION:
	UID:4FF899AA-1BE0-11D8-8939-000A959A2EA8-RID
	DTSTART;TZID=US/Pacific:20031007T190000
	DURATION:PT3H
ligne ("END:VEVENT");
*/


ligne ("END:VCALENDAR");



?>