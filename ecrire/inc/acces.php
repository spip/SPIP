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
 * Gestion des nombres aléatoires et de certains accès au site
 *
 * @package SPIP\Core\Authentification
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Créer un mot de passe
 *
 * @param int $longueur
 *     Longueur du password créé
 * @param string $sel
 *     Clé pour un salage supplémentaire
 * @return string
 *     Mot de passe
 **/
function creer_pass_aleatoire($longueur = 16, $sel = '') {
	$seed = (int)round(((float)microtime() + 1) * time());

	mt_srand($seed);
	srand($seed);
	$s = '';
	$pass = '';
	for ($i = 0; $i < $longueur; $i++) {
		if (!$s) {
			$s = mt_rand();
			if (!$s) {
				$s = rand();
			}
			$s = substr(md5(uniqid($s) . $sel), 0, 16);
		}
		$r = unpack('Cr', pack('H2', $s . $s));
		$x = $r['r'] & 63;
		if ($x < 10) {
			$x = chr($x + 48);
		} else {
			if ($x < 36) {
				$x = chr($x + 55);
			} else {
				if ($x < 62) {
					$x = chr($x + 61);
				} else {
					if ($x == 63) {
						$x = '/';
					} else {
						$x = '.';
					}
				}
			}
		}
		$pass .= $x;
		$s = substr($s, 2);
	}
	$pass = preg_replace('@[./]@', 'a', $pass);
	$pass = preg_replace('@[I1l]@', 'L', $pass);
	$pass = preg_replace('@[0O]@', 'o', $pass);

	return $pass;
}

/**
 * Créer un identifiant aléatoire
 *
 * @return string Identifiant
 */
function creer_uniqid() {
	static $seeded;

	if (!$seeded) {
		$seed = (int)round(((float)microtime() + 1) * time());
		mt_srand($seed);
		srand($seed);
		$seeded = true;
	}

	$s = mt_rand();
	if (!$s) {
		$s = rand();
	}

	return uniqid($s, 1);
}

/**
 * Charge les aléas ehpémères s'il ne sont pas encore dans la globale
 *
 * Si les métas 'alea_ephemere' et 'alea_ephemere_ancien' se sont pas encore chargées
 * en méta (car elles ne sont pas stockées, pour sécurité, dans le fichier cache des métas),
 * alors on les récupère en base. Et on les ajoute à nos métas globales.
 *
 * @see touch_meta()
 * @return string Retourne l'alea éphemère actuel au passage
 */
function charger_aleas() {
	if (!isset($GLOBALS['meta']['alea_ephemere'])) {
		include_spip('base/abstract_sql');
		$aleas = sql_allfetsel(
			array('nom', 'valeur'),
			'spip_meta',
			sql_in('nom', array('alea_ephemere', 'alea_ephemere_ancien')),
			'',
			'',
			'',
			'',
			'',
			'continue'
		);
		if ($aleas) {
			foreach ($aleas as $a) {
				$GLOBALS['meta'][$a['nom']] = $a['valeur'];
			}
			return $GLOBALS['meta']['alea_ephemere'];
		} else {
			spip_log('aleas indisponibles', 'session');
			return '';
		}
	}
	return $GLOBALS['meta']['alea_ephemere'];
}

/**
 * Renouveller l'alea (utilisé pour sécuriser les scripts du répertoire `action/`)
 **/
function renouvelle_alea() {
	charger_aleas();
	ecrire_meta('alea_ephemere_ancien', @$GLOBALS['meta']['alea_ephemere'], 'non');
	$GLOBALS['meta']['alea_ephemere'] = md5(creer_uniqid());
	ecrire_meta('alea_ephemere', $GLOBALS['meta']['alea_ephemere'], 'non');
	ecrire_meta('alea_ephemere_date', time(), 'non');
	spip_log("renouvellement de l'alea_ephemere");
}


/**
 * Retourne une clé de sécurité faible (low_sec) pour l'auteur indiqué
 *
 * low-security est un ensemble de fonctions pour gérer de l'identification
 * faible via les URLs (suivi RSS, iCal...)
 *
 * Retourne la clé de sécurité low_sec de l'auteur (la génère si elle n'exite pas)
 * ou la clé de sécurité low_sec du site (si auteur invalide)(la génère si elle
 * n'existe pas).
 *
 * @param int $id_auteur
 *     Identifiant de l'auteur
 * @return string
 *     Clé de sécurité.
 **/
function low_sec($id_auteur) {
	// Pas d'id_auteur : low_sec
	if (!$id_auteur = intval($id_auteur)) {
		include_spip('inc/config');
		if (!$low_sec = lire_config('low_sec')) {
			ecrire_meta('low_sec', $low_sec = creer_pass_aleatoire());
		}
	} else {
		$low_sec = sql_getfetsel('low_sec', 'spip_auteurs', 'id_auteur = '.intval($id_auteur));
		if (!$low_sec) {
			$low_sec = creer_pass_aleatoire();
			sql_updateq('spip_auteurs', array('low_sec' => $low_sec), 'id_auteur = '.intval($id_auteur));
		}
	}

	return $low_sec;
}

