<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use Spip\Auth\Alea;
use Spip\Auth\SessionCookie;
use Spip\Auth\SessionFile;

/**
 * Gestion de l'authentification par sessions
 * à utiliser pour valider l'accès (bloquant)
 * ou pour reconnaître un utilisateur (non bloquant)
 *
 * @package SPIP\Core\Session
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


$GLOBALS['visiteur_session'] = []; # globale decrivant l'auteur

/**
 * 3 actions sur les sessions, selon le type de l'argument:
 *
 * - numerique: efface toutes les sessions de l'auteur (retour quelconque)
 * - tableau: cree une session pour l'auteur decrit et retourne l'identifiant
 * - bool: predicat de validite de la session indiquee par le cookie
 *
 * @uses supprimer_sessions()
 * @uses ajouter_session()
 * @uses verifier_session()
 *
 * @param int|array|bool $auteur
 * @return bool|null|void
 */
function inc_session_dist($auteur = false) {
	if (is_numeric($auteur)) {
		return supprimer_sessions($auteur, $auteur > 0);
	} else {
		if (is_array($auteur)) {
			return ajouter_session($auteur);
		} else {
			return verifier_session($auteur);
		}
	}
}


/**
 * Supprimer toutes les vieilles sessions d'un auteur
 *
 * Cette fonction efface toutes les sessions appartenant a l'auteur
 * On en profite pour effacer toutes les sessions
 * creees il y a plus de 4*_RENOUVELLE_ALEA
 * Tenir compte de l'ancien format ou les noms commencaient par "session_"
 * et du meme coup des repertoires plats
 *
 * Attention : id_auteur peut etre negatif (cas des auteurs temporaires pendant le dump)
 *
 * @uses verifier_session()
 * @uses spip_session()
 *
 * @param int $id_auteur
 *     Identifiant d'auteur dont on veut supprimer les sessions
 * @param bool $toutes
 *     Supprimer aussi les vieilles sessions des autres auteurs ?
 * @param bool $actives
 *     false pour ne pas supprimer les sessions valides de $id_auteur.
 *     false revient donc a uniquement supprimer les vieilles sessions !
 */
function supprimer_sessions($id_auteur, $toutes = true, $actives = true) {

	$nb_files = 0;
	$nb_max_files = (defined('_MAX_NB_SESSIONS_OUVERTES') ? _MAX_NB_SESSIONS_OUVERTES : 1000);
	spip_log("supprimer sessions auteur $id_auteur", 'session');
	if ($toutes || $id_auteur !== $GLOBALS['visiteur_session']['id_auteur']) {
		if ($dir = opendir(_DIR_SESSIONS)) {
			$t = $_SERVER['REQUEST_TIME']  - (4 * _RENOUVELLE_ALEA); // 48h par defaut
			$t_short = $_SERVER['REQUEST_TIME']  - max(_RENOUVELLE_ALEA / 4, 3 * 3600); // 3h par defaut
			$t = time() - (4 * _RENOUVELLE_ALEA);
			while (($f = readdir($dir)) !== false) {
				$nb_files++;
				if (preg_match(',^[^\d-]*(-?\d+)_\w{32}\.php[3]?$,', $f, $regs)) {
					$f = _DIR_SESSIONS . $f;
					if ($actives && $regs[1] == $id_auteur || $t > filemtime($f)) {
						spip_unlink($f);
					}
					// si il y a trop de sessions ouvertes, on purge les sessions anonymes de plus de 3H
					// cf http://core.spip.org/issues/3276
					elseif ($nb_files > $nb_max_files && !intval($regs[1]) && $t_short > filemtime($f)) {
						spip_unlink($f);
					}
				}
			}
		}
	} else {
		verifier_session();
		(new SessionFile())->remove();
	}

	// forcer le recalcul de la session courante
	spip_session(true);
}

/**
 * Ajoute une session pour l'auteur décrit par un tableau issu d'un SELECT-SQL
 *
 * @uses spip_php_session_start() Lorsque session anonyme
 * @uses hash_env()
 * @uses preparer_ecriture_session()
 *
 * @param array $auteur
 *     Description de la session de l'auteur. Peut contenir (par exemple)
 *     les clés : id_auteur, nom, login, email, statut, lang, ...
 * @return bool|string
 */
