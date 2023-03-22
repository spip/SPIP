<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Le format souhaite : tmp/cache/ab/cd
 * soit au maximum 16^4 fichiers dans 256 repertoires
 * Attention a modifier simultanement le sanity check de
 * la fonction retire_cache() de inc/invalideur
 *
 * @param array $contexte
 * @param array $page
 * @return string
 */
function generer_nom_fichier_cache($contexte, $page) {
	$u = md5(var_export([$contexte, $page], true));

	return $u . '.cache';
}

/**
 * Calcule le chemin hashe du fichier cache
 * Le format souhaite : tmp/cache/ab/cd
 * soit au maximum 16^4 fichiers dans 256 repertoires
 * mais la longueur est configurable via un define qui permer d'avoir une taille de 16^_CACHE_PROFONDEUR_STOCKAGE
 *
 * Attention a modifier simultanement le sanity check de
 * la fonction retire_cache() de inc/invalideur
 *
 * @param $nom_cache
 * @return string
 */
function cache_chemin_fichier($nom_cache, $ecrire = false) {
	static $l1, $l2;
	if (is_null($l1)) {
		$length = (defined('_CACHE_PROFONDEUR_STOCKAGE') ? min(8, max(_CACHE_PROFONDEUR_STOCKAGE, 2)) : 4);
		$l1 = (int) floor($length / 2);
		$l2 = $length - $l1;
	}
	$d = substr((string) $nom_cache, 0, $l1);
	$u = substr((string) $nom_cache, $l1, $l2);

	if ($ecrire) {
		$rep = sous_repertoire(_DIR_CACHE, '', false, true);
		$rep = sous_repertoire($rep, 'calcul/', false, true);
		$rep = sous_repertoire($rep, $d, false, true);
	}
	else {
		// en lecture on essaye pas de creer les repertoires, on va au plus vite
		$rep = _DIR_CACHE . "calcul/$d/";
	}

	return $rep . $u . '.cache';
}

/**
 * ecrire le cache dans un casier
 *
 * @param string $nom_cache
 * @param $valeur
 * @return bool
 */
function ecrire_cache($nom_cache, $valeur) {
	return ecrire_fichier(cache_chemin_fichier($nom_cache, true), serialize(['nom_cache' => $nom_cache, 'valeur' => $valeur]));
}

/**
 * lire le cache depuis un casier
 *
 * @param string $nom_cache
 * @return mixed
 */
function lire_cache($nom_cache) {
	$tmp = [];
	if (
		file_exists($f = cache_chemin_fichier($nom_cache))
		&& lire_fichier($f, $tmp)
		&& ($tmp = unserialize($tmp))
		&& $tmp['nom_cache'] == $nom_cache
		&& isset($tmp['valeur'])
	) {
		return $tmp['valeur'];
	}

	return false;
}

// Parano : on signe le cache, afin d'interdire un hack d'injection
// dans notre memcache
function cache_signature(&$page) {
	if (!isset($GLOBALS['meta']['cache_signature'])) {
		include_spip('inc/acces');
		ecrire_meta(
			'cache_signature',
			hash('sha256', $_SERVER['DOCUMENT_ROOT'] . ($_SERVER['SERVER_SIGNATURE'] ?? '') . creer_uniqid()),
			'non'
		);
	}

	return crc32($GLOBALS['meta']['cache_signature'] . $page['texte']);
}

/**
 * Faut-il compresser ce cache ? A partir de 16ko ca vaut le coup
 * (pas de passage par reference car on veut conserver la version non compressee
 * pour l'afficher)
 * on positionne un flag gz si on comprime, pour savoir si on doit decompresser ou pas
 *
 * @param array $page
 * @return array
 */
function gzip_page($page) {
	if (function_exists('gzcompress') && strlen((string) $page['texte']) > 16 * 1024) {
		$page['gz'] = true;
		$page['texte'] = gzcompress((string) $page['texte']);
	} else {
		$page['gz'] = false;
	}

	return $page;
}

