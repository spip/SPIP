<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_LOGIN")) return;
define("_INC_LOGIN", "1");


include_ecrire ("inc_meta.php3");
//include_ecrire ("inc_presentation.php3");
include_ecrire ("inc_session.php3");
include_ecrire ("inc_filtres.php3");
include_ecrire ("inc_texte.php3");
include_local ("inc-formulaires.php3");

// gerer l'auth http
function auth_http($cible, $essai_auth_http) {
	if ($essai_auth_http == 'oui') {
		include_ecrire('inc_session.php3');
		if (!verifier_php_auth()) {
			$url = urlencode($cible->getUrl());
			$page_erreur = "<b>"._T('login_connexion_refusee')."</b><p>"._T('login_login_pass_incorrect')."<p>[<a href='./'>"._T('login_retour_site')."</a>] [<a href='./spip_cookie.php3?essai_auth_http=oui&url=$url'>"._T('login_nouvelle_tentative')."</a>]";
			if (ereg("ecrire/", $url))
				$page_erreur .= " [<a href='ecrire/'>"._T('login_espace_prive')."</a>]";
			ask_php_auth($page_erreur);
		}
		else
			@header("Location: " . $cible->getUrl() );
		exit;
	}
	// si demande logout auth_http
	else if ($essai_auth_http == 'logout') {
		include_ecrire('inc_session.php3');
		ask_php_auth("<b>"._T('login_deconnexion_ok')."</b><p>"._T('login_verifiez_navigateur')."<p>[<a href='./'>"._T('login_retour_public')."</a>] [<a href='./spip_cookie.php3?essai_auth_http=oui&redirect=ecrire'>"._T('login_test_navigateur')."</a>] [<a href='ecrire/'>"._T('login_espace_prive')."</a>]");
		exit;
	}
}

function ouvre_login($titre) {

	$retour .= "<div>";

	if ($titre) $retour .= "<h3 class='spip'>$titre</h3>";

	$retour .= '<font size="2" face="Verdana,arial,helvetica,sans-serif">';
	return $retour;
}

function ferme_login() {
	$retour =  "</font>";
	$retour .= "<div>";
	return $retour;
}

