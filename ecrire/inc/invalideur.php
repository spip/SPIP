<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion du cache et des invalidations de cache
 *
 * @package SPIP\Core\Cache
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/serial');

/**
 * Si un fichier n'a pas été servi (fileatime) depuis plus d'une heure, on se sent
 * en droit de l'éliminer
 */
if (!defined('_AGE_CACHE_ATIME')) {
	define('_AGE_CACHE_ATIME', 3600);
}


/**
 * Évalue approximativement la taille du cache
 *
 * Pour de gros volumes, impossible d'ouvrir chaque fichier,
 * on y va donc à l'estime !
 *
 * @return int Taille approximative en octets
 **/
function taille_du_cache() {
	# check dirs until we reach > 500 files
	$t = 0;
	$n = 0;
	$time = $GLOBALS['meta']['cache_mark'] ?? 0;
	for ($i = 0; $i < 256; $i++) {
		$dir = _DIR_CACHE . sprintf('%02s', dechex($i));
		if (@is_dir($dir) && is_readable($dir) && ($d = opendir($dir))) {
			while (($f = readdir($d)) !== false) {
				if (preg_match(',^[[0-9a-f]+\.cache$,S', $f) && ($a = stat("$dir/$f"))) {
					$n++;
					if ($a['mtime'] >= $time) {
						if ($a['blocks'] > 0) {
							$t += 512 * $a['blocks'];
						} else {
							$t += $a['size'];
						}
					}
				}
			}
		}
		if ($n > 500) {
			return (int) (256 * $t / (1 + $i));
		}
	}
	return $t;
}


/**
 * Invalider les caches liés à telle condition
 *
 * Les invalideurs sont de la forme 'objet/id_objet'.
 * La condition est géneralement "id='objet/id_objet'".
 *
 * Ici on se contente de noter la date de mise à jour dans les metas,
 * pour le type d'objet en question (non utilisé cependant) et pour
 * tout le site (sur la meta `derniere_modif`)
 *
 * @global array $derniere_modif_invalide
 *     Par défaut à `true`, la meta `derniere_modif` est systématiquement
 *     calculée dès qu'un invalideur se présente. Cette globale peut
 *     être mise à `false` (aucun changement sur `derniere_modif`) ou
 *     sur une liste de type d'objets (changements uniquement lorsqu'une
 *     modification d'un des objets se présente).
 *
 * @param string $cond
 *     Condition d'invalidation
 * @param bool $modif
 *     Inutilisé
 **/
function inc_suivre_invalideur_dist($cond, $modif = true) {
	if (!$modif) {
		return;
	}

	// determiner l'objet modifie : forum, article, etc
	if (preg_match(',["\']([a-z_]+)[/"\'],', $cond, $r)) {
		$objet = objet_type($r[1]);
	}

	// stocker la date_modif_$objet (ne sert a rien pour le moment)
	if (isset($objet)) {
		ecrire_meta('derniere_modif_' . $objet, time());
	}

	// si $derniere_modif_invalide est un array('article', 'rubrique')
	// n'affecter la meta que si un de ces objets est modifie
	if (is_array($GLOBALS['derniere_modif_invalide'])) {
		if (in_array($objet, $GLOBALS['derniere_modif_invalide'])) {
			ecrire_meta('derniere_modif', time());
		}
	} // sinon, cas standard, toujours affecter la meta
	else {
		ecrire_meta('derniere_modif', time());
	}
}


/**
 * Purge un répertoire de ses fichiers
 *
 * Utilisée entre autres pour vider le cache depuis l'espace privé
 *
 * @uses supprimer_fichier()
 *
 * @param string $dir
 *     Chemin du répertoire à purger
 * @param array $options
 *     Tableau des options. Peut être :
 *
 *     - atime : timestamp pour ne supprimer que les fichiers antérieurs
 *       à cette date (via fileatime)
 *     - mtime : timestamp pour ne supprimer que les fichiers antérieurs
 *       à cette date (via filemtime)
 *     - limit : nombre maximum de suppressions
 * @return int
 *     Nombre de fichiers supprimés
 **/
