<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_MAIL")) return;
define("_ECRIRE_INC_MAIL", "1");


$GLOBALS['queue_mails'] = '';

function envoyer_queue_mails() {
	global $queue_mails;
	if (!$queue_mails) return;
	reset($queue_mails);
	while (list(, $val) = each($queue_mails)) {
		$email = $val['email'];
		$sujet = $val['sujet'];
		$texte = $val['texte'];
		$headers = $val['headers'];
		@mail($email, $sujet, $texte, $headers);
	}
}

//
// Chez lyconiania, envoyer un mail coupe la connection MySQL (sic)
//

if ($GLOBALS['hebergeur'] == 'lycos') {
	register_shutdown_function(envoyer_queue_mails);
}

function tester_mail() {
	global $hebergeur;
	$test_mail = true;
	if ($hebergeur == 'free') $test_mail = false;
	return $test_mail;
}

function envoyer_mail($email, $sujet, $texte, $from = "", $headers = "") {
	global $hebergeur, $queue_mails, $flag_wordwrap, $os_serveur;

	if (!$from) $from = $email;
	if (! email_valide ($email) ) return false;
	if ($email == "vous@fournisseur.com") return false;

	$headers = "From: $from\n".
		"MIME-Version: 1.0\n".
		"Content-Type: text/plain; charset=iso-8859-1\n".
		"Content-Transfer-Encoding: 8bit\n$headers";
	if ($flag_wordwrap) $texte = wordwrap($texte);

	if ($os_serveur == 'windows') {
		$texte = ereg_replace ("\r*\n","\r\n", $texte);
		$headers = ereg_replace ("\r*\n","\r\n", $headers);
	}

	switch($hebergeur) {
	case 'lycos':
		$queue_mails[] = array(
			'email' => $email,
			'sujet' => $sujet,
			'texte' => $texte,
			'headers' => $headers);
		return true;
	case 'free':
		return false;
	case 'online':
		return @email('webmaster', $email, $sujet, $texte);
	case 'nexenservices':
		return @email($email, $sujet, $texte, $headers);
	default:
		return @mail($email, $sujet, $texte, $headers);
	}
}

function extrait_article($row) {
	$adresse_site = lire_meta("adresse_site");

	$id_article = $row[0];
	$titre = $row[2];
	$chapo = $row[6];
	$texte = $row[7];
	$date = $row[9];
	$statut = $row[10];

	$les_auteurs = "";
 	$query = "SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$id_article AND auteurs.id_auteur=lien.id_auteur";
	$result_auteurs = spip_query($query);

	while ($row = spip_fetch_array($result_auteurs)) {
		$nom_auteur = $row['nom'];

		if ($les_auteurs) $les_auteurs .= ', ';
		$les_auteurs .= $nom_auteur;
	}

	$extrait = "** $titre **\n";
	if ($les_auteurs) $extrait .= "par $les_auteurs ";
	if ($statut == 'publie') $extrait .= "le ".nom_jour($date)." ".affdate($date, 'CORRIGER_ENTITES');
	$extrait .= "\n\n".textebrut(propre(couper_intro("$chapo<p>$texte", 700)))."\n\n";
	if ($statut == 'publie') $extrait .= "-> ".$adresse_site."/spip_redirect.php3?id_article=$id_article\n\n";
	return $extrait;
}


function nettoyer_titre_email($titre) {
	$titre = ereg_replace("\n", ' ', supprimer_tags($titre));
	return ($titre); 
}

function envoyer_mail_publication($id_article) {
	global $connect_nom;
	$adresse_suivi = lire_meta("adresse_suivi");
	$adresse_site = lire_meta("adresse_site");
	$nom_site_spip = lire_meta("nom_site");
	$suivi_edito = lire_meta("suivi_edito");

	if ($suivi_edito == "oui") {
		$query = "SELECT * FROM spip_articles WHERE id_article = $id_article";
		$result = spip_query($query);

		if ($row = spip_fetch_array($result)) {
			$titre = nettoyer_titre_email($row['titre']);

			$sujet = "[$nom_site_spip] PUBLIE : $titre";
			$courr = "Article publi\xe9\n--------------\n\n";
			$courr .= "L'article \"$titre\" a \xe9t\xe9 valid\xe9 par $connect_nom.\n\n\n";
			$courr .= extrait_article($row);
			envoyer_mail($adresse_suivi, $sujet, $courr);
		}
	}
}

