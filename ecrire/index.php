<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
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
$reinstall = _request('reinstall')?_request('reinstall'):($exec=='install'?'oui':NULL);
//
// Authentification, redefinissable
//
if (autoriser_sans_cookie($exec)) {
	if (!isset($reinstall)) $reinstall = 'non';
	$var_auth = true;
} else {
	$auth = charger_fonction('auth', 'inc');
	$var_auth = $auth();
	if ($var_auth) { 
		include_spip('inc/minipres');
		include_spip('inc/headers');
		// pas authentifie. Pourquoi ?
		if (is_string($var_auth)) {
			// redirection vers une page d'authentification
			// on ne revient pas de cette fonction 
			// sauf si pb de header 
			$var_auth = redirige_formulaire($var_auth);
		} elseif (is_int($var_auth)) {
			// erreur SQL a afficher
			$var_auth = minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'). "<p><tt>".sql_errno()." ".sql_error()."</tt></p>");
		} elseif (@$var_auth['statut']) {
			// un simple visiteur n'a pas acces a l'espace prive
			$var_auth = minipres(_T('avis_erreur_connexion'),_T('avis_erreur_visiteur'));
		} else {
			// auteur en fin de droits ...
			$h = $var_auth['site'];
			$var_auth = minipres(_T('avis_erreur_connexion'), 
					"<br /><br /><p>"
					. _T('texte_inc_auth_1', array('auth_login' => $var_auth['login']))
					. " <a href='$h'>"
					.  _T('texte_inc_auth_2')
					. "</a>"
					. _T('texte_inc_auth_3'));
		}
		echo $var_auth;
		exit;
	}
 }

// initialiser a la langue par defaut
include_spip('inc/lang');
utiliser_langue_visiteur();

if (_request('action') OR _request('var_ajax') OR _request('formulaire_action')){
	// Charger l'aiguilleur qui va mettre sur la bonne voie les traitements derogatoires
	include_spip('public/aiguiller');
	if (
		// cas des appels actions ?action=xxx
		traiter_appels_actions()
	 OR
		// cas des hits ajax sur les inclusions ajax
		traiter_appels_inclusions_ajax()
	 OR 
	 	// cas des formulaires charger/verifier/traiter
	  traiter_formulaires_dynamiques())
	  exit; // le hit est fini !
}

//
// Gestion d'une page normale de l'espace prive
//

// Controle de la version, sauf si on est deja en train de s'en occuper
if (!$reinstall=='oui'
AND !_AJAX
AND isset($GLOBALS['meta']['version_installee'])
AND ($GLOBALS['spip_version_base'] != (str_replace(',','.',$GLOBALS['meta']['version_installee']))))
	$exec = 'demande_mise_a_jour';

// Quand une action d'administration est en cours (meta "admin"),
// refuser les connexions non-admin ou Ajax pour laisser la base intacte.
// Si c'est une admin, detourner le script demande vers cette action:
// si l'action est vraiment en cours, inc_admin refusera cette 2e demande,
// sinon c'est qu'elle a ete interrompue et il faut la reprendre

elseif (isset($GLOBALS['meta']["admin"])) {
	if (_AJAX OR !isset($_COOKIE['spip_admin']))
		die(_T('info_travaux_texte'));
	if (preg_match('/^(.*)_(\d+)_/', $GLOBALS['meta']["admin"], $l)) {
		list(,$var_f,$n) = $l;
		if ($var_f != $exec) {
			spip_log("Le script $var_f lance par $n se substitue a $exec");
			$exec = $var_f;
		}
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

// compatibilite ascendante
$GLOBALS['spip_display'] = isset($GLOBALS['visiteur_session']['prefs']['display'])
	? $GLOBALS['visiteur_session']['prefs']['display']
	: 0;
$GLOBALS['spip_ecran'] = isset($_COOKIE['spip_ecran']) ? $_COOKIE['spip_ecran'] : "etroit";

//  si la langue est specifiee par cookie et ne correspond pas
// (elle a ete changee dans une autre session, et on retombe sur un vieux cookie)
// on appelle directement la fonction, car un appel d'action peut conduire a une boucle infinie
// si le cookie n'est pas pose correctement dans l'action
if (!$var_auth AND isset($_COOKIE['spip_lang_ecrire'])
  AND $_COOKIE['spip_lang_ecrire'] <> $GLOBALS['visiteur_session']['lang']) {
  	include_spip('action/converser');
  	action_converser_post($GLOBALS['visiteur_session']['lang'],true);
}


// Passer la main aux outils XML a la demande (meme les redac s'ils veulent).
if ($var_f = _request('transformer_xml')) {
	set_request('var_url', $exec);
	$exec = $var_f;
}

// Trouver la fonction eventuellement surchargee
$var_f = charger_fonction($exec);

// Z'y va
$var_f();

if ($GLOBALS['var_mode'] == 'debug') {
	include_spip('public/debug');
	$var_mode_affiche = _request('var_mode_affiche');
	$var_mode_objet = _request('var_mode_objet');
	debug_dumpfile("",$var_mode_objet,$var_mode_affiche);
}
if (count($tableau_des_erreurs) AND $affiche_boutons_admin)
	echo affiche_erreurs_page($tableau_des_erreurs);

?>