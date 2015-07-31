<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	include 'inc_version.php';
}

include_spip('inc/cookie');

//
// Determiner l'action demandee
//

$exec = _request('exec');

//
// Authentification, redefinissable
//

if (autoriser_sans_cookie($exec)) {
	if (!isset($reinstall)) $reinstall = 'non';
	$var_auth = true;
} else {
	$auth = charger_fonction('auth', 'inc');
	$var_auth = $auth();
	if ($var_auth!=="") {
		if ($var_auth===-1) exit();
		include_spip('inc/headers');
		redirige_par_entete(generer_url_public('login',
			"url=" . 
			rawurlencode(str_replace('/./', '/',
				(_DIR_RESTREINT ? "" : _DIR_RESTREINT_ABS)
				. str_replace('&amp;', '&', self())))
		// $var_auth indique si c'est le statut qui est insuffisant
			. ((!isset($_GET['bonjour'])) ? ''
			    : (($var_auth == '6forum') ?
				 '&var_echec_visiteur=true'
			       : '&var_echec_cookie=true')),
						       true));
	}
 }

//
// Preferences de presentation
//

$prefs_mod = false;

if (isset($_GET['set_couleur'])) {
	$GLOBALS['auteur_session']['prefs']['couleur'] = floor($_GET['set_couleur']);
	$prefs_mod = true;
}
if (isset($_GET['set_disp'])) {
	$GLOBALS['auteur_session']['prefs']['display'] = floor($_GET['set_disp']);
	$prefs_mod = true;
}
if ($prefs_mod AND !$var_auth) {
	spip_query("UPDATE spip_auteurs SET prefs = " . _q(serialize($GLOBALS['auteur_session']['prefs'])) . " WHERE id_auteur = " .intval($GLOBALS['auteur_session']['id_auteur']));
 }

// compatibilite ascendante
$GLOBALS['spip_display'] = $GLOBALS['auteur_session']['prefs']['display'];

// Options "avancees" pour tout le monde (en attendant de les supprimer dans le code)
$GLOBALS['options'] = 'avancees';

if (isset($_GET['set_ecran'])) {
	// Poser un cookie,
	// car ce reglage depend plus du navigateur que de l'utilisateur
	$GLOBALS['spip_ecran'] = $_GET['set_ecran'];
	spip_setcookie('spip_ecran', $GLOBALS['spip_ecran'], time() + 365 * 24 * 3600);
 } else $GLOBALS['spip_ecran'] = isset($_COOKIE['spip_ecran']) ? $_COOKIE['spip_ecran'] : "etroit";


// charger l'affichage minimal et initialiser a la langue par defaut
include_spip('inc/minipres');

//  si la langue est specifiee par cookie alors ...
if (isset($_COOKIE['spip_lang_ecrire'])) {

	$spip_lang_ecrire = $_COOKIE['spip_lang_ecrire'];
	// si pas authentifie, changer juste pour cette execution
	if ($var_auth)
		changer_langue($_COOKIE['spip_lang_ecrire']);
	// si authentifie, changer definitivement si ce n'est fait
	else {	if (($spip_lang_ecrire <> $GLOBALS['auteur_session']['lang'])
		AND changer_langue($spip_lang_ecrire)) {
			spip_query("UPDATE spip_auteurs SET lang = " . _q($spip_lang_ecrire) . " WHERE id_auteur = " . intval($GLOBALS['auteur_session']['id_auteur']));
			$GLOBALS['auteur_session']['lang'] = $var_lang_ecrire;
			$session = charger_fonction('session', 'inc');
			$session($GLOBALS['auteur_session']);
		}
	}
}

utiliser_langue_visiteur(); 

define('_TRANCHES', 10);

//
// Gestion d'une page normale de l'espace prive
//

// Controle de la version, sauf si on est deja en train de s'en occuper
if (!isset($reinstall)
AND (!isset($var_ajaxcharset))
AND ($GLOBALS['spip_version'] != (str_replace(',','.',$GLOBALS['meta']['version_installee']))))
	$exec = 'demande_mise_a_jour';

// Si interruption d'une longue restauration
// detourner le script demande pour qu'il reprenne le boulot
// mais virer les Ajax pour eviter plusieurs restaurations en parallele
elseif ($_COOKIE['spip_admin']
AND isset($GLOBALS['meta']["debut_restauration"])) {
	if (isset($var_ajaxcharset)) exit;
	$exec = 'import_all';
}

// Verification des plugins
// (ne pas interrompre une restauration ou un upgrade)
elseif ($exec!='upgrade'
AND $GLOBALS['auteur_session']['statut']=='0minirezo'
AND !_DIR_RESTREINT
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

// si nom pas plausible, prendre le script par defaut
if (!preg_match(',^[a-z_][0-9a-z_]*$,i', $exec)) $exec = "accueil";

// Passer la main aux outils XML a la demande.
if (isset($GLOBALS['transformer_xml'])
AND $GLOBALS['auteur_session']['statut']=='0minirezo') {
	set_request('var_url', $exec);
	$exec = $GLOBALS['transformer_xml'];
 }

// Trouver la fonction eventuellement surchagee
if (!strncmp($exec,"accueil",7)==0
	OR !isset($GLOBALS['meta']['nouvelle_install']) 
	OR !($e = $GLOBALS['meta']['nouvelle_install'])
	OR !($var_f = charger_fonction("pas_$e",'premiers_pas',true))
	)
	$var_f = charger_fonction($exec);

// Feu !

$var_f();

?>