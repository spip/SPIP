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
 * Gestion de l'authentification par SPIP
 *
 * @package SPIP\Core\Authentification\SPIP
 **/
use Spip\Core\Chiffrer;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

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
function auth_spip_dist($login, $pass, $serveur = '', $phpauth = false) {

	// retrouver le login
	$login = auth_spip_retrouver_login($login);
	// login inconnu, n'allons pas plus loin
	if (!$login) {
		return [];
	}

	$md5pass = '';
	$shapass = $shanext = '';

	if ($pass) {
		$row = sql_fetsel(
			'*',
			'spip_auteurs',
			'login=' . sql_quote($login, $serveur, 'text') . " AND statut<>'5poubelle'",
			'',
			'',
			'',
			'',
			$serveur
		);
	}

	// login inexistant ou mot de passe vide
	if (!$pass or !$row) {
		return [];
	}

	include_spip('inc/chiffrer');

	switch ( strlen($row["pass"]) ) {
		case 32:
			// tres anciens mots de passe encodes en md5(alea.pass)
			$md5pass = md5($row['alea_actuel'] . $pass);
			if ($row["pass"] !== $md5pass) {
				unset($row);
			}
			break;
		case 64:
			// anciens mots de passe encodes en sha256(alea.pass)
			include_spip('auth/sha256.inc');
			$shapass = spip_sha256($row['alea_actuel'] . $pass);
			if ($row["pass"] !== $shapass) {
				unset($row);
			}
			break;

			case 60:
		case 98:
		default:
			if (!Chiffrer::verifier_mot_de_passe($pass, $row["pass"])) {
				// doit-on restaurer un backup des cles ?
				if ($row['webmestre'] === 'oui'
					and !empty($row['backup_cles'])) {
					if (Chiffrer::restaurer_cles_depuis_sauvegarde_chiffree($row['backup_cles'], $row['id_auteur'], $pass, $row['pass'])
					and Chiffrer::verifier_mot_de_passe($pass, $row["pass"])) {
						break;
					}
				}
				unset($row);
			}
			break;
	}

	// login/mot de passe incorrect
	if (!$row) {
		return [];
	}

	// fait tourner le codage du pass dans la base
	// sauf si phpauth : cela reviendrait a changer l'alea a chaque hit, et aucune action verifiable par securiser_action()
	if (!$phpauth) {
		include_spip('inc/acces'); // pour creer_uniqid et verifier_htaccess
		$pass_hash_next = Chiffrer::calculer_hash_sale_mot_de_passe($pass, $row['alea_futur']);
		if ($pass_hash_next) {

			$set = [
				'alea_actuel' => 'alea_futur',
				'pass' => sql_quote($pass_hash_next, $serveur, 'text'),
				'alea_futur' => sql_quote(creer_uniqid(), $serveur, 'text')
			];
			// a chaque login de webmestre : sauvegarde chiffree des clé du site (avec les pass du webmestre)
			if ($row['statut'] === '0minirezo' and $row['webmestre'] === 'oui') {
				// TODO : ajouter le champ en base
				//$set['backup_cles'] = Chiffrer::sauvegarde_chiffree_cles($row['id_auteur'], $pass);
			}

			@sql_update(
				'spip_auteurs',
				$set,
				'id_auteur=' . intval($row['id_auteur']) . ' AND pass=' . sql_quote(
					$row['pass'],
					$serveur,
					'text'
				),
				[],
				$serveur
			);

		}

		// En profiter pour verifier la securite de tmp/
		// Si elle ne fonctionne pas a l'installation, prevenir
		if (!verifier_htaccess(_DIR_TMP) and defined('_ECRIRE_INSTALL')) {
			return false;
		}
	}

	return $row;
}

/**
 * Completer le formulaire de login avec le js ou les saisie specifiques a ce mode d'auth
 *
 * @param array $flux
 * @return array
 */
function auth_spip_formulaire_login($flux) {
	// javascript qui gere la securite du login en evitant de faire circuler le pass en clair
	$js = file_get_contents(find_in_path("prive/javascript/login.js"));
	$flux['data'] .=
		  '<script type="text/javascript">/*<![CDATA[*/'
		. "$js\n"
		. "var login_info={'login':'" . $flux['args']['contexte']['var_login'] . "',"
		. "'page_auteur': '" . generer_url_public('informer_auteur') . "',"
		. "'informe_auteur_en_cours':false,"
		. "'attente_informe':0};"
		. "jQuery(function(){jQuery('#var_login').change(actualise_auteur);});"
		. '/*]]>*/</script>';

	return $flux;
}


