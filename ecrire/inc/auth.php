<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion des authentifications
 *
 * @package SPIP\Core\Authentification
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/abstract_sql');

/**
 * Teste l'authentification d'un visiteur
 *
 * Cette fonction ne fait pas l'authentification en soit ;
 * elle vérifie simplement qu'une personne est connectée ou non.
 *
 * @return array|int|string
 *  - URL de connexion si on ne sait rien (pas de cookie, pas Auth_user);
 *  - un tableau si visiteur sans droit (tableau = sa ligne SQL)
 *  - code numerique d'erreur SQL
 *  - une chaîne vide si autorisation à pénétrer dans l'espace privé.
 */
function inc_auth_dist() {
	$row = auth_mode();

	if ($row) {
		return auth_init_droits($row);
	}

	if (!$GLOBALS['connect_login']) {
		return auth_a_loger();
	}

	// Cas ou l'auteur a ete identifie mais on n'a pas d'info sur lui
	// C'est soit parce que la base est inutilisable,
	// soit parce que la table des auteurs a changee (restauration etc)
	// Pas la peine d'insister.
	// Renvoyer le nom fautif et une URL de remise a zero

	if (spip_connect()) {
		return [
			'login' => $GLOBALS['connect_login'],
			'site' => generer_url_public('', 'action=logout&amp;logout=prive')
		];
	}

	$n = (int) sql_errno();
	spip_log("Erreur base de donnees $n " . sql_error());

	return $n ?: 1;
}

/**
 * Vérifier qu'un mot de passe saisi pour confirmer une action est bien celui de l'auteur connecté
 * @return bool
 */
function auth_controler_password_auteur_connecte(#[\SensitiveParameter] string $password): bool {

	if (
		empty($GLOBALS['visiteur_session']['id_auteur'])
		|| empty($GLOBALS['visiteur_session']['login'])
	) {
		return false;
	}

	$auth = auth_identifier_login($GLOBALS['visiteur_session']['login'], $password, '', true);
	return is_array($auth) && $auth['id_auteur'] == $GLOBALS['visiteur_session']['id_auteur'];
}

/**
 * fonction appliquee par ecrire/index sur le resultat de la precedente
 * en cas de refus de connexion.
 * Retourne un message a afficher ou redirige illico.
 *
 * @param  $raison
 * @return array|string
 */
function auth_echec($raison) {
	include_spip('inc/minipres');
	include_spip('inc/headers');
	// pas authentifie. Pourquoi ?
	if (is_string($raison)) {
		// redirection vers une page d'authentification
		// on ne revient pas de cette fonction
		// sauf si pb de header
		$raison = redirige_formulaire($raison);
	} elseif (is_int($raison)) {
		// erreur SQL a afficher
		$raison = minipres(
			_T('info_travaux_titre'),
			_T('titre_probleme_technique') . '<p><tt>' . sql_errno() . ' ' . sql_error() . '</tt></p>'
		);
	} elseif (@$raison['statut']) {
		// un simple visiteur n'a pas acces a l'espace prive
		spip_log('connexion refusee a ' . @$raison['id_auteur']);
		$est_connecte = (!empty($GLOBALS['visiteur_session']['login']) && !empty($GLOBALS['visiteur_session']['statut'])); // idem test balise #URL_LOGOUT
		$raison = minipres(
			_T('avis_erreur_connexion'),
			_T('avis_erreur_visiteur')
				// Lien vers le site public
				. '<br /><a href="' . url_de_base() . '">' . _T('login_retour_public') . '</a>'
				// Si la personne est connectée, lien de déconnexion ramenant vers la page de login
				. ($est_connecte ? ' | <a href="' . generer_url_public('', 'action=logout&amp;logout=prive') . '">' . _T('icone_deconnecter') . '</a>' : '')
		);
	} else {
		// auteur en fin de droits ...
		$h = $raison['site'];
		$raison = minipres(
			_T('avis_erreur_connexion'),
			'<br /><br /><p>'
			. _T('texte_inc_auth_1', ['auth_login' => $raison['login']])
			. " <a href='$h'>"
			. _T('texte_inc_auth_2')
			. '</a>'
			. _T('texte_inc_auth_3')
		);
	}

	return $raison;
}

