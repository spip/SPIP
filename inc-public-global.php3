<?php

include ("ecrire/inc_version.php3");
include_local ("inc-cache.php3");

if ($INSECURE['fond'] || $INSECURE['delais']) exit;
if (!isset($delais))
	$delais = 1 * 3600;

/*function t($s = '') {
	global $t0, $t1;
	$t1 = explode(" ", microtime());
	$dt = floor(1000 * ($t1[0] + $t1[1] - $t0[0] - $t0[1])) / 1000;
	echo "<p>";
	if ($s) echo "<b>$s :</b> ";
	echo "$dt secondes</p>\n";
	$t0 = $t1;
}*/

//
// Inclusions de squelettes
//

function inclure_fichier($fond, $delais, $contexte_inclus = "") {
	$fichier_requete = $fond;
	if (is_array($contexte_inclus)) {
		reset($contexte_inclus);
		while(list($key, $val) = each($contexte_inclus)) $fichier_requete .= '&'.$key.'='.$val;
	}
	$fichier_cache = generer_nom_fichier_cache($fichier_requete);
	$chemin_cache = "CACHE/$fichier_cache";

	$use_cache = utiliser_cache($chemin_cache, $delais);

	if (!$use_cache) {
		include_local("inc-calcul.php3");
		$timer_a = explode(" ", microtime());
		$fond = chercher_squelette($fond, $contexte_inclus['id_rubrique']);
		$page = calculer_page($fond, $contexte_inclus);
		$timer_b = explode(" ", microtime());
		if ($page) {
			$timer = ceil(1000 * ($timer_b[0] + $timer_b[1] - $timer_a[0] - $timer_a[1]));
			$taille = ceil(strlen($page) / 1024);
			spip_log("inclus ($timer ms): $chemin_cache ($taille ko, delai: $delais s)");
			$chemin_cache = ecrire_fichier_cache($chemin_cache, $page);
		}
	}
	return $chemin_cache;
}


//
// Gestion du cache et calcul de la page
//

$fichier_requete = $REQUEST_URI;
$fichier_requete = strtr($fichier_requete, '?', '&');
$fichier_requete = eregi_replace('&(submit|valider|PHPSESSID|(var_[^=&]*)|recalcul)=[^&]*', '', $fichier_requete);

$fichier_cache = generer_nom_fichier_cache($fichier_requete);
$chemin_cache = "CACHE/$fichier_cache";

$use_cache = utiliser_cache($chemin_cache, $delais);

$ecraser_cache = false;
$cache_supprimes = Array();

if (!$use_cache OR !defined("_ECRIRE_INC_META_CACHE")) {
	include_ecrire("inc_meta.php3");
}


//
// Authentification
//

if ($HTTP_COOKIE_VARS['spip_session'] OR $PHP_AUTH_USER) {
	include_ecrire ("inc_session.php3");
	verifier_visiteur();
}


//
// Ajouter un forum
//

if ($ajout_forum) {
	include_local ("inc-forum.php3");
	ajout_forum();
}

if (!$use_cache) {
	$lastmodified = time();
	if (($lastmodified - lire_meta('date_purge_cache')) > 3600) {
		ecrire_meta('date_purge_cache', $lastmodified);
		$f = fopen('CACHE/.purge', 'w');
		fclose($f);
	}

	//
	// Recalculer le cache
	//

	$calculer_cache = true;

	// Gestion '=chapo'
	// + ne pas cacher si l'URL d'un article est demande avant sa publication
	// (une seule requete, deux usages)
	if ($id_article = intval($id_article)) {
		$query = "SELECT chapo FROM spip_articles WHERE id_article='$id_article' AND statut='publie'";
		$result = spip_query($query);
		$row = spip_fetch_array($result);
		if (!$row)
			$ecraser_cache = true;

		if (substr($row['chapo'], 0, 1) == '=') {
			include_ecrire('inc_texte.php3');

			$regs = array('','','',substr($row['chapo'], 1));
			list(,$url) = extraire_lien($regs);
			$url = addslashes($url);
			$texte = "<"."?php @header (\"Location: $url\"); ?".">";
			$calculer_cache = false;
			spip_log("redirection: $url");
			$chemin_cache = ecrire_fichier_cache($chemin_cache, $texte);
		}
	}

	if ($calculer_cache) {
		include_local ("inc-calcul.php3");
		$timer_a = explode(" ", microtime());
		$page = calculer_page_globale($fond);
		$timer_b = explode(" ", microtime());
		if ($page) {
			$timer = ceil(1000 * ($timer_b[0] + $timer_b[1] - $timer_a[0] - $timer_a[1]));
			$taille = ceil(strlen($page) / 1024);
			spip_log("calcul ($timer ms): $chemin_cache ($taille ko, delai: $delais s)");
			$chemin_cache = ecrire_fichier_cache($chemin_cache, $page);
		}
	}
}


