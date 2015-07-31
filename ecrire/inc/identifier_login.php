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


// http://doc.spip.org/@inc_identifier_login_dist
function inc_identifier_login_dist($session_login, $session_password, $session_md5pass="", $session_md5next="", $session_remember=''){

	$auteur = verifier_login($session_login, $session_password, $session_md5pass, $session_md5next);

	// OK on a ete authentifie, on se connecte
	if ($auteur) {
		spip_log("login de $session_login vers $redirect (".$auteur['auth']);

		// Prevoir de demander un cookie de correspondance
		if ($auteur['statut'] == '0minirezo')
			$set_cookie_admin = "@".$session_login;

		$session = charger_fonction('session', 'inc');
		$cookie_session = $session($auteur);

		// La case "rester connecte quelques jours"
		$session_remember = ($session_remember == 'oui') ? 'perma' : '';
		if ($session_remember)
			spip_setcookie('spip_session', $cookie_session, time() + 2 * _RENOUVELLE_ALEA);
		else
			spip_setcookie('spip_session', $cookie_session);

		$prefs = ($auteur['prefs']) ? unserialize($auteur['prefs']) : array();
		$prefs['cnx'] = $session_remember;

		sql_updateq('spip_auteurs', array('prefs' => serialize($prefs)), "id_auteur = " . $auteur['id_auteur']);
	}

	$cook = isset($_COOKIE['spip_admin']) ? $_COOKIE['spip_admin'] : '';

	// Ajout de cookie d'admin
	# rien a faire ici, trouver ou le mettre
	/*if (isset($set_cookie_admin)
	  OR $set_cookie_admin = _request('cookie_admin')
	) {
		spip_setcookie('spip_admin', $set_cookie_admin,	time() + 14 * 24 * 3600);
	}*/
	return $auteur;
}

// http://doc.spip.org/@verifier_login
function verifier_login($session_login, $session_password, $session_md5pass="", $session_md5next=""){
	if (!spip_connect()) {
		include_spip('inc/minipres');
		echo minipres(_T('info_travaux_titre'),  _T('titre_probleme_technique'));
		exit;
	}

	$auteur = array();

	// Essayer tour a tour les differentes sources d'authenfication
	// on s'en souviendra dans visiteur_session['auth']
	$sources_auth = $GLOBALS['liste_des_authentifications'];
	while (!$auteur
	AND list(,$methode) = each($sources_auth)) {
		if ($auth = charger_fonction('auth_'.$methode, 'inc', true)
		AND $auteur = $auth($session_login, $session_password, $session_md5pass,$session_md5next)) {
			$auteur['auth'] = $methode;
		} else {
			spip_log("pas de connexion avec $methode");
		}
	}
	return $auteur;
}


// http://doc.spip.org/@informer_login
function informer_login($login){
	$row =  sql_fetsel('id_auteur,login,alea_actuel,alea_futur,prefs,source', 'spip_auteurs', "login=" . sql_quote($login));
	// Retrouver ceux qui signent de leur nom ou email
	if (!$row AND !spip_connect_ldap()) {
		$row = sql_fetsel('id_auteur,login,alea_actuel,alea_futur,prefs,source', 'spip_auteurs', "(nom = " . sql_quote($login) . " OR email = " . sql_quote($login) . ") AND login<>'' AND statut<>'5poubelle'");
	}
	if ($row) {
		// desactiver le hash md5 si pas auteur spip ?
		if ($row['source']!=='spip'){
			$row['alea_actuel']='';
			$row['alea_futur']='';
		}
		unset($row['source']);
		$prefs = unserialize($row['prefs']);
		$row['cnx'] = ($prefs['cnx'] == 'perma') ? '1' : '0';
		unset($row['prefs']);
		
		$row['logo'] = recuperer_fond('formulaires/inc-logo_auteur', array('id_auteur'=>$row['id_auteur']));

	}
	return $row;	
}

?>
