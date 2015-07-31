<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

//
if (!defined("_ECRIRE_INC_VERSION")) return;


/*
 * Gestion de l'authentification par sessions
 * a utiliser pour valider l'acces (bloquant)
 * ou pour reconnaitre un utilisateur (non bloquant)
 *
 */

$GLOBALS['auteur_session'] = '';


//
// On verifie l'IP et le nom du navigateur
//
function hash_env() {
	global $_SERVER;
	return md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
}


//
// Calcule le nom du fichier session
//
function fichier_session($id_session, $alea) {
	if (ereg("^([0-9]+_)", $id_session, $regs))
		$id_auteur = $regs[1];
	return _DIR_SESSIONS . 'session_'.$id_auteur.md5($id_session.' '.$alea).'.php3';

}

//
// Ajouter une session pour l'auteur specifie
//
function ajouter_session($auteur, $id_session) {
	$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere'));
	$vars = array('id_auteur', 'nom', 'login', 'email', 'statut', 'lang', 'ip_change', 'hash_env');

	$texte = "<"."?php\n";
	reset($vars);
	while (list(, $var) = each($vars)) {
		$texte .= "\$GLOBALS['auteur_session']['$var'] = '".addslashes($auteur[$var])."';\n";
	}
	$texte .= "?".">\n";

	if ($f = @fopen($fichier_session, "wb")) {
		fputs($f, $texte);
 		fclose($f);
	} else {
		redirige_par_entete(lire_meta("adresse_site") .
				    "/spip_test_dirs.php3");
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
		if (@file_exists($fichier_session)) {
			include($fichier_session);
			$ok = true;
		}
		else {
			// Sinon, tester avec alea precedent
			$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere_ancien'));
			if (@file_exists($fichier_session)) {
				// Renouveler la session (avec l'alea courant)
				include($fichier_session);
				supprimer_session($id_session);
				ajouter_session($GLOBALS['auteur_session'], $id_session);
				$ok = true;
			}
		}
	}

	// marquer la session comme "ip-change" si le cas se presente
	if ($ok AND (hash_env() != $GLOBALS['auteur_session']['hash_env']) AND !$GLOBALS['auteur_session']['ip_change']) {
		$GLOBALS['auteur_session']['ip_change'] = true;
		ajouter_session($GLOBALS['auteur_session'], $id_session);
	}

	return $ok;
}

//
// Supprimer une session
//
function supprimer_session($id_session) {
	$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere'));
	if (@file_exists($fichier_session)) {
		@unlink($fichier_session);
	}
	$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere_ancien'));
	if (@file_exists($fichier_session)) {
		@unlink($fichier_session);
	}
}

//
// Creer une session et retourne le cookie correspondant (a poser)
//
function creer_cookie_session($auteur) {
	if ($id_auteur = $auteur['id_auteur']) {
		$id_session = $id_auteur.'_'.md5(creer_uniqid());
		$auteur['hash_env'] = hash_env();
		ajouter_session($auteur, $id_session);
		return $id_session;
	}
}

//
// Creer un identifiant aleatoire
//
function creer_uniqid() {
	static $seeded;

	if (!$seeded) {
		$seed = (double) (microtime() + 1) * time();
		mt_srand($seed);
		srand($seed);
		$seeded = true;
	}

	$s = mt_rand();
	if (!$s) $s = rand();
	return uniqid($s, 1);
}


//
// Cette fonction efface toutes les sessions appartenant a l'auteur
// On en profite pour effacer toutes les sessions creees il y a plus de 48 h
//
function zap_sessions ($id_auteur, $zap) {

	// ne pas se zapper soi-meme
	if ($s = $GLOBALS['spip_session'])
		$fichier_session = fichier_session($s, lire_meta('alea_ephemere'));

	$dir = opendir(_DIR_SESSIONS);
	$t = time();
	while(($item = readdir($dir)) != '') {
		$chemin = _DIR_SESSIONS . $item;
		if (ereg("^session_([0-9]+_)?([a-z0-9]+)\.php3$", $item, $regs)) {

			// Si c'est une vieille session, on jette
			if (($t - filemtime($chemin)) > 48 * 3600)
				@unlink($chemin);

			// sinon voir si c'est une session du meme auteur
			else if ($regs[1] == $id_auteur.'_') {
				$zap_num ++;
				if ($zap)
					@unlink($chemin);
			}

		}
	}

	return $zap_num;
}

//
// reconnaitre un utilisateur authentifie en php_auth
//
function verifier_php_auth() {
	global $_SERVER, $ignore_auth_http;
	if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']
	&& !$ignore_auth_http) {
		$login = addslashes($_SERVER['PHP_AUTH_USER']);
		$result = spip_query("SELECT * FROM spip_auteurs WHERE login='$login'");
		$row = spip_fetch_array($result);
		$auth_mdpass = md5($row['alea_actuel'] . $_SERVER['PHP_AUTH_PW']);
		if ($auth_mdpass != $row['pass']) {
			return false;
		} else {
			$GLOBALS['auteur_session']['id_auteur'] = $row['id_auteur'];
			$GLOBALS['auteur_session']['nom'] = $row['nom'];
			$GLOBALS['auteur_session']['login'] = $row['login'];
			$GLOBALS['auteur_session']['email'] = $row['email'];
			$GLOBALS['auteur_session']['statut'] = $row['statut'];
			$GLOBALS['auteur_session']['lang'] = $row['lang'];
			$GLOBALS['auteur_session']['hash_env'] = hash_env();
			return true;
		}
	}
}

//
// entete php_auth
//
function ask_php_auth($pb, $raison, $retour, $url='', $re='', $lien='') {
	@Header("WWW-Authenticate: Basic realm=\"espace prive\"");
	@Header("HTTP/1.0 401 Unauthorized");
	echo "<b>$pb</b><p>$raison</p>[<a href='./'>$retour</a>] ";
	if ($url) {
		include_ecrire('inc_filtres.php3');
		$url = quote_amp($url);
		echo "[<a href='spip_cookie.php3?essai_auth_http=oui"
			. "&amp;$url'>$re</a>]";
	}
	
	if ($lien)
		echo " [<a href='" . _DIR_RESTREINT_ABS
		. "'>"._T('login_espace_prive')."</a>]";
	exit;
}

//
// verifie si on a un cookie de session ou un auth_php correct
// et charge ses valeurs dans $GLOBALS['auteur_session']
//
function verifier_visiteur() {
	if (verifier_session($GLOBALS['_COOKIE']['spip_session']))
		return true;
	if (verifier_php_auth())
		return true;
	return false;
}
?>
