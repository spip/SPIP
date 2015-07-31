<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

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

// http://doc.spip.org/@inc_genie_dist
function inc_genie_dist($taches = array()) {
	include_spip('inc/queue');

	if (_request('exec')=='job_queue')
		return false;

	$force_jobs = array();
	// l'ancienne facon de lancer une tache cron immediatement
	// etait de la passer en parametre a ing_genie_dist
	// on reroute en ajoutant simplement le job a la queue, ASAP
	foreach($taches as $function=>$period)
		$force_jobs[] = queue_add_job($function, _T('tache_cron_asap', array('function'=>$function)), array(time()-abs($period)), "genie/");
	
	// et on passe la main a la gestion de la queue !
	// en forcant eventuellement les jobs ajoute a l'instant
	return queue_schedule(count($force_jobs)?$force_jobs:null);
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
function taches_generales($taches_generales = array()) {

	// verifier que toutes les taches cron sont planifiees
	// c'est une tache cron !
	$taches_generales['queue_watch'] = 3600*24;

	// MAJ des rubriques publiques (cas de la publication post-datee)
	// est fait au coup par coup a present
	//	$taches_generales['rubriques'] = 3600;

	// Optimisation de la base
	$taches_generales['optimiser'] = 3600*48;

	// cache (chaque 10 minutes => 1/16eme du repertoire cache,
	// soit toutes les 2h40 sur le meme rep)
	$taches_generales['invalideur'] = 600;

	// nouveautes
	if ($GLOBALS['meta']['adresse_neuf'] AND $GLOBALS['meta']['jours_neuf']
	AND ($GLOBALS['meta']['quoi_de_neuf'] == 'oui'))
		$taches_generales['mail']= 3600 * 24 * $GLOBALS['meta']['jours_neuf'];

	// maintenance (ajax, verifications diverses)
	$taches_generales['maintenance'] = 3600 * 2;

	// verifier si une mise a jour de spip est disponible (2 fois par semaine suffit largement)
	$taches_generales['mise_a_jour'] = 3*24*3600;

	return pipeline('taches_generales_cron',$taches_generales);
}

// Pas de fichier a part pour une fonction aussi petite:
// - elle peut retirer les fichiers perimes
// - elle fait appliquer le quota
// En cas de quota sur le CACHE/, nettoyer les fichiers les plus vieux
// http://doc.spip.org/@genie_invalideur_dist
function genie_invalideur_dist($t) {

	include_spip('inc/invalideur');
	$encore = appliquer_quota_cache();

	// si le cache est trop gonfle, redemander la main pour poursuivre
	if ($encore)
		return (0 - $t);
	return 1;
}

/**
 * Une tache periodique pour surveiller les taches crons et les relancer si besoin
 * quand ce cron s'execute, il n'est plus dans la queue, donc il se replanifie
 * lui meme, avec last=time()
 * avec une dose d'aleatoire pour ne pas planifier toutes les taches au meme moment
 *
 * @return int
 */
function genie_queue_watch_dist(){
	static $deja_la = false;
	if ($deja_la) return; // re-entrance si l'insertion des jobs echoue (pas de table spip_jobs a l'upgrade par exemple)
	$deja_la = true;
	$taches = taches_generales();
	$programmees = sql_allfetsel('fonction','spip_jobs',sql_in('fonction',array_keys($taches)));
	$programmees = array_map('reset',$programmees);
	foreach($taches as $tache=>$periode){
		if (!in_array($tache,$programmees))
			queue_genie_replan_job($tache,$periode,time()-round(rand(1,$periode)),0);
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
function queue_genie_replan_job($function,$period,$last=0,$time=null, $priority=0){
	static $done = array();
	if (isset($done[$function])) return;
	$done[$function] = true;
	if (is_null($time)){
		$time=time();
		if ($last)
			$time = max($last+$period,$time);
	}
	if (!$last)
		$last = $time-$period;
	spip_log("replan_job $function $period $last $time $priority",'queue');
	include_spip('inc/queue');
	// on replanifie un job cron
	// uniquement si il n'y en a pas deja un avec le meme nom
	// independament de l'argument
	queue_add_job($function, _T('tache_cron_secondes', array('function'=>$function, 'nb'=>$period)), array($last), "genie/", 'function_only', $time, $priority);
}


?>
