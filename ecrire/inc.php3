<?php

// SPIP est-il installe ?
if (!@file_exists("inc_connect.php3")) {
	header("Location: install.php3");
	exit;
}

include ("inc_version.php3");

include_ecrire("inc_auth.php3");

include_ecrire("inc_presentation.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_layer.php3");
include_ecrire("inc_rubriques.php3");


if (!@file_exists("data/inc_meta_cache.php3")) ecrire_metas();


//
// Preferences de presentation
//

if ($lang = $GLOBALS['HTTP_COOKIE_VARS']['spip_lang_ecrire'] AND $lang <> $auteur_session['lang'] AND changer_langue($lang)) {
	spip_query ("UPDATE spip_auteurs SET lang = '".addslashes($lang)."' WHERE id_auteur = $connect_id_auteur");
	$auteur_session['lang'] = $lang;
	ajouter_session($auteur_session, $spip_session);
}

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
if ($prefs_mod) {
	spip_query ("UPDATE spip_auteurs SET prefs = '".addslashes(serialize($prefs))."' WHERE id_auteur = $connect_id_auteur");
}

if ($set_ecran) {
	// Poser un cookie, car ce reglage depend plus du navigateur que de l'utilisateur
	spip_setcookie('spip_ecran', $set_ecran, time() + 365 * 24 * 3600);
	$spip_ecran = $set_ecran;
}
if (!$spip_ecran) $spip_ecran = "etroit";


// Debloquer articles
if ($debloquer_article) {
	if ($debloquer_article <> 'tous')
		$where_id = "AND id_article=".intval($debloquer_article);
	$query = "UPDATE spip_articles SET auteur_modif='0' WHERE auteur_modif=$connect_id_auteur $where_id";
	spip_query ($query);
}

// deux globales (compatibilite ascendante)
$options      = $prefs['options'];
$spip_display = $prefs['display'];

switch ($prefs['couleur']) {
	case 6:
		/// Jaune
		$couleur_foncee="#9DBA00";
		$couleur_claire="#C5E41C";
		$couleur_lien="#657701";
		$couleur_lien_off="#A6C113";
		break;
	case 1:
		/// Violet clair
		$couleur_foncee="#eb68b3";
		$couleur_claire="#ffa9e6";
		$couleur_lien="#E95503";
		$couleur_lien_off="#8F004D";
		break;
	case 2:
		/// Orange
		$couleur_foncee="#fa9a00";
		$couleur_claire="#ffc000";
		$couleur_lien="#81A0C1";
		$couleur_lien_off="#FF5B00";
		break;
	case 3:
		/// Saumon
		$couleur_foncee="#CDA261";
		$couleur_claire="#FFDDAA";
		$couleur_lien="#5E0283";
		$couleur_lien_off="#472854";
		break;
	case 4:
		/// Bleu pastelle
		$couleur_foncee="#5da7c5";
		$couleur_claire="#97d2e1";
		$couleur_lien="#869100";
		$couleur_lien_off="#5B55A0";
		break;
	case 5:
		/// Gris
		$couleur_foncee="#727D87";
		$couleur_claire="#C0CAD4";
		$couleur_lien="#854270";
		$couleur_lien_off="#666666";
		break;
	default:
		/// Jaune
		$couleur_foncee="#9DBA00";
		$couleur_claire="#C5E41C";
		$couleur_lien="#657701";
		$couleur_lien_off="#A6C113";
}


//
// Gestion de version
//

$version_installee = (double) lire_meta("version_installee");
if ($version_installee <> $spip_version) {
	debut_page();
	if (!$version_installee) $version_installee = _T('info_anterieur');
	echo "<blockquote><blockquote><h4><font color='red'>"._T('info_message_technique')."</font><br> "._T('info_procedure_maj_version')."</h4>
	"._T('info_administrateur_site_01')." <a href='upgrade.php3'>"._T('info_administrateur_site_02')."</a></blockquote></blockquote><p>";
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

// Supprimer rubrique
if ($supp_rubrique = intval($supp_rubrique) AND $connect_statut == '0minirezo' AND acces_rubrique($supp_rubrique)) {
	$query = "DELETE FROM spip_rubriques WHERE id_rubrique=$supp_rubrique";
	$result = spip_query($query);

	calculer_rubriques();
}


?>
