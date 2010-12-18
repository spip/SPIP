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

include_spip('inc/headers');
include_spip('base/abstract_sql');

// http://doc.spip.org/@install_bases_sup
function install_bases_sup($adresse_db, $login_db, $pass_db,  $server_db, $sup_db){

	if (!($GLOBALS['connexions'][$server_db] = spip_connect_db($adresse_db, 0, $login_db, $pass_db, '', $server_db)))

		return "<!-- connection perdue -->";

	$GLOBALS['connexions'][$server_db][$GLOBALS['spip_sql_version']]
	= $GLOBALS['spip_' . $server_db .'_functions_' . $GLOBALS['spip_sql_version']];

	if (!sql_selectdb($sup_db, $server_db))
		return "<!-- base inaccessible -->";

	$tables = sql_alltable('%', $server_db);

	if (!$tables)
	  $res = _T('install_pas_table');
	else {
	  $res = _T('install_tables_base')
	    . "<ol style='text-align: left'>\n<li>"
	    . join("</li>\n<li>", $tables)
	    . "</li>\n</ol>\n";
	}

	if (preg_match(',(.*):(.*),', $adresse_db, $r))
		list(,$adresse_db, $port) = $r;
	else
		$port = '';

	$adresse_db = addcslashes($adresse_db,"'\\");
	$port = addcslashes($port,"'\\");
	$login_db = addcslashes($login_db,"'\\");
	$pass_db = addcslashes($pass_db,"'\\");
	$sup_db = addcslashes($sup_db,"'\\");
	$server_db = addcslashes($server_db,"'\\");

	$conn = install_mode_appel($server_db)
	. "spip_connect_db("
	. "'$adresse_db','$port','$login_db',"
	. "'$pass_db','$sup_db'"
	. ",'$server_db', '');\n";

	install_fichier_connexion(_DIR_CONNECT . $sup_db . '.php', $conn);

	return '<div style="background-color: #eeeeee">' . $res . '</div>';
}

// http://doc.spip.org/@install_etape_sup2_dist
function install_etape_sup2_dist()
{
	$adresse_db = _request('adresse_db');
	if (!$adresse_db AND defined('_INSTALL_HOST_DB'))
		$adresse_db =_INSTALL_HOST_DB;

	$login_db = _request('login_db');
	if (!$login_db AND defined('_INSTALL_USER_DB'))
		$login_db = _INSTALL_USER_DB;

	$pass_db = _request('pass_db');
	if (!$pass_db  AND defined('_INSTALL_PASS_DB'))
		$pass_db  = _INSTALL_PASS_DB;

	$server_db =_request('server_db');
	if (!$server_db AND  defined('_INSTALL_SERVER_DB'))
		$server_db = _INSTALL_SERVER_DB;

	// Ceci indique la base principale (passe en hidden)
	// pour qu'on la refuse comme choix de base secondaire a chaque tour.

	$sel_db =_request('sel_db');
	if (!$sel_db AND  defined('_INSTALL_NAME_DB'))
		$sel_db = _INSTALL_NAME_DB;

	// le choix
	$choix_db = _request('choix_db');
	if (is_numeric($choix_db))
		$choix_db = _request('table_new');

	if (!$choix_db)
		$res = "<!-- il ne sait pas ce qu'il veut -->";
	else {
		$res = install_bases_sup($adresse_db, $login_db, $pass_db,  $server_db, $choix_db);

		if ($res[1]=='!')
			$res .= "<p class='resultat'><b>"._T('avis_operation_echec')."</b></p>";

		else {
			$res =  "<p class='resultat'><b>"
			  . _T('install_base_ok', 
			       array('base' => $choix_db))
			  . "</b></p>"
			  . $res;
		}
	}

	$res .= generer_form_ecrire('admin_declarer',
			(defined('_INSTALL_NAME_DB') ? ''
			   :  ("\n<input type='hidden' name='sel_db' value='"
			       . $sel_db
			       . "' />"))
			. predef_ou_cache($adresse_db,$login_db,$pass_db, $server_db)
			. bouton_suivant());

	echo install_debut_html(_T('config_titre_base_sup'));
	echo $res;
	echo install_fin_html();
}

?>
