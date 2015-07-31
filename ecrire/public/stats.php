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

# interface obsolete (?)
// http://doc.spip.org/@ecrire_stats
function ecrire_stats() {public_stats_dist();}

// http://doc.spip.org/@public_stats_dist
function public_stats_dist() {

	// Rejet des robots (qui sont pourtant des humains comme les autres)
	if (preg_match(
	',google|yahoo|msnbot|crawl|lycos|voila|slurp|jeeves|teoma,i',
	$_SERVER['HTTP_USER_AGENT']))
		return;

	// Ne pas compter les visiteurs sur les flux rss (qui sont pourtant
	// des pages web comme les autres) [hack pourri en attendant de trouver
	// une meilleure idee ?]
	if (preg_match(',^backend,', $GLOBALS['fond']))
		return;


	// Identification du client
	$client_id = substr(md5(
		$GLOBALS['ip'] . $_SERVER['HTTP_USER_AGENT']
//		. $_SERVER['HTTP_ACCEPT'] # HTTP_ACCEPT peut etre present ou non selon que l'on est dans la requete initiale, ou dans les hits associes
		. $_SERVER['HTTP_ACCEPT_LANGUAGE']
		. $_SERVER['HTTP_ACCEPT_ENCODING']
	), 0,10);

	// Analyse du referer
	$log_referer = '';
	if (isset($_SERVER['HTTP_REFERER'])) {
		$url_site_spip = preg_replace(',/$,', '',
			preg_replace(',^(https?://)?(www\.)?,i', '',
			url_de_base()));
		if (!(($url_site_spip<>'')
		AND strpos('-'.strtolower($_SERVER['HTTP_REFERER']), strtolower($url_site_spip))
		AND !isset($_GET['var_recherche']))) {
			$log_referer = $_SERVER['HTTP_REFERER'];
			$referer_md5 = '0x'.substr(md5($log_referer), 0, 15);
		}
	}

	//
	// stockage sous forme de fichier ecrire/data/stats/client_id
	//

	// 1. Chercher s'il existe deja une session pour ce numero IP.
	$content = array();
	$fichier = sous_repertoire(_DIR_TMP, 'visites') . $client_id;
	if (lire_fichier($fichier, $content))
		$content = @unserialize($content);

	// 2. Plafonner le nombre de hits pris en compte pour un IP (robots etc.)
	// et ecrire la session
	if (count($content) < 200) {

	// Identification de l'element
	// Attention il s'agit bien des $GLOBALS, regles (dans le cas des urls
	// personnalises), par la carte d'identite de la page... ne pas utiliser
	// _request() ici !
		if (isset($GLOBALS['id_article']))
			$log_type = "article";
		else if (isset($GLOBALS['id_breve']))
			$log_type = "breve";
		else if (isset($GLOBALS['id_rubrique']))
			$log_type = "rubrique";
		else
			$log_type = "";

		if ($log_type)
			$log_type .= "\t" . intval($GLOBALS["id_$log_type"]);
		else    $log_type = "autre\t0";

		$log_type .= "\t" . trim($log_referer);
		if (isset($content[$log_type]))
			$content[$log_type]++;
		else	$content[$log_type] = 1; // bienvenue au club

		ecrire_fichier($fichier, serialize($content));
	}
}

?>
