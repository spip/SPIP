<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_LOGIN")) return;
define("_INC_LOGIN", "1");

include_ecrire("inc_meta.php3");
include_ecrire("inc_session.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_texte.php3");

// gerer l'auth http
function auth_http($url, $essai_auth_http) {
	$lien = " [<a href='" . _DIR_RESTREINT_ABS . "'>"._T('login_espace_prive')."</a>]";
	if ($essai_auth_http == 'oui') {
		include_ecrire('inc_session.php3');
		if (!verifier_php_auth()) {
		  $url = quote_amp(urlencode($url));
			$page_erreur = "<b>"._T('login_connexion_refusee')."</b><p />"._T('login_login_pass_incorrect')."<p />[<a href='./'>"._T('login_retour_site')."</a>] [<a href='spip_cookie.php3?essai_auth_http=oui&amp;url=$url'>"._T('login_nouvelle_tentative')."</a>]";
			if (ereg(_DIR_RESTREINT_ABS, $url))
			  $page_erreur .= $lien;
			ask_php_auth($page_erreur);
		}
		else
			redirige_par_entete($url);
	}
	// si demande logout auth_http
	else if ($essai_auth_http == 'logout') {
		include_ecrire('inc_session.php3');
		ask_php_auth("<b>"._T('login_deconnexion_ok')."</b><p />"._T('login_verifiez_navigateur')."<p />[<a href='./'>"._T('login_retour_public')."</a>] [<a href='spip_cookie.php3?essai_auth_http=oui&amp;redirect=ecrire'>"._T('login_test_navigateur')."</a>] $lien");
		exit;
	}
}

// fonction pour les balises #LOGIN_*

function login($cible, $prive = 'prive') {

	global $auteur_session;
	global $clean_link;

	$clean_link->delVar('var_erreur');

	if (!$cible)
	  $cible = $prive ? _DIR_RESTREINT : $clean_link->getUrl();
	else
	  $cible = ereg_replace("[?&]var_erreur=[^&]*", '', $cible);

	$cible = ereg_replace("[?&]var_url[^&]*", '', $cible);
	$clean_link->delVar('var_login');
	$action = urldecode($clean_link->getUrl());

	include_ecrire("inc_session.php3");
	verifier_visiteur();

	if ($auteur_session AND 
	($auteur_session['statut']=='0minirezo' OR $auteur_session['statut']=='1comite')) {
		if (($cible != $action) &&  !headers_sent())
			redirige_par_entete($cible);
		echo "<a href='$cible'>"._T('login_par_ici')."</a>\n";
		return;
	}
	echo login_pour_tous($cible, $prive, '', $action, $prive?'redac':'forum');
}


// fonction aussi pour le forums sur abonnement

