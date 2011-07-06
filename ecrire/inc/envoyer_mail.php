<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/charsets');
include_spip('inc/texte');

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
else if (preg_match(',altern\.com$,', $SERVER_NAME)) {
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


// http://doc.spip.org/@nettoyer_titre_email
function nettoyer_titre_email($titre) {
	return str_replace("\n", ' ', textebrut(corriger_typo($titre)));
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

// Envoi d'un mail avec d'eventuelles pieces jointes
// specifiees en dernier argument sous forme d'un tableau de sous-tableaux
// de longueur 2 (headers / body)

// http://doc.spip.org/@inc_envoyer_mail_dist
function inc_envoyer_mail_dist($email, $sujet, $texte, $from = "", $headers = "", $parts=array()) {
	global $hebergeur, $queue_mails;

	if (!email_valide($email)) return false;
	if ($email == _T('info_mail_fournisseur')) return false; // tres fort

	// Fournir si possible un Message-Id: conforme au RFC1036,
	// sinon SpamAssassin denoncera un MSGID_FROM_MTA_HEADER

	$email_envoi = $GLOBALS['meta']["email_envoi"];
	if (!email_valide($email_envoi)) {
		spip_log("Meta email_envoi invalide. Le mail sera probablement vu comme spam.");
		$email_envoi = $email;
	}

	if (!$from) $from = $email_envoi;

	// ceci est la RegExp NO_REAL_NAME faisant hurler SpamAssassin
	if (preg_match('/^["\s]*\<?\S+\@\S+\>?\s*$/', $from))
		$from .= ' (' . str_replace(')','', translitteration(str_replace('@', ' at ', $from))) . ')';

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

	if (function_exists('wordwrap') && (preg_match(',multipart/mixed,',$headers) == 0))
		$texte = wordwrap($texte);

	list($headers, $texte) = mail_normaliser_headers($headers, $from, $email, $texte, $parts);

	if (_OS_SERVEUR == 'windows') {
		$texte = preg_replace ("@\r*\n@","\r\n", $texte);
		$headers = preg_replace ("@\r*\n@","\r\n", $headers);
		$sujet = preg_replace ("@\r*\n@","\r\n", $sujet);
	}

	spip_log("mail $email\n$sujet\n$headers",'mails');
	// mode TEST : forcer l'email
	if (defined('_TEST_EMAIL_DEST')) {
		if (!_TEST_EMAIL_DEST)
			return false;
		else
			$email = _TEST_EMAIL_DEST;
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
	default:
		return @mail($email, $sujet, $texte, $headers);
	}
}

function mail_normaliser_headers($headers, $from, $to, $texte, $parts)
{
	$charset = $GLOBALS['meta']['charset'];

	// Ajouter le Content-Type et consort s'il n'y est pas deja
	if (strpos($headers, "Content-Type: ") === false)
		$type =
		"Content-Type: text/plain;charset=\"$charset\";\n".
		"Content-Transfer-Encoding: 8bit\n";
	else $type = '';

	// calculer un identifiant unique
	preg_match('/@\S+/', $from, $domain);
	$uniq = rand() . '_' . md5($to . $texte) . $domain[0];

	// Si multi-part, s'en servir comme borne ...
	if ($parts) {
		$texte = "--$uniq\n$type\n" . $texte ."\n";
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

	$mid = 'Message-Id: <' . $uniq . ">";

	// indispensable pour les sites qui collent d'office From: serveur-http
	// sauf si deja mis par l'envoyeur
	$rep = (strpos($headers,"Reply-To:")!==FALSE) ? '' : "Reply-To: $from\n";

	// Nettoyer les en-tetes envoyees
	if (strlen($headers)) $headers = trim($headers)."\n";

	// Et mentionner l'indeboulonable nomenclature ratee 

	$headers .= "From: $from\n$type$rep$mid\nMIME-Version: 1.0\n";

	return array($headers, $texte);
}
?>