/**
 * Retourne la description d'un authentifie par cookie ou http_auth
 * Et affecte la globale $connect_login
 *
 * @return array|bool|string
 */
function auth_mode() {
	//
	// Initialiser variables (eviter hacks par URL)
	//
	$GLOBALS['connect_login'] = '';
	$id_auteur = null;
	$GLOBALS['auth_can_disconnect'] = false;

	//
	// Recuperer les donnees d'identification
	//
	include_spip('inc/session');
	// Session valide en cours ?
	if (isset($_COOKIE['spip_session'])) {
		$session = charger_fonction('session', 'inc');
		if (
			($id_auteur = $session()) || $id_auteur === 0 // reprise sur restauration
		) {
			$GLOBALS['auth_can_disconnect'] = true;
			$GLOBALS['connect_login'] = session_get('login');
		} else {
			unset($_COOKIE['spip_session']);
		}
	}

	// Essayer auth http si significatif
	// (ignorer les login d'intranet independants de spip)
	if (!$GLOBALS['ignore_auth_http']) {
		if (
			isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])
				&& ($r = lire_php_auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
			// Si auth http differtente de basic, PHP_AUTH_PW
			// est indisponible mais tentons quand meme pour
			// autocreation via LDAP
			|| isset($_SERVER['REMOTE_USER'])
				&& ($r = lire_php_auth($_SERVER['PHP_AUTH_USER'] = $_SERVER['REMOTE_USER'], ''))
		) {
			if (!$id_auteur) {
				$_SERVER['PHP_AUTH_PW'] = '';
				$GLOBALS['auth_can_disconnect'] = true;
				$GLOBALS['visiteur_session'] = $r;
				$GLOBALS['connect_login'] = session_get('login');
				$id_auteur = $r['id_auteur'];
			} else {
				// cas de la session en plus de PHP_AUTH
				/*				  if ($id_auteur != $r['id_auteur']){
					spip_log("vol de session $id_auteur" . join(', ', $r));
				unset($_COOKIE['spip_session']);
				$id_auteur = '';
				} */
			}
		} else {
			// Authentification .htaccess old style, car .htaccess semble
			// souvent definir *aussi* PHP_AUTH_USER et PHP_AUTH_PW
			if (isset($_SERVER['REMOTE_USER'])) {
				$GLOBALS['connect_login'] = $_SERVER['REMOTE_USER'];
			}
		}
	}

	$where = (is_numeric($id_auteur)
		/*AND $id_auteur>0*/ // reprise lors des restaurations
	) ?
		"id_auteur=$id_auteur" :
		(strlen((string) $GLOBALS['connect_login']) ? 'login=' . sql_quote($GLOBALS['connect_login'], '', 'text') : '');

	if (!$where) {
		return '';
	}

	// Trouver les autres infos dans la table auteurs.
	// le champ 'quand' est utilise par l'agenda

	return sql_fetsel('*, en_ligne AS quand', 'spip_auteurs', "$where AND statut!='5poubelle'");
}

/**
 * Initialisation des globales pour tout l'espace privé si visiteur connu
 *
 * Le tableau global visiteur_session contient toutes les infos pertinentes et
 * à jour (tandis que `$visiteur_session` peut avoir des valeurs un peu datées
 * s'il est pris dans le fichier de session)
 *
 * Les plus utiles sont aussi dans les variables simples ci-dessus
 * si la globale est vide ce n'est pas un tableau, on la force pour empêcher un warning.
 *
 * @param array $row
 * @return array|string|bool
 */
