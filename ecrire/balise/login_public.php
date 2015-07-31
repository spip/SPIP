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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('base/abstract_sql');
spip_connect();

// http://doc.spip.org/@balise_LOGIN_PUBLIC
function balise_LOGIN_PUBLIC ($p, $nom='LOGIN_PUBLIC') {
	return calculer_balise_dynamique($p, $nom, array('url'));
}

# retourner:
# 1. l'url collectee ci-dessus (args0) ou donnee en filtre (filtre0)
# 2. l'eventuel parametre de la balise (args1) fournie par
#    calculer_balise_dynamique, en l'occurence le #LOGIN courant si l'on
#    programme une <boucle(AUTEURS)>[(#LOGIN_PUBLIC{#LOGIN})]

// http://doc.spip.org/@balise_LOGIN_PUBLIC_stat
function balise_LOGIN_PUBLIC_stat ($args, $filtres) {
	return array($filtres[0] ? $filtres[0] : $args[0], $args[1], $args[2]);
}

// http://doc.spip.org/@balise_LOGIN_PUBLIC_dyn
function balise_LOGIN_PUBLIC_dyn($url, $login) {

	if (!$url 		# pas d'url passee en filtre ou dans le contexte
	AND !$url = _request('url') # ni d'url passee par l'utilisateur
	)
		$url = parametre_url(self(), '', '', '&');
	return login_explicite($login, $url);
}

// http://doc.spip.org/@login_explicite
function login_explicite($login, $cible) {
	global $auteur_session;

	$action = parametre_url(self(), '', '', '&');
	if ($cible) {
		$cible = parametre_url($cible, 'var_erreur', '', '&');
		$cible = parametre_url($cible, 'var_login', '', '&');
	} else {
		if (preg_match(",[?&]url=([^&]*),", $action, $m))
			$cible = rawurldecode($m[1]);
		else $cible = generer_url_ecrire();
	}

	verifier_visiteur();

	// Si on est connecte, envoyer vers la destination
	// si on en a le droit, et sauf si on y est deja
	if (!strncmp($cible, _DIR_RESTREINT_ABS, strlen(_DIR_RESTREINT_ABS))) {
		include_spip('inc/autoriser');
		$loge = autoriser('ecrire');
	} else {
		$loge = ($auteur_session != '');
	}

	if ($loge) {
		// on est a destination ?
		if ($cible == $action)
			return '';

		// sinon on y va
		if (!headers_sent() AND !$_GET['var_mode']) {
			include_spip('inc/headers');
			redirige_par_entete($cible);
		} else {
			return "<a href='$cible'>" .
			  _T('login_par_ici') .
			  "</a>";
		}
	}

	return login_pour_tous($login ? $login : _request('var_login'), $cible, $action);
}

// http://doc.spip.org/@login_pour_tous
function login_pour_tous($login, $cible, $action) {
	global $ignore_auth_http;

	// en cas d'echec de cookie, inc_auth a renvoye vers le script de
	// pose de cookie ; s'il n'est pas la, c'est echec cookie
	// s'il est la, c'est probablement un bookmark sur bonjour=oui,
	// et pas un echec cookie.
	if (_request('var_erreur') == 'cookie')
		$echec_cookie = ($_COOKIE['spip_session'] != 'test_echec_cookie');
	else $echec_cookie = '';

	// hack grossier pour changer le message en cas d'echec d'un statut interdit sur ecrire/
	$echec_visiteur = (_request('var_erreur') == 'statut') ?' ':'';


	$pose_cookie = generer_url_action('cookie');
	$auth_http = '';	
	if ($echec_cookie AND !$ignore_auth_http) {
		if (($GLOBALS['flag_sapi_name']
		     AND preg_match(",apache,i", @php_sapi_name()))
		OR preg_match(",^Apache.* PHP,", $_SERVER['SERVER_SOFTWARE']))
			$auth_http = $pose_cookie;
	}
	// Attention dans le cas 'intranet' la proposition de se loger
	// par auth_http peut conduire a l'echec.
	if (isset($_SERVER['PHP_AUTH_USER']) AND isset($_SERVER['PHP_AUTH_PW']))
		$auth_http = '';

	// Le login est memorise dans le cookie d'admin eventuel
	if (!$login) {
		if (preg_match(",^@(.*)$,", $_COOKIE['spip_admin'], $regs))
			$login = $regs[1];
	} else if ($login == '-1')
		$login = '';

	$erreur = '';
	if ($login) {
		$row =  sql_fetsel('*', 'spip_auteurs', "login=" . sql_quote($login));
		// Retrouver ceux qui signent de leur nom ou email
		if (!$row AND !spip_connect_ldap()) {
			$row = sql_fetsel('*', 'spip_auteurs', "(nom = " . sql_quote($login) . " OR email = " . sql_quote($login) . ") AND login<>'' AND statut<>'5poubelle'");
			if ($row) {
				$login_alt = $login; # afficher ce qu'on a tape
				$login = $row['login'];
			}
		}

		if ((!$row AND !spip_connect_ldap()) OR
			($row['statut'] == '5poubelle') OR 
			(($row['source'] == 'spip') AND $row['pass'] == '')) {
			$erreur =  _T('login_identifiant_inconnu',
				array('login' => htmlspecialchars($login)));
			$row = array();
			$login = '';
			include_spip('inc/cookie');
			spip_setcookie("spip_admin", "", time() - 3600);
		} else {
			// on laisse le menu decider de la langue
			unset($row['lang']);
		}
	} else {
		$row = array();
	}

	// afficher "erreur de mot de passe" si &var_erreur=pass
	if (_request('var_erreur') == 'pass')
		$erreur = _T('login_erreur_pass');

	// Ne pas proposer de "rester connecte quelques jours"
	// si la duree de l'alea est inferieure a 12 h (valeur par defaut)
	$rester_connecte = (_RENOUVELLE_ALEA < 12*3600) ? '' : ' ';

	// Appeler le squelette formulaire_login
	return array('formulaires/login', $GLOBALS['delais'],
		array_merge(
				array_map('texte_script', $row),
				array(
					'action2' => ($login ? $pose_cookie: $action),
					'erreur' => $erreur,
					'action' => $action,
					'url' => $cible,
					'auth_http' => $auth_http,
					'echec_cookie' => ($echec_cookie ? ' ' : ''),
					'echec_visiteur' => $echec_visiteur,
					'login' => $login,
					'login_alt' => (isset($login_alt) ? $login_alt : $login),
					'self' => self('&'),
					'rester_connecte' => $rester_connecte
					)
				)
			);

}

// Bouton duree de connexion

// http://doc.spip.org/@filtre_rester_connecte
function filtre_rester_connecte($prefs) {
	$prefs = unserialize(stripslashes($prefs));
	return $prefs['cnx'] == 'perma' ? ' ' : '';
}

?>
