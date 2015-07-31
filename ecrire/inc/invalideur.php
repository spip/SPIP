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

include_spip('base/serial');
include_spip('inc/meta');

# estime la taille moyenne d'un fichier cache, pour ne pas les regarder (10ko)
define('_TAILLE_MOYENNE_FICHIER_CACHE', 1024 * 10);
# si un fichier n'a pas servi (fileatime) depuis plus d'une heure, on se sent
# en droit de l'eliminer
define('_AGE_CACHE_ATIME', 3600);

// Donne le nombre de fichiers dans un repertoire (plat, pour aller vite)
// false si erreur
function nombre_de_fichiers_repertoire($dir) {
	if (!$h = @opendir($dir)) return false;
	$total = 0;
	while (($fichier = @readdir($h)) !== false)
		$total++;
	closedir($h);
	return $total;
}

// Indique la taille du repertoire cache ; pour de gros volumes,
// impossible d'ouvrir chaque fichier, on y va donc a l'estime
// http://doc.spip.org/@taille_du_cache
function taille_du_cache() {
	$total = 0;
	for ($i=0;$i<16;$i++) {
		$l = dechex($i);
		$dir = sous_repertoire(_DIR_CACHE, $l);
		$total += nombre_de_fichiers_repertoire($dir);
	}
	return $total * _TAILLE_MOYENNE_FICHIER_CACHE;
}


// Invalider les caches lies a telle condition
// ici on se contente de noter la date de mise a jour dans les metas
// http://doc.spip.org/@suivre_invalideur
function suivre_invalideur($cond, $modif=true) {
	if ($modif) {
		ecrire_meta('derniere_modif', time());
		ecrire_metas();
	}
}



// Utilisee pour vider le cache depuis l'espace prive
// (ou juste les squelettes si un changement de config le necessite)
// si $date est passee en argument, ne pas supprimer ce qui a servi
// plus recemment que cette date (via fileatime)
// retourne le nombre de fichiers supprimes
// http://doc.spip.org/@purger_repertoire
function purger_repertoire($dir, $options=array()) {
	$handle = @opendir($dir);
	if (!$handle) return;

	$total = 0;

	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", ".svn" etc.
		if ($fichier[0] == '.') continue;
		$chemin = "$dir/$fichier";
		if (is_file($chemin)) {
			if (!isset($options['date'])
			OR (@fileatime($chemin) < $options['date'])) {
				@unlink($chemin);
				$total ++;
			}
		}
		else if (is_dir($chemin))
			if ($fichier != 'CVS')
				$total += purger_repertoire($chemin, $options);

		if (isset($options['limit']) AND $total>=$options['limit'])
			break;
	}
	closedir($handle);

	return $total;
}


//
// Methode : on prend un des sous-repertoires de CACHE/
// on considere qu'il fait 1/16e de la taille du cache
// et on le ratiboise
//
function appliquer_quota_cache() {
	global $quota_cache;

	$l = dechex(rand(0,15));
	$dir = sous_repertoire(_DIR_CACHE, $l);
	$nombre = nombre_de_fichiers_repertoire($dir);
	$total_cache = _TAILLE_MOYENNE_FICHIER_CACHE * $nombre;
	spip_log("Taille du CACHE estimee: "
		.(intval(16*$total_cache/102400)/10)." Mo");

	if ($quota_cache > 0) {
		$trop = $total_cache - ($quota_cache/16)*1024*1024;
		if ($trop > 0) {
			$n = purger_repertoire($dir,
				array(
					'atime' => time()-_AGE_CACHE_ATIME,
					'limit' => intval($trop / _TAILLE_MOYENNE_FICHIER_CACHE)
				)
			);
			spip_log("$dir : $n caches supprimes");
		}
	}

}


// Cette fonction fait le menage dans le cache :
// - elle peut retirer les fichiers perimes
// - elle fait appliquer le quota
// http://doc.spip.org/@cron_invalideur
function cron_invalideur($t) {
	// En cas de quota sur le CACHE/, nettoyer les fichiers les plus vieux
	appliquer_quota_cache();
	return 1;
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

#######################################################################
##
## Ci-dessous les fonctions qui restent appellees dans le core
## pour pouvoir brancher le plugin invalideur ;
## mais ici elles ne font plus rien
##

// Supprimer les caches marques "x"
// A priori dans cette version la fonction ne sera pas appelee, car
// la meta est toujours false ; mais evitons un bug si elle est appellee
// http://doc.spip.org/@retire_caches
function retire_caches($chemin = '') {
	effacer_meta('invalider_caches'); # concurrence
	ecrire_metas();
}


// Pour que le compilo ajoute un invalideur a la balise #PARAMETRES_FORUM
// Noter l'invalideur de la page contenant ces parametres,
// en cas de premier post sur le forum
// http://doc.spip.org/@code_invalideur_forums
function code_invalideur_forums($p, $code) {
	return "''"; // code compile ne faisant rien
}


// Fonction permettant au compilo de calculer les invalideurs d'une page
// http://doc.spip.org/@calcul_invalideurs
function calcul_invalideurs($corps, $primary, &$boucles, $id_boucle) {
	return $corps;
}

// Cette fonction permet de supprimer tous les invalideurs
// Elle ne touche pas aux fichiers cache eux memes ; elle est
// invoquee quand on vide tout le cache en bloc (action/purger)
//
// http://doc.spip.org/@supprime_invalideurs
function supprime_invalideurs() { }


// Calcul des pages : noter dans la base les liens d'invalidation
// http://doc.spip.org/@maj_invalideurs
function maj_invalideurs ($fichier, &$page) { }

// pour les forums l'invalideur est : 'id_forum/a23'
// pour les petitions et autres, l'invalideur est par exemple :
// 'varia/pet60'
// http://doc.spip.org/@insere_invalideur
function insere_invalideur($inval, $fichier) { }


//
// Marquer les fichiers caches invalides comme etant a supprimer
//
// http://doc.spip.org/@applique_invalideur
function applique_invalideur($depart) { }

?>