function auth_init_droits($row) {

	include_spip('inc/autoriser');
	if (!autoriser('loger', '', 0, $row)) {
		return false;
	}


	if ($row['statut'] == 'nouveau') {
		include_spip('action/inscrire_auteur');
		$row = confirmer_statut_inscription($row);
	}

	$GLOBALS['connect_id_auteur'] = $row['id_auteur'];
	$GLOBALS['connect_login'] = $row['login'];
	$GLOBALS['connect_statut'] = $row['statut'];

	$GLOBALS['visiteur_session'] = array_merge((array)$GLOBALS['visiteur_session'], $row);

	// au cas ou : ne pas memoriser les champs sensibles
	unset($GLOBALS['visiteur_session']['pass']);
	unset($GLOBALS['visiteur_session']['htpass']);
	unset($GLOBALS['visiteur_session']['alea_actuel']);
	unset($GLOBALS['visiteur_session']['alea_futur']);
	unset($GLOBALS['visiteur_session']['ldap_password']);

	// creer la session au besoin
	if (!isset($_COOKIE['spip_session'])) {
		$session = charger_fonction('session', 'inc');
		$spip_session = $session($row);
	}

	// reinjecter les preferences_auteur apres le reset de spip_session
	// car utilisees au retour par auth_loger()
	$r = @unserialize($row['prefs']);
	$GLOBALS['visiteur_session']['prefs'] = ($r ?: []);
	// si prefs pas definies, les definir par defaut
	if (!isset($GLOBALS['visiteur_session']['prefs']['couleur'])) {
		$GLOBALS['visiteur_session']['prefs']['couleur'] = 2;
		$GLOBALS['visiteur_session']['prefs']['display'] = 2;
		$GLOBALS['visiteur_session']['prefs']['display_navigation'] = 'navigation_avec_icones';
	}

	$GLOBALS['visiteur_session'] = pipeline(
		'preparer_visiteur_session',
		['args' => ['row' => $row],
		'data' => $GLOBALS['visiteur_session']]
	);

	// Etablir les droits selon le codage attendu
	// dans ecrire/index.php ecrire/prive.php

	// Pas autorise a acceder a ecrire ? renvoyer le tableau
	// A noter : le premier appel a autoriser() a le bon gout
	// d'initialiser $GLOBALS['visiteur_session']['restreint'],
	// qui ne figure pas dans le fichier de session

	if (!autoriser('ecrire')) {
		return $row;
	}

	// autoriser('ecrire') ne laisse passer que les Admin et les Redac

	auth_trace($row);

	// Administrateurs
	if (in_array($GLOBALS['connect_statut'], explode(',', _STATUT_AUTEUR_RUBRIQUE))) {
		if (
			isset($GLOBALS['visiteur_session']['restreint'])
			&& is_array($GLOBALS['visiteur_session']['restreint'])
		) {
			$GLOBALS['connect_id_rubrique'] = $GLOBALS['visiteur_session']['restreint'];
		}
		if ($GLOBALS['connect_statut'] == '0minirezo') {
			$GLOBALS['connect_toutes_rubriques'] = !$GLOBALS['connect_id_rubrique'];
		}
	}

	// Pour les redacteurs, inc_version a fait l'initialisation minimale

	return ''; // i.e. pas de pb.
}

/**
 * Retourne l'url de connexion
 *
 * @return string
 */
function auth_a_loger() {
	$redirect = generer_url_public('login', 'url=' . rawurlencode((string) self('&', true)), true);

	// un echec au "bonjour" (login initial) quand le statut est
	// inconnu signale sans doute un probleme de cookies
	if (isset($_GET['bonjour'])) {
		$redirect = parametre_url(
			$redirect,
			'var_erreur',
			(isset($GLOBALS['visiteur_session']['statut'])
				? 'statut'
				: 'cookie'
			),
			'&'
		);
	}

	return $redirect;
}

/**
 * Tracer en base la date de dernière connexion de l'auteur
 *
 * @pipeline_appel trig_auth_trace
 *
 * @param array $row
 * @param null|string $date
 */
function auth_trace($row, $date = null) {
	// Indiquer la connexion. A la minute pres ca suffit.
	if (!is_numeric($connect_quand = $row['quand'] ?? '')) {
		$connect_quand = strtotime((string) $connect_quand);
	}

	$date ??= date('Y-m-d H:i:s');

	if (abs(strtotime($date) - $connect_quand) >= 60) {
		sql_updateq('spip_auteurs', ['en_ligne' => $date], 'id_auteur=' . (int) $row['id_auteur']);
		$row['en_ligne'] = $date;
	}

	pipeline('trig_auth_trace', ['args' => ['row' => $row, 'date' => $date]]);
}


/** ----------------------------------------------------------------------------
 * API Authentification, gestion des identites centralisees
 */

