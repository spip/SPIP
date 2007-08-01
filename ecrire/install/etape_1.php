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

// http://doc.spip.org/@inc_install_1
function install_etape_1_dist()
{

	echo install_debut_html();

	// stopper en cas de grosse incompatibilite de l'hebergement
	tester_compatibilite_hebergement();

	list($adresse_db, $login_db) = login_hebergeur();
	$pass_db = '';

	$chmod = (isset($_GET['chmod']) AND preg_match(',^[0-9]+$,', $_GET['chmod']))? sprintf('%04o', $_GET['chmod']):'0777';
	// Recuperer les anciennes donnees pour plus de facilite (si presentes)
	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php')) {
		$s = @join('', @file(_FILE_CONNECT_INS . _FILE_TMP . '.php'));
		if (preg_match("#mysql_connect\([\"'](.*)[\"'],[\"'](.*)[\"'],[\"'](.*)[\"']\)#", $s, $regs)) {
			$adresse_db = $regs[1];
			$login_db = $regs[2];
		}
		else if (preg_match("#spip_connect_db\('(.*)','(.*)','(.*)','(.*)'#", $s, $regs)) {
			$adresse_db = $regs[1];
			if ($port_db = $regs[2]) $adresse_db .= ':'.$port_db;
			$login_db = $regs[3];
		}
	}
	if(@file_exists(_FILE_CHMOD_INS . _FILE_TMP . '.php')){
		$s = @join('', @file(_FILE_CHMOD_INS . _FILE_TMP . '.php'));
		if(preg_match("#define\('_SPIP_CHMOD', (.*)\)#", $s, $regs)) {
			$chmod = $regs[1]; 
		}
	}

	$req = array($adresse_db,$login_db,$pass_db);

	$predef = array(defined('_INSTALL_HOST_DB'), defined('_INSTALL_USER_DB'), defined('_INSTALL_PASS_DB'));

	// ces deux chaines de langues doivent etre reecrites
#	echo info_etape(_T('info_connexion_mysql'), _T('texte_connexion_mysql').aide ("install1"));
	echo info_etape(_L('Connexion &agrave; votre base de donn&eacute;es'),
			_L("Consultez les informations fournies par votre h&eacute;bergeur : vous devez y trouver le serveur de base de donn&eacute;es qu'il propose et vos identifiants personnels pour vous y connecter. SPIP sait utiliser MySQL (le plus r&eacute;pandu) et PostGres (encore exp&eacute;rimental)."));
	echo install_etape_1_form($req, $predef, "\n<input type='hidden' name='chmod' value='$chmod' />", 2);
	echo info_progression_etape(1,'etape_','install/');
	echo install_fin_html();
}

function install_etape_1_form($req, $predef, $hidden, $etape)
{

  return generer_form_ecrire('install', (
	  "\n<input type='hidden' name='etape' value='$etape' />" 
	. $hidden
	. (_request('echec')?
			("<p><b>"._T('avis_connexion_echec_1').
			"</b></p><p>"._T('avis_connexion_echec_2')."</p><p style='font-size: small;'>"._T('avis_connexion_echec_3')."</p>")
			:"")

	. '<fieldset><legend>'._L('Indiquer le serveur de base de donn&eacute;es')
	. "\n<select name='server_db'><option>mysql</option><option>pg</option></select></legend></fieldset>"
	. ($predef[0]
	? '<h3>'._T('install_adresse_base_hebergeur').'</h3>'
	: fieldset(_T('entree_base_donnee_1'),
		array(
			'adresse_db' => array(
				'label' => _T('entree_base_donnee_2'),
				'valeur' => $req[0]
			),
		)
	)
	)

	. ($predef[1]
	? '<h3>'._T('install_login_base_hebergeur ').'</h3>'
	: fieldset(_T('entree_login_connexion_1'),
		array(
			'login_db' => array(
				'label' => _T('entree_login_connexion_2'),
				'valeur' => $req[1]
			),
		)
	)
	)

	. ($predef[2]
	? '<h3>'._T('install_pass_base_hebergeur').'</h3>'
	: fieldset(_T('entree_mot_passe_1'),
		array(
			'pass_db' => array(
				'label' => _T('entree_mot_passe_2'),
				'valeur' => $req[2]
			),
		)
	)
	)

	. bouton_suivant()));

}

?>
