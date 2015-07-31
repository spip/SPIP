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
		list($id_auteur, $pass) =  caracteriser_auteur();
	}
	$hash = _action_auteur("$action-$arg", $id_auteur, $pass, 'alea_ephemere');
	if (!is_string($mode))
	  return generer_url_action($action, "arg=$arg&id_auteur=$id_auteur&hash=$hash" . (!$redirect ? '' : ("&redirect=" . rawurlencode($redirect))), $mode);
	if ($redirect)
		$redirect = "\n\t\t<input name='redirect' type='hidden' value='$redirect' />";
	// Attention, JS n'aime pas le melange de param GET/POST
	return "\n<form action='" .
		generer_url_public('') .
		"'$att>\n\t<div>
		<input name='id_auteur' type='hidden' value='$id_auteur' />
		<input name='hash' type='hidden' value='$hash' />
		<input name='action' type='hidden' value='$action' />
		<input name='arg' type='hidden' value='$arg' />" .
		$redirect .  
		$mode .
		"\n\t</div>\n</form>\n";
}

function redirige_action_auteur($action, $arg, $ret, $gra, $mode=false, $atts='') {
	if (!$redirect = _request('redirect')) {
		$gra = preg_replace(',^&,', '', $gra);
		$redirect = generer_url_ecrire($ret ? $ret : _request('exec'),
			$gra, '&', _DIR_RESTREINT_ABS);
	}

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

function ajax_action_auteur($action, $id, $corps, $script, $args_ajax, $args)
{

	// Methode traditionnelle
	if ($_COOKIE['spip_accepte_ajax'] != 1) {
		if (is_string($corps)) {
			return redirige_action_auteur($action,
				$id,
				$script,
				$args,
				$corps,
				"\nmethod='post'");
		} else {
			list($clic, $class) = $corps;
			$href = redirige_action_auteur($action,
				$id,
				$script,
				$args,
				null,
				"\nmethod='post'");
			return "<div class='$class'><a href='$href'>$clic</a></div>";
		}
	}

	//
	// Ajax
	//
	$pere = '"' . "$action-" . intval($id) . '"';

	if (is_string($corps)) {
		return redirige_action_auteur($action,
				$id,
				$action,
				"var_ajax=1&script=$script$args_ajax",
				$corps,
				"\nmethod='post' onsubmit='return AjaxSqueeze(this, $pere)'");
	} else {
		list($clic, $class) = $corps;
		$href = redirige_action_auteur($action,
				$id,
				$action,
				"var_ajax=1&script=$script$args_ajax");
		return "<div class='$class' onclick='AjaxSqueeze(\"$href\",$pere)'>$clic</div>";
	}
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

//
// retourne le statut d'un utilisateur authentifie en php_auth, false sinon
//
function verifier_php_auth() {
	if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']
	&& !$GLOBALS['ignore_auth_http']) {
		$result = spip_query("SELECT * FROM spip_auteurs WHERE login=" . spip_abstract_quote($_SERVER['PHP_AUTH_USER']));

		if (!$GLOBALS['db_ok'])	return false;

		$row = spip_fetch_array($result);
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

?>
