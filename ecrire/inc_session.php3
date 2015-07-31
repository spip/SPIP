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


// $login est optionnel
function affiche_formulaire_login ($login, $redirect, $redirect_echec = '') {
	if ($GLOBALS['flag_ecrire']) $dir = "../";
	if (!$redirect_echec) $redirect_echec = $redirect;

	echo "<form action='$dir"."spip_cookie.php3' method='post'>\n";

	echo "<fieldset>\n";

	echo "<label><b>Login (identifiant de connexion au site)</b><br></label>";
	echo "<input type='text' name='session_login' class='formo' value=\"$login\" size='40'><p>\n";

	echo "<label><b>Mot de passe</b><br></label>";
	echo "<input type='password' name='session_password' class='formo' value=\"\" size='40'><p>\n";

	echo "<input type='hidden' name='essai_login' value='oui'>\n";
	echo "<input type='hidden' name='redirect' value='$redirect'>\n";
	echo "<input type='hidden' name='redirect_echec' value='$redirect_echec'>\n";
	echo "<div align='right'><input type='submit' class='fondl' name='submit' value='Valider'></div>\n";

	echo "</fieldset>\n";

	echo "</form>";
}

?>