<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@nettoyer_titre_email
function nettoyer_titre_email($titre) {
	return str_replace("\n", ' ', supprimer_tags(extraire_multi($titre)));
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

// http://doc.spip.org/@inc_envoyer_mail_dist
function inc_envoyer_mail_dist($email, $sujet, $texte, $from = "", $headers = "") {
	include_spip('inc/charsets');
	include_spip('inc/filtres');

	if (!email_valide($email)) return false;
	if ($email == _T('info_mail_fournisseur')) return false; // tres fort

	// Traiter les headers existants
	if (strlen($headers)) $headers = trim($headers)."\n";

	// Fournir si possible un Message-Id: conforme au RFC1036,
	// sinon SpamAssassin denoncera un MSGID_FROM_MTA_HEADER

	$email_envoi = $GLOBALS['meta']["email_envoi"];
	if (email_valide($email_envoi)) {
		preg_match('/(@\S+)/', $email_envoi, $domain);
		$mid = 'Message-Id: <' . time() . '_' . rand() . '_' . md5($email . $texte) . $domain[1] . ">\n";
	} else {
		spip_log("Meta email_envoi invalide. Le mail sera probablement vu comme spam.");
		$email_envoi = $email;
		$mid = '';
	}
	if (!$from) $from = $email_envoi;

	// ceci est la RegExp NO_REAL_NAME faisant hurler SpamAssassin
	if (preg_match('/^["\s]*\<?\S+\@\S+\>?\s*$/', $from))
		$from .= ' (' . str_replace(')','', translitteration(str_replace('@', ' at ', $from))) . ')';

	// Et maintenant le champ From:
	$headers .= "From: $from\n";

	// indispensable pour les sites qui colle d'office From: serveur-http
	// sauf si deja mis par l'envoyeur
	if (strpos($headers,"Reply-To:")===FALSE)
		$headers .= "Reply-To: $from\n";

	$charset = $GLOBALS['meta']['charset'];

	// Ajouter le Content-Type et consort s'il n'y est pas deja
	if (strpos($headers, "Content-Type: ") === false)
		$headers .=
		"Content-Type: text/plain; charset=$charset\n".
		"Content-Transfer-Encoding: 8bit\n" .
		"MIME-Version: 1.0\n";

	$headers .= $mid;

	// nettoyer les &eacute; &#8217, &emdash; etc...
	// les 'cliquer ici' etc sont a eviter;  voir:
	// http://mta.org.ua/spamassassin-2.55/stuff/wiki.CustomRulesets/20050914/rules/french_rules.cf
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

	spip_log("mail $email\n$sujet\n$headers",'mails');

	// Ajouter le \n final
	if ($headers = trim($headers)) $headers .= "\n";
	if (function_exists('wordwrap'))
		$texte = wordwrap($texte);

	if (_OS_SERVEUR == 'windows') {
		$texte = preg_replace ("@\r*\n@","\r\n", $texte);
		$headers = preg_replace ("@\r*\n@","\r\n", $headers);
		$sujet = preg_replace ("@\r*\n@","\r\n", $sujet);
	}

	return @mail($email, $sujet, $texte, $headers);
}

?>
