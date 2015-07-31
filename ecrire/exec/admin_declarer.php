<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/install');

// http://doc.spip.org/@exec_admin_declarer_dist
function exec_admin_declarer_dist()
{
	if (!autoriser('detruire')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_admin_effacer'), "configuration", "base");

	echo "\n<br /><br />";
	echo gros_titre(_T('titre_admin_effacer'),'',false);
	echo barre_onglets("administration", "declarer");

	echo debut_gauche('',true);
	echo debut_boite_info(true);

	echo _T('info_gauche_admin_effacer');

	echo fin_boite_info(true);
	
	echo creer_colonne_droite('',true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'admin_effacer'),'data'=>''));	  
	
	echo debut_droite('',true);

	echo autres_bases();

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'admin_effacer'),'data'=>''));	  

	echo fin_gauche(), fin_page();
	}
}

// http://doc.spip.org/@autres_bases
function autres_bases()
{
	$tables =  bases_referencees(_FILE_CONNECT_TMP);

	if ($tables)
		$tables = '<br /><br />'
		  .  _T('config_info_base_sup_disponibles')
		  . "<ul>\n<li>"
		  . join("</li>\n<li>",  $tables)
		  . "</li>\n</ul>";
	else $tables ='';

	list($adresse, $login, $pass, $sel, $server)
	= analyse_fichier_connection(_FILE_CONNECT);

	$adresse_db = defined('_INSTALL_HOST_DB') ? '' : $adresse;

	$login_db = defined('_INSTALL_USER_DB') ? '' : $login;

	$pass_db = defined('_INSTALL_PASS_DB') ? '' : $pass;

	$server_db = defined('_INSTALL_SERVER_DB') ? '' : $server;

	$hidden = defined('_INSTALL_NAME_DB')
		? ''
	: ("\n<input type='hidden' name='sel_db' value='" . $sel . "' />\n");

	return "<br ><div style='padding: 10px; border: 1px solid; text-align: left'>" 
	  .  _T('config_info_base_sup')
	  . $tables
	  .  install_connexion_form(array($adresse_db), array($login_db), array($pass_db), array($server_db), $hidden, 'sup1')
	  .  "</div>";
}
?>
