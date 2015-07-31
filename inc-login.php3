<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_LOGIN")) return;
define("_INC_LOGIN", "1");


include_ecrire ("inc_meta.php3");
include_ecrire ("inc_session.php3");
include_ecrire ("inc_filtres.php3");
include_ecrire ("inc_texte.php3");
include_local ("inc-formulaires.php3");

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

	$cible = ereg_replace("[?&]var_erreur=[^&]*", '', $cible);
	$cible = ereg_replace("[?&]var_url[^&]*", '', $cible);

	global $clean_link;
	$clean_link->delVar('var_erreur');
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
	login_pour_tous($cible, $prive, '', $action);
}


// fonction aussi pour le forums sur abonnement

function login_pour_tous($cible, $prive, $message, $action) {
  $pass_popup ='href="spip_pass.php3" target="spip_pass" onclick="'
                       . "javascript:window.open('spip_pass.php3', 'spip_pass', 'scrollbars=yes, resizable=yes, width=480, height=450'); return false;\"";

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
		$result = spip_query($query);
		if ($row = spip_fetch_array($result)) {
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

	if ($echec_cookie == "oui") {
	  echo "<div><h3 class='spip'>",
	    (_T('erreur_probleme_cookie')),
	    '</h3><div style="font-family: Verdana,arial,helvetica,sans-serif; font-size: 12px;"><p /><b>' .
	    _T('login_cookie_oblige')."</b> " .
	    _T('login_cookie_accepte')."\n";
	}
	else {
		echo '<div><div style="font-family: Verdana,arial,helvetica,sans-serif; font-size: 12px;">',
		  (!$message ? '' :
		   ("<br />" . 
		    _T("forum_vous_enregistrer") . 
		    " <a $pass_popup>" .
		    _T("forum_vous_inscrire") .
		    "</a><p />\n")) ;
		   
	}

	// javascript pour le focus
	if ($login)
		$js_focus = 'document.form_login.session_password.focus();';
	else
		$js_focus = 'document.form_login.var_login.focus();';


	if ($login) {
		// Affiche formulaire de login en incluant le javascript MD5
		$flag_challenge_md5 = ($source_auteur == 'spip');
		$src = _DIR_RESTREINT_ABS . 'md5.js';

		if ($flag_challenge_md5) echo "<script type=\"text/javascript\" src=\"$src\"></script>\n";
		echo "<form name='form_login' action='spip_cookie.php3' method='post'";
		if ($flag_challenge_md5) echo " onSubmit='if (this.session_password.value) {
				this.session_password_md5.value = calcMD5(\"$alea_actuel\" + this.session_password.value);
				this.next_session_password_md5.value = calcMD5(\"$alea_futur\" + this.session_password.value);
				this.session_password.value = \"\";
			}'";
		echo ">\n";
		echo "<div class='spip_encadrer' style='text-align:".$GLOBALS["spip_lang_left"].";'>";
		if ($erreur) echo "<div class='reponse_formulaire'><b>$erreur</b></div><p>";

		if ($flag_challenge_md5) {
			// si jaja actif, on affiche le login en 'dur', et on le passe en champ hidden
			echo "<script type=\"text/javascript\"><!--\n" .
			  "document.write('".addslashes(_T('login_login'))." <b>$login</b><br /><a href=\"spip_cookie.php3?cookie_admin=non&amp;url=".rawurlencode($action)."\"><font size=\"2\">["._T('login_autre_identifiant')."]</font></a>');\n" .
				"//--></script>\n";
			echo "<input type='hidden' name='session_login_hidden' value='$login' />";

			// si jaja inactif, le login est modifiable (puisque le challenge n'est pas utilise)
			echo "<noscript>";
			echo "<font face='Georgia, Garamond, Times, serif' size='3'>";
			echo _T('login_non_securise')." <a href=\"".quote_amp($action)."\">"._T('login_recharger')."</a>.</font>\n";
		}
		echo "<label><b>"._T('login_login2')."</b><br /></label>";
		echo "<input type='text' name='session_login' class='forml' value=\"$login\" size='40' />\n";
		if ($flag_challenge_md5) echo "</noscript>\n";

		echo "<p />\n<label><b>"._T('login_pass2')."</b><br /></label>";
		echo "<input type='password' name='session_password' class='forml' value=\"\" size='40' />\n";
		echo "<input type='hidden' name='essai_login' value='oui' />\n";

		echo "<br />&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='session_remember' value='oui' id='session_remember'$rester_checked /> ";
		echo "<label for='session_remember'>"._T('login_rester_identifie')."</label>";

		echo "<input type='hidden' name='url' value='$cible' />\n";
		echo "<input type='hidden' name='session_password_md5' value='' />\n";
		echo "<input type='hidden' name='next_session_password_md5' value='' />\n";
		echo "<div align='right'><input type='submit' class='spip_bouton' value='"._T('bouton_valider')."' /></div>\n";
		echo "</div>";
		echo "</form>";
	}
	else { // demander seulement le login
		$action = quote_amp($action);
		echo "<form name='form_login' action='$action' method='post'>\n";
		echo "<div class='spip_encadrer' style='text-align:".$GLOBALS["spip_lang_left"].";'>";
		if ($erreur) echo "<span style='color:red;'><b>$erreur</b></span><p />";
		echo "<label><b>"._T('login_login2')."</b><br /></label>";
		echo "<input type='text' name='var_login' class='forml' value=\"\" size='40' />\n";

		echo "<input type='hidden' name='var_url' value='$cible' />\n";
		echo "<div align='right'><input type='submit' class='spip_bouton' value='"._T('bouton_valider')."'/></div>\n";
		echo "</div>";
		echo "</form>";
	}

	// Gerer le focus
	echo "<script type=\"text/javascript\"><!--\n" . $js_focus . "\n//--></script>\n";

	if ($echec_cookie == "oui" AND $php_module AND !$ignore_auth_http) {
		echo "<form action='spip_cookie.php3' method='get'>";
		echo "<fieldset>\n<p>";
		echo _T('login_preferez_refuser')." \n";
		echo "<input type='hidden' name='essai_auth_http' value='oui'/> ";
		echo "<input type='hidden' name='url' value='$cible'/>\n";
		echo "<div align='right'><input type='submit' class='spip_bouton' value='"._T('login_sans_cookiie')."'/></div>\n";
		echo "</fieldset></form>\n";
	}

	echo "\n<div align='center' style='font-size: 12px;' >"; // debut du pied de login

	if ((lire_meta("accepter_inscriptions") == "oui") OR
	    (!$prive AND (lire_meta('forums_publics') == 'abo')))
		echo " [<a $pass_popup>" . _T('login_sinscrire').'</a>]';

	// bouton oubli de mot de passe
	include_ecrire ("inc_mail.php3");
	if (tester_mail()) {
		echo ' [<a href="spip_pass.php3?oubli_pass=oui" target="spip_pass" onclick="'
			."javascript:window.open(this.href, 'spip_pass', 'scrollbars=yes, resizable=yes, width=480, height=280'); return false;\">"
			._T('login_motpasseoublie').'</a>]';
	}
	// Bouton retour au site public

	if ($prive) {
	  $url_site = lire_meta('adresse_site');
	  if (!$url_site) $url_site = "./";
	  echo " [<a href='$url_site'>"._T('login_retoursitepublic')."</a>]";
	}

	echo "</div>\n";

	echo  "</div></div>";

}

?>
