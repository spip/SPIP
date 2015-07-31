<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/abstract_sql');

function is_url_prive($cible){
	include_spip('inc/filtres_mini');
	$path = parse_url(tester_url_absolue($cible)?$cible:url_absolue($cible));
	$path = (isset($path['path'])?$path['path']:'');
	return strncmp(substr($path,-strlen(_DIR_RESTREINT_ABS)), _DIR_RESTREINT_ABS, strlen(_DIR_RESTREINT_ABS))==0;
}

function formulaires_login_charger_dist($cible="",$login="",$prive=null)
{
	$erreur = _request('var_erreur');

	if (!$login) $login = strval(_request('var_login'));
	// si on est deja identifie
	if (!$login AND isset($GLOBALS['visiteur_session']['login'])) {
		$login = $GLOBALS['visiteur_session']['login'];
	}
	// ou si on a un cookie admin
	if (!$login) {
		if (isset($_COOKIE['spip_admin'])
		AND preg_match(",^@(.*)$,", $_COOKIE['spip_admin'], $regs))
			$login = $regs[1];
	}

	include_spip('inc/auth');
	$row = auth_informer_login($login);

	// Construire l'environnement du squelette
	// Ne pas proposer de "rester connecte quelques jours"
	// si la duree de l'alea est inferieure a 12 h (valeur par defaut)
		
	$valeurs = array(
		'var_login' => $login,
		'editable' => !$row,
		'cnx' => isset($row['cnx']) ? $row['cnx'] : '',
		'auth_http' => login_auth_http(),
		'rester_connecte' => ((_RENOUVELLE_ALEA < 12*3600)? '' : ' '),
		'_logo' => isset($row['logo']) ? $row['logo'] : '',
		'_alea_actuel' => isset($row['alea_actuel'])?$row['alea_actuel']:'',
		'_alea_futur' => isset($row['alea_futur'])?$row['alea_futur']:'',
		'_pipeline' => 'affiche_formulaire_login', // faire passer le formulaire dans un pipe dedie pour les methodes auth
		);

	if ($erreur OR !isset($GLOBALS['visiteur_session']['id_auteur']) OR !$GLOBALS['visiteur_session']['id_auteur'])
		$valeurs['editable'] = true;

	if (is_null($prive) ? is_url_prive($cible) : $prive) {
		include_spip('inc/autoriser');
		$loge = autoriser('ecrire');
	} 
	else
		$loge = (isset($GLOBALS['visiteur_session']['auth']) AND $GLOBALS['visiteur_session']['auth'] != '');

	// Si on est connecte, appeler traiter()
	// et lancer la redirection si besoin
	if (!$valeurs['editable'] AND $loge) {
		$traiter = charger_fonction('traiter','formulaires/login');
		$res = $traiter($cible, $login, $prive);
		$valeurs = array_merge($valeurs,$res);

		if (isset($res['redirect']) and $res['redirect']){
			include_spip('inc/headers');
			# preparer un lien pour quand redirige_formulaire ne fonctionne pas
			$m = redirige_formulaire($res['redirect']);
			$valeurs['_deja_loge'] = inserer_attribut(
				"<a>" . _T('login_par_ici') . "</a>$m",
				'href', $res['redirect']
			);
		}
	}
	// en cas d'echec de cookie, inc_auth a renvoye vers le script de
	// pose de cookie ; s'il n'est pas la, c'est echec cookie
	// s'il est la, c'est probablement un bookmark sur bonjour=oui,
	// et pas un echec cookie.
	if ($erreur == 'cookie') $valeurs['echec_cookie'] = ' ';
	elseif ($erreur){
		// une erreur d'un SSO indique dans la redirection vers ici
		// mais il faut se proteger de toute tentative d'injection malveilante
		include_spip('inc/texte');
		$valeurs['message_erreur'] = safehtml($erreur);
	}

	return $valeurs;
}


// Gerer le cas ou un utilisateur ne souhaite pas de cookie
// on propose alors un formulaire pour s'authentifier via http

