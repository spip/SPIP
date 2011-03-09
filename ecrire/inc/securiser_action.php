<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// interface d'appel:
// - au moins un argument: retourne une URL ou un formulaire securises
// - sans argument: verifie la securite et retourne _request('arg'), ou exit.

// http://doc.spip.org/@inc_securiser_action_dist
function inc_securiser_action_dist($action='', $arg='', $redirect="", $mode=false, $att='', $public=false)
{
	if ($action)
		return securiser_action_auteur($action, $arg, $redirect, $mode, $att, $public);
	else {
		$arg = _request('arg');
		$hash = _request('hash');
		$action = _request('action')?_request('action'):_request('formulaire_action');
		if ($a = verifier_action_auteur("$action-$arg", $hash))
			return $arg;
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
}

// Attention: PHP applique urldecode sur $_GET mais pas sur $_POST
// cf http://fr.php.net/urldecode#48481
// http://doc.spip.org/@securiser_action_auteur
function securiser_action_auteur($action, $arg, $redirect="", $mode=false, $att='', $public=false)
{
	static $id_auteur=0, $pass;
	if (!$id_auteur) {
		list($id_auteur, $pass) =  caracteriser_auteur();
	}
	$hash = _action_auteur("$action-$arg", $id_auteur, $pass, 'alea_ephemere');
	if (!is_string($mode)){
		$r = rawurlencode($redirect);
		if ($mode===-1)
			return array('action'=>$action,'arg'=>$arg,'hash'=>$hash);
		else
			return generer_url_action($action, "arg=$arg&hash=$hash" . (!$r ? '' : "&redirect=$r"), $mode, $att);
	}

	$att .= " style='margin: 0px; border: 0px'";
	if ($redirect)
		$redirect = "\n\t\t<input name='redirect' type='hidden' value='". str_replace("'", '&#39;', $redirect) ."' />";
	$mode .= $redirect . "
<input name='hash' type='hidden' value='$hash' />
<input name='arg' type='hidden' value='$arg' />";

	return generer_form_action($action, $mode, $att, $public);
}

// http://doc.spip.org/@caracteriser_auteur
function caracteriser_auteur() {
	global $visiteur_session;
	static $caracterisation = array();

	if ($caracterisation) return $caracterisation;

	if (!isset($visiteur_session['id_auteur'])) {
	// si l'auteur courant n'est pas connu alors qu'il peut demander une action
	// c'est une connexion par php_auth ou 1 instal, on se rabat sur le cookie.
	// S'il n'avait pas le droit de realiser cette action, le hash sera faux.
		if (isset($_COOKIE['spip_session'])
		AND (preg_match('/^(\d+)/',$_COOKIE['spip_session'],$r))) {
			  return array($r[1], '');
			  // Necessaire aux forums anonymes.
			  // Pour le reste, ca echouera.
		} else return array('0','');
	}
	// Eviter l'acces SQL si le pass est connu de PHP
	$id_auteur = $visiteur_session['id_auteur'];
	if (isset($visiteur_session['pass']) AND $visiteur_session['pass'])
		return $caracterisation = array($id_auteur, $visiteur_session['pass']); 
	else if ($id_auteur>0) {
		include_spip('base/abstract_sql');
		$t = sql_fetsel("id_auteur, pass", "spip_auteurs", "id_auteur=$id_auteur");
		if ($t)
			return $caracterisation = array($t['id_auteur'], $t['pass']);
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
	// Visiteur anonyme, pour ls forums par exemple
	else {
		return array('0','');
	}
}

// http://doc.spip.org/@_action_auteur
function _action_auteur($action, $id_auteur, $pass, $alea) {
	static $sha = array();
	if (!isset($sha[$id_auteur.$pass.$alea])){
		if (!isset($GLOBALS['meta'][$alea]) AND _request('exec')!=='install') {
			include_spip('base/abstract_sql');
			$GLOBALS['meta'][$alea] = sql_getfetsel('valeur', 'spip_meta', "nom=" . sql_quote($alea));
			if (!($GLOBALS['meta'][$alea])) {
				include_spip('inc/minipres');
				echo minipres();
				spip_log("$alea indisponible");
				exit;
			}
		}
		include_spip('auth/sha256.inc');
		$sha[$id_auteur.$pass.$alea] = _nano_sha256($id_auteur.$pass.@$GLOBALS['meta'][$alea]);
	}
	if (function_exists('sha1'))
		return sha1($action.$sha[$id_auteur.$pass.$alea]);
	else
		return md5($action.$sha[$id_auteur.$pass.$alea]);
}

// http://doc.spip.org/@calculer_action_auteur
function calculer_action_auteur($action) {
	list($id_auteur, $pass) = caracteriser_auteur();
	return _action_auteur($action, $id_auteur, $pass, 'alea_ephemere');
}

// http://doc.spip.org/@verifier_action_auteur
function verifier_action_auteur($action, $valeur) {
	list($id_auteur, $pass) = caracteriser_auteur();
	if ($valeur == _action_auteur($action, $id_auteur, $pass, 'alea_ephemere'))
		return true;
	if ($valeur == _action_auteur($action, $id_auteur, $pass, 'alea_ephemere_ancien'))
		return true;
	return false;
}

//
// Des fonctions independantes du visiteur, qui permettent de controler
// par exemple que l'URL d'un document a la bonne cle de lecture
//

// Le secret du site doit rester aussi secret que possible, et est eternel
// On ne doit pas l'exporter
// http://doc.spip.org/@secret_du_site
function secret_du_site() {
	if (!isset($GLOBALS['meta']['secret_du_site'])){
		include_spip('base/abstract_sql');
		$GLOBALS['meta']['secret_du_site'] = sql_getfetsel('valeur', 'spip_meta', "nom='secret_du_site'");
	}
	if (!isset($GLOBALS['meta']['secret_du_site'])
	  OR (strlen($GLOBALS['meta']['secret_du_site'])<64)) {
		include_spip('inc/acces');
		include_spip('auth/sha256.inc');
		ecrire_meta('secret_du_site', _nano_sha256($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SERVER_SIGNATURE"] . creer_uniqid()), 'non');
		lire_metas(); // au cas ou ecrire_meta() ne fonctionne pas
	}
	return $GLOBALS['meta']['secret_du_site'];
}

// http://doc.spip.org/@calculer_cle_action
function calculer_cle_action($action) {
	if (function_exists('sha1'))
		return sha1($action . secret_du_site());
	else
		return md5($action . secret_du_site());
}

// http://doc.spip.org/@verifier_cle_action
function verifier_cle_action($action, $cle) {
	return ($cle == calculer_cle_action($action));
}

?>
