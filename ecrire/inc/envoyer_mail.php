<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion des emails et de leur envoi
 *
 * @package SPIP\Core\Mail
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/charsets');
include_spip('inc/texte');

/**
 * Nettoyer le titre d'un email
 *
 * @uses textebrut()
 * @uses corriger_typo()
 *
 * @param  string $titre
 * @return string
 */
function nettoyer_titre_email($titre) {
	return str_replace("\n", ' ', nettoyer_caracteres_mail(textebrut(corriger_typo($titre))));
}

/**
 * Utiliser le bon encodage de caractères selon le charset
 *
 * Caractères pris en compte : apostrophe, double guillemet,
 * le tiret cadratin, le tiret demi-cadratin
 *
 * @uses filtrer_entites()
 *
 * @param string $t
 * @return string
 */
function nettoyer_caracteres_mail($t) {

	$t = filtrer_entites($t);

	if ($GLOBALS['meta']['charset'] <> 'utf-8') {
		$t = str_replace(
			['&#8217;', '&#8220;', '&#8221;'],
			["'", '"', '"'],
			$t
		);
	}

	$t = str_replace(
		['&mdash;', '&endash;'],
		['--', '-'],
		$t
	);

	return $t;
}

/**
 * Envoi d'un mail
 *
 * @param string $destinataire
 * @param string $sujet
 * @param string|array $corps
 *   - au format string, c'est un corps d'email au format texte, comme supporte nativement par le core
 *   - au format array, c'est un corps etendu qui peut contenir
 *     - string texte : le corps d'email au format texte
 *     - string from : email de l'envoyeur (prioritaire sur argument $from de premier niveau, deprecie)
 *     - array headers : tableau d'en-tetes personalises, une entree par ligne d'en-tete
 *         --- Support partiel par une fonction mail_embarquer_pieces_jointes a fournir, ---
 *         --- chargee de convertir en texte encodee les pieces jointes ---
 *     - array pieces_jointes : listes de pieces a embarquer dans l'email, chacune au format array :
 *       - string chemin : chemin file system pour trouver le fichier a embarquer
 *       - string nom : nom du document tel qu'apparaissant dans l'email
 *       - string encodage : encodage a utiliser, parmi 'base64', '7bit', '8bit', 'binary', 'quoted-printable'
 *       - string mime : mime type du document
 *             --- Non implemente ici ---
 *     - string html : le corps d'email au format html
 *     - string nom_envoyeur : un nom d'envoyeur pour completer l'email from
 *     - string cc : destinataires en copie conforme
 *     - string bcc : destinataires en copie conforme cachee
 *     - string adresse_erreur : addresse de retour en cas d'erreur d'envoi
 * @param string $from (deprecie, utiliser l'entree from de $corps)
 * @param string $headers (deprecie, utiliser l'entree headers de $corps)
 * @return bool
 */
