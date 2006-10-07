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

// fonction de securite appelee par les scripts de action/
// cf fabrication des arguments dans generer_action_auteur

// http://doc.spip.org/@inc_controler_action_auteur_dist
function inc_controler_action_auteur_dist()
{
	$arg = _request('arg');
	$hash = _request('hash');
	$action = _request('action');

	if (!verifier_action_auteur("$action-$arg", $hash)) {
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}
}

// http://doc.spip.org/@caracteriser_auteur
function caracteriser_auteur() {
	global $auteur_session;
	static $caracterisation = array();

	if ($caracterisation) return $caracterisation;

	$id_auteur = $auteur_session['id_auteur'];
	if (!$id_auteur) {
  // si l'auteur courant n'est pas connu alors qu'il peut demander une action
  // c'est une connexion par php_auth, on se rabat sur le cookie.
  // S'il n'avait pas le droit de realiser cette action, le hash sera faux.
		if (isset($_COOKIE['spip_session'])
		AND (preg_match('/^(\d+)/',$_COOKIE['spip_session'],$r))) {
			  return array($r[1], '');
			  // Necessaire aux forums anonymes.
			  // Pour le reste, ca echouera.
		} else return array('',''); 	  
	}
	// Eviter l'acces SQL si le pass est connu de PHP

	if ($auteur_session['pass'])
		return $caracterisation = array($id_auteur, $auteur_session['pass']); 
	else {
		$t = spip_query("SELECT id_auteur, pass FROM spip_auteurs WHERE id_auteur=$id_auteur");
		if ($t = spip_fetch_array($t))
			return $caracterisation = array($t['id_auteur'], $t['pass']);
		spip_log("auteur $id_auteur sans caracterisation");
		die(_T('info_acces_interdit'));
	}
}

// http://doc.spip.org/@_action_auteur
function _action_auteur($action, $id_auteur, $pass, $nom_alea) {
	return md5($action.$id_auteur.$pass .$GLOBALS['meta'][$nom_alea]);
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
	spip_log("verifier action $action $id_auteur : echec");
	return false;
}


// http://doc.spip.org/@generer_action_auteur
function generer_action_auteur($action, $arg, $redirect="", $mode=false, $att='')
{
	static $id_auteur=0, $pass;
	if (!$id_auteur) {
		list($id_auteur, $pass) =  caracteriser_auteur();
	}
	$hash = _action_auteur("$action-$arg", $id_auteur, $pass, 'alea_ephemere');
	if (!is_string($mode))
	  return generer_url_action($action, "arg=$arg&hash=$hash" . (!$redirect ? '' : ("&redirect=" . rawurlencode($redirect))), $mode);
	if ($redirect)
		$redirect = "\n\t\t<input name='redirect' type='hidden' value='$redirect' />";
	// Attention, JS n'aime pas le melange de param GET/POST
	return "\n<form style='margin:0px' action='" .
		generer_url_public('') .
		"'$att>\n\t<div>
		<input name='hash' type='hidden' value='$hash' />
		<input name='action' type='hidden' value='$action' />
		<input name='arg' type='hidden' value='$arg' />" .
		$redirect .  
		$mode .
		"\n\t</div>\n</form>\n";
}

// http://doc.spip.org/@redirige_action_auteur
function redirige_action_auteur($action, $arg, $ret, $gra='', $mode=false, $atts='') {

	$redirect = generer_url_ecrire($ret, $gra, true, _DIR_RESTREINT_ABS);

	return generer_action_auteur(
		$action,
		$arg,
		$redirect,
		$mode,
		$atts);
}

// Retourne un formulaire d'execution de $action sur $id,
// revenant a l'envoyeur $script d'arguments $args.
// Utilise Ajax si dispo, en ecrivant le resultat dans le innerHTML du noeud
// d'attribut  id = $action-$id (cf. AjaxSqueeze dans layer.js)
// Precise le charset de l'envoyeur avec la variable d'url var_ajaxcharset
// qui sert aussi a index.php de savoir que la requete est en Ajax.
// Attention, la redirection doit propager cette variable, 
// i.e. la mettre dans la 2e URL, et avant l'ancre de celle ci.

// http://doc.spip.org/@ajax_action_auteur
function ajax_action_auteur($action, $id, $script, $args='', $corps=false, $args_ajax='', $fct_ajax='')
{
	$ancre = "$action-" . intval($id);

	// Formulaire (POST)
	// methodes traditionnelle et ajax a unifier...
	if (is_string($corps)) {

		// Methode traditionnelle
		if ($_COOKIE['spip_accepte_ajax'] != 1) {
			return redirige_action_auteur($action,
				$id,
				$script,
				"$args#$ancre",
				$corps,
				"\nmethod='post'");
		}

		// Methode Ajax
		else {
			if ($args AND !$args_ajax) $args_ajax = "&$args";
			return redirige_action_auteur($action,
				$id,
				$action,
				"var_ajaxcharset=utf-8&script=$script$args_ajax",
				$corps,
				(" method='post'\nonsubmit="
				 . ajax_action_declencheur('this', $ancre, $fct_ajax)));
				 
		}
	}

	// Lien (GET)
	else {
		list($clic, $att) = $corps;

		$href = redirige_action_auteur($action,
			$id,
			$script,
			"$args#$ancre",
			false);

		if ($args AND !$args_ajax) $args_ajax = "&$args";

		$ajax = redirige_action_auteur($action,
			$id,
			$action,
			"var_ajaxcharset=utf-8&script=$script$args_ajax");

		if ($att) $clic = "\n<div$att>$clic</div>";
		return "<a href='$href'\nonclick="
		.  ajax_action_declencheur("\"$ajax\"", $ancre, $fct_ajax)
		. ">$clic</a>";
	}
}

function ajax_action_declencheur($request, $noeud, $fct_ajax='')
{
	return "'return AjaxSqueeze("
	. $request
	. ',"'
	. $noeud
	. '"'
	. (!$fct_ajax ? '' : ",$fct_ajax")
	. ")'";
}

function ajax_action_greffe($idom, $corps)
{
	return _request('var_ajaxcharset')
	? $corps
	: "\n<div id='$idom'$atts>$corps</div>";
}

function ajax_retour($corps)
{
	$c = $GLOBALS['meta']["charset"];
	header('Content-Type: text/html; charset='. $c);
	echo '<', "?xml version='1.0' encoding='", $c, "'?", ">\n", $corps;
	exit;
}

// http://doc.spip.org/@determine_upload
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

//
// retourne le statut d'un utilisateur authentifie en php_auth, false sinon
//
// http://doc.spip.org/@verifier_php_auth
function verifier_php_auth() {
	if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']
	&& !$GLOBALS['ignore_auth_http']) {
		$result = spip_query("SELECT * FROM spip_auteurs WHERE login=" . spip_abstract_quote($_SERVER['PHP_AUTH_USER']));

		$row = @spip_fetch_array($result);
		if ($row AND $row['source'] != 'ldap') {
		  if ($row['pass'] == md5($row['alea_actuel'] . $_SERVER['PHP_AUTH_PW'])) {
			$GLOBALS['auteur_session'] = $row;
			return $row['statut'];
		  } else return false;
		} else {
		  if (!$row AND !$GLOBALS['ldap_present'])
		    return false;
		  else {
			$f = charger_fonction('auth_ldap', 'inc', true);
			if ($f) {
			  $GLOBALS['auteur_session'] =  $f($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
			  return $GLOBALS['auteur_session']['statut'];
			}
		  }
		}
	} 
	return false;
}

//
// entete php_auth (est-encore utilise ?)
//
// http://doc.spip.org/@ask_php_auth
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

?>
