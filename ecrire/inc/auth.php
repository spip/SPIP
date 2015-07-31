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

function auth_rubrique()
{
	global $connect_id_auteur, $connect_toutes_rubriques, $connect_id_rubrique;

	$result = spip_query("SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur=$connect_id_auteur AND id_rubrique!='0'");

	$connect_toutes_rubriques = (@spip_num_rows($result) == 0);
	if (!$connect_toutes_rubriques) {
		for (;;) {
			$r = array();
			while ($row = spip_fetch_array($result)) {
				$id_rubrique = $row['id_rubrique'];
				$r[] = $connect_id_rubrique[$id_rubrique] = $id_rubrique;
			}
			if (!$r) break;
			$r = join(',', $r);
			$result = spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($r) AND id_rubrique NOT IN ($r)");
		}
	}
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
	global $auth_can_disconnect, $ignore_auth_http, $ignore_remote_user;
	global $prefs, $connect_id_auteur, $connect_login;
	global $connect_statut, $connect_toutes_rubriques, $connect_id_rubrique;

	//
	// Initialiser variables (eviter hacks par URL)
	//

	$connect_login = '';
	$connect_id_auteur = 0;
	$connect_id_rubrique = array();
	$auth_can_disconnect = false;
	$connect_toutes_rubriques = false;

	//
	// Recuperer les donnees d'identification
	//
	
	// Session valide en cours ?
	if ($_COOKIE['spip_session']) {
		$var_f = charger_fonction('session', 'inc');
		if ($connect_id_auteur = $var_f()) {
			$auth_can_disconnect = true;
		}
	}
	
	// sinon, essayer auth http si significatif
	// (ignorer les login d'intranet independants de spip)
	if (!$ignore_auth_http AND !$connect_id_auteur) {
		if ($_SERVER['PHP_AUTH_USER'] AND $_SERVER['PHP_AUTH_PW']) {
			include_spip('inc/actions');
			if (verifier_php_auth()) {
				$connect_login = $_SERVER['PHP_AUTH_USER'];
				$auth_can_disconnect = true;
				$_SERVER['PHP_AUTH_PW'] = '';
			}

		} else if ($GLOBALS['_SERVER']['REMOTE_USER'])

	// Authentification .htaccess old style, car .htaccess semble
	// souvent definir *aussi* PHP_AUTH_USER et PHP_AUTH_PW

			$connect_login = $GLOBALS['_SERVER']['REMOTE_USER'];
	}    

	$where = $connect_id_auteur ?
	  "id_auteur=$connect_id_auteur" :
	  (!$connect_login ? '' : "login=" . spip_abstract_quote($connect_login));

	// pas authentifie par cookie ni rien: demander login / mdp

	if (!$where) return auth_arefaire();

	// Trouver les autres infos dans la table auteurs.

	$result = @spip_query("SELECT *, UNIX_TIMESTAMP(en_ligne) AS quand FROM spip_auteurs WHERE $where AND statut!='5poubelle'");

	if (!$row = spip_fetch_array($result)) {

		auth_areconnecter($connect_login);
		exit;
	}

	// Indiquer la connexion. A la minute pres ca suffit.
	if ((time() - $row['quand']) >= 60) {
		@spip_query("UPDATE spip_auteurs SET en_ligne=NOW() WHERE id_auteur='$connect_id_auteur'");
	}

	$connect_id_auteur = $row['id_auteur'];
	$connect_statut = acces_statut($connect_id_auteur, $row['statut'], $row['bio']);
	if ($connect_statut == '0minirezo') auth_rubrique();
	else if ($connect_statut != '1comite') return auth_arefaire();

	$prefs = unserialize($row['prefs']);
	$connect_login = $row['login'];

	// Le tableau global auteur_session contient toutes les infos.
	// Les plus utiles sont aussi dans les variables simples ci-dessus

	$GLOBALS['auteur_session'] = $row;

	// rajouter les sessions meme en mode auth_http
	// pour permettre les connexions multiples
	if (!$_COOKIE['spip_session']) {
		$var_f = charger_fonction('session', 'inc');
		if ($session = $var_f($row)) {
			preg_match(',^[^/]*//[^/]*(.*)$,',
				   $GLOBALS['meta']['adresse_site'],
				   $r);
			spip_setcookie('spip_session', $session, time() + 3600 * 24 * 14, $r[1]);
		}
	}

	// ceci n'arrive qu'a la premiere connexion il me semble
	// si oui ce serait mieux de le mettre a la creation de l'auteur
	if (! isset($prefs['display'])) auth_prefs();

	// vide = pas de message d'erreur (cf exit(0) Unix)
	return "";
}

function auth_prefs()
{
	if (!$GLOBALS['set_disp'] = $GLOBALS['_COOKIE']['spip_display'])
		$GLOBALS['set_disp'] = 2;
	if (!$GLOBALS['set_couleur'] = $GLOBALS['_COOKIE']['spip_couleur'])
		$GLOBALS['set_couleur'] = 6;
	if (!$GLOBALS['set_options'] = $GLOBALS['_COOKIE']['spip_options'])
		$GLOBALS['set_options'] = 'basiques';
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

		minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'). "<p><tt>".spip_sql_errno()." ".spip_sql_error()."</tt></p>");
	} else {
		minipres(_T('avis_erreur_connexion'), "<br><br><p>" . _T('texte_inc_auth_1', array('auth_login' => $auth_login)). " <a href='".  generer_url_public('spip_cookie',"logout=$auth_login"). "'>". _T('texte_inc_auth_2'). "</a>"._T('texte_inc_auth_3'));
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
