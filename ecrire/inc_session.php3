<?php
//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_SESSION")) return;
define("_ECRIRE_INC_SESSION", "1");


/*
 * Gestion de l'authentification par sessions
 * a utiliser pour valider l'acces (bloquant)
 * ou pour reconnaitre un utilisateur (non bloquant)
 *
 */

$GLOBALS['auteur_session'] = '';


//
// Caracterisation du brouteur pour limiter le chourage de cookies
//
function hash_env() {
	return md5(getenv('HTTP_USER_AGENT'));
}


//
// Calcule le nom du fichier session
//
function fichier_session($id_session, $alea) {
	$fichier_session = 'session_'.md5($id_session.' '.$alea).'.php3';
	$fichier_session = 'data/'.$fichier_session;
	if (!$GLOBALS['flag_ecrire']) $fichier_session = 'ecrire/'.$fichier_session;
	return $fichier_session;
}

//
// Effacer toutes les sessions crees il y a plus de 48 heures
// (de toute facon invalides car l'alea est expire)
//
function nettoyer_sessions() {
	$dir = 'data';
	if (!$GLOBALS['flag_ecrire']) $dir = 'ecrire/'.$dir;
	$handle = opendir($dir);
	$t = time();
	while (($fichier = readdir($handle)) != '') {
		if (!eregi("^session_[0-9a-f]+\.php3?$", $fichier)) continue;
		$chemin = "$dir/$fichier";
		if (($t - filemtime($chemin)) > 48 * 3600) {
			@unlink($chemin);
		}
	}
	closedir($handle);
}

//
// Ajouter une session pour l'auteur specifie
//
function ajouter_session($auteur, $id_session) {
	nettoyer_sessions();
	$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere'));
	$vars = array('id_auteur', 'nom', 'login', 'email', 'statut');

	$texte = "<"."?php\n";
	reset($vars);
	while (list(, $var) = each($vars)) {
		$texte .= "\$GLOBALS['auteur_session']['$var'] = '".addslashes($auteur[$var])."';\n";
	}
	$texte .= "\$GLOBALS['auteur_session']['hash_env'] = '".hash_env()."';\n";
	$texte .= "?".">";
	if ($f = fopen($fichier_session, "wb")) {
		fputs($f, $texte);
 		fclose($f);
	}
}

//
// Verifier et inclure une session
//
function verifier_session($id_session) {
	// Tester avec alea courant
	$ok = false;
	if ($id_session) {
		$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere'));
		if (file_exists($fichier_session)) {
			include($fichier_session);
			$ok = true;
		}
		else {
			// Sinon, tester avec alea precedent
			$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere_ancien'));
			if (file_exists($fichier_session)) {
				// Renouveler la session (avec l'alea courant)
				include($fichier_session);
				supprimer_session($id_session);
				ajouter_session($GLOBALS['auteur_session'], $id_session);
				$ok = true;
			}
		}
	}

	// Valider le brouteur
	if ($ok) $ok = (hash_env() == $GLOBALS['auteur_session']['hash_env']);
	return $ok;
}

//
// Supprimer une session
//
function supprimer_session($id_session) {
	$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere'));
	if (file_exists($fichier_session)) {
		@unlink($fichier_session);
	}
	$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere_ancien'));
	if (file_exists($fichier_session)) {
		@unlink($fichier_session);
	}
}

//
// Creer une session et retourne le cookie correspondant (a poser)
//
function creer_cookie_session($auteur) {
	if ($id_auteur = $auteur['id_auteur']) {
		$id_session = md5(creer_uniqid($s));
		ajouter_session($auteur, $id_session);
		return $id_session;
	}
}

//
// Creer un identifiant aleatoire
//
function creer_uniqid() {
	static $seeded;
	global $flag_mt_rand;

	if (!$seeded) {
		$seed = (double) (microtime() + 1) * time();
		if ($flag_mt_rand) mt_srand($seed);
		srand($seed);
		$seeded = true;
	}

	if ($flag_mt_rand) $s = mt_rand();
	if (!$s) $s = rand();
	return uniqid($s);
}


//
// sessions a zapper (login, zapper oui/non)
// (un peu crado car lecture obligatoire de toutes les sessions... pour simplifier
//  il faudrait renommer les sessions en session_login_alea.php3 ? Noter aussi que
//  en attendant le login est lu directement sur la ligne 3 du fichier de session)
function zap_sessions ($login, $zap) {
	if ($GLOBALS['flag_ecrire']) {
		$dirname = "data/";
	} else {
		$dirname = "ecrire/data/";
	}

	// ne pas se zapper soi-meme
	if ($s = $GLOBALS['spip_session'])
		$fichier_session = fichier_session($s, lire_meta('alea_ephemere'));

	$dir = opendir($dirname);
	while(($item = readdir($dir)) != ''){
		if (ereg("^session_([a-z0-9]+)\.php3$", $item, $regs) AND ($fichier_session != $dirname.$item)) {
			$session = file("$dirname$item");
			if (ereg("GLOBALS\['auteur_session'\]\['login'\] = '$login'", $session[3])) {
				$zap_num ++;
				if ($zap) {
					@unlink("$dirname$item");
				}
			}
		}
	}
	return $zap_num;
}

//
// reconnaitre un utilisateur authentifie en php_auth
//
function verifier_php_auth() {
	global $PHP_AUTH_USER, $PHP_AUTH_PW;
	if ($PHP_AUTH_USER && $PHP_AUTH_PW) {
		include_ecrire("inc_connect.php3"); // uniquement si appel depuis espace public
		$login = addslashes($PHP_AUTH_USER);
		$result = spip_query("SELECT * FROM spip_auteurs WHERE login='$login'");
		$row = mysql_fetch_array($result);
		$auth_mdpass = md5($row['alea_actuel'] . $PHP_AUTH_PW);
		if ($auth_mdpass != $row['pass']) {
			$PHP_AUTH_USER='';
			return false;
		} else {
			$GLOBALS['auteur_session']['id_auteur'] = $row['id_auteur'];
			$GLOBALS['auteur_session']['nom'] = $row['nom'];
			$GLOBALS['auteur_session']['login'] = $row['login'];
			$GLOBALS['auteur_session']['email'] = $row['email'];
			$GLOBALS['auteur_session']['statut'] = $row['statut'];
			$GLOBALS['auteur_session']['hash_env'] = '';
			return true;
		}
	}
}

//
// entete php_auth
//
function ask_php_auth($text_failure) {
	@Header("WWW-Authenticate: Basic realm=\"espace prive\"");
	@Header("HTTP/1.0 401 Unauthorized");
	echo $text_failure;
	exit;
}

//
// verifie si on a un cookie de session ou un auth_php correct
// et charge ses valeurs dans $GLOBALS['auteur_session']
//
function verifier_visiteur() {
	return (
			verifier_session($GLOBALS['HTTP_COOKIE_VARS']['spip_session'])
		OR
			verifier_php_auth ()
	);
}

?>