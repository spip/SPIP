<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_PUBLIC_GLOBAL")) return;
define("_INC_PUBLIC_GLOBAL", "1");


//
// Aller chercher la page dans le cache ou pas
//
function obtenir_page ($contexte, $chemin_cache, $delais, $use_cache, $fond, $inclusion=false) {
	global $lastmodified;

	if (!$use_cache) {
		include_local('inc-calcul.php3');

		// page globale ? calculer le contexte
		if (!$contexte)
			$contexte = calculer_contexte();

		spip_timer();
		$page = calculer_page($chemin_cache,
			array('fond' => $fond,
				'contexte' => $contexte),
			$delais,
			$inclusion);
		spip_log (($inclusion ? 'calcul inclus':'calcul').' ('.spip_timer().
		"): $chemin_cache");
	} else {
		lire_fichier ($chemin_cache, $page['texte']);
		# spip_log ("cache $chemin_cache");
	}

	// Fixer la date de derniere modif
	if ($chemin_cache)
		$lastmodified = max($lastmodified, @filemtime($chemin_cache));
	else
		$lastmodified = time();

	// Analyser la carte d'identite du squelette
	if (preg_match("/^<!-- ([^\n]*) -->\n/", $page['texte'], $match)) {
		$page['texte'] = substr($page['texte'], strlen($match[0]));
		foreach (unserialize($match[1]) as $var=>$val)
			$page[$var] = $val;
	}

	return $page;
}


//
// Appeler cette fonction pour obtenir la page principale
//
function afficher_page_globale ($fond, $delais, &$use_cache) {
	global $flag_preserver, $recalcul, $lastmodified;
	include_local ("inc-cache.php3");

	$chemin_cache = 'CACHE/'.generer_nom_fichier_cache('', $fond);
	determiner_cache($delais, $use_cache, $chemin_cache);

	// Repondre gentiment aux requetes sympas
	// (ici on ne tient pas compte d'une obsolence du cache ou des
	// eventuels fichiers inclus modifies depuis la date
	// HTTP_IF_MODIFIED_SINCE du client)
	if ($GLOBALS['HTTP_IF_MODIFIED_SINCE'] AND $recalcul != oui
	AND $chemin_cache) {
		$lastmodified = @filemtime($chemin_cache);
		$headers_only = http_last_modified($lastmodified);
	}
	$headers_only |= ($GLOBALS['HTTP_SERVER_VARS']['REQUEST_METHOD'] == 'HEAD');

	if ($headers_only) {
		if ($chemin_cache)
			$t = @filemtime($chemin_cache);
		else
			$t = time();
		@header('Last-Modified: '.http_gmoddate($t).' GMT');
		@header('Connection: close');
		// Pas de bouton admin pour un HEAD
		$flag_preserver = true;
	}
	else {
		// Obtenir la page
		$page = obtenir_page ('', $chemin_cache, $delais, $use_cache,
		$fond, false);

		// Entete content-type: xml ou html ; charset
		if ($xhtml) {
			// Si Mozilla et tidy actif, passer en "application/xhtml+xml"
			// extremement risque: Mozilla passe en mode debugueur strict
			// mais permet d'afficher du MathML directement dans le texte
			// (et sauf erreur, c'est la bonne facon de declarer du xhtml)
			include_ecrire("inc_tidy.php");
			if (version_tidy() > 0) {
				if (ereg("application/xhtml\+xml", $GLOBALS['HTTP_ACCEPT'])) 
					@header("Content-Type: application/xhtml+xml; ".
					"charset=".lire_meta('charset'));
				else 
					@header("Content-Type: text/html; ".
					"charset=".lire_meta('charset'));
					
				echo '<'.'?xml version="1.0" encoding="'.
				lire_meta('charset').'"?'.">\n";
			} else {
				@header("Content-Type: text/html; ".
				"charset=".lire_meta('charset'));
			}
		} else {
			@header("Content-Type: text/html; charset=".lire_meta('charset'));
		}

		//
		// Ajouter au besoin les boutons admins
		//
		if ($page_boutons_admin = admin_page($use_cache, $page['texte'])) {
			$page['texte'] = $page_boutons_admin;
			$page['process_ins'] = 'php';
		}
	}

	if ($chemin_cache) $page['cache'] = $chemin_cache;

	return $page;
}