function ajouter_session($auteur) {
	// Si le client a deja une session valide pour son id_auteur
	// on conserve le meme fichier

	// Attention un visiteur peut avoir une session et un id=0,
	// => ne pas melanger les sessions des differents visiteurs
	$id_auteur = intval($auteur['id_auteur'] ?? 0);
	$session_cookie = new SessionCookie();

	// Si ce n'est pas un inscrit (les inscrits ont toujours des choses en session)
	// on va vérifier s'il y a vraiment des choses à écrire
	if (!$id_auteur) {
		// On supprime les données de base pour voir le contenu réel de la session
		$auteur_verif = $auteur;
		if (isset($auteur_verif['id_auteur'])) {
			unset($auteur_verif['id_auteur']);
		}
		if (isset($auteur_verif['hash_env'])) {
			unset($auteur_verif['hash_env']);
		}
		if (isset($auteur_verif['ip_change'])) {
			unset($auteur_verif['ip_change']);
		}
		if (isset($auteur_verif['date_session'])) {
			unset($auteur_verif['date_session']);
		}

		// Les variables vraiment nulle ne sont pas à prendre en compte non plus
		foreach ($auteur_verif as $variable => $valeur) {
			if ($valeur === null) {
				unset($auteur_verif[$variable]);
			}
		}

		// Si après ça la session est vide alors on supprime l'éventuel fichier et on arrête là
		if (!$auteur_verif) {
			if ($cookie = $session_cookie->get()) {
				if (isset($_SESSION[$cookie])) {
					unset($_SESSION[$cookie]);
				}
				unset($_COOKIE['spip_session']);
			}

			return false;
		}
	}

	if ($session_cookie->getUserId() !== $id_auteur) {
		$cookie = $session_cookie->generateValue($id_auteur);
	} else {
		$cookie = $session_cookie->get();
	}

	// Maintenant on sait qu'on a des choses à écrire
	// On s'assure d'avoir au moins ces valeurs
	$auteur['id_auteur'] = $id_auteur;
	if (!isset($auteur['hash_env'])) {
		$auteur['hash_env'] = hash_env();
	}
	if (!isset($auteur['ip_change'])) {
		$auteur['ip_change'] = false;
	}

	if (!isset($auteur['date_session'])) {
		$auteur['date_session'] = time();
	}
	if (
		isset($auteur['prefs'])
		&& is_string($auteur['prefs'])
		&& ($prefs = @unserialize($auteur['prefs']))
	) {
		$auteur['prefs'] = $prefs;
	}

	$fichier_session = '';

	// les sessions anonymes sont stockees dans $_SESSION
	if (!$id_auteur) {
		spip_php_session_start();
		$_SESSION[$cookie] = preparer_ecriture_session($auteur);
	} else {
		$fichier_session = (new SessionFile($session_cookie))->write($auteur);
		// verifier et limiter le nombre maxi de sessions
		// https://core.spip.net/issues/3807
		lister_sessions_auteur($id_auteur);
	}

	// poser le cookie de session SPIP
	include_spip('inc/cookie');
	$duree = definir_duree_cookie_session($auteur);
	$session_cookie->set($cookie,  time() + $duree);
	spip_log("ajoute session $fichier_session cookie $duree", 'session');

	// Si on est admin, poser le cookie de correspondance
	if (!function_exists('autoriser')) {
		include_spip('inc/autoriser');
	}
	if (autoriser('ecrire', '', '', $auteur) && _DUREE_COOKIE_ADMIN) {
		spip_setcookie(
			'spip_admin',
			'@' . ($auteur['email'] ?: $auteur['login']),
			time() + max(_DUREE_COOKIE_ADMIN, $duree),
			httponly: true
		);
	} else {
		// sinon le supprimer ...
		spip_setcookie('spip_admin', '', 1, httponly: true);
	}

	# on en profite pour purger les vieilles sessions anonymes abandonnees
	# supprimer_sessions(0, true, false);

	return $cookie;
}

