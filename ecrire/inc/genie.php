<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Gestion des tâches de fond
 *
 * Deux difficultés :
 * - la plupart des hebergeurs ne fournissent pas le Cron d'Unix
 * - les scripts usuels standard sont limites à 30 secondes
 *
 * Solution
 * --------
 * Toute connexion à SPIP s'achève par un appel (asynchrone si possible)
 * à la fonction `cron()` qui appelle la fonction surchargeable `inc_genie_dist()`
 *
 * Sa définition standard ci-dessous prend dans une liste de tâches
 * la plus prioritaire.
 *
 * Une fonction exécutant une tâche doit retourner un nombre :
 * - nul, si la tache n'a pas à être effecutée
 * - positif, si la tache a été effectuée
 * - négatif, si la tache doit être poursuivie ou recommencée
 *
 * Elle recoit en argument la date de la dernière exécution de la tâche.
 *
 * On peut appeler cette fonction avec d'autres tâches (pour étendre SPIP)
 * spécifiée par des fonctions respectant le protocole ci-dessus.
 *
 * On peut ajouter des tâches périodiques ou modifier la fréquence
 * de chaque tâche et leur priorité en utilisant le pipeline
 * `taches_generales_cron`.
 *
 * On peut également directement en déclarer avec la balise `genie` d'un paquet.xml
 * de plugin, tel que `<genie nom="nom_de_la_tache" periode="86400" />`
 *
 * @package SPIP\Core\Genie
 **/


/**
 * Prévoit l'exécution de la tâche cron la plus urgente
 *
 * Les tâches sont dans un tableau `'nom de la tâche' => périodicité`
 *
 * Cette fonction exécute la tache la plus urgente, c'est à dire
 * celle dont la date de dernière exécution + la périodicité est minimale.
 *
 * La date de la prochaîne exécution de chaque tâche est indiquée dans la
 * table SQL `spip_jobs`
 *
 * La fonction exécutant la tâche est (généralement) un homonyme de préfixe "genie_".
 * Le fichier homonyme du repertoire "genie/" est automatiquement lu
 * et il est supposé définir cette fonction.
 *
 * @uses queue_add_job() Lorsqu'une tâche est à forcer
 * @uses queue_schedule()
 * @see  taches_generales() Liste des tâches déclarées
 *
 * @param array $taches
 *     Tâches dont on force maintenant l'exécution le plus tôt possible.
 *     Sinon, prendra la tâche la plus prioritaire.
 * @return
 **/
function inc_genie_dist($taches = []) {
	include_spip('inc/queue');

	if (_request('exec') == 'job_queue') {
		return false;
	}

	$force_jobs = [];
	// l'ancienne facon de lancer une tache cron immediatement
	// etait de la passer en parametre a ing_genie_dist
	// on reroute en ajoutant simplement le job a la queue, ASAP
	foreach ($taches as $function => $period) {
		$force_jobs[] = queue_add_job(
			$function,
			_T('tache_cron_asap', ['function' => $function]),
			[time() - abs($period)],
			'genie/'
		);
	}

	// et on passe la main a la gestion de la queue !
	// en forcant eventuellement les jobs ajoute a l'instant
	return queue_schedule(count($force_jobs) ? $force_jobs : null);
}

//
// Construction de la liste des taches.
// la cle est la tache,
// la valeur le temps minimal, en secondes, entre deux memes taches
// NE PAS METTRE UNE VALEUR INFERIEURE A 30
// les serveurs Http n'accordant en general pas plus de 30 secondes
// a leur sous-processus
//
// https://code.spip.net/@taches_generales
function taches_generales($taches_generales = []) {

	// verifier que toutes les taches cron sont planifiees
	// c'est une tache cron !
	$taches_generales['queue_watch'] = 3600 * 24;

	// MAJ des rubriques publiques (cas de la publication post-datee)
	// est fait au coup par coup a present
	//	$taches_generales['rubriques'] = 3600;

	// Optimisation de la base
	$taches_generales['optimiser'] = 3600 * 48;

	// nouveautes
	if (
		isset($GLOBALS['meta']['adresse_neuf']) and $GLOBALS['meta']['adresse_neuf']
		and $GLOBALS['meta']['jours_neuf']
		and ($GLOBALS['meta']['quoi_de_neuf'] == 'oui')
	) {
		$taches_generales['mail'] = 3600 * 24 * $GLOBALS['meta']['jours_neuf'];
	}

	// maintenance (ajax, verifications diverses)
	$taches_generales['maintenance'] = 3600 * 2;

	// verifier si une mise a jour de spip est disponible (2 fois par semaine suffit largement)
	$taches_generales['mise_a_jour'] = 3 * 24 * 3600;

	return pipeline('taches_generales_cron', $taches_generales);
}

/**
 * Une tâche périodique pour surveiller les tâches crons et les relancer si besoin
 *
 * Quand ce cron s'execute, il n'est plus dans la queue, donc il se replanifie
 * lui même, avec last=time()
 * avec une dose d'aleatoire pour ne pas planifier toutes les taches au meme moment
 *
 * @uses taches_generales()
 * @uses queue_genie_replan_job()
 *
 * @return int
 */
function genie_queue_watch_dist() {
	static $deja_la = false;
	if ($deja_la) {
		return;
	} // re-entrance si l'insertion des jobs echoue (pas de table spip_jobs a l'upgrade par exemple)
	$deja_la = true;
	$taches = taches_generales();
	$programmees = sql_allfetsel('fonction', 'spip_jobs', sql_in('fonction', array_keys($taches)));
	$programmees = array_column($programmees, 'fonction');
	foreach ($taches as $tache => $periode) {
		if (!in_array($tache, $programmees)) {
			queue_genie_replan_job($tache, $periode, time() - round(rand(1, $periode)), 0);
		}
	}
	$deja_la = false;

	return 1;
}

/**
 * Replanifier une tache periodique
 *
 * @param string $function
 *   nom de la fonction a appeler
 * @param int $period
 *   periodicite en secondes
 * @param int $last
 *   date du dernier appel (timestamp)
 * @param int $time
 *   date de replanification
 *   si null calculee automaitquement a partir de $last et $period
 *   si 0  = asap mais on n'insere pas le job si deja en cours d'execution
 * @param int $priority
 *   priorite
 * @return void
 */
function queue_genie_replan_job($function, $period, $last = 0, $time = null, $priority = 0) {
	static $done = [];
	if (isset($done[$function])) {
		return;
	}
	$done[$function] = true;
	if (is_null($time)) {
		$time = time();
		if ($last) {
			$time = max($last + $period, $time);
		}
	}
	if (!$last) {
		$last = $time - $period;
	}
	spip_log("replan_job $function $period $last $time $priority", 'queue');
	include_spip('inc/queue');
	// on replanifie un job cron
	// uniquement si il n'y en a pas deja un avec le meme nom
	// independament de l'argument
	queue_add_job(
		$function,
		_T('tache_cron_secondes', ['function' => $function, 'nb' => $period]),
		[$last],
		'genie/',
		'function_only',
		$time,
		$priority
	);
}
