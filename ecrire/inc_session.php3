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
	$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere'));
	$ok = false;
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
	global $flag_mt_rand;
	if ($id_auteur = $auteur['id_auteur']) {
		$seed = (double) (microtime() + 1) * time();
		if ($flag_mt_rand) mt_srand($seed);
		srand($seed);
		if ($flag_mt_rand) $s = mt_rand();
		if (!$s) $s = rand();
		$id_session = md5(uniqid($s));
		ajouter_session($auteur, $id_session);
		return $id_session;
	}
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
	$dir = opendir($dirname);
	while(($item = readdir($dir)) != ''){
		if (ereg("^session_[a-z0-9]+\.php3$", $item)) {
			$session = file("$dirname$item");
			if (ereg("GLOBALS\['auteur_session'\]\['login'\] = '$login'", $session[3])) {
				if ($zap) {
					@unlink("$dirname$item");
				} else {
					return true;
				}
			}
		}
	}
}

?>