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
// Le format souhaite : "a/bout-d-url.md5" (.gz s'ajoutera pour les gros caches)
// Attention a modifier simultanement le sanity check de
// la fonction retire_cache() de inc/invalideur
//
// http://doc.spip.org/@generer_nom_fichier_cache
function generer_nom_fichier_cache($contexte) {

	if ($contexte === NULL) {
		$fichier_requete = nettoyer_uri();
	} else {
		$fichier_requete = '';
		foreach ($contexte as $var=>$val)
			$fichier_requete .= "&$var=$val";
	}

	$fichier_cache = preg_replace(',^/+,', '', $fichier_requete);
	$fichier_cache = preg_replace(',\.[a-zA-Z0-9]*,', '', $fichier_cache);
	$fichier_cache = preg_replace(',&[^&]+=([^&]+),', '&\1', $fichier_cache);
	$fichier_cache = rawurlencode(strtr($fichier_cache, '/&-', '--_'));
	if (strlen($fichier_cache) > 24)
		$fichier_cache = substr(preg_replace('/([a-zA-Z]{1,3})[^-]*-/',
		'\1-', $fichier_cache), -22);

	// Pour la page d'accueil
	if (!$fichier_cache)
		$fichier_cache = 'INDEX-';

	// Morceau de md5 selon HOST, $dossier_squelettes, $fond et $marqueur
	// permet de changer de chemin_cache si l'on change l'un de ces elements
	// donc, par exemple, de gerer differents dossiers de squelettes
	// en parallele, ou de la "personnalisation" via un marqueur (dont la
	// composition est totalement libre...)
	$md_cache = md5(
		$fichier_requete . ' '
		. $_SERVER['HTTP_HOST'] . ' '
		. $GLOBALS['fond'] . ' '
		. $GLOBALS['dossier_squelettes'] . ' '
		. (isset($GLOBALS['marqueur']) ?  $GLOBALS['marqueur'] : '')
	);
	$fichier_cache .= '.'.substr($md_cache, 1, 8);

	// Sous-repertoires 0...9a..f ; ne pas prendre la base _DIR_CACHE
	$repertoire = _DIR_CACHE;
	if(!@file_exists($repertoire)) {
		$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
		$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
	}
	$subdir = sous_repertoire($repertoire, substr($md_cache, 0, 1), true);

	return $subdir.$fichier_cache;
}

// Faut-il compresser ce cache ? A partir de 16ko ca vaut le coup
// http://doc.spip.org/@cache_gz
function cache_gz($page) {
	if ($GLOBALS['flag_gz'] AND strlen($page['texte']) > 16*1024)
		return '.gz';
	else
		return '';
}

// gestion des delais d'expiration du cache
// $page passee par reference pour accelerer
// http://doc.spip.org/@cache_valide
function cache_valide(&$page, $date) {

	if (!$page) return 1;

	// Cache invalide par la meta 'derniere_modif'
	if ($GLOBALS['derniere_modif_invalide']
	AND $date < $GLOBALS['meta']['derniere_modif'])
		return 1;

	// Sinon comparer l'age du fichier a sa duree de cache
	$duree = intval($page['entetes']['X-Spip-Cache']);
	if ($duree == 0)  #CACHE{0}
		return -1;
	else if ($date + $duree < time())
		return 1;
	else
		return 0;
}


// Creer le fichier cache
# Passage par reference de $page par souci d'economie
// http://doc.spip.org/@creer_cache
function creer_cache(&$page, &$chemin_cache) {

	// Normaliser le chemin et supprimer l'eventuelle contrepartie -gz du cache
	$chemin_cache = str_replace('.gz', '', $chemin_cache);
	$gz = cache_gz($page);
	supprimer_fichier(_DIR_CACHE . $chemin_cache . ($gz ? '' : '.gz'));

	// l'enregistrer, compresse ou non...
	$chemin_cache .= $gz;
	$ok = ecrire_fichier(_DIR_CACHE . $chemin_cache, serialize($page));

	spip_log("Creation du cache $chemin_cache pour "
		. $page['entetes']['X-Spip-Cache']." secondes". ($ok?'':' (erreur!)'));

	// Inserer ses invalideurs
	include_spip('inc/invalideur');
	maj_invalideurs($chemin_cache, $page);

}