function login_auth_http()
{
	if (!$GLOBALS['ignore_auth_http']
		AND _request('var_erreur')=='cookie' 
		AND (!isset($_COOKIE['spip_session']) OR $_COOKIE['spip_session'] != 'test_echec_cookie')
		AND (($GLOBALS['flag_sapi_name'] AND preg_match(",apache,i", @php_sapi_name()))
			OR preg_match(",^Apache.* PHP,", $_SERVER['SERVER_SOFTWARE']))
		// Attention dans le cas 'intranet' la proposition de se loger
		// par auth_http peut conduire a l'echec.
		AND !(isset($_SERVER['PHP_AUTH_USER']) AND isset($_SERVER['PHP_AUTH_PW'])))

		return generer_url_action('cookie',"",false,true);
	else 	return '';
}

function formulaires_login_verifier_dist($cible="",$login="",$prive=null){
	
	$session_login = _request('var_login');
	$session_password = _request('password');
	$session_remember = _request('session_remember');

	if (!$session_login) {
		# pas de login saisi !
		return array('var_login' => _T('info_obligatoire'));
	}

	// appeler auth_identifier_login qui va :
	// - renvoyer un string si echec (message d'erreur)
	// - un array decrivant l'auteur identifie si possible
	// - rediriger vers un SSO qui renverra in fine sur action/auth qui finira l'authentification
	include_spip('inc/auth');
	$auteur = auth_identifier_login($session_login, $session_password);
	// on arrive ici si on ne s'est pas identifie avec un SSO
	if (!is_array($auteur)) {
		$erreurs = array();
		if (is_string($auteur))
			$erreurs['var_login'] = $auteur;
		include_spip('inc/cookie');
		spip_setcookie("spip_admin", "", time() - 3600);
		if (strlen($session_password))
			$erreurs['password'] = _T('login_erreur_pass');
		// sinon c'est un login en deux passe old style (ou js en panne)
		// pas de message d'erreur
		else 
			$erreurs['password'] = ' ';
		return
			$erreurs;
	}
	// on a ete authentifie, construire la session
	// en gerant la duree demandee pour son cookie 
	if ($session_remember !== NULL)
		$auteur['cookie'] = $session_remember;
	auth_loger($auteur);

	return (is_null($prive) ? is_url_prive($cible) : $prive)
	?  login_autoriser() : array();
}

function login_autoriser()
{
	include_spip('inc/autoriser');
	if (!autoriser('ecrire')){
		$h = generer_url_action('logout','logout=prive&url='.urlencode(self()));
		return array('message_erreur' => "<h1>"
				._T('avis_erreur_visiteur')
				."</h1><p>"
				._T('texte_erreur_visiteur')
				."</p><p class='retour'>[<a href='$h'>"
				._T('icone_deconnecter')."</a>]</p>");
	}
	return array();
}

function formulaires_login_traiter_dist($cible="",$login="",$prive=null){
	$res = array();
	// Si on se connecte dans l'espace prive, 
	// ajouter "bonjour" (repere a peu pres les cookies desactives)
	if (is_null($prive) ? is_url_prive($cible) : $prive) {
		$cible = parametre_url($cible, 'bonjour', 'oui', '&');
	}
	if ($cible=='@page_auteur')
		$cible = generer_url_entite($GLOBALS['auteur_session']['id_auteur'],'auteur');

	if ($cible) {
		$cible = parametre_url($cible, 'var_login', '', '&');

		// transformer la cible absolue en cible relative
		// pour pas echouer quand la meta adresse_site est foireuse
		if (strncmp($cible,$u = url_de_base(),strlen($u))==0){
			$cible = "./".substr($cible,strlen($u));
		}

		// si c'est une url absolue, refuser la redirection
		// sauf si cette securite est levee volontairement par le webmestre
		elseif (tester_url_absolue($cible) AND !defined('_AUTORISER_LOGIN_ABS_REDIRECT')) {
			$cible = "";
		}
	}

	// Si on est connecte, envoyer vers la destination
	if ($cible AND ($cible!=self('&'))  AND ($cible!=self())) {
		if (!headers_sent() AND !$_GET['var_mode']) {
			include_spip('inc/headers');
			$res['redirect'] = $cible;
		} else {
			$res['message_ok'] = inserer_attribut(
				"<a>" . _T('login_par_ici') . "</a>",
				'href', $cible
			);
		}
	}
	return $res;
}

?>
