<?php

$dir_ecrire = 'ecrire/';
include ("ecrire/inc_version.php3");
include_local ("inc-cache.php3");


//
// Inclusions de squelettes
//

function inclure_fichier($fond, $delais, $contexte_inclus = "") {
	static $seed = 0;
	if (!$seed) {
		$seed = (double) (microtime() + 1) * time();
		srand($seed);
	}

	$fichier_requete = $fond;
	if (is_array($contexte_inclus)) {
		reset($contexte_inclus);
		while(list($key, $val) = each($contexte_inclus)) $fichier_requete .= '&'.$key.'='.$val;
	}
	$fichier_cache = generer_nom_fichier_cache($fichier_requete);
	$chemin_cache = "CACHE/".$fichier_cache;

	$use_cache = utiliser_cache($chemin_cache, $delais);

	if (!$use_cache) {
		include_local("inc-calcul.php3");
		$fond = chercher_squelette($fond, $contexte_inclus['id_rubrique']);
		$page = calculer_page($fond, $contexte_inclus);
		if ($page) {
			if ($GLOBALS['flag_apc']) {
				apc_rm($chemin_cache);
			}
			$f = fopen($chemin_cache, "wb");
			fwrite($f, $page);
			fclose($f);
		}
	}
	return $chemin_cache;
}


//
// Gestion du cache et calcul de la page
//

$fichier_requete = $REQUEST_URI;
$fichier_requete = strtr($fichier_requete, '?', '&');
$fichier_requete = eregi_replace('&(submit|valider|(var_[^=&]*)|recalcul)=[^&]*', '', $fichier_requete);

$fichier_cache = generer_nom_fichier_cache($fichier_requete);
$chemin_cache = "CACHE/".$fichier_cache;

$use_cache = utiliser_cache($chemin_cache, $delais);


if ($use_cache AND file_exists("ecrire/inc_meta_cache.php3")) {
	include_ecrire("inc_meta_cache.php3");
}
else {
	include_ecrire("inc_meta.php3");
}


//
// Authentification
//
$auteur_session = '';
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
	if (($lastmodified - lire_meta('date_purge_cache')) > 24 * 3600) {
		ecrire_meta('date_purge_cache', $lastmodified);
		$f = fopen('CACHE/.purge', 'w');
		fclose($f);
	}

	//
	// Recalculer le cache
	//

	$calculer_cache = true;

	// redirection d'article via le chapo =http...
	if ($id_article) {
		$query = "SELECT chapo FROM spip_articles WHERE id_article='$id_article'";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)) {
			$chapo = $row['chapo'];
		}
		if (substr($chapo, 0, 1) == '=') {
			include_ecrire('inc_texte.php3');

			$regs = array('','','',substr($chapo, 1));
			list(,$url) = extraire_lien($regs);

			$texte = "<"."?php @header (\"Location: $url\"); ?".">";
			$calculer_cache = false;
			if ($GLOBALS['flag_apc']) {
				apc_rm($chemin_cache);
			}
			$file = fopen($chemin_cache, "wb");
			fwrite($file, $texte);
			fclose($file);
		}
	}

	if ($calculer_cache) {
		include_local ("inc-calcul.php3");
		$page = calculer_page_globale($fond);
		if ($page) {
			if ($GLOBALS['flag_apc']) {
				apc_rm($chemin_cache);
			}
			$file = fopen($chemin_cache, "wb");
			fwrite($file, $page);
			fclose($file);
		}
	}
}


//
// si $var_recherche est positionnee, on met en rouge les mots cherches (php4 uniquement)
//

if ($var_recherche AND $flag_ob AND $flag_preg_replace AND !$flag_preserver AND !$mode_surligne) {
	include_ecrire("inc_surligne.php3");
	$mode_surligne = 'auto';
	ob_start("");
} else {
	unset ($var_recherche);
	unset ($mode_surligne);
}

//
// Inclusion du cache pour envoyer la page au client
//

$effacer_cache = !$delais; // $delais peut etre modifie par une inclusion de squelette...
if (file_exists($chemin_cache)) {
	if (!$effacer_cache && !$flag_dynamique && $recalcul != 'oui') {
		if ($lastmodified) {
			@Header ("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastmodified)." GMT");
			@Header ("Expires: ".gmdate("D, d M Y H:i:s", $lastmodified + $delais)." GMT");
		}
	}
	else {
		@Header("Expires: 0");
		@Header("Cache-Control: no-cache,must-revalidate");
		@Header("Pragma: no-cache");
	}
	include ($chemin_cache);
}


//
// suite et fin mots en rouge
//

if ($var_recherche) {
	fin_surligne($var_recherche, $mode_surligne);
}


//
// nettoie
//

@flush();
if ($effacer_cache) @unlink($chemin_cache);


//
// Verifier la presence du .htaccess dans le cache, sinon le generer
//

if (!file_exists("CACHE/.htaccess")) {
	$f = fopen("CACHE/.htaccess", "w");
	fputs($f, "deny from all\n");
	fclose($f);
}


//
// Gerer l'indexation automatique
//

if (lire_meta('activer_moteur') == 'oui') {
	$fichier_index = 'ecrire/data/.index';
	if ($db_ok) {
		include_ecrire("inc_texte.php3");
		include_ecrire("inc_filtres.php3");
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
			$f = fopen($fichier_index, 'a');
			fputs($f, $s);
			fclose($f);
		}
	}
	if ($use_cache AND file_exists($fichier_index) AND $size = filesize($fichier_index)) {
		include_ecrire("inc_connect.php3");
		if ($db_ok) {
			include_ecrire("inc_texte.php3");
			include_ecrire("inc_filtres.php3");
			include_ecrire("inc_index.php3");
			$f = fopen($fichier_index, 'r');
			$s = fgets($f, 100);
			$suite = fread($f, $size);
			fclose($f);
			$f = fopen($fichier_index, 'w');
			fwrite($f, $suite);
			fclose($f);
			$s = explode(' ', $s);
			indexer_objet($s[0], $s[1], false);
		}
	}
}


