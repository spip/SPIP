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

/**
 * Authentifie et si ok retourne le tableau de la ligne SQL de l'utilisateur
 * Si risque de secu repere a l'installation retourne False
 *
 * @param string $login
 * @param string $pass
 * @param string $serveur
 * @param bool $phpauth
 * @return array|bool
 */
function auth_spip_dist ($login, $pass, $serveur='', $phpauth=false) {

	// retrouver le login
	$login = auth_spip_retrouver_login($login);
	// login inconnu, n'allons pas plus loin
	if (!$login) return array();

	$md5pass = "";
	$shapass = $shanext = "";

	if (preg_match(",^\{([0-9a-f]{64});([0-9a-f]{64})\}$,i",$pass,$regs)){
		$shapass = $regs[1];
		$shanext = $regs[2];
	}
	// compat avec une base mixte md5/sha256 : le js a envoye les 2 hash
	elseif (preg_match(",^\{([0-9a-f]{64});([0-9a-f]{64});([0-9a-f]{32});([0-9a-f]{32})\}$,i",$pass,$regs)){
		$shapass = $regs[1];
		$shanext = $regs[2];
		$md5pass = $regs[3];
		//$md5next = $regs[4];
	}

	// si envoi non crypte, crypter maintenant
	elseif ($pass) {
		$row = sql_fetsel("alea_actuel, alea_futur", "spip_auteurs", "login=" . sql_quote($login,$serveur,'text'),'','','','',$serveur);

		if ($row) {
			include_spip('auth/sha256.inc');
			$shapass = _nano_sha256($row['alea_actuel'] . $pass);
			$shanext = _nano_sha256($row['alea_futur'] . $pass);
			$md5pass = md5($row['alea_actuel'] . $pass);
		}
	}

	// login inexistant ou mot de passe vide
	if (!$shapass AND !$md5pass) return array();

	$row = sql_fetsel("*", "spip_auteurs", "login=" . sql_quote($login,$serveur,'text') . " AND pass=" . sql_quote($shapass,$serveur,'text') . " AND statut<>'5poubelle'",'','','','',$serveur);

	// compat avec les anciennes bases en md5
	if (!$row AND $md5pass)
		$row = sql_fetsel("*", "spip_auteurs", "login=" . sql_quote($login,$serveur,'text') . " AND pass=" . sql_quote($md5pass,$serveur,'text') . " AND statut<>'5poubelle'",'','','','',$serveur);

	// login/mot de passe incorrect
	if (!$row) return array();

	// fait tourner le codage du pass dans la base
	// sauf si phpauth : cela reviendrait a changer l'alea a chaque hit, et aucune action verifiable par securiser_action()
	if ($shanext AND !$phpauth) {

		include_spip('inc/acces'); // pour creer_uniqid
		@sql_update('spip_auteurs', array('alea_actuel' => 'alea_futur', 'pass' => sql_quote($shanext,$serveur,'text'), 'alea_futur' => sql_quote(creer_uniqid(),$serveur,'text')), "id_auteur=" . $row['id_auteur'].' AND pass IN ('.sql_quote($shapass,$serveur,'text').', '.sql_quote($md5pass,$serveur,'text').')','',$serveur);
		// En profiter pour verifier la securite de tmp/
		// Si elle ne fonctionne pas a l'installation, prevenir
		if (!verifier_htaccess(_DIR_TMP) AND defined('_ECRIRE_INSTALL'))
			return false;
	}
	return $row;
}

/**
 * Completer le formulaire de login avec le js ou les saisie specifiques a ce mode d'auth
 *
 * @param array $flux
 * @return array
 */
function auth_spip_formulaire_login($flux){
	// faut il encore envoyer md5 ?
	// on regarde si il reste des pass md5 en base pour des auteurs en statut pas poubelle
	// les hash md5 ont une longueur 32, les sha 64
	$compat_md5 = sql_countsel("spip_auteurs", "length(pass)=32 AND statut<>'poubelle'");

	// javascript qui gere la securite du login en evitant de faire circuler le pass en clair
	$flux['data'].=
	  ($compat_md5?'<script type="text/javascript" src="'._DIR_JAVASCRIPT.'md5.js"></script>':'')
		.'<script type="text/javascript" src="'._DIR_JAVASCRIPT.'login-sha-min.js"></script>'
		.'<script type="text/javascript">/*<![CDATA[*/'
		."var alea_actuel='".$flux['args']['contexte']['_alea_actuel']."';"
		."var alea_futur='".$flux['args']['contexte']['_alea_futur']."';"
		."var login='".$flux['args']['contexte']['var_login']."';"
		."var page_auteur = '".generer_url_public('informer_auteur')."';"
		."var informe_auteur_en_cours = false;"
		."var attente_informe = 0;"
		."var compat_md5 = ".($compat_md5?"true;":"false;")
		."(function($){
		$('#password')
			.after(\"<em id='pass_securise'><img src='".chemin_image('cadenas-16.png')."' width='16' height='16' alt='" . attribut_html(_T('login_securise')) . "' title='" . attribut_html(_T('login_securise')) . "' \/><\/em>\");
		affiche_login_secure();
		$('#var_login').change(actualise_auteur);
		$('form#formulaire_login').submit(login_submit);
	}(jQuery));"
		."/*]]>*/</script>";

	return $flux;
}


