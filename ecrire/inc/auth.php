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


//
// Fonctions de gestion de l'acces restreint aux rubriques
//

function acces_rubrique($id_rubrique) {
	global $connect_toutes_rubriques;
	global $connect_id_rubrique;

	return ($connect_toutes_rubriques OR isset($connect_id_rubrique[$id_rubrique]));
}

function acces_restreint_rubrique($id_rubrique) {
	global $connect_id_rubrique;
	global $connect_statut;

	return ($connect_statut == "0minirezo" AND isset($connect_id_rubrique[$id_rubrique]));
}


// Un nouvel inscrit prend son statut definitif a la 1ere connexion
// Le statut a ete memorise dans bio (cf formulaire_inscription)
// Si vide se rabattre sur le mode d'inscription 
// (compatibilite vieille version ou redac/forum etait mutuellement exclusif)

function acces_statut($id_auteur, $statut, $bio)
{
	if ($statut == 'nouveau') {
		$statut = ($bio ? ($bio == 'redac' ? '1comite' : '6forum'):
			   (($GLOBALS['meta']['accepter_inscriptions'] == 'oui') ? '1comite' : '6forum'));
		spip_query("UPDATE spip_auteurs SET bio='', statut='$statut'	WHERE id_auteur=$id_auteur");
	}
	return $statut;
}


function inc_auth_dist() {
	global $_GET, $_COOKIE, $_SERVER;
	global $auth_can_disconnect, $ignore_auth_http, $ignore_remote_user;

	global $connect_id_auteur, $connect_login, $connect_pass;
	global $connect_statut, $connect_toutes_rubriques, $connect_id_rubrique;

	global $auteur_session, $prefs;

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

	// Authentification session
	if ($cookie_session = $_COOKIE['spip_session']) {
		if (verifier_session($cookie_session)) {
			if ($auteur_session['statut'] == '0minirezo'
			OR $auteur_session['statut'] == '1comite') {
				$auth_login = $auteur_session['login'];
				$auth_pass_ok = true;
				$auth_can_disconnect = true;
			}
		}
	}

	// Peut-etre sommes-nous en auth http?
	else if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']
	&& !$ignore_auth_http) {

		// Si le login existe dans la base, se loger
		if (verifier_php_auth()) {
			$auth_login = $_SERVER['PHP_AUTH_USER'];
			$auth_pass_ok = true;
			$auth_can_disconnect = true;
			$_SERVER['PHP_AUTH_PW'] = '';
		}
		// Sinon c'est un login d'intranet independant de spip, on ignore
	}

	// Authentification .htaccess old style, car .htaccess semble
	// souvent definir *aussi* PHP_AUTH_USER et PHP_AUTH_PW
	else if ($GLOBALS['_SERVER']['REMOTE_USER']
	&& !$ignore_remote_user) {
		$auth_login = $GLOBALS['_SERVER']['REMOTE_USER'];
		$auth_pass_ok = true;
		$auth_htaccess = true;
	}

	// pas authentifie par cookie ni rien: demander login / mdp

	if (!$auth_login) {
		return auth_arefaire();
	}
	//
	// Chercher le login dans la table auteurs
	//

	$result = @spip_query("SELECT * FROM spip_auteurs WHERE login=" . spip_abstract_quote($auth_login) . " AND statut!='5poubelle'");

	if (!$row = spip_fetch_array($result)) {
	  auth_areconnecter($auth_login);
		exit;
	}
	elseif ($row['statut']=='6forum') 
		return auth_arefaire();
	else {
		$connect_id_auteur = $row['id_auteur'];
		$connect_login = $row['login'];
		$connect_pass = $row['pass'];
		$connect_statut = acces_statut($connect_id_auteur, $row['statut'], $row['bio']);

		// Special : si dans la fiche auteur on modifie les valeurs
		// de messagerie, utiliser ces valeurs plutot que celle de la base.
		// D'ou leger bug si on modifie la fiche de quelqu'un d'autre.

		// regler les preferences de l'auteur
		$prefs = unserialize($row['prefs']);

		// vieux ! on pourra supprimer post 1.6 finale...
		if (! isset($prefs['display'])) { // recuperer les cookies ou creer defaut
			if (!$GLOBALS['set_disp'] = $GLOBALS['_COOKIE']['spip_display'])
				$GLOBALS['set_disp'] = 2;
			if (!$GLOBALS['set_couleur'] = $GLOBALS['_COOKIE']['spip_couleur'])
				$GLOBALS['set_couleur'] = 6;
			if (!$GLOBALS['set_options'] = $GLOBALS['_COOKIE']['spip_options'])
				$GLOBALS['set_options'] = 'basiques';
		}

		// Indiquer connexion
		@spip_query("UPDATE spip_auteurs SET en_ligne=NOW() WHERE id_auteur='$connect_id_auteur'");

		// Si administrateur, recuperer les rubriques gerees par l'admin
		if ($connect_statut == '0minirezo') {
			$result_admin = spip_query("SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur=$connect_id_auteur AND id_rubrique!='0'");

			$connect_toutes_rubriques = (@spip_num_rows($result_admin) == 0);
			if ($connect_toutes_rubriques) {
				$connect_id_rubrique = array();
			}
			else {
				for (;;) {
					$r = '';
					while ($row_admin = spip_fetch_array($result_admin)) {
						$id_rubrique = $row_admin['id_rubrique'];
						$r[] = $id_rubrique;
						$connect_id_rubrique[$id_rubrique] = $id_rubrique;
					}
					if (!$r) break;
					$r = join(',', $r);
				 	$result_admin = spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($r) AND id_rubrique NOT IN ($r)");

				 }
			}
		}
		// Si pas admin, acces egal a toutes rubriques
		else {
			$connect_toutes_rubriques = false;
			$connect_id_rubrique = array();
		}
	}

	if (!$auth_pass_ok)
		return	generer_url_public('login', 'var_erreur=pass', true);

	return "";
}


// Cas ou l'auteur a ete identifie mais on n'a pas d'info sur lui
// C'est soit parce que le serveur MySQL ne repond pas,
// soit parce que la table des auteurs a changee (restauration etc)
// Pas la peine d'insister.  Envoyer un message clair au client.

function auth_areconnecter($auth_login)
{
	include_spip('inc/minipres');
	if (!$GLOBALS['db_ok']) {
		spip_log("Erreur base de donnees");

		install_debut_html(_T('info_travaux_titre')); echo _T('titre_probleme_technique'), "<p><tt>".spip_sql_errno()." ".spip_sql_error()."</tt></p>";install_fin_html();
	} else {

		install_debut_html(_T('avis_erreur_connexion')); echo "<br><br><p>", _T('texte_inc_auth_1', array('auth_login' => $auth_login)), " <a href='",  generer_url_public('spip_cookie',"logout=$auth_login"), "'>", _T('texte_inc_auth_2'), "</A>",_T('texte_inc_auth_3');install_fin_html();
	}
}

// redemande login, avec nettoyage

function auth_arefaire()
{
	$url = rawurlencode(str_replace('/./', '/',
			(_DIR_RESTREINT ? "" : _DIR_RESTREINT_ABS) . str_replace('&amp;', '&', self()))); 
	return generer_url_public('login', "url=$url" . ($_GET['bonjour'] == 'oui' ? '&var_echec_cookie=true' : ''),true);
	  }


?>
