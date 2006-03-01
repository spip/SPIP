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

if (!defined('_ECRIRE_INC_VERSION')) include ("inc_version.php");
include_spip('inc/cookie');

//
// Determiner l'action demandee
//

$exec = _request('exec');
if (!preg_match(',^[a-z][0-9a-z_]*$,i', $exec)) $exec = "accueil";

$var_auth ="";
if (autoriser_sans_cookie($exec)) {
	if (!isset($reinstall)) $reinstall = 'non';
} else {
	include_spip('inc/session');
	$var_auth = include_fonction('auth', 'inc');
	if (!$var_auth()) exit;
}

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
if (!isset($couleurs_spip[$choix_couleur])) $choix_couleur = 1;

$couleur_foncee = $couleurs_spip[$choix_couleur]['couleur_foncee'];
$couleur_claire = $couleurs_spip[$choix_couleur]['couleur_claire'];

define (_ATTRIBUTES_BODY,  "
link='" .  $couleurs_spip[$choix_couleur]['couleur_lien'] . "'
vlink='" . $couleurs_spip[$choix_couleur]['couleur_lien_off'] ."'
alink='" . $couleurs_spip[$choix_couleur]['couleur_lien_off'] ."'
bgcolor='#f8f7f3' text='#000000' 
topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' frameborder='0'" .
	($spip_lang_rtl ? " dir='rtl'" : ""));

// charger l'affichage minimal et initialiser a la langue par defaut
include_spip('inc/minipres');

//  si la langue est specifiee par cookie alors ...
if ($spip_lang_ecrire = $GLOBALS['_COOKIE']['spip_lang_ecrire']) {

	// si pas authentifie, changer juste pour cette execution
	if (!$var_auth)
		changer_langue($spip_lang_ecrire);
	// si authentifie, changer definitivement si ce n'est fait
	else {	if (($spip_lang_ecrire <> $auteur_session['lang'])
		AND changer_langue($spip_lang_ecrire)) {
			spip_query ("UPDATE spip_auteurs SET lang = '".
				addslashes($spip_lang_ecrire) .
				"' WHERE id_auteur = $connect_id_auteur");
			$auteur_session['lang'] = $spip_lang_ecrire;
			ajouter_session($auteur_session, $spip_session);
	       }
	}
 }

//
// Controle de la version, sauf si on est deja en train de s'en occuper
//

if (!isset($reinstall)) {
	if ($spip_version <> ((double) str_replace(',','.',$GLOBALS['meta']['version_installee']))) {
		include_spip('inc/admin');
		demande_maj_version();
	}
}

//
// Controle d'interruption d'une longue restauration
//
if ($GLOBALS['_COOKIE']['spip_admin'] AND $GLOBALS['meta']["debut_restauration"])
	$exec = 'import_all';

$var_f = include_fonction($exec);
$var_f();

?>
