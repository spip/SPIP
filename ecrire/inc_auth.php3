<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_AUTH")) return;
define("_ECRIRE_INC_AUTH", "1");


//
// Fonctions de gestion de l'acces restreint aux rubriques
//

function recuperer_sous_rubriques($id_parent) {
	global $connect_id_rubrique;
		
	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent=$id_parent";
 	$result = spip_query($query);

	while ($row = mysql_fetch_array($result)) {
		$id_rubrique = $row['id_rubrique'];
		$connect_id_rubrique[$id_rubrique] = $id_rubrique;
		recuperer_sous_rubriques($id_rubrique);
	}
}

function acces_rubrique($id_rubrique) {
	global $connect_toutes_rubriques;
	global $connect_id_rubrique;
	
	return ($connect_toutes_rubriques OR $connect_id_rubrique[$id_rubrique]);
}

function acces_restreint_rubrique($id_rubrique) {
	global $connect_id_rubrique;
	global $connect_statut;
	
	return ($connect_statut == "0minirezo" AND $connect_id_rubrique[$id_rubrique]);
}


function auth() {
	global $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_COOKIE_VARS, $REMOTE_USER, $PHP_AUTH_USER, $PHP_AUTH_PW;
	global $auth_can_disconnect;

	global $connect_id_auteur, $connect_nom, $connect_bio, $connect_email;
	global $connect_nom_site, $connect_url_site, $connect_login, $connect_pass;
	global $connect_activer_imessage, $connect_activer_messagerie;
	global $connect_statut, $connect_toutes_rubriques, $connect_id_rubrique;

	global $auteur_session;

	//
	// Si pas MySQL, fini
	//
	if (!$GLOBALS['db_ok']) {
		echo "<P><H4>Attention&nbsp;: un probl&egrave;me technique (serveur MySQL) emp&ecirc;che l'acc&egrave;s &agrave; cette partie du site.\nMerci de votre compr&eacute;hension.</H4><P><P>\n";
		return false;
	}


	//
	// Initialiser variables (eviter hacks par URL)
	//

	$auth_login = "";
	$auth_pass = "";
	$auth_pass_ok = false;
	$auth_can_disconnect = false;
	$auth_htaccess = false;

	//
	// Recuperer les donnees d'identification
	//

	// Authentification .htaccess
	if ($REMOTE_USER &&
		!($HTTP_GET_VARS["REMOTE_USER"] || $HTTP_POST_VARS["REMOTE_USER"] || $HTTP_COOKIE_VARS["REMOTE_USER"])) {
		$auth_login = $REMOTE_USER;
		$auth_pass_ok = true;
		$auth_htaccess = true;
	}

	// Peut-etre sommes-nous en auth http?
	else if ($PHP_AUTH_USER && $PHP_AUTH_PW) {
		if ($GLOBALS['logout'] == $PHP_AUTH_USER) {
			@header("Location: ../spip_cookie.php3?essai_auth_http=logout");
			exit;
		} else {
			include_local ("inc_session.php3");
			if (verifier_php_auth()) {
				$auth_login = $PHP_AUTH_USER;
				$auth_pass_ok = true;
				$auth_can_disconnect = true;
			} else {
				// normalement on n'arrive pas la sauf changement de mot de passe dans la base
				$auth_login = '';
				echo "<p><b>Connexion refus&eacute;e</b></p>";
				echo "[<a href='../spip_cookie.php3?essai_auth_http=oui&redirect=./ecrire/'>r&eacute;essayer</a>]";
				exit;
			}
			$PHP_AUTH_PW = '';
			$_SERVER['PHP_AUTH_PW'] = '';
			$HTTP_SERVER_VARS['PHP_AUTH_PW'] = '';
		}
	}

	// Authentification session
	else if ($cookie_session = $HTTP_COOKIE_VARS['spip_session']) {
		include_local ("inc_meta.php3");
		include_local ("inc_session.php3");
		if (verifier_session($cookie_session)) {
			if ($auteur_session['statut'] == '0minirezo' OR $auteur_session['statut'] == '1comite') {
				$auth_login = $auteur_session['login'];
				$auth_pass_ok = true;
				$auth_can_disconnect = true;
				if ($GLOBALS['logout'] == $auth_login) {
					@header("Location: ../spip_cookie.php3?cookie_session=non&redirect=".rawurlencode("./ecrire/login.php3"));
					exit;
				}
			}
		}
	}
	else if ($GLOBALS['essai_cookie'] == 'oui') {
		@header("Location: ./login.php3?echec_cookie=oui");
		exit;
	}

	// Si pas authentifie, demander login / mdp
	if (!$auth_login) {
//		@header("Location: ./login.php3");
		include('login.php3');
		exit;
	}

	//
	// Chercher le login dans la table auteurs
	//
	
	$auth_login = addslashes($auth_login);
	$query = "SELECT * FROM spip_auteurs WHERE login='$auth_login' AND statut!='5poubelle' AND statut!='6forum'";
	$result = @spip_query($query);
	
	if (!@mysql_num_rows($result)) {
		@header("Location: ../spip_cookie.php3?essai_auth_http=oui&redirect=./ecrire/");
		exit;
	}
	
	if ($row = mysql_fetch_array($result)) {
		$connect_id_auteur = $row['id_auteur'];
		$connect_nom = $row['nom'];
		$connect_bio = $row['bio'];
		$connect_email = $row['email'];
		$connect_nom_site = $row['nom_site'];
		$connect_url_site = $row['url_site'];
		$connect_login = $row['login'];
		$connect_pass = $row['pass'];
		$connect_statut = $row['statut'];
		$connect_activer_messagerie = $row["messagerie"];
		$connect_activer_imessage = $row["imessage"];
	
		// Special : si dans la fiche auteur on modifie les valeurs
		// de messagerie, utiliser ces valeurs plutot que celle de la base.
		// D'ou leger bug si on modifie la fiche de quelqu'un d'autre.
		if ($GLOBALS['perso_activer_messagerie']) {
			$connect_activer_messagerie = $GLOBALS['perso_activer_messagerie'];
			$connect_activer_imessage = $GLOBALS['perso_activer_imessage'];
		}
	
		// Verifier si pass ok
		if ($connect_pass == $auth_mdpass) $auth_pass_ok = true;
	
		// Indiquer connexion
		if ($connect_activer_messagerie != "non") {
			@spip_query("UPDATE spip_auteurs SET en_ligne=NOW() WHERE id_auteur='$connect_id_auteur'");
		}
	
		// Si administrateur, recuperer les rubriques gerees par l'admin
		if ($connect_statut == '0minirezo') {
			$query_admin = "SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur=$connect_id_auteur AND id_rubrique!='0'";
			$result_admin = spip_query($query_admin);
			
			$connect_toutes_rubriques = (@mysql_num_rows($result_admin) == 0);
			if ($connect_toutes_rubriques) {
				$connect_id_rubrique = array();
			}
			else {
				for (;;) {
					$r = '';
					while ($row_admin = mysql_fetch_array($result_admin)) {
						$id_rubrique = $row_admin['id_rubrique'];
						$r[] = $id_rubrique;
						$connect_id_rubrique[$id_rubrique] = $id_rubrique;
					}
					if (!$r) break;
					$r = join(',', $r);
					$query_admin = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($r) AND id_rubrique NOT IN ($r)";
				 	$result_admin = spip_query($query_admin);
				 }
			}
		}
		// Si pas admin, acces egal a toutes rubriques
		else {
			$connect_toutes_rubriques = false;
			$connect_id_rubrique = array();
		}
	}
	else {
		$auth_pass_ok = false;
	}

	// Securite, ne pas garder la valeur en memoire
	$auth_mdpass = '';
	
	if (!$auth_pass_ok) {
		@header("Location: ./login.php3?erreur=pass");
		exit;
	}
	
	if ($connect_statut == 'nouveau') {
		$query = "UPDATE spip_auteurs SET statut='1comite' WHERE id_auteur=$connect_id_auteur";
		$result = spip_query($query);
		$connect_statut = '1comite';
	}
	return true;
}


if (!auth()) exit;

?>