/**
 * Informer du droit de modifier ou non son login
 * @param string $serveur
 * @return bool
 *	 toujours true pour un auteur cree dans SPIP
 */
function auth_spip_autoriser_modifier_login($serveur=''){
	if (strlen($serveur))
		return false; // les fonctions d'ecriture sur base distante sont encore incompletes
	return true;
}

/**
 * Verification de la validite d'un login pour le mode d'auth concerne
 *
 * @param string $new_login
 * @param int $id_auteur
 *	si auteur existant deja
 * @param string $serveur
 * @return string
 *	message d'erreur si login non valide, chaine vide sinon
 */
function auth_spip_verifier_login($new_login, $id_auteur=0, $serveur=''){
	// login et mot de passe
	if (strlen($new_login)){
		if (strlen($new_login) < _LOGIN_TROP_COURT)
			return _T('info_login_trop_court_car_pluriel',array('nb'=>_LOGIN_TROP_COURT));
		else {
			$n = sql_countsel('spip_auteurs', "login=" . sql_quote($new_login) . " AND id_auteur!=".intval($id_auteur)." AND statut!='5poubelle'",'','',$serveur);
			if ($n)
				return _T('info_login_existant');
		}
	}
	return '';
}

/**
 * Modifier le login d'un auteur SPIP
 *
 * @param string $new_login
 * @param int $id_auteur
 * @param string $serveur
 * @return bool
 */
function auth_spip_modifier_login($new_login, $id_auteur, $serveur=''){
	if (is_null($new_login) OR auth_spip_verifier_login($new_login,$id_auteur,$serveur)!='')
		return false;
	if (!$id_auteur = intval($id_auteur)
		OR !$auteur = sql_fetsel('login','spip_auteurs','id_auteur='.intval($id_auteur),'','','','',$serveur))
		return false;
	if ($new_login == $auteur['login'])
		return true; // on a rien fait mais c'est bon !

	include_spip('action/editer_auteur');

	// vider le login des auteurs a la poubelle qui avaient ce meme login
	if (strlen($new_login)){
		$anciens = sql_allfetsel('id_auteur','spip_auteurs','login='.sql_quote($new_login,$serveur,'text')." AND statut='5poubelle'",'','','','',$serveur);
		while ($row = array_pop($anciens)){
			auteur_modifier($row['id_auteur'], array('login'=>''), true); // manque la gestion de $serveur
		}
	}

	auteur_modifier($id_auteur, array('login'=>$new_login), true); // manque la gestion de $serveur

	return true;
}

/**
 * Retrouver le login de quelqu'un qui cherche a se loger
 * Reconnaitre aussi ceux qui donnent leur nom ou email au lieu du login
 *
 * @param string $login
 * @param string $serveur
 * @return string
 */
function auth_spip_retrouver_login($login, $serveur=''){
	if (!strlen($login)) return null; // pas la peine de requeter
	$l = sql_quote($login,$serveur,'text');
	if ($r = sql_getfetsel('login', 'spip_auteurs',
			"statut<>'5poubelle'" .
			" AND (length(pass)>0)" .
			" AND (login=$l)",'','','','',$serveur))
		return $r;
	// Si pas d'auteur avec ce login
	// regarder s'il a saisi son nom ou son mail.
	// Ne pas fusionner avec la requete precedente
	// car un nom peut etre homonyme d'un autre login
	else return sql_getfetsel('login', 'spip_auteurs',
			"statut<>'5poubelle'" .
			" AND (length(pass)>0)" .
			" AND (login<>'' AND (nom=$l OR email=$l))",'','','','',$serveur);
}


/**
 * informer sur un login
 * Ce dernier transmet le tableau ci-dessous a la fonction JS informer_auteur
 * Il est invoque par la fonction JS actualise_auteur via la globale JS
 * page_auteur=#URL_PAGE{informer_auteur} dans le squelette login
 * N'y aurait-il pas plus simple ?
 *
 * @param array $infos
 * @param array $row
 * @param string $serveur
 * @return array
 */
function auth_spip_informer_login($infos, $row, $serveur=''){

	// pour la methode SPIP on a besoin des alea en plus pour encoder le pass avec
	$infos['alea_actuel'] = $row['alea_actuel'];
	$infos['alea_futur'] = $row['alea_futur'];

	return $infos;
}

/**
 * Informer du droit de modifier ou non le pass
 * @param string $serveur
 * @return bool
 *	toujours true pour un auteur cree dans SPIP
 */
