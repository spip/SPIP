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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/headers');
include_spip('inc/acces');

function install_etape_4_dist()
{
	$login = _request('login');
	$email = _request('email');
	$nom = _request('nom');
	$pass = _request('pass');
	$pass_verif = _request('pass_verif');

	$server_db = defined('_INSTALL_SERVER_DB')
		? _INSTALL_SERVER_DB
		: _request('server_db');

	if($login!='' AND ($pass!=$pass_verif OR strlen($pass)<5 OR strlen($login)<3)) {
		echo minipres(
			'AUTO',
			"<h2>"._T('info_passes_identiques')."</h2>\n".
			"<p class='resultat'>"._T('avis_connexion_echec_2')."</p>"
		);
		exit;
	}

	if (@file_exists(_FILE_CHMOD_TMP))
		include(_FILE_CHMOD_TMP);
	else
		redirige_par_entete(generer_url_ecrire('install'));

	if (!@file_exists(_FILE_CONNECT_TMP))
		redirige_par_entete(generer_url_ecrire('install'));

	echo install_debut_html('AUTO', ' onload="document.getElementById(\'suivant\').focus();return false;"');

	echo info_etape(_T('info_derniere_etape'),
			_T('info_utilisation_spip')
	);

	# maintenant on connait le vrai charset du site s'il est deja configure
	# sinon par defaut lire_meta reglera _DEFAULT_CHARSET
	# (les donnees arrivent de toute facon postees en _DEFAULT_CHARSET)

	lire_metas();
	if ($login) {
		include_spip('inc/charsets');

		$nom = (importer_charset($nom, _DEFAULT_CHARSET));
		$login = (importer_charset($login, _DEFAULT_CHARSET));
		$email = (importer_charset($email, _DEFAULT_CHARSET));
		# pour le passwd, bizarrement il faut le convertir comme s'il avait
		# ete tape en iso-8859-1 ; car c'est en fait ce que voit md5.js
		$pass = unicode2charset(utf_8_to_unicode($pass), 'iso-8859-1');		$mdpass = md5($pass);
		$htpass = generer_htpass($pass);
		$alea = creer_uniqid();
		$id_auteur = sql_getfetsel("id_auteur", "spip_auteurs", "login=" . _q($login));
		if ($id_auteur !== NULL) {
			sql_updateq('spip_auteurs', array("nom"=> $nom, 'email'=> $email, 'login'=>$login, 'pass'=>$mdpass, 'alea_actuel'=>'', 'alea_futur'=> $alea, 'htpass'=>$htpass, 'statut'=>'0minirezo'), "id_auteur=$id_auteur");
		}
		else {
			sql_insertq('spip_auteurs', array(
				'nom' => $nom,
				'email' => $email,
				'login' => $login,
				'pass' => $mdpass,
				'htpass' => $htpass,
				'alea_futur' => $alea,
				'statut' =>'0minirezo'));
		}

		// inserer email comme email webmaster principal
		// (sauf s'il est vide: cas de la re-installation)
		if ($email)
			ecrire_meta('email_webmaster', $email);

		// Ici on va connecter directement celui qui vient de creer son login
		// on ne lui met ni cookie d'admin ni connexion longue
		if ($auth_spip = charger_fonction('auth_spip', 'inc', true)
		AND $row_auteur = $auth_spip($login, $pass)
		AND $session = charger_fonction('session', 'inc')
		AND $cookie_session = $session($row_auteur))
			spip_setcookie('spip_session', $cookie_session);
	}

	$config = charger_fonction('config', 'inc');
	$config();

	$htpasswd = _DIR_TMP . _AUTH_USER_FILE;
	spip_unlink($htpasswd);
	spip_unlink($htpasswd."-admin");
	ecrire_acces();

	// on l'envoie dans l'espace prive
	echo generer_form_ecrire('accueil', bouton_suivant());
	echo info_progression_etape(4,'etape_','install/');
	echo install_fin_html();

	// et on perennise

	$f = str_replace( _FILE_TMP_SUFFIX, '.php', _FILE_CHMOD_TMP);
	if (!@rename(_FILE_CHMOD_TMP, $f)) {
		if (@copy(_FILE_CHMOD_TMP, $f))
			spip_unlink(_FILE_CHMOD_TMP);
	}

	$f = str_replace( _FILE_TMP_SUFFIX, '.php', _FILE_CONNECT_TMP);
	if (!@rename(_FILE_CONNECT_TMP, $f)) {
		if (@copy(_FILE_CONNECT_TMP, $f))
			@spip_unlink(_FILE_CONNECT_TMP);
	}

}

?>