//
// Faire du menage dans le cache
// (effacer les fichiers tres anciens)
// Se declenche une fois par jour quand le cache n'est pas recalcule
//

if ($use_cache && file_exists('CACHE/.purge2')) {
		if ($db_ok) {
		unlink('CACHE/.purge2');
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

if ($use_cache && file_exists('CACHE/.purge')) {
	if ($db_ok) {
		unlink('CACHE/.purge');
		$f = fopen('CACHE/.purge2', 'w');
		fclose($f);
		include_local ("inc-cache.php3");
		purger_repertoire('CACHE', 14 * 24 * 3600);
	}
}

// ---------------------------------------------------------------------------------------

//include_local ("inc-debug.php3");


//
// Fonctionnalites administrateur (declenchees par le cookie admin, authentifie ou non)
//

$cookie_admin = $HTTP_COOKIE_VARS['spip_admin'];
$admin_ok = ($cookie_admin != '');

//
// Afficher un bouton admin
//

function bouton_admin($titre, $lien) {
	$lapage=substr($lien, 0, strpos($lien,"?"));
	$lesvars=substr($lien, strpos($lien,"?") + 1, strlen($lien));

	echo "\n<FORM ACTION='$lapage' METHOD='get'>\n";
	$lesvars=explode("&",$lesvars);
	
	for($i=0;$i<count($lesvars);$i++){
		$var_loc=explode("=",$lesvars[$i]);
		if ($var_loc[0] != "submit")
			echo "<INPUT TYPE='Hidden' NAME='$var_loc[0]' VALUE='$var_loc[1]'>\n";
	}
	echo "<INPUT TYPE='submit' NAME='submit' VALUE='$titre' CLASS='spip_bouton'>\n";
	echo "</FORM>";
}


if ($admin_ok AND !$flag_preserver) {
	include_ecrire("inc_filtres.php3");

	echo '<div class="spip-admin">';

	if ($id_article) {
		bouton_admin("Modifier cet article ($id_article)", "./ecrire/articles.php3?id_article=$id_article");
	}
	else if ($id_breve) {
		bouton_admin("Modifier cette br&egrave;ve ($id_breve)", "./ecrire/breves_voir.php3?id_breve=$id_breve");
	}
	else if ($id_rubrique) {
		bouton_admin("Modifier cette rubrique ($id_rubrique)", "./ecrire/naviguer.php3?coll=$id_rubrique");
	}
	else if ($id_mot) {
		bouton_admin("Modifier ce mot-cl&eacute; ($id_mot)", "./ecrire/mots_edit.php3?id_mot=$id_mot");
	}
	else if ($id_auteur) {
		bouton_admin("Modifier cet auteur ($id_auteur)", "./ecrire/auteurs_edit.php3?id_auteur=$id_auteur");
	}

	$link = $GLOBALS['clean_link'];
	$link->addVar('recalcul', 'oui');
	$link->delVar('submit');
	echo $link->getForm('GET');
	if ($use_cache) $pop = " *";
	else $pop = "";
	echo "<input type='submit' class='spip_bouton' name='submit' value='Recalculer cette page$pop'>";
	echo "</form>\n";

	if (lire_meta("activer_statistiques") != "non" AND $id_article) {
		include_local ("inc-stats.php3");
		afficher_raccourci_stats($id_article);
	}

	echo "</div>";
}


//
// Mise a jour d'un (ou de zero) site syndique
//

if ($db_ok AND lire_meta("activer_syndic") != "non") {
	include_ecrire("inc_texte.php3");
	include_ecrire("inc_filtres.php3");
	include_ecrire("inc_sites.php3");
	include_ecrire("inc_index.php3");

	executer_une_syndication();
	executer_une_indexation_syndic();
}

//
// Gestion des statistiques du site public
//

if (lire_meta("activer_statistiques") != "non") {
	include_local ("inc-stats.php3");
	ecrire_stats();
}


//
// Envoi du mail quoi de neuf
//

$majnouv = lire_meta('majnouv');
if ((lire_meta('quoi_de_neuf')=='oui') AND ($jours_neuf=lire_meta('jours_neuf')) AND (email_valide($adresse_neuf = lire_meta('adresse_neuf'))) AND ((time() - $majnouv) > 3600*24*$jours_neuf)) {
	include_ecrire('inc_connect.php3');
	if ($db_ok) {
		// lock && indication du prochain envoi
		include_ecrire('inc_meta.php3');
		ecrire_meta('majnouv', time());
		ecrire_metas();

		// preparation mail : date de reference au format MySQL pour l'age_relatif du squelette (grrr)
		if ($majnouv)
			$date = $majnouv;
		else
			$date = time() - 3600*24*$jours_neuf;
		unset ($mail_nouveautes);
		$fond = 'nouveautes';
		$delais = 0;
		$contexte_inclus['date'] = date('Y-m-d H:i:s', $date);
		include(inclure_fichier($fond, $delais, $contexte_inclus));

		// envoi
		if ($mail_nouveautes) {
			include_ecrire('inc_mail.php3');
			$nom_site = lire_meta('nom_site');
			spip_log("envoi mail nouveautes");
			envoyer_mail($adresse_neuf, "[$nom_site] Les nouveautes", $mail_nouveautes);
		}
	}
}

?>
