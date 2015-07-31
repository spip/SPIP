<?php

if (!file_exists("inc_connect.php3")) {
	@header("Location: install.php3");
	exit;
}

include ("inc_version.php3");

include_ecrire("inc_connect.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_auth.php3");

include_ecrire("inc_presentation.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_mail.php3");
include_ecrire("inc_admin.php3");
include_ecrire("inc_layer.php3");
include_ecrire("inc_sites.php3");
include_ecrire("inc_index.php3");

if (!file_exists("inc_meta_cache.php3")) ecrire_metas();


//
// Gestion de version
//

$version_installee = (double) lire_meta("version_installee");
if ($version_installee < $spip_version) {
	debut_page();
	if (!$version_installee) $version_installee = "ant&eacute;rieure";
	echo "<h4>Message technique : la proc&eacute;dure de mise &agrave; jour doit &ecirc;tre lanc&eacute;e afin d'adapter
	la base de donn&eacute;es &agrave; la nouvelle version de SPIP.</h4>
	Si vous &ecirc;tes administrateur du site, veuillez <a href='upgrade.php3'>cliquer sur ce lien</a>.<p>";
	fin_page();
	exit;
}


//
// Cookies de presentation
//

$options = $HTTP_COOKIE_VARS['spip_options'];
$graphisme = $HTTP_COOKIE_VARS['spip_graphisme'];
$spip_display = $HTTP_COOKIE_VARS['spip_display'];

if (!$graphisme) $graphisme="0";
if (!$HTTP_COOKIE_VARS['spip_display']) $spip_display = 2;


$fond = substr($graphisme,0,1);

if ($set_fond) {
	$fond = floor($set_fond);
	setcookie('spip_graphisme', $fond, time()+(3600*24*365));
}

if ($set_survol) {
	setcookie('spip_survol', $set_survol, time()+(3600*24*365));
	$spip_survol=$set_survol;
}

if ($set_couleur) {
	$couleur= floor($set_couleur);
	setcookie('spip_couleur', $couleur, time()+(3600*24*365));
	$spip_couleur=$couleur;
}

if ($set_disp) {
	$display= floor($set_disp);
	setcookie('spip_display', $display, time()+(3600*24*365));
	$spip_display=$display;
}

if ($set_options == 'avancees') {
	setcookie('spip_options', 'avancees', time()+(3600*24*365));
	$options = 'avancees';
}
if ($set_options == 'basiques') {
	setcookie('spip_options', 'basiques', time()+(3600*24*365));
	$options = 'basiques';
}

global $couleur_foncee, $couleur_claire;

switch ($spip_couleur) {
	case 1:
		/// Vert
		$couleur_foncee="#02531B";
		$couleur_claire="#CFFEDE";
		break;
	case 2:
		/// Rouge
		$couleur_foncee="#640707";
		$couleur_claire="#FFE0E0";
		break;
	case 3:
		/// Jaune
		$couleur_foncee="#666500";
		$couleur_claire="#FFFFE0";
		break;
	case 4:
		/// Violet
		$couleur_foncee="#340049";
		$couleur_claire="#F9EBFF";
		break;
	case 5:
		/// Gris
		$couleur_foncee="#3F3F3F";
		$couleur_claire="#F2F2F2";
		break;
	case 6:
		/// Bleu
		$couleur_foncee="#2b539c";
		$couleur_claire="#EDF3FE";
		break;
	case 7:
		/// Bleu pastelle
		$couleur_foncee="#4085CD";
		$couleur_claire="#EDF3FE";
		break;
	case 8:
		/// Vert pastelles
		$couleur_foncee="#009F3C";
		$couleur_claire="#E2FDEC";
		break;
	case 9:
		/// Rouge vif
		$couleur_foncee="#FF0000";
		$couleur_claire="#FFEDED";
		break;
	case 10:
		/// Orange
		$couleur_foncee="#E95503";
		$couleur_claire="#FFF2EB";
		break;
	case 11:
		/// Violet clair
		$couleur_foncee="#CD006F";
		$couleur_claire="#FDE5F2";
		break;
	case 12:
		/// Marron
		$couleur_foncee="#8C6635";
		$couleur_claire="#F5EEE5";
		break;
	default:
		/// Bleu
		$couleur_foncee="#2378CF";
		$couleur_claire="#E1EEFB";
}


//
// Gestion de la configuration globale du site
//

if ($envoi_now) {
	effacer_meta('majnouv');
}

