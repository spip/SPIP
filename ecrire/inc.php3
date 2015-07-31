<?php


if (!file_exists("inc_connect.php3")) {
	@header("Location: install.php3");
	exit;
}

include ("inc_version.php3");

include_ecrire("inc_auth.php3");

include_ecrire("inc_lang.php3");
include_ecrire("inc_presentation.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_layer.php3");


if (!file_exists("inc_meta_cache.php3")) ecrire_metas();


//
// Preferences de presentation
//

if ($set_couleur) {
	$prefs['couleur'] = floor($set_couleur);
	$prefs_mod = true;
}
if ($set_disp) {
	$prefs['display'] = floor($set_disp);
	$prefs_mod = true;
}
if ($set_options == 'avancees' OR $set_options == 'basiques') {
	$prefs['options'] = $set_options;
	$prefs_mod = true;
}
if ($set_lang) {
	if (changer_langue($set_lang)) {
		$prefs['spip_lang'] = $set_lang;
		$prefs_mod = true;
	}
}
if ($securite == 'normal' || $securite == 'strict') {
	$prefs['securite'] = $securite;
	$secu = 'oui';
	$prefs_mod = true;
}

if ($prefs_mod) {
	spip_query ("UPDATE spip_auteurs SET prefs = '".addslashes(serialize($prefs))."' WHERE id_auteur = $connect_id_auteur");
}

if ($set_ecran) {	// on pose un cookie long car ce reglage depend plus du navigateur que de l'utilisateur
	spip_setcookie('spip_ecran', $set_ecran, time() + 365 * 24 * 3600);
	$spip_ecran = $set_ecran;
}
if (!$spip_ecran) $spip_ecran = "etroit";


// Debloquer articles
if ($debloquer_article) {
	$query = "UPDATE spip_articles SET auteur_modif='0' WHERE id_article='$debloquer_article'";
	spip_query ($query);
}


// deux globales (compatibilite ascendante)
$options      = $prefs['options'];
$spip_display = $prefs['display'];

// fixer la langue
if ($prefs['spip_lang']) {
	changer_langue($prefs['spip_lang']);
}

switch ($prefs['couleur']) {
	case 1:
		/// Vert
		$couleur_foncee="#02531B";
		$couleur_claire="#CFFEDE";
		$couleur_lien_off="#304C38";
		$couleur_lien="#854270";
		break;
	case 2:
		/// Rouge
		$couleur_foncee="#640707";
		$couleur_claire="#FFE0E0";
		$couleur_lien="#346868";
		$couleur_lien_off="#684747";
		break;
	case 3:
		/// Jaune
		$couleur_foncee="#666500";
		$couleur_claire="#FFFFE0";
		$couleur_lien="#65659C";
		$couleur_lien_off="#6A6A43";
		break;
	case 4:
		/// Violet
		$couleur_foncee="#340049";
		$couleur_claire="#F9EBFF";
		$couleur_lien="#396B25";
		$couleur_lien_off="#472854";
		break;
	case 5:
		/// Gris
		$couleur_foncee="#3F3F3F";
		$couleur_claire="#F2F2F2";
		$couleur_lien="#854270";
		$couleur_lien_off="#666666";
		break;
	case 6:
		/// Bleu
		$couleur_foncee="#3874B0";
		$couleur_claire="#EDF3FE";
		$couleur_lien="#814E1B";
		$couleur_lien_off="#435E79";
		break;
	case 7:
		/// Bleu pastelle
		$couleur_foncee="#766CF6";
		$couleur_claire="#EBE9FF";
		$couleur_lien="#869100";
		$couleur_lien_off="#5B55A0";
		break;
	case 8:
		/// Vert pastelles
		$couleur_foncee="#009F3C";
		$couleur_claire="#E2FDEC";
		$couleur_lien="#EE0094";
		$couleur_lien_off="#02722C";
		break;
	case 9:
		/// Rouge vif
		$couleur_foncee="#FF0000";
		$couleur_claire="#FFEDED";
		$couleur_lien="#D302CE";
		$couleur_lien_off="#D40202";
		break;
	case 10:
		/// Orange
		$couleur_foncee="#E95503";
		$couleur_claire="#FFF2EB";
		$couleur_lien="#81A0C1";
		$couleur_lien_off="#FF5B00";
		break;
	case 11:
		/// Violet clair
		$couleur_foncee="#CD006F";
		$couleur_claire="#FDE5F2";
		$couleur_lien="#E95503";
		$couleur_lien_off="#8F004D";
		break;
	case 12:
		/// Marron
		$couleur_foncee="#8C6635";
		$couleur_claire="#F5EEE5";
		$couleur_lien="#1A64DF";
		$couleur_lien_off="#955708";
		break;
	default:
		/// Bleu
		$couleur_foncee="#3874B0";
		$couleur_claire="#EDF3FE";
		$couleur_lien="#814E1B";
		$couleur_lien_off="#435E79";
}


//
// Gestion de version
//

$version_installee = (double) lire_meta("version_installee");
if ($version_installee <> $spip_version) {
	debut_page();
	if (!$version_installee) $version_installee = _T('info_anterieur');
	echo "<blockquote><blockquote><h4><font color='red'>"._T('info_message_technique')."</font><br> "._T('info_procedure_maj_version')."</h4>
	"._T('info_administrateur_site_01')."<a href='upgrade.php3'>"._T('info_administrateur_site_02')."</a></blockquote></blockquote><p>";
	fin_page();
	exit;
}


