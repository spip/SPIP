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

function inc_install_6()
{
	global $email,$login,$nom,$pass,$spip_lang_right;

	install_debut_html();


	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_derniere_etape')."</B></FONT>";
	echo "<P>";
	echo "<B>"._T('info_code_acces')."</B>";
	echo "<P>"._T('info_utilisation_spip');

	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php'))
		include(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

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
		$result = spip_query("SELECT id_auteur FROM spip_auteurs WHERE login=" . spip_abstract_quote($login));

		unset($id_auteur);
		if ($row = spip_fetch_array($result)) $id_auteur = $row['id_auteur'];

		$mdpass = md5($pass);
		$htpass = generer_htpass($pass);

		if ($id_auteur) {
			spip_query("UPDATE spip_auteurs SET nom=" . spip_abstract_quote($nom) . ", email=" . spip_abstract_quote($email) . ", login=" . spip_abstract_quote($login) . ", pass='$mdpass', alea_actuel='', alea_futur=FLOOR(32000*RAND()), htpass='$htpass', statut='0minirezo' WHERE id_auteur=$id_auteur");
		}
		else {
			spip_query("INSERT INTO spip_auteurs (nom, email, login, pass, htpass, alea_futur, statut) VALUES(" . spip_abstract_quote($nom) . "," . spip_abstract_quote($email) . "," . spip_abstract_quote($login) . ",'$mdpass','$htpass',FLOOR(32000*RAND()),'0minirezo')");
		}

		// inserer email comme email webmaster principal
		spip_query("REPLACE spip_meta (nom, valeur) VALUES ('email_webmaster', " . spip_abstract_quote($email) . ")");
	}

	include_spip('inc/config');
	init_config();

	include_spip('inc/acces');
	$htpasswd = _DIR_SESSIONS . _AUTH_USER_FILE;
	@unlink($htpasswd);
	@unlink($htpasswd."-admin");
	ecrire_acces();

	if (!@rename(_FILE_CONNECT_INS . _FILE_TMP . '.php',
		    _FILE_CONNECT_INS . '.php')) {
		copy(_FILE_CONNECT_INS . _FILE_TMP . '.php', 
		     _FILE_CONNECT_INS . '.php');
		@unlink(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	}

	echo "<form action='./' method='post'>";
	echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";
	echo "</FORM>";

	ecrire_metas();

	install_fin_html();
}

?>