<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Infos de mails sur l'hebergeur (tout ca est assez sale)
//
global $hebergeur;
global $HTTP_X_HOST, $SERVER_NAME;
$hebergeur = '';

// Lycos (ex-Multimachin)
if ($HTTP_X_HOST == 'membres.lycos.fr') {
	$hebergeur = 'lycos';
}
// Altern
else if (ereg('altern\.com$', $SERVER_NAME)) {
	$hebergeur = 'altern';
}
// NexenServices
else if ($_SERVER['SERVER_ADMIN'] == 'www@nexenservices.com') {
	if (!function_exists('email'))
		include ('mail.inc');
	$hebergeur = 'nexenservices';
}
// Online
else if (function_exists('email')) {
	$hebergeur = 'online';
}


//
// Chez lyconiania, envoyer un mail coupe la connection MySQL (sic)
//

$GLOBALS['queue_mails'] = '';

// http://doc.spip.org/@envoyer_queue_mails
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

// Apparemment free etait le dernier hebergeur connu a ne pas offrir de mail
// cette fonction va donc pouvoir disparaitre
// http://doc.spip.org/@tester_mail
function tester_mail() {
	global $hebergeur;
	$test_mail = true;
	return $test_mail;
}

// http://doc.spip.org/@nettoyer_caracteres_mail
function nettoyer_caracteres_mail($t) {

	$t = filtrer_entites($t);

	if ($GLOBALS['meta']['charset'] <> 'utf-8') {
		$t = str_replace(
			array("&#8217;","&#8220;","&#8221;"),
			array("'",      '"',      '"'),
		$t);
	}

	$t = str_replace(
		array("&mdash;", "&endash;"),
		array("--","-" ),
	$t);

	return $t;
}

// http://doc.spip.org/@envoyer_mail
function envoyer_mail($email, $sujet, $texte, $from = "", $headers = "") {
	global $hebergeur, $queue_mails;
	include_spip('inc/charsets');

	if (!email_valide($email)) return false;
	if ($email == _T('info_mail_fournisseur')) return false; // tres fort

	// Ajouter au besoin le \n final dans les $headers passes en argument
	if ($headers = trim($headers)) $headers .= "\n";

	if (!$from) {
		$email_envoi = $GLOBALS['meta']["email_envoi"];
		$from = email_valide($email_envoi) ? $email_envoi : $email;
	} else {
		// pour les sites qui colle d'office From: serveur-http
		$headers .= "Reply-To: $from\n";
	}
	spip_log("mail ($email): $sujet". ($from ?", from <$from>":''));

	$charset = $GLOBALS['meta']['charset'];

	// Ajouter le Content-Type s'il n'y est pas deja
	if (strpos($headers, "Content-Type: ") === false)
		$headers .=
		"MIME-Version: 1.0\n".
		"Content-Type: text/plain; charset=$charset\n".
		"Content-Transfer-Encoding: 8bit\n";

	// Et maintenant le champ From:
	$headers .= "From: $from\n";

	// nettoyer les &eacute; &#8217, &emdash; etc...
	$texte = nettoyer_caracteres_mail($texte);
	$sujet = nettoyer_caracteres_mail($sujet);

	// encoder le sujet si possible selon la RFC
	if (init_mb_string()) {
		# un bug de mb_string casse mb_encode_mimeheader si l'encoding interne
		# est UTF-8 et le charset iso-8859-1 (constate php5-mac ; php4.3-debian)
		mb_internal_encoding($charset);
		$sujet = mb_encode_mimeheader($sujet, $charset, 'Q', "\n");
		mb_internal_encoding('utf-8');
	}

	if (function_exists('wordwrap'))
		$texte = wordwrap($texte);

	if (os_serveur == 'windows') {
		$texte = ereg_replace ("\r*\n","\r\n", $texte);
		$headers = ereg_replace ("\r*\n","\r\n", $headers);
		$sujet = ereg_replace ("\r*\n","\r\n", $sujet);
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
	default:
		return @mail($email, $sujet, $texte, $headers);
	}
}

// http://doc.spip.org/@extrait_article
function extrait_article($row) {
	include_spip('inc/texte');
	
	$id_article = $row['id_article'];
	$titre = nettoyer_titre_email($row['titre']);
	$chapo = $row['chapo'];
	$texte = $row['texte'];
	$date = $row['date'];
	$statut = $row['statut'];

	$les_auteurs = "";
	$result_auteurs = spip_query("SELECT nom FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$id_article AND auteurs.id_auteur=lien.id_auteur");

	while ($row = spip_fetch_array($result_auteurs)) {
		if ($les_auteurs) $les_auteurs .= ', ';
		$les_auteurs .= trim(supprimer_tags(typo($row['nom'])));
	}

	$extrait = "** $titre **\n";
	if ($les_auteurs) $extrait .= _T('info_les_auteurs_1', array('les_auteurs' => $les_auteurs));
	if ($statut == 'publie') $extrait .= " "._T('date_fmt_nomjour_date', array('nomjour'=>nom_jour($date), 'date'=>affdate($date)));
	$extrait .= "\n\n".textebrut(propre(couper_intro("$chapo<p>$texte", 700)))."\n\n";
	if ($statut == 'publie') 
		$extrait .= "-> ".
		  generer_url_action("redirect", "id_article=$id_article", true) .
		  "\n\n";
	return $extrait;
}

