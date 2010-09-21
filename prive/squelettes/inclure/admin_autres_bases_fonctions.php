<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function admin_autres_bases()
{
	include_spip('inc/install');

	$tables =  bases_referencees(_FILE_CONNECT_TMP);

	if ($tables)
		$tables = "<br /><br /><fieldset style='margin-bottom: 10px;'>"
		  .  "<legend>"._T('config_info_base_sup_disponibles')."</legend>"
		  . "<ul>\n<li>"
		  . join("</li>\n<li>",  $tables)
		  . "</li>\n</ul></fieldset>";
	else $tables ='';

	if (defined('_INSTALL_PASS_DB')) {

	  // Si l'utilisateur n'a pas a donner le mot de passe de la base SQL
	  // ce doit etre une installation mutualisee sur une meme base:
	  // interdiction de creer d'autres acces pour assure la confidentialite
		$form = '';

	} else {

	// Lire le fichier de connexion pour valeurs par defaut probables
		list($adresse_db, $login_db, $pass_db, $sel, $server_db)
		  = analyse_fichier_connection(_FILE_CONNECT);

	// Passer la base courante en Hidden pour ne pas la proposer
		$name_db = ("\n<input type='hidden' name='sel_db' value='" . $sel . "' />\n");
		// Dire que rien n'est predefini
		$predef = array(false, false, false, false);

		if (!autoriser('webmestre')){
			$login_db = $pass_db = "";
		}
		$form = install_connexion_form(array($adresse_db), array($login_db), array($pass_db), $predef, $name_db, 'sup1', false);
	}

	return $tables . $form;
}


?>