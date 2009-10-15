<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('base/abstract_sql');

function is_url_prive($cible){
	$parse = parse_url($cible);
	return strncmp(substr($parse['path'],-strlen(_DIR_RESTREINT_ABS)), _DIR_RESTREINT_ABS, strlen(_DIR_RESTREINT_ABS))==0;
}

function formulaires_login_charger_dist($cible="",$login="",$prive=null)
{
	$erreur = _request('var_erreur');

	if (!$login) $login = _request('var_login');
	if (!$login) {
		if (isset($_COOKIE['spip_admin'])
		AND preg_match(",^@(.*)$,", $_COOKIE['spip_admin'], $regs))
			$login = $regs[1];
	} 

	$row = informer_login($login);

	// Construire l'environnement du squelette
	// Ne pas proposer de "rester connecte quelques jours"
	// si la duree de l'alea est inferieure a 12 h (valeur par defaut)
		
	$valeurs = array(
		'var_login' => $login,
		'editable' => !$row,
		'cnx' => $row['cnx'],
		'auth_http' => login_auth_http(),
		'rester_connecte' => ((_RENOUVELLE_ALEA < 12*3600)? '' : ' '),
		'_logo' => $row['logo'],
		'_alea_actuel' => isset($row['alea_actuel'])?$row['alea_actuel']:'',
		'_alea_futur' => isset($row['alea_futur'])?$row['alea_futur']:'',
		'_hidden' => '<input type="hidden" name="session_password_md5" value="" /><input type="hidden" name="next_session_password_md5" value="" />'
		);

	if ($erreur OR !$GLOBALS['visiteur_session']['id_auteur'])
		$valeurs['editable'] = true;

	if (is_null($prive) ? is_url_prive($cible) : $prive) {
		include_spip('inc/autoriser');
		$loge = autoriser('ecrire');
	} else 	$loge = ($GLOBALS['visiteur_session']['auth'] != '');

	// Si on est connecte, appeler traiter()
	// et lancer la redirection si besoin
	if (!$valeurs['editable'] AND $loge) {
		$traiter = charger_fonction('traiter','formulaires/login');
		$res = $traiter($cible, $login, $prive);
		$valeurs = array_merge($valeurs,$res);

		if ($res['redirect']){
			include_spip('inc/headers');
			$m = redirige_formulaire($res['redirect']);
			# quand la redirection 302 ci-dessus ne fonctionne pas
			$valeurs['_deja_loge'] =
			"<a href='$cible'>" . _T('login_par_ici') . "</a>$m";
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

// Cette fonction sert a 2 squelettes: login et informer_auteur
// Ce dernier transmet le tableau ci-dessous a la fonction JS informer_auteur
// Il est invoque par la fonction JS actualise_auteur via la globale JS
// page_auteur=#URL_PAGE{informer_auteur} dans le squelette login
// N'y aurait-il pas plus simple ?

// http://doc.spip.org/@informer_login
function informer_login($login){
	if (!$login) return array();
	$row = retrouver_login($login);
	if (!$row) return array();
	// desactiver le hash md5 si pas auteur spip ?
	if ($row['source']!=='spip'){
		$row['alea_actuel']= '';
		$row['alea_futur']= '';
	}
	$prefs = unserialize($row['prefs']);
	$row['cnx'] = ($prefs['cnx'] == 'perma') ? '1' : '0';
	$row['logo'] = recuperer_fond('formulaires/inc-logo_auteur', $row);
	unset($row['prefs']);
	unset($row['source']);		
	verifier_visiteur();
	return $row;
}

// Gerer le cas ou un utilisateur ne souhaite pas de cookie
// on propose alors un formulaire pour s'authentifier via http

function login_auth_http()
{
	if (!$GLOBALS['ignore_auth_http']
		AND _request('var_erreur')=='cookie' 
		AND $_COOKIE['spip_session'] != 'test_echec_cookie'
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
	$session_md5pass = _request('session_password_md5');
	$session_md5next = _request('next_session_password_md5');
	$session_remember = _request('session_remember');

	if (!$session_login) {
		# pas de login saisi !
		return array('message_erreur' =>
			_T('login_identifiant_inconnu',
				array('login' => htmlspecialchars($login))));
	}
	$row = retrouver_login($session_login);
	if ($row) 
		$login = $row['login'];
	elseif (spip_connect_ldap()) 
		$login = $session_login;  // laisser une chance
	else {
		include_spip('inc/cookie');
		spip_setcookie("spip_admin", "", time() - 3600);
		return array('message_erreur' =>
			_T('login_identifiant_inconnu',
			array('login' => htmlspecialchars($session_login))));
	}
	$auteur = verifier_login($login, $session_password, $session_md5pass, $session_md5next);
	if (!$auteur) {
		if (strlen($session_password) OR strlen($session_md5pass))
			return array('password' => _T('login_erreur_pass'));
		// sinon c'est un login en deux passe old style (ou js en panne)
		// pas de message d'erreur
		else return array('password' => ' ');
	}
	// on a ete authentifie, construire la session
	// en gerant la duree demandee pour son cookie 
	if ($session_remember !== NULL)
		$auteur['cookie'] = $session_remember;
	$session = charger_fonction('session', 'inc');
	$session($auteur);
	$p = ($auteur['prefs']) ? unserialize($auteur['prefs']) : array();
	$p['cnx'] = ($session_remember == 'oui') ? 'perma' : '';
	$p = array('prefs' => serialize($p));
	sql_updateq('spip_auteurs', $p, "id_auteur=" . $auteur['id_auteur']);
	//  bloquer ici le visiteur qui tente d'abuser de ses droits
	verifier_visiteur();
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
	} 
	
	// transformer la cible absolue en cible relative
	// pour pas echouer quand la meta adresse_site est foireuse
	if (strncmp($cible,$u = url_de_base(),strlen($u))==0){
		$cible = "./".substr($cible,strlen($u));
	}

	// Si on est admin, poser le cookie de correspondance
	if ($GLOBALS['auteur_session']['statut'] == '0minirezo') {
		include_spip('inc/cookie');
		spip_setcookie('spip_admin', '@'.$GLOBALS['auteur_session']['login'],
		time() + 7 * 24 * 3600);
	}

	// Si on est connecte, envoyer vers la destination
	if ($cible AND ($cible!=self())) {
		if (!headers_sent() AND !$_GET['var_mode']) {
			include_spip('inc/headers');
			$res['redirect'] = $cible;
		} else {
			$res['message_ok'] .= "<a href='$cible'>" .
			  _T('login_par_ici') .
			  "</a>";
		}
	}
	return $res;
}


// Reconnaitre aussi ceux qui donnent leur nom ou email au lieu du login

function retrouver_login($login)
{
	if (!spip_connect()) {
		include_spip('inc/minipres');
		echo minipres(_T('info_travaux_titre'),
			      _T('titre_probleme_technique'));
		exit;
	}
	$l = sql_quote($login);
	if ($r = sql_fetsel('id_auteur,login,alea_actuel,alea_futur,prefs,source,login', 'spip_auteurs',
			"statut<>'5poubelle' AND (" .
			"pass<>'' OR source<>'spip') AND (" . 
			"login=$l)"))
		return $r;
	// Si pas d'auteur avec ce login
	// regarder s'il a saisi son nom ou son mail.
	// Ne pas fusionner avec la requete precedente
	// car un nom peut etre homonyme d'un autre login
	else return sql_fetsel('id_auteur,login,alea_actuel,alea_futur,prefs,source,login', 'spip_auteurs',
			"statut<>'5poubelle' AND (" .
			"pass<>'' OR source<>'spip') AND (" . 
			"login<>'' AND (nom=$l OR email=$l))");
}

// Essayer les differentes sources d'authenfication dans l'ordre specifie.
// S'en souvenir dans visiteur_session['auth']

// http://doc.spip.org/@verifier_login
function verifier_login($login, $password, $md5pass="", $md5next="")
{
	foreach ($GLOBALS['liste_des_authentifications'] as $methode) {
		if ($auth = charger_fonction($methode, 'auth')
		AND $auteur = $auth($login, $password, $md5pass, $md5next)) {
			spip_log("connexion de $login par methode $methode");
			$auteur['auth'] = $methode;
			return $auteur;
		}
	}
	return false;
}
?>
