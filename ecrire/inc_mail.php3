<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_MAIL")) return;
define("_ECRIRE_INC_MAIL", "1");


//
// Chez lyconiania, envoyer un mail coupe la connection MySQL (sic)
//

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
	if (!email_valide($email)) return false;
	if ($email == "vous@fournisseur.com") return false;

	spip_log("mail ($email): $sujet");

	if (!$charset = lire_meta('charset'))$
		$charset='iso-8859-1';

	$headers = "From: $from\n".
		"MIME-Version: 1.0\n".
		"Content-Type: text/plain; charset=$charset\n".
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
	include_ecrire("inc_texte.php3");
	include_ecrire("inc_filtres.php3");

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

?>
