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

// http://doc.spip.org/@inc_install_1
function install_etape_1_dist()
{
	global $spip_lang_right;

	install_debut_html();

	// stopper en cas de grosse incompatibilite de l'hebergement
	tester_compatibilite_hebergement();

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_connexion_mysql')."</FONT>";

	echo "<P>"._T('texte_connexion_mysql');

	echo aide ("install1");

	list($adresse_db, $login_db) = login_hebergeur();
	$pass_db = '';

	$chmod = (isset($_GET['chmod']) AND preg_match(',^[0-9]+$,', $_GET['chmod']))? sprintf('%04o', $_GET['chmod']):'0777';
	// Recuperer les anciennes donnees pour plus de facilite (si presentes)
	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php')) {
		$s = @join('', @file(_FILE_CONNECT_INS . _FILE_TMP . '.php'));
		if (ereg("mysql_connect\([\"'](.*)[\"'],[\"'](.*)[\"'],[\"'](.*)[\"']\)", $s, $regs)) {
			$adresse_db = $regs[1];
			$login_db = $regs[2];
		}
		else if (ereg("spip_connect_db\('(.*)','(.*)','(.*)','(.*)','(.*)'\)", $s, $regs)) {
			$adresse_db = $regs[1];
			if ($port_db = $regs[2]) $adresse_db .= ':'.$port_db;
			$login_db = $regs[3];
		}
	}
	if(@file_exists(_FILE_CHMOD_INS . _FILE_TMP . '.php')){
		$s = @join('', @file(_FILE_CHMOD_INS . _FILE_TMP . '.php'));
		if(ereg("define\('_SPIP_CHMOD', (.*)\)", $s, $regs)) {
			$chmod = $regs[1]; 
		}
	}
	echo generer_url_post_ecrire('install');
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='2' />";
	echo "<INPUT TYPE='hidden' NAME='chmod' VALUE='$chmod' />";
	echo "<fieldset><label><B>"._T('entree_base_donnee_1')."</B><BR />\n</label>";
	echo "<p>"._T('entree_base_donnee_2')."</p>\n";
	echo "<INPUT TYPE='text' NAME='adresse_db' CLASS='formo' VALUE=\"$adresse_db\" SIZE='40' /></fieldset>";

	echo "<fieldset><label><B>"._T('entree_login_connexion_1')."</B><BR />\n</label>";
	echo "<p>"._T('entree_login_connexion_2')."</p>\n";
	echo "<INPUT TYPE='text' NAME='login_db' CLASS='formo' VALUE=\"$login_db\" SIZE='40' /></fieldset>";

	echo "<fieldset><label><B>"._T('entree_mot_passe_1')."</B><BR />\n</label>";
	echo "<p>"._T('entree_mot_passe_2')."</p>\n";
	echo "<INPUT TYPE='password' NAME='pass_db' CLASS='formo' VALUE=\"$pass_db\" SIZE='40' /></fieldset>";

	echo bouton_suivant();
	echo "</FORM>";

	install_fin_html();
}

?>