function purger_repertoire($dir, $options = []) {
	if (!is_dir($dir) || !is_readable($dir)) {
		return 0;
	}

	// sur certains sites on veut absolument garder certains caches référencés dans un CDN
	// on peut donc inhiber la purge de ces répertoires pour eviter tout probleme
	if (file_exists(rtrim($dir, '/') . '/inhib_purger_repertoire.txt')) {
		return 0;
	}

	$handle = opendir($dir);
	if (!$handle) {
		return 0;
	}

	$total = 0;

	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", ".svn" etc & CACHEDIR.TAG
		if ($fichier[0] == '.' || $fichier == 'CACHEDIR.TAG') {
			continue;
		}
		$chemin = "$dir/$fichier";
		if (is_file($chemin)) {
			if (
				(!isset($options['atime']) || @fileatime($chemin) < $options['atime']) && (!isset($options['mtime']) || @filemtime($chemin) < $options['mtime'])
			) {
				supprimer_fichier($chemin);
				$total++;
			}
		} else {
			if (is_dir($chemin)) {
				$opts = $options;
				if (isset($options['limit'])) {
					$opts['limit'] = $options['limit'] - $total;
				}
				$total += purger_repertoire($chemin, $opts);
				if (isset($options['subdir']) && $options['subdir']) {
					spip_unlink($chemin);
				}
			}
		}

		if (isset($options['limit']) && $total >= $options['limit']) {
			break;
		}
	}
	closedir($handle);

	return $total;
}


//
// Destruction des fichiers caches invalides
//

// Securite : est sur que c'est un cache
function retire_cache($cache) {

	if (
		preg_match(
			',^([0-9a-f]/)?(\d+/)?[0-9a-f]+\.cache(\.gz)?$,i',
			(string) $cache
		)
	) {
		// supprimer le fichier (de facon propre)
		supprimer_fichier(_DIR_CACHE . $cache);
	} else {
		spip_log("Nom de fichier cache incorrect : $cache");
	}
}

// Supprimer les caches marques "x"
// A priori dans cette version la fonction ne sera pas appelee, car
// la meta est toujours false ; mais evitons un bug si elle est appellee
function inc_retire_caches_dist($chemin = '') {
	if (isset($GLOBALS['meta']['invalider_caches'])) {
		effacer_meta('invalider_caches');
	} # concurrence
}

#######################################################################
##
## Ci-dessous les fonctions qui restent appellees dans le core
## pour pouvoir brancher le plugin invalideur ;
## mais ici elles ne font plus rien
##

function retire_caches($chemin = '') {
	if ($retire_caches = charger_fonction('retire_caches', 'inc', true)) {
		return $retire_caches($chemin);
	}
}


// Fonction permettant au compilo de calculer les invalideurs d'une page
// (note: si absente, n'est pas appellee)

function calcul_invalideurs($corps, $primary, &$boucles, $id_boucle) {
	if ($calcul_invalideurs = charger_fonction('calcul_invalideurs', 'inc', true)) {
		return $calcul_invalideurs($corps, $primary, $boucles, $id_boucle);
	}
	return $corps;
}


// Cette fonction permet de supprimer tous les invalideurs
// Elle ne touche pas aux fichiers cache eux memes ; elle est
// invoquee quand on vide tout le cache en bloc (action/purger)
//
function supprime_invalideurs() {
	if ($supprime_invalideurs = charger_fonction('supprime_invalideurs', 'inc', true)) {
		return $supprime_invalideurs();
	}
}


// Calcul des pages : noter dans la base les liens d'invalidation
function maj_invalideurs($fichier, &$page) {
	if ($maj_invalideurs = charger_fonction('maj_invalideurs', 'inc', true)) {
		return $maj_invalideurs($fichier, $page);
	}
}


// les invalideurs sont de la forme "objet/id_objet"
function insere_invalideur($inval, $fichier) {
	if ($insere_invalideur = charger_fonction('insere_invalideur', 'inc', true)) {
		return $insere_invalideur($inval, $fichier);
	}
}

//
// Marquer les fichiers caches invalides comme etant a supprimer
//
function applique_invalideur($depart) {
	if ($applique_invalideur = charger_fonction('applique_invalideur', 'inc', true)) {
		return $applique_invalideur($depart);
	}
}

//
// Invalider les caches liés à telle condition
//
function suivre_invalideur($cond, $modif = true) {
	if ($suivre_invalideur = charger_fonction('suivre_invalideur', 'inc', true)) {
		return $suivre_invalideur($cond, $modif);
	}
}