/**
 * Calcule le temps de validité en seconde du cookie de session
 *
 * Applique un coefficient multiplicateur à la durée de renouvellement de l'alea
 * (noté ensuite `dR`, valant 12h par défaut) pour déterminer la durée du cookie.
 *
 * - `2 * dR`, par défaut
 * - `20 * dR` si le visiteur a indiqué vouloir rester connecté quelques jours
 *    sur le formulaire de login (la clé `cookie` vaut alors `oui`)
 * - `c * dR`, un coeficient défini manuellement si la clé `cookie` est numérique
 *
 * @param array $auteur
 *     Description de l'auteur
 * @return int
 *     Durée en secondes
**/
function definir_duree_cookie_session($auteur) {
	$coef = 2;
	if (isset($auteur['cookie'])) {
		if (is_numeric($auteur['cookie'])) {
			$coef = $auteur['cookie'];
		} else {
			$coef = 20;
		}
	}
	return (int)(_RENOUVELLE_ALEA * $coef);
}

/**
 * Lire le cookie de session et le valider de façon centralisée
 */
function lire_cookie_session(): ?string {
	return (new SessionCookie())->get();
}

/** Annuler le cookie de session */
function effacer_cookie_session(): void {
	(new SessionCookie())->remove();
}

/**
 * Prolonger / Changer la valeur du cookie de session
 *
 * @param string|null $valeur_cookie
 *   nouveau cookie (string), prolonger le cookie existant (null)
 * @param int $expires
 *   timestamp d'expiration
 */
function set_cookie_session(?string $valeur_cookie = null, int $expires = 0): ?string {
	$session_cookie = new SessionCookie();
	if ($valeur_cookie === null) {
		$session_cookie->expires($expires);
	} else {
		$session_cookie->set($valeur_cookie, $expires);
	}

	return $session_cookie->get();
}

/**
 * Vérifie si le cookie spip_session indique une session valide
 *
 * Si oui, la decrit dans le tableau $visiteur_session et retourne id_auteur
 * La rejoue si IP change puis accepte le changement si $change=true
 *
 * Retourne false en cas d'echec, l'id_auteur de la session si defini, null sinon
 *
 * @uses spip_php_session_start() Si session anonyme
 * @uses ajouter_session()
 * @uses hash_env()
 *
 * @param bool $change
 * @return bool|int|null
 */
