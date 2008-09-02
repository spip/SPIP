<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// http://doc.spip.org/@generer_action_auteur
function generer_action_auteur($action, $arg, $redirect="", $mode=false, $att='')
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	return $securiser_action($action, $arg, $redirect, $mode, $att);
}

// http://doc.spip.org/@redirige_action_auteur
function redirige_action_auteur($action, $arg, $ret, $gra='', $mode=false, $atts='') {

	$r = _DIR_RESTREINT . generer_url_ecrire($ret, $gra, true, true);

	return generer_action_auteur($action, $arg, $r, $mode, $atts);
}

// http://doc.spip.org/@redirige_action_post
function redirige_action_post($action, $arg, $ret, $gra, $corps, $att='') {
	return redirige_action_auteur($action, $arg, $ret, $gra, $corps, $att . " method='post'");
}

// Retourne un formulaire d'execution de $action sur $id,
// revenant a l'envoyeur $script d'arguments $args.
// Utilise Ajax si dispo, en ecrivant le resultat dans le innerHTML du noeud
// d'attribut  id = $action-$id (cf. AjaxSqueeze dans layer.js)

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
			return redirige_action_post($action,
				$id,
				$script,
				"$args#$ancre",
				$corps);
		}

		// Methode Ajax
		else {
			if ($args AND !$args_ajax) $args_ajax = "&$args";
			if ($GLOBALS['var_profile'])
				$args_ajax .= '&var_profile=1';
			return redirige_action_post($action,
				$id,
				$action,
				"script=$script$args_ajax",
				$corps,
				(" onsubmit="
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
		if (isset($GLOBALS['var_profile']))
			$args_ajax .= '&var_profile=1';

		$ajax = redirige_action_auteur($action,
			$id,
			$action,
			"script=$script$args_ajax");

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
		return redirige_action_post($action, $arg, $retour,
					($gra . '#' . $ancre),
				        $corps);
	} else { 

		if ($gra AND !$args_ajax) $args_ajax = "&$gra";
		if (isset($GLOBALS['var_profile']))
			$args_ajax .= '&var_profile=1';

		return redirige_action_post($action,
			$arg,
			$action,
			"script=$retour$args_ajax",
			$corps,
			" onsubmit=" . ajax_action_declencheur('this', $ancre));
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
// $fonction : denomination semantique du bloc, que l'on retouve en attribut class
// $id : id de l'objet concerne si il y a lieu ou "", sert a construire un identifiant unique au bloc ("fonction-id")
// http://doc.spip.org/@ajax_action_greffe
function ajax_action_greffe($fonction, $id, $corps)
{
	$idom = $fonction.(strlen($id)?"-$id":"");
	return _AJAX
		? "$corps"
		: "\n<div id='$idom' class='ajax-action $fonction'>$corps\n</div>\n";
}

// http://doc.spip.org/@ajax_retour
function ajax_retour($corps,$xml = true)
{
	if (isset($GLOBALS['transformer_xml']) OR $GLOBALS['exec'] == 'valider_xml') {
	 	$debut = _DOCTYPE_ECRIRE
		. "<html><head><title>Debug Spip Ajax</title></head>"
		.  "<body><div>\n\n"
		. "<!-- %%%%%%%%%%%%%%%%%%% Ajax %%%%%%%%%%%%%%%%%%% -->\n";

		$fin = '</div></body></html>';
	} else {

		if (isset($GLOBALS['tableau_des_temps'])) {
			include_spip('public/debug');
			$fin = chrono_requete($GLOBALS['tableau_des_temps']);
		} else $fin = '';

		$c = $GLOBALS['meta']["charset"];
		header('Content-Type: text/html; charset='. $c);
		$debut = $xml?'<' . "?xml version='1.0' encoding='" . $c . "'?" . ">\n":'';
	}
	if (count($GLOBALS['tableau_des_erreurs']) AND isset($_COOKIE['spip_admin'])) {
		find_in_path('debug.php','public/',true);
		$corps = affiche_erreurs_page($GLOBALS['tableau_des_erreurs']) . $corps;
	}

	echo $debut, $corps, $fin;
}

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

	if (!$GLOBALS['visiteur_session']['restreint'])
		return $repertoire;
	else
		return sous_repertoire($repertoire, $GLOBALS['visiteur_session']['login']);
}

//
//  Verif d'un utilisateur authentifie en php_auth
//

// http://doc.spip.org/@lire_php_auth
function lire_php_auth($user, $pw) {

	include_spip('base/abstract_sql');
	$row = sql_fetsel("*", "spip_auteurs", "login=" . sql_quote($user));

	if ($row AND $row['source'] != 'ldap')
		return ($row['pass'] == md5($row['alea_actuel'] . $pw)) ? $row : false;
	elseif (spip_connect_ldap()) {
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
		  $GLOBALS['visiteur_session'] = $r;
		  return $GLOBALS['visiteur_session']['statut'];
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
		ask_php_auth(_T('info_connexion_refusee'),
			     _T('login_login_pass_incorrect'),
			     _T('login_retour_site'),
			     "url=".rawurlencode($url),
			     _T('login_nouvelle_tentative'),
			     (strpos($url,_DIR_RESTREINT_ABS)!==false));
	}
}
?>