/**
 * Fonction privée d'aiguillage des fonctions d'authentification
 *
 * Charge une fonction d'authentification présente dans un répertoire `auth/`.
 * Ainsi, utiliser `auth_administrer('informer_login', array('spip', ...)` appellera
 * `auth_spip_informer_login()` de `ecrire/auth/spip.php`.
 *
 * @uses charger_fonction()
 *
 * @param string $fonction
 *        Nom de la fonction d'authentification
 * @param array $args
 *        Le premier élément du tableau doit être le nom du système d'authentification
 *        choisi, tel que `spip` (par défaut) ou encore `ldap`.
 * @param mixed $defaut
 * @return mixed
 */
function auth_administrer($fonction, $args, mixed $defaut = false) {
	$auth_methode = array_shift($args);
	$auth_methode = $auth_methode ?: 'spip'; // valeur par defaut au cas ou
	if (
		($auth = charger_fonction($auth_methode, 'auth', true))
		&& function_exists($f = "auth_{$auth_methode}_$fonction")
	) {
		$res = $f(...$args);
	} else {
		$res = $defaut;
	}
	return pipeline(
		'auth_administrer',
		[
			'args' => [
				'fonction' => $fonction,
				'methode' => $auth_methode,
				'args' => $args
			],
			'data' => $res
		]
	);
}

/**
 * Pipeline pour inserer du contenu dans le formulaire de login
 *
 * @param array $flux
 * @return array
 */
function auth_formulaire_login($flux) {
	foreach ($GLOBALS['liste_des_authentifications'] as $methode) {
		$flux = auth_administrer('formulaire_login', [$methode, $flux], $flux);
	}

	return $flux;
}


/**
 * Retrouver le login interne lie a une info login saisie
 * la saisie peut correspondre a un login delegue
 * qui sera alors converti en login interne apres verification
 *
 * @param string $login
 * @param string $serveur
 * @return string/bool
 */
function auth_retrouver_login($login, $serveur = '') {
	if (!spip_connect($serveur)) {
		include_spip('inc/minipres');
		echo minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'));
		exit;
	}

	foreach ($GLOBALS['liste_des_authentifications'] as $methode) {
		if ($auteur = auth_administrer('retrouver_login', [$methode, $login, $serveur])) {
			return $auteur;
		}
	}

	return false;
}

/**
 * informer sur un login
 * Ce dernier transmet le tableau ci-dessous a la fonction JS informer_auteur
 * Il est invoque par la fonction JS actualise_auteur via la globale JS
 * page_auteur=#URL_PAGE{informer_auteur} dans le squelette login
 * N'y aurait-il pas plus simple ?
 *
 * @param string $login
 * @param string $serveur
 * @return array
 */
function auth_informer_login($login, $serveur = '') {
	if (
		!$login
		|| !($login_base = auth_retrouver_login($login, $serveur))
		|| !($row = sql_fetsel('*', 'spip_auteurs', 'login=' . sql_quote($login_base, $serveur, 'text'), '', '', '', '', $serveur))
	) {
		// generer de fausses infos, mais credibles, pour eviter une attaque
		// https://core.spip.net/issues/1758 + https://core.spip.net/issues/3691

		$row = [
			'login' => $login,
			'cnx' => '0',
			'logo' => '',
		];

		return $row;
	}

	$prefs = @unserialize($row['prefs']);
	$infos = [
		'id_auteur' => $row['id_auteur'],
		'login' => $row['login'],
		'cnx' => (isset($prefs['cnx']) && $prefs['cnx'] === 'perma') ? '1' : '0',
		'logo' => recuperer_fond('formulaires/inc-logo_auteur', $row),
	];

	verifier_visiteur();

	return auth_administrer('informer_login', [$row['source'], $infos, $row, $serveur], $infos);
}


/**
 * Essayer les differentes sources d'authenfication dans l'ordre specifie.
 * S'en souvenir dans visiteur_session['auth']
 *
 * @param string $login
 * @param string $password
 * @param string $serveur
 * @return mixed
 */
function auth_identifier_login($login, #[\SensitiveParameter] $password, $serveur = '', bool $phpauth = false) {
	$erreur = '';
	foreach ($GLOBALS['liste_des_authentifications'] as $methode) {
		if ($auth = charger_fonction($methode, 'auth', true)) {
			$auteur = $auth($login, $password, $serveur, $phpauth);
			if (is_array($auteur) && count($auteur)) {
				spip_log("connexion de $login par methode $methode");
				$auteur['auth'] = $methode;
				return $auteur;
			} elseif (is_string($auteur)) {
				$erreur .= "$auteur ";
			}
		}
	}

	return $erreur;
}

