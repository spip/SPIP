<?php

include ("ecrire/inc_version.php3");

include_ecrire("inc_filtres.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_charsets.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");

// pour low_sec (iCal)
include_ecrire("inc_acces.php3");

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
	//$texte = ereg_replace("\r", " ", $texte);
	$texte = ereg_replace(",", "\,", $texte);

	return $texte;
}


if (!$charset = lire_meta('charset')) $charset = 'utf-8';
$nom_site = filtrer_ical(lire_meta("nom_site"));
$adresse_site = lire_meta("adresse_site");

// securite
unset($id_utilisateur);
$id_auteur = intval($id);

// verifier la cle
if (verifier_low_sec($id_auteur, $cle, 'ical')) {
	$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur";
	$result = spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$id_utilisateur=$row['id_auteur'];
		$nom_utilisateur=$row['nom'];
		$statut_utilisateur=$row['statut'];
		$nom_utilisateur = filtrer_ical(unicode2charset(charset2unicode($nom_utilisateur, $charset, 1), 'utf-8'));
	}
}


//@header ("content-type:text/calendar");


if ($type == "public") {
	ligne ("BEGIN:VCALENDAR");
	ligne ("CALSCALE:GREGORIAN");
	ligne ("X-WR-CALNAME;VALUE=TEXT:$nom_site");
	ligne ("X-WR-RELCALID:$adresse_site");

	// Articles et breves publies
	$result_articles = spip_query("SELECT id_article, titre, date FROM spip_articles WHERE statut = 'publie'");
	while($row=spip_fetch_array($result_articles)){
		$id_article=$row['id_article'];
		$titre = filtrer_ical(_T('info_article_publie').": ".$row['titre']);
		$date_heure = $row['date'];
		ligne ("BEGIN:VEVENT");
		ligne ("SUMMARY:[$nom_site] $titre ");
		ligne ("UID:article$id_article @ $adresse_site");
				ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
				ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
				ligne ("DTEND:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure)+60,0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("CATEGORIES:".filtrer_ical(_T('titre_breve_publiee')));
		ligne("URL:$adresse_site/spip_redirect.php3?id_article=$id_article");
		ligne("STATUS:CONFIRMED");
		ligne ("END:VEVENT");
	}
	$result_articles = spip_query("SELECT id_breve, titre, date_heure FROM spip_breves WHERE statut = 'publie'");
	while($row=spip_fetch_array($result_articles)){
		$id_breve=$row['id_breve'];
		$titre = filtrer_ical(_T('titre_breve_publiee').": ".$row['titre']);
		$date_heure = $row['date_heure'];
		ligne ("BEGIN:VEVENT");
		ligne ("SUMMARY:[$nom_site] $titre");
		ligne ("UID:breve$id_breve @ $adresse_site");
				ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
				ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
				ligne ("DTEND:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure)+60,0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("CATEGORIES:".filtrer_ical(_T('titre_breve_publiee')));
		ligne("URL:$adresse_site/spip_redirect.php3?id_breve=$id_breve");
		ligne("STATUS:CONFIRMED");
		ligne ("END:VEVENT");
	}
	ligne ("END:VCALENDAR");
}




if ($id_utilisateur) {
	if (!$type) {
		ligne ("BEGIN:VCALENDAR");
		ligne ("CALSCALE:GREGORIAN");
		ligne ("X-WR-CALNAME;VALUE=TEXT:$nom_site / $nom_utilisateur");
		ligne ("X-WR-RELCALID:cal$id_utilisateur @ $adresse_site");
	
	
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
	
			if ($type == 'normal') {
				$le_type = filtrer_ical(_T('info_message_2'));
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
			else if ($type == 'pb') {
				$le_type = filtrer_ical(_T('info_pense_bete'));
			}
			else if ($type == 'affich') {
				$le_type = filtrer_ical(_T('info_annonce'));
				$titre = "[$nom_site] $titre";
			}
	
			ligne ("BEGIN:VEVENT");
			ligne ("SUMMARY:".$titre);
			ligne ("DESCRIPTION:$texte");
			ligne ("UID:mess$id_message @ $adresse_site");
			ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
			ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
			if ($date_heure_fin > $date_heure) ligne ("DTEND:".date ("Ymd\THis", mktime (heures($date_heure_fin),minutes($date_heure_fin),0,mois($date_heure_fin),jour($date_heure_fin),annee($date_heure_fin))));
			ligne ("CATEGORIES:$le_type");
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
	
			if ($type == 'normal') {
				$le_type = filtrer_ical(_T('info_message_2'));
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
			else if ($type == 'pb') {
				$le_type = filtrer_ical(_T('info_pense_bete'));
			}
			else if ($type == 'affich') {
				$le_type = filtrer_ical(_T('info_annonce'));
				$titre = "[$nom_site] $titre";
			}
			
			ligne ("BEGIN:VTODO");
			ligne ("SUMMARY:".$titre);
			ligne ("DESCRIPTION:$texte");
			ligne ("UID:mess$id_message @ $adresse_site");
			ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
			ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
			ligne ("CATEGORIES:$le_type");
			ligne("URL:$adresse_site/ecrire/message.php3?id_message=$id_message");
			ligne ("END:VTODO");
			
		}
	
		// Articles et breves proposes
		$result_articles = spip_query("SELECT id_article, titre, date FROM spip_articles WHERE statut = 'prop'");
		while($row=spip_fetch_array($result_articles)){
			$id_article=$row['id_article'];
			$titre = filtrer_ical(_T('info_article_propose').": ".$row['titre']);
			$date_heure = $row['date'];
			$nb_articles ++;
			ligne ("BEGIN:VEVENT");
			ligne ("SUMMARY:[$nom_site] $titre ");
			ligne ("UID:article$id_article @ $adresse_site");
			ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
			ligne ("DTSTART;VALUE=DATE:".date ("Ymd", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
			ligne ("CATEGORIES:".filtrer_ical(_T('info_article_propose')));
			ligne("URL:$adresse_site/ecrire/articles.php3?id_article=$id_article");
			ligne ("END:VEVENT");
		}
		$result_articles = spip_query("SELECT id_breve, titre, date_heure FROM spip_breves WHERE statut = 'prop'");
		while($row=spip_fetch_array($result_articles)){
			$id_breve=$row['id_breve'];
			$titre = filtrer_ical(_T('item_breve_proposee').": ".$row['titre']);
			$date_heure = $row['date_heure'];
			$nb_breves++;
			ligne ("BEGIN:VEVENT");
			ligne ("SUMMARY:[$nom_site] $titre ");
			ligne ("UID:breve$id_breve @ $adresse_site");
			ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
			ligne ("DTSTART;VALUE=DATE:".date ("Ymd", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
			ligne ("CATEGORIES:".filtrer_ical(_T('item_breve_proposee')));
			ligne("URL:$adresse_site/ecrire/breves_voir.php3?id_breve=$id_breve");
			ligne ("END:VEVENT");

		}

		if ($nb_articles + $nb_breves > 0) {
			if ($nb_articles > 0) $titre_prop[] = filtrer_ical(_T('info_articles_proposes')).": ".$nb_articles;
			if ($nb_breves > 0) $titre_prop[] = filtrer_ical(_T('info_breves_valider')).": ".$nb_breves;
			$titre = join($titre_prop," / ");
			ligne ("BEGIN:VTODO");
			ligne ("SUMMARY:[$nom_site] $titre");
			ligne ("UID:prop @ $adresse_site");
			
			$today=getdate(time());
			$jour = $today["mday"];
			$mois=$today["mon"];
			$annee=$today["year"];
			ligne ("DTSTAMP:".date ("Ymd\THis", mktime (12,0,0,$mois,$jour,$annee)));
			ligne ("DTSTART:".date ("Ymd\THis", mktime (12,0,0,$mois,$jour,$annee)));
			ligne ("CATEGORIES:".filtrer_ical(_T('icone_a_suivre')));
			ligne("URL:$adresse_site/ecrire/");
			ligne ("END:VTODO");
		}


		// Nouveaux messages
		
		$result_messages = spip_query("SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$id_utilisateur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
		while($row=spip_fetch_array($result_messages)){
			$id_message=$row['id_message'];
			$date_heure=$row["date_heure"];
			$titre = filtrer_ical($row["titre"]);
			$texte = filtrer_ical($row["texte"]);
			$type=$row["type"];
	
			if ($type == 'normal') {
				$le_type = filtrer_ical(_T('info_message_2'));
				$result_auteurs=spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE (lien.id_message='$id_message' AND lien.id_auteur=auteurs.id_auteur)");
				while($row_auteur=spip_fetch_array($result_auteurs)){
					$id_auteur=$row_auteur['id_auteur'];
					$nom_auteur=$row_auteur['nom'];
					$email = $row_auteur ['email'];
					$nom_auteur = filtrer_ical($nom_auteur);
	
					if ($id_auteur != $id_utilisateur) $titre = $nom_auteur." - ".$titre;
					
					if ($id_auteur == $id_utilisateur) ligne ("ORGANIZER:$nom_auteur <$email>");
					else  ligne ("ATTENDEE:$nom_auteur <$email>");
				}
			}
			else if ($type == 'pb') {
				$le_type = filtrer_ical(_T('info_pense_bete'));
			}
			else if ($type == 'affich') {
				$le_type = filtrer_ical(_T('info_annonce'));
				$titre = "[$nom_site] $titre";
			}
			
			ligne ("BEGIN:VTODO");
			ligne ("SUMMARY:".$titre);
			ligne ("DESCRIPTION:$texte");
			ligne ("UID:nouv_mess$id_message @ $adresse_site");
			ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
			ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
			ligne ("CATEGORIES:$le_type");
			ligne("URL:$adresse_site/ecrire/message.php3?id_message=$id_message");
			ligne ("END:VTODO");
			
		}
		
		// Messages de forum a valider
		if ($statut_utilisateur == "0minirezo") {

			$query_forum = "SELECT * FROM spip_forum WHERE statut = 'prop'";
			$result_forum = spip_query($query_forum);
	
			while($row=spip_fetch_array($result_forum)){
				$nb_forum ++;
			
				$id_forum=$row['id_forum'];
				$date_heure = $row['date_heure'];
				$titre = filtrer_ical($row['titre']);
				$texte = filtrer_ical($row['texte']);
				$auteur = filtrer_ical($row['auteur']);
				$email_auteur = filtrer_ical($row['email_auteur']);
				if ($email_auteur) $email_auteur = "<$email_auteur>";
				
				ligne ("BEGIN:VEVENT");
				ligne ("SUMMARY:[$nom_site / ".filtrer_ical(_T('icone_forum_suivi'))."] $titre ");
				ligne ("DESCRIPTION:$texte\r$auteur $email_auteur");
				ligne ("UID:forum$id_forum @ $adresse_site");
				ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
				ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
				ligne ("DTEND:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure)+60,0,mois($date_heure),jour($date_heure),annee($date_heure))));
				ligne ("CATEGORIES:".filtrer_ical(_T('icone_forum_suivi')));
				ligne("URL:$adresse_site/ecrire/controle_forum.php3");
				ligne ("END:VEVENT");
	
	
				
			}

			if ($nb_forum > 0) {
				ligne ("BEGIN:VTODO");
				ligne ("SUMMARY:[$nom_site] "._T('icone_forum_suivi').": $nb_forum");
				ligne ("UID:forum @ $adresse_site");
				
				$today=getdate(time());
				$jour = $today["mday"];
				$mois=$today["mon"];
				$annee=$today["year"];
				ligne ("DTSTAMP:".date ("Ymd\THis", mktime (12,0,0,$mois,$jour,$annee)));
				ligne ("DTSTART:".date ("Ymd\THis", mktime (12,0,0,$mois,$jour,$annee)));
				ligne ("CATEGORIES:".filtrer_ical(_T('icone_forum_suivi')));
				ligne("URL:$adresse_site/ecrire/controle_forum.php3");
				ligne ("END:VTODO");
			}
		}



	
		ligne ("END:VCALENDAR");
	}
}


?>