/**
 * Faut-il decompresser ce cache ?
 * (passage par reference pour alleger)
 * on met a jour le flag gz quand on decompresse, pour ne pas risquer
 * de decompresser deux fois de suite un cache (ce qui echoue)
 *
 * @param array $page
 * @return void
 */
function gunzip_page(&$page) {
	if ($page['gz']) {
		$page['texte'] = gzuncompress($page['texte']);
		$page['gz'] = false; // ne pas gzuncompress deux fois une meme page
	}
}

/**
 * gestion des delais d'expiration du cache...
 * $page passee par reference pour accelerer
 *
 * @param array $page
 * @param int $date
 * @return int
 * 1 si il faut mettre le cache a jour
 * 0 si le cache est valide
 * -1 si il faut calculer sans stocker en cache
 */
function cache_valide(&$page, $date) {
	$now = $_SERVER['REQUEST_TIME'];

	// Apparition d'un nouvel article post-date ?
	if (
		isset($GLOBALS['meta']['post_dates'])
		&& $GLOBALS['meta']['post_dates'] == 'non'
		&& isset($GLOBALS['meta']['date_prochain_postdate'])
		&& $now > $GLOBALS['meta']['date_prochain_postdate']
	) {
		spip_log('Un article post-date invalide le cache');
		include_spip('inc/rubriques');
		calculer_prochain_postdate(true);
	}

	if (defined('_VAR_NOCACHE') && _VAR_NOCACHE) {
		return -1;
	}
	if (isset($GLOBALS['meta']['cache_inhib']) && $_SERVER['REQUEST_TIME'] < $GLOBALS['meta']['cache_inhib']) {
		return -1;
	}
	if (defined('_NO_CACHE')) {
		return (_NO_CACHE == 0 && !isset($page['texte'])) ? 1 : _NO_CACHE;
	}

	// pas de cache ? on le met a jour, sauf pour les bots (on leur calcule la page sans mise en cache)
	if (!$page || !isset($page['texte']) || !isset($page['entetes']['X-Spip-Cache'])) {
		return _IS_BOT ? -1 : 1;
	}

	// controle de la signature
	if ($page['sig'] !== cache_signature($page)) {
		return _IS_BOT ? -1 : 1;
	}

	// #CACHE{n,statique} => on n'invalide pas avec derniere_modif
	// cf. ecrire/public/balises.php, balise_CACHE_dist()
	// Cache invalide par la meta 'derniere_modif'
	// sauf pour les bots, qui utilisent toujours le cache
	if (
		(!isset($page['entetes']['X-Spip-Statique']) || $page['entetes']['X-Spip-Statique'] !== 'oui')
		&& (
			!_IS_BOT
			&& $GLOBALS['derniere_modif_invalide']
			&& isset($GLOBALS['meta']['derniere_modif'])
			&& $date < $GLOBALS['meta']['derniere_modif']
		)
	) {
		return 1;
	}

	// Sinon comparer l'age du fichier a sa duree de cache
	$duree = (int) $page['entetes']['X-Spip-Cache'];
	$cache_mark = ($GLOBALS['meta']['cache_mark'] ?? 0);
	if ($duree == 0) {  #CACHE{0}
	return -1;
	} // sauf pour les bots, qui utilisent toujours le cache
	else {
		if (
			!_IS_BOT && $date + $duree < $now
			|| $date < $cache_mark
		) {
			return _IS_BOT ? -1 : 1;
		} else {
			return 0;
		}
	}
}

/**
 * Creer le fichier cache
 * Passage par reference de $page par souci d'economie
 *
 * @param array $page
 * @param string $chemin_cache
 * @return void
 */
