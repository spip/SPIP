<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CRON")) return;
define("_ECRIRE_INC_CRON", "1");


// ---------------------------------------------------------------------------------------------
// Gestion des taches de fond


//
// Demarrer les referers
//
function cron_referers($t) {
	ecrire_meta("date_stats_referers", $t);
	ecrire_meta('calculer_referers_now', 'oui');
	ecrire_metas();
}

function cron_referers_suite() {
	if (timeout('archiver_stats')) {
		include_ecrire("inc_statistiques.php3");
		ecrire_meta('calculer_referers_now', 'non');
		ecrire_metas();
		calculer_referers();
	}
}


//
// Archiver les stats du jour
//
function cron_archiver_stats($last_date) {
	if (timeout('archiver_stats')) {
		include_ecrire("inc_meta.php3");
		include_ecrire("inc_statistiques.php3");
		ecrire_meta("date_statistiques", date("Y-m-d"));
		ecrire_metas();
		calculer_visites($last_date);

		if (lire_meta('activer_statistiques_ref') == 'oui') {
			// purger les referers du jour
			spip_query("UPDATE spip_referers SET visites_jour=0");
			// poser un message pour traiter les referers au prochain hit
			ecrire_meta('calculer_referers_now','oui');
			ecrire_metas();
		}
	}
}