function login($cible = '', $prive = 'prive', $message_login='') {
	$login = $GLOBALS['var_login'];
	$erreur = $GLOBALS['var_erreur'];
	$essai_auth_http = $GLOBALS['var_essai_auth_http'];
	$logout = $GLOBALS['var_logout'];

	// en cas d'echec de cookie, inc_auth a renvoye vers spip_cookie qui
	// a tente de poser un cookie ; s'il n'est pas la, c'est echec cookie
	// s'il est la, c'est probablement un bookmark sur bonjour=oui,
	// et pas un echec cookie.
	if ($GLOBALS['var_echec_cookie'])
		$echec_cookie = ($GLOBALS['spip_session'] != 'test_echec_cookie');

	global $auteur_session;
	global $spip_session, $PHP_AUTH_USER;
	global $spip_admin;
	global $php_module;
	global $clean_link;

	if (!$cible) {
		if ($GLOBALS['var_url']) $cible = new Link($GLOBALS['var_url']);
		else if ($prive) $cible = new Link('ecrire/');
		else $cible = $clean_link;
	}
	$cible->delVar('var_erreur');
	$cible->delVar('var_url');
	$clean_link->delVar('var_erreur');
	$clean_link->delVar('var_login');

	include_ecrire("inc_session.php3");
	verifier_visiteur();
	if ($auteur_session AND !$logout AND
	($auteur_session['statut']=='0minirezo' OR $auteur_session['statut']=='1comite')) {
		$url = $cible->getUrl();
		if ($url != $GLOBALS['clean_link']->getUrl())
			@Header("Location: $url");
		echo "<a href='$url'>"._T('login_par_ici')."</a>\n";
		return;
	}

	// initialisations
	$nom_site = lire_meta('nom_site');
	if (!$nom_site) $nom_site = _T('info_mon_site_spip');
	$url_site = lire_meta('adresse_site');
	if (!$url_site) $url_site = "./";
	if ($erreur=='pass') $erreur = _T('login_erreur_pass');

	// Le login est memorise dans le cookie d'admin eventuel
	if (!$login) {
		if (ereg("^@(.*)$", $spip_admin, $regs))
			$login = $regs[1];
	} else if ($login == '-1')
		$login = '';

	$flag_autres_sources = $GLOBALS['ldap_present'];

	// quels sont les aleas a passer ?
	if ($login) {
		$login = addslashes($login);
		$query = "SELECT * FROM spip_auteurs WHERE login='$login' AND statut!='5poubelle'";
		$result = spip_query($query);
		if ($row = spip_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$source_auteur = $row['source'];
			$alea_actuel = $row['alea_actuel'];
			$alea_futur = $row['alea_futur'];
		} else if (!$flag_autres_sources) {
			$erreur = _T('login_identifiant_inconnu', array('login' => $login));
			$login = '';
			@spip_setcookie("spip_admin", "", time() - 3600);
		}
	}

	// javascript pour le focus
	if ($login)
		$js_focus = 'document.form_login.session_password.focus();';
	else
		$js_focus = 'document.form_login.var_login.focus();';

	if ($echec_cookie == "oui") {
		echo ouvre_login ("$nom_site : probl&egrave;me de cookie");
		echo "<p><b>"._T('login_cookie_oblige')."</b> ";
		echo _T('login_cookie_accepte')."\n";
	}
	else if ($prive) {
		echo ouvre_login ("$nom_site<br><small>"._T('login_acces_prive')."</small>");
	} else {
		echo ouvre_login ("$nom_site<br><small>"._T('login_identification')."</small>");
		echo "<br>$message_login<br>\n";
	}

	if ($login) {
		// Affiche formulaire de login en incluant le javascript MD5
		$flag_challenge_md5 = ($source_auteur == 'spip');

		if ($flag_challenge_md5) echo "<script type=\"text/javascript\" src=\"ecrire/md5.js\"></script>";
		echo "<form name='form_login' action='./spip_cookie.php3' method='post'";
		if ($flag_challenge_md5) echo " onSubmit='if (this.session_password.value) {
				this.session_password_md5.value = calcMD5(\"$alea_actuel\" + this.session_password.value);
				this.next_session_password_md5.value = calcMD5(\"$alea_futur\" + this.session_password.value);
				this.session_password.value = \"\";
			}'";
		echo ">\n";
		echo "<div class='spip_encadrer'>";
		if ($erreur) echo "<div class='reponse_formulaire'><b>$erreur</b></div><p>";

		if ($flag_challenge_md5) {
			// si jaja actif, on affiche le login en 'dur', et on le passe en champ hidden
			echo "<script type=\"text/javascript\"><!--\n" .
				"document.write('"._T('login_login')." <b>$login</b> <br><font size=\\'2\\'>[<a href=\\'spip_cookie.php3?cookie_admin=non&url=".rawurlencode($clean_link->getUrl())."\\'>"._T('login_autre_identifiant')."</a>]</font>');\n" .
				"//--></script>\n";
			echo "<input type='hidden' name='session_login_hidden' value='$login'>";

			// si jaja inactif, le login est modifiable (puisque le challenge n'est pas utilise)
			echo "<noscript>";
			echo "<font face='Georgia, Garamond, Times, serif' size='3'>";
			echo _T('login_non_securise')." <a href=\"".$clean_link->getUrl()."\">"._T('login_recharger')."</a>.<p></font>\n";
		}
		echo "<label><b>"._T('login_login2')."</b><br></label>";
		echo "<input type='text' name='session_login' class='forml' value=\"$login\" size='40'>\n";
		if ($flag_challenge_md5) echo "</noscript>\n";

		echo "<br><br>\n<label><b>"._T('login_pass2')."</b><br></label>";
		echo "<input type='password' name='session_password' class='forml' value=\"\" size='40'>\n";
		echo "<input type='hidden' name='essai_login' value='oui'>\n";

		$url = $cible->getUrl();
		echo "<input type='hidden' name='url' value='$url'>\n";
		echo "<input type='hidden' name='session_password_md5' value=''>\n";
		echo "<input type='hidden' name='next_session_password_md5' value=''>\n";
		echo "<div align='right'><input type='submit' class='spip_bouton' name='submit' value='"._T('bouton_valider')."'></div>\n";
		echo "</div>";
		echo "</form>";
	}
	else { // demander seulement le login

		$url = $cible->getUrl();
		$action = $clean_link->getUrl();

		echo "<form name='form_login' action='$action' method='post'>\n";
		echo "<div class='spip_encadrer'>";
		if ($erreur) echo "<font color=red><b>$erreur</b></font><p>";
		echo "<label><b>"._T('login_login2')."</b><br></label>";
		echo "<input type='text' name='var_login' class='forml' value=\"\" size='40'>\n";

		echo "<input type='hidden' name='var_url' value='$url'>\n";
		echo "<div align='right'><input type='submit' class='spip_bouton' name='submit' value='"._T('bouton_valider')."'></div>\n";
		echo "</div>";
		echo "</form>";
	}

	// Gerer le focus
	echo "<script type=\"text/javascript\"><!--\n" . $js_focus . "\n//--></script>\n";

	if ($echec_cookie == "oui" AND $php_module) {
		echo "<form action='spip_cookie.php3' method='get'>";
		echo "<fieldset>\n<p>";
		echo _T('login_preferez_refuser')." \n";
		echo "<input type='hidden' name='essai_auth_http' value='oui'> ";
		$url = $cible->getUrl();
		echo "<input type='hidden' name='url' value='$url'>\n";
		echo "<div align='right'><input type='submit' name='submit' class='spip_bouton' value='"._T('login_sans_cookiie')."'></div>\n";
		echo "</fieldset></form>\n";
	}

	echo "\n<center>"; // debut du pied de login

	$inscriptions_ecrire = (lire_meta("accepter_inscriptions") == "oui");
	if ((!$prive AND (lire_meta('accepter_visiteurs') == 'oui') OR (lire_meta('forums_publics') == 'abo')) OR ($prive AND $inscriptions_ecrire))
		echo ' [<script language="JavaScript"><!--
document.write("<a href=\\"javascript:window.open(\\\'spip_pass.php3\\\', \\\'spip_pass\\\', \\\'scrollbars=yes,resizable=yes,width=480,height=450\\\'); void(0);\\"");
//--></script><noscript><a href=\'spip_pass.php3\' target=\'_blank\'></noscript>'._T('login_sinscrire').'</a>]';

	// bouton oubli de mot de passe
	include_ecrire ("inc_mail.php3");
	if (tester_mail()) {
		echo ' [<script language="JavaScript"><!--
document.write("<a href=\\"javascript:window.open(\\\'spip_pass.php3?oubli_pass=oui\\\', \\\'spip_pass\\\', \\\'scrollbars=yes,resizable=yes,width=480,height=280\\\'); void(0);\\"");
//--></script><noscript><a href=\'spip_pass.php3?oubli_pass=oui\' target=\'_blank\'></noscript>'._T('login_motpasseoublie').'</a>]';
	}

	if ($prive) echo " [<a href='$url_site'>"._T('login_retoursitepublic')."</a>]";

	echo "</center>\n";

	echo ferme_login();
}

?>