/**
 * Inclure les arguments significatifs pour le hachage
 *
 * Cas particulier du statut pour compatibilité ancien rss/suivi_revisions
 *
 * @param string $op
 * @param array $args
 * @param string $lang
 * @param string $mime
 *     Par défaut 'rss'.
 * @return string
 */
function param_low_sec($op, $args = array(), $lang = '', $mime = 'rss') {
	$a = $b = '';
	foreach ($args as $val => $var) {
		if ($var) {
			if ($val <> 'statut') {
				$a .= ':' . $val . '-' . $var;
			}
			$b .= $val . '=' . $var . '&';
		}
	}
	$a = substr($a, 1);
	$id = intval(@$GLOBALS['connect_id_auteur']);

	return $b
	. 'op='
	. $op
	. '&id='
	. $id
	. '&cle='
	. afficher_low_sec($id, "$mime $op $a")
	. (!$a ? '' : "&args=$a")
	. (!$lang ? '' : "&lang=$lang");
}

/**
 * Retourne une clé basée sur le low_sec de l'auteur et l'action demandé
 *
 * @uses low_sec()
 *
 * @param int $id_auteur
 *     Identifiant de l'auteur
 * @param string $action
 *     Action désirée
 * @return string
 *     Clé
 **/
function afficher_low_sec($id_auteur, $action = '') {
	return substr(md5($action . low_sec($id_auteur)), 0, 8);
}

/**
 * Vérifie une clé basée sur le low_sec de l'auteur et l'action demandé
 *
 * @uses afficher_low_sec()
 *
 * @param int $id_auteur
 *     Identifiant de l'auteur
 * @param string $cle
 *     Clé à comparer
 * @param string $action
 *     Action désirée
 * @return bool
 *     true si les clés corresponde, false sinon
 **/
function verifier_low_sec($id_auteur, $cle, $action = '') {
	return ($cle == afficher_low_sec($id_auteur, $action));
}

/**
 * Efface la clé de sécurité faible (low_sec) d'un auteur
 *
 * @param int $id_auteur
 *     Identifiant de l'auteur
 **/
function effacer_low_sec($id_auteur) {
	if (!$id_auteur = intval($id_auteur)) {
		return;
	} // jamais trop prudent ;)
	sql_updateq('spip_auteurs', array('low_sec' => ''), 'id_auteur = '.intval($id_auteur));
}

/**
 * Initialiser la globale htsalt si cela n'a pas déjà été fait.
 *
 * @return void|bool
 */
function initialiser_sel() {
	if (!isset($GLOBALS['htsalt'])) {
		if (CRYPT_MD5) {
			$GLOBALS['htsalt'] = '$1$' . creer_pass_aleatoire();
		} else {
			$GLOBALS['htsalt'] = '';
		}
	}
	return $GLOBALS['htsalt'];
}

/**
 * Créer un fichier htpasswd
 *
 * Cette fonction ne sert qu'à la connexion en mode http_auth.non LDAP.
 * Voir le plugin «Accès Restreint»
 *
 * S'appuie sur la meta `creer_htpasswd` pour savoir s'il faut créer
 * le `.htpasswd`.
 *
 * @return null|void
 *     - null si pas de htpasswd à créer, ou si LDAP
 *     - void sinon.
 **/
function ecrire_acces() {
	$htaccess = _DIR_RESTREINT . _ACCESS_FILE_NAME;
	$htpasswd = _DIR_TMP . _AUTH_USER_FILE;

	// Cette variable de configuration peut etre posee par un plugin
	// par exemple acces_restreint ;
	// si .htaccess existe, outrepasser spip_meta
	if ((!isset($GLOBALS['meta']['creer_htpasswd'])
			or ($GLOBALS['meta']['creer_htpasswd'] != 'oui'))
		and !@file_exists($htaccess)
	) {
		spip_unlink($htpasswd);
		spip_unlink($htpasswd . '-admin');
		return;
	}

	# remarque : ici on laisse passer les "nouveau" de maniere a leur permettre
	# de devenir redacteur le cas echeant (auth http)... a nettoyer
	// attention, il faut au prealable se connecter a la base (necessaire car utilise par install)
	// TODO: factoriser avec auth/spip qui fait deja ce job et generaliser le test spip_connect_ldap()

	if (spip_connect_ldap()) {
		return;
	}

	generer_htpasswd_files($htpasswd, "$htpasswd-admin");
}

/**
 * Generer le fichier de htpasswd contenant les htpass
 * @param $htpasswd
 * @param $htpasswd_admin
 */
