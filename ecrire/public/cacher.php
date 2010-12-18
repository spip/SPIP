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

//
// Le format souhaite : "a/bout-d-url.md5" (.gz s'ajoutera pour les gros caches)
// Attention a modifier simultanement le sanity check de
// la fonction retire_cache() de inc/invalideur
//
// http://doc.spip.org/@generer_nom_fichier_cache
function generer_nom_fichier_cache($contexte, $page) {

	$cache = $page['contexte_implicite']['cache'] . '-';

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
	$md_cache = md5(
		var_export($page['contexte_implicite'],true) . ' '
		. var_export($contexte,true)
	);

	$cache .= '-'.substr($md_cache, 1, 32-strlen($cache));

	// Sous-repertoires 0...9a..f ; ne pas prendre la base _DIR_CACHE
	$rep = _DIR_CACHE;

	$rep = sous_repertoire($rep, '', false,true);
	$subdir = sous_repertoire($rep, substr($md_cache, 0, 1), true,true);
	return $subdir.$cache;
}

// Faut-il compresser ce cache ? A partir de 16ko ca vaut le coup
// (pas de passage par reference car on veut conserver la version non compressee
// pour l'afficher)
// http://doc.spip.org/@gzip_page
function gzip_page($page) {
	if (function_exists('gzcompress') AND strlen($page['texte']) > 16*1024) {
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
	if ($page['gz']) {
		$page['texte'] = gzuncompress($page['texte']);
		$page['gz'] = false; // ne pas gzuncompress deux fois une meme page
	}
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
	if (defined('_NO_CACHE')) return (_NO_CACHE==0 AND !isset($page['texte']))?1:_NO_CACHE;
	if (!$page OR !isset($page['texte']) OR !isset($page['entetes']['X-Spip-Cache'])) return 1;

	// #CACHE{n,statique} => on n'invalide pas avec derniere_modif
	// cf. ecrire/public/balises.php, balise_CACHE_dist()
	if (!isset($page['entetes']['X-Spip-Statique']) OR $page['entetes']['X-Spip-Statique'] !== 'oui') {

		// Cache invalide par la meta 'derniere_modif'
		// sauf pour les bots, qui utilisent toujours le cache
		if (!_IS_BOT
		AND $GLOBALS['derniere_modif_invalide']
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

// Creer le fichier cache
# Passage par reference de $page par souci d'economie
// http://doc.spip.org/@creer_cache
function creer_cache(&$page, &$chemin_cache) {

	// Ne rien faire si on est en preview, debug, ou si une erreur
	// grave s'est presentee (compilation du squelette, MySQL, etc)
	// le cas var_nocache ne devrait jamais arriver ici (securite)
	// le cas spip_interdire_cache correspond a une ereur SQL grave non anticipable
	if ((isset($GLOBALS['var_nocache']) AND $GLOBALS['var_nocache'])
		OR defined('spip_interdire_cache'))
		return;

	// Si la page c1234 a un invalideur de session 'zz', sauver dans
	// 'tmp/cache/MD5(chemin_cache)_zz'
	if (isset($page['invalideurs'])
	AND isset($page['invalideurs']['session'])) {
		// on verifie que le contenu du chemin cache indique seulement
		// "cache sessionne" ; sa date indique la date de validite
		// des caches sessionnes
		if (!lire_fichier(_DIR_CACHE . $chemin_cache, $tmp)
		OR !$tmp = @unserialize($tmp)) {
			spip_log('Creation cache sessionne '.$chemin_cache);
			$tmp = array(
				'invalideurs' => array('session' => ''),
				'lastmodified' => time()
			);
			ecrire_fichier(_DIR_CACHE . $chemin_cache, serialize($tmp));
		}
		$chemin_cache .= '_'.$page['invalideurs']['session'];
	}

	// ajouter la date de production dans le cache lui meme
	// (qui contient deja sa duree de validite)
	$page['lastmodified'] = time();


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
// vieux fichiers ; (cette fonction ne sert que dans des plugins obsoletes)
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
	$contexte_implicite = $page['contexte_implicite'];

	// Cas ignorant le cache car completement dynamique
	if ($_SERVER['REQUEST_METHOD'] == 'POST'
	OR (substr($contexte_implicite['cache'],0,8)=='modeles/') 
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

	// Controler l'existence d'un cache nous correspondant
	$chemin_cache = generer_nom_fichier_cache($contexte, $page);
	$lastmodified = 0;

	// charger le cache s'il existe
	if (lire_fichier(_DIR_CACHE . $chemin_cache, $page))
		$page = @unserialize($page);
	else
		$page = array();

	// s'il est sessionne, charger celui correspondant a notre session
	if (isset($page['invalideurs'])
	AND isset($page['invalideurs']['session'])) {
		$chemin_cache_session = $chemin_cache . '_' . spip_session();
		if (lire_fichier(_DIR_CACHE . $chemin_cache_session, $page_session)
		AND $page_session = @unserialize($page_session)
		AND $page_session['lastmodified'] >= $page['lastmodified'])
			$page = $page_session;
		else
			$page = array();
	}

	// HEAD : cas sans jamais de calcul pour raisons de performance
	if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
		$use_cache = 0;
		$page = array('contexte_implicite'=>$contexte_implicite);
		return;
	}

	// Si un calcul, recalcul [ou preview, mais c'est recalcul] est demande,
	// on supprime le cache
	if ($GLOBALS['var_mode'] &&
		(isset($_COOKIE['spip_session'])
		|| isset($_COOKIE['spip_admin'])
		|| @file_exists(_ACCESS_FILE_NAME))
	) {
		$page = array('contexte_implicite'=>$contexte_implicite); // ignorer le cache deja lu
		include_spip('inc/invalideur');
		retire_caches($chemin_cache); # API invalideur inutile
		supprimer_fichier(_DIR_CACHE.$chemin_cache);
		if ($chemin_cache_session)
			supprimer_fichier(_DIR_CACHE.$chemin_cache_session);
	}

	// $delais par defaut (pour toutes les pages sans #CACHE{})
	if (!isset($GLOBALS['delais'])) {
		define('_DUREE_CACHE_DEFAUT', 24*3600);
		$GLOBALS['delais'] = _DUREE_CACHE_DEFAUT;
	}

	// determiner la validite de la page
	if ($page) {
		$use_cache = cache_valide($page, $page['lastmodified']);
		// le contexte implicite n'est pas stocke dans le cache, mais il y a equivalence
		// par le nom du cache. On le reinjecte donc ici pour utilisation eventuelle au calcul
		$page['contexte_implicite'] = $contexte_implicite;
		if (!$use_cache) {
			// $page est un cache utilisable
			gunzip_page($page);
			return;
		}
	} else {
		$page = array('contexte_implicite'=>$contexte_implicite);
		$use_cache = cache_valide($page,0); // fichier cache absent : provoque le calcul
	}

	// Si pas valide mais pas de connexion a la base, le garder quand meme
	if (!spip_connect()) {
		if (isset($page['texte'])) {
			gunzip_page($page);
			$use_cache = 0;
		}
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
