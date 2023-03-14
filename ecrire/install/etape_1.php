<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Affichage de l'écran d'installation (étape 1 : tests des répertoires
 * et hébergement, et demande d'identifiants de connexion à la BDD)
 *
 * @package SPIP\Core\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Affichage de l'étape 1 d'installation : tests des répertoires
 * et hébergement ; demande d'identifiants de connexion à la BDD
 *
 * Teste que l'hébergement est compatible, que les répertoires qui doivent
 * être accessibles en écriture le sont effectivement, auquel cas demande les identifiants
 * de connexion à une base de données
 *
 * @uses tester_compatibilite_hebergement()
 * @uses analyse_fichier_connection()
 *
 */
function install_etape_1_dist() {

	$minipage = new Spip\Afficher\Minipage\Installation();
	echo $minipage->installDebutPage();

	// stopper en cas de grosse incompatibilite de l'hebergement
	tester_compatibilite_hebergement();

	// Recuperer les anciennes donnees pour plus de facilite (si presentes)
	$s = @is_readable(_FILE_CONNECT_TMP) ? analyse_fichier_connection(_FILE_CONNECT_TMP) : '';

	[$adresse_db, $login_db] = $s ?: ['localhost', ''];

	$chmod = (isset($_GET['chmod']) && preg_match(',^\d+$,', $_GET['chmod'])) ?
		sprintf('%04o', $_GET['chmod']) : '0777';

	if (@is_readable(_FILE_CHMOD_TMP)) {
		$s = @implode('', @file(_FILE_CHMOD_TMP));
		if (preg_match("#define\('_SPIP_CHMOD', (.*)\)#", $s, $regs)) {
			$chmod = $regs[1];
		}
	}


	$db = [$adresse_db, _T('entree_base_donnee_2')];
	$login = [$login_db, _T('entree_login_connexion_2')];
	$pass = ['', _T('entree_mot_passe_2')];

	$predef = [
		defined('_INSTALL_SERVER_DB') ? _INSTALL_SERVER_DB : '',
		defined('_INSTALL_HOST_DB'),
		defined('_INSTALL_USER_DB'),
		defined('_INSTALL_PASS_DB')
	];


	echo info_progression_etape(1, 'etape_', 'install/');

	// ces deux chaines de langues doivent etre reecrites
#	echo info_etape(_T('info_connexion_mysql'), _T('texte_connexion_mysql').aide ("install1", true));
	echo info_etape(_T('info_connexion_base_donnee'));
	echo install_connexion_form($db, $login, $pass, $predef, "\n<input type='hidden' name='chmod' value='$chmod' />", 2);
	echo $minipage->installFinPage();
}
