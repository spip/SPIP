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
include_spip('inc/acces');
include_spip('install/etape_2');

// Mise en place d'un fichier de connexion supplementaire
// Le serveur n'est pas forcement celui standard
// mais on se rabat dessus si on n'a pas mieux.

// http://doc.spip.org/@install_etape_sup1_dist
function install_etape_sup1_dist()
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
	// pour qu'on la refuse comme choix de base secondaire

	$sel_db =_request('sel_db');
	if (!$sel_db AND  defined('_INSTALL_NAME_DB'))
		$sel_db = _INSTALL_NAME_DB;

	echo install_debut_html(_T('config_titre_base_sup'));

	$link = spip_connect_db($adresse_db, 0, $login_db, $pass_db, '', $server_db);
	$GLOBALS['connexions'][$server_db][$GLOBALS['spip_sql_version']]
	= $GLOBALS['spip_' . $server_db .'_functions_' . $GLOBALS['spip_sql_version']];

	if ($link) {
		$GLOBALS['connexions'][$server_db] = $link;

		echo '<div style="background-color: #eeeeee">';
		echo "\n<!--\n", join(', ', $link), " $login_db ";
		echo join(', ', $GLOBALS['connexions'][$server_db]);
		echo "\n-->\n<p class='resultat'><b>";
		echo _T('info_connexion_ok'),"</b></p>\n";
		echo '<!-- ',  sql_version($server_db), ' -->' ;
		$l = bases_referencees();
		array_push($l, $sel_db);
		list(, $res) = install_etape_liste_bases($server_db, $login_db, $l);

		$hidden = predef_ou_cache($adresse_db,$login_db,$pass_db, $server_db)
		  . (defined('_INSTALL_NAME_DB')
		     ? ''
		     : ("\n<input type='hidden' name='sel_db' value='$sel_db' />\n"));

		echo install_etape_sup1_form($hidden, '', $res, 'sup2');
		echo '</div>';
	} else  {
		echo info_etape(_T('info_connexion_base'));
		echo "<p class='resultat'><b>",
		  _T('avis_connexion_echec_1'),
		  "</b></p>";
	}

	echo install_fin_html();
}

// http://doc.spip.org/@install_etape_sup1_form
function install_etape_sup1_form($hidden, $checked, $bases, $etape)
 {
	if ($bases) {
		$bases = "\n<fieldset><legend>"
		  . _T('config_titre_base_sup_choix')
		  . "</legend>\n"
		  . "<ul>\n<li>"
		  . join("</li>\n<li>",$bases)
		  . "</li>\n</ul><p>"
		  . _T('info_ou');
		$type = " type='radio'" . ($checked ? '' : " checked='checked'");

	} else {
		$bases = _T('config_erreur_base_sup') . '<br /><br >';
		$type = " type='hidden'";
	}

	return generer_form_ecrire('install', (
	  "\n<input type='hidden' name='etape' value='$etape' />"
	  . $hidden
	  . $bases
	  . "\n<input name=\"choix_db\" value='-1' id='nou'"
	  . $type
	  . " />\n"
	  . "<label for='nou'><b>"
	  ._T('config_choix_base_sup')
	  ."</b></label></p>\n"
	  . "\n<input type='text' name='table_new' class='text' size='20' /></p></fieldset>\n"
	  . bouton_suivant()));
}
?>
