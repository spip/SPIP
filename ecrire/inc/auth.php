<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

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
	include_spip('action/editer_auteur');
	instituer_auteur($id_auteur,array('statut'=> $s));
	include_spip('inc/modifier');
	revision_auteur($id_auteur, array('bio'=>''));
	include_spip('inc/session');
	session_set('statut',$s);
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
	// C'est soit parce que la base est inutilisable,
	// soit parce que la table des auteurs a changee (restauration etc)
	// Pas la peine d'insister.
	// Renvoyer le nom fautif et une URL de remise a zero

	if (spip_connect())
		return array('login' => $connect_login,
			'site' => generer_url_public('', "action=logout&amp;logout=prive"));

	$n = intval(sql_errno());
	spip_log("Erreur base de donnees $n " . sql_error());
	return $n ? $n : 1;
}

// fonction appliquee par ecrire/index sur le resultat de la precedente
// en cas de refus de connexion.
// Retourne un message a afficher ou redirige illico.

function auth_echec($raison)
{
	include_spip('inc/minipres');
	include_spip('inc/headers');
	// pas authentifie. Pourquoi ?
	if (is_string($raison)) {
		// redirection vers une page d'authentification
		// on ne revient pas de cette fonction
		// sauf si pb de header
		$raison = redirige_formulaire($raison);
	} elseif (is_int($raison)) {
		// erreur SQL a afficher
		$raison = minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'). "<p><tt>".sql_errno()." ".sql_error()."</tt></p>");
	} elseif (@$raison['statut']) {
		// un simple visiteur n'a pas acces a l'espace prive
		spip_log("connexion refusee a " . @$raison['id_auteur']);
		$raison = minipres(_T('avis_erreur_connexion'),_T('avis_erreur_visiteur'));
	} else {
		// auteur en fin de droits ...
		$h = $raison['site'];
		$raison = minipres(_T('avis_erreur_connexion'),
				"<br /><br /><p>"
				. _T('texte_inc_auth_1',
				array('auth_login' => $raison['login']))
				. " <a href='$h'>"
				.  _T('texte_inc_auth_2')
				. "</a>"
				. _T('texte_inc_auth_3'));
	}
	return $raison;
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
		if (
			(isset($_SERVER['PHP_AUTH_USER']) AND isset($_SERVER['PHP_AUTH_PW'])
						AND $r = lire_php_auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
			OR
			// Si auth http differtente de basic, PHP_AUTH_PW
			// est indisponible mais tentons quand meme pour
			// autocreation via LDAP
			(isset($_SERVER['REMOTE_USER'])
						AND $r = lire_php_auth($_SERVER['PHP_AUTH_USER'] = $_SERVER['REMOTE_USER'], ''))
			) {
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
		else if (isset($_SERVER['REMOTE_USER']))
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
function auth_trace($row, $date=null)
{
	// Indiquer la connexion. A la minute pres ca suffit.
	if (!is_numeric($connect_quand = $row['quand']))
		$connect_quand = strtotime($connect_quand);

	if (is_null($date))
		$date = date('Y-m-d H:i:s');

	if (abs(strtotime($date) - $connect_quand)  >= 60) {
		sql_updateq("spip_auteurs", array("en_ligne" => $date), "id_auteur=" .$row['id_auteur']);
	}
}


/** ----------------------------------------------------------------------------
 * API Authentification, gestion des identites centralisees
 */

/**
 * Fonction aiguillage, privee
 * @param string $fonction
 * @param array $args
 * @param mixed $defaut
 * @return mixed
 */
function auth_administrer($fonction,$args,$defaut=false){
	$auth_methode = array_shift($args);
	$auth_methode = $auth_methode ? $auth_methode : 'spip'; // valeur par defaut au cas ou
	if ($auth = charger_fonction($auth_methode,'auth',true)
		AND function_exists($f="auth_{$auth_methode}_$fonction")
	)
		return call_user_func_array($f, $args);
	else
		return $defaut;
}

/**
 * Pipeline pour inserer du contenu dans le formulaire de login
 *
 * @param array $flux
 * @return array
 */
function auth_formulaire_login($flux){
	foreach ($GLOBALS['liste_des_authentifications'] as $methode)
		$flux = auth_administrer('formulaire_login',array($methode,$flux),$flux);
	return $flux;
}



/**
 * Retrouver le login interne lie a une info login saisie
 * la saisie peut correspondre a un login delegue
 * qui sera alors converti en login interne apres verification
 *
 * @param string $login
 * @param string $serveur
 * @return string/bool
 */
function auth_retrouver_login($login, $serveur=''){
	if (!spip_connect($serveur)) {
		include_spip('inc/minipres');
		echo minipres(_T('info_travaux_titre'),
			      _T('titre_probleme_technique'));
		exit;
	}

	foreach ($GLOBALS['liste_des_authentifications'] as $methode) {
		if ($auteur = auth_administrer('retrouver_login',array($methode, $login, $serveur))) {
			return $auteur;
		}
	}
	return false;
}


/**
 * informer sur un login
 * Ce dernier transmet le tableau ci-dessous a la fonction JS informer_auteur
 * Il est invoque par la fonction JS actualise_auteur via la globale JS
 * page_auteur=#URL_PAGE{informer_auteur} dans le squelette login
 * N'y aurait-il pas plus simple ?
 *
 * @param string $login
 * @param string $serveur
 * @return array
 */
function auth_informer_login($login, $serveur=''){
	if (!$login
		OR !$login = auth_retrouver_login($login, $serveur)
		OR !$row = sql_fetsel('*','spip_auteurs','login='.sql_quote($login),'','','','',$serveur)
		)
		return array();

	$prefs = unserialize($row['prefs']);
	$infos = array(
		'id_auteur'=>$row['id_auteur'],
		'login'=>$row['login'],
		'cnx' => ($prefs['cnx'] == 'perma') ? '1' : '0',
		'logo' => recuperer_fond('formulaires/inc-logo_auteur', $row),
	);

	// desactiver le hash md5 si pas auteur spip ?
	if ($row['source']!=='spip'){
		$row['alea_actuel']= '';
		$row['alea_futur']= '';
	}
	verifier_visiteur();

	return auth_administrer('informer_login',array($row['source'],$infos, $row, $serveur),$infos);
}


/**
 * Essayer les differentes sources d'authenfication dans l'ordre specifie.
 * S'en souvenir dans visiteur_session['auth']
 *
 * @param string $login
 * @param string $password
 * @param string $serveur
 * @return mixed
 */
function auth_identifier_login($login, $password, $serveur=''){
	$erreur = "";
	foreach ($GLOBALS['liste_des_authentifications'] as $methode) {
		if ($auth = charger_fonction($methode, 'auth',true)){
			$auteur = $auth($login, $password, $serveur);
			if (is_array($auteur) AND count($auteur)) {
				spip_log("connexion de $login par methode $methode");
				$auteur['auth'] = $methode;
				return $auteur;
			}
			elseif (is_string($auteur))
				$erreur .= "$auteur ";
		}
	}
	return $erreur;
}

/**
 * Fournir une url de retour apres login par un SSO
 * pour finir l'authentification
 *
 * @param string $auth_methode
 * @param string $login
 * @param string $serveur
 * @return string
 */
function auth_url_retour_login($auth_methode, $login, $redirect='', $serveur=''){
	$securiser_action = charger_fonction('securiser_action','inc');
	return $securiser_action('auth', "$auth_methode/$login", $redirect, true);
}

function auth_terminer_identifier_login($auth_methode, $login, $serveur=''){
	$args = func_get_args();
	$auteur = auth_administrer('terminer_identifier_login',$args);
	return $auteur;
}

 /**
  * Loger un auteur suite a son identification
  *
  * @param array $auteur
  */
 function auth_loger($auteur, $refuse_cookie_admin=false){
	if (!is_array($auteur) OR !count($auteur))
		return false;

	$session = charger_fonction('session', 'inc');
	$session($auteur);
	$p = ($auteur['prefs']) ? unserialize($auteur['prefs']) : array();
	$p['cnx'] = ($auteur['cookie'] == 'oui') ? 'perma' : '';
	$p = array('prefs' => serialize($p));
	sql_updateq('spip_auteurs', $p, "id_auteur=" . $auteur['id_auteur']);

	if ($auteur['statut'] == 'nouveau') {
		$session(); // charger la session car on va la modifier
		$auteur['statut'] = acces_statut($auteur['id_auteur'], $auteur['statut'], $auteur['bio']);
	}

	// Si on est admin, poser le cookie de correspondance
	include_spip('inc/cookie');
	if (!$refuse_cookie_admin AND $auteur['statut'] == '0minirezo') {
		spip_setcookie('spip_admin', '@'.$auteur['login'],
		time() + 7 * 24 * 3600);
	}
	// sinon le supprimer ...
	else {
		spip_setcookie('spip_admin', '',1);
	}

	//  bloquer ici le visiteur qui tente d'abuser de ses droits
	verifier_visiteur();
	return true;
}


function auth_deloger(){
	$logout = charger_fonction('logout','action');
	$logout();
}

/**
 * Tester la possibilite de modifier le login d'authentification
 * pour la methode donnee
 *
 * @param string $auth_methode
 * @param string $serveur
 * @return bool
 */
function auth_autoriser_modifier_login($auth_methode, $serveur=''){
	$args = func_get_args();
	return auth_administrer('autoriser_modifier_login',$args);
}

/**
 * Verifier la validite d'un nouveau login pour modification
 * pour la methode donnee
 *
 * @param string $auth_methode
 * @param string $new_login
 * @param int $id_auteur
 * @param string $serveur
 * @return string
 *  message d'erreur ou chaine vide si pas d'erreur
 */
function auth_verifier_login($auth_methode, $new_login, $id_auteur=0, $serveur=''){
	$args = func_get_args();
	return auth_administrer('verifier_login',$args,'');
}

/**
 * Modifier le login d'un auteur pour la methode donnee
 *
 * @param string $auth_methode
 * @param string $new_login
 * @param int $id_auteur
 * @param string $serveur
 * @return bool
 */
function auth_modifier_login($auth_methode, $new_login, $id_auteur, $serveur=''){
	$args = func_get_args();
	return auth_administrer('modifier_login',$args);
}

/**
 * Tester la possibilite de modifier le pass
 * pour la methode donnee
 *
 * @param string $auth_methode
 * @param string $serveur
 * @return bool
 *	succes ou echec
 */
function auth_autoriser_modifier_pass($auth_methode, $serveur=''){
	$args = func_get_args();
	return auth_administrer('autoriser_modifier_pass',$args);
}

/**
 * Verifier la validite d'un pass propose pour modification
 * pour la methode donnee
 *
 * @param string $auth_methode
 * @param string $login
 * @param string $new_pass
 * @param int $id_auteur
 * @param string $serveur
 * @return string
 *	message d'erreur ou chaine vide si pas d'erreur
 */
function auth_verifier_pass($auth_methode, $login, $new_pass, $id_auteur=0, $serveur=''){
	$args = func_get_args();
	return auth_administrer('verifier_pass',$args,'');
}

/**
 * Modifier le mot de passe d'un auteur
 * pour la methode donnee
 *
 * @param string $auth_methode
 * @param string $login
 * @param string $new_pass
 * @param int $id_auteur
 * @param string $serveur
 * @return bool
 *	succes ou echec
 */
function auth_modifier_pass($auth_methode, $login, $new_pass, $id_auteur, $serveur=''){
	$args = func_get_args();
	return auth_administrer('modifier_pass',$args);
}

/**
 * Synchroniser un compte sur une base distante pour la methode
 * donnee lorsque des modifications sont faites dans la base auteur
 *
 * @param string $auth_methode
 *   ici true permet de forcer la synchronisation de tous les acces pour toutes les methodes
 * @param int $id_auteur
 * @param array $champs
 * @param array $options
 * @param string $serveur
 * @return void
 */
function auth_synchroniser_distant($auth_methode=true, $id_auteur=0, $champs=array(), $options = array(), $serveur=''){
	$args = func_get_args();
	if ($auth_methode===true OR (isset($options['all']) AND $options['all']==true)){
		$options['all'] = true; // ajouter une option all=>true pour chaque auth
		$args = array(true, $id_auteur, $champs, $options, $serveur);
		foreach ($GLOBALS['liste_des_authentifications'] as $methode) {
			array_shift($args);
			array_unshift($args,$methode);
			auth_administrer('synchroniser_distant',$args);
		}
	}
	else
		auth_administrer('synchroniser_distant',$args);
}


/**
 *
 * @param string $login
 * @param string $pw
 * @param string $serveur
 * @return array
 */
function lire_php_auth($login, $pw, $serveur=''){

	$row = sql_fetsel('*', 'spip_auteurs', 'login=' . sql_quote($login),'','','','',$serveur);

	if (!$row) {
		if (spip_connect_ldap($serveur)
		AND $auth_ldap = charger_fonction('ldap', 'auth', true))
			return $auth_ldap($login, $pw, $serveur);
		return false;
	}
	// su pas de source definie
	// ou auth/xxx introuvable, utiliser 'spip'
	if (!$auth_methode = $row['source']
		OR !$auth = charger_fonction($auth_methode, 'auth', true))
		$auth = charger_fonction('spip', 'auth', true);

	$auteur='';
	if ($auth)
		$auteur = $auth($login, $pw, $serveur);
	// verifier que ce n'est pas un message d'erreur
	if (is_array($auteur) AND count($auteur))
		return $auteur;
	return false;
}

/**
 * entete php_auth (est-encore utilise ?)
 *
 * @param <type> $pb
 * @param <type> $raison
 * @param <type> $retour
 * @param <type> $url
 * @param <type> $re
 * @param <type> $lien
 */
function ask_php_auth($pb, $raison, $retour, $url='', $re='', $lien='') {
	@Header("WWW-Authenticate: Basic realm=\"espace prive\"");
	@Header("HTTP/1.0 401 Unauthorized");
	$ici = generer_url_ecrire();
	echo "<b>$pb</b><p>$raison</p>[<a href='$ici'>$retour</a>] ";
	if ($url) {
		echo "[<a href='", generer_url_action('cookie',"essai_auth_http=oui&$url"), "'>$re</a>]";
	}

	if ($lien)
		echo " [<a href='$ici'>"._T('login_espace_prive')."</a>]";
	exit;
}
?>
