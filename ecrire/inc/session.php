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
$GLOBALS['rejoue_session'] = '';

//
// 3 actions sur les sessions, selon le type de l'argument:
//
// - numérique: efface toutes les sessions de l'id_auteur (retour quelconque)
// - tableau: cree une session pour l'auteur decrit et retourne l'identifiant
// - autre: predicat de validite de la session indiquee par le cookie

function inc_session_dist($auteur=false)
{
	if (is_numeric($auteur))
		return supprimer_sessions($auteur);
	else if (is_array($auteur))
		return ajouter_session($auteur);
	else return verifier_session($auteur);
}

//
// Ajoute une session pour l'auteur decrit par un tableau issu d'un SELECT-SQL
//

function ajouter_session($auteur) {

	global $spip_session;
	renouvelle_alea();
	if (!$spip_session) 
		$spip_session = $auteur['id_auteur'].'_'.md5(creer_uniqid());

	$fichier_session = fichier_session($spip_session, $GLOBALS['meta']['alea_ephemere']);

	if (!isset($auteur['hash_env'])) $auteur['hash_env'] = hash_env();

	$texte = "<"."?php\n";
	foreach (array('id_auteur', 'nom', 'login', 'email', 'statut', 'lang', 'ip_change', 'hash_env') AS $var) {
		$code = addslashes($auteur[$var]);
		$texte .= "\$GLOBALS['auteur_session']['$var'] = '$code';\n";
	}
	$texte .= "?".">\n";

	if (!ecrire_fichier($fichier_session, $texte))
		redirige_par_entete(generer_url_action('test_dirs','',true));
	else return $spip_session;
}

//
// Cette fonction efface toutes les sessions appartenant a l'auteur
// On en profite pour effacer toutes les sessions creees il y a plus de 48 h
//

function supprimer_sessions($id_auteur) {

	$dir = opendir(_DIR_SESSIONS);
	$t = time()  - (48 * 3600);
	while(($f = readdir($dir)) !== false) {

		if (ereg("^session_([0-9]+)_[a-z0-9]+\.php[3]?$", $f, $regs)){
			$f = _DIR_SESSIONS . $f;
			if (($regs[1] == $id_auteur) OR ($t > filemtime($f)))
				@unlink($f);
		}
	}
}

//
// Verifie et inclut une session. 
// La rejoue si IP change puis accepte le changement si $change=true
//

function verifier_session($change=false) {

	global $spip_session; // issu du cookie

	// Tester avec alea courant
	if (!$spip_session) return false;

	$fichier_session = fichier_session($spip_session, $GLOBALS['meta']['alea_ephemere']);
	if (@file_exists($fichier_session)) {
		include($fichier_session);
	} else {
		// Sinon, tester avec alea precedent
		$fichier_session = fichier_session($spip_session, $GLOBALS['meta']['alea_ephemere_ancien']);
		if (!@file_exists($fichier_session)) return false;

		// Renouveler la session avec l'alea courant
		include($fichier_session);
		@unlink($fichier_session);
		ajouter_session($GLOBALS['auteur_session']);
	}

	// Si l'adresse IP change, inc/presentation mettra une balise image
	// avec un URL de rappel demandant a changer le nom de la session.
	// Seul celui qui a l'IP d'origine est rejoue
	// ainsi un eventuel voleur de cookie ne pourrait pas deconnecter
	// sa victime, mais se ferait deconnecter par elle.

	if (hash_env() != $GLOBALS['auteur_session']['hash_env']) {
	    if (!$GLOBALS['auteur_session']['ip_change']) {
		$GLOBALS['rejoue_session'] = rejouer_session();
		$GLOBALS['auteur_session']['ip_change'] = true;
		ajouter_session($GLOBALS['auteur_session']);
	    } else if ($change)
	      spip_log("session non rejouee, vol de cookie ?");
	} else { if ($change) {
		spip_log("rejoue session $fichier_session $spip_session");
		@unlink($fichier_session);
		$auteur_session['ip_change'] = false;
		unset($spip_session);
		$cookie= ajouter_session($auteur_session);
		spip_setcookie('spip_session', $cookie);
	  }
	}
	return 	true;
}

