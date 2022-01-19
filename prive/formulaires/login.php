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

/**
 * Gestion du formulaire d'identification / de connexion à SPIP
 *
 * @package SPIP\Core\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/abstract_sql');

/**
 * Teste si une URL est une URL de l'espace privé (administration de SPIP)
 * ou de l'espace public
 *
 * @param string $cible URL
 * @return bool
 *     true si espace privé, false sinon.
 **/
function is_url_prive($cible) {
	include_spip('inc/filtres_mini');
	$path = parse_url(tester_url_absolue($cible) ? $cible : url_absolue($cible));
	$path = ($path['path'] ?? '');

	return strncmp(substr($path, -strlen(_DIR_RESTREINT_ABS)), _DIR_RESTREINT_ABS, strlen(_DIR_RESTREINT_ABS)) == 0;
}

/**
 * Chargement du formulaire de login
 *
 * Si on est déjà connecté, on redirige directement sur l'URL cible !
 *
 * @uses auth_informer_login()
 * @uses is_url_prive()
 * @uses login_auth_http()
 *
 * @param string $cible
 *     URL de destination après identification.
 *     Cas spécifique : la valeur `@page_auteur` permet d'être redirigé
 *     après connexion sur le squelette public de l'auteur qui se connecte.
 * @param array $options
 *   string $login : Login de la personne à identifier (si connu)
 *   null|bool $prive : Identifier pour l'espace privé (true), public (false) ou automatiquement (null) en fonction de la destination de l'URL cible.
 * @param null $deprecated
 *
 * @return array
 *     Environnement du formulaire
 **/
function formulaires_login_charger_dist($cible = '', $options = [], $deprecated = null) {
	$erreur = _request('var_erreur');

	if (!is_array($options)) {
		$options = [
			'login' => $options,
			'prive' => $deprecated
		];
	}

	$login = (empty($options['login']) ? '' : $options['login']);
	$prive = (empty($options['prive']) ? null : $options['prive']);
	// conserver le fonctionnement historique
	if (!isset($options['autofocus'])) {
		$options['autofocus'] = true;
	}

	if (!$login) {
		$login = strval(_request('var_login'));
	}
	// si on est deja identifie
	if (!$login and isset($GLOBALS['visiteur_session']['email'])) {
		$login = $GLOBALS['visiteur_session']['email'];
	}
	if (!$login and isset($GLOBALS['visiteur_session']['login'])) {
		$login = $GLOBALS['visiteur_session']['login'];
	}
	// ou si on a un cookie admin
	if (!$login) {
		if (
			isset($_COOKIE['spip_admin'])
			and preg_match(',^@(.*)$,', $_COOKIE['spip_admin'], $regs)
		) {
			$login = $regs[1];
		}
	}

	$lang = $GLOBALS['spip_lang'];
	include_spip('inc/auth');
	$row = auth_informer_login($login);

	// retablir la langue de l'URL si forcee (on ignore la langue de l'auteur dans ce cas)
	if (_request('lang') === $lang and $GLOBALS['spip_lang'] !== $lang) {
		changer_langue($lang);
	}

	// Construire l'environnement du squelette
	// Ne pas proposer de "rester connecte quelques jours"
	// si la duree de l'alea est inferieure a 12 h (valeur par defaut)

	$valeurs = [
		'var_login' => $login,
		'editable' => !$row,
		'cnx' => $row['cnx'] ?? '0',
		'auth_http' => login_auth_http(),
		'rester_connecte' => ((_RENOUVELLE_ALEA < 12 * 3600) ? '' : ' '),
		'_logo' => $row['logo'] ?? '',
		'_alea_actuel' => $row['alea_actuel'] ?? '',
		'_alea_futur' => $row['alea_futur'] ?? '',
		'_pipeline' => 'affiche_formulaire_login', // faire passer le formulaire dans un pipe dedie pour les methodes auth
		'_autofocus' => ($options['autofocus'] and $options['autofocus'] !== 'non') ? ' ' : '',
	];

	if ($erreur or !isset($GLOBALS['visiteur_session']['id_auteur']) or !$GLOBALS['visiteur_session']['id_auteur']) {
		$valeurs['editable'] = true;
	}

	if (is_null($prive) ? is_url_prive($cible) : $prive) {
		include_spip('inc/autoriser');
		$loge = autoriser('ecrire');
	} else {
		$loge = (isset($GLOBALS['visiteur_session']['auth']) and $GLOBALS['visiteur_session']['auth'] != '');
	}

	// Si on est connecte, appeler traiter()
	// et lancer la redirection si besoin
	if (!$valeurs['editable'] and $loge and _request('formulaire_action') !== 'login') {
		$traiter = charger_fonction('traiter', 'formulaires/login');
		$res = $traiter($cible, $login, $prive);
		$valeurs = array_merge($valeurs, $res);

		if (isset($res['redirect']) and $res['redirect']) {
			include_spip('inc/headers');
			# preparer un lien pour quand redirige_formulaire ne fonctionne pas
			$m = redirige_formulaire($res['redirect']);
			$valeurs['_deja_loge'] = inserer_attribut(
				'<a>' . _T('login_par_ici') . "</a>$m",
				'href',
				$res['redirect']
			);
		}
	}
	// en cas d'echec de cookie, inc_auth a renvoye vers le script de
	// pose de cookie ; s'il n'est pas la, c'est echec cookie
	// s'il est la, c'est probablement un bookmark sur bonjour=oui,
	// et pas un echec cookie.
	if ($erreur == 'cookie') {
		$valeurs['echec_cookie'] = ' ';
	} elseif ($erreur) {
		// une erreur d'un SSO indique dans la redirection vers ici
		// mais il faut se proteger de toute tentative d'injection malveilante
		include_spip('inc/filtres');
		$valeurs['message_erreur'] = textebrut($erreur);
	}

	return $valeurs;
}