function login_pour_tous($cible, $prive, $message, $action, $mode) {
  $pass_popup ="href='spip_pass.php3?mode=$mode' target='spip_pass'
 onclick=\"javascript:window.open('spip_pass.php3?mode=$mode', 'spip_pass', 'scrollbars=yes, resizable=yes, width=480, height=450'); return false;\"";

	global $ignore_auth_http;
	global $spip_admin;
	global $php_module;

	$login = $GLOBALS['var_login'];
	// Le login est memorise dans le cookie d'admin eventuel
	if (!$login) {
		if (ereg("^@(.*)$", $spip_admin, $regs))
			$login = $regs[1];
	} else if ($login == '-1')
		$login = '';

	$flag_autres_sources = $GLOBALS['ldap_present'];
	// en cas d'echec de cookie, inc_auth a renvoye vers spip_cookie qui
	// a tente de poser un cookie ; s'il n'est pas la, c'est echec cookie
	// s'il est la, c'est probablement un bookmark sur bonjour=oui,
	// et pas un echec cookie.
	if ($GLOBALS['var_echec_cookie'])
	  $echec_cookie = ($GLOBALS['spip_session'] != 'test_echec_cookie');


	// quels sont les aleas a passer ?
	if ($login) {
		$statut_login = 0; // statut inconnu
		$login = addslashes($login);
		$query = "SELECT * FROM spip_auteurs WHERE login='$login'";
		$row = spip_fetch_array(spip_query($query));
		if ($row) {
		  if ($row['statut'] == '5poubelle' OR ($row['source'] == 'spip' AND $row['pass'] == '')) {
				$statut_login = -1; // refus
			} else {

				$statut_login = 1; // login connu

				// Quels sont les aleas a passer pour le javascript ?
				if ($row['source'] == 'spip') {
					$id_auteur = $row['id_auteur'];
					$source_auteur = $row['source'];
					$alea_actuel = $row['alea_actuel'];
					$alea_futur = $row['alea_futur'];
				}

				// Bouton duree de connexion
				if ($row['prefs']) {
					$prefs = unserialize($row['prefs']);
					$rester_checked = ($prefs['cnx'] == 'perma' ? ' checked=\'checked\'':'');
				}
			}
		}

		// login inconnu (sauf LDAP) ou refuse
		if ($statut_login == -1 OR ($statut_login == 0 AND !$flag_autres_sources)) {
			$erreur = _T('login_identifiant_inconnu', array('login' => htmlspecialchars($login)));
 			$login = '';
			@spip_setcookie("spip_admin", "", time() - 3600);
		}
	}

	if ($echec_cookie) {
		$res = "<div><h3 class='spip'>" .
		(_T('erreur_probleme_cookie')) .
		'</h3><div style="font-family: Verdana, arial,helvetica,sans-serif; font-size: 12px;"><p /><b>' .
		_T('login_cookie_oblige')."</b> " .
		_T('login_cookie_accepte')."\n";
	}
	else {
		$res = '<div><div style="font-family: Verdana,arial,helvetica,sans-serif; font-size: 12px;">' .
		(!$message ? '' :
			("<br />" . 
			_T("forum_vous_enregistrer") . 
			" <a $pass_popup>" .
			_T("forum_vous_inscrire") .
			"</a><p />\n")) ;
	}

# Affichage du formulaire de login avec un challenge MD5 en javascript
# si jaja actif, on affiche le login en 'dur', et on le passe en champ hidden
# sinon , le login est modifiable (puisque le challenge n'est pas utilise)

	if ($login) {

		$session = "<br /><br /><label><b>"._T('login_login2')."</b><br /></label>\n<input type='text' name='session_login' class='forml' value=\"$login\" size='40' />";
		if ($source_auteur != 'spip') 
			$challenge = '';
		else {
			$challenge = 
		  (" onSubmit='if (this.session_password.value) {
				this.session_password_md5.value = calcMD5(\"$alea_actuel\" + this.session_password.value);
				this.next_session_password_md5.value = calcMD5(\"$alea_futur\" + this.session_password.value);
				this.session_password.value = \"\";
			}'");
			$res .= http_script('', _DIR_INCLUDE . 'md5.js');
		}
		$res .= "<form name='form_login' action='spip_cookie.php3' method='post'" .
		  $challenge .
		  ">\n" .
		  "<input type='hidden' name='session_login_hidden' value='$login' />\n" .
		  "<div class='spip_encadrer' style='text-align:".$GLOBALS["spip_lang_left"].";'>\n" .
		  (!$erreur ? '' : "<div class='reponse_formulaire'><b>$erreur</b></div>\n") .
		  (!$challenge ? $session :
		   http_script("document.write('".addslashes(_T('login_login'))." <b>$login</b><br /><a href=\"spip_cookie.php3?cookie_admin=non&amp;url=".rawurlencode($action)."\"><font size=\"2\">["._T('login_autre_identifiant')."]</font></a>');",
			       '',
				"<font face='Georgia, Garamond, Times, serif' size='3'>" .
				_T('login_non_securise') .
			       "\n<a href=\"".quote_amp($action)."\">"._T('login_recharger')."</a>.\n</font>$session")) .
		  "\n<p /><label><b>"._T('login_pass2')."</b><br /></label>" .
		  "<input type='password' name='session_password' class='forml' value=\"\" size='40' />\n" .
		  "<input type='hidden' name='essai_login' value='oui' />\n" .
		  "<br />&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='session_remember' value='oui' id='session_remember'$rester_checked /> " .
		  "<label for='session_remember'>" .
		  _T('login_rester_identifie') .
		  "</label>" .
		  "<input type='hidden' name='url' value='$cible' />\n" .
		  "<input type='hidden' name='session_password_md5' value='' />\n" .
		  "<input type='hidden' name='next_session_password_md5' value='' />\n" .
		  "<div align='right'><input type='submit' class='spip_bouton' value='"._T('bouton_valider')."' /></div>\n" .
		  "</div>" .
		  "</form>";
	}
	else { // demander seulement le login
		$action = quote_amp($action);
		$res .= "<form name='form_login' action='$action' method='post'>\n" .
		  "<div class='spip_encadrer' style='text-align:".$GLOBALS["spip_lang_left"].";'>";
		if ($erreur) $res .= "<span style='color:red;'><b>$erreur</b></span><p />";
		$res .=
		  "<label><b>"._T('login_login2')."</b><br /></label>" .
		  "<input type='text' name='var_login' class='forml' value=\"\" size='40' />\n" .
		  "<input type='hidden' name='var_url' value='$cible' />\n" .
		  "<div align='right'><input type='submit' class='spip_bouton' value='"._T('bouton_valider')."'/></div>\n" .
		  "</div>" .
		  "</form>";
	}

	// Gerer le focus
	$res .= http_script($login ?
			 'document.form_login.session_password.focus();' :
			 'document.form_login.var_login.focus();');

	if ($echec_cookie AND $php_module AND !$ignore_auth_http) {
		$res .= "<form action='spip_cookie.php3' method='get'>";
		$res .= "<fieldset>\n<p>";
		$res .= _T('login_preferez_refuser')." \n";
		$res .= "<input type='hidden' name='essai_auth_http' value='oui'/> ";
		$res .= "<input type='hidden' name='url' value='$cible'/>\n";
		$res .= "<div align='right'><input type='submit' class='spip_bouton' value='"._T('login_sans_cookiie')."'/></div>\n";
		$res .= "</fieldset></form>\n";
	}

	$res .= "\n<div align='center' style='font-size: 12px;' >"; // debut du pied de login

	if ((lire_meta("accepter_inscriptions") == "oui") OR
		(!$prive AND (
			lire_meta("accepter_visiteurs") == "oui"
			OR lire_meta('forums_publics') == 'abo'
			)))
		$res .= " [<a $pass_popup>" . _T('login_sinscrire').'</a>]';

	// bouton oubli de mot de passe
	include_ecrire("inc_mail.php3");
	if (tester_mail()) {
		$res .= ' [<a href="spip_pass.php3?mode=oubli_pass" target="spip_pass" onclick="'
			."javascript:window.open(this.href, 'spip_pass', 'scrollbars=yes, resizable=yes, width=480, height=280'); return false;\">"
			._T('login_motpasseoublie').'</a>]';
	}
	// Bouton retour au site public

	if ($prive) {
	  $url_site = lire_meta('adresse_site');
	  if (!$url_site) $url_site = "./";
	  $res .= " [<a href='$url_site'>"._T('login_retoursitepublic')."</a>]";
	}

	$res .= "</div>\n";

	return $res .  "</div></div>";

}

?>