function creer_cache(&$page, &$chemin_cache) {

	// Ne rien faire si on est en preview, debug, ou si une erreur
	// grave s'est presentee (compilation du squelette, MySQL, etc)
	// le cas var_nocache ne devrait jamais arriver ici (securite)
	// le cas spip_interdire_cache correspond a une ereur SQL grave non anticipable
	if (
		defined('_VAR_NOCACHE') && _VAR_NOCACHE
		|| defined('spip_interdire_cache')
	) {
		return;
	}

	// Si la page c1234 a un invalideur de session 'zz', sauver dans
	// 'tmp/cache/MD5(chemin_cache)_zz'
	if (
		isset($page['invalideurs'])
		&& isset($page['invalideurs']['session'])
	) {
		// on verifie que le contenu du chemin cache indique seulement
		// "cache sessionne" ; sa date indique la date de validite
		// des caches sessionnes
		if (!$tmp = lire_cache($chemin_cache)) {
			spip_log('Creation cache sessionne ' . $chemin_cache);
			$tmp = [
				'invalideurs' => ['session' => ''],
				'lastmodified' => $_SERVER['REQUEST_TIME']
			];
			ecrire_cache($chemin_cache, $tmp);
		}
		$chemin_cache = generer_nom_fichier_cache(
			['chemin_cache' => $chemin_cache],
			['session' => $page['invalideurs']['session']]
		);
	}

	// ajouter la date de production dans le cache lui meme
	// (qui contient deja sa duree de validite)
	$page['lastmodified'] = $_SERVER['REQUEST_TIME'];

	// compresser le contenu si besoin
	$pagez = gzip_page($page);

	// signer le contenu
	$pagez['sig'] = cache_signature($pagez);

	// l'enregistrer, compresse ou non...
	$ok = ecrire_cache($chemin_cache, $pagez);

	spip_log((_IS_BOT ? 'Bot:' : '') . "Creation du cache $chemin_cache pour "
		. $page['entetes']['X-Spip-Cache'] . ' secondes' . ($ok ? '' : ' (erreur!)'), _LOG_INFO);

	// Inserer ses invalideurs
	include_spip('inc/invalideur');
	maj_invalideurs($chemin_cache, $page);
}


/**
 * purger un petit cache (tidy ou recherche) qui ne doit pas contenir de
 * vieux fichiers ; (cette fonction ne sert que dans des plugins obsoletes)
 *
 * @param string $prefix
 * @param int $duree
 * @return void
 */
function nettoyer_petit_cache($prefix, $duree = 300) {
	// determiner le repertoire a purger : 'tmp/CACHE/rech/'
	$dircache = sous_repertoire(_DIR_CACHE, $prefix);
	if (spip_touch($dircache . 'purger_' . $prefix, $duree, true)) {
		foreach (preg_files($dircache, '[.]txt$') as $f) {
			if ($_SERVER['REQUEST_TIME'] - (@file_exists($f) ? @filemtime($f) : 0) > $duree) {
				spip_unlink($f);
			}
		}
	}
}


/**
 * Interface du gestionnaire de cache
 * Si son 3e argument est non vide, elle passe la main a creer_cache
 * Sinon, elle recoit un contexte (ou le construit a partir de REQUEST_URI)
 * et affecte les 4 autres parametres recus par reference:
 * - use_cache qui vaut
 *     -1 s'il faut calculer la page sans la mettre en cache
 *      0 si on peut utiliser un cache existant
 *      1 s'il faut calculer la page et la mettre en cache
 * - chemin_cache qui est le chemin d'acces au fichier ou vide si pas cachable
 * - page qui est le tableau decrivant la page, si le cache la contenait
 * - lastmodified qui vaut la date de derniere modif du fichier.
 * Elle retourne '' si tout va bien
 * un message d'erreur si le calcul de la page est totalement impossible
 *
 * @param array $contexte
 * @param int $use_cache
 * @param string $chemin_cache
 * @param array $page
 * @param int $lastmodified
 * @return string|void
 */
