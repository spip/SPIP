<?php

include(_FILE_CONNECT);
include_ecrire("inc_meta.php3");
include_ecrire("inc_session.php3");

global $balise_LOGIN_PUBLIC_collecte;
$balise_LOGIN_PUBLIC_collecte = array('url');

# retourner:
# 1. l'url collectee ci-dessus (args0) ou donnee en filtre (filtre0)
# 2. l'eventuel parametre de la balise (args1) fournie par calculer_balise_dynamique

function balise_LOGIN_PUBLIC_stat ($args, $filtres)
{
	return array($filtres[0] ? $filtres[0] : $args[0], $args[1]);
}

function balise_LOGIN_PUBLIC_dyn($cible, $login)
{
	return login_explicite($login, $cible,  'forum');
}

function login_explicite($login, $cible, $mode) {

	global $auteur_session, $clean_link;

	$clean_link->delVar('var_erreur');
	$clean_link->delVar('var_login');
	$action = $clean_link->getUrl();

	if ($cible) {
	  $cible = ereg_replace("[?&]var_erreur=[^&]*", '', $cible);
	  $cible = ereg_replace("[?&]var_login=[^&]*", '', $cible);
	} else {
	  if (ereg("[?&]url=([^&]*)", $action, $m))
	    $cible = urldecode($m[1]);
	  else
	    $cible = _DIR_RESTREINT ;
	}
	      
	include_ecrire("inc_session.php3");
	verifier_visiteur();

	if ($auteur_session AND 
	($auteur_session['statut']=='0minirezo' OR $auteur_session['statut']=='1comite')) {
		if (($cible != $action) &&  !headers_sent())
			redirige_par_entete($cible);
		return "<a href='$cible'>"._T('login_par_ici')."</a>\n";
	}
	return login_pour_tous($login ? $login : $GLOBALS['var_login'], $cible, '', $action, $mode);
}

// fonction aussi pour les forums sur abonnement

function login_pour_tous($login, $cible, $message, $action, $mode) {

  global $ignore_auth_http, $spip_admin, $php_module, $spip_lang_left;

	// en cas d'echec de cookie, inc_auth a renvoye vers spip_cookie qui
	// a tente de poser un cookie ; s'il n'est pas la, c'est echec cookie
	// s'il est la, c'est probablement un bookmark sur bonjour=oui,
	// et pas un echec cookie.
	if ($GLOBALS['var_echec_cookie'])
	  $echec_cookie = ($GLOBALS['spip_session'] != 'test_echec_cookie');
	$auth = ($echec_cookie AND $php_module AND !$ignore_auth_http) ?
	  'spip_cookie.php3' : '';
	$sinscrire = ((lire_meta("accepter_inscriptions") == "oui") OR
		      (($mode == 'forum') AND (
				     lire_meta("accepter_visiteurs") == "oui"
				     OR lire_meta('forums_publics') == 'abo')));
	// Le login est memorise dans le cookie d'admin eventuel
	if (!$login) {
		if (ereg("^@(.*)$", $spip_admin, $regs))
			$login = $regs[1];
	} else if ($login == '-1')
	  $login = '';

	$row = array();
	$erreur = '';
	if ($login) {
		$row = spip_query("SELECT * FROM spip_auteurs WHERE login='" .addslashes($login) ."'");
		$row =  spip_fetch_array($row);
		if ((!$row AND !$GLOBALS['ldap_present']) OR
		    ($row['statut'] == '5poubelle') OR 
		    (($row['source'] == 'spip') AND $row['pass'] == '')) {
			$erreur =  _T('login_identifiant_inconnu', array('login' => $login));
 			$row = array();
			$login = '';
			@spip_setcookie("spip_admin", "", time() - 3600);
		}
	}

	include_ecrire("inc_mail.php3");

	return array('formulaire_login', 0, 
		     array_merge(array_map('addslashes', $row),
				 array(
				       'action2' => ($login ? 'spip_cookie.php3' : $action),
				       'erreur' => $erreur,
				       'action' => $action,
				       'url' => $cible,
				       'auth' => $auth,
				       'mode' => $mode,
				       'oubli' => (tester_mail() ? 'spip_pass.php3?mode=oubli_pass' : ''),
				       'echec_cookie' => ($echec_cookie ? ' ' : ''),
				       'spip_lang_left' => $spip_lang_left,
				       'message' => ($message ? ' ' : ''),
				       'sinscrire' => ($sinscrire ? ' ': ''),
				       )
				 )
		     );

}

// Bouton duree de connexion

function filtre_rester_connecte($prefs) 
{
	$prefs = unserialize($row['prefs']);
	return $prefs['cnx'] == 'perma' ? ' checked="checked"' : '';
}

function retoursite($mode)
{
  return (($mode == 'forum') ? '' : (lire_meta('adresse_site'))) ;
}

function vide($a) {return $a ? '' : ' ';}

function choisir($t,$v,$f) {return $t ? $v : $f;}

function egal($a1,$a2) {return ($a1 == $a2) ? ' ' : '';}

?>