function generer_htpasswd_files($htpasswd, $htpasswd_admin) {
	if ($generer_htpasswd = charger_fonction('generer_htpasswd_files', 'inc', true)) {
		$generer_htpasswd($htpasswd, $htpasswd_admin);
	}

	$pwd_all = ''; // login:htpass pour tous
	$pwd_admin = ''; // login:htpass pour les admins

	$res = sql_select('login, htpass, statut', 'spip_auteurs', "htpass!='' AND login!='' AND ".sql_in('statut', ['1comite', '0minirezo', 'nouveau']));
	while ($row = sql_fetch($res)) {
		if (strlen($row['login']) and strlen($row['htpass'])) {
			$ligne = $row['login'] . ':' . $row['htpass'] . "\n";
			$pwd_all .= $ligne;
			if ($row['statut'] == '0minirezo') {
				$pwd_admin .= $ligne;
			}
		}
	}

	if ($pwd_all) {
		ecrire_fichier($htpasswd, $pwd_all);
		ecrire_fichier($htpasswd_admin, $pwd_admin);
		spip_log("Ecriture de $htpasswd et $htpasswd_admin", 'htpass');
	}
}

/**
 * Créer un password htaccess
 *
 * @link http://docs.php.net/manual/fr/function.crypt.php Documentation de `crypt()`
 *
 * @param string $pass
 *   Le mot de passe
 * @return void|string
 *  La chaîne hachée si fonction crypt présente, rien sinon.
 */
function generer_htpass($pass) {
	if ($generer_htpass = charger_fonction('generer_htpass', 'inc', true)) {
		return $generer_htpass($pass);
	}
	elseif (function_exists('crypt')) {
		return crypt($pass, initialiser_sel());
	}
	return '';
}

/**
 * Installe ou vérifie un fichier .htaccess, y compris sa prise en compte par Apache
 *
 * @uses recuperer_url()
 * @param string $rep
 *     Nom du répertoire où SPIP doit vérifier l'existence d'un fichier .htaccess
 * @param bool $force
 * @return boolean
 */
function verifier_htaccess($rep, $force = false) {
	$htaccess = rtrim($rep, '/') . '/' . _ACCESS_FILE_NAME;
	if (((@file_exists($htaccess)) or defined('_TEST_DIRS')) and !$force) {
		return true;
	}

	// directive deny compatible Apache 2.0+
	$deny =
		'# Deny all requests from Apache 2.4+.
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
# Deny all requests from Apache 2.0-2.2.
<IfModule !mod_authz_core.c>
  Deny from all
</IfModule>
';
	// support des vieilles versions Apache 1.x mais uniquement si elles l'annoncent (pas en mode PROD)
	if (function_exists('apache_get_version')
		and $v = apache_get_version()
		and strncmp($v, 'Apache/1.', 9) == 0) {
		$deny = "deny from all\n";
	}

	if ($ht = @fopen($htaccess, 'w')) {
		fputs($ht, $deny);
		fclose($ht);
		@chmod($htaccess, _SPIP_CHMOD & 0666);
		$t = rtrim($rep, '/') . '/.ok';
		if ($ht = @fopen($t, 'w')) {
			@fclose($ht);
			include_spip('inc/distant');
			$t = substr($t, strlen(_DIR_RACINE));
			$t = url_de_base() . $t;
			$ht = recuperer_url($t, ['methode' => 'HEAD', 'taille_max' => 0, 'follow_location' => false]);
			$ht = ($ht['status'] ?? null) === 403;
		}
	}
	spip_log("Creation de $htaccess " . ($ht ? ' reussie' : ' manquee'));

	return $ht;
}

/**
 * Créer un fichier .htaccess pour chaque répertoire d'extension
 * dans `_DIR_IMG` si la configuration le demande
 *
 * @note
 *     La variable de configuration `creer_htaccess` peut être posée
 *     par un plugin tel acces_restreint.
 *
 * @uses _DIR_IMG
 * @uses verifier_htaccess()
 *
 * @return string
 *         Valeur de la configuration `creer_htaccess`
 */
function gerer_htaccess() {
	// Cette variable de configuration peut etre posee par un plugin
	// par exemple acces_restreint
	$f = (isset($GLOBALS['meta']['creer_htaccess']) and ($GLOBALS['meta']['creer_htaccess'] === 'oui'));
	$dirs = sql_allfetsel('extension', 'spip_types_documents');
	$dirs[] = array('extension' => 'distant');
	foreach ($dirs as $e) {
		if (is_dir($dir = _DIR_IMG . $e['extension'])) {
			if ($f) {
				verifier_htaccess($dir);
			} else {
				spip_unlink($dir . '/' . _ACCESS_FILE_NAME);
			}
		}
	}

	return isset($GLOBALS['meta']['creer_htaccess']) ? $GLOBALS['meta']['creer_htaccess'] : '';
}
