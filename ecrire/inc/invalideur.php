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

include_spip('base/serial');

# estime la taille moyenne d'un fichier cache, pour ne pas les regarder (10ko)
define('_TAILLE_MOYENNE_FICHIER_CACHE', 1024 * 10);
# si un fichier n'a pas servi (fileatime) depuis plus d'une heure, on se sent
# en droit de l'eliminer
define('_AGE_CACHE_ATIME', 3600);

// Donne le nombre de fichiers dans un repertoire (plat, pour aller vite)
// false si erreur
// http://doc.spip.org/@nombre_de_fichiers_repertoire
function nombre_de_fichiers_repertoire($dir,$nb_estim_taille = 20) {
	$taille = 0; // mesurer la taille de N fichiers au hasard dans le repertoire
	$nb = $nb_estim_taille;
	if (!$h = @opendir($dir)) return false;
	$total = 0;
	while (($fichier = @readdir($h)) !== false)
		if ($fichier[0]!='.'){
			$total++;
			if ($nb AND rand(1,10)==1){
				$taille += filesize("$dir/$fichier");
				$nb--;
			}
		}
	closedir($h);
	return array($total,$taille?$taille/($nb_estim_taille-$nb):_TAILLE_MOYENNE_FICHIER_CACHE);
}

// Indique la taille du repertoire cache ; pour de gros volumes,
// impossible d'ouvrir chaque fichier, on y va donc a l'estime
// http://doc.spip.org/@taille_du_cache
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

// Invalider les caches lies a telle condition
// ici on se contente de noter la date de mise a jour dans les metas
// http://doc.spip.org/@suivre_invalideur
function suivre_invalideur($cond, $modif=true) {
	if (!$modif)
		return;

	// determiner l'objet modifie : forum, article, etc
	if (preg_match(',id_([a-z]+),', $cond, $r))
		$objet = $r[1];
	// cas particulier des signatures
	else if (strpos($cond, 'varia/pet'))
		$objet = 'signature';

	// stocker la date_modif_$objet (ne sert a rien pour le moment)
	if (isset($objet))
		ecrire_meta('derniere_modif_'.$objet, time());

	// si $derniere_modif_invalide est un array('forum', 'signature')
	// n'affecter la meta que si un de ces objets est modifie
	if (is_array($GLOBALS['derniere_modif_invalide'])) {
		if (in_array($objet, $GLOBALS['derniere_modif_invalide']))
			ecrire_meta('derniere_modif', time());
	}
	// sinon, cas standard, toujours affecter la meta
	else
		ecrire_meta('derniere_modif', time());


}



// Utilisee pour vider le cache depuis l'espace prive
// (ou juste les squelettes si un changement de config le necessite)
// si $atime est passee en argument, ne pas supprimer ce qui a servi
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
			if (!isset($options['atime'])
			OR (@fileatime($chemin) < $options['atime'])) {
				supprimer_fichier($chemin);
				$total ++;
			}
		}
		else if (is_dir($chemin)){
			if ($fichier != 'CVS')
				$total += purger_repertoire($chemin, $options);
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
// http://doc.spip.org/@appliquer_quota_cache
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
// http://doc.spip.org/@retire_cache
function retire_cache($cache) {

	if (preg_match(
	"|^([0-9a-f]/)?([0-9]+/)?[^.][\-_\%0-9a-z]+--[0-9a-f]+(\.gz)?$|i",
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
// http://doc.spip.org/@retire_caches
function retire_caches($chemin = '') {
	if (isset($GLOBALS['meta']['invalider_caches']))
		effacer_meta('invalider_caches'); # concurrence
}


// Pour que le compilo ajoute un invalideur a la balise #PARAMETRES_FORUM
// Noter l'invalideur de la page contenant ces parametres,
// en cas de premier post sur le forum
// http://doc.spip.org/@code_invalideur_forums
function code_invalideur_forums($p, $code) {
	return $code;
}


// Fonction permettant au compilo de calculer les invalideurs d'une page
// (note: si absente, n'est pas appellee)
/*
// http://doc.spip.org/@calcul_invalideurs
function calcul_invalideurs($corps, $primary, &$boucles, $id_boucle) {
	return $corps;
}
*/

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
