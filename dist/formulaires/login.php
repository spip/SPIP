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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('base/abstract_sql');
spip_connect();

function formulaires_login_charger_dist($cible="",$login="",$prive=null){
	$auteur = array();
	$login = $login ? $login : _request('var_login');
	if (is_null($prive)){
		$parse = parse_url($cible);
		$prive = strncmp(substr($parse['path'],-strlen(_DIR_RESTREINT_ABS)), _DIR_RESTREINT_ABS, strlen(_DIR_RESTREINT_ABS))==0;
	}
	
	// Le login est memorise dans le cookie d'admin eventuel
	if (!$login) {
		if (isset($_COOKIE['spip_admin']) && preg_match(",^@(.*)$,", $_COOKIE['spip_admin'], $regs))
			$login = $regs[1];
	} 
	else if ($login == '-1'){
		$login = '';
	}
	if ($login){
		include_spip('inc/identifier_login');
		$auteur = informer_login($login);
	}
	// Ne pas proposer de "rester connecte quelques jours"
	// si la duree de l'alea est inferieure a 12 h (valeur par defaut)
	$rester_connecte = (_RENOUVELLE_ALEA < 12*3600) ? '' : ' ';

	// Gerer le cas ou un utilisateur ne souhaite pas de cookie
	// on propose alors un formulaire pour s'authentifier via http
	$auth_http = '';	
	if (!$ignore_auth_http
		AND _request('var_erreur')=='cookie' 
		AND $_COOKIE['spip_session'] != 'test_echec_cookie'
		AND (($GLOBALS['flag_sapi_name'] AND preg_match(",apache,i", @php_sapi_name()))
			OR preg_match(",^Apache.* PHP,", $_SERVER['SERVER_SOFTWARE']))
		// Attention dans le cas 'intranet' la proposition de se loger
		// par auth_http peut conduire a l'echec.
		AND !(isset($_SERVER['PHP_AUTH_USER']) AND isset($_SERVER['PHP_AUTH_PW'])))
	{
		$auth_http = generer_url_action('cookie',"",false,true);
	}
		
	$valeurs = array(
		'auth_http' => $auth_http,
		'var_login' => $login,
		'rester_connecte' => $rester_connecte,
		'_logo' => isset($auteur['logo'])?$auteur['logo']:'',
		'cnx' => isset($auteur['cnx'])?$auteur['cnx']:'',
		'_alea_actuel' => isset($auteur['alea_actuel'])?$auteur['alea_actuel']:'',
		'_alea_futur' => isset($auteur['alea_futur'])?$auteur['alea_futur']:'',
	);
	$valeurs['_hidden'] = 
	'<input type="hidden" name="session_password_md5" value="" />'
	. '<input type="hidden" name="next_session_password_md5" value="" />';

	// Si on est connecte, envoyer vers la destination
	// si on en a le droit, et sauf si on y est deja
	verifier_visiteur();
	$editable = false;
	if (_request('var_erreur')
	OR !$GLOBALS['visiteur_session']['id_auteur'])
		$editable = true;

	if ($prive) {
		include_spip('inc/autoriser');
		$loge = autoriser('ecrire');
	} else {
		$loge = ($visiteur_session['auth'] != '');
	}
	if ($loge) {
		// on est a destination ?
		if ($cible == self())
			$editable = false;
		else {
			// sinon on y va
			include_spip('inc/headers');
			$valeurs['_deja_loge'] = 
			  "<a href='$cible'>" . _T('login_par_ici') . "</a>"
				. redirige_formulaire($cible)
				;
		}
	}

	// en cas d'echec de cookie, inc_auth a renvoye vers le script de
	// pose de cookie ; s'il n'est pas la, c'est echec cookie
	// s'il est la, c'est probablement un bookmark sur bonjour=oui,
	// et pas un echec cookie.
	if (_request('var_erreur') == 'cookie')
		$valeurs['echec_cookie'] = ' ';


	return array($editable,$valeurs);
}

