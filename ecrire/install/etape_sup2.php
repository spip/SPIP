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
include_spip('base/abstract_sql');

function install_bases_sup($adresse_db, $login_db, $pass_db,  $server_db, $sup_db){

	if (!($GLOBALS['connexions'][$server_db] = spip_connect_db($adresse_db, 0, $login_db, $pass_db, '', $server_db)))

		return "<!-- connection perdue -->";

	if (!sql_selectdb($sup_db, $server_db))
		return "<!-- base inaccessible -->";


	$q = sql_showbase('%', $server_db);

	$tables = '';
	while($r = sql_fetch($q)) {
		$tables .= "<li>" . array_shift($r) . "</li>\n";
	}
	
	$res = _L('Tables de la base') . "<ol>" . $tables . "</ol>\n";

	if (preg_match(',(.*):(.*),', $adresse_db, $r))
		list(,$adresse_db, $port) = $r;
	else
		$port = '';

	$conn = "spip_connect_db("
	. "'$adresse_db','$port','$login_db','"
	. addcslashes($pass_db, "'\\") . "','$sup_db'"
	. ",'$server_db', '');\n";

	install_fichier_connexion(_DIR_CONNECT . $sup_db . '.php', $conn);

	return $res;
}

function install_etape_sup2_dist()
{
	if (file_exists(_FILE_CONNECT_TMP))
			include(_FILE_CONNECT_TMP);
	else
			redirige_par_entete(generer_url_ecrire('install'));
	
	if (file_exists(_FILE_CHMOD_TMP))
			include(_FILE_CHMOD_TMP);
	else
			redirige_par_entete(generer_url_ecrire('install'));

	$adresse_db = defined('_INSTALL_HOST_DB')
		? _INSTALL_HOST_DB
		: _request('adresse_db');

	$login_db = defined('_INSTALL_USER_DB')
		? _INSTALL_USER_DB
		: _request('login_db');

	$pass_db = defined('_INSTALL_PASS_DB')
		? _INSTALL_PASS_DB
		: _request('pass_db');

	$server_db = defined('_INSTALL_SERVER_DB')
		? _INSTALL_SERVER_DB
		: _request('server_db');

	$sel_db = defined('_INSTALL_NAME_DB')
		? _INSTALL_NAME_DB
		: _request('sel_db');

	$choix_db = _request('choix_db');

	if (!$choix_db)
		$res = "<!-- il ne sait pas ce qu'il veut -->";
	else {
		$res = install_bases_sup($adresse_db, $login_db, $pass_db,  $server_db, $choix_db);

		if ($res[1]=='!')
			$res .= "<p class='resultat'><b>"._T('avis_operation_echec')."</b></p>";

		else {
			$res =  "<p class='resultat'><b>"
			  . _L('base @base@ reconnue', 
			       array('base' => $choix_db))
			  . "</b></p>"
			  . $res;
		}
	}

	$res .= generer_form_ecrire('install',
			"\n<input type='hidden' name='etape' value='4' />"
			. (defined('_INSTALL_NAME_DB') ? ''
			   :  ("\n<input type='hidden' name='sel_db' value='"
			       . $sel_db
			       . "' />"))
			. predef_ou_cache($adresse_db,$login_db,$pass_db, $server_db)
			. bouton_suivant());

	echo install_debut_html();
	echo $res;
	echo install_fin_html();
}

?>