function terminer_public_global($use_cache, $chemin_cache='') {

	// Mise a jour des fichiers langues de l'espace public
	if ($GLOBALS['cache_lang_modifs']) {
		include_ecrire('inc_lang.php3');
		ecrire_caches_langues();
	}

	// Toutes les heures, menage du repertoire cache courant
	if ($use_cache && (time() - lire_meta('date_purge_cache') > 3600)) {
		if (eregi("^(CACHE.*/)[^/]*$", $chemin_cache, $regs)) {
			include_ecrire('inc_invalideur.php3');
			retire_vieux_caches($regs[1]);
		}
	}

	// Calculs en background
	if ($use_cache)
		taches_de_fond();

	// Gestion des statistiques du site public
	// (a la fin pour ne pas forcer le $db_ok)
	if (lire_meta("activer_statistiques") != "non") {
		include_local ("inc-stats.php3");
		ecrire_stats();
	}
}

function inclure_page($fond, $delais_inclus, $contexte_inclus, $cache_incluant='') {
	global $delais;

	$contexte_inclus['fond'] = $fond;

	$chemin_cache = 'CACHE/'.generer_nom_fichier_cache($contexte_inclus, $fond);

	// Si on a inclus sans fixer le critere de lang, de deux choses l'une :
	// - on est dans la langue du site, et pas besoin d'inclure inc_lang
	// - on n'y est pas, et alors il faut revenir dans la langue par defaut
	if (($lang = $contexte_inclus['lang'])
	|| ($GLOBALS['spip_lang'] != ($lang = lire_meta('langue_site')))) {
		include_ecrire('inc_lang.php3');
		lang_select($lang);
		$lang_select = true; // pour lang_dselect en sortie
	}

	$page = obtenir_page ($contexte_inclus, $chemin_cache, $delais,
	$use_cache, $fond, true);

	$page['lang_select'] = $lang_select;

	// Retourner le contenu...
	return $page;

}

//
// Le bouton des administrateurs
//
function admin_page($cached, $texte) {
	if (!$GLOBALS['flag_preserver']
	&& ($admin = $GLOBALS['HTTP_COOKIE_VARS']['spip_admin'])) {
		include_local('inc-admin.php3');
		return calcul_admin_page($cached, $texte);
	}
	return false; // pas de boutons admin
}

// Si l'admin a demande un affichage
function afficher_page_si_demande_admin ($type, $texte, $fichier){
	if (
	$GLOBALS['bouton_admin_debug']
	AND $GLOBALS['var_afficher_debug'] == $type
	AND $GLOBALS['auteur_session']['statut'] == '0minirezo') {
		include_local('inc-admin.php3');
		page_debug($type,$texte,$fichier);
		exit;
	}
}

function cherche_image_nommee($nom) {
	$dossier = 'IMG';
	$formats = array ('gif', 'jpg', 'png');
	while (list(, $format) = each($formats)) {
		$d = "$dossier/$nom.$format";
		if (file_exists($d))
			return ($d);
	}
}

function taches_de_fond() {
	// Gestion des taches de fond ?  toutes les 5 secondes
	// (on mettra 30 s quand on aura prevu la preemption par une image-cron)
	if (!@file_exists('ecrire/data/cron.lock')
	OR (time() - @filemtime('ecrire/data/cron.lock') > 5)) {

		// Si MySQL est out, laisser souffler
		if (!@file_exists('ecrire/data/mysql_out')
		OR (time() - @filemtime('ecrire/data/mysql_out') > 300)) {
			include_ecrire('inc_cron.php3');
			spip_cron();
		}
	}
}

?>