//
// La fonction de base qui distribue les taches
//
function spip_cron() {
	global $flag_ecrire, $dir_ecrire, $use_cache, $db_ok;

	include_ecrire("inc_connect.php3");
	if (!$db_ok) {
		@touch($dir_ecrire.'data/mysql_out');
		spip_log('pas de connexion DB pour taches de fond (cron)');
		return;
	}

	@touch($dir_ecrire.'data/cron.lock');

	include_ecrire("inc_meta.php3");
	$t = time();


	//
	// Envoi du mail quoi de neuf
	//
	if (!$flag_ecrire AND (lire_meta('quoi_de_neuf') == 'oui') AND ($jours_neuf = lire_meta('jours_neuf'))
	AND ($adresse_neuf = lire_meta('adresse_neuf')) AND (time() - ($majnouv = lire_meta('majnouv'))) > 3600 * 24 * $jours_neuf) {

		if (timeout('quoide_neuf')) {
			ecrire_meta('majnouv', time());
			ecrire_metas();

			// preparation mail
			unset ($mail_nouveautes);
			unset ($sujet_nouveautes);
			$fond = 'nouveautes';
			$delais = 0;
			$contexte_inclus = array('date' => date('Y-m-d H:i:s', $majnouv));
			include(inclure_fichier($fond, $delais, $contexte_inclus));

			// envoi
			if ($mail_nouveautes) {
				spip_log("envoi mail nouveautes");
				include_ecrire('inc_mail.php3');
				envoyer_mail($adresse_neuf, $sujet_nouveautes, $mail_nouveautes);
			} else
				spip_log("envoi mail nouveautes : pas de nouveautes");
		}
	}


	// statistiques
	if (lire_meta("activer_statistiques") != "non") {
		if ($t - lire_meta('date_stats_referers') > 3600)
			cron_referers($t);
	
		if (lire_meta('calculer_referers_now') == 'oui')
			cron_referers_suite();
	
		if (date("Y-m-d") <> ($last_date = lire_meta("date_statistiques")))
			cron_archiver_stats($last_date);
	
		if ($t - lire_meta('date_stats_popularite') > 1800) {
			if (timeout('archiver_stats')) {
				include_ecrire("inc_statistiques.php3");
				calculer_popularites();
			}
		}

		if (timeout(false, false))	// no lock, no action
		{
			// Conditions declenchant un eventuel calcul des stats
			if ((lire_meta('calculer_referers_now') == 'oui')
			OR (date("Y-m-d") <> lire_meta("date_statistiques"))
			OR (time() - lire_meta('date_stats_popularite') > 1800)) {
				include_local ("inc-stats.php3");
				archiver_stats();
			}
		}
	}

	// recalcul des rubriques publiques (cas de la publication post-datee)
	if (($t - lire_meta('calcul_rubriques') > 3600) AND timeout('calcul_rubriques')) {
		ecrire_meta('calcul_rubriques', $t);
		ecrire_metas();
		include_ecrire('inc_rubriques.php3');
		calculer_rubriques();
	}


	//
	// Faire du menage dans le cache (effacer les fichiers tres anciens ou inutilises)
	// Se declenche une fois par heure quand le cache n'est pas recalcule
	//
	if (!$flag_ecrire AND $use_cache AND @file_exists('CACHE/.purge2')) {
		if (timeout('purge_cache')) {
			unlink('CACHE/.purge2');
			spip_log("purge cache niveau 2");
			$query = "SELECT fichier FROM spip_forum_cache WHERE maj < DATE_SUB(NOW(), INTERVAL 14 DAY)";
			$result = spip_query($query);
			unset($fichiers);
			while ($row = spip_fetch_array($result)) {
				$fichier = $row['fichier'];
				if (!@file_exists("CACHE/$fichier")) $fichiers[] = "'$fichier'";
			}
			if ($fichiers) {
				$query = "DELETE FROM spip_forum_cache WHERE fichier IN (".join(',', $fichiers).")";
				spip_query($query);
			}
		}
	}
	if (!$flag_ecrire AND $use_cache AND @file_exists('CACHE/.purge')) {
		if (timeout('purge_cache')) {
			$dir = 'CACHE/'.dechex((time() / 3600) & 0xF);
			unlink('CACHE/.purge');
			spip_log("purge cache niveau 1: $dir");
			$f = fopen('CACHE/.purge2', 'w');
			fclose($f);
			include_local ("inc-cache.php3");
			purger_repertoire($dir, 14 * 24 * 3600);
		}
	}



	//
	// Gerer l'indexation automatique
	//

	if (lire_meta('activer_moteur') == 'oui') {
		$fichier_index = $dir_ecrire.'data/.index';
		if ($id_article OR $id_auteur OR $id_breve OR $id_mot OR $id_rubrique) {
			include_ecrire("inc_index.php3");
			$s = '';
			if ($id_article AND !deja_indexe('article', $id_article))
				$s .= "article $id_article\n";
			if ($id_auteur AND !deja_indexe('auteur', $id_auteur))
				$s .= "auteur $id_auteur\n";
			if ($id_breve AND !deja_indexe('breve', $id_breve))
				$s .= "breve $id_breve\n";
			if ($id_mot AND !deja_indexe('mot', $id_mot))
				$s .= "mot $id_mot\n";
			if ($id_rubrique AND !deja_indexe('rubrique', $id_rubrique))
				$s .= "rubrique $id_rubrique\n";
			if ($s) {
				if ($f = @fopen($fichier_index, 'a')) {
					fputs($f, $s);
					fclose($f);
				}
			}
		}
		if ($use_cache AND @file_exists($fichier_index)) {
			if (timeout('indexation')) {
				include_ecrire("inc_index.php3");
				effectuer_une_indexation();
			}
		}
	}


	//
	// Mise a jour d'un (ou de zero) site syndique
	//

	if (lire_meta("activer_syndic") == "oui") {
		if (timeout()) {
			include_ecrire("inc_sites.php3");
			executer_une_syndication();
			if (lire_meta('activer_moteur') == 'oui') {
				include_ecrire("inc_index.php3");
				executer_une_indexation_syndic();
			}
		}
	}


	//
	// Effacement de la poubelle (documents supprimes)
	//

	if (@file_exists($fichier_poubelle = $dir_ecrire.'data/.poubelle')) {
		if (timeout('poubelle')) {
			if ($s = sizeof($suite = file($fichier_poubelle))) {
				$s = $suite[$n = rand(0, $s)];
				$s = trim($s);

				// Verifier qu'on peut vraiment effacer le fichier...
				$query = "SELECT id_document FROM spip_documents WHERE fichier='$s'";
				$result = spip_query($query);
				if (spip_num_rows($result) OR !ereg('^IMG/', $s) OR strpos($s, '..')) {
					spip_log("Tentative d'effacement interdit: $s");
				}
				else {
					@unlink($s);
				}
				unset($suite[$n]);
				$f = fopen($fichier_poubelle, 'wb');
				fwrite($f, join("", $suite));
				fclose($f);
			}
		}
		else @unlink($fichier_poubelle);
	}
}


?>