/**
 * Informer du droit de modifier ou non son login
 *
 * @param string $serveur
 * @return bool
 *   toujours true pour un auteur cree dans SPIP
 */
function auth_spip_autoriser_modifier_login(string $serveur = ''): bool {
	// les fonctions d'ecriture sur base distante sont encore incompletes
	if (strlen($serveur)) {
		return false;
	}
	return true;
}

/**
 * Verification de la validite d'un login pour le mode d'auth concerne
 *
 * @param string $new_login
 * @param int $id_auteur
 *  si auteur existant deja
 * @param string $serveur
 * @return string
 *  message d'erreur si login non valide, chaine vide sinon
 */
function auth_spip_verifier_login($new_login, $id_auteur = 0, $serveur = '') {
	// login et mot de passe
	if (strlen($new_login)) {
		if (strlen($new_login) < _LOGIN_TROP_COURT) {
			return _T('info_login_trop_court_car_pluriel', ['nb' => _LOGIN_TROP_COURT]);
		} else {
			$n = sql_countsel(
				'spip_auteurs',
				'login=' . sql_quote($new_login) . ' AND id_auteur!=' . intval($id_auteur) . " AND statut!='5poubelle'",
				'',
				'',
				$serveur
			);
			if ($n) {
				return _T('info_login_existant');
			}
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
function auth_spip_modifier_login($new_login, $id_auteur, $serveur = '') {
	if (is_null($new_login) or auth_spip_verifier_login($new_login, $id_auteur, $serveur) != '') {
		return false;
	}
	if (
		!$id_auteur = intval($id_auteur)
		or !$auteur = sql_fetsel('login', 'spip_auteurs', 'id_auteur=' . intval($id_auteur), '', '', '', '', $serveur)
	) {
		return false;
	}
	if ($new_login == $auteur['login']) {
		return true;
	} // on a rien fait mais c'est bon !

	include_spip('action/editer_auteur');

	// vider le login des auteurs a la poubelle qui avaient ce meme login
	if (strlen($new_login)) {
		$anciens = sql_allfetsel(
			'id_auteur',
			'spip_auteurs',
			'login=' . sql_quote($new_login, $serveur, 'text') . " AND statut='5poubelle'",
			'',
			'',
			'',
			'',
			$serveur
		);
		while ($row = array_pop($anciens)) {
			auteur_modifier($row['id_auteur'], ['login' => ''], true); // manque la gestion de $serveur
		}
	}

	auteur_modifier($id_auteur, ['login' => $new_login], true); // manque la gestion de $serveur

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
function auth_spip_retrouver_login($login, $serveur = '') {
	if (!strlen($login)) {
		return null;
	} // pas la peine de requeter
	$l = sql_quote($login, $serveur, 'text');
	if (
		$r = sql_getfetsel(
			'login',
			'spip_auteurs',
			"statut<>'5poubelle'" .
			' AND (length(pass)>0)' .
			" AND (login=$l)",
			'',
			'',
			'',
			'',
			$serveur
		)
	) {
		return $r;
	}
	// Si pas d'auteur avec ce login
	// regarder s'il a saisi son nom ou son mail.
	// Ne pas fusionner avec la requete precedente
	// car un nom peut etre homonyme d'un autre login
	else {
		return sql_getfetsel(
			'login',
			'spip_auteurs',
			"statut<>'5poubelle'" .
			' AND (length(pass)>0)' .
			" AND (login<>'' AND (nom=$l OR email=$l))",
			'',
			'',
			'',
			'',
			$serveur
		);
	}
}

/**
 * Informer du droit de modifier ou non le pass
 *
 * @param string $serveur
 * @return bool
 *  toujours true pour un auteur cree dans SPIP
 */
function auth_spip_autoriser_modifier_pass(string $serveur = ''): bool {
	// les fonctions d'ecriture sur base distante sont encore incompletes
	if (strlen($serveur)) {
		return false;
	}
	return true;
}


/**
 * Verification de la validite d'un mot de passe pour le mode d'auth concerne
 * c'est ici que se font eventuellement les verifications de longueur mini/maxi
 * ou de force
 *
 * @param string $login
 *  Le login de l'auteur : permet de verifier que pass et login sont differents
 *  meme a la creation lorsque l'auteur n'existe pas encore
 * @param string $new_pass
 *  Nouveau mot de passe
 * @param int $id_auteur
 *  si auteur existant deja
 * @param string $serveur
 * @return string
 *  message d'erreur si login non valide, chaine vide sinon
 */
function auth_spip_verifier_pass($login, $new_pass, $id_auteur = 0, $serveur = '') {
	// login et mot de passe
	if (strlen($new_pass) < _PASS_LONGUEUR_MINI) {
		return _T('info_passe_trop_court_car_pluriel', ['nb' => _PASS_LONGUEUR_MINI]);
	}

	return '';
}

/**
 * Modifier le mot de passe de l'auteur sur le serveur concerne
 * en s'occupant du hash et companie
 *
 * @param string $login
 * @param string $new_pass
 * @param int $id_auteur
 * @param string $serveur
 * @return bool
 */
function auth_spip_modifier_pass($login, $new_pass, $id_auteur, $serveur = '') {
	if (is_null($new_pass) or auth_spip_verifier_pass($login, $new_pass, $id_auteur, $serveur) != '') {
		return false;
	}

	if (
		!$id_auteur = intval($id_auteur)
		or !sql_fetsel('login', 'spip_auteurs', 'id_auteur=' . intval($id_auteur), '', '', '', '', $serveur)
	) {
		return false;
	}

	$c = [];
	include_spip('inc/acces');
	include_spip('inc/chiffrer');
	$htpass = generer_htpass($new_pass);
	$alea_actuel = creer_uniqid();
	$alea_futur = creer_uniqid();
	$pass = Chiffrer::calculer_hash_sale_mot_de_passe($new_pass, $alea_actuel);
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
 *  all=>true permet de demander la regeneration complete des acces apres operation en base (import, upgrade)
 * @param string $serveur
 * @return void
 */
function auth_spip_synchroniser_distant($id_auteur, $champs, $options = [], string $serveur = ''): void {
	// ne rien faire pour une base distante : on ne sait pas regenerer les htaccess
	if (strlen($serveur)) {
		return;
	}
	// si un login, pass ou statut a ete modifie
	// regenerer les fichier htpass
	if (
		isset($champs['login'])
		or isset($champs['pass'])
		or isset($champs['statut'])
		or (isset($options['all']) and $options['all'])
	) {
		$htaccess = _DIR_RESTREINT . _ACCESS_FILE_NAME;
		$htpasswd = _DIR_TMP . _AUTH_USER_FILE;

		// Cette variable de configuration peut etre posee par un plugin
		// par exemple acces_restreint ;
		// si .htaccess existe, outrepasser spip_meta
		if (
			(!isset($GLOBALS['meta']['creer_htpasswd']) or ($GLOBALS['meta']['creer_htpasswd'] != 'oui'))
			and !@file_exists($htaccess)
		) {
			spip_unlink($htpasswd);
			spip_unlink($htpasswd . '-admin');

			return;
		}

		# remarque : ici on laisse passer les "nouveau" de maniere a leur permettre
		# de devenir redacteur le cas echeant (auth http)... a nettoyer
		// attention, il faut au prealable se connecter a la base (necessaire car utilise par install)

		$p1 = ''; // login:htpass pour tous
		$p2 = ''; // login:htpass pour les admins
		$s = sql_select(
			'login, htpass, statut',
			'spip_auteurs',
			sql_in('statut', ['1comite', '0minirezo', 'nouveau'])
		);
		while ($t = sql_fetch($s)) {
			if (strlen($t['login']) and strlen($t['htpass'])) {
				$p1 .= $t['login'] . ':' . $t['htpass'] . "\n";
				if ($t['statut'] == '0minirezo') {
					$p2 .= $t['login'] . ':' . $t['htpass'] . "\n";
				}
			}
		}
		sql_free($s);
		if ($p1) {
			ecrire_fichier($htpasswd, $p1);
			ecrire_fichier($htpasswd . '-admin', $p2);
			spip_log("Ecriture de $htpasswd et $htpasswd-admin");
		}
	}
}