function verifier_session($change = false) {
	// si pas de cookie, c'est fichu
	$session_file = new SessionFile();
	$cookie = $session_file->cookie->get();
	if (!$cookie) {
		return false;
	}

	$fichier_session = '';
	$id_auteur = $session_file->cookie->getUserId();

	// est-ce une session anonyme ?
	if (!$id_auteur) {
		spip_php_session_start();
		if (!isset($_SESSION[$cookie]) || !is_array($_SESSION[$cookie])) {
			return false;
		}
		$GLOBALS['visiteur_session'] = $_SESSION[$cookie];
	} else {
		// Tester avec alea courant
		$fichier_session = $session_file->getPath(Alea::CURRENT);
		if ($fichier_session && @file_exists($fichier_session)) {
			$GLOBALS['visiteur_session'] = include($fichier_session);
		} else {
			// Sinon, tester avec alea precedent
			$fichier_session = $session_file->getPath(Alea::PREVIOUS);
			if (!$fichier_session || !@file_exists($fichier_session)) {
				return false;
			}

			// Renouveler la session avec l'alea courant
			$GLOBALS['visiteur_session'] = include($fichier_session);
			spip_log('renouvelle session ' . $GLOBALS['visiteur_session']['id_auteur'], 'session');
			spip_unlink($fichier_session);
			ajouter_session($GLOBALS['visiteur_session']);
		}
	}

	// Compatibilite ascendante : auteur_session est visiteur_session si
	// c'est un auteur SPIP authentifie (tandis qu'un visiteur_session peut
	// n'etre qu'identifie, sans aucune authentification).

	if (isset($GLOBALS['visiteur_session']['id_auteur']) && $GLOBALS['visiteur_session']['id_auteur']) {
		$GLOBALS['auteur_session'] = &$GLOBALS['visiteur_session'];
	}


	// Si l'adresse IP change, inc/presentation mettra une balise image
	// avec un URL de rappel demandant a changer le nom de la session.
	// Seul celui qui a l'IP d'origine est rejoue
	// ainsi un eventuel voleur de cookie ne pourrait pas deconnecter
	// sa victime, mais se ferait deconnecter par elle.
	if (hash_env() != $GLOBALS['visiteur_session']['hash_env']) {
		if (!$GLOBALS['visiteur_session']['ip_change']) {
			define('_SESSION_REJOUER', true);
			$GLOBALS['visiteur_session']['ip_change'] = true;
			ajouter_session($GLOBALS['visiteur_session']);
		} else {
			if ($change) {
				spip_log('session non rejouee, vol de cookie ?', 'session');
			}
		}
	} else {
		if ($change) {
			spip_log("rejoue session $fichier_session $cookie", 'session');
			if ($fichier_session) {
				spip_unlink($fichier_session);
			}
			$GLOBALS['visiteur_session']['ip_change'] = false;
			$session_file->cookie->remove();
			ajouter_session($GLOBALS['visiteur_session']);
		}
	}

	// Si la session a ete initiee il y a trop longtemps, elle est annulee
	if (
		isset($GLOBALS['visiteur_session'])
		&& defined('_AGE_SESSION_MAX')
		&& _AGE_SESSION_MAX > 0
		&& time() - @$GLOBALS['visiteur_session']['date_session'] > _AGE_SESSION_MAX
	) {
		unset($GLOBALS['visiteur_session']);

		return false;
	}

	return is_numeric($GLOBALS['visiteur_session']['id_auteur'])
		? $GLOBALS['visiteur_session']['id_auteur']
		: null;
}

/**
 * Lire une valeur dans la session SPIP
 *
 * @api
 * @example `$login = session_get('login');`
 *
 * @param string $nom
 *    Clé dont on souhaite la valeur
 * @return mixed|null
 *     Valeur, si trouvée, `null` sinon.
 */
function session_get($nom) {
	return $GLOBALS['visiteur_session'][$nom] ?? null;
}


/**
 * Ajouter une donnée dans la session SPIP
 *
 * @api
 * @uses ajouter_session()
 * @uses terminer_actualiser_sessions() Ajoute la fonction en fin de hit.
 *
 * @param string $nom
 * @param mixed $val
 * @return void|array
 */
function session_set($nom, $val = null) {
	static $remove = [];
	static $actualiser_sessions = false;
	if ($nom === false) {
		return $remove;
	}
	if (is_null($val)) {
		// rien a faire
		if (!isset($GLOBALS['visiteur_session'][$nom])) {
			return;
		}
		unset($GLOBALS['visiteur_session'][$nom]);
		$remove[] = $nom;
	} else {
		// On ajoute la valeur dans la globale
		$GLOBALS['visiteur_session'][$nom] = $val;
		if ($remove) {
			$remove = array_diff($remove, [$nom]);
		}
	}
	if (!$actualiser_sessions) {
		// il faut creer la session si on en a pas, la premiere fois
		ajouter_session($GLOBALS['visiteur_session']);
		// in register la fonction qui mettra a jour toutes les sessions en fin de hit
		register_shutdown_function('terminer_actualiser_sessions');
		$actualiser_sessions = true;
	}
}

/**
 * En fin de hit, synchroniser toutes les sessions
 *
 * @uses actualiser_sessions()
 */
function terminer_actualiser_sessions() {
	// se remettre dans le dossier de travail au cas ou Apache a change
	chdir(_ROOT_CWD);
	// recuperer les variables a effacer
	$remove = session_set(false);
	// mettre a jour toutes les sessions
	actualiser_sessions($GLOBALS['visiteur_session'], $remove);
}


