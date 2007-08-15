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

// --------------------------
// Gestion des taches de fond
// --------------------------

// Deux difficultes:
// - la plupart des hebergeurs ne fournissent pas le Cron d'Unix
// - les scripts usuels standard sont limites a 30 secondes

// Solution:
// Toute connexion a SPIP s'acheve par un appel a la fonction cron()
// qui appelle la fonction surchargeable genie dans inc/
// Sa definition standard ci-dessous prend dans une liste de taches
// la plus prioritaire, leurs dates etant donnees par leur fichier-verrou.
// Une fonction executant une tache doit retourner un nombre:
// - nul, si la tache n'a pas a etre effecutee
// - positif, si la tache a ete effectuee
// - negatif, si la tache doit etre poursuivie ou recommencee
// Elle recoit en argument la date de la derniere execution de la tache.

// On peut appeler cette fonction avec d'autres taches (pour etendre Spip)
// specifiee par des fonctions respectant le protocole ci-dessus
// On peut modifier la frequence de chaque tache et leur ordre d'analyse
// en modifiant les variables ci-dessous.

//----------

// Les taches sont dans un tableau ('nom de la tache' => periodicite)
// Cette fonction execute la tache la plus urgente
// (celle dont la date de derniere execution + la periodicite est minimale)
// La date de la derniere intervention est donnee par un fichier homonyme,
// de suffixe ".lock", modifie a chaque intervention et des le debut
// de celle-ci afin qu'un processus concurrent ne la demarre pas aussi.
// Les taches les plus longues sont tronconnees, ce qui impose d'antidater
// le fichier de verrouillage (avec la valeur absolue du code de retour).
// La fonction executant la tache est un homonyme de prefixe "genie_".
// Le fichier homonyme du repertoire "genie/" est automatiquement lu
// et il est suppose definir cette fonction.

// http://doc.spip.org/@inc_cron_dist
function inc_genie_dist($taches = array()) {

	if (!$taches)
		$taches = taches_generales();

	// Quelle est la tache la plus urgente ?
	$tache = '';
	$tmin = $t = time();
	foreach ($taches as $nom => $periode) {
		$celock = _DIR_TMP . $nom . '.lock';
		$date_lock = @filemtime($celock);
		if ($date_lock + $periode < $tmin) {
			$tmin = $date_lock + $periode;
			$tache = $nom;
			$lock = $celock;
			$last = $date_lock;
		}
	// debug : si la date du fichier est superieure a l'heure actuelle,
	// c'est que les serveurs Http et de fichiers sont desynchro.
	// Ca peut mettre en peril les taches cron : signaler dans le log
	// (On laisse toutefois flotter sur une heure, pas la peine de s'exciter
	// pour si peu)
		else if ($date_lock > $t + 3600)
			spip_log("Erreur de date du fichier $lock : $date_lock > $t !");
	}

	if ($tache) {

		spip_timer('tache');
		touch($lock);
		$cron = charger_fonction($tache, 'genie');
		$retour = $cron($last);
		// si la tache a eu un effet : log
		if ($retour) {
			spip_log("cron: $tache (" . spip_timer('tache') . ") $retour");
			if ($retour < 0)
				@touch($lock, 0 - $retour);
		}
	}
}

//
// Construction de la liste des taches.
// la cle est la tache, 
// la valeur le temps minimal, en secondes, entre deux memes taches
// NE PAS METTRE UNE VALEUR INFERIEURE A 30 
// les serveurs Http n'accordant en general pas plus de 30 secondes
// a leur sous-processus
//
// http://doc.spip.org/@taches_generales
function taches_generales() {
	$taches_generales = array();

	// MAJ des rubriques publiques (cas de la publication post-datee)
	// est fait au coup par coup a present
	//	$taches_generales['rubriques'] = 3600;

	// Optimisation de la base
	$taches_generales['optimiser'] = 3600*48;

	// cache (chaque 20 minutes => 1/16eme du repertoire cache)
	$taches_generales['invalideur'] = 1200;

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

	// maintenance (ajax, verifications diverses)
		$taches_generales['maintenance'] = 3600 * 2;

	return pipeline('taches_generales_cron',$taches_generales);
}

// Pas de fichier a part pour une fonction aussi petite:
// - elle peut retirer les fichiers perimes
// - elle fait appliquer le quota
// En cas de quota sur le CACHE/, nettoyer les fichiers les plus vieux
// http://doc.spip.org/@cron_invalideur_dist
function genie_invalideur_dist($t) {

	include_spip('inc/invalideur');
	appliquer_quota_cache();
	return 1;
}
?>