function public_cacher_dist($contexte, &$use_cache, &$chemin_cache, &$page, &$lastmodified) {

	# fonction de cache minimale : dire "non on ne met rien en cache"
	# $use_cache = -1; return;

	// Second appel, destine a l'enregistrement du cache sur le disque
	if (isset($chemin_cache)) {
		creer_cache($page, $chemin_cache);
		return;
	}

	// Toute la suite correspond au premier appel
	$contexte_implicite = $page['contexte_implicite'];

	// Cas ignorant le cache car completement dynamique
	if (
		!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST'
		|| _request('connect')
	) {
		$use_cache = -1;
		$lastmodified = 0;
		$chemin_cache = '';
		$page = [];

		return;
	}

	// Controler l'existence d'un cache nous correspondant
	$chemin_cache = generer_nom_fichier_cache($contexte, $page);
	$lastmodified = 0;

	// charger le cache s'il existe (et si il a bien le bon hash = anticollision)
	if (!$page = lire_cache($chemin_cache)) {
		$page = [];
	}

	// s'il est sessionne, charger celui correspondant a notre session
	if (
		isset($page['invalideurs'])
		&& isset($page['invalideurs']['session'])
	) {
		$chemin_cache_session = generer_nom_fichier_cache(
			['chemin_cache' => $chemin_cache],
			['session' => spip_session()]
		);
		if (
			($page_session = lire_cache($chemin_cache_session)) && $page_session['lastmodified'] >= $page['lastmodified']
		) {
			$page = $page_session;
		} else {
			$page = [];
		}
	}


	// Faut-il effacer des pages invalidees (en particulier ce cache-ci) ?
	// ne le faire que si la base est disponible
	if (isset($GLOBALS['meta']['invalider']) && spip_connect()) {
		include_spip('inc/invalideur');
		retire_caches($chemin_cache);
		# API invalideur inutile
		supprimer_fichier(_DIR_CACHE . $chemin_cache);
		if (isset($chemin_cache_session) && $chemin_cache_session) {
			supprimer_fichier(_DIR_CACHE . $chemin_cache_session);
		}
	}

	// Si un calcul, recalcul [ou preview, mais c'est recalcul] est demande,
	// on supprime le cache
	if (
		defined('_VAR_MODE') &&
		_VAR_MODE &&
		(isset($_COOKIE['spip_session']) ||
			isset($_COOKIE['spip_admin']) ||
			@file_exists(_ACCESS_FILE_NAME))
	) {
		$page = ['contexte_implicite' => $contexte_implicite]; // ignorer le cache deja lu
		include_spip('inc/invalideur');
		retire_caches($chemin_cache); # API invalideur inutile
		supprimer_fichier(_DIR_CACHE . $chemin_cache);
		if (isset($chemin_cache_session) && $chemin_cache_session) {
			supprimer_fichier(_DIR_CACHE . $chemin_cache_session);
		}
	}

	// $delais par defaut
	// pour toutes les pages sans #CACHE{} hors modeles/ et espace privé
	// qui sont a cache nul par defaut
	if (!isset($GLOBALS['delais'])) {
		if (!defined('_DUREE_CACHE_DEFAUT')) {
			define('_DUREE_CACHE_DEFAUT', 24 * 3600);
		}
		$GLOBALS['delais'] = _DUREE_CACHE_DEFAUT;
	}

	// determiner la validite de la page
	if ($page) {
		$use_cache = cache_valide($page, $page['lastmodified'] ?? 0);
		// le contexte implicite n'est pas stocke dans le cache, mais il y a equivalence
		// par le nom du cache. On le reinjecte donc ici pour utilisation eventuelle au calcul
		$page['contexte_implicite'] = $contexte_implicite;
		if (!$use_cache) {
			// $page est un cache utilisable
			gunzip_page($page);

			return;
		}
	} else {
		$page = ['contexte_implicite' => $contexte_implicite];
		$use_cache = cache_valide($page, 0); // fichier cache absent : provoque le calcul
	}

	// Si pas valide mais pas de connexion a la base, le garder quand meme
	if (!spip_connect()) {
		if (isset($page['texte'])) {
			gunzip_page($page);
			$use_cache = 0;
		} else {
			spip_log("Erreur base de donnees, impossible utiliser $chemin_cache");
			include_spip('inc/minipres');

			return minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'), ['status' => 503]);
		}
	}

	if ($use_cache < 0) {
		$chemin_cache = '';
	}

	return;
}
