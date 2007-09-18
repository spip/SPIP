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

define('_ESPACE_PRIVE', true);
if (!defined('_ECRIRE_INC_VERSION')) include 'inc_version.php';

// Verification anti magic_quotes_sybase, pour qui addslashes("'") = "''"
// On prefere la faire ici plutot que dans inc_version, c'est moins souvent et
// si le reglage est modifie sur un site en prod, ca fait moins mal
if (addslashes("'") !== "\\'") die('SPIP incompatible magic_quotes_sybase');

include_spip('inc/cookie');

//
// Determiner l'action demandee
//

$exec = _request('exec');
$reinstall = _request('reinstall');

//
// Authentification, redefinissable
//
if (autoriser_sans_cookie($exec)) {
	if (!isset($reinstall)) $reinstall = 'non';
	$var_auth = true;
} else {
	$auth = charger_fonction('auth', 'inc');
	$var_auth = $auth();

	if (!autoriser('ecrire')) {
		// Erreur SQL ?
		if ($var_auth===-1) exit(); // un message d'erreur a deja ete envoye

		// Sinon rediriger vers la page de login
		include_spip('inc/headers');
		$redirect = generer_url_public('login',
			"url=" . rawurlencode(str_replace('/./', '/',
				(_DIR_RESTREINT ? "" : _DIR_RESTREINT_ABS)
				. str_replace('&amp;', '&', self()))), '&');

		// un echec au "bonjour" (login initial) quand le statut est
		// inconnu signale sans doute un probleme de cookies
		if (isset($_GET['bonjour']))
			$redirect = parametre_url($redirect,
				'var_erreur',
				(!isset($GLOBALS['auteur_session']['statut'])
					? 'cookie'
					: 'statut'
				),
				'&'
			);
		redirige_par_entete($redirect);
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
$GLOBALS['spip_display'] = isset($GLOBALS['auteur_session']['prefs']['display'])
	? $GLOBALS['auteur_session']['prefs']['display']
	: 0;

if (isset($_GET['set_ecran'])) {
	// Poser un cookie,
	// car ce reglage depend plus du navigateur que de l'utilisateur
	$GLOBALS['spip_ecran'] = $_GET['set_ecran'];
	spip_setcookie('spip_ecran', $GLOBALS['spip_ecran'], time() + 365 * 24 * 3600);
 } else $GLOBALS['spip_ecran'] = isset($_COOKIE['spip_ecran']) ? $_COOKIE['spip_ecran'] : "etroit";

// initialiser a la langue par defaut
include_spip('inc/lang');

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
			$GLOBALS['auteur_session']['lang'] = $spip_lang_ecrire;
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

// Quand une action d'administration est en cours (meta "admin"),
// refuser les connexions non-admin ou Ajax pour laisser la base intacte.
// Si c'est une admin, detourner le script demande vers cette action:
// si l'action est vraiment en cours, inc_admin refusera cette 2e demande,
// sinon c'est qu'elle a ete interrompue et il faut la reprendre

elseif (isset($GLOBALS['meta']["admin"])) {
	if (isset($var_ajaxcharset) OR !isset($_COOKIE['spip_admin']))
		die(_T('info_travaux_texte'));
	if (preg_match('/^(.*)_(\d+)_/', $GLOBALS['meta']["admin"], $l)) {
	  list(,$exec,$n) = $l;
	  spip_log("Le script $e lance par $n se substitue a celui prevu");
	}
}
// si nom pas plausible, prendre le script par defaut
elseif (!preg_match(',^[a-z_][0-9a-z_]*$,i', $exec)) $exec = "accueil";

// Verification des plugins
// (ne pas interrompre une restauration ou un upgrade)
elseif ($exec!='upgrade'
AND !$var_auth
AND !_DIR_RESTREINT
AND autoriser('configurer')
AND lire_fichier(_DIR_TMP.'verifier_plugins.txt',$l)
AND $l = @unserialize($l)) {
	foreach ($l as $fichier) {
		if (!@is_readable($fichier)) {
			spip_log("Verification plugin: echec sur $fichier !");
			include_spip('inc/plugin');
			verifie_include_plugins();
			break; // sortir de la boucle, on a fait un verif
		}
	}
}

// Passer la main aux outils XML a la demande (meme les redac s'ils veulent).
if ($var_f = _request('transformer_xml')) {
	set_request('var_url', $exec);
	$exec = $var_f;
 }

// Trouver la fonction eventuellement surchagee
$var_f = charger_fonction($exec);

// Z'y va
$var_f();
?>