// Code a inserer par inc/presentation pour rejouer la session
// Voir action/cookie qui sera appele.

function rejouer_session()
{
	include_spip('inc/minipres');
	return	  http_img_pack('rien.gif', " ", "id='img_session' width='0' height='0'") .
		  http_script("\ndocument.img_session.src='" . generer_url_action('cookie','change_session=oui', true) .  "'");
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
// On verifie l'IP et le nom du navigateur
//

function hash_env() {
  static $res ='';
  if ($res) return $res;
  return $res = md5($GLOBALS['ip'] . $_SERVER['HTTP_USER_AGENT']);
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
// reconnaitre un utilisateur authentifie en php_auth
//
function verifier_php_auth() {
	if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']
	&& !$GLOBALS['ignore_auth_http']) {
		$result = spip_query("SELECT * FROM spip_auteurs WHERE login=" . spip_abstract_quote($_SERVER['PHP_AUTH_USER']));
		if (!$GLOBALS['db_ok'])
			return false;
		$row = spip_fetch_array($result);
		if (($row['source'] != 'ldap' OR !$GLOBALS['ldap_present'])
                AND $row['pass'] != md5($row['alea_actuel'] . $_SERVER['PHP_AUTH_PW'])) {
			return false;
		} else {
			$GLOBALS['auteur_session'] = $row;
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

function caracteriser_auteur($id_auteur=0) {
	global $auteur_session;
	if (!($id_auteur = intval($id_auteur))) {
		return array($auteur_session['id_auteur'], $auteur_session['pass']); 
	}
	else {
		$result = spip_query("SELECT id_auteur, pass FROM spip_auteurs WHERE id_auteur=$id_auteur");
		return spip_fetch_array($result);
	}
}

function _action_auteur($action, $id_auteur, $pass, $nom_alea) {
	return md5($action.$id_auteur.$pass .$GLOBALS['meta'][$nom_alea]);
}

function calculer_action_auteur($action, $id_auteur = 0) {
	renouvelle_alea();
	list($id_auteur, $pass) = caracteriser_auteur($id_auteur);
	return _action_auteur($action, $id_auteur, $pass, 'alea_ephemere');
}

function verifier_action_auteur($action, $valeur, $id_auteur = 0) {
	list($id_auteur, $pass) = caracteriser_auteur($id_auteur);

	if ($valeur == _action_auteur($action, $id_auteur, $pass, 'alea_ephemere'))
		return true;
	if ($valeur == _action_auteur($action, $id_auteur, $pass, 'alea_ephemere_ancien'))
		return true;
	spip_log("verifier action $action $id_auteur : echec");
	return false;
}

function generer_action_auteur($action, $arg, $redirect="", $mode=false, $att='')
{
	static $id_auteur=0, $pass;
	if (!$id_auteur) {
		renouvelle_alea();
		list($id_auteur, $pass) =  caracteriser_auteur();
	}
	$hash = _action_auteur("$action-$arg", $id_auteur, $pass, 'alea_ephemere');
	if (!is_string($mode))
	  return generer_url_action($action, "arg=$arg&id_auteur=$id_auteur&hash=$hash" . (!$redirect ? '' : ("&redirect=" . rawurlencode($redirect))), $mode);
	if ($redirect)
		$redirect = "\n\t\t<input name='redirect' type='hidden' value='$redirect' />";
	return "\n<form action='" .
		generer_url_action($action,'') .
		"'$att>\n\t<div>
		<input name='id_auteur' type='hidden' value='$id_auteur' />
		<input name='hash' type='hidden' value='$hash' />
		<input name='action' type='hidden' value='$action' />
		<input name='arg' type='hidden' value='$arg' />" .
		$redirect .  
		$mode .
		"\n\t</div>\n</form>\n";
}

function redirige_action_auteur($action, $arg, $ret, $gra)
{
	return generer_action_auteur($action, $arg, generer_url_ecrire($ret, $gra, true, _DIR_RESTREINT_ABS));
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
