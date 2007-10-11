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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/headers');
include_spip('inc/acces');
include_spip('install/etape_2');

// Mise en place d'un fichier de configuration supplementaire

function install_etape_sup1_dist()
{
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

	$link = spip_connect_db($adresse_db, 0, $login_db, $pass_db, '', $server_db);

	$GLOBALS['connexions'][$server_db] = $link;

	echo install_debut_html();

	echo "\n<!--\n", join(', ', $link), " $login_db ";
	$db_connect = 0; // revoirfunction_exists($ferrno) ? $ferrno() : 0;
	echo join(', ', $GLOBALS['connexions'][$server_db]);
	echo "\n-->\n";

	if (($db_connect=="0") && $link) {
		echo "<p class='resultat'><b>"._T('info_connexion_ok')."</b></p>\n";
		spip_connect_db($adresse_db, 0, $login_db, $pass_db, '',$server_db);

		echo "\n", '<!-- ',  sql_version($server_db), ' -->' ;
		$l = bases_referencees();
		array_push($l, $sel_db);
		list(, $res) = install_etape_liste_bases($server_db, $l);

		$hidden = predef_ou_cache($adresse_db,$login_db,$pass_db, $server_db)
		  . (defined('_INSTALL_NAME_DB')
		     ? ''
		     : ("\n<input type='hidden' name='sel_db' value='" . _request('sel_db') . "' />\n"));



		echo install_etape_sup1_form($hidden, $checked, $res, 'sup2');
	} else  {
		echo info_etape(_T('info_connexion_base'));
		echo "<p class='resultat'><b>",
#		  _T('avis_connexion_echec_1'),
		  _L('La connexion &agrave; la base de donn&eacute;es a &eacute;chou&eacute;.'),
		  "</b></p>";
	}
	
	echo install_fin_html();
}

function install_etape_sup1_form($hidden, $checked, $bases, $etape)
 {
	return generer_form_ecrire('install', (
	  "\n<input type='hidden' name='etape' value='$etape' />"
	  . $hidden
	  .  "\n<fieldset><legend>"
	  . _L('Choisissez une base suppl&eacute;mentaire')
	  . "</legend>\n"
	  . "<ul>\n<li>"
	  . join("</li>\n<li>",$bases)
	  . "</li>\n</ul>"
	  . bouton_suivant()));
}
?>