function inc_envoyer_mail_dist($destinataire, $sujet, $corps, $from = '', $headers = '') {

	if (!email_valide($destinataire)) {
		return false;
	}
	if ($destinataire == _T('info_mail_fournisseur')) {
		return false;
	} // tres fort

	// Fournir si possible un Message-Id: conforme au RFC1036,
	// sinon SpamAssassin denoncera un MSGID_FROM_MTA_HEADER

	$email_envoi = $GLOBALS['meta']['email_envoi'];
	if (!email_valide($email_envoi)) {
		spip_log('Meta email_envoi invalide. Le mail sera probablement vu comme spam.');
		$email_envoi = $destinataire;
	}

	$parts = '';
	if (is_array($corps)) {
		$texte = $corps['texte'];
		$from = ($corps['from'] ?? $from);
		$headers = ($corps['headers'] ?? $headers);
		if (is_array($headers)) {
			$headers = implode("\n", $headers);
		}
		if (isset($corps['pieces_jointes']) and function_exists('mail_embarquer_pieces_jointes')) {
			$parts = mail_embarquer_pieces_jointes($corps['pieces_jointes']);
		}
	} else {
		$texte = $corps;
	}

	if (!$from) {
		$from = $email_envoi;
	}

	// ceci est la RegExp NO_REAL_NAME faisant hurler SpamAssassin
	if (preg_match('/^["\s]*\<?\S+\@\S+\>?\s*$/', $from)) {
		$from .= ' (' . str_replace(')', '', translitteration(str_replace('@', ' at ', $from))) . ')';
	}

	// nettoyer les &eacute; &#8217, &emdash; etc...
	// les 'cliquer ici' etc sont a eviter;  voir:
	// http://mta.org.ua/spamassassin-2.55/stuff/wiki.CustomRulesets/20050914/rules/french_rules.cf
	$texte = nettoyer_caracteres_mail($texte);
	$sujet = nettoyer_caracteres_mail($sujet);

	// encoder le sujet si possible selon la RFC
	if (init_mb_string()) {
		# un bug de mb_string casse mb_encode_mimeheader si l'encoding interne
		# est UTF-8 et le charset iso-8859-1 (constate php5-mac ; php4.3-debian)
		$charset = $GLOBALS['meta']['charset'];
		mb_internal_encoding($charset);
		$sujet = mb_encode_mimeheader($sujet, $charset, 'Q', "\n");
		mb_internal_encoding('utf-8');
	}

	$headers = $headers ?? '';
	if (function_exists('wordwrap') && (preg_match(',multipart/mixed,', $headers) == 0)) {
		$texte = wordwrap($texte);
	}

	[$headers, $texte] = mail_normaliser_headers($headers, $from, $destinataire, $texte, $parts);

	if (_OS_SERVEUR == 'windows') {
		$texte = preg_replace("@\r*\n@", "\r\n", $texte);
		$headers = preg_replace("@\r*\n@", "\r\n", $headers);
		$sujet = preg_replace("@\r*\n@", "\r\n", $sujet);
	}

	spip_log("mail $destinataire\n$sujet\n$headers", 'mails');
	// mode TEST : forcer l'email
	if (defined('_TEST_EMAIL_DEST')) {
		if (!_TEST_EMAIL_DEST) {
			return false;
		} else {
			$texte = "Dest : $destinataire\r\n" . $texte;
			$destinataire = _TEST_EMAIL_DEST;
		}
	}

	return @mail($destinataire, $sujet, $texte, $headers);
}

/**
 * Formater correctement l'entête d'un email
 *
 * @param string $headers
 * @param string $from
 * @param string $to
 * @param string $texte
 * @param string $parts
 * @return array
 */
function mail_normaliser_headers($headers, $from, $to, $texte, $parts = '') {
	$charset = $GLOBALS['meta']['charset'];

	// Ajouter le Content-Type et consort s'il n'y est pas deja
	if (strpos($headers, 'Content-Type: ') === false) {
		$type =
			"Content-Type: text/plain;charset=\"$charset\";\n" .
			"Content-Transfer-Encoding: 8bit\n";
	} else {
		$type = '';
	}

	// calculer un identifiant unique
	// Marie Toto <Marie@toto.com> => @toto.com
	if (preg_match('/@[^\s>]+/', $from, $domain)) {
		$domain = $domain[0];
	}
	else {
		$domain = '@unknown-' . md5($from) . '.org';
	}
	$uniq = random_int(0, mt_getrandmax()) . '_' . md5($to . $texte) . $domain;

	// Si multi-part, s'en servir comme borne ...
	if ($parts) {
		$texte = "--$uniq\n$type\n" . $texte . "\n";
		foreach ($parts as $part) {
			$n = strlen($part[1]) . ($part[0] ? "\n" : '');
			$e = join("\n", $part[0]);
			$texte .= "\n--$uniq\nContent-Length: $n$e\n\n" . $part[1];
		}
		$texte .= "\n\n--$uniq--\n";
		// Si boundary n'est pas entre guillemets,
		// elle est comprise mais le charset est ignoree !
		$type = "Content-Type: multipart/mixed; boundary=\"$uniq\"\n";
	}

	// .. et s'en servir pour plaire a SpamAssassin

	$mid = 'Message-Id: <' . $uniq . '>';

	// indispensable pour les sites qui collent d'office From: serveur-http
	// sauf si deja mis par l'envoyeur
	$rep = (strpos($headers, 'Reply-To:') !== false) ? '' : "Reply-To: $from\n";

	// Nettoyer les en-tetes envoyees
	// Ajouter le \n final
	if (strlen($headers = trim($headers))) {
		$headers .= "\n";
	}

	// Et mentionner l'indeboulonable nomenclature ratee

	$headers .= "From: $from\n$type$rep$mid\nMIME-Version: 1.0\n";

	return [$headers, $texte];
}
