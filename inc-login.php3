<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_LOGIN")) return;
define("_INC_LOGIN", "1");

include_ecrire("inc_meta.php3");
include_ecrire("inc_session.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_texte.php3");

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
		return "<a href='$cible'>"._T('login_par_ici')."</a>\n";
		;
	}
	return login_pour_tous($cible, $prive, '', $action, $prive?'redac':'forum');
}


// fonction aussi pour le forums sur abonnement

function login_pour_tous($cible, $prive, $message, $action, $mode) {
  $pass_popup ="href='spip_pass.php3?mode=$mode' target='spip_pass'
 onclick=\"javascript:window.open('spip_pass.php3?mode=$mode', 'spip_pass', 'scrollbars=yes, resizable=yes, width=480, height=450'); return false;\"";

	global $ignore_auth_http;
	global $spip_admin;
	global $php_module;

	// en cas d'echec de cookie, inc_auth a renvoye vers spip_cookie qui
	// a tente de poser un cookie ; s'il n'est pas la, c'est echec cookie
	// s'il est la, c'est probablement un bookmark sur bonjour=oui,
	// et pas un echec cookie.
	if ($GLOBALS['var_echec_cookie'])
	  $echec_cookie = ($GLOBALS['spip_session'] != 'test_echec_cookie');

	$login = $GLOBALS['var_login'];
	// Le login est memorise dans le cookie d'admin eventuel
	if (!$login) {
		if (ereg("^@(.*)$", $spip_admin, $regs))
			$login = $regs[1];
	} else if ($login == '-1')
	  $login = '';
		
	$source_auteur = false ;
	// quels sont les aleas a passer ?
	if ($login) {
		$statut_login = 0; // statut inconnu
		$login = addslashes($login);
		$query = "SELECT * FROM spip_auteurs WHERE login='$login'";
		$row = spip_fetch_array(spip_query($query));
		if ($row) {
		  $source_auteur = ($row['source'] == 'spip') ;
		  if ($row['statut'] == '5poubelle' OR ((!$source_auteur) AND $row['pass'] == '')) {
				$statut_login = -1; // refus
			} else {

				$statut_login = 1; // login connu

				// Quels sont les aleas a passer pour le javascript ?
				if ($source_auteur) {
					$alea_actuel = $row['alea_actuel'];
					$alea_futur = $row['alea_futur'];
				}

				// Bouton duree de connexion
				if ($prefs = unserialize($row['prefs'])) {
					
					$rester_checked = ($prefs['cnx'] == 'perma' ? ' checked=\'checked\'':'');
				}
			}
		}

		// login inconnu (sauf LDAP) ou refuse
		if ($statut_login == -1 OR ($statut_login == 0 AND !$GLOBALS['ldap_present'])) {
			$erreur = _T('login_identifiant_inconnu', array('login' => htmlspecialchars($login)));
 			$login = '';
			@spip_setcookie("spip_admin", "", time() - 3600);
		}
	}

	if ($echec_cookie) {
		$message = '<h3 class="spip">' .
		  (_T('erreur_probleme_cookie')) .
		  '</h3><b>' .
		  _T('login_cookie_oblige')."</b> " .
		  _T('login_cookie_accepte')."<p />\n";
	}
	else { if ($message)
	    $message = "<br />" . 
	      _T("forum_vous_enregistrer") . 
	      " <a $pass_popup>" .
	      _T("forum_vous_inscrire") .
	      "</a><p />\n" ;
	}

	$res = $message .
	  '<div style="font-family: Verdana,arial,helvetica,sans-serif; font-size: 12px;">';

# Affichage du formulaire de login avec un challenge MD5 en javascript
# si jaja actif, on affiche le login en 'dur', et on le passe en champ hidden
# sinon , le login est modifiable (puisque le challenge n'est pas utilise)

	if ($login) {
		$session = "<br /><br /><label><b>"._T('login_login2')."</b><br /></label>\n<input type='text' name='session_login' class='forml' value=\"$login\" size='40' />";

		$res .= (!$source_auteur ? '' : http_script('', _DIR_INCLUDE . 'md5.js')) .
		  "<form name='form_login' action='spip_cookie.php3' method='post'" .
		  (!$source_auteur ?  '' : 
		   (" onSubmit='if (this.session_password.value) {
				this.session_password_md5.value = calcMD5(\"$alea_actuel\" + this.session_password.value);
				this.next_session_password_md5.value = calcMD5(\"$alea_futur\" + this.session_password.value);
				this.session_password.value = \"\";
			}'")) .
		  ">\n<input type='hidden' name='session_login_hidden' value='$login' />\n" .
		  "<div class='spip_encadrer' style='text-align:".$GLOBALS["spip_lang_left"].";'>\n" .
		  (!$erreur ? '' : "<div class='reponse_formulaire'><b>$erreur</b></div>\n") .
		  (!$source_auteur ? $session :
		   http_script("document.write('".addslashes(_T('login_login'))." <b>$login</b><br /><a href=\"spip_cookie.php3?cookie_admin=non&amp;var_url=".rawurlencode($action)."\"><font size=\"2\">["._T('login_autre_identifiant')."]</font></a>');",
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
		  "<input type='hidden' name='session_password_md5' value='' />\n" .
		  "<input type='hidden' name='next_session_password_md5' value='' />\n" .
		  "<input type='hidden' name='var_url' value='$cible' />\n" .
		  "<div align='right'><input type='submit' class='spip_bouton' value='"._T('bouton_valider')."' /></div>\n</div></form>";
			}
	else { // demander seulement le login
		$action = quote_amp($action);
		$res .= 
		"<form name='form_login' action='$action' method='post'>\n" .
		  "<div class='spip_encadrer' style='text-align:".$GLOBALS["spip_lang_left"].";'>";
		if ($erreur) $res .= "<span style='color:red;'><b>$erreur</b></span><p />";
		$res .=
		  "<label><b>"._T('login_login2')."</b><br /></label>" .
		  "<input type='text' name='var_login' class='forml' value=\"\" size='40' />\n" .
		  "<input type='hidden' name='var_url' value='$cible' />\n" .
		  "<div align='right'><input type='submit' class='spip_bouton' value='"._T('bouton_valider')."'/></div>\n</div></form>";
	}

	// Gerer le focus
	$res .= http_script($login ?
			 'document.form_login.session_password.focus();' :
			 'document.form_login.var_login.focus();');

	if ($echec_cookie AND $php_module AND !$ignore_auth_http) {
		$res .= "<form action='spip_cookie.php3' method='get'><fieldset>\n<p>"
			. _T('login_preferez_refuser')
			. "<input type='hidden' name='essai_auth_http' value='oui'/>\n"
			. "<input type='hidden' name='var_url' value='$cible'/>\n"
			. "<div align='right'><input type='submit' class='spip_bouton' value='"._T('login_sans_cookiie')."'/></div>\n"
			.  "</fieldset></form>\n";
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

	return $res .  "</div></div>";
}

?>
