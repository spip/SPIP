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
// la fonction retire_cache()
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
	$subdir = sous_repertoire(_DIR_CACHE, substr($md_cache, 0, 1), true);

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

//
// Destruction des fichiers caches invalides
//

// Securite : est sur que c'est un cache
// http://doc.spip.org/@retire_cache
function retire_cache($cache) {

	if (preg_match(
	"|^([0-9a-f]/)?([0-9]+/)?[^.][\-_\%0-9a-z]+\.[0-9a-f]+(\.gz)?$|i",
	$cache)) {
		// supprimer le fichier (de facon propre)
		supprimer_fichier(_DIR_CACHE . $cache);
	} else
		spip_log("Impossible de retirer $cache");
}

// Supprimer les caches marques "x"
// http://doc.spip.org/@retire_caches
function retire_caches($chemin = '') {

	include_spip('base/abstract_sql');
	// recuperer la liste des caches voues a la suppression
	$suppr = array();

	// En priorite le cache qu'on appelle maintenant
	if ($chemin) {
		$f = spip_abstract_fetsel(array("fichier"),  array("spip_caches"), array("fichier = " . spip_abstract_quote($chemin) . " ",  "type='x'"), "", array(), 1);
		if ($f['fichier']) $suppr[$f['fichier']] = true;
	}

	// Et puis une centaine d'autres
	$compte = 0;
	if (isset($GLOBALS['meta']['invalider_caches'])) {
		$compte = 1;
		effacer_meta('invalider_caches'); # concurrence
		ecrire_metas();

		$q = spip_abstract_select(array("fichier"),
				    array("spip_caches"),
				    array("type='x'"),
				    "",
				    array(),
				    100);
		while ($r = spip_abstract_fetch($q)) {
			$compte ++;	# compte le nombre de resultats vus (y compris doublons)
			$suppr[$r['fichier']] = true;
		}
	}

	if ($n = count($suppr)) {
		spip_log ("Retire $n caches");
		foreach ($suppr as $cache => $ignore)
			retire_cache($cache);
		spip_query("DELETE FROM spip_caches WHERE " . calcul_mysql_in('fichier', "'".join("','",array_keys($suppr))."'") );
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

// gestion des delais par specification a l'exterieur du squelette

// http://doc.spip.org/@cache_valide
function cache_valide($chemin_cache, $date) {

	if (!isset($GLOBALS['delais']))
		$GLOBALS['delais'] = 3600;

	if (
		isset($_REQUEST['delais'])
		AND $GLOBALS['delais'] == $_REQUEST['delais']
	)
		die ("tester_variable: 'delais' interdite");

	if (!$GLOBALS['delais']) return -1;

	if ((time() - $date) > $GLOBALS['delais']) return $GLOBALS['delais'];

	return 0;
}

// gestion des delais par specification a l'interieur du squelette

// http://doc.spip.org/@cache_valide_autodetermine
function cache_valide_autodetermine($chemin_cache, $page, $date) {

	if (!$page) return 1;

	if (isset($page['entetes']['X-Spip-Cache'])) {
		$duree = intval($page['entetes']['X-Spip-Cache']);
		if ($duree == 0)  #CACHE{0}
			return -1;
		else if ($date + $duree < time())
			return $duree;
		else
			return 0;
	}

	// squelette ancienne maniere, on se rabat sur le vieux modele
	return cache_valide($chemin_cache, $date);
}


// Creer le fichier cache
# Passage par reference de $page par souci d'economie
// http://doc.spip.org/@creer_cache
function creer_cache(&$page, &$chemin_cache, $duree) {
	// Entrer dans la base les invalideurs calcules par le compilateur
	// (et supprimer les anciens)

	// arbitrage entre ancien et nouveau modele de delai:
	// primaute a la duree de vie de la page donnee a l'interieur de la page 
	if (isset($page['entetes']['X-Spip-Cache']))
		$duree = intval($page['entetes']['X-Spip-Cache']);

	// Enregistrer le fichier cache qui contient
	// 1) la carte d'identite de la page (ses "globals", genre id_article=7)
	// 2) son contenu
	$page['signal']['process_ins'] = $page['process_ins'];
	$page['signal']['entetes'] = $page['entetes'];

	// Normaliser le chemin et supprimer l'eventuelle contrepartie -gz du cache
	$chemin_cache = str_replace('.gz', '', $chemin_cache);
	$gz = cache_gz($page);
	supprimer_fichier(_DIR_CACHE . $chemin_cache . ($gz ? '' : '.gz'));

	// l'enregistrer, compresse ou non...
	$chemin_cache .= $gz;
	ecrire_fichier(_DIR_CACHE . $chemin_cache,
		"<!-- "
		. str_replace("\n", " ", serialize($page['signal']))
		. " -->\n"
		. $page['texte']);

	spip_log("Creation du cache $chemin_cache pour $duree secondes");
	// Inserer ses invalideurs
	include_spip('inc/invalideur');
	maj_invalideurs($chemin_cache, $page, $duree);

	// En profiter pour verifier que le .htaccess (deny all) est bien la
	include_spip('inc/acces');
	verifier_htaccess(_DIR_CACHE);
}

// http://doc.spip.org/@restaurer_meta_donnees
function restaurer_meta_donnees ($contenu) {

	if (preg_match("/^<!-- ([^\n]*) -->\n/ms", $contenu, $match)) {
		$meta_donnees = unserialize($match[1]);
		if (is_array($meta_donnees)) {
			foreach ($meta_donnees as $var=>$val) {
				$page[$var] = $val;
			}
		}
		$page['texte'] = substr($contenu, strlen($match[0]));
	} else	$page['texte'] = $contenu;

	return $page;
}


// purger un petit cache (tidy ou recherche) qui ne doit pas contenir de
// vieux fichiers
// http://doc.spip.org/@nettoyer_petit_cache
function nettoyer_petit_cache($prefix, $duree = 300) {
	// determiner le repertoire a purger : 'CACHE/rech/'
	$dircache = sous_repertoire(_DIR_CACHE,$prefix);
	if (spip_touch($dircache.'purger_'.$prefix, $duree, true)) {
		foreach (preg_files("$dircache$prefix") as $f) {
			if (time() - (@file_exists($f)?@filemtime($f):0) > $duree)
				@unlink($f);
		}
	}
}


// Interface du gestionnaire de cache
// Si son 3e argument est non vide, elle passe la main a creer_cache
// Sinon, elle recoit un contexte (ou le construit a partir de REQUEST_URI) 
// et affecte les 4 autres parametres recus par reference:
// - use_cache qui est
//	< 0 s'il faut calculer la page sans la mettre en cache
// 	= 0 si on peut utiliser un cache existant
//	> 0 s'il faut calculer la page et la mette en cache use_cache secondes
// - chemin_cache qui est le chemin d'acces au fichier ou vide si pas cachable
// - page qui est le tableau decrivant la page, si le cache la contenait
// - lastmodified qui vaut la date de derniere modif du fichier.

// http://doc.spip.org/@public_cacher_dist
function public_cacher_dist($contexte, &$use_cache, &$chemin_cache, &$page, &$lastmodified) {

	if ($chemin_cache) return creer_cache($page, $chemin_cache, $use_cache);

	// cas ignorant le cache car complement dynamique
	if (($_SERVER['REQUEST_METHOD'] == 'POST')
		OR substr($contexte['fond'],0,8)=='modeles/')
	) {
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
		// tester si la base est dispo
		spip_connect();
		if ($GLOBALS['db_ok']) {
			include_spip('inc/meta');
			lire_metas();
			retire_caches($chemin_cache);
		}
	}

	// cas sans jamais de cache pour raison interne
	if (isset($GLOBALS['var_mode']) &&
	    (isset($_COOKIE['spip_session'])
	    || isset($_COOKIE['spip_admin'])
		 || @file_exists(_ACCESS_FILE_NAME))) {
			supprimer_fichier(_DIR_CACHE . $chemin_cache);
	}

	if ($ok = lire_fichier(_DIR_CACHE . $chemin_cache, $page)) {
		$lastmodified = @file_exists(_DIR_CACHE . $chemin_cache) ?
			@filemtime(_DIR_CACHE . $chemin_cache) : 0;
		$page = restaurer_meta_donnees ($page);
		$use_cache = cache_valide_autodetermine($chemin_cache, $page, $lastmodified);
		if (!$use_cache) return;
	} else {
		$use_cache = 1;
	}


	// tester si la base est dispo
	spip_connect();

	// Si pas valide mais pas de connexion a la base, le garder quand meme
	if (!$GLOBALS['db_ok']) {
		if (file_exists(_DIR_CACHE . $chemin_cache))
			$use_cache = 0 ;
		else {
			spip_log("Erreur base de donnees, impossible utiliser $chemin_cache");
			include_spip('inc/minipres');
			minipres(_T('info_travaux_titre'),  _T('titre_probleme_technique'));
		}
	}

	if ($use_cache < 0) $chemin_cache = "";
	return;
}
?>
