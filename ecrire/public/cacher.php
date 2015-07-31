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

//
// Le format souhaite : "a/bout-d-url.md5" (.gz s'ajoutera pour les gros caches)
// Attention a modifier simultanement le sanity check de
// la fonction retire_cache() de inc/invalideur
//
// http://doc.spip.org/@generer_nom_fichier_cache
function generer_nom_fichier_cache($contexte, $page) {

	$cache = $page . '-';
	foreach ($contexte as $var=>$val) {
		$val = is_array($val) ? var_export($val,true) : strval($val);
		$cache .= str_replace('-', '_', $val) . '-' ;
	}

	$cache = str_replace('/', '_', rawurlencode($cache));

	if (strlen($cache) > 24) {
		$cache = preg_replace('/([a-zA-Z]{1,3})[^-_]*[-_]/', '\1-', $cache);
		$cache = substr($cache, 0, 24);
	}

	// Morceau de md5 selon HOST, $dossier_squelettes et $marqueur
	// permet de changer le nom du cache en changeant ceux-ci
	// donc, par exemple, de gerer differents dossiers de squelettes
	// en parallele, ou de la "personnalisation" via un marqueur (dont la
	// composition est totalement libre...)
	$md_cache = md5($page . ' '
		. var_export($contexte,true)
		. $_SERVER['HTTP_HOST'] . ' '
		. $GLOBALS['dossier_squelettes'] . ' '
		. (isset($GLOBALS['marqueur']) ?  $GLOBALS['marqueur'] : '')
	);

	$cache .= '-'.substr($md_cache, 1, 32-strlen($cache));

	// Sous-repertoires 0...9a..f ; ne pas prendre la base _DIR_CACHE
	$rep = _DIR_CACHE;

	if(!@file_exists($rep)) {
		$rep = preg_replace(','._DIR_TMP.',', '', $rep);
		$rep = sous_repertoire(_DIR_TMP, $rep, false,true);
	}
	$subdir = sous_repertoire($rep, substr($md_cache, 0, 1), true,true);
	return $subdir.$cache;
}

// Faut-il compresser ce cache ? A partir de 16ko ca vaut le coup
// (pas de passage par reference car on veut conserver la version non compressee
// pour l'afficher)
// http://doc.spip.org/@gzip_page
function gzip_page($page) {
	if ($GLOBALS['flag_gz'] AND strlen($page['texte']) > 16*1024) {
		$page['gz'] = true;
		$page['texte'] = gzcompress($page['texte']);
	} else {
		$page['gz'] = false;
	}
	return $page;
}

// Faut-il decompresser ce cache ?
// (passage par reference pour alleger)
// http://doc.spip.org/@gunzip_page
function gunzip_page(&$page) {
	if ($page['gz'])
		$page['texte'] = gzuncompress($page['texte']);
}

/**
 * gestion des delais d'expiration du cache...
 * $page passee par reference pour accelerer
 * 
 * La fonction retourne 
 * 1 si il faut mettre le cache a jour
 * 0 si le cache est valide
 * -1 si il faut calculer sans stocker en cache
 *
 * @param array $page
 * @param int $date
 * @return -1/0/1
 */
/// http://doc.spip.org/@cache_valide
function cache_valide(&$page, $date) {

	if (isset($GLOBALS['var_nocache']) AND $GLOBALS['var_nocache']) return -1;
	if (defined('_NO_CACHE')) return (_NO_CACHE==0 AND !$page)?1:_NO_CACHE;
	if (!$page) return 1;

	// #CACHE{n,statique} => on n'invalide pas avec derniere_modif
	// cf. ecrire/public/balises.php, balise_CACHE_dist()
	if (!isset($page['entetes']['X-Spip-Statique']) OR $page['entetes']['X-Spip-Statique'] !== 'oui') {

		// Cache invalide par la meta 'derniere_modif'
		if ($GLOBALS['derniere_modif_invalide']
		AND $date < $GLOBALS['meta']['derniere_modif'])
			return 1;

		// Apparition d'un nouvel article post-date ?
		if ($GLOBALS['meta']['post_dates'] == 'non'
		AND isset($GLOBALS['meta']['date_prochain_postdate'])
		AND time() > $GLOBALS['meta']['date_prochain_postdate']) {
			spip_log('Un article post-date invalide le cache');
			include_spip('inc/rubriques');
			ecrire_meta('derniere_modif', time());
			calculer_prochain_postdate();
			return 1;
		}

	}

	// Sinon comparer l'age du fichier a sa duree de cache
	$duree = intval($page['entetes']['X-Spip-Cache']);
	if ($duree == 0)  #CACHE{0}
		return -1;
	else if ($date + $duree < time())
		return 1;
	else
		return 0;
}

function cache_sessionne($chemin_cache, $creer=false) {
	$fs = substr(md5($chemin_cache),0,8);
	$a = substr($fs,0,1);
	$b = sous_repertoire(_DIR_CACHE, $a);
	if ($creer)
		sous_repertoire($b, $fs);
	return $a.'/'.$fs.'/_';
}

