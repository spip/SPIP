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

// --------------------------
// Gestion des taches de fond
// --------------------------

// Deux difficultes:
// - la plupart des hebergeurs ne fournissent pas le Cron d'Unix
// - les scripts usuels standard sont limites a 30 secondes

// Solution:
// les scripts usuels les plus brefs, en plus de livrer la page demandee,
// s'achevent  par un appel a la fonction spip_cron.
// Celle-ci prend dans la liste des taches a effectuer la plus prioritaire.
// Une seule tache est executee pour eviter la guillotine des 30 secondes.
// Une fonction executant une tache doit retourner un nombre:
// - nul, si la tache n'a pas a etre effecutee
// - positif, si la tache a ete effectuee
// - negatif, si la tache doit etre poursuivie ou recommencee
// Elle recoit en argument la date de la derniere execution de la tache.

// On peut appeler spip_cron avec d'autres taches (pour etendre Spip)
// specifiee par des fonctions respectant le protocole ci-dessus
// On peut modifier la frequence de chaque tache et leur ordre d'analyse
// en modifiant les variables ci-dessous.

//----------

// Les taches sont dans un array ('nom de la tache' => periodicite)
// Cette fonction execute la tache la plus urgente (celle dont la date
// de derniere execution + la periodicite est minimale) sous reserve que
// le serveur MySQL soit actif.
// La date de la derniere intervention est donnee par un fichier homonyme,
// de suffixe ".lock", modifie a chaque intervention et des le debut
// de celle-ci afin qu'un processus concurrent ne la demarre pas aussi.
// Les taches les plus longues sont tronconnees, ce qui impose d'antidater
// le fichier de verrouillage (avec la valeur absolue du code de retour).
// La fonction executant la tache est un homonyme de prefixe "cron_"
// Le fichier homonyme de prefixe "inc_"
// est automatiquement charge si besoin, et est supposee la definir si ce
// n'est fait ici.

function spip_cron($taches = array()) {
	$t = time();
	if (@file_exists(_FILE_MYSQL_OUT)
	AND ($t - @filemtime(_FILE_MYSQL_OUT) < 300))
		return;

	include_spip('inc/meta');
	// force un spip_query
	lire_metas();

	if (!$taches)
		$taches = taches_generales();

	// Quelle est la tache la plus urgente ?
	$tache = '';
	$tmin = $t;
	clearstatcache();
	foreach ($taches as $nom => $periode) {
		$lock = _DIR_SESSIONS . $nom . '.lock';
		$date_lock = @filemtime($lock);
		if ($date_lock + $periode < $tmin) {
			$tmin = $date_lock + $periode;
			$tache = $nom;
			$last = $date_lock;
		}
		// debug : si la date du fichier est superieure a l'heure actuelle,
		// c'est que le serveur a (ou a eu) des problemes de reglage horaire
		// qui peuvent mettre en peril les taches cron : signaler dans le log
		// (On laisse toutefois flotter sur une heure, pas la peine de s'exciter
		// pour si peu)
		else if ($date_lock > $t + 3600)
			spip_log("Erreur de date du fichier $lock : $date_lock > $t !");
	}
	if (!$tache) return;

	// Interdire des taches paralleles, de maniere a eviter toute concurrence
	// entre deux SPIP partageant la meme base, ainsi que toute interaction
	// bizarre entre des taches differentes
	// Ne rien lancer non plus si serveur naze evidemment

	if (!spip_get_lock('cron')) {
		spip_log("tache $tache: pas de lock cron");
		return;
	}

	// Un autre lock dans _DIR_SESSIONS, pour plus de securite
	$lock = _DIR_SESSIONS . $tache . '.lock';
	if (spip_touch($lock, $taches[$tache])) {
		// preparer la tache
		spip_timer('tache');

		$fonction = 'cron_' . $tache;
		if (!function_exists($fonction))
			include_spip('inc/' . $tache);


		// l'appeler
		$code_de_retour = $fonction($last);

		// si la tache a eu un effet : log
		if ($code_de_retour) {
			spip_log("cron: $tache (" . spip_timer('tache') . ")");
			// eventuellement modifier la date du fichier
			if ($code_de_retour < 0) @touch($lock, (0 - $code_de_retour));
		}# else spip_log("cron $tache a reprendre");
	}

	// relacher le lock mysql
	spip_release_lock('cron');
}

//
// Construction de la liste des taches.
// la cle est la tache, la valeur le temps minimal, en secondes, entre
// deux memes taches
// NE PAS METTRE UNE VALEUR INFERIEURE A 30 (cf ci-dessus)
//
function taches_generales() {
	$taches_generales = array();

	// MAJ des rubriques publiques (cas de la publication post-datee)
	$taches_generales['rubriques'] = 3600;

	// Optimisation de la base
	$taches_generales['optimiser'] = 3600*48;

	// cache
	$taches_generales['invalideur'] = 3600;

	// nouveautes
	if ($GLOBALS['meta']['adresse_neuf'] AND $GLOBALS['meta']['jours_neuf']
	AND ($GLOBALS['meta']['quoi_de_neuf'] == 'oui'))
		$taches_generales['mail']= 3600 * 24 * $GLOBALS['meta']['jours_neuf'];

	// stats : toutes les 5 minutes on peut vider un panier de visites
	if ($GLOBALS['meta']["activer_statistiques"] == "oui") {
		$taches_generales['visites'] = 300; 
		$taches_generales['popularites'] = 7200; # calcul lourd
	}

	// syndication
	if ($GLOBALS['meta']["activer_syndic"] == "oui") 
		$taches_generales['syndic'] = 90;

	// indexation
	if ($GLOBALS['meta']["activer_moteur"] == "oui") 
		$taches_generales['indexation'] = 90;
		
	// ajax
		$taches_generales['ajax'] = 3600 * 2;

	return pipeline('taches_generales_cron',$taches_generales);
}


// Cas particulier : optimiser est dans base/optimiser, et pas dans inc/
// il faut donc definir la fonction _cron ici.
function cron_optimiser($t) {
	include_spip('base/optimiser');
	optimiser_base();
	return 1;
}

?>