/**
 * Identification via HTTP (si pas de cookie)
 *
 * Gére le cas où un utilisateur ne souhaite pas de cookie :
 * on propose alors un formulaire pour s'authentifier via http
 *
 * @return string
 *     - Si connection possible en HTTP : URL pour réaliser cette identification,
 *     - chaîne vide sinon.
 **/
function login_auth_http() {
	if (
		!$GLOBALS['ignore_auth_http']
		and _request('var_erreur') == 'cookie'
		and (!isset($_COOKIE['spip_session']) or $_COOKIE['spip_session'] != 'test_echec_cookie')
		and (($GLOBALS['flag_sapi_name'] and preg_match(',apache,i', @php_sapi_name()))
			or preg_match(',^Apache.* PHP,', $_SERVER['SERVER_SOFTWARE']))
		// Attention dans le cas 'intranet' la proposition de se loger
		// par auth_http peut conduire a l'echec.
		and !(isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW']))
	) {
		return generer_url_action('cookie', '', false, true);
	} else {
		return '';
	}
}


/**
 * Vérifications du formulaire de login
 *
 * Connecte la personne si l'identification réussie.
 *
 * @uses auth_identifier_login()
 * @uses auth_loger()
 * @uses login_autoriser()
 *
 * @param string $cible
 *     URL de destination après identification.
 *     Cas spécifique : la valeur `@page_auteur` permet d'être redirigé
 *     après connexion sur le squelette public de l'auteur qui se connecte.
 * @param array $options
 *   string $login : Login de la personne à identifier (si connu)
 *   null|bool $prive : Identifier pour l'espace privé (true), public (false) ou automatiquement (null) en fonction de la destination de l'URL cible.
 * @param null $deprecated
 * @return array
 *     Erreurs du formulaire
 **/
function formulaires_login_verifier_dist($cible = '', $options = [], $deprecated = null) {

	$erreurs = [];
	if (!is_array($options)) {
		$options = [
			'login' => $options,
			'prive' => $deprecated
		];
	}

	$prive = (empty($options['prive']) ? null : $options['prive']);

	$session_login = _request('var_login');
	$session_password = _request('password');
	$session_remember = _request('session_remember');

	if (!$session_login) {
		# pas de login saisi !
		return ['var_login' => _T('info_obligatoire')];
	}

	// appeler auth_identifier_login qui va :
	// - renvoyer un string si echec (message d'erreur)
	// - un array decrivant l'auteur identifie si possible
	// - rediriger vers un SSO qui renverra in fine sur action/auth qui finira l'authentification
	include_spip('inc/auth');
	$auteur = auth_identifier_login($session_login, $session_password);
	// on arrive ici si on ne s'est pas identifie avec un SSO
	if (!is_array($auteur)) {
		$erreurs = [];
		if (is_string($auteur) and strlen($auteur)) {
			$erreurs['var_login'] = $auteur;
		}
		include_spip('inc/cookie');
		spip_setcookie('spip_admin', '', time() - 3600);
		if (strlen($session_password)) {
			$erreurs['password'] = _T('login_erreur_pass');
		} else {
			// sinon c'est un login en deux passe old style (ou js en panne)
			// pas de message d'erreur
			$erreurs['password'] = ' ';
			$erreurs['message_erreur'] = '';
		}

		return
			$erreurs;
	}
	// on a ete authentifie, construire la session
	// en gerant la duree demandee pour son cookie
	if ($session_remember !== null) {
		$auteur['cookie'] = $session_remember;
	}
	// si la connexion est refusee on renvoi un message erreur de mot de passe
	// car en donnant plus de detail on renseignerait un assaillant sur l'existence d'un compte
	if (auth_loger($auteur) === false) {
		$erreurs['message_erreur'] = _T('login_erreur_pass');
		return $erreurs;
	}

	return (is_null($prive) ? is_url_prive($cible) : $prive)
		? login_autoriser() : [];
}

/**
 * Teste l'autorisation d'accéder à l'espace privé une fois une connexion
 * réussie, si la cible est une URL privée.
 *
 * Dans le cas contraire, un message d'erreur est retourné avec un lien
 * pour se déconnecter.
 *
 * @return array
 *     - Erreur si un connecté n'a pas le droit d'acceder à l'espace privé
 *     - tableau vide sinon.
 **/
function login_autoriser() {
	include_spip('inc/autoriser');
	if (!autoriser('ecrire')) {
		$h = generer_url_action('logout', 'logout=prive&url=' . urlencode(self()));

		return [
			'message_erreur' => '<h1>'
				. _T('avis_erreur_visiteur')
				. '</h1><p>'
				. _T('texte_erreur_visiteur')
				. "</p><p class='retour'>[<a href='$h'>"
				. _T('icone_deconnecter') . '</a>]</p>'
		];
	}

	return [];
}

/**
 * Traitements du formulaire de login
 *
 * On arrive ici une fois connecté.
 * On redirige simplement sur l'URL cible désignée.
 *
 * @param string $cible
 *     URL de destination après identification.
 *     Cas spécifique : la valeur `@page_auteur` permet d'être redirigé
 *     après connexion sur le squelette public de l'auteur qui se connecte.
 * @param array $options
 *   string $login : Login de la personne à identifier (si connu)
 *   null|bool $prive : Identifier pour l'espace privé (true), public (false) ou automatiquement (null) en fonction de la destination de l'URL cible.
 * @param null $deprecated
 * @return array
 *     Retours du traitement
 **/
function formulaires_login_traiter_dist($cible = '', $options = [], $deprecated = null) {
	$res = [];

	if (!is_array($options)) {
		$options = [
			'login' => $options,
			'prive' => $deprecated
		];
	}

	$login = (empty($options['login']) ? '' : $options['login']);
	$prive = (empty($options['prive']) ? null : $options['prive']);

	// Si on se connecte dans l'espace prive,
	// ajouter "bonjour" (repere a peu pres les cookies desactives)
	if (is_null($prive) ? is_url_prive($cible) : $prive) {
		$cible = parametre_url($cible, 'bonjour', 'oui', '&');
	}
	if ($cible == '@page_auteur') {
		$cible = generer_objet_url($GLOBALS['auteur_session']['id_auteur'], 'auteur');
	}

	if ($cible) {
		$cible = parametre_url($cible, 'var_login', '', '&');

		// transformer la cible absolue en cible relative
		// pour pas echouer quand la meta adresse_site est foireuse
		if (strncmp($cible, $u = url_de_base(), strlen($u)) == 0) {
			$cible = './' . substr($cible, strlen($u));
		} elseif (tester_url_absolue($cible) and !defined('_AUTORISER_LOGIN_ABS_REDIRECT')) {
			// si c'est une url absolue, refuser la redirection
			// sauf si cette securite est levee volontairement par le webmestre
			$cible = '';
		}
	}

	// Si on est connecte, envoyer vers la destination
	if ($cible and ($cible != self('&')) and ($cible != self())) {
		$res['redirect'] = $cible;
		$res['message_ok'] = inserer_attribut(
			'<a>' . _T('login_par_ici') . '</a>',
			'href',
			$cible
		);
	}

	// avant de rediriger il faut mettre a jour les sessions sur le disque si on a charge une session
	if (function_exists('terminer_actualiser_sessions')) {
		terminer_actualiser_sessions();
	}

	return $res;
}
