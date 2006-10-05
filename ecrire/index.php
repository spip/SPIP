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


// Placer definition des couleurs avant inc_version,
// sinon impossible de les redefinir dans mes_options
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


if (!defined('_ECRIRE_INC_VERSION')) {
	include 'inc_version.php';
}

include_spip('inc/cookie');

//
// Determiner l'action demandee
//

$exec = _request('exec');
if (!preg_match(',^[a-z][0-9a-z_]*$,i', $exec)) $exec = "accueil";

//
// Authentification, redefinissable
//

if (autoriser_sans_cookie($exec)) {
	if (!isset($reinstall)) $reinstall = 'non';
	$prefs = array('couleur' =>1, 'display'=>0, 'options'=>'avancees');
	$var_auth = true;
} else {
	$var_auth = charger_fonction('auth', 'inc');
	$var_auth = $var_auth();
	if ($var_auth) redirige_par_entete($var_auth);
 }

//
// Preferences de presentation
//

$prefs_mod = false;

if (isset($set_couleur)) {
	$prefs['couleur'] = floor($set_couleur);
	$prefs_mod = true;
}
if (isset($set_disp)) {
	$prefs['display'] = floor($set_disp);
	$prefs_mod = true;
}
if (isset($set_options) AND ($set_options == 'avancees' OR $set_options == 'basiques')) {
	$prefs['options'] = $set_options;
	$prefs_mod = true;
}
if ($prefs_mod AND !$var_auth)
	spip_query("UPDATE spip_auteurs SET prefs = " . spip_abstract_quote(serialize($prefs)) . " WHERE id_auteur = $connect_id_auteur");

if (isset($set_ecran)) {
	// Poser un cookie, car ce reglage depend plus du navigateur que de l'utilisateur
	spip_setcookie('spip_ecran', $set_ecran, time() + 365 * 24 * 3600);
	$spip_ecran = $set_ecran;
}
if (!isset($spip_ecran)) $spip_ecran = "etroit";



// deux globales (compatibilite ascendante)
$options      = $prefs['options'];
$spip_display = $prefs['display'];
$choix_couleur = $prefs['couleur'];
if (!isset($couleurs_spip[$choix_couleur])) $choix_couleur = 1;

$couleur_foncee = $couleurs_spip[$choix_couleur]['couleur_foncee'];
$couleur_claire = $couleurs_spip[$choix_couleur]['couleur_claire'];

// charger l'affichage minimal et initialiser a la langue par defaut
include_spip('inc/minipres');

//  si la langue est specifiee par cookie alors ...
if (isset($GLOBALS['_COOKIE']['spip_lang_ecrire'])) {

	$spip_lang_ecrire = $GLOBALS['_COOKIE']['spip_lang_ecrire'];
	// si pas authentifie, changer juste pour cette execution
	if ($var_auth)
		changer_langue($GLOBALS['_COOKIE']['spip_lang_ecrire']);
	// si authentifie, changer definitivement si ce n'est fait
	else {	if (($spip_lang_ecrire <> $auteur_session['lang'])
		AND changer_langue($spip_lang_ecrire)) {
			spip_query("UPDATE spip_auteurs SET lang = " . spip_abstract_quote($spip_lang_ecrire) . " WHERE id_auteur = " . intval($auteur_session['id_auteur']));
			$auteur_session['lang'] = $var_lang_ecrire;
			$var_f = charger_fonction('session', 'inc');
			$var_f($auteur_session);
		}
	}
}

utiliser_langue_visiteur(); 

define ('_ATTRIBUTES_BODY',  "
link='" .  $couleurs_spip[$choix_couleur]['couleur_lien'] . "'
vlink='" . $couleurs_spip[$choix_couleur]['couleur_lien_off'] ."'
alink='" . $couleurs_spip[$choix_couleur]['couleur_lien_off'] ."'
bgcolor='#f8f7f3' text='#000000' 
topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' frameborder='0'" .
	($spip_lang_rtl ? " dir='rtl'" : ""));

define('_TRANCHES', 10);

//
// Fragment (ajax) ?
//
// var_ajaxcharset repere les requetes de fragments, et indique en plus
// le charset [utf-8] utilise par le client, utile a _request.
if (isset($var_ajaxcharset)) {
	header("Content-Type: text/html; charset=".$GLOBALS['meta']["charset"]);
	$var_f = charger_fonction($exec, 'exec');
	$fragment = $var_f();
	echo "<","?xml version='1.0' encoding='",
		$GLOBALS['meta']["charset"],
		"'?",">\n",
		$fragment;
	exit;
}


//
// Gestion d'une page normale de l'espace prive
//

// Controle de la version, sauf si on est deja en train de s'en occuper
if (!isset($reinstall)) {
	if ($spip_version <> ((double) str_replace(',','.',$GLOBALS['meta']['version_installee']))) {
		include_spip('inc/admin');
		demande_maj_version();
	}
}

// Controle d'interruption d'une longue restauration
if ($_COOKIE['spip_admin']
AND isset($GLOBALS['meta']["debut_restauration"]))
	$exec = 'import_all';

// Verification des plugins
// (ne pas interrompre une restauration ou un upgrade)
if ($exec!='upgrade'
AND $auteur_session['statut']=='0minirezo'
AND lire_fichier(_DIR_TMP.'verifier_plugins.txt',$l)
AND $l = @unserialize($l)) {
	foreach ($l as $fichier) {
		if (!@is_readable($fichier)) {
			include_spip('inc/plugin');
			verifie_include_plugins();
			break; // sortir de la boucle, on a fait un verif
		}
	}
}
// Trouver la fonction eventuellement surchagee et l'appeler.
// Elle envoie parfois des en-tetes http,
// et en mode Ajax retourne un resultat.
$var_f = charger_fonction($exec);
$var_f();

?>
