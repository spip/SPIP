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
 * Gestion de l'authentification par LDAP
 *
 * @package SPIP\Core\Authentification\Ldap
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Authentifie via LDAP et retourne la ligne SQL decrivant l'utilisateur si ok

// Attributs LDAP correspondants a ceux de SPIP, notamment pour le login
// ne pas ecraser une definition perso dans mes_options
if (!isset($GLOBALS['ldap_attributes']) or !is_array($GLOBALS['ldap_attributes'])) {
	$GLOBALS['ldap_attributes'] = array(
		'login' => array('sAMAccountName', 'uid', 'login', 'userid', 'cn', 'sn'),
		'nom' => 'cn',
		'email' => 'mail',
		'bio' => 'description'
	);
}

/**
 * Fonction principale d'authentification du module auth/ldap
 *
 * - On se bind avec le compte generique defini dans config/ldap.php,
 * - On determine le DN de l'utilisateur candidat a l'authentification,
 * - On se re-bind avec ce DN et le mot de passe propose.
 *
 * Si la connexion est autorisee, on renvoie pour enregistrement en session,
 * en plus des champs SQL habituels, les informations de connexion de
 * l'utilisateur (DN et password). Cela permettra de se binder en cours de
 * session sous son identite specifique pour les operations necessitant des
 * privileges particuliers.
 * TODO: Gerer une constante de conf qui permette de choisir entre ce
 *       comportement et tout faire avec le compte generique.
 *
 * @param string $login
 * @param string $pass
 * @param string $serveur
 * @param bool $phpauth
 * @return string
 */
function auth_ldap_dist($login, $pass, $serveur = '', $phpauth = false) {

	#spip_log("ldap $login " . ($pass ? "mdp fourni" : "mdp absent"));

	// Utilisateur connu ?
	// si http auth, inutile de reauthentifier: cela
	// ne marchera pas avec auth http autre que basic.
	$checkpass = isset($_SERVER['REMOTE_USER']) ? false : true;
	if (!($dn = auth_ldap_search($login, $pass, $checkpass, $serveur))) {
		return array();
	}
	$credentials_ldap = array('ldap_dn' => $dn, 'ldap_password' => $pass);

	// Si l'utilisateur figure deja dans la base, y recuperer les infos
	$r = sql_fetsel('*', 'spip_auteurs', 'login=' . sql_quote($login) . " AND source='ldap'", '', '', '', '', $serveur);

	if ($r) {
		return array_merge($r, $credentials_ldap);
	}

	// sinon importer les infos depuis LDAP,

	if ($GLOBALS['meta']['ldap_statut_import']
		and $desc = auth_ldap_retrouver($dn, array(), $serveur)
	) {
		// rajouter le statut indique  a l'install
		$desc['statut'] = $GLOBALS['meta']['ldap_statut_import'];
		$desc['login'] = $login;
		$desc['source'] = 'ldap';
		$desc['pass'] = '';

		$r = sql_insertq('spip_auteurs', $desc, '', $serveur);
	}

	if ($r) {
		return array_merge(
			$credentials_ldap,
			sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . intval($r), '', '', '', '', $serveur)
		);
	}

	// sinon echec
	spip_log("Creation de l'auteur '$login' impossible");

	return array();
}

/**
 * Connexion à l'annuaire LDAP
 *
 * Il faut passer par `spip_connect()` pour avoir les info
 * donc potentiellement indiquer un serveur
 * meme si dans les fait cet argument est toujours vide
 *
 * @param string $serveur
 * @return array
 */
function auth_ldap_connect($serveur = '') {
	include_spip('base/connect_sql');
	static $connexions_ldap = array();
	if (isset($connexions_ldap[$serveur])) {
		return $connexions_ldap[$serveur];
	}
	$connexion = spip_connect($serveur);
	if (!is_array($connexion['ldap'])) {
		if ($connexion['authentification']['ldap']) {
			$f = _DIR_CONNECT . $connexion['authentification']['ldap'];
			unset($GLOBALS['ldap_link']);
			if (is_readable($f)) {
				include_once($f);
			};
			if (isset($GLOBALS['ldap_link'])) {
				$connexion['ldap'] = array(
					'link' => $GLOBALS['ldap_link'],
					'base' => $GLOBALS['ldap_base']
				);
			} else {
				spip_log("connection LDAP $serveur mal definie dans $f");
			}
			if (isset($GLOBALS['ldap_champs'])) {
				$connexion['ldap']['attributes'] = $GLOBALS['ldap_champs'];
			}
		} else {
			spip_log("connection LDAP $serveur inconnue");
		}
	}

	return $connexions_ldap[$serveur] = $connexion['ldap'];
}

/**
 * Retrouver un login, et vérifier son pass si demandé par `$checkpass`
 *
 * @param string $login
 * @param string $pass
 * @param bool $checkpass
 * @param string $serveur
 * @return string
 *    Le login trouvé ou chaine vide si non trouvé
 */
