<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('base/abstract_sql');
//
// Fonctions de gestion de l'acces restreint aux rubriques
//

// http://doc.spip.org/@acces_restreint_rubrique
function acces_restreint_rubrique($id_rubrique) {
	global $connect_id_rubrique;

	return (isset($connect_id_rubrique[$id_rubrique]));
}

// http://doc.spip.org/@auteurs_article
function auteurs_article($id_article, $cond='')
{
	return sql_allfetsel("id_auteur", "spip_auteurs_articles", "id_article=$id_article". ($cond ? " AND $cond" : ''));
}

// http://doc.spip.org/@auteurs_autorises
function auteurs_autorises($in, $cond='')
{
	return sql_in("statut", array('0minirezo','1comite'))
	  . (!$cond ? '' : " AND $cond")
	  . (!$in ? '' : (" AND ". sql_in("id_auteur", $in, 'NOT')));
}

// Un nouvel inscrit prend son statut definitif a la 1ere connexion.
// Le statut a ete memorise dans bio (cf formulaire_inscription).
// On le verifie, car la config a peut-etre change depuis,
// et pour compatibilite avec les anciennes versions n'utilisait pas "bio".

// http://doc.spip.org/@acces_statut
function acces_statut($id_auteur, $statut, $bio)
{
	if ($statut != 'nouveau') return $statut;
	include_spip('inc/filtres');
	if (!($s = tester_config('', $bio))) return $statut;
		sql_updateq('spip_auteurs', array('bio'=>'', 'statut'=> $s), "id_auteur=$id_auteur");
	return $s;
}

// Fonction d'authentification. Retourne:
//  - URL de connexion  si on ne sait rien (pas de cookie, pas Auth_user);
//  - un tableau si visiteur sans droit (tableau = sa ligne SQL)
//  - code numerique d'erreur SQL
//  - une chaine vide si autorisation a penetrer dans l'espace prive.

// http://doc.spip.org/@inc_auth_dist
function inc_auth_dist() {

	global $connect_login ;

	$row = auth_mode();

	if ($row) return auth_init_droits($row);

	if (!$connect_login) return auth_a_loger();

	// Cas ou l'auteur a ete identifie mais on n'a pas d'info sur lui
	// C'est soit parce que le serveur MySQL ne repond pas,
	// soit parce que la table des auteurs a changee (restauration etc)
	// Pas la peine d'insister.  Envoyer un message clair au client.

	if (spip_connect()) return array('nom' => $connect_login);

	$n = intval(sql_errno());
	spip_log("Erreur base de donnees $n " . sql_error());
	return $n ? $n : 1;
}

// Retourne la description d'un authentifie par cookie ou http_auth
// Et affecte la globale $connect_login

function auth_mode()
{
	global $auth_can_disconnect, $ignore_auth_http, $ignore_remote_user;
	global $connect_login ;

	//
	// Initialiser variables (eviter hacks par URL)
	//

	$connect_login = '';
	$id_auteur = NULL;
	$auth_can_disconnect = false;

	//
	// Recuperer les donnees d'identification
	//

	// Session valide en cours ?
	if (isset($_COOKIE['spip_session'])) {
		$session = charger_fonction('session', 'inc');
		if ($id_auteur = $session() 
		OR $id_auteur===0 // reprise sur restauration
		) {
			$auth_can_disconnect = true;
			$connect_login = $GLOBALS['visiteur_session']['login'];
		} else unset($_COOKIE['spip_session']);
	}

	// Essayer auth http si significatif
	// (ignorer les login d'intranet independants de spip)
	if (!$ignore_auth_http) {
		if (isset($_SERVER['PHP_AUTH_USER'])
		AND isset($_SERVER['PHP_AUTH_PW'])) {
			include_spip('inc/actions');
			if ($r = lire_php_auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				if (!$id_auteur) {
					$_SERVER['PHP_AUTH_PW'] = '';
					$auth_can_disconnect = true;
					$GLOBALS['visiteur_session'] = $r;
					$connect_login = $GLOBALS['visiteur_session']['login'];
				} else {
				  // cas de la session en plus de PHP_AUTH
				  /*				  if ($id_auteur != $r['id_auteur']){
				    spip_log("vol de session $id_auteur" . join(', ', $r));
					unset($_COOKIE['spip_session']);
					$id_auteur = '';
					} */
				}
			}
		// Authentification .htaccess old style, car .htaccess semble
		// souvent definir *aussi* PHP_AUTH_USER et PHP_AUTH_PW
		} else if (isset($_SERVER['REMOTE_USER']))
			$connect_login = $_SERVER['REMOTE_USER'];
	}

	$where = (is_numeric($id_auteur)
	/*AND $id_auteur>0*/ // reprise lors des restaurations
	) ?
	  "id_auteur=$id_auteur" :
	  (!strlen($connect_login) ? '' : "login=" . sql_quote($connect_login));

	if (!$where) return '';

	// Trouver les autres infos dans la table auteurs.
	// le champ 'quand' est utilise par l'agenda

	return sql_fetsel("*, en_ligne AS quand", "spip_auteurs", "$where AND statut!='5poubelle'");
}