//
// Gestion de la configuration globale du site
//

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
	$nom_site_spip = _T('info_mon_site_spip');
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
	list($n) = spip_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_article FROM spip_articles WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prepa' OR statut='prop') LIMIT 0,1";
	list($n) = spip_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_breve FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 0,1";
	list($n) = spip_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 0,1";
	list($n) = spip_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_document FROM spip_documents_rubriques WHERE id_rubrique='$id_rubrique' LIMIT 0,1";
	list($n) = spip_fetch_row(spip_query($query));
	if ($n > 0) return false;

	return true;
}


//
// Recuperation du cookie
//

$cookie_admin = $HTTP_COOKIE_VARS['spip_admin'];



//
// Supprimer / valider forum
//

function changer_statut_forum($id_forum, $statut) {
	global $connect_statut, $connect_toutes_rubriques;

	if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) return;

	$query = "SELECT * FROM spip_forum WHERE id_forum=$id_forum";
	$result = spip_query($query);
 	if ($row = spip_fetch_array($result)) {
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
		if ($result) while ($row = spip_fetch_array($result)) {
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
if ($supp_forum_priv) changer_statut_forum($supp_forum_priv, 'privoff');
if ($valid_forum) changer_statut_forum($valid_forum, 'publie');


//
// Recalculer les secteurs de chaque article, rubrique, syndication
//

function calculer_secteurs() {
	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0";
	$result = spip_query($query);

	while ($row = spip_fetch_array($result)) $secteurs[] = $row['id_rubrique'];
	if (!$secteurs) return;

	while (list(, $id_secteur) = each($secteurs)) {
		$rubriques = "$id_secteur";
		$rubriques_totales = $rubriques;
		while ($rubriques) {
			$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($rubriques)";
			$result = spip_query($query);

			unset($rubriques);
			while ($row = spip_fetch_array($result)) $rubriques[] = $row['id_rubrique'];
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


function calculer_dates_rubriques($id_rubrique = 0, $date_parent = "0000-00-00") {
	$date_rubrique = "0000-00-00";
	if ($id_rubrique) {
		$query = "SELECT MAX(date_heure) as date_h FROM spip_breves WHERE id_rubrique=$id_rubrique AND statut='publie'";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			$date_breves = $row['date_h'];
			if ($date_breves > $date_rubrique) $date_rubrique = $date_breves;
		}
		$query = "SELECT MAX(date) AS date_h FROM spip_syndic WHERE id_rubrique=$id_rubrique AND statut='publie'";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			$date_syndic = $row['date_h'];
			if ($date_syndic > $date_rubrique) $date_rubrique = $date_syndic;
		}
		$post_dates = lire_meta("post_dates");
		if ($post_dates != "non") {
			$query = "SELECT MAX(date) AS date_h FROM spip_articles ".
				"WHERE id_rubrique=$id_rubrique AND statut = 'publie'";
		}
		else {
			$query = "SELECT MAX(date) AS date_h FROM spip_articles ".
				"WHERE id_rubrique=$id_rubrique AND statut = 'publie' AND date < NOW()";
		}
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			$date_article = $row['date_h'];
			if ($date_article > $date_rubrique) $date_rubrique = $date_article;
		}
	}

	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent=$id_rubrique";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		$date_rubrique = calculer_dates_rubriques($row['id_rubrique'], $date_rubrique);
	}
	if ($id_rubrique) {
		spip_query("UPDATE spip_rubriques SET date='$date_rubrique' WHERE id_rubrique=$id_rubrique");
	}
	if ($date_rubrique > $date_parent) $date_parent = $date_rubrique;
	return $date_parent;
}


function calculer_rubriques_publiques() {
	$post_dates = lire_meta("post_dates");

	if ($post_dates != "non") {
		$query = "SELECT DISTINCT id_rubrique FROM spip_articles WHERE statut = 'publie'";
	}
	else {
		$query = "SELECT DISTINCT id_rubrique FROM spip_articles WHERE statut = 'publie' AND date <= NOW()";
	}
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_breves WHERE statut = 'publie'";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_syndic WHERE statut = 'publie'";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_documents_rubriques";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}

	while ($rubriques) {
		$rubriques = join(",", $rubriques);
		if ($rubriques_publiques) $rubriques_publiques .= ",$rubriques";
		else $rubriques_publiques = $rubriques;
		$query = "SELECT DISTINCT id_parent FROM spip_rubriques WHERE (id_rubrique IN ($rubriques)) AND (id_parent NOT IN ($rubriques_publiques))";
		$result = spip_query($query);
		unset($rubriques);
		while ($row = spip_fetch_array($result)) {
			if ($row['id_parent']) $rubriques[] = $row['id_parent'];
		}
	}
	if ($rubriques_publiques) {
		$query = "UPDATE spip_rubriques SET statut='prive' WHERE id_rubrique NOT IN ($rubriques_publiques)";
		spip_query($query);
		$query = "UPDATE spip_rubriques SET statut='publie' WHERE id_rubrique IN ($rubriques_publiques)";
		spip_query($query);
	}
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
