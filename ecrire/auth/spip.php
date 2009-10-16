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

if (!defined("_ECRIRE_INC_VERSION")) return;

// Authentifie et retourne la ligne SQL decrivant l'utilisateur si ok
function auth_spip_dist ($login, $pass, $md5pass="", $md5next="") {

	// retrouver le login
	$login = auth_spip_retrouver_login($login);

  // si envoi non crypte, crypter maintenant
	if (!$md5pass AND $pass) {
		$row = sql_fetsel("alea_actuel, alea_futur", "spip_auteurs", "login=" . sql_quote($login));

		if ($row) {
			$md5pass = md5($row['alea_actuel'] . $pass);
			$md5next = md5($row['alea_futur'] . $pass);
		}
	}
	// login inexistant ou mot de passe vide
	if (!$md5pass) return array();

	$row = sql_fetsel("*", "spip_auteurs", "login=" . sql_quote($login) . " AND pass=" . sql_quote($md5pass) . " AND statut<>'5poubelle'");

	// login/mot de passe incorrect
	if (!$row) return array(); 

	if ($row['statut'] == 'nouveau') {
		include_spip('inc/auth');
		$row['statut'] = acces_statut($row['id_auteur'], $row['statut'], $row['bio']);
	}

	// fait tourner le codage du pass dans la base
	if ($md5next) {
		include_spip('inc/acces'); // pour creer_uniqid
		@sql_update('spip_auteurs', array('alea_actuel' => 'alea_futur', 'pass' => sql_quote($md5next), 'alea_futur' => sql_quote(creer_uniqid())), "id_auteur=" . $row['id_auteur']);
		// En profiter pour verifier la securite de tmp/
		verifier_htaccess(_DIR_TMP);
	}
	return $row;
}

function auth_spip_formulaire_login($flux){
	// javascript qui gere la securite du login en evitant de faire circuler le pass en clair
	$flux['data'].=
		'<script type="text/javascript" src="'._DIR_JAVASCRIPT.'md5.js"></script>'
		.'<script type="text/javascript" src="'._DIR_JAVASCRIPT.'login.js"></script>'
		.'<script type="text/javascript">/*<![CDATA[*/'
		."var alea_actuel='".$flux['args']['_alea_actuel']."';"
		."var alea_futur='".$flux['args']['_alea_futur']."';"
		."var login='".$flux['args']['var_login']."';"
		."var page_auteur = '".generer_url_public('informer_auteur')."';"
		."var informe_auteur_en_cours = false;"
		."var attente_informe = 0;"
		."(function($){
		$('#password')
			.after(\"<em id='pass_securise'><img src='"._DIR_IMG_PACK."securise.gif' width='16' height='16' alt='<:login_securise:>' title='<:login_securise:>' \/><\/em>\");
		affiche_login_secure();
		$('#var_login').change(actualise_auteur);
		$('form#formulaire_login').submit(login_submit);
	}(jQuery));"
		."/*]]>*/</script>";

	return $flux;
}


/**
 * Informer du droit de modifier ou non son login
 * @return bool
 *	toujours true pour un auteur cree dans SPIP
 */
function auth_spip_autoriser_modifier_login(){
	return true;
}

/**
 * Verification de la validite d'un login pour le mode d'auth concerne
 * 
 * @param string $new_login
 * @param int $id_auteur
 *	si auteur existant deja
 * @return string
 *	message d'erreur si login non valide, chaine vide sinon
 */
