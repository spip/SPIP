<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
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

?>