function envoyer_mail_proposition($id_article) {
	$adresse_suivi = lire_meta("adresse_suivi");
	$adresse_site = lire_meta("adresse_site");
	$nom_site_spip = lire_meta("nom_site");
	$suivi_edito = lire_meta("suivi_edito");

	if ($suivi_edito == "oui") {
		$query = "SELECT * FROM spip_articles WHERE id_article = $id_article";
		$result = spip_query($query);

		if ($row = spip_fetch_array($result)) {
			$titre = nettoyer_titre_email($row['titre']);

			$sujet = "[$nom_site_spip] Propose : $titre";
			$courr = "Article propos\xe9\n---------------\n\n";
			$courr .= "L'article \"$titre\" est propos\xe9 \xe0 la publication.\n";
			$courr .= "Vous \xeates invit\xe9 \xe0 venir le consulter et \xe0 donner votre opinion\n";
			$courr .= "dans le forum qui lui est attach\xe9. Il est disponible \xe0 l'adresse :\n";
			$courr .= $adresse_site."/ecrire/articles.php3?id_article=$id_article\n\n\n";
			$courr .= extrait_article($row);
			envoyer_mail($adresse_suivi, $sujet, $courr);
		}
	}
}

/*
function envoyer_mail_nouveautes($majnouv) {
	$jours_neuf = lire_meta("jours_neuf");
	$adresse_site = lire_meta("adresse_site");
	$adresse_neuf = lire_meta("adresse_neuf");
	$nom_site_spip = lire_meta("nom_site");
	$post_dates = lire_meta("post_dates");

	$courr .= "Bonjour,\n\n";
	$courr .= "Voici la lettre d'information du site \"$nom_site_spip\" ($adresse_site),\n";
	$courr .= "qui recense les articles et les breves publi\xe9s depuis $jours_neuf jours.\n\n";

	if ($post_dates == 'non')
		$query_post_dates = 'AND date < NOW()';

	// intervalle de reference
	if ($majnouv>0)	// depuis le dernier envoi
		$intervalle = time()-$majnouv;
	else			// mise a jour du site ou 'envoyer maintenant'
		$intervalle = 3600*24*$jours_neuf;

	$query = "SELECT * FROM spip_articles WHERE statut = 'publie' AND date > DATE_SUB(NOW(), INTERVAL $intervalle SECOND) $query_post_dates ORDER BY date DESC";
 	$result = spip_query($query);

	if (spip_num_rows($result) > 0) {
		$contenu = "\n          -----------------\n          NOUVEAUX ARTICLES\n          -----------------\n\n";
	}
	while ($row = spip_fetch_array($result)) {
		$contenu .= "\n".extrait_article($row);
	}
	spip_free_result($result);

	$activer_breves = lire_meta("activer_breves");

	if ($activer_breves != "non") {

	   	$query = "SELECT * FROM spip_breves WHERE statut = 'publie' AND date_heure > DATE_SUB(NOW(), INTERVAL $intervalle SECOND) ORDER BY date_heure DESC";
	 	$result = spip_query($query);

		if (spip_num_rows($result) > 0) {
			$contenu .= "\n          -----------------\n             LES BREVES\n          -----------------\n\n";
		}

	 	while($row = spip_fetch_array($result)) {
			$id_breve = $row['id_breve'];
			$date_heure = nom_jour($row['date_heure'])." ".affdate($row['date_heure'], 'CORRIGER_ENTITES');
			$breve_titre = $row['titre'];
			$breve_texte = $row['texte'];

			$extrait = textebrut(propre(couper_intro($breve_texte, 500)));
	
			$contenu .= "\n** $breve_titre ** - $date_heure\n\n$extrait\n\n-> ".$adresse_site."/spip_redirect.php3?id_breve=$id_breve\n\n";
		}
	}

	if ($contenu)
		envoyer_mail($adresse_neuf, "[$nom_site_spip] Les nouveautes", $courr.$contenu);
}

function envoyer_mail_quoi_de_neuf($force=false) {
	if (!$force)
		$majnouv = lire_meta('majnouv');
	else
		$majnouv = 0;
	ecrire_meta('majnouv', time());
	ecrire_metas();
	envoyer_mail_nouveautes($majnouv);
} */

?>