if (!$adresse_site) {
	$nom_site_spip = lire_meta("nom_site");
	$adresse_site = lire_meta("adresse_site");
}
if (!$activer_breves){
	$activer_breves = lire_meta("activer_breves");
	$articles_mots = lire_meta("articles_mots");
}

if (!$activer_statistiques){
	$activer_statistiques = lire_meta("activer_statistiques");
}

if (!$nom_site_spip) {
	$nom_site_spip = "Mon site SPIP";
	ecrire_meta("nom_site", $nom_site_spip);
	ecrire_metas();
}

if (!$adresse_site) {
	$adresse_site = "http://$HTTP_HOST".substr($REQUEST_URI, 0, strpos($REQUEST_URI, "/ecrire"));
	ecrire_meta("adresse_site", $adresse_site);
	ecrire_metas();
}


function tester_rubrique_vide($id_rubrique) {
	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent='$id_rubrique' LIMIT 0,1";
	list($n) = mysql_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_article FROM spip_articles WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prepa' OR statut='prop') LIMIT 0,1";
	list($n) = mysql_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_breve FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 0,1";
	list($n) = mysql_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 0,1";
	list($n) = mysql_fetch_row(spip_query($query));
	if ($n > 0) return false;

	return true;
}


//
// Recuperation du cookie
//

$cookie_admin = $HTTP_COOKIE_VARS["spip_admin"];


//
// Ajouter un message de forum
//

if ($ajout_forum AND strlen($texte) > 10 AND strlen($titre) > 2) {
	$titre = addslashes($titre);
	$texte = addslashes($texte);
	$nom_site = addslashes($nom_site);
	$auteur = addslashes($auteur);
	$query_forum = "INSERT INTO spip_forum (id_parent, id_rubrique, id_article, id_breve, id_message, id_syndic, date_heure, titre, texte, nom_site, url_site, auteur, email_auteur, statut, id_auteur) VALUES ('$forum_id_parent','$forum_id_rubrique','$forum_id_article','$forum_id_breve','$forum_id_message', '$forum_id_syndic', NOW(),\"$titre\",\"$texte\",\"$nom_site\",\"$url_site\",\"$auteur\",\"$email_auteur\",\"$forum_statut\",\"$connect_id_auteur\")";
	$result_forum = spip_query($query_forum);
}


//
// Supprimer / valider forum
//

function changer_statut_forum($id_forum, $statut) {
	global $connect_statut, $connect_toutes_rubriques;

	if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) return;

	$query = "SELECT * FROM spip_forum WHERE id_forum=$id_forum";
	$result = spip_query($query);
 	if ($row = mysql_fetch_array($result)) {
		$id_parent = $row['id_parent'];
		$id_rubrique = $row['id_rubrique'];
		$id_article = $row['id_article'];
		$id_breve = $row['id_breve'];
		$id_syndic = $row['id_syndic'];
	}
	else return;

	unset($where);
	if ($id_article) $where[] = "id_article=$id_article";
	if ($id_rubrique) $where[] = "id_rubrique=$id_rubrique";
	if ($id_breve) $where[] = "id_breve=$id_breve";
	if ($id_syndic) $where[] = "id_syndic=$id_syndic";
	if ($id_parent) $where[] = "id_forum=$id_parent";
	if ($where) {
		$query = "SELECT fichier FROM spip_forum_cache WHERE ".join(' OR ', $where);
		$result = spip_query($query);
		unset($fichiers);
		if ($result) while ($row = mysql_fetch_array($result)) {
			$fichier = $row['fichier'];
			@unlink("../CACHE/$fichier");
			$fichiers[] = $fichier;
		}
		if ($fichiers) {
			$fichiers = join(',', $fichiers);
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN ($fichiers)";
			spip_query($query);
		}
	}
	$query_forum = "UPDATE spip_forum SET statut='$statut' WHERE id_forum=$id_forum";
	$result_forum = spip_query($query_forum);
}

if ($supp_forum) changer_statut_forum($supp_forum, 'off');
if ($valid_forum) changer_statut_forum($valid_forum, 'publie');


//
// Recalculer les secteurs de chaque article, rubrique, syndication
//

