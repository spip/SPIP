<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/meta');

// http://doc.spip.org/@generer_action_auteur
function generer_action_auteur($action, $arg, $redirect="", $mode=false, $att='')
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	return $securiser_action($action, $arg, $redirect, $mode, $att);
}

// http://doc.spip.org/@redirige_action_auteur
function redirige_action_auteur($action, $arg, $ret, $gra='', $mode=false, $atts='') {

	$r = generer_url_ecrire($ret, $gra, true, _DIR_RESTREINT_ABS);

	return generer_action_auteur($action, $arg, $r, $mode, $atts);
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
	if (strpos($args,"#")===FALSE)
		$ancre = "$action-" . intval($id);
	else {
		$ancre = explode("#",$args);
		$args = $ancre[0];
		$ancre = $ancre[1];
	}

	// Formulaire (POST)
	// methodes traditionnelle et ajax a unifier...
	if (is_string($corps)) {

		// Methode traditionnelle
		if (_SPIP_AJAX !== 1) {
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

		$cli = array_shift($corps);
		return "<a href='$href'\nonclick="
		.  ajax_action_declencheur($ajax, $ancre, $fct_ajax)
		. ">"
		. (!$corps ?  $cli : ("\n<span" . $corps[0] . ">$cli</span>"))
		. "</a>";
	}
}

// Comme ci-dessus, mais reduit au cas POST et on fournit le bouton Submit.
// 
// http://doc.spip.org/@ajax_action_post
function ajax_action_post($action, $arg, $retour, $gra, $corps, $clic='', $atts_i='', $atts_span = "", $args_ajax='')
{
	global $spip_lang_right;

	if (strpos($gra,"#")===FALSE) {
	  // A etudier: prendre systematiquement arg en trancodant les \W
		$n = intval($arg);
		$ancre = "$action-" . ($n ? $n : $arg);
	} else {
		$ancre = explode("#",$gra);
		$args = $ancre[0];
		$ancre = $ancre[1];
	}

	if (!$atts_i) 
		$atts_i = " class='fondo' style='float: $spip_lang_right'";

	if (is_array($clic)) {
		$submit = "";
		$atts_i .= "\nonclick='AjaxNamedSubmit(this)'";
		foreach($clic as $n => $c)
		  $submit .= "\n<input type='submit' name='$n' value='$c' $atts_i />";
	} else {
		if (!$clic)  $clic =  _T('bouton_valider');
		$submit = "<input type='submit' value='$clic' $atts_i />";
	}
	$corps = "<div>"
	  . $corps 
	  . "<span"
	  . $atts_span
	  . ">"
	  . $submit
	  . "</span></div>";

	if (_SPIP_AJAX !== 1) {
	  return redirige_action_auteur($action, $arg, $retour,
					($gra . '#' . $ancre),
				        $corps ,
					"\nmethod='post'");
	} else { 

		if ($gra AND !$args_ajax) $args_ajax = "&$gra";

		return redirige_action_auteur($action,
			$arg,
			$action,
			"var_ajaxcharset=utf-8&script=$retour$args_ajax",
			$corps ,
			" method='post' onsubmit=" . ajax_action_declencheur('this', $ancre));
	}

}

//
// Attention pour que Safari puisse manipuler cet evenement
// il faut onsubmit="return AjaxSqueeze(x,'truc',...)"
// et non pas onsubmit='return AjaxSqueeze(x,"truc",...)'
//
// http://doc.spip.org/@ajax_action_declencheur
function ajax_action_declencheur($request, $noeud, $fct_ajax='') {
	if (strpos($request, 'this') !== 0) 
		$request = "'".$request."'";

	return '"return AjaxSqueeze('
	. $request
	. ",'"
	. $noeud
	. "',"
	  . ($fct_ajax ? $fct_ajax : "''")
	. ',event)"';
}

// Place un element HTML dans une div nommee,
// sauf si c'est un appel Ajax car alors la div y est deja 
// Rajoute <br /> en debut pour que l'animation AjaxSqueeze soit visible

// http://doc.spip.org/@ajax_action_greffe
function ajax_action_greffe($idom, $corps, $br='')
{
	return _request('var_ajaxcharset')
	? "$br$corps"
	: "\n<div id='$idom' style='padding-top:1px;'>$br$corps\n</div>\n";
}

// http://doc.spip.org/@ajax_retour
function ajax_retour($corps,$xml = true)
{
	if (isset($GLOBALS['transformer_xml'])) {
	 	echo _DOCTYPE_ECRIRE
		. "<html><head><title>Debug Spip Ajax</title></head>"
		.  "<body>\n\n"
		. "<!-- %%%%%%%%%%%%%%%%%%% Ajax %%%%%%%%%%%%%%%%%%% -->\n"
		. $corps
		. '</body></html>';
		return;
	}

	$c = $GLOBALS['meta']["charset"];
	header('Content-Type: text/html; charset='. $c);
	$c = $xml?'<' . "?xml version='1.0' encoding='" . $c . "'?" . ">\n":'';
	echo $c, $corps;
	exit;
}

/* specifique FF+FB
// http://doc.spip.org/@ajax_debug_retour
function ajax_debug_retour($corps, $c)
{
	$sax = charger_fonction('sax', 'inc');
	$corps = $sax($corps);
	if ($GLOBALS['xhtml_error']) {
	  spip_log("ajax_retour " .  $GLOBALS['REQUEST_URI'] . $GLOBALS['xhtml_error']);
	  $debut = "<script type='text/javascript'>console.log('";
	  $fin = "')</script>\n";
	  echo $GLOBALS['xhtml_error']
	  . $debut
	  . join("$fin$debut", split("\n", addslashes($corps)))
	  . $fin;
	  exit;
	}
}
*/

// http://doc.spip.org/@determine_upload
function determine_upload($type='') {

	if (!autoriser('chargerftp')
	OR $type == 'logos') # on ne le permet pas pour les logos
		return false;

	$repertoire = _DIR_TRANSFERT;
	if(!@is_dir($repertoire)) {
		$repertoire = str_replace(_DIR_TMP, '', $repertoire);
		$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
	}

	if ($GLOBALS['connect_toutes_rubriques'])
		return $repertoire;
	else
		return sous_repertoire($repertoire, $GLOBALS['connect_login']);
}

//
//  Verif d'un utilisateur authentifie en php_auth
//

// http://doc.spip.org/@lire_php_auth
function lire_php_auth($user, $pw) {

	$row = spip_query("SELECT * FROM spip_auteurs WHERE login=" . _q($user));

	$row = spip_fetch_array($row);
	if ($row AND $row['source'] != 'ldap')
		return ($row['pass'] == md5($row['alea_actuel'] . $pw)) ? $row : false;
	elseif ($GLOBALS['ldap_present']) {
		$auth_ldap = charger_fonction('auth_ldap', 'inc', true);
		if ($auth_ldap) return $auth_ldap($user, $pw);
	}
	return false;
}


// http://doc.spip.org/@verifier_php_auth
function verifier_php_auth() {

	if (@$_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']
	&& !@$GLOBALS['ignore_auth_http']) {
		if ($r = lire_php_auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
		  $GLOBALS['auteur_session'] = $r;
		  return $GLOBALS['auteur_session']['statut'];
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
	$ici = generer_url_ecrire();
	echo "<b>$pb</b><p>$raison</p>[<a href='$ici'>$retour</a>] ";
	if ($url) {
		echo "[<a href='", generer_url_action('cookie',"essai_auth_http=oui&$url"), "'>$re</a>]";
	}
	
	if ($lien)
		echo " [<a href='$ici'>"._T('login_espace_prive')."</a>]";
	exit;
}

// Verifie si le visiteur est authentifie en http,
// sinon lui renvoie une demande (status 401)
// http://doc.spip.org/@auth_http
function auth_http($url) {

	if (verifier_php_auth())
		redirige_par_entete($url);
	else {
		ask_php_auth(_T('login_connexion_refusee'),
			     _T('login_login_pass_incorrect'),
			     _T('login_retour_site'),
			     "url=".rawurlencode($url),
			     _T('login_nouvelle_tentative'),
			     (strpos($url,_DIR_RESTREINT_ABS)!==false));
		exit;
	}
}


?>