/**
 * Mettre à jour les sessions existantes pour un auteur
 *
 * Quand on modifie une fiche auteur on appelle cette fonction qui va
 * mettre à jour les fichiers de session de l'auteur en question.
 * (auteurs identifiés seulement)
 *
 * Ne concerne que les sessions des auteurs loges (id_auteur connu)
 *
 * @uses ajouter_session()
 * @uses preg_files()
 * @uses preparer_ecriture_session()
 * @uses ecrire_fichier_session()
 *
 * @param array $auteur
 * @param array $supprimer_cles
 *     Liste des clés à supprimer des tableaux de sessions
 */
function actualiser_sessions($auteur, $supprimer_cles = []) {

	$id_auteur = intval($auteur['id_auteur'] ?? 0);
	$id_auteur_courant = intval($GLOBALS['visiteur_session']['id_auteur'] ?? 0);

	// si l'auteur est celui de la session courante, verifier/creer la session si besoin
	$fichier_session_courante = '';
	if ($id_auteur === $id_auteur_courant) {
		$auteur = [...$GLOBALS['visiteur_session'], ...$auteur];
		ajouter_session($auteur);
		if ($id_auteur) {
			$fichier_session_courante = (new SessionFile())->getPath(Alea::CURRENT);
		}
	}

	// si session anonyme on ne fait rien d'autre ici : les sessions anonymes sont non partagees
	if (!$id_auteur) {
		return;
	}

	// les préférences sont désérialisées, toujours.
	// [fixme] Le champ 'prefs' sert aussi à l’inscription
	if (isset($auteur['prefs']) && is_string($auteur['prefs'])) {
		$auteur['prefs'] = @unserialize($auteur['prefs']);
		if (!is_array($auteur['prefs'])) {
			$auteur['prefs'] = [];
		}
	}

	// .. mettre a jour les sessions de l'auteur cible
	// attention au $ final pour ne pas risquer d'embarquer un .php.jeton temporaire
	// cree par une ecriture concurente d'une session (fichier atomique temporaire)
	$sessions = lister_sessions_auteur($id_auteur);

	// 1ere passe : lire et fusionner les sessions
	foreach ($sessions as $session) {
		// a pu etre supprime entre le preg initial et le moment ou l'on arrive la (concurrence)
		if (
			$session !== $fichier_session_courante
			&& @file_exists($session)
		) {
			$auteur_fichier_session = include $session;
			$auteur = [...$auteur_fichier_session, ...$auteur];
		}
	}

	// supprimer les eventuelles cles dont on ne veut plus
	foreach ($supprimer_cles as $cle) {
		unset($auteur[$cle]);
	}

	$auteur_session = preparer_ecriture_session($auteur);

	// seconde passe : ecrire les sessions qui ne sont pas a jour
	foreach ($sessions as $session) {
		// a pu etre supprime entre le preg initial et le moment ou l'on arrive la (concurrence)
		if (@file_exists($session)) {
			$auteur_fichier_session = include $session;

			// est-ce que cette session est a mettre a jour ?
			if ($auteur_session != $auteur_fichier_session) {
				ecrire_fichier_session($session, $auteur);
			}
		}
	}

	if ($id_auteur === $id_auteur_courant) {
		$GLOBALS['visiteur_session'] = $auteur;
		$GLOBALS['auteur_session'] = &$GLOBALS['visiteur_session'];
	}
}

/**
 * lister les sessions et en verifier le nombre maxi
 * en supprimant les plus anciennes si besoin
 * https://core.spip.net/issues/3807
 *
 * @param int $id_auteur
 * @param int $nb_max
 * @return array
 */