//
// si $var_recherche est positionnee, on met en rouge les mots cherches (php4 uniquement)
//

if ($var_recherche AND $flag_ob AND $flag_pcre AND !$flag_preserver AND !$mode_surligne) {
	include_ecrire("inc_surligne.php3");
	$mode_surligne = 'auto';
	timeout(false, true, false);	// no lock, action, no mysql
	ob_start();
}
else {
	unset ($var_recherche);
	unset ($mode_surligne);
}

//
// Inclusion du cache pour envoyer la page au client
//

$effacer_cache = !$delais;
$effacer_cache |= $ecraser_cache;	// ecraser le cache de l'article x s'il n'est pas publie

// Envoyer les entetes
$headers_only = ($HTTP_SERVER_VARS['REQUEST_METHOD'] == 'HEAD');
if ($HTTP_SERVER_VARS['REQUEST_METHOD'] == 'HEAD') $flag_dynamique = true;
if (!$effacer_cache && !$flag_dynamique && $recalcul != 'oui' && !$HTTP_COOKIE_VARS['spip_admin']) {
	if ($lastmodified) {
		$gmoddate = gmdate("D, d M Y H:i:s", $lastmodified);
		@Header ("Last-Modified: ".$gmoddate." GMT");
		$if_modified_since = ereg_replace(';.*$', '', $HTTP_IF_MODIFIED_SINCE);
		if ($if_modified_since == $gmoddate) {
			@Header("HTTP/1.0 304 Not Modified");
			$headers_only = true;
		}
		@Header ("Expires: ".gmdate("D, d M Y H:i:s", $lastmodified + $delais)." GMT");
	}
}
else {
	@Header("Expires: 0");
	@Header("Cache-Control: no-cache,must-revalidate");
	@Header("Pragma: no-cache");
}
if (!$flag_preserver) {
	@Header("Content-Type: text/html; charset=".lire_meta('charset'));
}


// Envoyer la page
if (file_exists($chemin_cache) && !$headers_only) {
	include($chemin_cache);
} else if (!$flag_preserver) {
	// Message d'erreur base de donnees
	include_ecrire('inc_presentation.php3');
	install_debut_html(_T('info_travaux_titre'));
	echo "<p>"._T('titre_probleme_technique')."</p>\n";
	install_fin_html();
}


// suite et fin mots en rouge
if ($var_recherche)
	fin_surligne($var_recherche, $mode_surligne);


// nettoie
if ($effacer_cache) @unlink($chemin_cache);
while (list(, $chemin_cache_supprime) = each($cache_supprimes))
	@unlink($chemin_cache_supprime);


//
// Verifier la presence du .htaccess dans le cache, sinon le generer
//

if (!file_exists("CACHE/.htaccess")) {
	if ($hebergeur == 'nexenservices'){
		echo "<font color=\"#FF0000\">IMPORTANT : </font>";
		echo "Votre h&eacute;bergeur est Nexen Services.<br />";
		echo "La protection du r&eacute;pertoire <i>CACHE/</i> doit se faire par l'interm&eacute;diaire de ";
		echo "<a href=\"http://www.nexenservices.com/webmestres/htlocal.php\" target=\"_blank\">l'espace webmestres</a>.";
		echo "Veuillez cr&eacute;er manuellement la protection pour ce r&eacute;pertoire (un couple login/mot de passe est n&eacute;cessaire).<br />";
	}
	else{
		$f = fopen("CACHE/.htaccess", "w");
		fputs($f, "deny from all\n");
		fclose($f);
	}
}