/**
 * Fournir une url de retour apres login par un SSO
 * pour finir l'authentification
 *
 * @param string $auth_methode
 * @param string $login
 * @param string $redirect
 * @param string $serveur
 * @return string
 */
function auth_url_retour_login($auth_methode, $login, $redirect = '', $serveur = '') {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	return $securiser_action('auth', "$auth_methode/$login", $redirect, true);
}

/**
 * Terminer l'action d'authentification d'un auteur
 *
 * @uses auth_administrer()
 *
 * @param string $auth_methode
 * @param string $login
 * @param string $serveur
 * @return mixed
 */
function auth_terminer_identifier_login($auth_methode, $login, $serveur = '') {
	$args = func_get_args();
	return auth_administrer('terminer_identifier_login', $args);
}

/**
 * Loger un auteur suite a son identification
 *
 * @param array $auteur
 * @return bool
 */
function auth_loger($auteur) {
	if (!is_array($auteur) || $auteur === []) {
		return false;
	}

	// initialiser et poser le cookie de session
	unset($_COOKIE['spip_session']);
	if (auth_init_droits($auteur) === false) {
		return false;
	}

	// initialiser les prefs
	$p = $GLOBALS['visiteur_session']['prefs'];
	$p['cnx'] = (isset($auteur['cookie']) && $auteur['cookie'] == 'oui') ? 'perma' : '';

	sql_updateq(
		'spip_auteurs',
		['prefs' => serialize($p)],
		'id_auteur=' . (int) $auteur['id_auteur']
	);

	//  bloquer ici le visiteur qui tente d'abuser de ses droits
	verifier_visiteur();
	return true;
}

/**
 * Déconnexion de l'auteur
 *
 * @uses action_logout_dist()
 * return void
 **/
function auth_deloger() {
	$logout = charger_fonction('logout', 'action');
	$logout();
}

/**
 * Tester la possibilité de modifier le login d'authentification
 * pour la méthode donnée
 *
 * @uses auth_administrer()
 *
 * @param string $auth_methode
 * @param string $serveur
 * @return bool
 */
function auth_autoriser_modifier_login($auth_methode, $serveur = '') {
	$args = func_get_args();
	return auth_administrer('autoriser_modifier_login', $args);
}

/**
 * Verifier la validite d'un nouveau login pour modification
 * pour la methode donnee
 *
 * @param string $auth_methode
 * @param string $new_login
 * @param int $id_auteur
 * @param string $serveur
 * @return string
 *  message d'erreur ou chaine vide si pas d'erreur
 */
function auth_verifier_login($auth_methode, $new_login, $id_auteur = 0, $serveur = '') {
	$args = func_get_args();
	return auth_administrer('verifier_login', $args, '');
}

/**
 * Modifier le login d'un auteur pour la methode donnee
 *
 * @param string $auth_methode
 * @param string $new_login
 * @param int $id_auteur
 * @param string $serveur
 * @return bool
 */
function auth_modifier_login($auth_methode, $new_login, $id_auteur, $serveur = '') {
	$args = func_get_args();
	return auth_administrer('modifier_login', $args);
}

/**
 * Tester la possibilité de modifier le pass
 * pour la méthode donnée
 *
 * @uses auth_administrer()
 *
 * @param string $auth_methode
 * @param string $serveur
 * @return bool
 *  succès ou échec
 */
function auth_autoriser_modifier_pass($auth_methode, $serveur = '') {
	$args = func_get_args();
	return auth_administrer('autoriser_modifier_pass', $args);
}

/**
 * Verifier la validite d'un pass propose pour modification
 * pour la methode donnee
 *
 * @param string $auth_methode
 * @param string $login
 * @param string $new_pass
 * @param int $id_auteur
 * @param string $serveur
 * @return string
 *  message d'erreur ou chaine vide si pas d'erreur
 */