// purger un petit cache (tidy ou recherche) qui ne doit pas contenir de
// vieux fichiers
// http://doc.spip.org/@nettoyer_petit_cache
function nettoyer_petit_cache($prefix, $duree = 300) {
	// determiner le repertoire a purger : 'tmp/CACHE/rech/'
	$dircache = sous_repertoire(_DIR_CACHE,$prefix);
	if (spip_touch($dircache.'purger_'.$prefix, $duree, true)) {
		foreach (preg_files($dircache,'[.]txt$') as $f) {
			if (time() - (@file_exists($f)?@filemtime($f):0) > $duree)
				@unlink($f);
		}
	}
}


// Interface du gestionnaire de cache
// Si son 3e argument est non vide, elle passe la main a creer_cache
// Sinon, elle recoit un contexte (ou le construit a partir de REQUEST_URI)
// et affecte les 4 autres parametres recus par reference:
// - use_cache qui vaut
//     -1 s'il faut calculer la page sans la mettre en cache
//      0 si on peut utiliser un cache existant
//      1 s'il faut calculer la page et la mettre en cache
// - chemin_cache qui est le chemin d'acces au fichier ou vide si pas cachable
// - page qui est le tableau decrivant la page, si le cache la contenait
// - lastmodified qui vaut la date de derniere modif du fichier.

// http://doc.spip.org/@public_cacher_dist
function public_cacher_dist($contexte, &$use_cache, &$chemin_cache, &$page, &$lastmodified) {

	// Second appel, destine a l'enregistrement du cache sur le disque
	if ($chemin_cache) return creer_cache($page, $chemin_cache);

	// Toute la suite correspond au premier appel

	// Cas ignorant le cache car complement dynamique
	if ($_SERVER['REQUEST_METHOD'] == 'POST'
	OR substr($contexte['fond'],0,8)=='modeles/') {
		$use_cache = -1;
		$lastmodified = 0;
		$chemin_cache = "";
		$page = array();
		return;
	}

	$chemin_cache = generer_nom_fichier_cache($contexte);
	if ($GLOBALS['flag_gz'] AND @file_exists(_DIR_CACHE.$chemin_cache.'.gz'))
		$chemin_cache .= '.gz';

	// HEAD : cas sans jamais de calcul pour raisons de performance
	if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
		$use_cache = 0;
		$page = array();
		$lastmodified = @file_exists(_DIR_CACHE . $chemin_cache) ?
			@filemtime(_DIR_CACHE . $chemin_cache) : 0;
		return;
	}

	// Faut-il effacer des pages invalidees (en particulier ce cache-ci) ?
	if (isset($GLOBALS['meta']['invalider'])) {
		// ne le faire que si la base est disponible
		if (spip_connect()) {
			include_spip('inc/invalideur');
			retire_caches($chemin_cache);
		}
	}

	// Cas sans jamais de cache pour raison interne
	if (isset($GLOBALS['var_mode']) &&
		(isset($_COOKIE['spip_session'])
		|| isset($_COOKIE['spip_admin'])
		|| @file_exists(_ACCESS_FILE_NAME))) {
			supprimer_fichier(_DIR_CACHE . $chemin_cache);
	}

	// $delais par defaut (pour toutes les pages sans #CACHE{})
	if (!isset($GLOBALS['delais'])) $GLOBALS['delais'] = 3600;

	// Lire le fichier cache et determiner sa validite
	if (lire_fichier(_DIR_CACHE . $chemin_cache, $page)) {
		$lastmodified = @file_exists(_DIR_CACHE . $chemin_cache) ?
			@filemtime(_DIR_CACHE . $chemin_cache) : 0;
		$page = @unserialize($page);
		$use_cache = cache_valide($page, $lastmodified);
		if (!$use_cache) return; // cache utilisable
	} else
		$use_cache = 1; // fichier cache absent : provoque le calcul

	// Si pas valide mais pas de connexion a la base, le garder quand meme
	if (!spip_connect()) {
		if (file_exists(_DIR_CACHE . $chemin_cache))
			$use_cache = 0;
		else {
			spip_log("Erreur base de donnees, impossible utiliser $chemin_cache");
			include_spip('inc/minipres');
			minipres(_T('info_travaux_titre'),  _T('titre_probleme_technique'));
		}
	}

	if ($use_cache < 0) $chemin_cache = '';
	return;
}

?>