function auth_spip_autoriser_modifier_pass($serveur=''){
	if (strlen($serveur))
		return false; // les fonctions d'ecriture sur base distante sont encore incompletes
	return true;
}


/**
 * Verification de la validite d'un mot de passe pour le mode d'auth concerne
 * c'est ici que se font eventuellement les verifications de longueur mini/maxi
 * ou de force
 *
 * @param string $login
 *	Le login de l'auteur : permet de verifier que pass et login sont differents
 *  meme a la creation lorsque l'auteur n'existe pas encore
 * @param string $new_pass
 * 	Nouveau mot de passe
 * @param int $id_auteur
 *	si auteur existant deja
 * @param string $serveur
 * @return string
 *	message d'erreur si login non valide, chaine vide sinon
 */
function auth_spip_verifier_pass($login, $new_pass, $id_auteur=0, $serveur=''){
	// login et mot de passe
	if (strlen($new_pass) < _PASS_LONGUEUR_MINI)
		return _T('info_passe_trop_court_car_pluriel',array('nb'=>_PASS_LONGUEUR_MINI));

	return '';
}

/**
 * Modifier le mot de passe de l'auteur sur le serveur concerne
 * en s'occupant du hash et companie
 * @param string $login
 * @param string $new_pass
 * @param int $id_auteur
 * @param string $serveur
 * @return bool
 */
function auth_spip_modifier_pass($login, $new_pass, $id_auteur, $serveur=''){
	if (is_null($new_pass) OR auth_spip_verifier_pass($login, $new_pass,$id_auteur,$serveur)!='')
		return false;

	if (!$id_auteur = intval($id_auteur)
		OR !sql_fetsel('login','spip_auteurs','id_auteur='.intval($id_auteur),'','','','',$serveur))
		return false;

	$c = array();
	include_spip('inc/acces');
	include_spip('auth/sha256.inc');
	$htpass = generer_htpass($new_pass);
	$alea_actuel = creer_uniqid();
	$alea_futur = creer_uniqid();
	$pass = _nano_sha256($alea_actuel.$new_pass);
	$c['pass'] = $pass;
	$c['htpass'] = $htpass;
	$c['alea_actuel'] = $alea_actuel;
	$c['alea_futur'] = $alea_futur;
	$c['low_sec'] = '';

	include_spip('action/editer_auteur');
	auteur_modifier($id_auteur, $c, true); // manque la gestion de $serveur

	return true; // on a bien modifie le pass
}

/**
 * Synchroniser les fichiers htpasswd
 *
 * @param int $id_auteur
 * @param array $champs
 * @param array $options
 *	all=>true permet de demander la regeneration complete des acces apres operation en base (import, upgrade)
 * @param string $serveur
 * @return void
 */
function auth_spip_synchroniser_distant($id_auteur, $champs, $options = array(), $serveur=''){
	// ne rien faire pour une base distante : on ne sait pas regenerer les htaccess
	if (strlen($serveur))
		return;
	// si un login, pass ou statut a ete modifie
	// regenerer les fichier htpass
	if (isset($champs['login'])
		OR isset($champs['pass'])
		OR isset($champs['statut'])
		OR (isset($options['all']) AND $options['all'])
	) {

		$htaccess = _DIR_RESTREINT . _ACCESS_FILE_NAME;
		$htpasswd = _DIR_TMP . _AUTH_USER_FILE;

		// Cette variable de configuration peut etre posee par un plugin
		// par exemple acces_restreint ;
		// si .htaccess existe, outrepasser spip_meta
		if (($GLOBALS['meta']['creer_htpasswd'] != 'oui')
		AND !@file_exists($htaccess)) {
			spip_unlink($htpasswd);
			spip_unlink($htpasswd."-admin");
			return;
		}

		# remarque : ici on laisse passer les "nouveau" de maniere a leur permettre
		# de devenir redacteur le cas echeant (auth http)... a nettoyer
		// attention, il faut au prealable se connecter a la base (necessaire car utilise par install)

		$p1 = ''; // login:htpass pour tous
		$p2 = ''; // login:htpass pour les admins
		$s = sql_select("login, htpass, statut", "spip_auteurs", sql_in("statut",  array('1comite','0minirezo','nouveau')));
		while ($t = sql_fetch($s)) {
			if (strlen($t['login']) AND strlen($t['htpass'])) {
				$p1 .= $t['login'].':'.$t['htpass']."\n";
				if ($t['statut'] == '0minirezo')
					$p2 .= $t['login'].':'.$t['htpass']."\n";
			}
		}
		sql_free($s);
		if ($p1) {
			ecrire_fichier($htpasswd, $p1);
			ecrire_fichier($htpasswd.'-admin', $p2);
			spip_log("Ecriture de $htpasswd et $htpasswd-admin");
		}
	}
}

?>