function auth_spip_verifier_login($new_login,$id_auteur=0){
	// login et mot de passe
	if (strlen($login)){
		if (strlen($new_login) < _LOGIN_TROP_COURT)
			return 'info_login_trop_court';
		else {
			$n = sql_countsel('spip_auteurs', "login=" . sql_quote($new_login) . " AND id_auteur!=".intval($id_auteur)." AND statut!='5poubelle'");
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
 * @return bool
 */
function auth_spip_modifier_login($new_login,$id_auteur){
	if (is_null($new_login) OR auth_spip_verifier_login($new_login,$id_auteur)!='')
		return false;
	if (!$id_auteur = intval($id_auteur)
		OR !$auteur = sql_fetsel('login','spip_auteurs','id_auteur='.intval($id_auteur)))
		return false;
	if ($new_login == $auteur['login'])
		return true; // on a rien fait mais c'est bon !

	// vider le login des auteurs a la poubelle qui avaient ce meme login
	if (strlen($new_login)){
		$anciens = sql_select('id_auteur','spip_auteurs','login='.sql_quote($new_login)." AND statut='5poubelle'");
		while ($row = sql_fetch($anciens)){
			auth_spip_login_changer('',$row['id_auteur']);
		}
	}

	include_spip('inc/modifier');
	revision_auteur($id_auteur, array('login'=>$new_login));

	return true;
}

/**
 * Retrouver le login de quelqu'un qui cherche a se loger
 * Reconnaitre aussi ceux qui donnent leur nom ou email au lieu du login
 *
 * @param string $login
 * @return string
 */
function auth_spip_retrouver_login($login){
	$l = sql_quote($login);
	if ($r = sql_getfetsel('login', 'spip_auteurs',
			"statut<>'5poubelle'" .
			" AND (length(pass)>0)" .
			" AND (login=$l)"))
		return $r;
	// Si pas d'auteur avec ce login
	// regarder s'il a saisi son nom ou son mail.
	// Ne pas fusionner avec la requete precedente
	// car un nom peut etre homonyme d'un autre login
	else return sql_getfetsel('login', 'spip_auteurs',
			"statut<>'5poubelle'" .
			" AND (length(pass)>0)" .
			" AND (login<>'' AND (nom=$l OR email=$l))");
}


/**
 * Informer du droit de modifier ou non le pass
 * @return bool
 *	toujours true pour un auteur cree dans SPIP
 */
function auth_spip_autoriser_modifier_pass(){
	return true;
}


/**
 * Verification de la validite d'un mot de passe pour le mode d'auth concerne
 * c'est ici que se font eventuellement les verifications de longueur mini/maxi
 * ou de force
 *
 * @param string $new_pass
 * @param string $login
 *	le login de l'auteur : permet de verifier que pass et login sont differents
 *  meme a la creation lorsque l'auteur n'existe pas encore
 * @param int $id_auteur
 *	si auteur existant deja
 * @return string
 *	message d'erreur si login non valide, chaine vide sinon
 */
function auth_spip_verifier_pass($login, $new_pass, $id_auteur=0){
	// login et mot de passe
	if (strlen($new_pass) < 6)
		return _T('info_passe_trop_court');
	
	return '';
}

function auth_spip_modifier_pass($login, $new_pass, $id_auteur){
	if (is_null($new_pass) OR auth_spip_verifier_pass($login, $new_pass,$id_auteur)!='')
		return false;

	if (!$id_auteur = intval($id_auteur)
		OR !$auteur = sql_fetsel('login','spip_auteurs','id_auteur='.intval($id_auteur)))
		return false;

	$c = array();
	include_spip('inc/acces');
	$htpass = generer_htpass($new_pass);
	$alea_actuel = creer_uniqid();
	$alea_futur = creer_uniqid();
	$pass = md5($alea_actuel.$new_pass);
	$c['pass'] = $pass;
	$c['htpass'] = $htpass;
	$c['alea_actuel'] = $alea_actuel;
	$c['alea_futur'] = $alea_futur;
	$c['low_sec'] = '';

	include_spip('inc/modifier');
	revision_auteur($id_auteur, $c);

}

/**
 * Synchroniser les fichiers htpasswd
 * 
 * @param int $id_auteur
 * @param array $champs
 * @param array $options
 *	all=>true permet de demander la regeneration complete des acces apres operation en base (import, upgrade)
 * @return void 
 */
function auth_spip_synchroniser_distant($id_auteur, $champs, $options = array()){
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
		if ($p1) {
			ecrire_fichier($htpasswd, $p1);
			ecrire_fichier($htpasswd.'-admin', $p2);
			spip_log("Ecriture de $htpasswd et $htpasswd-admin");
		}
	}
}

?>