//
// Fonctionnalites administrateur (declenchees par le cookie admin, authentifie ou non)
//

if ($HTTP_COOKIE_VARS['spip_admin'] AND !$flag_preserver AND !$flag_boutons_admin) {
	include_local("inc-admin.php3");
	afficher_boutons_admin();
}


// envoyer la page si possible
@flush();


// ---------------------------------------------------------------------------------------------
// Taches de fond

//
// Envoi du mail quoi de neuf
//
if ((lire_meta('quoi_de_neuf') == 'oui') AND ($jours_neuf = lire_meta('jours_neuf'))
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


// Mise a jour des fichiers langues de l'espace public
if ($cache_lang_modifs) {
	include_ecrire('inc_lang.php3');
	ecrire_caches_langues();
}


// recalcul des rubriques publiques (cas de la publication post-datee)
if ((time()-lire_meta('calcul_rubriques') > 3600) AND timeout('calcul_rubriques')) {
	include_ecrire('inc_meta.php3');
	ecrire_meta('calcul_rubriques', time());
	ecrire_metas();
	include_ecrire('inc_rubriques.php3');
	calculer_rubriques();
}


//
// Faire du menage dans le cache (effacer les fichiers tres anciens ou inutilises)
// Se declenche une fois par heure quand le cache n'est pas recalcule
//
if ($use_cache AND file_exists('CACHE/.purge2')) {
	if (timeout('purge_cache')) {
		unlink('CACHE/.purge2');
		spip_log("purge cache niveau 2");
		$query = "SELECT fichier FROM spip_forum_cache WHERE maj < DATE_SUB(NOW(), INTERVAL 14 DAY)";
		$result = spip_query($query);
		unset($fichiers);
		while ($row = spip_fetch_array($result)) {
			$fichier = $row['fichier'];
			if (!file_exists("CACHE/$fichier")) $fichiers[] = "'$fichier'";
		}
		if ($fichiers) {
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN (".join(',', $fichiers).")";
			spip_query($query);
		}
	}
}
if ($use_cache AND file_exists('CACHE/.purge')) {
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
// Archivage des statistiques du site public
//

if (lire_meta("activer_statistiques") != "non") {
	if (timeout(false, false))	// no lock, no action
	{
		// Conditions declanchant un eventuel calcul des stats
		if ((lire_meta('calculer_referers_now') == 'oui')
		OR (date("Y-m-d") <> lire_meta("date_statistiques"))
		OR (time() - lire_meta('date_stats_popularite') > 1800)) {
			include_local ("inc-stats.php3");
			archiver_stats();
		}
	}
}


//
// Gerer l'indexation automatique
//

if (lire_meta('activer_moteur') == 'oui') {
	$fichier_index = 'ecrire/data/.index';
	if ($db_ok) {
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
	if ($use_cache AND file_exists($fichier_index)) {
		if (timeout('indexation')) {
			if ($s = sizeof($suite = file($fichier_index))) {
				include_ecrire("inc_texte.php3");
				include_ecrire("inc_filtres.php3");
				include_ecrire("inc_index.php3");
				$s = $suite[$n = rand(0, $s)];
				unset($suite[$n]);
				$f = fopen($fichier_index, 'wb');
				fwrite($f, join("", $suite));
				fclose($f);
				$s = explode(' ', trim($s));
				indexer_objet($s[0], $s[1], $s[2]);
			}
			else @unlink($fichier_index);
		}
	}
}


//
// Mise a jour d'un (ou de zero) site syndique
//

if ($db_ok AND lire_meta("activer_syndic") != "non") {
	if (timeout()) {
		include_ecrire("inc_texte.php3");
		include_ecrire("inc_filtres.php3");
		include_ecrire("inc_sites.php3");
		include_ecrire("inc_index.php3");

		executer_une_syndication();
		if (lire_meta('activer_moteur') == 'oui' AND timeout())
			executer_une_indexation_syndic();
	}
}


//
// Gestion des statistiques du site public
// (a la fin pour ne pas forcer le $db_ok)
//

if (lire_meta("activer_statistiques") != "non") {
	include_local ("inc-stats.php3");
	ecrire_stats();
}


?>
