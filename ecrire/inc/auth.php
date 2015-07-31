<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


//
// Fonctions de gestion de l'acces restreint aux rubriques
//

// http://doc.spip.org/@acces_restreint_rubrique
function acces_restreint_rubrique($id_rubrique) {
	global $connect_id_rubrique;
	global $connect_statut;

	return (isset($connect_id_rubrique[$id_rubrique]));
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

// Fonction d'authentification
// retourne -1 si authentification impossible a cause du serveur SQL 
// retourne une chaine vide si authentification reussie
// retourne une chaine non vide expliquant l'echec sinon:
//	"rien" ==> nouvel arrivant, envoyer le formulaire
//	autre  ==> statut incompatible

// http://doc.spip.org/@inc_auth_dist
function inc_auth_dist() {
	global $auth_can_disconnect, $ignore_auth_http, $ignore_remote_user;
	global $connect_id_auteur, $connect_login, $connect_quand;
	global $connect_statut, $connect_toutes_rubriques, $connect_id_rubrique;
	//
	// Initialiser variables (eviter hacks par URL)
	//

	$connect_login = '';
	$connect_id_auteur = NULL;
	$auth_can_disconnect = false;

	//
	// Recuperer les donnees d'identification
	//
	
	// Session valide en cours ?
	if (isset($_COOKIE['spip_session'])) {
		$session = charger_fonction('session', 'inc');
		if ($connect_id_auteur = $session()) {
			$auth_can_disconnect = true;
		} else unset($_COOKIE['spip_session']);
	}

	// Essayer auth http si significatif
	// (ignorer les login d'intranet independants de spip)
	if (!$ignore_auth_http) {
		if (isset($_SERVER['PHP_AUTH_USER'])
		AND isset($_SERVER['PHP_AUTH_PW'])) {
			include_spip('inc/actions');
			if ($r = lire_php_auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				if (!$connect_id_auteur) {
					$_SERVER['PHP_AUTH_PW'] = '';
					$auth_can_disconnect = true;
					$GLOBALS['auteur_session'] = $r;
					$connect_login = $GLOBALS['auteur_session']['login'];
				} else {
				  // cas de la session en plus de PHP_AUTH
				  /*				  if ($connect_id_auteur != $r['id_auteur']){
				    spip_log("vol de session $connect_id_auteur" . join(', ', $r));
					unset($_COOKIE['spip_session']);
					$connect_id_auteur = '';
					} */
				}
			}
		} else { if (isset($_SERVER['REMOTE_USER']))

	// Authentification .htaccess old style, car .htaccess semble
	// souvent definir *aussi* PHP_AUTH_USER et PHP_AUTH_PW

				$connect_login = $_SERVER['REMOTE_USER'];
		}
	}

	$where = (is_numeric($connect_id_auteur)) ?
	  "id_auteur=$connect_id_auteur" :
	  (!$connect_login ? '' : "login=" . _q($connect_login));

	// pas authentifie par cookie ni http_auth:

	if (!$where) return "inconnu";

	// Trouver les autres infos dans la table auteurs.

	$result = @spip_query("SELECT *, UNIX_TIMESTAMP(en_ligne) AS quand FROM spip_auteurs WHERE $where AND statut!='5poubelle'");

	if (!$row = spip_fetch_array($result)) {

	  // il n'est PLUS connu. c'est SQL qui est desyncrho
		auth_areconnecter($connect_login);
		return -1;
	}

	// connu. Mais avec quels droits ?
	$connect_quand = $row['quand'];
	$connect_id_auteur = $row['id_auteur'];
	$connect_statut = acces_statut($connect_id_auteur, $row['statut'], $row['bio']);
	$droits = auth_rubrique($connect_id_auteur, $connect_statut);

	// rajouter les sessions meme en mode auth_http
	// pour permettre les connexions multiples et identifier les visiteurs
	if (!$_COOKIE['spip_session']) {
		$session = charger_fonction('session', 'inc');
		if ($spip_session = $session($row)) {
			include_spip('inc/cookie');
			preg_match(',^[^/]*//[^/]*(.*)/$,',
				   url_de_base(),
				   $r);
			spip_setcookie('spip_session', $spip_session, time() + 3600 * 24 * 14, $r[1]);
		}
	}

	// Indiquer la connexion. A la minute pres ca suffit.
	if ((time() - 	$connect_quand)  >= 60) {
		@spip_query("UPDATE spip_auteurs SET en_ligne=NOW() WHERE id_auteur='$connect_id_auteur'");
	}

	// Le tableau global auteur_session contient toutes les infos.
	// Les plus utiles sont aussi dans les variables simples ci-dessus

	$GLOBALS['auteur_session'] = $row;
	$GLOBALS['auteur_session']['prefs'] = @unserialize($GLOBALS['auteur_session']['prefs']);

	if (is_string($droits)) {
	  // ordres mineurs: redac, visiteur ou indefini
		if ($droits != '1comite') return $droits;
	} elseif (is_array($droits))
		$connect_id_rubrique = $droits;
	else $connect_toutes_rubriques = true;

	$connect_login = $row['login'];
	// vide = pas de message d'erreur (cf exit(0) Unix)
	return "";
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

		echo minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'). "<p><tt>".spip_sql_errno()." ".spip_sql_error()."</tt></p>");
	} else {
		echo minipres(_T('avis_erreur_connexion'), "<br /><br /><p>" . _T('texte_inc_auth_1', array('auth_login' => $auth_login)). " <a href='".  generer_url_action('logout', "logout=prive"). "'>". _T('texte_inc_auth_2'). "</a>"._T('texte_inc_auth_3'));
	}
}
?>