// Creer le fichier cache
# Passage par reference de $page par souci d'economie
// http://doc.spip.org/@creer_cache
function creer_cache(&$page, &$chemin_cache) {

	// Si la page c1234 a un invalideur de session 'zz', sauver dans
	// 'tmp/cache/MD5(chemin_cache)/_zz'
	// en prenant soin de supprimer un eventuel cache non-sessionne
	// si l'ajout de #SESSION dans le squelette est recent
	if (isset($page['invalideurs'])
	AND isset($page['invalideurs']['session'])) {
		supprimer_fichier(_DIR_CACHE . $chemin_cache);
		$chemin_cache = cache_sessionne($chemin_cache, true).$page['invalideurs']['session'];
		
	}

	// l'enregistrer, compresse ou non...
	$ok = ecrire_fichier(_DIR_CACHE . $chemin_cache,
		serialize(gzip_page($page)));

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
				spip_unlink($f);
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
// Elle retourne '' si tout va bien
// un message d'erreur si le calcul de la page est totalement impossible

// http://doc.spip.org/@public_cacher_dist
function public_cacher_dist($contexte, &$use_cache, &$chemin_cache, &$page, &$lastmodified) {

	// Second appel, destine a l'enregistrement du cache sur le disque
	if (isset($chemin_cache)) return creer_cache($page, $chemin_cache);

	// Toute la suite correspond au premier appel

	// Cas ignorant le cache car completement dynamique
	if ($_SERVER['REQUEST_METHOD'] == 'POST'
	OR (substr($page,0,8)=='modeles/') 
	OR (_request('connect'))
// Mode auteur authentifie appelant de ecrire/ : il ne faut rien lire du cache
// et n'y ecrire que la compilation des squelettes (pas les pages produites)
// car les references aux repertoires ne sont pas relatifs a l'espace public
	OR test_espace_prive()) {
		$use_cache = -1;
		$lastmodified = 0;
		$chemin_cache = "";
		$page = array();
		return;
	}

	// Controler l'existence d'un cache nous correspondant, dans les
	// deux versions possibles : session ou non
	$chemin_cache = generer_nom_fichier_cache($contexte, $page);
	if (@file_exists(_DIR_CACHE . ($f = $chemin_cache))
	OR $fs = @file_exists(_DIR_CACHE . ($f = cache_sessionne($f).spip_session()))
	) {
		$lastmodified = @filemtime(_DIR_CACHE . $f);
	} else
		$lastmodified = 0;

	// HEAD : cas sans jamais de calcul pour raisons de performance
	if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
		$use_cache = 0;
		$page = array();
		return;
	}

	// Faut-il effacer des pages invalidees (en particulier ce cache-ci) ?
	if (isset($GLOBALS['meta']['invalider'])) {
		// ne le faire que si la base est disponible
		if (spip_connect()) {
			include_spip('inc/invalideur');
			retire_caches($f);
		}
	}

	// Si un calcul, recalcul [ou preview, mais c'est recalcul] est demande,
	// on supprime le cache, et ses voisins dans le cas des sessions
	if ($GLOBALS['var_mode'] &&
		(isset($_COOKIE['spip_session'])
		|| isset($_COOKIE['spip_admin'])
		|| @file_exists(_ACCESS_FILE_NAME))
	) {
		supprimer_fichier(_DIR_CACHE . $f);
		if (in_array($GLOBALS['var_mode'], array('calcul', 'recalcul'))
		AND $fs) {
			include_spip('inc/invalideur');
			purger_repertoire(dirname(_DIR_CACHE.$f));
		}
	}

	// $delais par defaut (pour toutes les pages sans #CACHE{})
	if (!isset($GLOBALS['delais'])) {
		define('_DUREE_CACHE_DEFAUT', 24*3600);
		$GLOBALS['delais'] = _DUREE_CACHE_DEFAUT;
	}

	// Lire le fichier cache et determiner sa validite
	if ($lastmodified
	AND lire_fichier(_DIR_CACHE . $f, $page)) {
		$page = @unserialize($page);
		$use_cache = cache_valide($page, $lastmodified);
		if (!$use_cache) {
			// $page est un cache utilisable
			gunzip_page($page);
			return;
		}
	} else {
		$page = array();
		$use_cache = cache_valide($page,0); // fichier cache absent : provoque le calcul
	}

	// Si pas valide mais pas de connexion a la base, le garder quand meme
	if (!spip_connect()) {
		if ($lastmodified)
			$use_cache = 0;
		else {
			spip_log("Erreur base de donnees, impossible utiliser $chemin_cache");
			include_spip('inc/minipres');
			return minipres(_T('info_travaux_titre'),  _T('titre_probleme_technique'));
		}
	}

	if ($use_cache < 0) $chemin_cache = '';
	return;
}

?>