function formulaires_login_verifier_dist($cible="",$login="",$prive=null){
	global $ignore_auth_http;
	if (is_null($prive)){
		$parse = parse_url($cible);
		$prive = strncmp(substr($parse['path'],-strlen(_DIR_RESTREINT_ABS)), _DIR_RESTREINT_ABS, strlen(_DIR_RESTREINT_ABS))==0;
	}
	$erreurs = array();
	$session_login = _request('var_login');
	$session_password = _request('password');
	$session_md5pass = _request('session_password_md5');
	$session_md5next = _request('next_session_password_md5');
	$session_remember = _request('session_remember');


	if ($session_login) {
		$row =  sql_fetsel('*', 'spip_auteurs', "login=" . sql_quote($session_login));
		// Retrouver ceux qui signent de leur nom ou email
		if (!$row AND !spip_connect_ldap()) {
			$row = sql_fetsel('*', 'spip_auteurs', "(nom = " . sql_quote($session_login) . " OR email = " . sql_quote($session_login) . ") AND login<>'' AND statut<>'5poubelle'");
			if ($row) {
				$login_alt = $session_login; # afficher ce qu'on a tape
				$session_login = $row['login'];
			}
		}

		if ((!$row AND !spip_connect_ldap()) OR
			($row['statut'] == '5poubelle') OR 
			(($row['source'] == 'spip') AND $row['pass'] == '')) {
			$erreurs['message_erreur'] =  _T('login_identifiant_inconnu',
				array('login' => htmlspecialchars($session_login)));
			$row = array();
			$session_login = '';
			include_spip('inc/cookie');
			spip_setcookie("spip_admin", "", time() - 3600);
		} else {
			// on laisse le menu decider de la langue
			unset($row['lang']);
		}
		$identifier_login = charger_fonction('identifier_login','inc');
		if (!$identifier_login($session_login, $session_password,
		$session_md5pass, $session_md5next, $session_remember)){
			$erreurs['password'] = _T('login_erreur_pass');
		}
		else {
			# login ok
			# verifier si on a pas affaire a un visiteur qui essaye de se loge sur ecrire/
			if ($prive) {
				include_spip('inc/autoriser');
				verifier_visiteur();
				if (!autoriser('ecrire')){
					$erreurs['message_erreur'] = "<h1>"._T('avis_erreur_visiteur')."</h1>"
						. "<p>"._T('texte_erreur_visiteur')."</p>"
						. "<p class='retour'>[<a href='".generer_url_action('logout','logout=prive&url='.urlencode(self()))."'>"._T('icone_deconnecter')."</a>]</p>";
				}
			}
		}
	} else {
		# pas de login saisi !
		$erreurs['message_erreur'] =  _T('login_identifiant_inconnu',
			array('login' => htmlspecialchars($login)));
	}
	
	return $erreurs;
}

function formulaires_login_traiter_dist($cible="",$login="",$prive=null){
	if (is_null($prive)){
		$parse = parse_url($cible);
		$prive = strncmp(substr($parse['path'],-strlen(_DIR_RESTREINT_ABS)), _DIR_RESTREINT_ABS, strlen(_DIR_RESTREINT_ABS))==0;
	}
	$message = '';	
	$auth = charger_fonction('auth','inc');
	$auth();

	// Si on se connecte dans l'espace prive, 
	// ajouter "bonjour" (repere a peu pres les cookies desactives)
	if ($prive)
		$cible = parametre_url($cible, 'bonjour', 'oui', '&');

	if ($cible) {
		$cible = parametre_url($cible, 'var_login', '', '&');
	} 
	/* cible est fourni par la balise si on veut vraiment etre redirige
	else {
		if ($cible = parametre_url($action,'url'))
			$cible = $cible;
		else $cible = generer_url_ecrire();
	}*/

	// Si on est admin, poser le cookie de correspondance
	if ($GLOBALS['auteur_session']['statut'] == '0minirezo') {
		include_spip('inc/cookie');
		spip_setcookie('spip_admin', '@'.$GLOBALS['auteur_session']['login'],
		time() + 7 * 24 * 3600);
	}

	// Si on est connecte, envoyer vers la destination
	if ($cible
	 AND ($cible!=self())) {
		if (!headers_sent() AND !$_GET['var_mode']) {
			include_spip('inc/headers');
			$message .= redirige_formulaire($cible);
		} else {
			$message .= "<a href='$cible'>" .
			  _T('login_par_ici') .
			  "</a>";
		}
	}
	return $message;
}


?>
