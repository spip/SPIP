<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/meta');

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
	return md5($GLOBALS['ip'] . $_SERVER['HTTP_USER_AGENT']);
}


//
// Calcule le nom du fichier session
//
function fichier_session($id_session, $alea) {
	if (ereg("^([0-9]+_)", $id_session, $regs))
		$id_auteur = $regs[1];
	return _DIR_SESSIONS . 'session_'.$id_auteur.md5($id_session.' '.$alea). '.php';

}

//
// Ajouter une session pour l'auteur specifie
//
function ajouter_session($auteur, $id_session, $lang='') {

	global $auteur_session;

	if ($lang) {
		spip_query("UPDATE spip_auteurs SET lang = " . spip_abstract_quote($lang) . " WHERE id_auteur = " . intval($auteur['id_auteur']));
		$auteur_session['lang'] = $lang;
	}

	renouvelle_alea();
	$fichier_session = fichier_session($id_session, $GLOBALS['meta']['alea_ephemere']);

	$texte = "<"."?php\n";
	foreach (array('id_auteur', 'nom', 'login', 'email', 'statut', 'lang', 'ip_change', 'hash_env') AS $var) {
		$code = addslashes($auteur[$var]);
		$texte .= "\$GLOBALS['auteur_session']['$var'] = '$code';\n";
	}
	$texte .= "?".">\n";

	if (!ecrire_fichier($fichier_session, $texte))
		redirige_par_entete(generer_url_action('test_dirs','',true));
}

function update_prefs_session($prefs, $id_auteur)
{
  $prefs = serialize($prefs);
	spip_query("UPDATE spip_auteurs SET prefs = " . spip_abstract_quote($prefs) . " WHERE id_auteur = $id_auteur");
}

//
// Verifier et inclure une session
//
function verifier_session($id_session) {

	// Tester avec alea courant
	$ok = false;
	if ($id_session) {
		$fichier_session = fichier_session($id_session, $GLOBALS['meta']['alea_ephemere']);
		if (@file_exists($fichier_session)) {
			include($fichier_session);
			$ok = true;
		}
		else {
			// Sinon, tester avec alea precedent
			$fichier_session = fichier_session($id_session, $GLOBALS['meta']['alea_ephemere_ancien']);
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
	$fichier_session = fichier_session($id_session, $GLOBALS['meta']['alea_ephemere']);
	if (@file_exists($fichier_session)) {
		@unlink($fichier_session);
	}
	$fichier_session = fichier_session($id_session, $GLOBALS['meta']['alea_ephemere_ancien']);
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
		$fichier_session = fichier_session($s, $GLOBALS['meta']['alea_ephemere']);

	$dir = opendir(_DIR_SESSIONS);
	$t = time();
	while(($item = readdir($dir)) !== false) {
		$chemin = _DIR_SESSIONS . $item;
		if (ereg("^session_([0-9]+_)?([a-z0-9]+)\.php[3]?$", $item, $regs)) {

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
	if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']
	&& !$GLOBALS['ignore_auth_http']) {
		$result = spip_query("SELECT * FROM spip_auteurs WHERE login=" . spip_abstract_quote($_SERVER['PHP_AUTH_USER']));
		if (!$GLOBALS['db_ok'])
			return false;
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
// entete php_auth (est-encore utilise ?)
//
function ask_php_auth($pb, $raison, $retour, $url='', $re='', $lien='') {
	@Header("WWW-Authenticate: Basic realm=\"espace prive\"");
	@Header("HTTP/1.0 401 Unauthorized");
	echo "<b>$pb</b><p>$raison</p>[<a href='./'>$retour</a>] ";
	if ($url) {
		echo "[<a href='", generer_url_public('spip_cookie',"essai_auth_http=oui&$url"), "'>$re</a>]";
	}
	
	if ($lien)
		echo " [<a href='" . _DIR_RESTREINT_ABS . "'>"._T('login_espace_prive')."</a>]";
	exit;
}

//
// verifie si on a un cookie de session ou un auth_php correct
// et charge ses valeurs dans $GLOBALS['auteur_session']
//
function verifier_session_visiteur() {
	if (verifier_session($_COOKIE['spip_session']))
		return true;
	if (verifier_php_auth())
		return true;
	return false;
}

//
// Renouvellement de l'alea utilise pour valider certaines operations
// (session, ajouter une image, etc.)
//
function renouvelle_alea()
{
	if (abs(time() -  $GLOBALS['meta']['alea_ephemere_date']) > 2 * 24*3600) {
	  	spip_log("renouvellement de l'alea_ephemere");
		$alea = md5(creer_uniqid());
		ecrire_meta('alea_ephemere_ancien', $GLOBALS['meta']['alea_ephemere']);
		ecrire_meta('alea_ephemere', $alea);
		ecrire_meta('alea_ephemere_date', time());
		ecrire_metas();
	}
}


function _action_auteur($action, $id_auteur, $nom_alea) {
	if (!($id_auteur = intval($id_auteur))) {
		global $connect_id_auteur, $connect_pass;
		$id_auteur = $connect_id_auteur;
		$pass = $connect_pass;
	}
	else {
		$result = spip_query("SELECT pass FROM spip_auteurs WHERE id_auteur=$id_auteur");
		if ($result) if ($row = spip_fetch_array($result)) $pass = $row['pass'];
	}
	return md5($action.$id_auteur.$pass .$GLOBALS['meta'][$nom_alea]);
}

function calculer_action_auteur($action, $id_auteur = 0) {
	renouvelle_alea();
	return _action_auteur($action, $id_auteur, 'alea_ephemere');
}

function verifier_action_auteur($action, $valeur, $id_auteur = 0) {
	if ($valeur == _action_auteur($action, $id_auteur, 'alea_ephemere'))
		return true;
	if ($valeur == _action_auteur($action, $id_auteur, 'alea_ephemere_ancien'))
		return true;
	spip_log("verifier action $action $id_auteur : echec");
	return false;
}

function generer_action_auteur($action, $arg, $redirect="", $no_entites=false)
{
	global $connect_id_auteur;
	$hash = calculer_action_auteur("$action $arg");
	if ($redirect) $redirect = "&redirect=" . rawurlencode($redirect);

	return generer_url_action($action, "arg=$arg&id_auteur=$connect_id_auteur&hash=$hash$redirect", $no_entites);
}

function determine_upload()
{
	global $connect_toutes_rubriques, $connect_login, $connect_statut ;

	if (!$GLOBALS['flag_upload']) return false;
	if (!$connect_statut) {
		$var_auth = charger_fonction('auth', 'inc');
		$var_auth = $var_auth();
	}
	if ($connect_statut != '0minirezo') return false;
 	return _DIR_TRANSFERT . 
	  ($connect_toutes_rubriques ? '' : ($connect_login . '/'));
}

?>
