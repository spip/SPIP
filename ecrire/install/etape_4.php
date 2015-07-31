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

function install_etape_4_dist()
{
	global $email,$login,$nom,$pass, $pass_verif,$spip_lang_right;

	if($login!='' AND ($pass!=$pass_verif OR strlen($pass)<5 OR strlen($login)<3)) {
		echo minipres(
			'AUTO',
			"<h2>"._T('info_passes_identiques')."</h2>\n".
			"<p class='resultat'>"._T('avis_connexion_echec_2')."</p>"
		);
		exit;
	}

	echo install_debut_html('AUTO', ' onload="document.getElementById(\'suivant\').focus();return false;"');

	echo info_etape(_T('info_derniere_etape'),
			"<b>"._T('info_code_acces')."</b><br />" .
			_T('info_utilisation_spip')
	);

	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php'))
		include(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	if (@file_exists(_FILE_CHMOD_INS . _FILE_TMP . '.php'))
		include(_FILE_CHMOD_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	if (!@rename(_FILE_CONNECT_INS . _FILE_TMP . '.php',
		    _DIR_ETC . 'connect.php')) {
		copy(_FILE_CONNECT_INS . _FILE_TMP . '.php', 
		     _DIR_ETC . 'connect.php');
		@unlink(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	}

	# maintenant on connait le vrai charset du site s'il est deja configure
	# sinon par defaut inc/meta reglera _DEFAULT_CHARSET
	# (les donnees arrivent de toute facon postees en _DEFAULT_CHARSET)
	include_spip('inc/meta');
	lire_metas();

	if ($login) {
		include_spip('inc/charsets');

		$nom = (importer_charset($nom, _DEFAULT_CHARSET));
		$login = (importer_charset($login, _DEFAULT_CHARSET));
		$email = (importer_charset($email, _DEFAULT_CHARSET));
		# pour le passwd, bizarrement il faut le convertir comme s'il avait
		# ete tape en iso-8859-1 ; car c'est en fait ce que voit md5.js
		$pass = unicode2charset(utf_8_to_unicode($pass), 'iso-8859-1');
		$result = spip_query("SELECT id_auteur FROM spip_auteurs WHERE login=" . _q($login));

		unset($id_auteur);
		if ($row = spip_fetch_array($result)) $id_auteur = $row['id_auteur'];

		$mdpass = md5($pass);
		$htpass = generer_htpass($pass);

		if ($id_auteur) {
			spip_query("UPDATE spip_auteurs SET nom=" . _q($nom) . ", email=" . _q($email) . ", login=" . _q($login) . ", pass='$mdpass', alea_actuel='', alea_futur=FLOOR(32000*RAND()), htpass='$htpass', statut='0minirezo' WHERE id_auteur=$id_auteur");
		}
		else {
			spip_query("INSERT INTO spip_auteurs (nom, email, login, pass, htpass, alea_futur, statut) VALUES(" . _q($nom) . "," . _q($email) . "," . _q($login) . ",'$mdpass','$htpass',FLOOR(32000*RAND()),'0minirezo')");
		}

		// inserer email comme email webmaster principal
		spip_query("REPLACE spip_meta (nom, valeur) VALUES ('email_webmaster', " . _q($email) . ")");
	}

	include_spip('inc/config');
	init_config();

	include_spip('inc/acces');
	$htpasswd = _DIR_TMP . _AUTH_USER_FILE;
	@unlink($htpasswd);
	@unlink($htpasswd."-admin");
	ecrire_acces();
	ecrire_metas();

	if (!@rename(_FILE_CHMOD_INS . _FILE_TMP . '.php',
		    _DIR_ETC . 'chmod.php')) {
		copy(_FILE_CHMOD_INS . _FILE_TMP . '.php', 
		     _DIR_ETC . 'chmod.php');
		@unlink(_FILE_CHMOD_INS . _FILE_TMP . '.php');
	}

	echo generer_form_ecrire('accueil', bouton_suivant());
	echo info_progression_etape(4,'etape_','install/');
	echo install_fin_html();
}

?>
