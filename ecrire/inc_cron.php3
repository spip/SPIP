<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CRON")) return;
define("_ECRIRE_INC_CRON", "1");

// --------------------------
// Gestion des taches de fond
// --------------------------

// Deux difficultes:
// - la plupat des hebergeurs ne fournissent pas le Cron d'Unix
// - les scripts usuels standard sont limites a 30 secondes

// Solution:
// les scripts usuels les plus brefs, en plus de livrer la page demandee,
// s'achevent  par un appel à la fonction spip_cron.
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

// Cette fonction execute la premiere tache d'intervalle de temps expire,
// sous reserve que le serveur MySQL soit actif.
// La date de la derniere intervention est donnee par un fichier homonyme,
// de suffixe ".lock", modifie a chaque intervention et des le debut
// de celle-ci afin qu'un processus concurrent ne la demarre pas aussi.
// Les taches les plus longues sont tronconnees, ce qui impose d'antidater
// le fichier de verrouillage.
// La fonction executant la tache est un homonyme de prefixe "cron_"
// Le fichier homonyme de prefixe "inc_" et de suffixe _EXTENSION_PHP
// est automatiquement charge et est supposee la definir si ce n'est fait ici.

function spip_cron($taches=array()) {

	global $frequence_taches;
	$t = time();

	if (!@file_exists(_FILE_MYSQL_OUT)
	OR ($t - @filemtime(_FILE_MYSQL_OUT) > 300)) {
		include(_FILE_CONNECT);
		if (!$GLOBALS['db_ok']) {
			@touch(_FILE_MYSQL_OUT);
			spip_log('pas de connexion DB pour taches de fond (cron)');
			return;
		}
	}

	if (!$taches)
		$taches = taches_generales();

	include_ecrire("inc_meta.php3");

	foreach ($taches as $tache) {
		$lock = _DIR_SESSIONS . $tache . '.lock';
		clearstatcache();
		$last = (@file_exists($lock) ? filemtime($lock) : 0);

		if (($t - $frequence_taches[$tache]) > $last) {
			@touch($lock);
			spip_timer('tache');
			include_ecrire('inc_' . $tache . _EXTENSION_PHP);
			$fonction = 'cron_' . $tache;
			$code_de_retour = $fonction($last);
			if ($code_de_retour) {
				$msg = "cron: $tache";
				if ($code_de_retour < 0) {
					@touch($lock, $last);
					spip_log($msg . " (en cours, " . spip_timer('tache') .")");
					spip_timer('tache');
				}
				else
					spip_log($msg . " (" . spip_timer('tache') . ")");
				break;
			}
		}
	}
}

// Construction de la liste ordonnee des taches.
// Certaines ne sont pas activables de l'espace prive. A revoir.

function taches_generales() {

  // recalcul des rubriques publiques (cas de la publication post-datee)

	$taches_generales = array('rubriques');
	
  // cache
	if (_DIR_RESTREINT)
	  $taches_generales[]= 'invalideur';

	// nouveautes
	if (lire_meta('adresse_neuf') AND lire_meta('jours_neuf') AND (lire_meta('quoi_de_neuf') == 'oui') AND _DIR_RESTREINT)
		$taches_generales[]= 'mail';

// Stat. Attention: la popularite DOIT preceder les visites
	if (lire_meta("activer_statistiques") == "oui") {
		$taches_generales[]= 'statistiques';
		$taches_generales[]= 'popularites';
		$taches_generales[]= 'visites';
	}

	// syndication
	if (lire_meta("activer_syndic") == "oui") 
		$taches_generales[]= 'sites';

	// indexation
	if (lire_meta("activer_moteur") == "oui") 
		$taches_generales[]= 'index';

	return $taches_generales;
}

// Definit le temps minimal, en secondes, entre deux memes taches 
// NE PAS METTRE UNE VALEUR INFERIEURE A 30 (cf ci-dessus)
// ca entrainerait plusieurs execution en parallele de la meme tache
// Ces valeurs sont destinees a devenir des "metas" accessibles dans
// le panneau de configuration, comme l'est deja la premiere

global $frequence_taches;
$frequence_taches = array(
			  'mail' => 3600 * 24 * lire_meta('jours_neuf'),
			  'visites' => 3600 * 24,
			  'statistiques' => 3600,
			  'invalideur' => 3600,
			  'rubriques' => 3600,
			  'popularites' => 1800,
			  'sites' => 60,
			  'index' => 60
);

// Fonctions effectivement appelees.
// Elles sont destinees a migrer dans leur fichier homonyme.

function cron_rubriques($t) {
	calculer_rubriques();
	return 1;
}

function cron_index($t) {
	effectuer_une_indexation();
	return 1;
}

function cron_sites($t) {
	executer_une_syndication();
	if (lire_meta('activer_moteur') == 'oui') {
		include_ecrire("inc_index.php3");
		executer_une_indexation_syndic();
	}
	return 1;
}

// calcule les stats en plusieurs etapes par tranche de 100

function cron_statistiques($t) {

	$ref = calculer_n_referers(100);
	if ($ref == 100) return -1;
	// Supprimer les referers trop vieux
	supprimer_referers();
	supprimer_referers("article");
	return 1;
}

function cron_popularites($t) {
	calculer_popularites($t);
	return 1;
}

function cron_visites($t) {
	calculer_visites();
	return 1;
}

function cron_mail($t) {
	$adresse_neuf = lire_meta('adresse_neuf');
	$jours_neuf = lire_meta('jours_neuf');

	include_local("inc-calcul.php3");
	$page= cherche_page('',
				array('date' => date('Y-m-d H:i:s')),
				'nouveautes',
				'',
				lire_meta('langue_site'));
	$page = $page['texte'];
	if (substr($page,0,5) == '<'.'?php') {
# ancienne version: squelette en PHP avec affections. 1 passe de +
				unset ($mail_nouveautes);
				unset ($sujet_nouveautes);
				eval ('?' . '>' . $page);
	} else {
# nouvelle version: squelette en mode texte, 1ere ligne = sujet
# il faudrait ge'ne'raliser en produisant les Headers standars SMTP
# a` passer en 4e argument de mail. Surtout utile pour le charset.
				$page = stripslashes($page);
				$p = strpos($page,"\n");
				$sujet_nouveautes = substr($page,0,$p);
				$mail_nouveautes = ereg_replace('\$jours_neuf',
								$jours_neuf,
								substr($page,$p+1));
	}

	if ($mail_nouveautes)
		envoyer_mail($adresse_neuf, $sujet_nouveautes, $mail_nouveautes);
	return 1;
}

function cron_invalideur($t) {
	//
	// menage des vieux fichiers du cache
	// marques par l'invalideur 't' = date de fin de fichier
	//

	retire_vieux_caches();

	// En cas de quota sur le CACHE/, nettoyer les fichiers les plus vieux

	// A revoir: il semble y avoir une désynchro ici.
	
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
			include_ecrire('inc_invalideur.php3');
			suivre_invalideur("id <= $date_limite AND type in ('t', 'x')");
		}
	return 1;
}

?>
