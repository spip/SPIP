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

// http://doc.spip.org/@acces_rubrique
function acces_rubrique($id_rubrique) {
	global $connect_toutes_rubriques;
	global $connect_id_rubrique;

	return ($connect_toutes_rubriques OR isset($connect_id_rubrique[$id_rubrique]));
}

// http://doc.spip.org/@acces_restreint_rubrique
function acces_restreint_rubrique($id_rubrique) {
	global $connect_id_rubrique;
	global $connect_statut;

	return (isset($connect_id_rubrique[$id_rubrique]));
}

// http://doc.spip.org/@acces_mots
function acces_mots() {
	global $connect_toutes_rubriques;
	return $connect_toutes_rubriques;
}

// http://doc.spip.org/@acces_article
function acces_article($id_article)
{
	global $auteur_session, $connect_toutes_rubriques;

	if ($connect_toutes_rubriques) return true;

	$s = spip_query("SELECT id_rubrique, statut FROM spip_articles WHERE id_article=$id_article");
	$row = spip_fetch_array($s);

	if (acces_rubrique($row['id_rubrique'])) return true;

	$s = auteurs_article($id_article, " id_auteur=" . $auteur_session['id_auteur']);
	if (!spip_num_rows($s)) return false;

	$s = $row['statut'];

	return ($s == 'prepa' OR $s == 'prop' OR $s == 'poubelle');
}

// http://doc.spip.org/@auteurs_article
function auteurs_article($id_article, $cond='')
{
	return spip_query("SELECT id_auteur FROM spip_auteurs_articles WHERE id_article=$id_article". ($cond ? " AND $cond" : ''));
}

// Retourne les droits de publication d'un auteur selon le codage suivant:
// - le tableau de ses rubriques si c'est un admin restreint
// - 0 si c'est un admin de plein droit
// - la chaine indiquant son statut s'il n'est pas admin

// http://doc.spip.org/@auth_rubrique
function auth_rubrique($id_auteur, $statut)
{
	if ($statut != '0minirezo') return $statut;

	$result = spip_query("SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur=$id_auteur AND id_rubrique!='0'");

	if (!spip_num_rows($result)) {
		$GLOBALS['connect_toutes_rubriques'] = true;
		return 0;
	}

	$rubriques = array();
	for (;;) {
		$r = array();
		while ($row = spip_fetch_array($result)) {
			$id_rubrique = $row['id_rubrique'];
			$r[]= $rubriques[$id_rubrique] = $id_rubrique;
		}
		if (!$r) return $rubriques;
		$r = join(',', $r);

		$result = spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($r) AND id_rubrique NOT IN ($r)");
	}
}

// Un nouvel inscrit prend son statut definitif a la 1ere connexion
// Le statut a ete memorise dans bio (cf formulaire_inscription)
// Si vide se rabattre sur le mode d'inscription 
// (compatibilite vieille version ou redac/forum etait mutuellement exclusif)

// http://doc.spip.org/@acces_statut
function acces_statut($id_auteur, $statut, $bio)
{
	if ($statut == 'nouveau') {
		$statut = ($bio ? ($bio == 'redac' ? '1comite' : '6forum'):
			   (($GLOBALS['meta']['accepter_inscriptions'] == 'oui') ? '1comite' : '6forum'));
		spip_query("UPDATE spip_auteurs SET bio='', statut='$statut'	WHERE id_auteur=$id_auteur");
	}
	return $statut;
}

// http://doc.spip.org/@inc_auth_dist
function inc_auth_dist() {
	global $auth_can_disconnect, $ignore_auth_http, $ignore_remote_user;
	global $prefs, $connect_id_auteur, $connect_login;
	global $connect_statut, $connect_toutes_rubriques, $connect_id_rubrique;
	//
	// Initialiser variables (eviter hacks par URL)
	//

	$connect_login = '';
	$connect_id_auteur = 0;
	$auth_can_disconnect = false;

	//
	// Recuperer les donnees d'identification
	//
	
	// Session valide en cours ?
	if ($_COOKIE['spip_session']) {
		$var_f = charger_fonction('session', 'inc');
		if ($connect_id_auteur = $var_f()) {
			$auth_can_disconnect = true;
		} else unset($_COOKIE['spip_session']);
	}
	
	// Essayer auth http si significatif
	// (ignorer les login d'intranet independants de spip)
	if (!$ignore_auth_http AND !$connect_id_auteur) {
		if ($_SERVER['PHP_AUTH_USER'] AND $_SERVER['PHP_AUTH_PW']) {
			include_spip('inc/actions');
			if (verifier_php_auth()) {
				$connect_login = $_SERVER['PHP_AUTH_USER'];
				$auth_can_disconnect = true;
				$_SERVER['PHP_AUTH_PW'] = '';
			}

		} else if ($_SERVER['REMOTE_USER'])

	// Authentification .htaccess old style, car .htaccess semble
	// souvent definir *aussi* PHP_AUTH_USER et PHP_AUTH_PW

			$connect_login = $_SERVER['REMOTE_USER'];
	}    

	$where = $connect_id_auteur ?
	  "id_auteur=$connect_id_auteur" :
	  (!$connect_login ? '' : "login=" . _q($connect_login));

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
	$r = auth_rubrique($connect_id_auteur, $connect_statut);

	if (is_string($r)) {
		if ($r != '1comite') return auth_arefaire();
	} elseif (is_array($r))
		$connect_id_rubrique = $r;
	else $connect_toutes_rubriques = true;

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
			preg_match(',^[^/]*//[^/]*(.*)/$,',
				   url_de_base(),
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

// http://doc.spip.org/@auth_prefs
function auth_prefs()
{
	if (!$GLOBALS['set_disp'] = $_COOKIE['spip_display'])
		$GLOBALS['set_disp'] = 2;
	if (!$GLOBALS['set_couleur'] = $_COOKIE['spip_couleur'])
		$GLOBALS['set_couleur'] = 6;
	if (!$GLOBALS['set_options'] = $_COOKIE['spip_options'])
		$GLOBALS['set_options'] = 'basiques';
}

// Cas ou l'auteur a ete identifie mais on n'a pas d'info sur lui
// C'est soit parce que le serveur MySQL ne repond pas,
// soit parce que la table des auteurs a changee (restauration etc)
// Pas la peine d'insister.  Envoyer un message clair au client.

// http://doc.spip.org/@auth_areconnecter
function auth_areconnecter($auth_login)
{
	include_spip('inc/minipres');
	if (!spip_connect()) {
		spip_log("Erreur base de donnees");

		minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'). "<p><tt>".spip_sql_errno()." ".spip_sql_error()."</tt></p>");
	} else {
		minipres(_T('avis_erreur_connexion'), "<br><br><p>" . _T('texte_inc_auth_1', array('auth_login' => $auth_login)). " <a href='".  generer_url_action('logout', "logout=prive"). "'>". _T('texte_inc_auth_2'). "</a>"._T('texte_inc_auth_3'));
	}
}

// redemande login, avec nettoyage

// http://doc.spip.org/@auth_arefaire
function auth_arefaire()
{
	$url = rawurlencode(str_replace('/./', '/',
			(_DIR_RESTREINT ? "" : _DIR_RESTREINT_ABS) . str_replace('&amp;', '&', self()))); 
	return generer_url_public('login', "url=$url" . ($_GET['bonjour'] == 'oui' ? '&var_echec_cookie=true' : ''),true);
}
?>