function lister_sessions_auteur($id_auteur, $nb_max = null) {

	if (is_null($nb_max)) {
		if (!defined('_NB_SESSIONS_MAX')) {
			define('_NB_SESSIONS_MAX', 100);
		}
		$nb_max = _NB_SESSIONS_MAX;
	}

	// liste des sessions
	$sessions = preg_files(_DIR_SESSIONS, '/' . $id_auteur . '_.*\.php$');

	// si on en a plus que la limite, supprimer les plus vieilles
	// si ce ne sont pas des sessions anonymes car elles sont alors chacune differentes
	if (
		$id_auteur
		&& count($sessions) > $nb_max
	) {
		// limiter le nombre de sessions ouvertes par un auteur
		// filemtime sur les sessions
		$sessions = array_flip($sessions);

		// 1ere passe : lire les filemtime
		foreach ($sessions as $session => $z) {
			if (
				$d = @filemtime($session)
			) {
				$sessions[$session] = $d;
			} else {
				$sessions[$session] = 0;
			}
		}

		// les plus anciennes en premier
		asort($sessions);

		$sessions = array_keys($sessions);
		while (count($sessions) > $nb_max) {
			$session = array_shift($sessions);
			@unlink($session);
		}
	}

	return $sessions;
}


/**
 * Préparer le tableau de session avant écriture
 *
 * Nettoyage de quelques variables sensibles, et appel d'un pipeline
 *
 * @pipeline preparer_fichier_session
 * @param array $auteur
 * @return array
 */
function preparer_ecriture_session(array $auteur): array {

	$row = $auteur;

	// ne pas enregistrer ces elements de securite dans le fichier de session
	include_spip('inc/auth');
	$auteur = auth_desensibiliser_session($auteur);

	$auteur = pipeline('preparer_fichier_session', ['args' => ['row' => $row], 'data' => $auteur]);

	// ne pas enregistrer les valeurs vraiment nulle dans le fichier
	$auteur = array_filter($auteur, fn ($valeur) => $valeur !== null);

	return $auteur;
}

/**
 * Ecrire le fichier d'une session
 */
function ecrire_fichier_session(string $fichier, array $auteur): bool {
	$auteur = preparer_ecriture_session($auteur);
	$auteur = var_export($auteur, true);
	return ecrire_fichier($fichier, "<?php\nreturn $auteur;\n");
}

/**
 * Calculer le chemin du fichier de session
 */
function chemin_fichier_session(string $alea, string $cookie_session, bool $tantpis = false): string {
	return (new SessionFile())->getPathForName(Alea::from($alea), $cookie_session, $tantpis);
}

/**
 * Calculer chemin du fichier session
 *
 * @deprecated 5.0 Use `(new SessionFile())->getPath(Alea::CURRENT)` or `(new SessionFile())->getPathOrFail(Alea::CURRENT)`
 * @param string $alea
 * @param bool $tantpis
 */
function fichier_session($alea, $tantpis = false): string {
	$session_file = new SessionFile();
	if ($tantpis) {
		return $session_file->getPath(Alea::from($alea));
	}
	return $session_file->getPathOrFail(Alea::from($alea));
}


/**
 * Code à insérer par `inc/presentation` pour rejouer la session
 *
 * @note
 *   Pourquoi insère-t-on le src par js et non directement en statique dans le HTML ?
 *   Historiquement, inséré par une balise `<script>` en r424
 *   puis modifié par `<img>` statique + js en r427
 *
 * @see action_cookie_dist() qui sera appelé
 *
 * @return string
 */
function rejouer_session() {
	return '<img src="' . generer_url_action('cookie', 'change_session=oui', true) . '" width="0" height="0" alt="" />';
}


/**
 * On verifie l'IP et le nom du navigateur
 *
 * @return string
 */
function hash_env() {
	static $res = '';
	if ($res) {
		return $res;
	}

	return $res = md5($GLOBALS['ip'] . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
}


/**
 * Démarre une session PHP si ce n'est pas déjà fait.
 *
 * @link http://php.net/manual/fr/function.session-start.php
 * @uses is_php_session_started()
 * @return bool True si une session PHP est ouverte.
 **/
function spip_php_session_start() {
	if (!is_php_session_started()) {
		return session_start();
	}

	return true;
}

/**
 * Indique si une sesssion PHP est active
 *
 * @link http://php.net/manual/fr/function.session-status.php#113468
 * @return bool true si une session PHP est active
 **/
function is_php_session_started() {
	if (php_sapi_name() !== 'cli') {
		return session_status() === PHP_SESSION_ACTIVE ? true : false;
	}

	return false;
}
