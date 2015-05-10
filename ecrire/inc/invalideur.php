<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2015                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion du cache et des invalidations de cache
 *
 * @package SPIP\Core\Cache
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/serial');

/** Estime la taille moyenne d'un fichier cache, pour ne pas les regarder (10ko) */
define('_TAILLE_MOYENNE_FICHIER_CACHE', 1024 * 10);
/**
 * Si un fichier n'a pas servi (fileatime) depuis plus d'une heure, on se sent
 * en droit de l'éliminer
 */
define('_AGE_CACHE_ATIME', 3600);

/**
 * Calcul le nombre de fichiers à la racine d'un répertoire ainsi qu'une
 * approximation de la taille du répertoire
 *
 * On ne calcule que la racine pour pour aller vite.
 * 
 * @param string $dir Chemin du répertoire
 * @param string $nb_estim_taille Nombre de fichiers maximum pour estimer la taille
 * @return bool|array
 *
 *     - false si le répertoire ne peut pas être ouvert
 *     - array(nombre de fichiers, approximation de la taille en octet) sinon
**/
function nombre_de_fichiers_repertoire($dir,$nb_estim_taille = 20) {
	$taille = 0; // mesurer la taille de N fichiers au hasard dans le repertoire
	$nb = $nb_estim_taille;
	if (!$h = @opendir($dir)) return false;
	$total = 0;
	while (($fichier = @readdir($h)) !== false)
		if ($fichier[0]!='.' AND !is_dir("$dir/$fichier")){
			$total++;
			if ($nb AND rand(1,10)==1){
				$taille += filesize("$dir/$fichier");
				$nb--;
			}
		}
	closedir($h);
	return array($total,$taille?$taille/($nb_estim_taille-$nb):_TAILLE_MOYENNE_FICHIER_CACHE);
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
	$total = 0;
	$taille = 0;
	for ($i=0;$i<16;$i++) {
		$l = dechex($i);
		$dir = sous_repertoire(_DIR_CACHE, $l);
		list($n,$s) = nombre_de_fichiers_repertoire($dir);
		$total += $n;
		$taille += $s;
	}
	return $total * $taille / 16;
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
 * @global derniere_modif_invalide
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
function suivre_invalideur($cond, $modif=true) {
	if (!$modif)
		return;

	// determiner l'objet modifie : forum, article, etc
	if (preg_match(',["\']([a-z_]+)[/"\'],', $cond, $r))
		$objet = objet_type($r[1]);

	// stocker la date_modif_$objet (ne sert a rien pour le moment)
	if (isset($objet))
		ecrire_meta('derniere_modif_'.$objet, time());

	// si $derniere_modif_invalide est un array('article', 'rubrique')
	// n'affecter la meta que si un de ces objets est modifie
	if (is_array($GLOBALS['derniere_modif_invalide'])) {
		if (in_array($objet, $GLOBALS['derniere_modif_invalide']))
			ecrire_meta('derniere_modif', time());
	}
	// sinon, cas standard, toujours affecter la meta
	else
		ecrire_meta('derniere_modif', time());

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
 *     - limit : nombre maximum de suppressions
 * @return int
 *     Nombre de fichiers supprimés
**/
function purger_repertoire($dir, $options=array()) {
	$handle = @opendir($dir);
	if (!$handle) return;

	$total = 0;

	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", ".svn" etc.
		if ($fichier[0] == '.') continue;
		$chemin = "$dir/$fichier";
		if (is_file($chemin)) {
			if (!isset($options['atime'])
			OR (@fileatime($chemin) < $options['atime'])) {
				supprimer_fichier($chemin);
				$total ++;
			}
		}
		else if (is_dir($chemin)){
			$opts = $options;
			if (isset($otpions['limit']))
				$otps['limit'] = $otpions['limit'] - $total;
			$total += purger_repertoire($chemin, $opts);
			if (isset($options['subdir']) && $options['subdir'])
				spip_unlink($chemin);
		}

		if (isset($options['limit']) AND $total>=$options['limit'])
			break;
	}
	closedir($handle);

	return $total;
}


//
// Methode : on prend un des sous-repertoires de CACHE/
// on considere qu'il fait 1/16e de la taille du cache
// et on le ratiboise en supprimant les fichiers qui n'ont pas
// ete sollicites dans l'heure qui vient de s'ecouler
//
// http://code.spip.net/@appliquer_quota_cache
function appliquer_quota_cache() {
	global $quota_cache;
	$encore = false;

	$tour_quota_cache = intval(1+$GLOBALS['meta']['tour_quota_cache'])%16;
	ecrire_meta('tour_quota_cache', $tour_quota_cache);

	$l = dechex($tour_quota_cache);
	$dir = sous_repertoire(_DIR_CACHE, $l);
	list($nombre,$taille) = nombre_de_fichiers_repertoire($dir);
	$total_cache = $taille * $nombre;
	spip_log("Taille du CACHE estimee ($l): "
		.(intval(16*$total_cache/(1024*1024/10))/10)." Mo","invalideur");

	// Nombre max de fichiers a supprimer
	if ($quota_cache > 0
	AND $taille > 0) {
		$trop = $total_cache - ($quota_cache/16)*1024*1024;
		$trop = 3 * intval($trop / $taille);
		if ($trop > 0) {
			$n = purger_repertoire($dir,
				array(
					'atime' => time() - _AGE_CACHE_ATIME,
					'limit' => $trop,
					'subdir' => true // supprimer les vieux sous repertoire de session (avant [15851])
				)
			);
			spip_log("$dir : $n/$trop caches supprimes [taille moyenne $taille]","invalideur");
			$total_cache = intval(max(0,(16*$total_cache) - $n*$taille)/(1024*1024)*10)/10;
			spip_log("cache restant estime : $total_cache Mo, ratio ".$total_cache/$quota_cache,"invalideur");

			// redemander la main pour eviter que le cache ne gonfle trop
			// mais pas si on ne peut pas purger car les fichiers sont trops recents
			if (
			  $total_cache/$quota_cache>1.5
			  AND $n*50>$trop) {
				$encore = true;
				spip_log("Il faut encore purger","invalideur");
			}
		}
	}
	return $encore;
}


//
// Destruction des fichiers caches invalides
//

// Securite : est sur que c'est un cache
// http://code.spip.net/@retire_cache
function retire_cache($cache) {

	if (preg_match(
	"|^([0-9a-f]/)?([0-9]+/)?[0-9a-f]+\.cache(\.gz)?$|i",
	$cache)) {
		// supprimer le fichier (de facon propre)
		supprimer_fichier(_DIR_CACHE . $cache);
	} else
		spip_log("Nom de fichier cache incorrect : $cache");
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
// http://code.spip.net/@retire_caches
function retire_caches($chemin = '') {
	if (isset($GLOBALS['meta']['invalider_caches']))
		effacer_meta('invalider_caches'); # concurrence
}


// Fonction permettant au compilo de calculer les invalideurs d'une page
// (note: si absente, n'est pas appellee)
/*
// http://code.spip.net/@calcul_invalideurs
function calcul_invalideurs($corps, $primary, &$boucles, $id_boucle) {
	return $corps;
}
*/

// Cette fonction permet de supprimer tous les invalideurs
// Elle ne touche pas aux fichiers cache eux memes ; elle est
// invoquee quand on vide tout le cache en bloc (action/purger)
//
// http://code.spip.net/@supprime_invalideurs
function supprime_invalideurs() { }


// Calcul des pages : noter dans la base les liens d'invalidation
// http://code.spip.net/@maj_invalideurs
function maj_invalideurs ($fichier, &$page) { }

// les invalideurs sont de la forme "objet/id_objet"
// http://code.spip.net/@insere_invalideur
function insere_invalideur($inval, $fichier) { }


//
// Marquer les fichiers caches invalides comme etant a supprimer
//
// http://code.spip.net/@applique_invalideur
function applique_invalideur($depart) { }

?>
