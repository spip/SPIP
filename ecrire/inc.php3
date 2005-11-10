<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) {
	include ("inc_version.php3");
}

$var_nom = "auth";
$var_f = find_in_path('inc_' . $var_nom . '.php');

if ($var_f) 
      include($var_f);
else  include_ecrire('inc_' . $var_nom . '.php3');
    
if (function_exists($var_nom))
        $var_res = $var_nom();
elseif (function_exists($var_f = $var_nom . "_dist"))
        $var_res = $var_f();
else {spip_log("fonction $var_nom indisponible dans $var_f");exit;}

if (!$var_res) exit;


include_ecrire("inc_minipres.php"); // choisit la langue
include_ecrire('inc_admin.php3');
include_ecrire('inc_cookie.php');


//
// Preferences de presentation
//

# teste la capacite ajax : on envoie un cookie -1
# et un script ajax ; si le script reussit le cookie passera a +1
if (!$GLOBALS['_COOKIE']['spip_accepte_ajax']) {
	spip_setcookie('spip_accepte_ajax', -1);
}

if ($spip_lang_ecrire = $GLOBALS['_COOKIE']['spip_lang_ecrire']
AND $spip_lang_ecrire <> $auteur_session['lang']
AND changer_langue($spip_lang_ecrire)) {
	spip_query ("UPDATE spip_auteurs SET lang = '".addslashes($spip_lang_ecrire)
	."' WHERE id_auteur = $connect_id_auteur");
	$auteur_session['lang'] = $spip_lang_ecrire;
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


$GLOBALS['couleurs_spip'] = array(

// Vert
1 => array (
		"couleur_foncee" => "#9DBA00",
		"couleur_claire" => "#C5E41C",
		"couleur_lien" => "#657701",
		"couleur_lien_off" => "#A6C113"
		),

// Violet clair
2 => array (
		"couleur_foncee" => "#eb68b3",
		"couleur_claire" => "#ffa9e6",
		"couleur_lien" => "#8F004D",
		"couleur_lien_off" => "#BE6B97"
		),

// Orange
3 => array (
		"couleur_foncee" => "#fa9a00",
		"couleur_claire" => "#ffc000",
		"couleur_lien" => "#FF5B00",
		"couleur_lien_off" => "#B49280"
		),

// Saumon
4 => array (
		"couleur_foncee" => "#CDA261",
		"couleur_claire" => "#FFDDAA",
		"couleur_lien" => "#AA6A09",
		"couleur_lien_off" => "#B79562"
		),

//  Bleu pastel
5 => array (
		"couleur_foncee" => "#5da7c5",
		"couleur_claire" => "#97d2e1",
		"couleur_lien" => "#116587",
		"couleur_lien_off" => "#81B7CD"
		),

//  Gris
6 => array (
		"couleur_foncee" => "#85909A",
		"couleur_claire" => "#C0CAD4",
		"couleur_lien" => "#3B5063",
		"couleur_lien_off" => "#6D8499"
		),
);

// deux globales (compatibilite ascendante)
$options      = $prefs['options'];
$spip_display = $prefs['display'];
$choix_couleur = $prefs['couleur'];
if (strlen($couleurs_spip[$choix_couleur]['couleur_foncee']) < 7) $choix_couleur = 1;

$couleur_foncee = $couleurs_spip[$choix_couleur]['couleur_foncee'];
$couleur_claire = $couleurs_spip[$choix_couleur]['couleur_claire'];

$attributes_body = "
link='" .  $couleurs_spip[$choix_couleur]['couleur_lien'] . "'
vlink='" . $couleurs_spip[$choix_couleur]['couleur_lien_off'] ."'
alink='" . $couleurs_spip[$choix_couleur]['couleur_lien_off'] ."'
bgcolor='#f8f7f3' text='#000000' 
topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' frameborder='0'" .
  ($spip_lang_rtl ? " dir='rtl'" : "");

//
// Gestion de version
//

if (!isset($reinstall)) if (demande_maj_version()) exit;

//
// Gestion de la configuration globale du site
//

if (!$adresse_site) {
	$nom_site_spip = lire_meta("nom_site");
	$adresse_site = lire_meta("adresse_site");
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



//
// Recuperation du cookie
//

$cookie_admin = $_COOKIE['spip_admin'];

?>
