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

?>