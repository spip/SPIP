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
// Le fichier homonyme de prefixe "inc_" et de suffixe _EXTENSION_PHP
// est automatiquement charge si besoin, et est supposee la definir si ce
// n'est fait ici.

function spip_cron($taches = array()) {
	$t = time();
	if (@file_exists(_FILE_MYSQL_OUT)
	AND ($t - @filemtime(_FILE_MYSQL_OUT) < 300))
		return;

	include_ecrire("inc_meta");
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
		include_ecrire('inc_' . $tache);
		$fonction = 'cron_' . $tache;

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

	return $taches_generales;
}

// Fonctions effectivement appelees.
// Elles sont destinees a migrer dans leur fichier homonyme.

function cron_rubriques($t) {
	calculer_rubriques();
	return 1;
}

function cron_optimiser($t) {
	optimiser_base ();
	return 1;
}

function cron_indexation($t) {
	$c = count(effectuer_une_indexation());
	// si des indexations ont ete effectuees, on passe la periode a 0 s
	## note : (time() - 90) correspond en fait a :
	## time() - $taches_generales['indexation']
	if ($c)
		return (0 - (time() - 90));
	else
		return 0;
}

function cron_syndic($t) {
	$r = executer_une_syndication();
	if (($GLOBALS['meta']['activer_moteur'] == 'oui') &&
	    ($GLOBALS['meta']["visiter_sites"] == 'oui')) {
		include_spip("inc/indexation");
		$r2 = executer_une_indexation_syndic();
		$r = $r && $r2;
	}
	return $r;
}

//
// Calcule les stats en plusieurs etapes
//
function cron_visites($t) {
	$encore = calculer_visites($t);

	// Si ce n'est pas fini on redonne la meme date au fichier .lock
	// pour etre prioritaire lors du cron suivant
	if ($encore)
		return (0 - $t);

	return 1;
}

//
// Applique la regle de decroissance des popularites
//
function cron_popularites($t) {
	calculer_popularites();
	return 1;
}


//
// Mail des nouveautes
//
function cron_mail($t) {
	$adresse_neuf = $GLOBALS['meta']['adresse_neuf'];
	$jours_neuf = $GLOBALS['meta']['jours_neuf'];
	// $t = 0 si le fichier de lock a ete detruit
	if (!$t) $t = time() - (3600 * 24 * $jours_neuf);

	include_spip('public/calcul');
	$page= cherche_page('',
			    array('date' => date('Y-m-d H:i:s', $t),
				  'jours_neuf' => $jours_neuf),
				'nouveautes',
				'',
				$GLOBALS['meta']['langue_site']);
	$page = $page['texte'];
	if (substr($page,0,5) == '<'.'?php') {
# ancienne version: squelette en PHP avec affection des 2 variables ci-dessous
# 1 passe de plus a la sortie
				$mail_nouveautes = '';
				$sujet_nouveautes = '';
				$headers = '';
				eval ('?' . '>' . $page);
	} else {
# nouvelle version en une seule passe avec un squelette textuel:
# 1ere ligne = sujet
# lignes suivantes jusqu'a la premiere blanche: headers SMTP

				$page = stripslashes(trim($page));
				$page = preg_replace(",\r\n?,", "\n", $page);
				$p = strpos($page,"\n\n");
				$s = strpos($page,"\n");
				if ($p AND $s) {
					if ($p>$s)
						$headers = substr($page,$s+1,$p-$s);
					$sujet_nouveautes = substr($page,0,$s);
					$mail_nouveautes = trim(substr($page,$p+2));
				}
	}

	if (strlen($mail_nouveautes) > 10)
		envoyer_mail($adresse_neuf, $sujet_nouveautes, $mail_nouveautes, '', $headers);
	else
		spip_log("mail nouveautes : rien de neuf depuis $jours_neuf jours");
	return 1;
}

function cron_ajax ($t) {
	nettoyer_ajax();
	return 1;
}

function cron_invalideur($t) {
	//
	// menage des vieux fichiers du cache
	// marques par l'invalideur 't' = date de fin de fichier
	//

	retire_vieux_caches();

	// En cas de quota sur le CACHE/, nettoyer les fichiers les plus vieux

	// A revoir: il semble y avoir une desynchro ici.
	
		list ($total_cache) = spip_fetch_array(spip_query("SELECT SUM(taille)
		FROM spip_caches WHERE type IN ('t', 'x')"));
		spip_log("Taille du CACHE: $total_cache octets");

		global $quota_cache;
		$total_cache -= $quota_cache*1024*1024;
		if ($quota_cache > 0 AND $total_cache > 0) {
			$q = spip_query("SELECT id, taille FROM spip_caches
			WHERE type IN ('t', 'x') ORDER BY id");
			while ($r = spip_fetch_array($q)
			AND ($total_cache > $taille_supprimee)) {
				$date_limite = $r['id'];
				$taille_supprimee += $r['taille'];
			}
			spip_log ("Quota cache: efface $taille_supprimee octets");
			include_spip('inc/invalideur');
			suivre_invalideur("id <= $date_limite AND type in ('t', 'x')");
		}
	return 1;
}

?>
