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
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CACHE")) return;
define("_INC_CACHE", "1");


//
// Calcul du nom du fichier cache
//

function nettoyer_uri() {
	$fichier_requete = $GLOBALS['REQUEST_URI'];
	$fichier_requete = eregi_replace
		('[?&](PHPSESSID|(var_[^=&]*))=[^&]*',
		'', $fichier_requete);
	return $fichier_requete;
}

//
// Le format souhaite : "CACHE/a/(8400/)bout-d-url.md5(.gz)"
// Attention a modifier simultanement le sanity check de
// la fonction retire_cache()
//
function generer_nom_fichier_cache($contexte='', $fond='') {
	global $delais;
	global $flag_gz;

	if ($delais == 0) return '';

	if (!$contexte) {
		$fichier_requete = nettoyer_uri();
	} else {
		$fichier_requete = $fond;
		foreach ($contexte as $var=>$val)
			$fichier_requete .= "&$var=$val";
	}

	$md_cache = md5($fichier_requete);

	$fichier_cache = ereg_replace('^/+', '', $fichier_requete);
	$fichier_cache = ereg_replace('\.[a-zA-Z0-9]*', '', $fichier_cache);
	$fichier_cache = ereg_replace('&[^&]+=([^&]+)', '&\1', $fichier_cache);
	$fichier_cache = rawurlencode(strtr($fichier_cache, '/&-', '--_'));
	if (strlen($fichier_cache) > 24)
		$fichier_cache = substr(ereg_replace('([a-zA-Z]{1,3})[^-]*-',
		'\1-', $fichier_cache), -22);

	// Pour la page d'accueil
	if (!$fichier_cache)
		$fichier_cache = 'INDEX-';

	// morceau de md5
	$fichier_cache .= '.'.substr($md_cache, 1, 8);

	// Sous-repertoires 0...9a..f/
	$subdir = creer_repertoire(_DIR_CACHE, substr($md_cache, 0, 1));
	// Sous-sous-repertoires delais/ (inutile avec l'invalidation par 't')
	# $subdir2 = creer_repertoire("CACHE/$subdir", $delais);

	include_ecrire('inc_acces.php3');
	verifier_htaccess(_DIR_CACHE);

	$gzip = $flag_gz ? '.gz' : '';

	return _DIR_CACHE . $subdir.$subdir2.$fichier_cache.$gzip;
}

//
// Destruction des fichiers caches invalides
//
// NE PAS appeler ces fonctions depuis l'espace prive 
// car openbase_dir peut leur interdire l'acces au repertoire de cache

// Securite : est sur que c'est un cache
function retire_cache($cache) {
	if (!_DIR_RESTREINT) return;
	if (preg_match('|^' . _DIR_CACHE .
		"([0-9a-f]/)?([0-9]+/)?[^.][\-_\%0-9a-z]+\.[0-9a-f]+(\.gz)?$|i",
		       $cache)) {
		// supprimer le fichier (de facon propre)
		supprimer_fichier($cache);
	} else
		spip_log("Impossible de retirer $cache");
}

// Supprimer les caches marques "x"
function retire_caches($chemin = '') {
	if (!_DIR_RESTREINT) return;

	// recuperer la liste des caches voues a la suppression
	$suppr = array();

	// En priorite le cache qu'on appelle maintenant
	if ($chemin) {
		$q = spip_query("SELECT fichier FROM spip_caches
		WHERE fichier = '".addslashes($chemin)."' AND type='x' LIMIT 0,1");
		if ($r = spip_fetch_array($q))
			$suppr[$r['fichier']] = true;
	}

	// Et puis une centaine d'autres
	if (lire_meta('invalider_caches')) {
		$compte = 1;
		effacer_meta('invalider_caches'); # concurrence
		ecrire_metas();

		$q = spip_query("SELECT fichier FROM spip_caches
		WHERE type='x' LIMIT 0,100");
		while ($r = spip_fetch_array($q)) {
			$compte ++;	# compte le nombre de resultats vus (y compris doublons)
			$suppr[$r['fichier']] = true;
		}
	}


	if ($n = count($suppr)) {
		spip_log ("Retire $n caches");
		foreach ($suppr as $cache => $ignore)
			retire_cache($cache);
		spip_query("DELETE FROM spip_caches WHERE "
		.calcul_mysql_in('fichier', "'".join("','",array_keys($suppr))."'") );
	}

	// Si on a regarde (compte > 0), signaler s'il reste des caches invalides
	if ($compte > 0) {
		if ($compte > 100) # s'il y en a 101 c'est qu'on n'a pas fini
			ecrire_meta('invalider_caches', 'oui');
		else
			effacer_meta('invalider');
		ecrire_metas();
	}
}

//
// Retourne 0 s'il faut calculer le cache, 1 si on peut l'utiliser
//
function utiliser_cache($chemin_cache, $delais) {
	global $_SERVER;

	// ne jamais calculer pour les moteurs de recherche, proxies...
	if ($_SERVER['REQUEST_METHOD'] == 'HEAD')
		return 1;

	//  calcul par forcage
	if ($GLOBALS['var_mode'] &&
		($GLOBALS['_COOKIE']['spip_session']
		|| $GLOBALS['_COOKIE']['spip_admin']
		|| @file_exists(_ACCESS_FILE_NAME))) # insuffisant...
		return 0;

	// calcul par absence
	if (!@file_exists($chemin_cache)) return 0;

	// calcul par obsolescence
	return ((time() - @filemtime($chemin_cache)) > $delais) ? 0 : 1;
}


// Obsolete ?  Utilisee pour vider le cache depuis l'espace prive
// (ou juste les squelettes si un changement de config le necessite)
function purger_repertoire($dir, $age='ignore', $regexp = '') {
	$handle = @opendir($dir);
	if (!$handle) return;

	while (($fichier = @readdir($handle)) != '') {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') continue;
		if ($regexp AND !ereg($regexp, $fichier)) continue;
		$chemin = "$dir/$fichier";
		if (is_file($chemin))
			@unlink($chemin);
		else if (is_dir($chemin))
			if ($fichier != 'CVS')
				purger_repertoire($chemin);
	}
	closedir($handle);
}

function purger_cache() {
	spip_log('vider le cache');
	include_ecrire('inc_invalideur.php3');
	supprime_invalideurs();
	purger_repertoire(_DIR_CACHE, 0);
}

function purger_squelettes() {
	spip_log('effacer les squelettes compiles');
	purger_repertoire(_DIR_CACHE, 0, '^skel_');
}


// Determination du fichier cache (si besoin)
function determiner_cache($delais, &$use_cache, &$chemin_cache) {
	global $_POST;

	// Le fichier cache est-il valide ?
	if ($delais == 0 OR count($_POST)) {
		$use_cache = 0;
		$chemin_cache = '';
	} else {
		$use_cache = utiliser_cache($chemin_cache, $delais);
	}

	// Sinon, tester qu'on a la connexion a la base
	if (!$use_cache) {
		include_local(_FILE_CONNECT);
		if (!$GLOBALS['db_ok']) {
			if ($chemin_cache AND @file_exists($chemin_cache)) {
				$use_cache = 1;
			}
			else {
				spip_log("Erreur base de donnees & "
					. "impossible utiliser $chemin_cache");
				if (!$GLOBALS['flag_preserver']) {
					include_ecrire('inc_presentation.php3');
					install_debut_html(_T('info_travaux_titre'));
					echo _T('titre_probleme_technique');
					install_fin_html();
				}
				exit;
			}
		}
	}
}

?>