function auth_ldap_search($login, $pass, $checkpass = true, $serveur = '') {
	// Securite anti-injection et contre un serveur LDAP laxiste
	$login_search = preg_replace('/[^-@._\s\d\w]/', '', $login);
	if (!strlen($login_search) or ($checkpass and !strlen($pass))) {
		return '';
	}

	// verifier la connexion
	if (!$ldap = auth_ldap_connect($serveur)) {
		return '';
	}

	$ldap_link = isset($ldap['link']) ? $ldap['link'] : null;
	$ldap_base = isset($ldap['base']) ? $ldap['base'] : null;
	$desc = isset($ldap['attributes']) && $ldap['attributes'] ? $ldap['attributes'] : $GLOBALS['ldap_attributes'] ;

	$logins = is_array($desc['login']) ? $desc['login'] : array($desc['login']);

	// Tenter une recherche pour essayer de retrouver le DN
	foreach ($logins as $att) {
		$result = @ldap_search($ldap_link, $ldap_base, "$att=$login_search", array('dn'));
		$info = @ldap_get_entries($ldap_link, $result);
		// Ne pas accepter les resultats si plus d'une entree
		// (on veut un attribut unique)

		if (is_array($info) and $info['count'] == 1) {
			$dn = $info[0]['dn'];
			if (!$checkpass) {
				return $dn;
			}
			if (@ldap_bind($ldap_link, $dn, $pass)) {
				return $dn;
			}
		}
	}

	if ($checkpass and !isset($dn)) {
		// Si echec, essayer de deviner le DN
		foreach ($logins as $att) {
			$dn = "$att=$login_search, $ldap_base";
			if (@ldap_bind($ldap_link, $dn, $pass)) {
				return "$att=$login_search, $ldap_base";
			}
		}
	}

	return '';
}

/**
 * Retrouver un DN depuis LDAP
 *
 * @param string $dn
 * @param array $desc
 * @param string $serveur
 * @return array
 */
function auth_ldap_retrouver($dn, $desc = array(), $serveur = '') {
	// Lire les infos sur l'utilisateur a partir de son DN depuis LDAP

	if (!$ldap = spip_connect_ldap($serveur)) {
		spip_log("ldap $serveur injoignable");

		return array();
	}

	$ldap_link = $ldap['link'];
	if (!$desc) {
		$desc = $ldap['attributes'] ? $ldap['attributes'] : $GLOBALS['ldap_attributes'];
		unset($desc['login']);
	}
	$result = @ldap_read($ldap_link, $dn, 'objectClass=*', array_values($desc));

	if (!$result) {
		return array();
	}

	// Recuperer les donnees du premier (unique?) compte de l'auteur
	$val = @ldap_get_entries($ldap_link, $result);
	if (!is_array($val) or !is_array($val[0])) {
		return array();
	}
	$val = $val[0];

	// Convertir depuis UTF-8 (jeu de caracteres par defaut)
	include_spip('inc/charsets');

	foreach ($desc as $k => $v) {
		$desc[$k] = importer_charset($val[strtolower($v)][0], 'utf-8');
	}

	return $desc;
}


/**
 * Retrouver le login de quelqu'un qui cherche à se loger
 *
 * @param string $login
 * @param string $serveur
 * @return string
 */
function auth_ldap_retrouver_login($login, $serveur = '') {
	return auth_ldap_search($login, '', false, $serveur) ? $login : '';
}

/**
 * Vérification de la validité d'un mot de passe pour le mode d'auth concerné
 *
 * C'est ici que se font éventuellement les vérifications de longueur mini/maxi
 * ou de force.
 *
 * @param string $login
 *   Le login de l'auteur : permet de vérifier que pass et login sont différents
 *   même à la creation lorsque l'auteur n'existe pas encore
 * @param string $new_pass
 * @param int $id_auteur
 *   Si auteur existant déjà
 * @param string $serveur
 * @return string
 *   Message d'erreur si login non valide, chaîne vide sinon
 */
function auth_ldap_verifier_pass($login, $new_pass, $id_auteur = 0, $serveur = '') {
	include_spip('auth/spip');

	return auth_spip_verifier_pass($login, $new_pass, $id_auteur, $serveur);
}

/**
 * Informer du droit de modifier ou non le pass
 *
 * On ne peut pas détecter à l'avance si l'autorisation sera donnée, il
 * faudra informer l'utilisateur a posteriori si la modif n'a pas pu se
 * faire.
 *
 * @param string $serveur
 * @return bool
 *   Pour un auteur LDAP, a priori toujours true, à conditiion que le serveur
 *   l'autorise: par exemple, pour OpenLDAP il faut avoir dans slapd.conf:
 *   ```
 *    access to attr=userPassword
 *       by self write
 *       ...
 *   ```
 */
function auth_ldap_autoriser_modifier_pass($serveur = '') {
	return true;
}

/**
 * Fonction de modification du mot de passe
 *
 * On se bind au LDAP cette fois sous l'identité de l'utilisateur, car le
 * compte générique defini dans config/ldap.php n'a généralement pas (et
 * ne devrait pas avoir) les droits suffisants pour faire la modification.
 *
 * @param $login
 * @param $new_pass
 * @param $id_auteur
 * @param string $serveur
 * @return bool
 *    Informe du succès ou de l'echec du changement du mot de passe
 */
function auth_ldap_modifier_pass($login, $new_pass, $id_auteur, $serveur = '') {
	if (is_null($new_pass) or auth_ldap_verifier_pass($login, $new_pass, $id_auteur, $serveur) != '') {
		return false;
	}
	if (!$ldap = auth_ldap_connect($serveur)) {
		return '';
	}
	$link = $ldap['link'];
	include_spip('inc/session');
	$dn = session_get('ldap_dn');
	if ('' == $dn) {
		return false;
	}
	if (!ldap_bind($link, $dn, session_get('ldap_password'))) {
		return false;
	}
	$encoded_pass = '{MD5}' . base64_encode(pack('H*', md5($new_pass)));
	$success = ldap_mod_replace($link, $dn, array('userPassword' => $encoded_pass));

	return $success;
}