// http://doc.spip.org/@nettoyer_titre_email
function nettoyer_titre_email($titre) {
	return ereg_replace("\n", ' ', supprimer_tags(extraire_multi($titre)));
}

// http://doc.spip.org/@envoyer_mail_publication
function envoyer_mail_publication($id_article) {
	$adresse_suivi = $GLOBALS['meta']["adresse_suivi"];
	$nom_site_spip = nettoyer_titre_email($GLOBALS['meta']["nom_site"]);
	$suivi_edito = $GLOBALS['meta']["suivi_edito"];

	if ($suivi_edito == "oui") {
		$result = spip_query("SELECT * FROM spip_articles WHERE id_article = $id_article");

		if ($row = spip_fetch_array($result)) {

			// selectionne langue
			$lang_utilisateur = $GLOBALS['spip_lang'];
			changer_langue($row['lang']);

			$titre = nettoyer_titre_email($row['titre']);

			$sujet = _T('info_publie_1', array('nom_site_spip' => $nom_site_spip, 'titre' => $titre));
			$courr = _T('info_publie_2')."\n\n";

			$nom = $GLOBALS['auteur_session']['nom'];
			$nom = trim(supprimer_tags(typo($nom)));
			$courr .= _T('info_publie_01', array('titre' => $titre, 'connect_nom' => $nom))."\n\n\n";
			$courr = $courr . extrait_article($row);
			envoyer_mail($adresse_suivi, $sujet, $courr);

			// reinstalle la langue utilisateur (au cas ou)
			changer_langue($lang_utilisateur);
		}
	}
}

// http://doc.spip.org/@envoyer_mail_proposition
function envoyer_mail_proposition($id_article) {
	$adresse_suivi = $GLOBALS['meta']["adresse_suivi"];
	$nom_site_spip = nettoyer_titre_email($GLOBALS['meta']["nom_site"]);
	$suivi_edito = $GLOBALS['meta']["suivi_edito"];

	if ($suivi_edito == "oui") {
		$row = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article = $id_article"));
		if ($row) {

			$lang_utilisateur = $GLOBALS['spip_lang'];
			changer_langue($row['lang']);

			$titre = nettoyer_titre_email($row['titre']);

			$sujet = _T('info_propose_1', array('nom_site_spip' => $nom_site_spip, 'titre' => $titre));
			envoyer_mail($adresse_suivi,
				     $sujet,
				     _T('info_propose_2')
				     ."\n\n" 
				     . _T('info_propose_3', array('titre' => $titre))
				     ."\n" 
				     . _T('info_propose_4')
				     ."\n" 
				     . _T('info_propose_5')
				     ."\n" 
				     . generer_url_ecrire("articles", "id_article=$id_article", true)
				     . "\n\n\n" 
				     . extrait_article($row));
			changer_langue($lang_utilisateur);
		}
	}
}


//
// Mail des nouveautes
//
// http://doc.spip.org/@cron_mail
function cron_mail($t) {
	$adresse_neuf = $GLOBALS['meta']['adresse_neuf'];
	$jours_neuf = $GLOBALS['meta']['jours_neuf'];
	// $t = 0 si le fichier de lock a ete detruit
	if (!$t) $t = time() - (3600 * 24 * $jours_neuf);

	$f = charger_fonction('parametrer', 'public');
	$page = $f('nouveautes',
			    array('date' => date('Y-m-d H:i:s', $t),
				  'jours_neuf' => $jours_neuf));
	$page = $page['texte'];
	if (substr($page,0,5) == '<'.'?php') {
# ancienne version: squelette en PHP avec affection des 2 variables ci-dessous
# 1 passe de plus a la sortie
				$mail_nouveautes = '';
				$sujet_nouveautes = '';
				$headers = '';
				eval ('?' . '>' . $page);
	} else {
# nouvelle version en une seule passe avec un squelette textuel:
# 1ere ligne = sujet
# lignes suivantes jusqu'a la premiere blanche: headers SMTP

				$page = stripslashes(trim($page));
				$page = preg_replace(",\r\n?,", "\n", $page);
				$p = strpos($page,"\n\n");
				$s = strpos($page,"\n");
				if ($p AND $s) {
					if ($p>$s)
						$headers = substr($page,$s+1,$p-$s);
					$sujet_nouveautes = substr($page,0,$s);
					$mail_nouveautes = trim(substr($page,$p+2));
				}
	}

	if (strlen($mail_nouveautes) > 10)
		envoyer_mail($adresse_neuf, $sujet_nouveautes, $mail_nouveautes, '', $headers);
	else
		spip_log("mail nouveautes : rien de neuf depuis $jours_neuf jours");
	return 1;
}

?>