function auth_verifier_pass($auth_methode, $login, #[\SensitiveParameter] $new_pass, $id_auteur = 0, $serveur = '') {
	$args = func_get_args();
	return auth_administrer('verifier_pass', $args, '');
}

/**
 * Modifier le mot de passe d'un auteur
 * pour la methode donnee
 *
 * @param string $auth_methode
 * @param string $login
 * @param string $new_pass
 * @param int $id_auteur
 * @param string $serveur
 * @return bool
 *  succes ou echec
 */
function auth_modifier_pass($auth_methode, $login, #[\SensitiveParameter] $new_pass, $id_auteur, $serveur = '') {
	$args = func_get_args();
	return auth_administrer('modifier_pass', $args);
}

/**
 * Synchroniser un compte sur une base distante pour la methode
 * donnée lorsque des modifications sont faites dans la base auteur
 *
 * @param string|bool $auth_methode
 *   ici true permet de forcer la synchronisation de tous les acces pour toutes les methodes
 * @param int $id_auteur
 * @param array $champs
 * @param array $options
 * @param string $serveur
 * @return void
 */
function auth_synchroniser_distant(
	$auth_methode = true,
	$id_auteur = 0,
	$champs = [],
	$options = [],
	$serveur = ''
) {
	$args = func_get_args();
	if ($auth_methode === true || isset($options['all']) && $options['all'] == true) {
		$options['all'] = true; // ajouter une option all=>true pour chaque auth
		$args = [true, $id_auteur, $champs, $options, $serveur];
		foreach ($GLOBALS['liste_des_authentifications'] as $methode) {
			array_shift($args);
			array_unshift($args, $methode);
			auth_administrer('synchroniser_distant', $args);
		}
	} else {
		auth_administrer('synchroniser_distant', $args);
	}
}


/**
 * Vérifier si l'auteur est bien authentifié
 *
 * @param string $login
 * @param string $pw
 * @param string $serveur
 * @return array|bool
 */
function lire_php_auth($login, #[\SensitiveParameter] $pw, $serveur = '') {
	if (
		!$login
		|| !$login_base = auth_retrouver_login($login, $serveur)
	) {
		return false;
	}

	$row = sql_fetsel('*', 'spip_auteurs', 'login=' . sql_quote($login_base, $serveur, 'text'), '', '', '', '', $serveur);

	if (!$row) {
		if (
			include_spip('inc/auth')
			&& auth_ldap_connect($serveur)
			&& ($auth_ldap = charger_fonction('ldap', 'auth', true))
		) {
			return $auth_ldap($login_base, $pw, $serveur, true);
		}

		return false;
	}

	// si pas de source definie
	// ou auth/xxx introuvable, utiliser 'spip' ou autre et avec le login passé par PHP_AUTH_USER
	if (
		!($auth_methode = $row['source'])
		|| !($auth = charger_fonction($auth_methode, 'auth', true))
	) {
		$auth = charger_fonction('spip', 'auth', true);
	}

	$auteur = '';
	if ($auth) {
		$auteur = $auth($login, $pw, $serveur, true);
	}
	// verifier que ce n'est pas un message d'erreur
	if (is_array($auteur) && count($auteur)) {
		return $auteur;
	}

	return false;
}

/**
 * entête php_auth (est-encore utilisé ?)
 *
 * @uses minipres()
 *
 * @param string $pb
 * @param string $raison
 * @param string $retour
 * @param string $url
 * @param string $re
 * @param string $lien
 */
function ask_php_auth($pb, $raison, $retour = '', $url = '', $re = '', $lien = '') {
	@Header('WWW-Authenticate: Basic realm="espace prive"');
	@Header('HTTP/1.0 401 Unauthorized');
	$corps = '';
	$public = generer_url_public();
	$ecrire = generer_url_ecrire();
	$retour = $retour ?: _T('icone_retour');
	$corps .= "<p>$raison</p>[<a href='$public'>$retour</a>] ";
	if ($url) {
		$corps .= "[<a href='" . generer_url_action('cookie', "essai_auth_http=oui&$url") . "'>$re</a>]";
	}

	if ($lien) {
		$corps .= " [<a href='$ecrire'>" . _T('login_espace_prive') . '</a>]';
	}
	include_spip('inc/minipres');
	echo minipres($pb, $corps);
	exit;
}
