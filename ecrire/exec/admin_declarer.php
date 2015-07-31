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
	echo $commencer_page(_T('titre_admin_tech'), "configuration", "base");

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
		$form = install_connexion_form(array($adresse_db), array($login_db), array($pass_db), $predef, $name_db, 'sup1');
	}

	return debut_cadre_trait_couleur('',true,'',_T('onglet_declarer_une_autre_base'))
	  . _T('config_info_base_sup')
	  . $tables
	  . $form
	  . fin_cadre_trait_couleur(true);
}

?>
