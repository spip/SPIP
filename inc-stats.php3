<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
if (!defined("_ECRIRE_INC_VERSION")) return;

function ecrire_stats() {
	global $id_article, $id_breve, $id_rubrique;

	if ($GLOBALS['HTTP_X_FORWARDED_FOR'])
		$log_ip = $GLOBALS['HTTP_X_FORWARDED_FOR'];
	else
		$log_ip = $GLOBALS['REMOTE_ADDR'];

	if ($log_id_num = intval($id_rubrique))
		$log_type = "rubrique";
	else if ($log_id_num = intval($id_article))
		$log_type = "article";
	else if ($log_id_num = intval($id_breve))
		$log_type = "breve";
	else
		$log_type = "autre";

	// Conversion IP 4 octets -> entier 32 bits
	if (preg_match(",^(::ffff:)?([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$,",
	$log_ip, $r))
		$log_ip = sprintf("%02x%02x%02x%02x", $r[2], $r[3], $r[4], $r[5]);
	else
		return;

	// Analyse du referer
	if ($log_referer = $GLOBALS['HTTP_REFERER']) {
		$url_site_spip = preg_replace(',^((https?|ftp)://)?(www\.)?,i', '',
			$GLOBALS['meta']['adresse_site']);
		if (($url_site_spip<>'')
		AND strpos('-'.strtolower($log_referer), strtolower($url_site_spip))
		AND !$_GET['var_recherche'])
			$log_referer = '';
		else
			$referer_md5 = '0x'.substr(md5($log_referer), 0, 15);
	}

	//
	// stockage sous forme de fichier dans ecrire/data/stats_200511161005/ip
	//

	// 1. Chercher dans les paniers recents (moins de 30 minutes) s'il existe
	// deja une session pour ce numero IP. Chaque panier couvre 5 minutes
	$content = array();
	for ($i = -5; $i <= 0; $i++) {
		$panier = date('YmdHi', (intval(time()/300)+$i)*300);
		if (@file_exists($s = _DIR_SESSIONS.'stats_'.$panier.'/'.$log_ip)) {
			lire_fichier($s, $content);
			$content = @unserialize($content);
			if ($i<0) @unlink($s);
			break;
		}
	}

	// 2. Determiner le fichier session dans le panier actuel
	$panier = date('YmdHi', (intval(time()/300))*300);
	$dir = _DIR_SESSIONS.creer_repertoire(_DIR_SESSIONS,'stats_'.$panier);

	// 3. Plafonner le nombre de hits pris en compte pour un IP (robots etc.)
	if (count($content) < 200) {
		$entree = trim("$log_type\t$log_id_num\t$log_referer");
		$content[$entree] ++;
		ecrire_fichier($s, serialize($content));
	}
}

?>
