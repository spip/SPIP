<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@install_etape_1_dist
function install_etape_1_dist()
{
	echo install_debut_html();

	// stopper en cas de grosse incompatibilite de l'hebergement
	tester_compatibilite_hebergement();

	// Recuperer les anciennes donnees pour plus de facilite (si presentes)
	$s = !@is_readable(_FILE_CONNECT_TMP) ? ''
	  : analyse_fichier_connection(_FILE_CONNECT_TMP);

	list($adresse_db, $login_db) = $s ? $s : login_hebergeur();

	$chmod = (isset($_GET['chmod']) AND preg_match(',^[0-9]+$,', $_GET['chmod']))? sprintf('%04o', $_GET['chmod']):'0777';

	if(@is_readable(_FILE_CHMOD_TMP)){
		$s = @join('', @file(_FILE_CHMOD_TMP));
		if(preg_match("#define\('_SPIP_CHMOD', (.*)\)#", $s, $regs)) {
			$chmod = $regs[1]; 
		}
	}
	
	

	$db = array($adresse_db, _T('entree_base_donnee_2'));
	$login = array($login_db, _T('entree_login_connexion_2'));
	$pass = array($pass_db, _T('entree_mot_passe_2'));

	$predef = array(defined('_INSTALL_SERVER_DB') ? _INSTALL_SERVER_DB:'',
			defined('_INSTALL_HOST_DB'),
			defined('_INSTALL_USER_DB'),
			defined('_INSTALL_PASS_DB'));


	echo info_progression_etape(1,'etape_','install/');

	// ces deux chaines de langues doivent etre reecrites
#	echo info_etape(_T('info_connexion_mysql'), _T('texte_connexion_mysql').aide ("install1", true));
	echo info_etape(_T('info_connexion_base_donnee'));
	echo install_connexion_form($db, $login, $pass, $predef, "\n<input type='hidden' name='chmod' value='$chmod' />", 2);
	echo install_fin_html();
}


?>