// 
// Init des globales pour tout l'espace prive si visiteur connu
// Le tableau global visiteur_session contient toutes les infos pertinentes et
// a jour (tandis que $visiteur_session peut avoir des valeurs un peu datees
// s'il est pris dans le fichier de session)
// Les plus utiles sont aussi dans les variables simples ci-dessus
// si la globale est vide ce n'est pas un tableau, on la force pour empecher un warning

function auth_init_droits($row)
{
	global $connect_statut, $connect_toutes_rubriques, $connect_id_rubrique, $connect_login, $connect_id_auteur;

	$connect_id_auteur = $row['id_auteur'];
	$connect_login = $row['login'];
	$connect_statut = acces_statut($connect_id_auteur, $row['statut'], $row['bio']);

	
	$GLOBALS['visiteur_session'] = array_merge((array)$GLOBALS['visiteur_session'], $row);
	$r = @unserialize($row['prefs']);
	$GLOBALS['visiteur_session']['prefs'] =
	  (@isset($r['couleur'])) ? $r : array('couleur' =>1, 'display'=>0);

	// au cas ou : ne pas memoriser les champs sensibles
	unset($GLOBALS['visiteur_session']['pass']);
	unset($GLOBALS['visiteur_session']['htpass']);
	unset($GLOBALS['visiteur_session']['alea_actuel']);
	unset($GLOBALS['visiteur_session']['alea_futur']);

	// rajouter les sessions meme en mode auth_http
	// pour permettre les connexions multiples et identifier les visiteurs
	if (!isset($_COOKIE['spip_session'])) {
		$session = charger_fonction('session', 'inc');
		if ($spip_session = $session($row)) {
			include_spip('inc/cookie');
			spip_setcookie(
				'spip_session',
				$_COOKIE['spip_session'] = $spip_session,
				time() + 3600 * 24 * 14
			);
		}
	}

	// Etablir les droits selon le codage attendu
	// dans ecrire/index.php ecrire/prive.php

	// Pas autorise a acceder a ecrire ? renvoyer le tableau
	// A noter : le premier appel a autoriser() a le bon gout
	// d'initialiser $GLOBALS['visiteur_session']['restreint'],
	// qui ne figure pas dans le fichier de session
	include_spip('inc/autoriser');

	if (!autoriser('ecrire'))
		return $row;

	// autoriser('ecrire') ne laisse passer que les Admin et les Redac

	auth_trace($row);

	// Administrateurs
	if ($connect_statut == '0minirezo') {
		if (is_array($GLOBALS['visiteur_session']['restreint']))
			$connect_id_rubrique = $GLOBALS['visiteur_session']['restreint'];
		$connect_toutes_rubriques = !$connect_id_rubrique;
	} 
	// Pour les redacteurs, inc_version a fait l'initialisation minimale

	return ''; // i.e. pas de pb.
}

function auth_a_loger()
{
	$redirect = generer_url_public('login',
	"url=" . rawurlencode(self('&',true)), '&');

	// un echec au "bonjour" (login initial) quand le statut est
	// inconnu signale sans doute un probleme de cookies
	if (isset($_GET['bonjour']))
		$redirect = parametre_url($redirect,
			'var_erreur',
			(!isset($GLOBALS['visiteur_session']['statut'])
					? 'cookie'
					: 'statut'
			 ),
					  '&'
					  );
	return $redirect;
}

// http://doc.spip.org/@auth_trace
function auth_trace($row, $date='NOW()')
{
	// Indiquer la connexion. A la minute pres ca suffit.
	if (!is_numeric($connect_quand = $row['quand']))
		$connect_quand = strtotime($connect_quand);
	
	if ((time() - $connect_quand)  >= 60) {
		sql_updateq("spip_auteurs", array("en_ligne" => $date), "id_auteur=" .$row['id_auteur']);
	}
}
?>
