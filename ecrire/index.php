<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Fichier d'exécution de l'interface privée
 *
 * @package SPIP\Core\Chargement
 **/

/** Drapeau indiquant que l'on est dans l'espace privé */
define('_ESPACE_PRIVE', true);
if (!defined('_ECRIRE_INC_VERSION')) {
	include 'inc_version.php';
}

include_spip('inc/cookie');

//
// Determiner l'action demandee
//

$exec = (string)_request('exec');
$reinstall = (!is_null(_request('reinstall'))) ? _request('reinstall') : ($exec == 'install' ? 'oui' : null);
//
// Les scripts d'insallation n'authentifient pas, forcement,
// alors il faut blinder les variables d'URL
//
if (autoriser_sans_cookie($exec, false)) {
	if (!isset($reinstall)) {
		$reinstall = 'non';
	}
	$var_auth = true;
} else {
	// Authentification, redefinissable
	$auth = charger_fonction('auth', 'inc');
	$var_auth = $auth();
	if ($var_auth) {
		echo auth_echec($var_auth);
		exit;
	}
}

// initialiser a la langue par defaut
include_spip('inc/lang');
utiliser_langue_visiteur();
// forcer la langue de l'utilisateur pour les squelettes
$forcer_lang = true;


if (_request('action') or _request('var_ajax') or _request('formulaire_action')) {
	if (!autoriser_sans_cookie($exec)) {
		// Charger l'aiguilleur qui va mettre sur la bonne voie les traitements derogatoires
		include_spip('public/aiguiller');
		if (
			// cas des appels actions ?action=xxx
			traiter_appels_actions()
			or
			// cas des hits ajax sur les inclusions ajax
			traiter_appels_inclusions_ajax()
			or
			// cas des formulaires charger/verifier/traiter
			traiter_formulaires_dynamiques()
		) {
			exit;
		} // le hit est fini !
	}
}
// securiser les redirect du back-office
if (_request('redirect')) {
	if (!function_exists('securiser_redirect_action')) {
		include_spip('public/aiguiller');
	}
	set_request('redirect', securiser_redirect_action(_request('redirect')));
}


//
// Gestion d'une page normale de l'espace prive
//

// Controle de la version, sauf si on est deja en train de s'en occuper
if (
	!$reinstall == 'oui'
	and !_AJAX
	and isset($GLOBALS['meta']['version_installee'])
	and ($GLOBALS['spip_version_base'] != (str_replace(',', '.', $GLOBALS['meta']['version_installee'])))
) {
	$exec = 'demande_mise_a_jour';
}

// Quand une action d'administration est en cours (meta "admin"),
// refuser les connexions non-admin ou Ajax pour laisser la base intacte.
// Si c'est une admin, detourner le script demande vers cette action:
// si l'action est vraiment en cours, inc_admin refusera cette 2e demande,
// sinon c'est qu'elle a ete interrompue et il faut la reprendre

elseif (isset($GLOBALS['meta']['admin'])) {
	if (preg_match('/^(.*)_(\d+)_/', $GLOBALS['meta']['admin'], $l)) {
		[, $var_f, $n] = $l;
	}
	if (
		_AJAX
		or !(
			isset($_COOKIE['spip_admin'])
			or (isset($GLOBALS['visiteur_session']) and $GLOBALS['visiteur_session']['statut'] == '0minirezo')
		)
	) {
		spip_log('Quand la meta admin vaut ' .
			$GLOBALS['meta']['admin'] .
			' seul un admin peut se connecter et sans AJAX.' .
			' En cas de probleme, detruire cette meta.');
		die(_T('info_travaux_texte'));
	}
	if ($n) {
		[, $var_f, $n] = $l;
		if (tester_url_ecrire("base_$var_f")) {
			$var_f = "base_$var_f";
		}
		if ($var_f != $exec) {
			spip_log("Le script $var_f lance par auteur$n se substitue a l'exec $exec");
			$exec = $var_f;
			set_request('exec', $exec);
		}
	}
}
// si nom pas plausible, prendre le script par defaut
// attention aux deux cas 404/403 qui commencent par un 4 !
elseif (!preg_match(',^[a-z4_][0-9a-z_-]*$,i', $exec)) {
	$exec = 'accueil';
	set_request('exec', $exec);
}

//  si la langue est specifiee par cookie et ne correspond pas
// (elle a ete changee dans une autre session, et on retombe sur un vieux cookie)
// on appelle directement la fonction, car un appel d'action peut conduire a une boucle infinie
// si le cookie n'est pas pose correctement dans l'action
if (
	!$var_auth and isset($_COOKIE['spip_lang_ecrire'])
	and $_COOKIE['spip_lang_ecrire'] <> $GLOBALS['visiteur_session']['lang']
) {
	include_spip('action/converser');
	action_converser_post($GLOBALS['visiteur_session']['lang'], true);
}

if ($var_f = tester_url_ecrire($exec)) {
	$var_f = charger_fonction($var_f);
	$var_f(); // at last
} else {
	// Rien de connu: rerouter vers exec=404 au lieu d'echouer
	// ce qui permet de laisser la main a un plugin
	$var_f = charger_fonction('404');
	$var_f($exec);
}
