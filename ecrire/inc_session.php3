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
		if (!eregi("^session_[0-9a-f].php3?$", $fichier)) continue;
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


// Ajoute une session dans le cache des sessions
// ou supprimer toute session de cet auteur si $session == false
/*function ajouter_session($auteur, $session) {

	if (file_exists ('ecrire/inc_sessions_cache.php3')) {
		include ('ecrire/inc_sessions_cache.php3');
	}
	unset ($sessions[$auteur->id_auteur]);

	if ($session) {
		$id = $auteur->id_auteur;
		$sessions[$id]['nom'] = $auteur->nom;
		$sessions[$id]['login'] = $auteur->login;
		$sessions[$id]['email'] = $auteur->email;
		$sessions[$id]['statut'] = $auteur->statut;
		$sessions[$id]['session'] = $session;
		$sessions[$id]['creation'] = time();
		$sessions[$id]['brouteur'] = md5_brouteur();
	}

	$vars = array ('nom', 'login', 'email', 'session', 'statut', 'creation', 'brouteur');
	$liste_sessions = array_keys($sessions);
	$t = time() - 48 * 3600; // expire la session apres 48 h
	while (list(,$s) = each ($liste_sessions)) {
		if ($sessions[$s]['creation'] > $t) {
			reset ($vars);
			unset ($contenu);
			while (list(, $var) = each($vars)) {
				$contenu[] = "'$var' => '".addslashes($sessions[$s][$var])."'";
			}
			$contenu = join (",\n", $contenu);
			$texte[] = "$s => array ( $contenu )";
		}
	}

	$texte = '<'.'?php $sessions = array ('."\n". join(",\n" ,$texte) . "\n); ?".'>';
	if ($myFile = fopen("ecrire/inc_sessions_cache.php3", "wb")) {
		fputs($myFile, $texte);
 		fclose($myFile);
	}
}*/

// cree le cookie correspondant a l'auteur
// attention aux trous de securite ;)
/*function cree_cookie_session ($auteur) {
	if ($auteur->id_auteur > 0) {
		$session = md5(rand()); // numero de session
		ajouter_session($auteur, $session);
		$cookie = $auteur->id_auteur ."@". $auteur->login ."@". $session;
		return $cookie;
	}
}*/

// cree le cookie admin correspondant a l'auteur
/*function cree_cookie_admin ($auteur) {
	if ($auteur->id_auteur > 0) {
		$cookie = $auteur->id_auteur ."@". $auteur->login ."@". $auteur->nom ."@". $auteur->email;
		return $cookie;
	}
}*/

/*function verifie_cookie_session ($cookie) {
	if ((list(,$id,$login,$session) = decode_cookie_session ($cookie)) AND ($id > 0)) {
		if (file_exists ('ecrire/inc_sessions_cache.php3')) {
			include ('ecrire/inc_sessions_cache.php3');
		} else if (file_exists ('inc_sessions_cache.php3')) {
			include ('inc_sessions_cache.php3');
		}

		// verifier le cookie et la provenance
		if (($session == $sessions[$id]['session']) AND ($sessions[$id]['brouteur'] == md5_brouteur())) {
			$auteur->id_auteur = $id;
			$auteur->login = $sessions[$id]['login'];
			$auteur->nom = $sessions[$id]['nom'];
			$auteur->email = $sessions[$id]['email'];
			$auteur->statut = $sessions[$id]['statut'];
			// raviver la session si > 48 h
			if ($sessions[$id]['creation'] < time() - 48 * 3600)
				ajouter_session($auteur, $session);
			return ($auteur);
		}
	}
}

function decode_cookie_session ($cookie) {
	if (eregi("^([0-9]+)@(.*)@([0-9A-Z]+)$", $cookie, $regs))
	return $regs;
	// list(,$id_auteur,$login,$session) = decode_cookie_session($cookie)
}
*/

/*function pose_cookie_session ($cookie_session, $cookie_admin='') {
	global $redirect;
	$cookie_pose = false;

	// est-ce qu'il faut poser le cookie ?
	if ($GLOBALS['HTTP_COOKIE_VARS']['spip_session'] == $cookie_session)
		return true;

	// est-ce qu'on peut le faire ?
	if (headers_sent() OR ereg("/ecrire/", $GLOBALS['REQUEST_URI']))
		return false;

	// on pose
	if ($cookie_session) {
		// un cookie spip_session d'authentification,
		// qui meurt avec le navigateur
		setcookie ('spip_session', $cookie_session);
		$cookie_pose = true;
	}

	if ($cookie_admin) {
		// un cookie spip_admin qui n'authentifie pas
		// mais conserve des infos deux semaines
		setcookie ('spip_admin', $cookie_admin, time() + 14*24*3600);
		$cookie_pose = true;
	}

	return $cookie_pose;
}


function lit_cookie_session () {
	return $GLOBALS['HTTP_COOKIE_VARS']['spip_session'];
}


function supprime_cookie_session () {
	if ((list(,$id,,) = decode_cookie_session(lit_cookie_session())) AND ($id > 0)) {
		$auteur->id_auteur = $id;
		ajouter_session ($auteur, false); // effacer dans le fichier de sessions
	}
	setcookie ('spip_session', '', time() - 24*3600);
	setcookie ('spip_admin', '', time() - 24*3600);
}*/


// $login est optionnel
function affiche_formulaire_login ($login, $redirect) {
	if ($GLOBALS['flag_ecrire']) $dir = "../";

	echo "<form action='$dir"."spip_cookie.php3' method='post'>\n";

	echo "<fieldset>\n";

	echo "<label><b>Login (identifiant de connexion au site)</b><br></label>";
	echo "<input type='text' name='session_login' class='formo' value=\"$login\" size='40'><p>\n";

	echo "<label><b>Mot de passe</b><br></label>";
	echo "<input type='password' name='session_password' class='formo' value=\"\" size='40'><p>\n";

	echo "<input type='hidden' name='redirect' value='$redirect'>";
	echo "<div align='right'><input type='submit' class='fondl' name='submit' value='Valider'></div>\n";

	echo "</fieldset>\n";

	echo "</form>";
}

?>