function calculer_secteurs() {
	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0";
	$result = spip_query($query);

	while ($row = mysql_fetch_array($result)) $secteurs[] = $row['id_rubrique'];
	if (!$secteurs) return;

	while (list(, $id_secteur) = each($secteurs)) {
		$rubriques = "$id_secteur";
		$rubriques_totales = $rubriques;
		while ($rubriques) {
			$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($rubriques)";
			$result = spip_query($query);

			unset($rubriques);
			while ($row = mysql_fetch_array($result)) $rubriques[] = $row['id_rubrique'];
			if ($rubriques) {
				$rubriques = join(',', $rubriques);
				$rubriques_totales .= ",".$rubriques;
			}
		}
		$query = "UPDATE spip_articles SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = spip_query($query);
		$query = "UPDATE spip_breves SET id_rubrique=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = spip_query($query);
		$query = "UPDATE spip_rubriques SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = spip_query($query);
		$query = "UPDATE spip_syndic SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = spip_query($query);
	}
}


function calculer_dates_rubriques($id_parent="0", $date_parent="0000-00-00") {
	$query = "SELECT MAX(date_heure) as date_h FROM spip_breves WHERE id_rubrique = '$id_parent' GROUP BY id_rubrique";
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		$date_breves = $row['date_h'];
		if ($date_breves > $date_parent) $date_parent = $date_breves;
	}
	
	$query = "SELECT MAX(date) AS date_h FROM spip_syndic WHERE id_rubrique = '$id_parent' GROUP BY id_rubrique";
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		$date_syndic = $row['date_h'];
		if ($date_syndic > $date_parent) $date_parent = $date_syndic;
	}
	
	
	
	if ($post_dates != "non") {
		$query = "SELECT rubrique.id_rubrique,  MAX(articles.date) FROM spip_rubriques AS rubrique, spip_articles AS articles WHERE rubrique.id_parent='$id_parent' AND articles.id_rubrique=rubrique.id_rubrique AND articles.statut = 'publie' GROUP BY rubrique.id_rubrique";
	}
	else {
		$query = "SELECT rubrique.id_rubrique,  MAX(articles.date) AS date_h FROM spip_rubriques AS rubrique, spip_articles AS articles WHERE rubrique.id_parent='$id_parent' AND articles.id_rubrique=rubrique.id_rubrique AND articles.statut = 'publie' AND articles.date < NOW() GROUP BY rubrique.id_rubrique";
	}
	$result = spip_query($query);
	
	while ($row = mysql_fetch_array($result)) {
		$id_rubrique = $row['id_rubrique'];
		$date_rubrique = $row['date_h'];
		
		$date_rubrique = calculer_dates_rubriques($id_rubrique,$date_rubrique);
		
		if ($date_rubrique > $date_parent) $date_parent = $date_rubrique;
	}


	spip_query("UPDATE spip_rubriques SET date='$date_parent' WHERE id_rubrique='$id_parent'");

	return $date_parent;


}


function calculer_rubriques_publiques()
{
	$post_dates = lire_meta("post_dates");

	if ($post_dates != "non") {
		$query = "SELECT DISTINCT id_rubrique FROM spip_articles WHERE statut = 'publie'";
	}
	else {
		$query = "SELECT DISTINCT id_rubrique FROM spip_articles WHERE statut = 'publie' AND date < NOW()";
	}
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_breves WHERE statut = 'publie'";
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_syndic WHERE statut = 'publie'";
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}

	while ($rubriques) {
		$rubriques = join(",", $rubriques);
		if ($rubriques_publiques) $rubriques_publiques .= ",$rubriques";
		else $rubriques_publiques = $rubriques;
		$query = "SELECT DISTINCT id_parent FROM spip_rubriques WHERE (id_rubrique IN ($rubriques)) AND (id_parent NOT IN ($rubriques_publiques))";
		$result = spip_query($query);
		unset($rubriques);
		while ($row = mysql_fetch_array($result)) {
			if ($row['id_parent']) $rubriques[] = $row['id_parent'];
		}
	}
	$query = "UPDATE spip_rubriques SET statut='prive' WHERE id_rubrique NOT IN ($rubriques_publiques)";
	spip_query($query);
	$query = "UPDATE spip_rubriques SET statut='publie' WHERE id_rubrique IN ($rubriques_publiques)";
	spip_query($query);
}


//
// Recalculer l'ensemble des donnees associees a l'arborescence des rubriques
// (cette fonction est a appeler a chaque modification sur les rubriques)
//

function calculer_rubriques()
{
	calculer_secteurs();
	calculer_rubriques_publiques();
	calculer_dates_rubriques();
}


// Supprimer rubrique
if ($supp_rubrique = intval($supp_rubrique) AND $connect_statut == '0minirezo' AND acces_rubrique($supp_rubrique)) {
	$query = "DELETE FROM spip_rubriques WHERE id_rubrique=$supp_rubrique";
	$result = spip_query($query);

	calculer_rubriques();
}


?>
